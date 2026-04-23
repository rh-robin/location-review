<?php

namespace App\Filament\Resources\EstimationRequestResource\Pages;

use App\Filament\Resources\EstimationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEstimationRequests extends ListRecords
{
    protected static string $resource = EstimationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'sale' => Tab::make('Sale')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estimation_type', 'sale')),
            'rent' => Tab::make('Rent')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estimation_type', 'rent')),
            'mortgage' => Tab::make('Mortgage')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estimation_type', 'mortgage')),
            'remortgage' => Tab::make('Remortgage')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estimation_type', 'remortgage')),
        ];
    }
}
