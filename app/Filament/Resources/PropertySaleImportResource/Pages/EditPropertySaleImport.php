<?php

namespace App\Filament\Resources\PropertySaleImportResource\Pages;

use App\Filament\Resources\PropertySaleImportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPropertySaleImport extends EditRecord
{
    protected static string $resource = PropertySaleImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
