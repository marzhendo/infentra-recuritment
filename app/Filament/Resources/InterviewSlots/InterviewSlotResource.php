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
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class InterviewSlotResource extends Resource
{
    protected static ?string $model = InterviewSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static ?string $modelLabel = 'Slot Wawancara';
    protected static ?string $pluralModelLabel = 'Slot Wawancara';

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
                Group::make('interviewDay.date')->label('Hari Wawancara'),
            ])
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Waktu')
                    ->formatStateUsing(fn($record) => substr($record->starts_at, 0, 5) . ' - ' . substr($record->ends_at, 0, 5)),
                TextColumn::make('candidate.name')
                    ->label('Kandidat')
                    ->searchable()
                    ->placeholder('Kosong'),
                TextColumn::make('candidate.pilihan1.name')
                    ->label('Pilihan 1'),
                TextColumn::make('candidate.pilihan2.name')
                    ->label('Pilihan 2'),
                IconColumn::make('is_locked')
                    ->label('Terkunci')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInterviewSlots::route('/'),
        ];
    }
}
