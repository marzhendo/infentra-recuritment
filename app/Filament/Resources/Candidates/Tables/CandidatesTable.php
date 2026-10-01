<?php

namespace App\Filament\Resources\Candidates\Tables;

use App\Models\Candidate;
use App\Models\Division;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;

class CandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
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
                    ->sortable(),
                IconColumn::make('is_hmif')
                    ->label('HMIF')
                    ->boolean()
                    ->sortable(),
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
                ViewAction::make(),
                Action::make('tandai_hmif')
                    ->label('Tandai HMIF')
                    ->icon('heroicon-o-check-circle')
                    ->hidden(fn (Candidate $record) => $record->is_hmif)
                    ->action(fn (Candidate $record) => $record->update(['is_hmif' => true])),
                Action::make('hapus_tanda_hmif')
                    ->label('Hapus Tanda HMIF')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Candidate $record) => $record->is_hmif)
                    ->action(fn (Candidate $record) => $record->update(['is_hmif' => false])),
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
            ->whereRaw('LOWER(TRIM(REPLACE(name, "  ", " "))) = ?', [$normalizedName])
            ->count();
            
        if ($dupCount > 0) {
            $badges[] = 'Possible duplicate';
        }

        return implode(' | ', $badges);
    }
}
