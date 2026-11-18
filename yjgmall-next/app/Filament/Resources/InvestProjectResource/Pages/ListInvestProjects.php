<?php

namespace App\Filament\Resources\InvestProjectResource\Pages;

use App\Filament\Resources\InvestProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestProjects extends ListRecords
{
    protected static string $resource = InvestProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
