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
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\TimePicker;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Repeater;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class InterviewDayResource extends Resource
{
    protected static ?string $model = InterviewDay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;
    protected static ?string $modelLabel = 'Hari Wawancara';
    protected static ?string $pluralModelLabel = 'Hari Wawancara';

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
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Tanggal')->date(),
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
