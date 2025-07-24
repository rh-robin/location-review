<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Infolists\Components\ImageEntry;
use Filament\Tables;
use App\Models\Report;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Actions;
use App\Filament\Resources\ReportResource\Pages;
use Filament\Infolists\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $modelLabel = 'Report';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['review.user', 'review.location', 'user']); // eager load
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                    ->schema([
                        Forms\Components\Select::make('review_id')
                            ->label('Review')
                            ->relationship('review', 'id')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->columnSpan(['sm' => 1, 'md' => 1]),

                        Forms\Components\Select::make('user_id')
                            ->label('Reported By')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(['sm' => 1, 'md' => 1]),

                        Forms\Components\Select::make('reason')
                            ->label('Report Reason')
                            ->options([
                                'spam' => 'Spam',
                                'inappropriate' => 'Inappropriate Content',
                                'harassment' => 'Harassment',
                                'false_info' => 'False Information',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state !== 'other') {
                                    $set('other_reason', null);
                                }
                            })
                            ->columnSpan(['sm' => 1, 'md' => 1]),

                        Forms\Components\Textarea::make('other_reason')
                            ->label('Other Reason Details')
                            ->visible(fn(callable $get) => $get('reason') === 'other')
                            ->required(fn(callable $get) => $get('reason') === 'other')
                            ->maxLength(255)
                            ->columnSpan(['sm' => 1, 'md' => 1]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpan(['sm' => 1, 'md' => 2]),

                        Forms\Components\FileUpload::make('image')
                            ->label('Evidence Image')
                            ->image()
                            ->maxSize(2048)
                            ->disk('public')
                            ->directory('report-images')
                            ->visibility('public')
                            ->columnSpan(['sm' => 1, 'md' => 2]),
                    ]),

                Forms\Components\Section::make('Status')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Report Status')
                            ->options([
                                'pending' => 'Pending',
                                'reviewed' => 'Reviewed',
                                'resolved' => 'Resolved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required()
                            ->columnSpan(['sm' => 1, 'md' => 1]),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Report Date')
                            ->disabled()
                            ->default(now())
                            ->columnSpan(['sm' => 1, 'md' => 1]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('image')
                        ->height(200)
                        ->width('100%')
                        ->defaultImageUrl('/images/default-review.jpg')
                        ->label('Report Image')
                        ->disk('public')
                        ->url(fn($record) => $record->image ? asset('storage/' . $record->image) : null),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('user.name')
                            ->label('Reported By')
                            ->searchable()
                            ->extraAttributes(['style' => 'padding-left: 15px;'])
                            ->sortable(),

                        Tables\Columns\TextColumn::make('reason')
                            ->label('Reason')
                            ->formatStateUsing(fn(string $state) => str($state)->title()->replace('_', ' '))
                            ->searchable()
                            ->extraAttributes(['style' => 'padding-left: 15px;'])
                            ->sortable(),

                        Tables\Columns\TextColumn::make('description')
                            ->label('Description')
                            ->limit(50)
                            ->tooltip(fn($record) => $record->description)
                            ->extraAttributes(['style' => 'padding-left: 15px;'])
                            ->searchable(),

                        Tables\Columns\TextColumn::make('status')
                            ->label('Status')
                            ->badge()
                            ->colors([
                                'warning' => 'pending',
                                'success' => 'resolved',
                                'primary' => 'reviewed',
                                'danger' => 'rejected',
                            ])
                            ->extraAttributes(['style' => 'padding-left: 15px;'])
                            ->sortable(),
                    ])->space(2),
                ])->space(3),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'resolved' => 'Resolved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('reason')
                    ->options([
                        'spam' => 'Spam',
                        'inappropriate' => 'Inappropriate Content',
                        'harassment' => 'Harassment',
                        'false_info' => 'False Information',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Report Details')
                    ->modalWidth('lg'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Report Details')
                    ->heading('Report Overview')
                    ->icon('heroicon-o-flag')
                    ->extraAttributes(['class' => 'bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900'])
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Infolists\Components\Card::make([

                            Infolists\Components\TextEntry::make('user.name')
                                ->label('Reported By')
                                ->icon('heroicon-o-user')
                                ->columnSpan(['sm' => 1, 'md' => 1]),
                        ])->columns(['sm' => 1, 'md' => 2]),
                        Infolists\Components\TextEntry::make('review.user.name')
                            ->label('Review Author')
                            ->icon('heroicon-o-user-circle')
                            ->columnSpan(['sm' => 1, 'md' => 1]),

                        Infolists\Components\TextEntry::make('review.location.name')
                            ->label('Review Location')
                            ->icon('heroicon-o-map-pin')
                            ->columnSpan(['sm' => 1, 'md' => 1]),

                        // ✅ New - Review Comment
                        Infolists\Components\TextEntry::make('review.comment')
                            ->label('Review Comment')
                            ->icon('heroicon-o-chat-bubble-left')
                            ->extraAttributes(['class' => 'border-l-4 border-primary-400 pl-4 bg-white/5 rounded-md'])
                            ->columnSpan(['sm' => 1, 'md' => 1]),

                        // ✅ New - Review Images
                        Infolists\Components\RepeatableEntry::make('review.images')
                            ->label('Review Images')
                            ->schema([
                                ImageEntry::make('image')
                                    ->disk('public')
                                    ->height(180)
                                    ->defaultImageUrl('/images/default-review.jpg')
                                    ->action(
                                        Action::make('view_review_image')
                                            ->label('View Full Image')
                                            ->url(function ($record) {
                                                $imagePath = data_get($record, 'image');

                                                return $imagePath ? asset('storage/' . $imagePath) : null;
                                            })
                                            ->openUrlInNewTab()
                                    )
                                    ->extraAttributes(['class' => 'hover:scale-105 transition duration-200 cursor-pointer']),
                            ])
                            ->grid([
                                'default' => 2,
                                'md' => 3,
                                'lg' => 4,
                            ])
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('reason')
                            ->label('Reason')
                            ->formatStateUsing(fn(string $state) => str($state)->title()->replace('_', ' '))
                            ->columnSpan(['sm' => 1, 'md' => 2]),
                        Infolists\Components\TextEntry::make('other_reason')
                            ->label('Other Reason Details')
                            ->hidden(fn($record) => !$record->other_reason)
                            ->columnSpan(['sm' => 1, 'md' => 1]),
                        Infolists\Components\TextEntry::make('description')
                        ->label('Description')
                        ->hint('Details provided by the reporter about the issue.')
                        ->extraAttributes(['class' => 'p-4 bg-white/10 rounded-lg shadow-md text-white border border-gold-500/20'])
                        ->columnSpan(['sm' => 1, 'md' => 3]),
                        Infolists\Components\ImageEntry::make('image')
                            ->label('Evidence Image')
                            ->disk('public')
                            ->height(500)
                            ->defaultImageUrl('/images/default-review.jpg')
                            ->extraAttributes(['class' => 'hover:scale-105 transition-transform duration-200 cursor-pointer'])
                            ->action(
                                Action::make('view_image')
                                    ->label('View Full Image')
                                    ->url(fn($record) => $record->image ? asset('storage/' . $record->image) : null)
                                    ->openUrlInNewTab()
                            )
                            ->columnSpan(['sm' => 1, 'md' => 2]),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->colors([
                                'warning' => 'pending',
                                'success' => 'resolved',
                                'primary' => 'reviewed',
                                'danger' => 'rejected',
                            ])
                            ->icons([
                                'heroicon-o-clock' => 'pending',
                                'heroicon-o-check-circle' => 'resolved',
                                'heroicon-o-eye' => 'reviewed',
                                'heroicon-o-x-circle' => 'rejected',
                            ])
                            ->columnSpan(['sm' => 1, 'md' => 1]),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Report Date')
                            ->dateTime()
                            ->columnSpan(['sm' => 1, 'md' => 1]),
                        Infolists\Components\Actions::make([
                            Action::make('update_status')
                                ->label('Update Status')
                                ->form([
                                    Forms\Components\Select::make('status')
                                        ->label('New Status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'reviewed' => 'Reviewed',
                                            'resolved' => 'Resolved',
                                            'rejected' => 'Rejected',
                                        ])
                                        ->required(),
                                ])
                                ->action(function (array $data, $record) {
                                    $record->update(['status' => $data['status']]);
                                })
                                ->color('primary')
                                ->requiresConfirmation()
                                ->modalHeading('Update Report Status'),

                        ])->columnSpan(['sm' => 1, 'md' => 2]),
                    ]),

                Infolists\Components\Section::make('Replies')
                    ->heading('Conversation Thread')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->extraAttributes(['class' => 'bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900'])
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('replies')
                            ->label('Report Replies')
                            ->schema([
                                Infolists\Components\Split::make([
                                    Infolists\Components\TextEntry::make('user.name')
                                        ->label('Replied By')
                                        ->weight('bold'),
                                    Infolists\Components\TextEntry::make('created_at')
                                        ->label('Reply Date')
                                        ->dateTime()
                                        ->alignEnd(),
                                ])->from('md'),
                                Infolists\Components\TextEntry::make('reply')
                                    ->label('Reply')
                                    ->extraAttributes(['class' => 'border-l-4 border-primary-500 pl-4 ml-2']),
                            ])
                            ->columns(['sm' => 1, 'md' => 1])
                            ->columnSpan(['sm' => 1, 'md' => 2]),
                        Infolists\Components\Actions::make([
                            Action::make('add_reply')
                                ->label('Add Reply')
                                ->color('success')
                                ->icon('heroicon-o-plus-circle')
                                ->form([
                                    Textarea::make('reply')
                                        ->label('Reply')
                                        ->required()
                                        ->maxLength(1000)
                                        ->rows(4),
                                ])
                                ->action(function (array $data, $record) {
                                    $record->replies()->create([
                                        'user_id' => Auth::id(),
                                        'reply' => $data['reply'],
                                    ]);
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Add New Reply')
                                ->modalButton('Submit')
                                ->extraAttributes(['aria-label' => 'Add a reply to this report'])
                                ->visible(fn() => Auth::check()),
                        ])->columnSpan(['sm' => 1, 'md' => 2]),
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
            'index' => Pages\ListReports::route('/'),
            'view' => Pages\ViewReport::route('/{record}'),
        ];
    }
}
