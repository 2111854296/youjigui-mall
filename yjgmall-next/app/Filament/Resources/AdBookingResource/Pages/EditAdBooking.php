<?php

namespace App\Filament\Resources\AdBookingResource\Pages;

use App\Filament\Resources\AdBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdBooking extends EditRecord
{
    protected static string $resource = AdBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
