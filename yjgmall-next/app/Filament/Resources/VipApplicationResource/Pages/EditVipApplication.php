<?php

namespace App\Filament\Resources\VipApplicationResource\Pages;

use App\Filament\Resources\VipApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVipApplication extends EditRecord
{
    protected static string $resource = VipApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->status !== 'approved') {
            return;
        }

        $this->record->loadMissing('grade', 'user');

        $grade = $this->record->grade;
        $user = $this->record->user;

        if (! $grade || ! $user) {
            return;
        }

        $user->update([
            'level' => $grade->level,
            'vip_expire_at' => now()->addYear(),
            'is_certified' => true,
        ]);
    }
}
