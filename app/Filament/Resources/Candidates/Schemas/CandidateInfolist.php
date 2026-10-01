<?php

namespace App\Filament\Resources\Candidates\Schemas;

use App\Enums\DecisionStatus;
use App\Models\Decision;
use App\Models\Division;
use App\Models\Placement;
use App\Services\PlacementSuggester;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;

class CandidateInfolist
{
    public static function configure(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Data Kandidat')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')->label('Nama Lengkap'),
                            TextEntry::make('nim')->label('NIM'),
                            TextEntry::make('whatsapp')->label('WhatsApp'),
                            TextEntry::make('angkatan')->label('Angkatan'),
                            TextEntry::make('pilihan1.name')->label('Pilihan 1'),
                            TextEntry::make('pilihan2.name')->label('Pilihan 2'),
                            TextEntry::make('form_timestamp')->label('Waktu Submit')->dateTime(),
                        ]),
                    ]),

                Section::make('Berkas')
                    ->schema([
                        self::fileEntry('Sertifikat PKKMB', 'file_cert_pkkmb'),
                        self::fileEntry('Sertifikat WPI', 'file_cert_wpi'),
                        self::fileEntry('CV', 'file_cv'),
                        self::fileEntry('Portofolio', 'file_portfolio'),
                    ]),

                Section::make('Semua Data Form Asli')
                    ->schema([
                        TextEntry::make('form_data')
                            ->label('Data Asli')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return 'Tidak ada data.';
                                }
                                $out = [];
                                foreach ($state as $key => $val) {
                                    if (is_array($val)) {
                                        $val = json_encode($val);
                                    }
                                    $out[] = "**$key**: $val";
                                }

                                return implode("\n\n", $out);
                            })
                            ->markdown(),
                    ]),

                Section::make('Keputusan')
                    ->schema([
                        Grid::make(2)->schema([
                            self::decisionEntry(1),
                            self::decisionEntry(2),
                        ]),
                    ]),

                Section::make('Penempatan')
                    ->visible(fn () => auth()->user()->is_head_interviewer)
                    ->description(function ($record) {
                        $suggester = new PlacementSuggester;
                        $suggestion = $suggester->suggest($record);
                        $name = $suggestion ? $suggestion->name : 'Tidak ada rekomendasi';

                        return "Rekomendasi otomatis: {$name}";
                    })
                    ->schema([
                        TextEntry::make('placement_status')
                            ->label('Status Penempatan')
                            ->getStateUsing(fn ($record) => Placement::where('candidate_id', $record->id)->first() ? 'Sudah ditempatkan' : 'Belum ditempatkan')
                            ->badge()
                            ->color(fn ($state) => $state === 'Belum ditempatkan' ? 'warning' : 'success')
                            ->suffixAction(
                                Action::make('set_placement')
                                    ->label('Atur Penempatan')
                                    ->form([
                                        Select::make('division_id')
                                            ->label('Divisi')
                                            ->options(Division::pluck('name', 'id'))
                                            ->nullable(),
                                        Select::make('final_status')
                                            ->label('Status Akhir')
                                            ->options([
                                                'lolos' => 'Lolos',
                                                'tidak_lolos' => 'Tidak Lolos',
                                                'cadangan' => 'Cadangan',
                                            ])
                                            ->required(),
                                    ])
                                    ->fillForm(function ($record) {
                                        $p = Placement::where('candidate_id', $record->id)->first();
                                        if ($p) {
                                            return ['division_id' => $p->division_id, 'final_status' => $p->final_status];
                                        }
                                        $suggester = new PlacementSuggester;
                                        $s = $suggester->suggest($record);

                                        return ['division_id' => $s?->id, 'final_status' => $s ? 'lolos' : null];
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
                                    })
                            ),
                        TextEntry::make('placement_division')
                            ->label('Divisi Ditetapkan')
                            ->getStateUsing(fn ($record) => Placement::where('candidate_id', $record->id)->first()?->division?->name ?? '-'),
                    ]),
            ]);
    }

    private static function decisionEntry(int $pilihan): TextEntry
    {
        return TextEntry::make("decision_{$pilihan}")
            ->label(fn ($record) => 'Keputusan Pilihan '.$pilihan.' ('.($pilihan === 1 ? ($record->pilihan1->name ?? '-') : ($record->pilihan2->name ?? '-')).')')
            ->getStateUsing(function ($record) use ($pilihan) {
                $divId = $pilihan === 1 ? $record->pilihan_1_id : $record->pilihan_2_id;
                if (! $divId) {
                    return 'N/A';
                }
                $dec = Decision::where('candidate_id', $record->id)->where('division_id', $divId)->first();
                if (! $dec) {
                    return 'Belum ada keputusan';
                }

                return $dec->status->value.($dec->note ? " - {$dec->note}" : '');
            })
            ->suffixAction(
                Action::make("set_decision_{$pilihan}")
                    ->label('Ubah')
                    ->visible(function ($record) use ($pilihan) {
                        $divId = $pilihan === 1 ? $record->pilihan_1_id : $record->pilihan_2_id;
                        if (! $divId) {
                            return false;
                        }
                        $div = Division::find($divId);

                        return auth()->user()->can('create', [Decision::class, $record, $div]);
                    })
                    ->form([
                        Select::make('status')
                            ->label('Keputusan')
                            ->options([
                                DecisionStatus::Lolos->value => 'Lolos',
                                DecisionStatus::TidakLolos->value => 'Tidak Lolos',
                                DecisionStatus::Cadangan->value => 'Cadangan',
                            ])
                            ->required(),
                        Textarea::make('note')
                            ->label('Catatan (Opsional)'),
                    ])
                    ->fillForm(function ($record) use ($pilihan) {
                        $divId = $pilihan === 1 ? $record->pilihan_1_id : $record->pilihan_2_id;
                        $dec = Decision::where('candidate_id', $record->id)->where('division_id', $divId)->first();

                        return [
                            'status' => $dec?->status->value,
                            'note' => $dec?->note,
                        ];
                    })
                    ->action(function ($record, array $data) use ($pilihan) {
                        $divId = $pilihan === 1 ? $record->pilihan_1_id : $record->pilihan_2_id;
                        Decision::updateOrCreate(
                            ['candidate_id' => $record->id, 'division_id' => $divId],
                            [
                                'status' => $data['status'],
                                'note' => $data['note'],
                                'decided_by' => auth()->id(),
                            ]
                        );
                    })
            );
    }

    private static function fileEntry(string $label, string $field): ViewEntry
    {
        return ViewEntry::make($field)
            ->label($label)
            ->view('filament.infolists.components.drive-file');
    }
}
