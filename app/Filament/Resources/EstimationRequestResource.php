<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstimationRequestResource\Pages;
use App\Models\EstimationRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EstimationRequestResource extends Resource
{
    protected static ?string $model = EstimationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $navigationLabel = 'Estimations';
    protected static ?string $modelLabel = 'Estimation Request';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\TextInput::make('estimation_type')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('postcode')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('address')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Data')
                    ->schema([
                        Forms\Components\KeyValue::make('input')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\KeyValue::make('output')
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('postcode')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('estimation_type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Date & Time'),
            ])
            ->defaultGroup('postcode')
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListEstimationRequests::route('/'),
            'view' => Pages\ViewEstimationRequest::route('/{record}'),
        ];
    }
}
