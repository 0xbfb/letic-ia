# leticia-seo-mvp

MVP em Laravel para geração de conteúdo SEO com apoio de LLM, com revisão humana obrigatória e envio para WordPress **apenas como draft**.

## Requisitos
- PHP 8.2+
- Composer 2+
- PostgreSQL 15+ com extensão `vector`
- Redis
- Node.js (somente se for compilar assets)

## Setup local
1. Instale dependências:
   - `composer install`
2. Configure ambiente:
   - `cp .env.example .env`
   - Ajuste variáveis de banco, redis, OpenAI e WordPress.
3. Gere chave da aplicação:
   - `php artisan key:generate`
4. Suba banco/redis locais e rode migrations:
   - `php artisan migrate`
5. (Opcional) Seed de dados:
   - `php artisan db:seed`
6. Inicie app e workers:
   - `php artisan serve`
   - `php artisan queue:work --queue=documents,embeddings,generation,wordpress,default`
   - `php artisan horizon`

## Setup com Docker
1. Validar compose:
   - `docker compose config`
2. Subir containers:
   - `docker compose up -d --build`
3. Instalar dependências no app:
   - `docker compose exec app composer install`
4. Migrar banco:
   - `docker compose exec app php artisan migrate`
5. Seed (opcional):
   - `docker compose exec app php artisan db:seed`
6. Testes:
   - `docker compose exec app php artisan test`
7. Status do Horizon:
   - `docker compose exec app php artisan horizon:status`

## Variáveis importantes
- `OPENAI_API_KEY`: chave do provider LLM.
- `OPENAI_MODEL`: modelo padrão para geração.
- `WORDPRESS_BASE_URL`: URL base da API WordPress.
- `WORDPRESS_USERNAME` e `WORDPRESS_APPLICATION_PASSWORD`: credenciais de integração.

> Nunca commitar `.env` real ou credenciais.

## Fluxo operacional (resumo)
1. Cadastrar documento-fonte.
2. Extrair texto, gerar chunks e embeddings.
3. Criar briefing e gerar outline/artigo.
4. Executar auditorias e revisão humana.
5. Aprovar e enviar para WordPress como draft.

## Comandos de validação
- `composer validate`
- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan route:list`
- `php artisan test`

## Troubleshooting
- **Fila parada**: verifique `redis`, worker ativo e filas configuradas.
- **Erro de embeddings**: confirme extensão `vector` e configuração de dimensão.
- **Falha no WordPress**: valide URL, credenciais de aplicação e permissões do usuário.
- **Erro de OpenAI/LLM**: confira `OPENAI_API_KEY`, modelo e logs em `llm_runs`.

## Documentação técnica
- [Guia técnico da arquitetura](docs/TECHNICAL_GUIDE.md)

## Documentação complementar
- Checklist de release do MVP inicial: `docs/MVP_RELEASE_CHECKLIST.md`
- Veja `docs/` para detalhes funcionais por feature e guias técnicos.
