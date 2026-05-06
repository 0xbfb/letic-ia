<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeneratedPostResource\Pages;
use App\Filament\Resources\GeneratedPostResource\RelationManagers\PostVersionsRelationManager;
use App\Models\GeneratedPost;
use App\Models\LlmRun;
use App\Models\WordPressPublication;
use App\Jobs\SendPostToWordPressJob;
use App\Services\Content\PostVersionService;
use App\Services\Content\EditorialAuditService;
use App\Services\Content\MetadataGeneratorService;
use App\Services\Content\SeoAuditService;
use App\Services\WordPress\WordPressException;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class GeneratedPostResource extends Resource
{
    protected static ?string $model = GeneratedPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Conteúdo SEO';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados principais')
                ->schema([
                    Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(255),
                    Forms\Components\TextInput::make('slug')->required()->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->required()
                        ->label('Status de revisão')->options(GeneratedPost::reviewStatusOptions()),
                    Forms\Components\Textarea::make('excerpt')->label('Resumo')->rows(4)->columnSpanFull(),
                ])->columns(3),

            Forms\Components\Section::make('SEO')
                ->schema([
                    Forms\Components\TextInput::make('meta_title')->label('Meta title')->maxLength(255),
                    Forms\Components\Textarea::make('meta_description')->label('Meta description')->rows(3)->columnSpanFull(),
                    Forms\Components\TextInput::make('seo_score')->label('Score SEO')->numeric()->readOnly(),
                    Forms\Components\TextInput::make('tone_score')->label('Score editorial (tom)')->numeric()->readOnly(),
                    Forms\Components\TextInput::make('readability_score')->label('Score editorial (legibilidade)')->numeric()->readOnly(),
                ])->columns(3),

            Forms\Components\Section::make('Conteúdo')
                ->schema([
                    Forms\Components\MarkdownEditor::make('content')
                        ->label('Conteúdo (Markdown)')
                        ->required()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('FAQ')
                ->schema([
                    Forms\Components\KeyValue::make('faq_json')->label('FAQ')->columnSpanFull(),
                ]),

            Forms\Components\Section::make('CTAs')
                ->schema([
                    Forms\Components\KeyValue::make('cta_json')->label('CTAs')->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Auditorias')
                ->schema([
                    Forms\Components\Textarea::make('latestSeoAudit.checks_json')
                        ->label('Última auditoria SEO - checks')
                        ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                        ->rows(8)
                        ->readOnly()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('latestSeoAudit.warnings_json')
                        ->label('Última auditoria SEO - avisos')
                        ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                        ->rows(5)
                        ->readOnly()
                        ->dehydrated(false)
                        ->columnSpan(1),
                    Forms\Components\Textarea::make('latestSeoAudit.errors_json')
                        ->label('Última auditoria SEO - erros')
                        ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                        ->rows(5)
                        ->readOnly()
                        ->dehydrated(false)
                        ->columnSpan(1),
                    Forms\Components\Textarea::make('latestEditorialAudit.checks_json')
                        ->label('Última auditoria editorial - checks')
                        ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                        ->rows(8)
                        ->readOnly()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('latestEditorialAudit.warnings_json')
                        ->label('Última auditoria editorial - sugestões')
                        ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                        ->rows(5)
                        ->readOnly()
                        ->dehydrated(false),
                    Forms\Components\Textarea::make('latestEditorialAudit.errors_json')
                        ->label('Última auditoria editorial - problemas')
                        ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                        ->rows(5)
                        ->readOnly()
                        ->dehydrated(false),
                ])->columns(2),

            Forms\Components\Section::make('Versões')
                ->description('Histórico completo disponível no bloco de relacionamento abaixo do formulário.'),

            Forms\Components\Section::make('Logs LLM relacionados')
                ->schema([
                    Forms\Components\Placeholder::make('related_llm_runs')
                        ->label('Últimos logs (post e briefing)')
                        ->content(function (?GeneratedPost $record): string {
                            if (! $record) {
                                return 'Sem registro carregado.';
                            }

                            $relatedIds = [$record->id];
                            if ($record->content_brief_id) {
                                $relatedIds[] = $record->content_brief_id;
                            }

                            $runs = LlmRun::query()
                                ->where(function ($query) use ($record, $relatedIds) {
                                    $query->where('related_type', GeneratedPost::class)
                                        ->whereIn('related_id', $relatedIds)
                                        ->orWhere(function ($nested) use ($record) {
                                            $nested->where('related_type', 'App\\Models\\ContentBrief')
                                                ->where('related_id', $record->content_brief_id);
                                        });
                                })
                                ->latest()
                                ->limit(8)
                                ->get();

                            if ($runs->isEmpty()) {
                                return 'Nenhum llm_run relacionado encontrado.';
                            }

                            return $runs
                                ->map(fn (LlmRun $run): string => sprintf(
                                    '[%s] %s | op=%s | model=%s | duração=%sms | related=%s#%s',
                                    $run->created_at?->format('d/m/Y H:i:s') ?? '-',
                                    $run->status,
                                    $run->operation,
                                    $run->model,
                                    $run->duration_ms ?? '-',
                                    $run->related_type,
                                    $run->related_id,
                                ))
                                ->implode("\n");
                        }),
                ]),

            Forms\Components\Textarea::make('change_summary')
                ->label('Resumo da alteração para histórico de versões')
                ->rows(3)
                ->dehydrated(true)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('contentBrief.title')->label('Briefing')->toggleable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->formatStateUsing(fn (string $state): string => GeneratedPost::reviewStatusOptions()[$state] ?? $state)
                ->colors(GeneratedPost::reviewStatusColors())
                ->sortable(),
            Tables\Columns\TextColumn::make('seo_score')->label('SEO Score')->sortable(),
            Tables\Columns\TextColumn::make('tone_score')->label('Tone')->sortable(),
            Tables\Columns\TextColumn::make('readability_score')->label('Readability')->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('wordpressPublications.0.wordpress_url')
                ->label('URL WordPress (draft)')
                ->url(fn (?string $state): ?string => $state)
                ->openUrlInNewTab()
                ->placeholder('-')
                ->toggleable(),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            self::makeGenerateMetadataAction(),
            self::makeSeoChecklistAction(),
            self::makeEditorialAuditAction(),
            self::makeApproveAction(),
            self::makeRequestAdjustmentsAction(),
            self::makeBackToReviewAction(),
            self::makeSendToWordPressAction(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            PostVersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeneratedPosts::route('/'),
            'view' => Pages\ViewGeneratedPost::route('/{record}'),
            'edit' => Pages\EditGeneratedPost::route('/{record}/edit'),
        ];
    }

    public static function makeGenerateMetadataAction(): Action
    {
        return Action::make('generate_seo_metadata')
            ->label('Gerar metadados')
            ->icon('heroicon-o-sparkles')
            ->requiresConfirmation()
            ->action(function (GeneratedPost $record, MetadataGeneratorService $metadataGeneratorService): void {
                $success = $metadataGeneratorService->generateForPost($record);

                Notification::make()
                    ->title($success ? 'Metadados SEO gerados.' : 'Falha ao gerar metadados SEO.')
                    ->success($success)
                    ->danger(! $success)
                    ->send();
            });
    }

    public static function makeSeoChecklistAction(): Action
    {
        return Action::make('run_seo_checklist')
            ->label('Rodar checklist SEO')
            ->icon('heroicon-o-check-badge')
            ->requiresConfirmation()
            ->action(function (GeneratedPost $record, SeoAuditService $seoAuditService): void {
                $audit = $seoAuditService->runForPost($record);

                Notification::make()
                    ->title('Checklist SEO executado com sucesso.')
                    ->body('Score calculado: '.$audit->score)
                    ->success()
                    ->send();
            });
    }

    public static function makeEditorialAuditAction(): Action
    {
        return Action::make('run_editorial_audit')
            ->label('Rodar auditoria editorial')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->requiresConfirmation()
            ->action(function (GeneratedPost $record, EditorialAuditService $editorialAuditService): void {
                $audit = $editorialAuditService->runForPost($record);

                if ($audit === null) {
                    Notification::make()
                        ->title('Falha na auditoria editorial.')
                        ->body('A resposta do LLM foi inválida ou houve erro de execução. Verifique llm_runs.')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Auditoria editorial executada com sucesso.')
                    ->body('Score editorial: '.$audit->score)
                    ->success()
                    ->send();
            });
    }

    public static function makeApproveAction(): Action
    {
        return Action::make('approve_post')
            ->label('Aprovar')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (GeneratedPost $record): bool => in_array($record->status, [
                GeneratedPost::STATUS_GENERATED,
                GeneratedPost::STATUS_NEEDS_REVIEW,
            ], true))
            ->action(function (GeneratedPost $record): void {
                $seoErrors = data_get($record->latestSeoAudit, 'errors_json', []);
                $hasCriticalSeoErrors = is_array($seoErrors) && count($seoErrors) > 0;

                if (
                    blank($record->content)
                    || blank($record->title)
                    || blank($record->slug)
                    || blank($record->meta_description)
                    || $hasCriticalSeoErrors
                ) {
                    Notification::make()
                        ->title('Aprovação bloqueada.')
                        ->body('Preencha conteúdo, título, slug, meta description e revise erros críticos de SEO antes de aprovar. Rode a checklist SEO para validar.')
                        ->danger()
                        ->send();

                    return;
                }

                $metadata = $record->metadata ?? [];
                $metadata['approved_at'] = now()->toISOString();

                $record->update([
                    'status' => GeneratedPost::STATUS_APPROVED,
                    'approved_by' => auth()->id(),
                    'metadata' => $metadata,
                ]);

                Notification::make()->title('Post aprovado.')->success()->send();
            });
    }

    public static function makeRequestAdjustmentsAction(): Action
    {
        return Action::make('request_adjustments')
            ->label('Solicitar ajustes')
            ->icon('heroicon-o-pencil-square')
            ->requiresConfirmation()
            ->color('warning')
            ->form([
                Forms\Components\Textarea::make('adjustments_note')
                    ->label('Observação para ajustes')
                    ->required()
                    ->minLength(5)
                    ->rows(4),
            ])
            ->visible(fn (GeneratedPost $record): bool => in_array($record->status, [
                GeneratedPost::STATUS_GENERATED,
                GeneratedPost::STATUS_NEEDS_REVIEW,
                GeneratedPost::STATUS_APPROVED,
            ], true))
            ->action(function (GeneratedPost $record, array $data, PostVersionService $postVersionService): void {
                $metadata = $record->metadata ?? [];
                $reviewNotes = $metadata['review_notes'] ?? [];
                $reviewNotes[] = [
                    'type' => GeneratedPost::STATUS_CHANGES_REQUESTED,
                    'note' => $data['adjustments_note'],
                    'created_at' => now()->toISOString(),
                    'created_by' => auth()->id(),
                ];
                $metadata['review_notes'] = $reviewNotes;

                $record->update([
                    'status' => GeneratedPost::STATUS_CHANGES_REQUESTED,
                    'metadata' => $metadata,
                ]);

                $postVersionService->createVersionIfChanged(
                    $record->refresh(),
                    'Solicitação de ajustes: '.$data['adjustments_note']
                );

                Notification::make()->title('Ajustes solicitados.')->success()->send();
            });
    }

    public static function makeBackToReviewAction(): Action
    {
        return Action::make('back_to_review')
            ->label('Voltar para revisão')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (GeneratedPost $record): bool => in_array($record->status, [
                GeneratedPost::STATUS_CHANGES_REQUESTED,
                GeneratedPost::STATUS_APPROVED,
                GeneratedPost::STATUS_FAILED,
            ], true))
            ->action(function (GeneratedPost $record): void {
                $record->update(['status' => GeneratedPost::STATUS_NEEDS_REVIEW]);
                Notification::make()->title('Post retornou para revisão.')->success()->send();
            });
    }

    public static function makeSendToWordPressAction(): Action
    {
        return Action::make('send_to_wordpress')
            ->label('Enviar para WordPress')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->requiresConfirmation()
            ->visible(fn (GeneratedPost $record): bool => $record->status === GeneratedPost::STATUS_APPROVED)
            ->action(function (GeneratedPost $record): void {
                try {
                    SendPostToWordPressJob::dispatchSync($record->id, auth()->id());
                    $latestPublication = $record->refresh()->wordpressPublications()->first();

                    Notification::make()
                        ->title('Rascunho enviado ao WordPress.')
                        ->body($latestPublication?->wordpress_url ? 'URL: '.$latestPublication->wordpress_url : 'WordPress retornou sem URL pública para o draft.')
                        ->success()
                        ->send();
                } catch (WordPressException $exception) {
                    Notification::make()
                        ->title('Falha ao enviar para WordPress.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
