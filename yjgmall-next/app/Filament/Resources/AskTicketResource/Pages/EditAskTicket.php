<?php

namespace App\Filament\Resources\AskTicketResource\Pages;

use App\Filament\Resources\AskTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAskTicket extends EditRecord
{
    protected static string $resource = AskTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
