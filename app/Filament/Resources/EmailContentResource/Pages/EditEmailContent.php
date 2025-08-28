<?php

namespace App\Filament\Resources\EmailContentResource\Pages;

use App\Filament\Resources\EmailContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailContent extends EditRecord
{
    protected static string $resource = EmailContentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
