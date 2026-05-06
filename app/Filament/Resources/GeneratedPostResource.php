<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeneratedPostResource\Pages;
use App\Models\GeneratedPost;
use App\Services\Content\MetadataGeneratorService;
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('contentBrief.title')->label('Briefing')->toggleable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
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
        ]);
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
