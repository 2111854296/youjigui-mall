<?php

namespace App\Filament\Resources\GroupDealResource\Pages;

use App\Filament\Resources\GroupDealResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroupDeals extends ListRecords
{
    protected static string $resource = GroupDealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
