# FEATURE: Seeders de desenvolvimento

## Objetivo
Criar dados fake simples para validar o fluxo do MVP localmente sem dependência de APIs externas.

## Dados criados
O comando `php artisan db:seed` passa a executar os seeders abaixo:

- `UserSeeder`
  - cria/atualiza 1 usuário admin local.
- `SourceDocumentSeeder`
  - cria 2 `source_documents` fake;
  - cria 3 `document_chunks` por documento (6 chunks no total);
  - `embedding` é salvo como `null` para evitar acoplamento com dimensão de vetor.
- `ContentBriefSeeder`
  - cria 2 `content_briefs` com palavras-chave principais distintas;
  - associa briefings aos documentos via tabela pivot.
- `GeneratedPostSeeder`
  - cria 4 `generated_posts` com distribuição de status:
    - 2 `needs_review`
    - 1 `approved`
    - 1 `changes_requested`
  - cria 1 `seo_audit` por post com scores e warnings/errors simulados.

## Credenciais locais
Por padrão, o admin local é criado com:

- Email: `admin.local@leticia-seo.test`
- Senha: `dev-only-change-me`

É possível sobrescrever via variáveis de ambiente:

- `DEV_ADMIN_NAME`
- `DEV_ADMIN_EMAIL`
- `DEV_ADMIN_PASSWORD`

## Comandos
```bash
php artisan db:seed
```

Se necessário, reset completo:

```bash
php artisan migrate:fresh --seed
```

## Cuidados para produção
- Esses seeders são para **desenvolvimento local** e homologação controlada.
- Não usar senha padrão em ambientes públicos.
- Não incluir dados reais de clientes, documentos sensíveis ou credenciais.
- Seeders não fazem chamadas reais para OpenAI nem WordPress.
- Em produção, usar processo de criação de usuários e dados por fluxo administrativo seguro.
