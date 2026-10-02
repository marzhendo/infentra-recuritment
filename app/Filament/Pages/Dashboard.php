<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Beranda';
    protected static ?string $navigationLabel = 'Beranda';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    
    protected string $view = 'filament.pages.dashboard';
}
