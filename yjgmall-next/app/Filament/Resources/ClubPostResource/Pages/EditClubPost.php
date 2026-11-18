<?php

namespace App\Filament\Resources\ClubPostResource\Pages;

use App\Filament\Resources\ClubPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClubPost extends EditRecord
{
    protected static string $resource = ClubPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
