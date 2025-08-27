<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserLocationResource\Pages;
use App\Models\UserLocation;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ViewAction;

class UserLocationResource extends Resource
{
    // The model this resource is based on
    protected static ?string $model = UserLocation::class;

    // Icon for the navigation menu
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    // Label shown in the navigation menu
    protected static ?string $navigationLabel = 'User Role for Location';

    // Group the menu item under "User Management"
    //protected static ?string $navigationGroup = 'User Management';

    // Define the form (optional for now, can be expanded later)
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Add form fields here if you want to create/edit records later
            ]);
    }

    // Define the table structure
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Column for user's name, linked via the user relationship
                TextColumn::make('user.name')
                    ->label('User Name')
                    ->sortable()
                    ->searchable(),
                // Column for user's email
                TextColumn::make('user.email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                // Column for the role from user_locations
                TextColumn::make('role')
                    ->label('Role')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucfirst($state)), // e.g., 'former_tenant' becomes 'Former Tenant'
                // Column for location name, linked via the location relationship
                TextColumn::make('location.name')
                    ->label('Location Name')
                    ->sortable()
                    ->searchable(),
                // Column for status
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucfirst($state)), // e.g., 'pending' becomes 'Pending'
            ])
            ->actions([
                // Add a view button for each row to see details
                ViewAction::make(),
            ]);
    }

    // Link to the resource pages in the Pages subfolder
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserLocations::route('/'),
            'view' => Pages\ViewUserLocation::route('/{record}'),
        ];
    }
}
