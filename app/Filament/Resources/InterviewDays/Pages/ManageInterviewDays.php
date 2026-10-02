<?php

namespace App\Filament\Resources\InterviewDays\Pages;

use App\Filament\Resources\InterviewDays\InterviewDayResource;
use App\Models\Candidate;
use App\Models\InterviewSlot;
use App\Services\ScheduleGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\HtmlString;

class ManageInterviewDays extends ManageRecords
{
    protected static string $resource = InterviewDayResource::class;

    public function getSubheading(): ?string
    {
        return 'Atur hari wawancara beserta jam istirahat, lalu generate slot wawancara.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Jadwal')
                ->icon('heroicon-o-cpu-chip')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Generate Jadwal Wawancara')
                ->modalDescription(function () {
                    $total = Candidate::count();
                    $hmif = Candidate::where('is_hmif', true)->count();
                    $locked = InterviewSlot::where('is_locked', true)->count();

                    // Count duplicates roughly for display
                    $candidates = Candidate::where('is_hmif', false)->get();
                    $names = [];
                    foreach ($candidates as $c) {
                        $norm = strtolower(preg_replace('/\s+/', ' ', trim($c->name)));
                        $names[$norm] = ($names[$norm] ?? 0) + 1;
                    }
                    $duplicates = count(array_filter($names, fn ($count) => $count > 1));

                    return new HtmlString("
                        <p>Total kandidat: <strong>{$total}</strong></p>
                        <p>Dilewati (HMIF): <strong>{$hmif}</strong></p>
                        <p>Dilewati (Duplikat): <strong>{$duplicates}</strong></p>
                        <p>Slot terkunci: <strong>{$locked}</strong></p>
                        <p class='mt-2 text-danger-600'>Aksi ini akan menghapus semua slot yang tidak terkunci dan membuat ulang jadwal.</p>
                    ");
                })
                ->action(function () {
                    try {
                        $generator = new ScheduleGenerator;
                        $reports = $generator->generate();

                        $lines = [];
                        foreach ($reports['days'] as $date => $rep) {
                            $lines[] = "<strong>{$date}</strong>: {$rep['sessions']} sesi ({$rep['first_start']} - {$rep['estimated_finish']})";
                            if ($rep['left_over'] > 0) {
                                $lines[] = "<span class='text-danger-600'>Sisa kandidat: {$rep['left_over']}</span>";
                            }
                        }

                        $lines[] = "<hr><p>Skipped HMIF: {$reports['skipped_hmif']}</p>";
                        $lines[] = "<p>Skipped Duplicates: {$reports['skipped_duplicate']}</p>";

                        Notification::make()
                            ->title('Jadwal Berhasil Dibuat')
                            ->body(new HtmlString(implode('<br>', $lines)))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Generate')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
