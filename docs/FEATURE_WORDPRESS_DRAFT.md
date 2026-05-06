# FEATURE: Envio de GeneratedPost aprovado para WordPress (draft)

## Objetivo
Permitir o envio de um `GeneratedPost` com status `approved` para o WordPress via REST API, criando **somente rascunho** (`status=draft`) e registrando trilha operacional em `wordpress_publications`.

## Configuração necessária no WordPress
1. Garantir que a REST API esteja acessível em: `https://SEU_SITE/wp-json/wp/v2/posts`.
2. Criar usuário com permissão de criar posts.
3. Gerar **Application Password** no perfil do usuário.
4. Usar HTTPS no `WORDPRESS_BASE_URL`.

## Variáveis de ambiente
Adicionar no `.env`:

- `WORDPRESS_BASE_URL=` (ex.: `https://exemplo.com`)
- `WORDPRESS_USERNAME=`
- `WORDPRESS_APPLICATION_PASSWORD=`

Arquivo de referência: `.env.example`.

## Payload enviado
Payload mínimo enviado para o endpoint `/wp-json/wp/v2/posts`:

```json
{
  "title": "...",
  "content": "...html...",
  "status": "draft",
  "slug": "...",
  "excerpt": "..."
}
```

### Conversão Markdown -> HTML
Se o conteúdo do `GeneratedPost` aparentar Markdown, o envio converte para HTML com `Str::markdown(...)` antes de chamar o WordPress.

## Implementação

### Banco de dados
- Migration: `create_wordpress_publications_table`
- Campos:
  - `generated_post_id`
  - `wordpress_post_id`
  - `wordpress_url`
  - `status` (`draft_created` ou `failed`)
  - `request_payload`
  - `response_payload`
  - `error_message`
  - `published_by`

### Model
- `App\Models\WordPressPublication`
- Casts JSON para `request_payload` e `response_payload`
- Relação `belongsTo` com `GeneratedPost`

### Services
- `WordPressClient`: valida configuração, envia request HTTP com basic auth e valida resposta.
- `WordPressPostPublisher`: monta payload, converte conteúdo, salva trilha e atualiza status do post para `sent_to_wordpress` em caso de sucesso.

### Job
- `SendPostToWordPressJob` (fila `wordpress`): orquestra envio e loga falhas com `post_id` e `operation`.

### Filament
No `GeneratedPostResource`, action **Enviar para WordPress**:
- aparece apenas para `status = approved`;
- executa `SendPostToWordPressJob::dispatchSync(...)`;
- mostra notificação de sucesso/erro;
- exibe URL do draft quando disponível.

## Tratamento de erros
São tratados com mensagem operacional clara:
- credenciais ausentes;
- `WORDPRESS_BASE_URL` inválida;
- erro HTTP no endpoint WordPress;
- resposta inesperada (sem campo `id`).

Falhas geram registro com `status=failed` em `wordpress_publications`.

## Como testar
1. Configurar variáveis `WORDPRESS_*` no `.env`.
2. Rodar migrations.
3. Garantir um `GeneratedPost` com status `approved`.
4. No painel Filament, clicar em **Enviar para WordPress**.
5. Verificar:
   - criação no WordPress como `draft`;
   - registro em `wordpress_publications` com payload de request/response;
   - `wordpress_post_id` salvo;
   - `wordpress_url` quando retornada pela API.

## Limitações
- Não publica automaticamente (somente draft).
- Não atualiza posts já enviados.
- Não realiza upload de imagem.
- Não envia categorias/tags avançadas.
- Não há retry customizado além do comportamento padrão de filas/jobs.
