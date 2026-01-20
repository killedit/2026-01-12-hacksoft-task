<?php

namespace App\Filament\Pages;

// use Filament\Pages\Page;

// class Dashboard extends Page
// {
    //     protected string $view = 'filament.pages.dashboard';
// }

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';
}
