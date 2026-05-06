<?php

namespace App\Filament\Widgets;

use App\Models\GeneratedPost;
use App\Models\LlmRun;
use App\Models\WordPressPublication;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOperationalTables extends TableWidget
{
    protected static ?int $sort = 4;

    public string $context = 'posts';

    public function getHeading(): ?string
    {
        return match ($this->context) {
            'errors' => 'Últimos erros operacionais',
            'wordpress' => 'Últimas publicações no WordPress',
            default => 'Últimos posts gerados',
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getContextQuery())
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns($this->getContextColumns());
    }

    protected function getContextQuery(): Builder
    {
        return match ($this->context) {
            'errors' => LlmRun::query()
                ->where('status', 'failed')
                ->orWhereNotNull('error')
                ->latest(),
            'wordpress' => WordPressPublication::query()->latest(),
            default => GeneratedPost::query()->latest(),
        };
    }

    /**
     * @return array<int, Tables\Columns\Column>
     */
    protected function getContextColumns(): array
    {
        return match ($this->context) {
            'errors' => [
                Tables\Columns\TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('operation')->label('Operação')->limit(30),
                Tables\Columns\TextColumn::make('model')->label('Modelo')->toggleable(),
                Tables\Columns\TextColumn::make('error')->label('Erro')->limit(90)->placeholder('-'),
            ],
            'wordpress' => [
                Tables\Columns\TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('generatedPost.title')->label('Post')->limit(40)->placeholder('-'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('error_message')->label('Erro')->limit(80)->placeholder('-'),
            ],
            default => [
                Tables\Columns\TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('title')->label('Título')->limit(50),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('seo_score')->label('SEO')->placeholder('-'),
            ],
        };
    }
}
