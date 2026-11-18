<?php

namespace App\Filament\Resources\ClubPostResource\Pages;

use App\Filament\Resources\ClubPostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClubPosts extends ListRecords
{
    protected static string $resource = ClubPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
