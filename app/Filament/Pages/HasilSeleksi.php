<?php

namespace App\Filament\Pages;

use App\Models\Candidate;
use App\Models\Placement;
use App\Models\Decision;
use App\Models\User;
use App\Services\PlacementSuggester;
use App\Services\ResultsQuery;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HasilSeleksi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'Hasil Seleksi';
    protected static ?string $navigationLabel = 'Hasil Seleksi';

    protected string $view = 'filament.pages.hasil-seleksi';

    public static function canAccess(): bool
    {
        return auth()->user() && in_array(auth()->user()->role?->value ?? auth()->user()->role, ['admin', 'koor']);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export_rekap')
                ->label('Export Rekap Nilai')
                ->action(fn () => $this->exportCsv('rekap'))
                ->visible(fn () => auth()->user()->role === \App\Enums\Role::Admin),
            \Filament\Actions\Action::make('export_penempatan')
                ->label('Export Penempatan')
                ->action(fn () => $this->exportCsv('penempatan'))
                ->visible(fn () => auth()->user()->role === \App\Enums\Role::Admin),
            \Filament\Actions\Action::make('export_divisi')
                ->label('Export Per Divisi')
                ->action(fn () => $this->exportCsv('divisi'))
                ->visible(fn () => auth()->user()->role === \App\Enums\Role::Admin),
        ];
    }

    public function table(Table $table): Table
    {
        $query = (new ResultsQuery)->query();

        if (auth()->user()->role === \App\Enums\Role::Koor) {
            $divId = auth()->user()->division_id;
            $query->where(function ($q) use ($divId) {
                $q->where('pilihan_1_id', $divId)
                  ->orWhere('pilihan_2_id', $divId);
            });
        }

        return $table
            ->query($query)
            ->defaultSort('average_primary', 'desc')
            ->recordClasses(fn ($record) => $record->is_hmif ? 'bg-gray-50 opacity-75' : null)
            ->contentGrid(['md' => 1])
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('angkatan')
                    ->label('Angkatan')
                    ->sortable(),
                TextColumn::make('pilihan1.name')
                    ->label('Pilihan 1')
                    ->sortable(),
                TextColumn::make('pilihan2.name')
                    ->label('Pilihan 2')
                    ->sortable(),
                TextColumn::make('average_primary')
                    ->label('Rata-rata Utama')
                    ->sortable(query: function (Builder $query, string $direction) {
                        $isPg = $query->getConnection()->getDriverName() === 'pgsql';
                        if ($isPg) {
                            return $query->orderByRaw('average_primary ' . $direction . ' NULLS LAST');
                        }
                        return $query->orderByRaw('average_primary IS NULL, average_primary ' . $direction);
                    }),
                TextColumn::make('average_all')
                    ->label('Rata-rata Semua')
                    ->sortable(query: function (Builder $query, string $direction) {
                        $isPg = $query->getConnection()->getDriverName() === 'pgsql';
                        if ($isPg) {
                            return $query->orderByRaw('average_all ' . $direction . ' NULLS LAST');
                        }
                        return $query->orderByRaw('average_all IS NULL, average_all ' . $direction);
                    }),
                TextColumn::make('decisions_summary')
                    ->label('Keputusan')
                    ->getStateUsing(function ($record) {
                        $p1 = $record->decisions->firstWhere('division_id', $record->pilihan_1_id);
                        $p2 = $record->decisions->firstWhere('division_id', $record->pilihan_2_id);
                        return 'P1: '.($p1 ? $p1->status->value : '-').' | P2: '.($p2 ? $p2->status->value : '-');
                    }),
                TextColumn::make('placement.division.name')
                    ->label('Penempatan')
                    ->getStateUsing(fn ($record) => $record->placement ? ($record->placement->division ? $record->placement->division->name . ' (' . $record->placement->final_status . ')' : '-') : '-'),
            ])
            ->filters([
                SelectFilter::make('pilihan_1_id')
                    ->label('Divisi (Pilihan 1)')
                    ->relationship('pilihan1', 'name'),
                SelectFilter::make('is_hmif')
                    ->label('HMIF')
                    ->options([
                        '1' => 'Ya',
                        '0' => 'Bukan',
                    ]),
                TernaryFilter::make('has_placement')
                    ->label('Status Penempatan')
                    ->queries(
                        true: fn (Builder $query) => $query->has('placement'),
                        false: fn (Builder $query) => $query->doesntHave('placement'),
                    ),
                SelectFilter::make('decision_status')
                    ->label('Status Keputusan')
                    ->options([
                        'lolos' => 'Lolos',
                        'cadangan' => 'Cadangan',
                        'tidak_lolos' => 'Tidak Lolos',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('decisions', fn($q) => $q->where('status', $data['value']));
                        }
                    }),
                TernaryFilter::make('not_yet_scored')
                    ->label('Belum Dinilai Penuh (Primary)')
                    ->queries(
                        true: function (Builder $query) {
                            $query->whereRaw('scorers_count < (SELECT COUNT(DISTINCT id) FROM users WHERE is_head_interviewer = 1 OR (role = \'koor\' AND division_id IN (candidates.pilihan_1_id, candidates.pilihan_2_id)))');
                        },
                        false: function (Builder $query) {
                            $query->whereRaw('scorers_count >= (SELECT COUNT(DISTINCT id) FROM users WHERE is_head_interviewer = 1 OR (role = \'koor\' AND division_id IN (candidates.pilihan_1_id, candidates.pilihan_2_id)))');
                        },
                    ),
            ])
            ->actions([
                Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => 'Detail: ' . $record->name_override ?? $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn($a) => $a->label('Tutup'))
                    ->modalContent(fn ($record) => view('filament.infolists.components.penilaian-dan-catatan', ['getRecord' => fn() => $record])),
                Action::make('tetapkan_keputusan')
                    ->label('Beri Keputusan')
                    ->icon('heroicon-o-pencil')
                    ->visible(fn () => auth()->user()->role === \App\Enums\Role::Koor)
                    ->form([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'lolos' => 'Lolos',
                                'cadangan' => 'Cadangan',
                                'tidak_lolos' => 'Tidak Lolos',
                            ])
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Koor')
                    ])
                    ->fillForm(function ($record) {
                        $divisionId = auth()->user()->division_id;
                        $decision = \App\Models\Decision::where('candidate_id', $record->id)
                            ->where('division_id', $divisionId)
                            ->first();
                        return [
                            'status' => $decision?->status?->value ?? $decision?->status,
                            'catatan' => $decision?->catatan,
                        ];
                    })
                    ->action(function ($record, array $data) {
                        $divisionId = auth()->user()->division_id;
                        \App\Models\Decision::updateOrCreate(
                            [
                                'candidate_id' => $record->id,
                                'division_id' => $divisionId,
                            ],
                            [
                                'status' => $data['status'],
                                'catatan' => $data['catatan'],
                                'decided_by' => auth()->id(),
                            ]
                        );
                    }),
                Action::make('tetapkan_penempatan')
                    ->label('Tetapkan Penempatan')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn () => auth()->user()->is_head_interviewer)
                    ->form([
                        Select::make('division_id')
                            ->label('Divisi')
                            ->options(\App\Models\Division::pluck('name', 'id'))
                            ->nullable(),
                        Select::make('final_status')
                            ->label('Status Akhir')
                            ->options([
                                'lolos' => 'Lolos',
                                'cadangan' => 'Cadangan',
                                'tidak_lolos' => 'Tidak Lolos',
                            ])
                            ->required(),
                    ])
                    ->fillForm(function ($record) {
                        if ($record->placement) {
                            return [
                                'division_id' => $record->placement->division_id,
                                'final_status' => $record->placement->final_status,
                            ];
                        }
                        $suggester = new PlacementSuggester;
                        $suggestion = $suggester->suggest($record);
                        return [
                            'division_id' => $suggestion?->id,
                            'final_status' => $suggestion ? 'lolos' : null,
                        ];
                    })
                    ->action(function ($record, array $data) {
                        Placement::updateOrCreate(
                            ['candidate_id' => $record->id],
                            [
                                'division_id' => $data['division_id'],
                                'final_status' => $data['final_status'],
                                'set_by' => auth()->id(),
                            ]
                        );
                    }),
            ])
            ->bulkActions([
                BulkAction::make('terapkan_saran')
                    ->label('Terapkan Saran')
                    ->visible(fn () => auth()->user()->is_head_interviewer)
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $suggester = new PlacementSuggester;
                        $count = 0;
                        foreach ($records as $record) {
                            $suggestion = $suggester->suggest($record);
                            if ($suggestion) {
                                // Double check if Pilihan 1 koor decided lolos (which PlacementSuggester already does)
                                Placement::updateOrCreate(
                                    ['candidate_id' => $record->id],
                                    [
                                        'division_id' => $suggestion->id,
                                        'final_status' => 'lolos',
                                        'set_by' => auth()->id(),
                                    ]
                                );
                                $count++;
                            }
                        }
                        \Filament\Notifications\Notification::make()
                            ->title("Berhasil menerapkan saran untuk $count kandidat.")
                            ->success()
                            ->send();
                    })
            ]);
    }

    private function neutraliseCsv(string|null $value): string
    {
        if ($value === null) return '';
        $value = (string) $value;
        if (preg_match('/^[=\+\-@]/', $value)) {
            $value = "'" . $value;
        }
        return $value;
    }

    public function exportCsv(string $type)
    {
        if (auth()->user()->role !== \App\Enums\Role::Admin) {
            abort(403);
        }

        DB::table('export_logs')->insert([
            'user_id' => auth()->id(),
            'export_type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $query = (new ResultsQuery)->query()->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"export_{$type}.csv\"",
        ];

        return new StreamedResponse(function () use ($query, $type) {
            $file = fopen('php://output', 'w');
            // BOM
            fwrite($file, "\xEF\xBB\xBF");

            if ($type === 'rekap') {
                fputcsv($file, ['Kandidat', 'Pewawancara', 'Jabatan', 'Aspect 1', 'Aspect 2', 'Aspect 3', 'Aspect 4', 'Aspect 5', 'Aspect 6', 'Aspect 7', 'Rata-rata', 'Catatan']);
                
                foreach ($query as $candidate) {
                    $notes = $candidate->notes->keyBy('author_id');
                    $scoresByAuthor = [];
                    
                    if ($candidate->slot) {
                        $scores = \App\Models\Score::with('interviewer')->where('slot_id', $candidate->slot->id)->get();
                        foreach ($scores as $score) {
                            $scoresByAuthor[$score->interviewer_id]['interviewer'] = $score->interviewer;
                            $scoresByAuthor[$score->interviewer_id]['scores'][$score->rubric_aspect_id] = $score->value;
                        }
                    }

                    $allAuthorIds = collect(array_keys($scoresByAuthor))->merge($notes->keys())->unique();

                    foreach ($allAuthorIds as $authorId) {
                        $interviewer = $scoresByAuthor[$authorId]['interviewer'] ?? User::find($authorId);
                        $s = $scoresByAuthor[$authorId]['scores'] ?? [];
                        $note = $notes->get($authorId)?->body;
                        $avg = count($s) > 0 ? array_sum($s)/count($s) : null;
                        
                        $row = [
                            $this->neutraliseCsv($candidate->display_name),
                            $this->neutraliseCsv($interviewer->name),
                            $this->neutraliseCsv($interviewer->jabatan),
                            $s[1] ?? '',
                            $s[2] ?? '',
                            $s[3] ?? '',
                            $s[4] ?? '',
                            $s[5] ?? '',
                            $s[6] ?? '',
                            $s[7] ?? '',
                            $avg !== null ? round($avg, 2) : '',
                            $this->neutraliseCsv($note),
                        ];
                        fputcsv($file, $row);
                    }
                }
            } elseif ($type === 'penempatan') {
                fputcsv($file, ['Nama', 'Angkatan', 'NIM', 'WhatsApp', 'Pilihan 1', 'Pilihan 2', 'Divisi Final', 'Status Final', 'Catatan Bersama']);
                foreach ($query as $candidate) {
                    $row = [
                        $this->neutraliseCsv($candidate->display_name),
                        $this->neutraliseCsv($candidate->angkatan),
                        $this->neutraliseCsv($candidate->nim),
                        $this->neutraliseCsv($candidate->whatsapp),
                        $this->neutraliseCsv($candidate->pilihan1?->name),
                        $this->neutraliseCsv($candidate->pilihan2?->name),
                        $this->neutraliseCsv($candidate->placement?->division?->name),
                        $this->neutraliseCsv($candidate->placement?->final_status),
                        $this->neutraliseCsv($candidate->catatan),
                    ];
                    fputcsv($file, $row);
                }
            } elseif ($type === 'divisi') {
                fputcsv($file, ['Divisi', 'Kandidat', 'Status']);
                $placements = Placement::with(['candidate', 'division'])->whereIn('final_status', ['lolos', 'cadangan'])->get();
                $grouped = $placements->groupBy('division.name');
                foreach ($grouped as $divName => $records) {
                    foreach ($records as $record) {
                        $row = [
                            $this->neutraliseCsv($divName),
                            $this->neutraliseCsv($record->candidate->display_name),
                            $this->neutraliseCsv($record->final_status),
                        ];
                        fputcsv($file, $row);
                    }
                }
            }

            fclose($file);
        }, 200, $headers);
    }
}
