<?php

namespace App\Filament\Resources\InterviewDays;

use App\Filament\Resources\InterviewDays\Pages\ManageInterviewDays;
use App\Models\InterviewDay;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class InterviewDayResource extends Resource
{
    protected static ?string $model = InterviewDay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;
    protected static ?string $modelLabel = 'Jadwal (Hari)';
    protected static ?string $pluralModelLabel = 'Jadwal';
    protected static ?string $navigationLabel = 'Jadwal';

    public static function canViewAny(): bool
    {
        return filament()->auth()->user()->role?->value === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->required()
                    ->unique(ignoreRecord: true),
                TimePicker::make('starts_at')
                    ->label('Mulai (Waktu)')
                    ->required(),
                TextInput::make('slot_minutes')
                    ->label('Durasi Slot (menit)')
                    ->numeric()
                    ->default(10)
                    ->required(),
                TextInput::make('room')
                    ->label('Ruangan')
                    ->default('DC-302')
                    ->required(),
                TimePicker::make('ends_at')
                    ->label('Selesai (Opsional)')
                    ->nullable(),
                Repeater::make('breakBlocks')
                    ->relationship()
                    ->label('Blok Istirahat')
                    ->schema([
                        TextInput::make('label')
                            ->label('Label (Mis: Dzuhur)')
                            ->required(),
                        TimePicker::make('starts_at')
                            ->label('Mulai')
                            ->required(),
                        TextInput::make('duration_minutes')
                            ->label('Durasi (menit)')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->label('Publikasikan jadwal')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Tanggal')->date(),
                ToggleColumn::make('is_published')->label('Publikasi'),
                TextColumn::make('starts_at')->label('Mulai')->time('H:i'),
                TextColumn::make('room')->label('Ruangan'),
                TextColumn::make('slot_minutes')->label('Slot (m)'),
                TextColumn::make('ends_at')->label('Est. Selesai')->time('H:i'),
                TextColumn::make('sessions')
                    ->label('Sesi')
                    ->getStateUsing(fn(InterviewDay $record) => $record->interviewSlots()->count()),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInterviewDays::route('/'),
        ];
    }
}
