<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/jadwal', \App\Livewire\PublicSchedule::class)
    ->middleware(\App\Http\Middleware\NoIndex::class);
