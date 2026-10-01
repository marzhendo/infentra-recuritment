<?php

namespace App\Filament\Pages;

use App\Models\InterviewDay;
use App\Models\InterviewSlot;
use App\Models\Score;
use App\Services\ScoreSubmitter;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PanelWawancara extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $title = 'Panel Wawancara';

    protected static ?string $navigationLabel = 'Panel Wawancara';

    protected string $view = 'filament.pages.panel-wawancara';

    public $selectedDay;

    public function mount()
    {
        $today = now()->format('Y-m-d');
        $day = InterviewDay::whereDate('date', $today)->first() ?? InterviewDay::orderBy('date')->first();
        if ($day) {
            $this->selectedDay = $day->id;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $q = InterviewSlot::with(['candidate.pilihan1', 'candidate.pilihan2'])
                    ->whereNotNull('candidate_id')
                    ->orderBy('starts_at');

                if ($this->selectedDay) {
                    $q->where('interview_day_id', $this->selectedDay);
                }

                return $q;
            })
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Waktu')
                    ->formatStateUsing(fn ($record) => substr($record->starts_at, 0, 5)),
                TextColumn::make('candidate.display_name')
                    ->label('Kandidat')
                    ->description(fn ($record) => ($record->candidate->pilihan1->name ?? '-').' / '.($record->candidate->pilihan2->name ?? '-')),
                TextColumn::make('candidate.catatan')
                    ->label('Catatan'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $submitter = new ScoreSubmitter;
                        $user = auth()->user();
                        $count = Score::where('slot_id', $record->id)->where('interviewer_id', $user->id)->count();
                        if ($count === 0) {
                            return 'Belum dinilai';
                        }
                        if ($submitter->isComplete($record, $user)) {
                            return 'Lengkap';
                        }

                        return 'Sebagian';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Belum dinilai' => 'danger',
                        'Sebagian' => 'warning',
                        'Lengkap' => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('interview_day_id')
                    ->label('Hari Wawancara')
                    ->options(InterviewDay::orderBy('date')->get()->mapWithKeys(fn ($d) => [$d->id => $d->date->format('Y-m-d')]))
                    ->default(fn () => $this->selectedDay),
            ])
            ->recordAction('score')
            ->recordActions([
                Action::make('score')
                    ->label('Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->hidden(fn ($record) => ! auth()->user()->can('create', [Score::class, $record]))
                    ->modalHeading(fn ($record) => 'Nilai Kandidat: '.$record->candidate->display_name)
                    ->modalSubmitActionLabel('Simpan')
                    ->form(function ($record) {
                        $activeAspects = DB::table('rubric_aspects')->where('is_active', true)->get();
                        $user = auth()->user();

                        $components = [];

                        // Candidate summary and links
                        $links = [];
                        if ($record->candidate->file_cv) {
                            $links[] = "<a href='{$record->candidate->file_cv}' target='_blank' class='text-primary-600 underline'>CV</a>";
                        }
                        if ($record->candidate->file_portfolio) {
                            $links[] = "<a href='{$record->candidate->file_portfolio}' target='_blank' class='text-primary-600 underline'>Portofolio</a>";
                        }

                        $components[] = Placeholder::make('summary')
                            ->label('Ringkasan')
                            ->content(new HtmlString(
                                '<strong>Divisi:</strong> '.($record->candidate->pilihan1->name ?? '-').' (1), '.($record->candidate->pilihan2->name ?? '-').' (2)<br>'.
                                '<strong>Berkas:</strong> '.implode(', ', $links)
                            ));

                        // Show other interviewers' scores
                        $otherScores = Score::with(['interviewer', 'aspect'])
                            ->where('slot_id', $record->id)
                            ->where('interviewer_id', '!=', $user->id)
                            ->get();

                        if ($otherScores->isNotEmpty()) {
                            $othersHtml = "<ul class='list-disc pl-5'>";
                            foreach ($otherScores->groupBy('interviewer.name') as $interviewerName => $scores) {
                                $othersHtml .= "<li><strong>{$interviewerName}:</strong> ";
                                $scoreParts = [];
                                foreach ($scores as $s) {
                                    $scoreParts[] = "{$s->aspect->name} ({$s->value})";
                                }
                                $othersHtml .= implode(', ', $scoreParts);
                                $othersHtml .= '</li>';
                            }
                            $othersHtml .= '</ul>';

                            $components[] = Placeholder::make('other_scores')
                                ->label('Nilai Pewawancara Lain')
                                ->content(new HtmlString($othersHtml));
                        }

                        // Current user's scores
                        $myScores = Score::where('slot_id', $record->id)
                            ->where('interviewer_id', $user->id)
                            ->get()
                            ->keyBy('rubric_aspect_id');

                        foreach ($activeAspects as $aspect) {
                            $components[] = Radio::make("aspect_{$aspect->id}")
                                ->label($aspect->name)
                                ->options([
                                    1 => '1 - Sangat Kurang',
                                    2 => '2 - Kurang',
                                    3 => '3 - Cukup',
                                    4 => '4 - Baik',
                                    5 => '5 - Sangat Baik',
                                ])
                                ->inline()
                                ->default($myScores->get($aspect->id)?->value);
                        }

                        // We take the first note found for this user/slot, or empty
                        $components[] = Textarea::make('note')
                            ->label('Catatan (Opsional)')
                            ->default($myScores->first()?->note);

                        return $components;
                    })
                    ->action(function ($record, array $data) {
                        $submitter = new ScoreSubmitter;
                        $scores = [];
                        foreach ($data as $key => $value) {
                            if (str_starts_with($key, 'aspect_') && $value !== null) {
                                $aspectId = str_replace('aspect_', '', $key);
                                $scores[$aspectId] = (int) $value;
                            }
                        }

                        if (count($scores) > 0) {
                            $submitter->submit($record, auth()->user(), $scores, $data['note'] ?? null);
                        }
                    }),
            ]);
    }
}
