<?php

namespace App\Filament\Resources\InterviewSlots\Pages;

use App\Filament\Resources\InterviewSlots\InterviewSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInterviewSlots extends ManageRecords
{
    protected static string $resource = InterviewSlotResource::class;

    public function getSubheading(): ?string
    {
        return 'Penilai: Ketua Pelaksana dan koor dari divisi pilihan calon. Pilih aksi "Nilai" pada baris kandidat.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
