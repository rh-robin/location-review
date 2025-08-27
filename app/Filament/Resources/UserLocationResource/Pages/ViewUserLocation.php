<?php

namespace App\Filament\Resources\UserLocationResource\Pages;

use App\Filament\Resources\UserLocationResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUserLocation extends ViewRecord
{
    protected static string $resource = UserLocationResource::class;

    // Define the form schema for the status update modal
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ])
                    ->required()
                    ->default($this->record->status ?? 'pending'),
            ]);
    }

    // Eager load relationships to avoid null issues
    protected function getViewData(): array
    {
        return [
            'record' => $this->getRecord()->load(['user', 'user.reviews', 'location']),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Section for user information
                Section::make('User Information')
                    ->schema([
                        TextEntry::make('user.name')->label('Name')->default('N/A'),
                        TextEntry::make('user.email')->label('Email')->default('N/A'),
                        TextEntry::make('role')->label('Role'),
                        TextEntry::make('location.name')->label('Assigned Location')->default('N/A'),
                    ]),
                // Section for reviews related to this user and location
                Section::make('Reviews')
                    ->schema(function ($record) {
                        // Get reviews as a collection and filter by location_id
                        $reviews = $record->user?->reviewsForLocation($record->location_id)->get() ?? collect();
                        if ($reviews->isEmpty()) {
                            return [TextEntry::make('no_reviews')->label('Reviews')->state('No reviews available')];
                        }
                        return $reviews->map(function ($review) {
                            // Convert rating to star icons
                            $filledStars = str_repeat('⭐', $review->rating);
                            $starRating = $filledStars;

                            // Combine rating and comment
                            $displayText = "Rating: {$starRating}\nComment: {$review->comment}";

                            return TextEntry::make('review_' . $review->id)
                                ->label('Review')
                                ->state($displayText)
                                ->columnSpanFull();
                        })->all();
                    }),
                // Section for status display and edit action
                Section::make('Status')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Current Status')
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                    ])
                    ->headerActions([
                        Action::make('editStatus')
                            ->modalHeading('Edit Status')
                            ->modalSubmitActionLabel('Save')
                            ->form([
                                \Filament\Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'verified' => 'Verified',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->required()
                                    ->default($this->record->status ?? 'pending'),
                            ])
                            ->modalWidth('sm')
                            ->action(function (array $data): void {
                                $this->record->update(['status' => $data['status']]);
                                Notification::make()
                                    ->title('Status updated successfully')
                                    ->success()
                                    ->send();
                                $this->refreshFormData(['status']);
                            }),
                    ]),
            ]);
    }
}
