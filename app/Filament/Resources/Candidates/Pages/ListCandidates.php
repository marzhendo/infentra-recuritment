<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Resources\Candidates\CandidateResource;
use App\Services\CandidateImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListCandidates extends ListRecords
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('csv_file')
                        ->label('File CSV')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain'])
                        ->required()
                        ->storeFiles(false),
                ])
                ->action(function (array $data, CandidateImporter $importer) {
                    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file */
                    $file = is_array($data['csv_file']) ? $data['csv_file'][0] : $data['csv_file'];
                    $path = $file->getRealPath();
                    $summary = $importer->import($path);

                    $msg = "Import selesai. Dibuat: {$summary['created']}, Diperbarui: {$summary['updated']}, Tetap: {$summary['unchanged']}, Dilewati: {$summary['skipped']}.";
                    if ($summary['errors_count'] > 0) {
                        $msg .= "\nError: " . implode("\n", $summary['errors']);
                    }

                    Notification::make()
                        ->title('Hasil Import')
                        ->body($msg)
                        ->success()
                        ->send();
                }),
        ];
    }
}
