<?php

namespace App\Filament\Resources\Candidates;

use App\Filament\Resources\Candidates\Pages\CreateCandidate;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\Candidates\Pages\ViewCandidate;
use App\Filament\Resources\Candidates\Schemas\CandidateForm;
use App\Filament\Resources\Candidates\Schemas\CandidateInfolist;
use App\Filament\Resources\Candidates\Tables\CandidatesTable;
use App\Models\Candidate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static ?string $modelLabel = 'Calon Panitia';
    protected static ?string $pluralModelLabel = 'Calon Panitia';
    protected static ?string $navigationLabel = 'Calon Panitia';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function canCreate(): bool
    {
        return filament()->auth()->user()->role?->value === 'admin';
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return filament()->auth()->user()->role?->value === 'admin';
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return filament()->auth()->user()->role?->value === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return CandidateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CandidateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CandidatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCandidates::route('/'),
            'view' => ViewCandidate::route('/{record}'),
        ];
    }
}
