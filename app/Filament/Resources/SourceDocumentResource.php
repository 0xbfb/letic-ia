<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SourceDocumentResource\Pages;
use App\Models\SourceDocument;
use Filament\Forms;
use Filament\Forms\Form;
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
                Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->label('Descrição')->rows(3)->maxLength(2000),
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
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('file_path')->label('Caminho do arquivo')->copyable()->limit(50)->tooltip(fn ($record) => $record->file_path),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['uploaded' => 'uploaded']),
                Tables\Filters\SelectFilter::make('file_type')->options(['txt' => 'txt', 'pdf' => 'pdf', 'docx' => 'docx']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
