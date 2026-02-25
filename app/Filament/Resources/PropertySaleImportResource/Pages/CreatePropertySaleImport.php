<?php

namespace App\Filament\Resources\PropertySaleImportResource\Pages;

use App\Filament\Resources\PropertySaleImportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Jobs\ImportPropertySalesJob;
use Illuminate\Support\Facades\Auth;

class CreatePropertySaleImport extends CreateRecord
{
    protected static string $resource = PropertySaleImportResource::class;

    protected function afterCreate(): void
    {
        $this->record->update([
            'created_by' => Auth::id(),
        ]);

        ImportPropertySalesJob::dispatch($this->record);
    }
}
