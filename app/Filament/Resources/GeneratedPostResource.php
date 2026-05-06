<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeneratedPostResource\Pages;
use App\Filament\Resources\GeneratedPostResource\RelationManagers\PostVersionsRelationManager;
use App\Models\GeneratedPost;
use App\Services\Content\EditorialAuditService;
use App\Services\Content\MetadataGeneratorService;
use App\Services\Content\SeoAuditService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GeneratedPostResource extends Resource
{
    protected static ?string $model = GeneratedPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Conteúdo SEO';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->required()->maxLength(255),
            Forms\Components\Textarea::make('excerpt')->rows(3),
            Forms\Components\MarkdownEditor::make('content')->required()->columnSpanFull(),
            Forms\Components\KeyValue::make('faq_json')->label('FAQ JSON')->columnSpanFull(),
            Forms\Components\KeyValue::make('cta_json')->label('CTA JSON')->columnSpanFull(),
            Forms\Components\TextInput::make('status')->required()->maxLength(32),
            Forms\Components\TextInput::make('seo_score')->label('SEO Score')->numeric()->readOnly(),
            Forms\Components\TextInput::make('tone_score')->label('Tone Score')->numeric()->readOnly(),
            Forms\Components\TextInput::make('readability_score')->label('Readability Score')->numeric()->readOnly(),
            Forms\Components\Textarea::make('latestSeoAudit.checks_json')
                ->label('SEO Checks (última auditoria)')
                ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                ->rows(10)
                ->readOnly()
                ->dehydrated(false)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('latestEditorialAudit.checks_json')
                ->label('Auditoria editorial: checks (última execução)')
                ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                ->rows(10)
                ->readOnly()
                ->dehydrated(false)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('latestEditorialAudit.errors_json')
                ->label('Auditoria editorial: problemas (última execução)')
                ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                ->rows(6)
                ->readOnly()
                ->dehydrated(false)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('latestEditorialAudit.warnings_json')
                ->label('Auditoria editorial: sugestões (última execução)')
                ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                ->rows(6)
                ->readOnly()
                ->dehydrated(false)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('latestSeoAudit.warnings_json')
                ->label('SEO Warnings (última auditoria)')
                ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                ->rows(6)
                ->readOnly()
                ->dehydrated(false)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('latestSeoAudit.errors_json')
                ->label('SEO Errors (última auditoria)')
                ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]')
                ->rows(6)
                ->readOnly()
                ->dehydrated(false)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('change_summary')
                ->label('Resumo da alteração para histórico')
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
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('seo_score')->label('SEO Score')->sortable(),
            Tables\Columns\TextColumn::make('tone_score')->label('Tone')->sortable(),
            Tables\Columns\TextColumn::make('readability_score')->label('Readability')->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime('d/m/Y H:i')->sortable(),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('generate_seo_metadata')
                ->label('Gerar metadados SEO')
                ->icon('heroicon-o-sparkles')
                ->requiresConfirmation()
                ->action(function (GeneratedPost $record, MetadataGeneratorService $metadataGeneratorService): void {
                    $success = $metadataGeneratorService->generateForPost($record);

                    Notification::make()
                        ->title($success ? 'Metadados SEO gerados.' : 'Falha ao gerar metadados SEO.')
                        ->success($success)
                        ->danger(! $success)
                        ->send();
                }),
            Tables\Actions\Action::make('run_seo_checklist')
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
                }),
            Tables\Actions\Action::make('run_editorial_audit')
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
                }),
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
}
