<?php

namespace App\Filament\Pages;

use App\Models\CandidateNote;
use App\Models\InterviewDay;
use App\Models\InterviewSlot;
use App\Models\Score;
use App\Services\ScoreSubmitter;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PanelWawancara extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $title = 'Panel Wawancara';

    protected static ?string $navigationLabel = 'Panel Wawancara';

    protected string $view = 'filament.pages.panel-wawancara';

    public $selectedDay;

    public function mount()
    {
        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $day = InterviewDay::whereDate('date', $today)->first() ?? InterviewDay::orderBy('date')->first();
        if ($day) {
            $this->selectedDay = $day->id;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->query(function () {
                $q = InterviewSlot::with(['candidate.pilihan1', 'candidate.pilihan2'])
                    ->whereNotNull('candidate_id')
                    ->orderBy('starts_at');

                if ($this->selectedDay) {
                    $q->where('interview_day_id', $this->selectedDay);
                }

                return $q;
            })
            ->recordClasses(function ($record) {
                $now = now()->timezone('Asia/Jakarta');
                $day = InterviewDay::find($record->interview_day_id);
                if (!$day) return null;
                $slotStart = \Carbon\Carbon::parse($day->date->format('Y-m-d') . ' ' . $record->starts_at, 'Asia/Jakarta');
                $slotEnd = \Carbon\Carbon::parse($day->date->format('Y-m-d') . ' ' . $record->ends_at, 'Asia/Jakarta');
                if ($now->between($slotStart, $slotEnd)) {
                    return 'bg-primary-50 dark:bg-primary-900/10 border-l-4 border-primary-600';
                }
                return null;
            })
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Waktu')
                    ->formatStateUsing(fn ($record) => substr($record->starts_at, 0, 5)),
                TextColumn::make('candidate.display_name')
                    ->label('Kandidat')
                    ->description(fn ($record) => ($record->candidate->pilihan1->name ?? '-').' / '.($record->candidate->pilihan2->name ?? '-')),
                TextColumn::make('candidate.catatan')
                    ->label('Catatan umum'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->candidate && $record->candidate->is_hmif) {
                            return 'HMIF (Tanpa Nilai)';
                        }
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
                        'HMIF (Tanpa Nilai)' => 'gray',
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
                    ->label(fn ($record) => auth()->user()->can('create', [Score::class, $record]) ? 'Nilai' : 'Hanya lihat')
                    ->icon(fn ($record) => auth()->user()->can('create', [Score::class, $record]) ? 'heroicon-o-pencil-square' : 'heroicon-o-eye')
                    ->color(fn ($record) => auth()->user()->can('create', [Score::class, $record]) ? 'primary' : 'gray')
                    ->modalHeading(fn ($record) => 'Nilai Kandidat: '.$record->candidate->display_name)
                    ->modalSubmitActionLabel(fn($record) => auth()->user()->can('create', [Score::class, $record]) ? 'Simpan' : 'Tutup')
                    ->modalCancelAction(fn($action) => $action->hidden())
                    ->form(function ($record) {
                        $activeAspects = DB::table('rubric_aspects')->where('is_active', true)->get();
                        $user = auth()->user();
                        $canScore = $user->can('create', [Score::class, $record]);

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

                        // Show other interviewers' scores and notes
                        $otherScores = Score::with(['interviewer', 'aspect'])
                            ->where('slot_id', $record->id)
                            ->where('interviewer_id', '!=', $user->id)
                            ->get();
                            
                        $otherNotes = CandidateNote::with(['author'])
                            ->where('candidate_id', $record->candidate_id)
                            ->where('author_id', '!=', $user->id)
                            ->get()
                            ->keyBy('author_id');

                        $otherPersons = $otherScores->pluck('interviewer')->merge($otherNotes->pluck('author'))->unique('id');

                        if ($otherPersons->isNotEmpty()) {
                            $othersHtml = "<div class='space-y-4'>";
                            foreach ($otherPersons as $person) {
                                $personScores = $otherScores->where('interviewer_id', $person->id);
                                $personNote = $otherNotes->get($person->id)?->body;
                                $avg = $personScores->count() > 0 ? round($personScores->avg('value'), 2) : '-';
                                
                                $othersHtml .= "<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-lg'>";
                                $othersHtml .= "<div class='flex items-center gap-2 mb-2'>
                                    <strong>{$person->name}</strong>
                                    <span class='px-2 py-0.5 text-xs bg-gray-200 dark:bg-gray-700 rounded-full'>{$person->jabatan}</span>
                                    <span class='ml-auto text-sm'>Rata-rata: <strong>{$avg}</strong></span>
                                </div>";
                                
                                if ($personNote) {
                                    $othersHtml .= "<p class='text-sm italic mb-2'>\"{$personNote}\"</p>";
                                }
                                
                                if ($personScores->isNotEmpty()) {
                                    $othersHtml .= "<div class='text-xs text-gray-500'>";
                                    $scoreParts = [];
                                    foreach ($personScores as $s) {
                                        $scoreParts[] = "{$s->aspect->name} ({$s->value})";
                                    }
                                    $othersHtml .= implode(', ', $scoreParts);
                                    $othersHtml .= "</div>";
                                }
                                
                                $othersHtml .= "</div>";
                            }
                            $othersHtml .= '</div>';

                            $components[] = Placeholder::make('other_scores')
                                ->label('Penilaian dan catatan lain')
                                ->content(new HtmlString($othersHtml));
                        }

                        // Current user's scores
                        $myScores = Score::where('slot_id', $record->id)
                            ->where('interviewer_id', $user->id)
                            ->get()
                            ->keyBy('rubric_aspect_id');
                            
                        $myNote = CandidateNote::where('candidate_id', $record->candidate_id)
                            ->where('author_id', $user->id)
                            ->first();

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
                                ->default($myScores->get($aspect->id)?->value)
                                ->disabled(!$canScore)
                                ->hidden(!$canScore && !$myScores->has($aspect->id));
                        }

                        $components[] = Textarea::make('note')
                            ->label('Catatan saya')
                            ->default($myNote?->body)
                            ->disabled(!$canScore);

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

                        // we will pass the note explicitly 
                        if (count($scores) > 0 || !empty($data['note'])) {
                            $submitter->submit($record, auth()->user(), $scores, $data['note'] ?? null);
                        }
                    }),
            ]);
    }
}
