<?php

namespace App\Filament\Resources\InterviewSlots\Pages;

use App\Filament\Resources\InterviewSlots\InterviewSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInterviewSlots extends ManageRecords
{
    protected static string $resource = InterviewSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
