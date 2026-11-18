<?php

namespace App\Filament\Resources\HotwordResource\Pages;

use App\Filament\Resources\HotwordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHotwords extends ListRecords
{
    protected static string $resource = HotwordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
