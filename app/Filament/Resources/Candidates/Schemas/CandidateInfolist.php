<?php

namespace App\Filament\Resources\Candidates\Schemas;

use App\Support\DriveLink;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Grid;
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
                                if (empty($state)) return 'Tidak ada data.';
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
            ]);
    }

    private static function fileEntry(string $label, string $field): ViewEntry
    {
        return ViewEntry::make($field)
            ->label($label)
            ->view('filament.infolists.components.drive-file');
    }
}
