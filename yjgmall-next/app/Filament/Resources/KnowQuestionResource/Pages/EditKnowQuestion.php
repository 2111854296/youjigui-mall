<?php

namespace App\Filament\Resources\KnowQuestionResource\Pages;

use App\Filament\Resources\KnowQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKnowQuestion extends EditRecord
{
    protected static string $resource = KnowQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
