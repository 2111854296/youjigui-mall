<?php

namespace App\Filament\Resources\InvestProjectResource\Pages;

use App\Filament\Resources\InvestProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvestProject extends EditRecord
{
    protected static string $resource = InvestProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
