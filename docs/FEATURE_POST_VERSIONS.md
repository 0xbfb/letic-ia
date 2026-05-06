# FEATURE: Post Versions (GeneratedPost)

## Objetivo
Adicionar versionamento simples para `GeneratedPost`, garantindo rastreabilidade mínima de alterações editoriais e de metadados relevantes no MVP.

## Regras de versionamento
- Cada versão pertence a um `GeneratedPost`.
- `version_number` é incremental por post (`1, 2, 3...`).
- A versão salva snapshot dos campos:
  - `title`
  - `content`
  - `meta_title`
  - `meta_description`
- `change_summary` pode ser:
  - informado manualmente no painel durante edição; ou
  - automático quando não houver texto informado.
- Não criar versão nova se não houver mudança relevante nos campos de snapshot.

## Quando uma versão é criada
1. **Criação por IA**
   - Ao finalizar geração de artigo (criação do `GeneratedPost`), criar automaticamente a versão 1.
2. **Edição manual no painel**
   - Ao salvar edição no `GeneratedPostResource`, criar uma nova versão apenas se houver mudança relevante.
3. **Metadados**
   - Alterações em `meta_title` e `meta_description` também contam como mudança relevante.

## Histórico no painel
- Histórico aparece como relação em `GeneratedPostResource` (`postVersions`).
- É possível visualizar dados completos de versões anteriores.
- Não há restauração de versão nesta etapa.
- Não há diff lado a lado nesta etapa.

## Como testar
1. Executar migrations.
2. Gerar artigo via fluxo existente de geração.
3. Validar que existe `post_versions.version_number = 1` para o post gerado.
4. Editar manualmente `title` ou `content` no painel e salvar.
5. Confirmar nova versão com `version_number` incrementado.
6. Editar apenas campo não relevante ao snapshot (ex.: status) e salvar.
7. Confirmar que **não** foi criada versão duplicada.
8. Editar `meta_title` ou `meta_description` e salvar.
9. Confirmar criação de nova versão.
10. Abrir histórico no painel e usar ação de visualizar versão.

## Limitações
- Sem restauração automática de versão.
- Sem comparação/diff entre versões.
- `change_summary` automático é genérico (não descreve diff semântico).
- Controle de concorrência simultânea não avançado (MVP).
