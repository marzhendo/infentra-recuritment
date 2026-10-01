<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/jadwal', \App\Livewire\PublicSchedule::class)
    ->middleware(\App\Http\Middleware\NoIndex::class);
