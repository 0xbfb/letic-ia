# Feature: extração de texto para TXT, PDF e DOCX

## Dependências usadas

- `smalot/pdfparser` para extração de texto de PDFs com texto selecionável.
- `phpoffice/phpword` para leitura de conteúdo textual de arquivos `.docx`.

> Observação: se o projeto ainda não tiver dependências instaladas, execute `composer install` / `composer update` após atualizar o `composer.json`.

## Escopo implementado

- Extração de texto para:
  - `txt`
  - `pdf`
  - `docx`
- Persistência do texto extraído em `storage/app/extracted-documents/{document_id}.txt`.
- Atualização de status do documento (`uploaded` → `extracting` → `extracted` ou `failed`).
- Logs com:
  - tipo de arquivo
  - tempo de extração em ms
  - tamanho do texto extraído
  - falhas com mensagem de erro

## Limitações

- **Sem OCR** (fora do escopo desta etapa).
- PDFs escaneados (imagem sem camada de texto) podem retornar texto vazio.
- DOCX complexos (ex.: caixas de texto avançadas, objetos embutidos, tabelas muito estruturadas) podem não ter 100% do conteúdo recuperado por leitura simples de parágrafos.

## Como testar

1. Upload de um arquivo `txt`, `pdf` (com texto selecionável) e `docx` simples via painel.
2. Acionar **Extrair texto** no documento.
3. Verificar no banco:
   - `status = extracted` para arquivos válidos
   - `extracted_text_path` preenchido
4. Verificar no storage local:
   - existe `storage/app/extracted-documents/{id}.txt`
5. Verificar logs da aplicação para métricas e diagnóstico.

### Comandos simples de validação

```bash
# Reprocessar um job manualmente (ajuste o ID)
php artisan tinker --execute="App\\Jobs\\ExtractDocumentTextJob::dispatchSync(1);"

# Conferir arquivo extraído no storage
php artisan tinker --execute="echo Illuminate\\Support\\Facades\\Storage::disk('local')->get('extracted-documents/1.txt');"
```

## Exemplos de erro esperado

- Arquivo ausente no storage:
  - `Arquivo não encontrado em source-documents/...`
- PDF corrompido/inválido:
  - `Falha ao interpretar PDF. Verifique se o arquivo não está corrompido.`
- DOCX inválido/corrompido:
  - `Falha ao interpretar DOCX. Verifique se o arquivo é válido.`
- Documento sem texto útil:
  - `PDF sem texto útil para extração. PDF escaneado sem OCR pode retornar vazio.`
  - `DOCX sem texto útil para extração. PDF escaneado sem OCR pode retornar vazio.`
  - `TXT sem texto útil para extração. PDF escaneado sem OCR pode retornar vazio.`
