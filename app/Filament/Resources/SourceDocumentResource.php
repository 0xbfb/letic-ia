<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SourceDocumentResource\Pages;
use App\Jobs\ChunkDocumentJob;
use App\Jobs\ExtractDocumentTextJob;
use App\Jobs\GenerateDocumentEmbeddingsJob;
use App\Models\SourceDocument;
use App\Services\Documents\DocumentSearchService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SourceDocumentResource extends Resource
{
    protected static ?string $model = SourceDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Base de Conhecimento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->label('Título do documento')->required()->maxLength(255)->placeholder('Ex.: Guia completo de SEO local 2026'),
                Forms\Components\Textarea::make('description')->label('Descrição')->rows(3)->maxLength(2000)->placeholder('Contexto opcional para facilitar a reutilização editorial.'),
                Forms\Components\Select::make('source_type')->label('Tipo de fonte')->options(['upload' => 'Upload'])->default('upload')->required(),
                Forms\Components\FileUpload::make('file_path')
                    ->label('Arquivo')
                    ->required()
                    ->disk('local')
                    ->directory('source-documents')
                    ->acceptedFileTypes([
                        'text/plain',
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->helperText('Tipos suportados para extração: TXT, PDF (texto selecionável) e DOCX.')
                    ->validationMessages([
                        'required' => 'Envie um arquivo .txt, .pdf ou .docx.',
                    ])
                    ->preserveFilenames(),
                Forms\Components\Hidden::make('file_type')->required(),
                Forms\Components\Hidden::make('status')->default(SourceDocument::STATUS_UPLOADED),
                Forms\Components\KeyValue::make('metadata')->label('Metadados')->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('file_type')->label('Tipo')->badge()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => SourceDocument::statusOptions()[$state] ?? $state)->colors(SourceDocument::statusColors())->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('file_path')->label('Caminho do arquivo')->copyable()->limit(50)->tooltip(fn ($record) => $record->file_path),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(SourceDocument::statusOptions()),
                Tables\Filters\SelectFilter::make('file_type')->options(['txt' => 'txt', 'pdf' => 'pdf', 'docx' => 'docx']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('extractText')
                    ->label('Extrair texto')
                    ->icon('heroicon-o-bolt')
                    ->visible(fn (SourceDocument $record): bool => in_array($record->status, [SourceDocument::STATUS_UPLOADED, SourceDocument::STATUS_FAILED], true))
                    ->action(function (SourceDocument $record): void {
                        ExtractDocumentTextJob::dispatch($record->id);

                        Notification::make()->title('Extração iniciada')->success()->send();
                    }),

                Action::make('generateChunks')
                    ->label('Gerar chunks')
                    ->icon('heroicon-o-queue-list')
                    ->visible(fn (SourceDocument $record): bool => in_array($record->status, [SourceDocument::STATUS_EXTRACTED, SourceDocument::STATUS_FAILED], true))
                    ->action(function (SourceDocument $record): void {
                        ChunkDocumentJob::dispatch($record->id);

                        Notification::make()->title('Geração de chunks iniciada')->success()->send();
                    }),

                Action::make('generateEmbeddings')
                    ->label('Gerar embeddings')
                    ->icon('heroicon-o-cpu-chip')
                    ->visible(fn (SourceDocument $record): bool => $record->chunks()->count() > 0 && in_array($record->status, [SourceDocument::STATUS_CHUNKED, SourceDocument::STATUS_FAILED], true))
                    ->action(function (SourceDocument $record): void {
                        GenerateDocumentEmbeddingsJob::dispatch($record->id);

                        Notification::make()->title('Geração de embeddings iniciada')->success()->send();
                    }),

                Action::make('viewChunks')
                    ->label('Ver chunks')
                    ->icon('heroicon-o-list-bullet')
                    ->visible(fn (SourceDocument $record): bool => $record->chunks()->count() > 0)
                    ->modalHeading(fn (SourceDocument $record): string => 'Chunks: '.$record->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalContent(fn (SourceDocument $record) => view('filament.source-document.chunks-list', [
                        'chunks' => $record->chunks()->get(),
                    ])),

                Action::make('viewExtractedText')
                    ->label('Ver texto extraído')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (SourceDocument $record): bool => ! empty($record->extracted_text_path) && Storage::disk('local')->exists($record->extracted_text_path))
                    ->modalHeading(fn (SourceDocument $record): string => 'Texto extraído: '.$record->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalContent(fn (SourceDocument $record) => view('filament.source-document.extracted-text', [
                        'text' => Storage::disk('local')->get($record->extracted_text_path),
                    ])),

                Action::make('semanticSearchPreview')
                    ->label('Preview busca semântica')
                    ->icon('heroicon-o-magnifying-glass')
                    ->form([
                        Forms\Components\Textarea::make('query')
                            ->label('Consulta')
                            ->rows(3)
                            ->required(),
                        Forms\Components\TextInput::make('limit')
                            ->label('Limite')
                            ->numeric()
                            ->default(8)
                            ->minValue(1)
                            ->maxValue(30),
                    ])
                    ->action(function (array $data, DocumentSearchService $searchService): void {
                        $results = $searchService->search((string) $data['query'], (int) ($data['limit'] ?? 8));

                        if ($results->isEmpty()) {
                            Notification::make()
                                ->title('Nenhum chunk encontrado')
                                ->body('A busca semântica não encontrou chunks relevantes com embedding.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $preview = $results
                            ->map(fn ($chunk) => sprintf(
                                'Doc #%d | Chunk %d | Similaridade %.4f
%s',
                                $chunk->source_document_id,
                                $chunk->chunk_index,
                                (float) $chunk->similarity,
                                mb_strimwidth(preg_replace('/\s+/', ' ', $chunk->content), 0, 140, '...')
                            ))
                            ->implode("

");

                        Notification::make()
                            ->title('Resultados da busca semântica')
                            ->body($preview)
                            ->success()
                            ->persistent()
                            ->send();
                    }),

                Action::make('download')
                    ->label('Baixar/Abrir')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (SourceDocument $record): ?string => Storage::disk('local')->exists($record->file_path) ? Storage::disk('local')->url($record->file_path) : null)
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSourceDocuments::route('/'),
            'create' => Pages\CreateSourceDocument::route('/create'),
            'edit' => Pages\EditSourceDocument::route('/{record}/edit'),
        ];
    }
}
