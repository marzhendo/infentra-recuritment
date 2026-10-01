<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('poh:users', function () {
    $this->table(
        ['NIM', 'Name', 'Role', 'Jabatan', 'Password Set?'],
        \App\Models\User::all()->map(fn ($u) => [
            $u->nim,
            $u->name,
            $u->role,
            $u->jabatan,
            $u->password ? 'Yes' : 'No'
        ])
    );
})->purpose('List POH users');

Artisan::command('poh:set-password {nim}', function ($nim) {
    $user = \App\Models\User::where('nim', $nim)->first();
    if (!$user) {
        $this->error('User not found.');
        return;
    }
    
    $password = $this->secret('Enter new password');
    $confirm = $this->secret('Confirm new password');
    
    if ($password !== $confirm) {
        $this->error('Passwords do not match.');
        return;
    }
    
    $user->password = \Illuminate\Support\Facades\Hash::make($password);
    $user->save();
    
    $this->info("Password updated successfully for {$user->name}.");
})->purpose('Set password for a POH user');
