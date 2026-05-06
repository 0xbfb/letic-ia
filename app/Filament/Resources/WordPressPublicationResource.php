<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WordPressPublicationResource\Pages;
use App\Models\WordPressPublication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WordPressPublicationResource extends Resource
{
    protected static ?string $model = WordPressPublication::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'Observabilidade';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('generated_post_id')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\Textarea::make('error_message')->label('Erro')->rows(3)->columnSpanFull()->disabled(),
            Forms\Components\Textarea::make('request_payload')
                ->label('Request')
                ->formatStateUsing(fn ($state): string => json_encode(self::maskSensitiveData($state ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}')
                ->rows(12)
                ->columnSpanFull()
                ->disabled(),
            Forms\Components\Textarea::make('response_payload')
                ->label('Response')
                ->formatStateUsing(fn ($state): string => json_encode(self::maskSensitiveData($state ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}')
                ->rows(12)
                ->columnSpanFull()
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('generated_post_id')->label('Post ID')->sortable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('error_message')->label('Erro')->limit(80)->tooltip(fn (?string $state): ?string => $state),
            Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i:s')->sortable(),
        ])->actions([
            Tables\Actions\ViewAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWordPressPublications::route('/'),
            'view' => Pages\ViewWordPressPublication::route('/{record}'),
        ];
    }

    private static function maskSensitiveData(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        $sensitiveKeys = ['authorization', 'password', 'token', 'api_key', 'application_password'];
        $masked = [];

        foreach ($payload as $key => $value) {
            $normalized = strtolower((string) $key);
            $isSensitive = collect($sensitiveKeys)->contains(fn (string $needle): bool => str_contains($normalized, $needle));

            $masked[$key] = $isSensitive
                ? '***masked***'
                : (is_array($value) ? self::maskSensitiveData($value) : $value);
        }

        return $masked;
    }
}
