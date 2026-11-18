<?php

namespace App\Filament\Resources\ExhibitResource\Pages;

use App\Filament\Resources\ExhibitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExhibit extends EditRecord
{
    protected static string $resource = ExhibitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
