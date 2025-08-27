<?php

namespace App\Filament\Resources\UserLocationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\UserLocationResource;

class ListUserLocations extends ListRecords
{
    // Specify which resource this page belongs to
    protected static string $resource = UserLocationResource::class;
}
