<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentBriefResource\Pages;
use App\Jobs\GenerateOutlineFromBriefJob;
use App\Models\ContentBrief;
use App\Models\SourceDocument;
use App\Services\Content\BriefingBuilderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class ContentBriefResource extends Resource
{
    protected static ?string $model = ContentBrief::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Conteúdo SEO';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(255),
            Forms\Components\TextInput::make('content_type')->label('Tipo de conteúdo')->maxLength(100),
            Forms\Components\TextInput::make('main_keyword')->label('Palavra-chave principal')->required()->maxLength(255),
            Forms\Components\TagsInput::make('secondary_keywords')->label('Palavras-chave secundárias')->placeholder('Digite e pressione Enter'),
            Forms\Components\Select::make('sourceDocuments')
                ->label('Documentos obrigatórios (seleção manual)')
                ->multiple()
                ->relationship('sourceDocuments', 'title', fn ($query) => $query->where('status', SourceDocument::STATUS_EMBEDDED))
                ->searchable()
                ->preload()
                ->helperText('Quando selecionados, a busca de contexto é restrita a estes documentos.'),
            Forms\Components\TextInput::make('target_audience')->label('Público-alvo')->required()->maxLength(255),
            Forms\Components\Select::make('search_intent')->label('Intenção de busca')->required()->options([
                'informational' => 'Informacional',
                'navigational' => 'Navegacional',
                'commercial' => 'Comercial',
                'transactional' => 'Transacional',
            ]),
            Forms\Components\TextInput::make('business_objective')->label('Objetivo de negócio')->required()->maxLength(255),
            Forms\Components\TextInput::make('tone_of_voice')->label('Tom de voz')->required()->maxLength(255),
            Forms\Components\TextInput::make('cta_goal')->label('Objetivo de CTA')->maxLength(255),
            Forms\Components\TextInput::make('minimum_words')->label('Mínimo de palavras')->numeric()->minValue(1),
            Forms\Components\TextInput::make('maximum_words')->label('Máximo de palavras')->numeric()->minValue(1),
            Forms\Components\TagsInput::make('mandatory_sources')->label('Fontes obrigatórias (legado)')->placeholder('URL, título ou referência'),
            Forms\Components\Textarea::make('notes')->label('Notas')->rows(4),
            Forms\Components\Select::make('status')->label('Status')->options([
                ContentBrief::STATUS_DRAFT => 'draft',
                ContentBrief::STATUS_READY_TO_GENERATE => 'ready_to_generate',
                ContentBrief::STATUS_GENERATING => 'generating',
                ContentBrief::STATUS_GENERATED_OUTLINE => 'generated_outline',
            ])->default(ContentBrief::STATUS_DRAFT)->required(),
            Forms\Components\Hidden::make('created_by'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('content_type')->label('Tipo')->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('main_keyword')->label('Palavra-chave principal')->searchable()->limit(50),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options([
                ContentBrief::STATUS_DRAFT => 'draft',
                ContentBrief::STATUS_READY_TO_GENERATE => 'ready_to_generate',
                ContentBrief::STATUS_GENERATING => 'generating',
                ContentBrief::STATUS_GENERATED_OUTLINE => 'generated_outline',
            ]),
            Tables\Filters\SelectFilter::make('content_type'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Action::make('previewContext')
                ->label('Pré-visualizar contexto')
                ->icon('heroicon-o-magnifying-glass')
                ->infolist(function (ContentBrief $record, BriefingBuilderService $builder): array {
                    $context = $builder->buildContext($record);

                    return [
                        TextEntry::make('query')->label('Query usada')->state($context['query']),
                        TextEntry::make('total_chunks')->label('Chunks retornados')->state((string) $context['total_chunks']),
                        RepeatableEntry::make('chunks')->label('Chunks')->state($context['chunks'])->schema([
                            TextEntry::make('source_document_title')->label('Documento'),
                            TextEntry::make('source_document_id')->label('ID documento'),
                            TextEntry::make('chunk_index')->label('Índice do chunk'),
                            TextEntry::make('distance')->label('Distância')->numeric(decimalPlaces: 6),
                            TextEntry::make('similarity')->label('Similaridade')->numeric(decimalPlaces: 6),
                            TextEntry::make('content')->label('Conteúdo')->columnSpanFull(),
                        ]),
                    ];
                })
                ->modalWidth('7xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fechar')
                ->action(fn () => null)
                ->color('gray')
                ->failureNotificationTitle('Falha ao montar contexto')
                ->successNotificationTitle('Contexto carregado'),
            Action::make('generateOutline')
                ->label('Gerar outline')
                ->icon('heroicon-o-sparkles')
                ->visible(fn (ContentBrief $record): bool => $record->status === ContentBrief::STATUS_READY_TO_GENERATE)
                ->requiresConfirmation()
                ->action(function (ContentBrief $record): void {
                    $record->update(['status' => ContentBrief::STATUS_GENERATING]);
                    GenerateOutlineFromBriefJob::dispatch($record->id);
                    Notification::make()->title('Geração de outline iniciada')->success()->send();
                }),
            Action::make('viewOutline')
                ->label('Ver outline')
                ->icon('heroicon-o-document-text')
                ->visible(fn (ContentBrief $record): bool => filled(data_get($record->metadata, 'outline')))
                ->infolist(fn (ContentBrief $record): array => [
                    TextEntry::make('h1')->label('H1')->state((string) data_get($record->metadata, 'outline.h1', '')),
                    TextEntry::make('intro_objective')->label('Objetivo da introdução')->state((string) data_get($record->metadata, 'outline.intro_objective', '')),
                    RepeatableEntry::make('sections')->label('Seções')->state(data_get($record->metadata, 'outline.sections', []))->schema([
                        TextEntry::make('heading')->label('Heading'),
                        TextEntry::make('objective')->label('Objetivo'),
                        TextEntry::make('key_points')->label('Pontos-chave')->listWithLineBreaks(),
                    ]),
                    TextEntry::make('cta_plan')->label('Plano de CTA')->state(json_encode(data_get($record->metadata, 'outline.cta_plan', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
                ])
                ->modalWidth('5xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fechar')
                ->action(fn () => null),
            Action::make('markReadyToGenerate')->label('Marcar como ready_to_generate')->icon('heroicon-o-play')
                ->visible(fn (ContentBrief $record): bool => $record->status !== ContentBrief::STATUS_READY_TO_GENERATE)
                ->requiresConfirmation()
                ->action(function (ContentBrief $record): void {
                    $record->update(['status' => ContentBrief::STATUS_READY_TO_GENERATE]);
                    Notification::make()->title('Briefing marcado como ready_to_generate')->success()->send();
                }),
            Action::make('markDraft')->label('Voltar para draft')->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn (ContentBrief $record): bool => $record->status !== ContentBrief::STATUS_DRAFT)
                ->requiresConfirmation()
                ->action(function (ContentBrief $record): void {
                    $record->update(['status' => ContentBrief::STATUS_DRAFT]);
                    Notification::make()->title('Briefing voltou para draft')->success()->send();
                }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentBriefs::route('/'),
            'create' => Pages\CreateContentBrief::route('/create'),
            'edit' => Pages\EditContentBrief::route('/{record}/edit'),
        ];
    }
}
