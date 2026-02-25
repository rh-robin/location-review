<?php

namespace App\Filament\Resources\PropertySaleImportResource\Pages;

use App\Filament\Resources\PropertySaleImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPropertySaleImports extends ListRecords
{
    protected static string $resource = PropertySaleImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
