<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Review;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Infolists\Components\Grid;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use App\Filament\Resources\ReviewResource\Pages;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Resources\ReviewResource\RelationManagers;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('images.image')
                        ->height(200)
                        ->width('100%')
                        ->defaultImageUrl('/images/default-review.jpg')
                        ->label('Review Images')
                        ->limit(1)
                        ->disk('public')
                        ->url(fn($record) => $record->image ? asset($record->image) : null),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('location.name')
                            ->weight('bold')
                            ->size('lg'),

                        Tables\Columns\TextColumn::make('user.name')
                            ->icon('heroicon-m-user')
                            ->color('gray'),

                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('rating')
                                ->badge()
                                ->formatStateUsing(fn($state) => str_repeat('⭐', $state))
                                ->color('warning'),

                            Tables\Columns\TextColumn::make('created_at')
                                ->since()
                                ->color('gray')
                                ->size('sm'),
                        ]),

                        Tables\Columns\TextColumn::make('comment')
                            ->limit(100)
                            ->wrap()
                            ->color('gray'),
                    ])->space(2),
                ])->space(3),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ]),
                Tables\Filters\SelectFilter::make('location')
                    ->relationship('location', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Review Details')
                    ->modalWidth('lg'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Review Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('location.name')
                                    ->label('Location')
                                    ->icon('heroicon-m-map-pin')
                                    ->weight('bold'),

                                TextEntry::make('user.name')
                                    ->label('Reviewer')
                                    ->icon('heroicon-m-user')
                                    ->weight('bold'),

                                TextEntry::make('rating')
                                    ->label('Rating')
                                    ->formatStateUsing(fn($state) => str_repeat('⭐', $state) . " ({$state}/5)")
                                    ->color('warning'),

                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime()
                                    ->icon('heroicon-m-calendar'),
                            ]),
                    ]),

                Section::make('Review Images')
                    ->schema([
                        RepeatableEntry::make('images')
                            ->hiddenLabel()
                            ->schema([
                                ImageEntry::make('image')
                                    ->hiddenLabel()
                                    ->height(300)
                                    ->width('100%')
                                    ->disk('public'),
                            ])
                            ->columns(2)
                            ->grid(),
                    ])
                    ->visible(fn($record) => $record->images->count() > 0),

                Section::make('Comment')
                    ->schema([
                        TextEntry::make('comment')
                            ->hiddenLabel()
                            ->prose()
                            ->markdown(),
                    ])
                    ->visible(fn($record) => $record->comment),
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
            'index' => Pages\ListReviews::route('/'),
            'view' => Pages\ViewReview::route('/{record}'),
        ];
    }
}
