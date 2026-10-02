<?php

namespace App\Filament\Resources\PohAccounts\Pages;

use App\Filament\Resources\PohAccounts\PohAccountResource;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\View\View;

class ManagePohAccounts extends ManageRecords
{
    protected static string $resource = PohAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
    
    public function getHeader(): ?View
    {
        if (session()->has('generated_passwords')) {
            return view('poh-passwords-alert', [
                'passwords' => session()->get('generated_passwords')
            ]);
        }
        
        return null;
    }
}
