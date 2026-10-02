<?php

namespace App\Filament\Resources\Candidates\Tables;

use App\Models\Candidate;
use App\Models\Division;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Nama Lengkap')
                    ->searchable(['name', 'name_override'])
                    ->sortable(['name'])
                    ->description(fn (Candidate $record) => self::getBadges($record)),
                TextColumn::make('angkatan')
                    ->label('Angkatan')
                    ->sortable(),
                TextColumn::make('pilihan1.name')
                    ->label('Pilihan 1')
                    ->sortable(),
                TextColumn::make('pilihan2.name')
                    ->label('Pilihan 2')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\ToggleColumn::make('is_hmif')
                    ->label('HMIF')
                    ->sortable(),
                IconColumn::make('is_duplicate')
                    ->label('Duplikat')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-duplicate')
                    ->falseIcon('')
                    ->trueColor('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('catatan')
                    ->label('Catatan umum')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('division')
                    ->label('Divisi (Pilihan 1 atau 2)')
                    ->options(fn () => Division::pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        $divId = $data['value'];

                        return $query->where(function (Builder $q) use ($divId) {
                            $q->where('pilihan_1_id', $divId)
                                ->orWhere('pilihan_2_id', $divId);
                        });
                    }),
                SelectFilter::make('angkatan')
                    ->label('Angkatan')
                    ->options([
                        '2024' => '2024',
                        '2025' => '2025',
                        '2026' => '2026',
                    ]),
                TernaryFilter::make('is_hmif')
                    ->label('HMIF')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                ViewAction::make(),
                Action::make('ubah_nama')
                    ->label('Ubah Nama')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        TextInput::make('name_override')
                            ->label('Nama Override (Kosongkan untuk kembali ke awal)')
                            ->nullable(),
                    ])
                    ->action(fn (Candidate $record, array $data) => $record->update(['name_override' => $data['name_override']])),
                Action::make('catatan_action')
                    ->label('Catatan umum')
                    ->icon('heroicon-o-document-text')
                    ->form([
                        Textarea::make('catatan')
                            ->label('Catatan umum')
                            ->nullable(),
                    ])
                    ->action(fn (Candidate $record, array $data) => $record->update(['catatan' => $data['catatan']])),
                Action::make('tandai_duplikat')
                    ->label('Tandai Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->hidden(fn (Candidate $record) => $record->is_duplicate)
                    ->action(fn (Candidate $record) => $record->update(['is_duplicate' => true])),
                Action::make('hapus_tanda_duplikat')
                    ->label('Hapus Tanda Duplikat')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Candidate $record) => $record->is_duplicate)
                    ->action(fn (Candidate $record) => $record->update(['is_duplicate' => false])),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('tandai_hmif_bulk')
                        ->label('Tandai HMIF')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_hmif' => true])),
                    BulkAction::make('hapus_tanda_hmif_bulk')
                        ->label('Hapus Tanda HMIF')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['is_hmif' => false])),
                    BulkAction::make('tandai_duplikat_bulk')
                        ->label('Tandai Duplikat')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(fn (Collection $records) => $records->each->update(['is_duplicate' => true])),
                    BulkAction::make('hapus_tanda_duplikat_bulk')
                        ->label('Hapus Tanda Duplikat')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['is_duplicate' => false])),
                ]),
            ]);
    }

    private static function getBadges(Candidate $record): string
    {
        $badges = [];
        if ($record->pilihan_1_id && $record->pilihan_1_id === $record->pilihan_2_id) {
            $badges[] = 'Pilihan 1 = Pilihan 2';
        }

        // Possible duplicate: same normalized name
        $normalizedName = strtolower(preg_replace('/\s+/', ' ', trim($record->name)));

        // Count others with same normalized name
        $dupCount = Candidate::where('id', '!=', $record->id)
            ->whereRaw("LOWER(TRIM(REPLACE(name, '  ', ' '))) = ?", [$normalizedName])
            ->count();

        if ($dupCount > 0) {
            $badges[] = 'Possible duplicate';
        }

        return implode(' | ', $badges);
    }
}



