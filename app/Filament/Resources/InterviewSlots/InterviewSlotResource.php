<?php

namespace App\Filament\Resources\InterviewSlots;

use App\Filament\Resources\InterviewSlots\Pages\ManageInterviewSlots;
use App\Models\Candidate;
use App\Models\InterviewSlot;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class InterviewSlotResource extends Resource
{
    protected static ?string $model = InterviewSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static ?string $modelLabel = 'Wawancara';
    protected static ?string $pluralModelLabel = 'Wawancara';
    protected static ?string $navigationLabel = 'Wawancara';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at')
            ->defaultGroup('interviewDay.date')
            ->groups([
                Group::make('interviewDay.date')
                    ->label('Hari Wawancara')
                    ->getTitleFromRecordUsing(fn ($record) => \Carbon\Carbon::parse($record->interviewDay->date)->translatedFormat('l, d F Y')),
            ])
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Waktu')
                    ->formatStateUsing(fn($record) => substr($record->starts_at, 0, 5) . ' - ' . substr($record->ends_at, 0, 5)),
                TextColumn::make('candidate.display_name')
                    ->label('Kandidat')
                    ->searchable()
                    ->placeholder('Kosong'),
                TextColumn::make('candidate.pilihan1.name')
                    ->label('Pilihan 1'),
                TextColumn::make('candidate.pilihan2.name')
                    ->label('Pilihan 2'),
                IconColumn::make('is_locked')
                    ->label('Terkunci')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('')
                    ->trueColor('warning'),
            ])
            ->recordClasses(function (InterviewSlot $record) {
                static $highlightId = null;
                if ($highlightId === null) {
                    $now = \Carbon\Carbon::now('Asia/Jakarta');
                    $time = $now->format('H:i:s');
                    $date = $now->toDateString();
                    
                    // Current slot
                    $current = InterviewSlot::whereHas('interviewDay', fn($q) => $q->where('date', $date))
                        ->where('starts_at', '<=', $time)
                        ->where('ends_at', '>=', $time)
                        ->first();
                        
                    if ($current) {
                        $highlightId = $current->id;
                    } else {
                        // Next slot
                        $next = InterviewSlot::whereHas('interviewDay', fn($q) => $q->where('date', $date))
                            ->where('starts_at', '>', $time)
                            ->orderBy('starts_at')
                            ->first();
                        $highlightId = $next ? $next->id : false;
                    }
                }
                
                return $record->id === $highlightId ? 'bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-500' : null;
            })
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('interview_day_id')
                    ->label('Hari Wawancara')
                    ->options(fn () => \App\Models\InterviewDay::pluck('date', 'id')->map(fn ($d) => \Carbon\Carbon::parse($d)->translatedFormat('l, d F Y'))->toArray())
                    ->default(function () {
                        $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
                        $day = \App\Models\InterviewDay::where('date', $today)->first();
                        return $day ? $day->id : null;
                    }),
            ])
            ->recordActions([
                Action::make('nilai')
                    ->label(function (InterviewSlot $record) {
                        $user = filament()->auth()->user();
                        if (!$record->candidate_id) return 'Hanya lihat';
                        if ($user->role?->value === 'admin' && in_array($user->jabatan, ['Ketua Pelaksana', 'Steering Committee', 'PIC'])) return 'Nilai';
                        if ($user->role?->value === 'koor') {
                            $divId = $user->division_id;
                            if ($record->candidate->pilihan_1_id === $divId || $record->candidate->pilihan_2_id === $divId) {
                                return 'Nilai';
                            }
                        }
                        return 'Hanya lihat';
                    })
                    ->icon(function (InterviewSlot $record) {
                        $user = filament()->auth()->user();
                        if (!$record->candidate_id) return 'heroicon-o-eye';
                        if ($user->role?->value === 'admin' && in_array($user->jabatan, ['Ketua Pelaksana', 'Steering Committee', 'PIC'])) return 'heroicon-o-pencil-square';
                        if ($user->role?->value === 'koor') {
                            $divId = $user->division_id;
                            if ($record->candidate->pilihan_1_id === $divId || $record->candidate->pilihan_2_id === $divId) {
                                return 'heroicon-o-pencil-square';
                            }
                        }
                        return 'heroicon-o-eye';
                    })
                    ->color(function (InterviewSlot $record) {
                        $user = filament()->auth()->user();
                        if (!$record->candidate_id) return 'gray';
                        if ($user->role?->value === 'admin' && in_array($user->jabatan, ['Ketua Pelaksana', 'Steering Committee', 'PIC'])) return 'primary';
                        if ($user->role?->value === 'koor') {
                            $divId = $user->division_id;
                            if ($record->candidate->pilihan_1_id === $divId || $record->candidate->pilihan_2_id === $divId) {
                                return 'primary';
                            }
                        }
                        return 'gray';
                    })
                    ->url(fn (InterviewSlot $record) => $record->candidate_id ? "/admin/candidates/{$record->candidate_id}" : null)
                    ->disabled(fn (InterviewSlot $record) => !$record->candidate_id),
                \Filament\Actions\ActionGroup::make([
                    Action::make('assign')
                    ->label('Pilih Kandidat')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Select::make('candidate_id')
                            ->label('Kandidat')
                            ->options(function () {
                                $scheduled = InterviewSlot::whereNotNull('candidate_id')->pluck('candidate_id')->toArray();
                                $candidates = Candidate::where('is_hmif', false)->get();
                                $seen = [];
                                $options = [];
                                foreach ($candidates as $c) {
                                    $norm = strtolower(preg_replace('/\s+/', ' ', trim($c->name)));
                                    if (isset($seen[$norm])) continue;
                                    $seen[$norm] = true;
                                    if (!in_array($c->id, $scheduled)) {
                                        $options[$c->id] = $c->name . ' (' . ($c->pilihan1->name ?? '-') . ')';
                                    }
                                }
                                return $options;
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (InterviewSlot $record, array $data) {
                        $record->update(['candidate_id' => $data['candidate_id']]);
                    }),
                Action::make('clear')
                    ->label('Kosongkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(InterviewSlot $record) => $record->candidate_id !== null)
                    ->action(fn(InterviewSlot $record) => $record->update(['candidate_id' => null])),
                Action::make('toggle_lock')
                    ->label(fn(InterviewSlot $record) => $record->is_locked ? 'Buka Kunci' : 'Kunci')
                    ->icon(fn(InterviewSlot $record) => $record->is_locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn(InterviewSlot $record) => $record->is_locked ? 'success' : 'warning')
                    ->action(fn(InterviewSlot $record) => $record->update(['is_locked' => !$record->is_locked])),
                Action::make('swap')
                    ->label('Tukar Slot')
                    ->icon('heroicon-o-arrows-right-left')
                    ->form([
                        Select::make('target_slot_id')
                            ->label('Tukar dengan slot')
                            ->options(function (InterviewSlot $record) {
                                return InterviewSlot::with('candidate')
                                    ->where('id', '!=', $record->id)
                                    ->get()
                                    ->mapWithKeys(function ($slot) {
                                        $label = $slot->starts_at . ' - ' . ($slot->candidate->name ?? 'Kosong');
                                        return [$slot->id => $label];
                                    });
                            })
                            ->required()
                            ->searchable()
                    ])
                    ->action(function (InterviewSlot $record, array $data) {
                        $target = InterviewSlot::find($data['target_slot_id']);
                        $temp = $record->candidate_id;
                        $record->update(['candidate_id' => $target->candidate_id]);
                        $target->update(['candidate_id' => $temp]);
                    }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInterviewSlots::route('/'),
        ];
    }
}
