# Feature: Extração de Texto TXT

## Fluxo implementado

1. Documento é enviado e salvo em `storage/app/source-documents`.
2. Na listagem do Filament, a action **Extrair texto** aparece para documentos com status `uploaded` ou `failed`.
3. A action dispara o `ExtractDocumentTextJob`.
4. O job marca status `extracting`, processa o arquivo TXT via `DocumentExtractorService` e salva o resultado em:
   - `storage/app/extracted-documents/{document_id}.txt`
5. Ao finalizar com sucesso, o job atualiza:
   - `extracted_text_path`
   - `status = extracted`
6. Em erro, o job atualiza:
   - `status = failed`
   - `metadata.last_extraction_error` com mensagem e timestamp
7. O usuário pode abrir a action **Ver texto extraído** para visualizar o conteúdo em modal no Filament.

## Comandos e jobs envolvidos

- Job principal: `App\Jobs\ExtractDocumentTextJob`
- Serviço de extração: `App\Services\DocumentExtractorService`
- Dispatch manual via interface (action do resource):
  - Botão `Extrair texto` na tabela de `SourceDocumentResource`

Opcional para ambiente local com queue assíncrona:

```bash
php artisan queue:work
```

## Como testar manualmente

1. Criar um documento com arquivo `.txt` no painel Filament.
2. Confirmar que o status inicial é `uploaded`.
3. Clicar em **Extrair texto**.
4. Confirmar transição para `extracting` durante o processamento.
5. Confirmar status final `extracted`.
6. Verificar campo `extracted_text_path` no banco ou no registro.
7. Verificar arquivo gerado em `storage/app/extracted-documents/{id}.txt`.
8. Clicar em **Ver texto extraído** e validar o conteúdo.
9. Testar falha (ex.: remover arquivo físico do storage e acionar extração) e confirmar status `failed` e preenchimento de `metadata.last_extraction_error`.
10. Conferir logs em `storage/logs/laravel.log` para eventos de sucesso/erro.

## Problemas conhecidos

- Nesta etapa apenas `txt` é suportado para extração; `pdf` e `docx` retornam erro de tipo não suportado.
- Arquivos TXT muito grandes podem impactar memória/tempo, pois leitura é feita integralmente com `file_get_contents`.
- A action de visualização depende da existência física de `extracted_text_path` no disco `local`.
