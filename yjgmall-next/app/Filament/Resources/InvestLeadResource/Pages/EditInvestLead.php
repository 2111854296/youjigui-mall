<?php

namespace App\Filament\Resources\InvestLeadResource\Pages;

use App\Filament\Resources\InvestLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvestLead extends EditRecord
{
    protected static string $resource = InvestLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
