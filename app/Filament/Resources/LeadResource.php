<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Actions\Action;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Leads & Inquiries';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Lead Overview')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'plan' => 'info',
                                'consultation' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('source')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Received At'),
                        TextEntry::make('user_role')
                            ->label('User Role')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->visible(fn (Lead $record) => $record->source === 'rent'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'new' => 'gray',
                                'contacted' => 'info',
                                'sent_out' => 'warning',
                                'completed' => 'success',
                                'not interested', 'invalid' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => str($state)->headline()),
                    ]),

                Section::make('Contact Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->placeholder('N/A'),
                        TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->placeholder('N/A'),
                        TextEntry::make('phone')
                            ->icon('heroicon-m-phone')
                            ->placeholder('N/A'),
                        TextEntry::make('address')
                            ->icon('heroicon-m-map-pin')
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                    ]),

                Grid::make(2)
                    ->schema([
                        Section::make('Consultation Specifics')
                            ->visible(fn (Lead $record) => $record->type === 'consultation')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('number_of_experts')
                                    ->label('Experts Requested'),
                                TextEntry::make('preferred_date')
                                    ->date(),
                                TextEntry::make('preferred_time'),
                                TextEntry::make('preferred_contact_method')
                                    ->badge()
                                    ->color('info'),
                            ]),

                        Section::make('Plan Specifics')
                            ->visible(fn (Lead $record) => $record->type === 'plan')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('planning_time')
                                    ->label('Timeline'),
                                TextEntry::make('note')
                                    ->columnSpanFull()
                                    ->placeholder('No notes provided'),
                            ]),
                    ]),

                Section::make('Calculation Data')
                    ->description('This data shows what the user entered and what the system estimated at the time of the lead submission.')
                    ->columns(2)
                    ->schema([
                        KeyValueEntry::make('calculation_input')
                            ->label('User Input Data')
                            ->keyLabel('Field Name')
                            ->valueLabel('Input Value'),
                        KeyValueEntry::make('calculation_output')
                            ->label('Estimated Results')
                            ->keyLabel('Metric')
                            ->valueLabel('Value'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'plan' => 'info',
                        'consultation' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('source')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user_role')
                    ->label('Role')
                    ->badge()
                    ->color('warning')
                    ->toggleable()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? ucfirst($state) : null)
                    ->placeholder('-'),
                TextColumn::make('name')
                    ->searchable()
                    ->placeholder('N/A'),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray',
                        'contacted' => 'info',
                        'sent_out' => 'warning',
                        'completed' => 'success',
                        'not interested', 'invalid' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->headline())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'plan' => 'Plan',
                        'consultation' => 'Consultation',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'sales' => 'Sales',
                        'rent' => 'Rent',
                        'mortgage' => 'Mortgage',
                        'remortgage' => 'Remortgage',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'sent_out' => 'Sent Out',
                        'completed' => 'Completed',
                        'not interested' => 'Not Interested',
                        'invalid' => 'Invalid',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('updateStatus')
                    ->label('Update Status')
                    ->icon('heroicon-m-chevron-right')
                    ->color('info')
                    ->form([
                        Select::make('status')
                            ->label('Lead Status')
                            ->options([
                                'new' => 'New',
                                'contacted' => 'Contacted',
                                'sent_out' => 'Sent Out',
                                'completed' => 'Completed',
                                'not interested' => 'Not Interested',
                                'invalid' => 'Invalid',
                            ])
                            ->required(),
                    ])
                    ->action(function (Lead $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            // 'create' => Pages\CreateLead::route('/create'), // No need to create leads manually
            'view' => Pages\ViewLead::route('/{record}'),
            // 'edit' => Pages\EditLead::route('/{record}/edit'), // Leads should be read-only mostly
        ];
    }
}
