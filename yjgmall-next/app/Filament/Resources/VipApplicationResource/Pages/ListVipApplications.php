<?php

namespace App\Filament\Resources\VipApplicationResource\Pages;

use App\Filament\Resources\VipApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListVipApplications extends ListRecords
{
    protected static string $resource = VipApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
