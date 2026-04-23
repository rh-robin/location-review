<?php

namespace App\Filament\Resources\EstimationRequestResource\Pages;

use App\Filament\Resources\EstimationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEstimationRequest extends EditRecord
{
    protected static string $resource = EstimationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
