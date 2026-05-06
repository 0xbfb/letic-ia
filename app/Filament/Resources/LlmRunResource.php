<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LlmRunResource\Pages;
use App\Models\LlmRun;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LlmRunResource extends Resource
{
    protected static ?string $model = LlmRun::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'Observabilidade';
    protected static ?string $navigationLabel = 'LLM Runs';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('operation')->disabled(),
            Forms\Components\TextInput::make('provider')->disabled(),
            Forms\Components\TextInput::make('model')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\TextInput::make('duration_ms')->label('Duração (ms)')->disabled(),
            Forms\Components\TextInput::make('total_tokens')->disabled(),
            Forms\Components\TextInput::make('metadata.estimated_cost_usd')->label('Custo estimado (USD)')->disabled(),
            Forms\Components\Textarea::make('error')->rows(4)->columnSpanFull()->disabled(),
            Forms\Components\Textarea::make('metadata')
                ->label('Metadata')
                ->formatStateUsing(fn ($state): string => json_encode(self::maskSensitiveData($state ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}')
                ->rows(14)
                ->columnSpanFull()
                ->disabled(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('operation')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('provider')->badge()->sortable(),
                Tables\Columns\TextColumn::make('model')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('duration_ms')->label('Duração (ms)')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('total_tokens')->label('Tokens')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('metadata.estimated_cost_usd')->label('Custo est. (USD)')->toggleable(),
                Tables\Columns\TextColumn::make('error')->limit(80)->tooltip(fn (?string $state): ?string => $state)->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i:s')->sortable(),
            ])
            ->filters([
                SelectFilter::make('operation')->options([
                    'generate_embedding' => 'generate_embedding',
                    'generate_outline' => 'generate_outline',
                    'generate_article' => 'generate_article',
                    'generate_metadata' => 'generate_metadata',
                    'audit_editorial' => 'audit_editorial',
                ]),
                SelectFilter::make('status')->options([
                    'success' => 'success',
                    'failed' => 'failed',
                ]),
                SelectFilter::make('provider'),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('De'),
                        Forms\Components\DatePicker::make('until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLlmRuns::route('/'),
            'view' => Pages\ViewLlmRun::route('/{record}'),
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
