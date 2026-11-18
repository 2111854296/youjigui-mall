<?php

namespace App\Filament\Resources\KnowQuestionResource\Pages;

use App\Filament\Resources\KnowQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKnowQuestions extends ListRecords
{
    protected static string $resource = KnowQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
