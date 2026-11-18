<?php

namespace App\Filament\Resources\QuoteProductResource\Pages;

use App\Filament\Resources\QuoteProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuoteProducts extends ListRecords
{
    protected static string $resource = QuoteProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
