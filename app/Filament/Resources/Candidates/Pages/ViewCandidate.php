<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Resources\Candidates\CandidateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCandidate extends ViewRecord
{
    protected static string $resource = CandidateResource::class;

    public function getSubheading(): ?string
    {
        return 'Lihat detail berkas kandidat dan isi keputusan lolos/tidak untuk divisi Anda.';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
