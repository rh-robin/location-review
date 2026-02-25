<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertySaleImportResource\Pages;
use App\Filament\Resources\PropertySaleImportResource\RelationManagers;
use App\Models\PropertySaleImport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;

class PropertySaleImportResource extends Resource
{
    protected static ?string $model = PropertySaleImport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('file_name')
                ->label('CSV File')
                ->disk('local')
                ->directory('property-imports')
                ->acceptedFileTypes(['text/csv'])
                ->rules(['required', 'file', 'max:204800']) // 200MB
                ->required(),

            TextInput::make('status')
                ->disabled()
                ->default('pending'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('inserted_rows'),
                TextColumn::make('created_at')->dateTime(),
                TextColumn::make('completed_at')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPropertySaleImports::route('/'),
            'create' => Pages\CreatePropertySaleImport::route('/create'),
            'edit' => Pages\EditPropertySaleImport::route('/{record}/edit'),
        ];
    }
}
