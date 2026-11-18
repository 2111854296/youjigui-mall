<?php

namespace App\Filament\Resources\AdBookingResource\Pages;

use App\Filament\Resources\AdBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListAdBookings extends ListRecords
{
    protected static string $resource = AdBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
