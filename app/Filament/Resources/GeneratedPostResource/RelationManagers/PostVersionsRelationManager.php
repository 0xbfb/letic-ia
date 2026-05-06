<?php

namespace App\Filament\Resources\GeneratedPostResource\RelationManagers;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'postVersions';

    protected static ?string $recordTitleAttribute = 'version_number';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('version_number')->disabled(),
            TextInput::make('title')->disabled()->columnSpanFull(),
            Textarea::make('meta_title')->disabled()->rows(2)->columnSpanFull(),
            Textarea::make('meta_description')->disabled()->rows(3)->columnSpanFull(),
            Textarea::make('change_summary')->disabled()->rows(3)->columnSpanFull(),
            Textarea::make('content')->disabled()->rows(16)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Histórico de versões')
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('version_number')->label('Versão')->sortable(),
                TextColumn::make('change_summary')->label('Resumo da mudança')->limit(80)->wrap(),
                TextColumn::make('created_at')->label('Criada em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Visualizar'),
            ])
            ->bulkActions([]);
    }
}
