<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailContentResource\Pages;
use App\Filament\Resources\EmailContentResource\RelationManagers;
use App\Models\EmailContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;

class EmailContentResource extends Resource
{
    protected static ?string $model = EmailContent::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('company_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Company Name'),
                Forms\Components\TextInput::make('company_location')
                    ->required()
                    ->maxLength(255)
                    ->label('Company Location'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Company Name'),
                Tables\Columns\TextColumn::make('company_location')->label('Company Location'),
                Tables\Columns\TextColumn::make('updated_at')->label('Last Updated')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => self::getUrl('edit', ['record' => $record]))
            ])
            ->bulkActions([
                // No bulk actions needed for single row
            ]);
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
            'index' => Pages\ListEmailContents::route('/'),
            'edit' => Pages\EditEmailContent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id', 1);
    }
}
