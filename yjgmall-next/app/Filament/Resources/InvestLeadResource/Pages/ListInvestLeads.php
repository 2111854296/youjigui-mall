<?php

namespace App\Filament\Resources\InvestLeadResource\Pages;

use App\Filament\Resources\InvestLeadResource;
use Filament\Resources\Pages\ListRecords;

class ListInvestLeads extends ListRecords
{
    protected static string $resource = InvestLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
