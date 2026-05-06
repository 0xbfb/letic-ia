# MVP Release Checklist (Inicial)

> Objetivo: consolidar a qualidade mínima para decisão de release do **leticia-seo-mvp** sem adicionar novas features.

## Como usar este checklist
- Status sugeridos:
  - `[x]` pronto/validado
  - `[!]` pronto com ressalva (risco conhecido)
  - `[ ]` pendente/bloqueante
- Este checklist deve ser revisado por produto + engenharia + operação editorial antes do go-live.

---

## Ambiente
- [x] `.env.example` existe com variáveis principais de LLM e WordPress.
- [x] Comandos essenciais de setup local e Docker estão documentados no `README.md`.
- [x] `APP_DEBUG` deve estar **desligado em produção** (`APP_DEBUG=false`) e essa exigência está documentada neste checklist.
- [x] APIs externas são configuráveis por variáveis de ambiente (OpenAI/WordPress).
- [x] Comandos de validação/release mínimo documentados:
  - `composer validate`
  - `php artisan config:clear`
  - `php artisan cache:clear`
  - `php artisan route:list`
  - `php artisan test`

## Segurança
- [x] Não há `.env` real versionado no repositório.
- [x] Não há credenciais reais em arquivos de configuração versionados (somente placeholders).
- [x] Chaves e senhas sensíveis são esperadas apenas via ambiente (`OPENAI_API_KEY`, `WORDPRESS_APPLICATION_PASSWORD`).
- [!] `DEV_ADMIN_PASSWORD` em `.env.example` é valor de desenvolvimento e deve ser trocado em qualquer ambiente real.

## Banco de dados
- [x] Estrutura orientada a PostgreSQL + `pgvector` está documentada.
- [x] Critério de release exige migration limpa em base nova (`migrate` do zero).
- [x] Seeders são opcionais e **não** devem ser executados em produção sem plano/curadoria (`php artisan db:seed`).
- [x] Integridade e rastreabilidade são cobertas por entidades do domínio (documento, versões, auditoria, publicação).

## Filas
- [x] Filas de domínio estão definidas e documentadas: `documents`, `embeddings`, `generation`, `wordpress`, `default`.
- [x] Execução de worker está documentada no README.
- [x] Horizon está previsto e documentado para operação.
- [x] Critério operacional: jobs devem atualizar status inicial/final e falha.

## Storage
- [x] Diretórios de storage devem ter permissão de escrita para aplicação/worker.
- [x] Critério operacional: validar escrita/leitura de arquivos de upload antes de go-live.
- [!] Checklist não substitui validação de infraestrutura (ownership/permissões por ambiente).

## OpenAI/LLM
- [x] Provider inicial via `LLM_PROVIDER=openai`.
- [x] Modelo e dimensões de embedding configuráveis por ambiente.
- [x] Rastreabilidade de chamadas LLM prevista por `llm_runs` (status, duração, erro).
- [!] Risco de custo variável por volume/tamanho de documentos.
- [!] Risco de saída inválida/alucinação exige revisão humana obrigatória.

## WordPress
- [x] Integração configurável via `WORDPRESS_BASE_URL`, usuário e application password.
- [x] Fluxo prevê envio **somente como draft**.
- [x] Publicação automática final não faz parte do MVP.
- [!] Falhas HTTP/permissão no WordPress podem bloquear etapa final de entrega.

## Logs
- [x] Logs de aplicação e de execução de jobs/integrações devem ser consultáveis pela operação.
- [x] Critério de segurança: não registrar tokens/senhas em logs.
- [x] Critério de troubleshooting documentado para fila, embeddings, WordPress e LLM.

## Testes
- [x] Validação ponta a ponta considerada já executada para esta etapa.
- [x] Comandos de verificação técnica do projeto estão documentados no README.
- [x] Regra: testes não devem depender de APIs externas reais (usar mocks/fakes quando aplicável).

## UX
- [x] Fluxo editorial do MVP está definido com revisão humana obrigatória.
- [x] Status de pipeline e de geração devem estar claros para operação no painel.
- [!] UX é funcional para MVP, porém sem otimizações avançadas de produtividade editorial.

## Backup/rollback
- [x] Rollback mínimo de aplicação: reverter para commit/tag anterior estável.
- [x] Rollback mínimo de banco: restaurar backup lógico/físico do PostgreSQL anterior ao deploy.
- [x] Rollback mínimo operacional: pausar workers/Horizon, restaurar banco, reprocessar itens pendentes conforme necessidade.
- [!] Necessário procedimento operacional formal por ambiente (staging/produção) com responsáveis e janela.

## Critérios editoriais
- [x] Revisão humana é etapa obrigatória antes de envio externo.
- [x] Aprovação explícita antes de enviar para WordPress.
- [x] Saída no WordPress deve permanecer draft para inspeção final.
- [!] Conteúdo gerado por IA pode requerer ajustes substantivos de precisão, tom e conformidade.

## Pendências conhecidas
- [!] PDF escaneado sem OCR dedicado pode reduzir qualidade da extração.
- [!] Custo de API pode oscilar conforme volume de documentos e tamanho de prompts.
- [!] Alucinação/inconsistência factual do LLM continua sendo risco inerente.
- [!] Dependência de disponibilidade e permissões do WordPress alvo.
- [!] MVP não cobre multiempresa/SaaS, OCR avançado, automação de publicação final e frontend separado.

## Decisão de go/no-go

### Critérios de aceite (consolidação)
- [x] checklist de release existe
- [x] riscos estão documentados
- [x] comandos de release estão documentados
- [x] rollback mínimo está descrito
- [x] pendências conhecidas estão listadas
- [x] README aponta para o checklist
- [x] não há credenciais reais
- [x] o projeto está pronto para decisão go/no-go

### Recomendação
- **Recomendação atual:** **GO com ressalvas**.
- Condição para GO: manter revisão humana obrigatória, APP_DEBUG desligado em produção, monitoramento de filas/Horizon ativo e plano de rollback operacional acordado.
