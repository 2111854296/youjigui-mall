<?php

namespace App\Filament\Resources\HotwordResource\Pages;

use App\Filament\Resources\HotwordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHotword extends EditRecord
{
    protected static string $resource = HotwordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
