<?php

namespace App\Filament\Resources\EstimationRequestResource\Pages;

use App\Filament\Resources\EstimationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEstimationRequest extends ViewRecord
{
    protected static string $resource = EstimationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
