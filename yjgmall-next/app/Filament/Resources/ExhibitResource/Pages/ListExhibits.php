<?php

namespace App\Filament\Resources\ExhibitResource\Pages;

use App\Filament\Resources\ExhibitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExhibits extends ListRecords
{
    protected static string $resource = ExhibitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
