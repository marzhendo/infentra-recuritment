<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Filament\Schemas\Components\Utilities\Get;

class Login extends BaseLogin
{
    public $login_type = 'nim';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('login_method')
                    ->label('Metode Masuk')
                    ->options([
                        'nim' => 'Masuk dengan Nama & NIM',
                        'email' => 'Masuk dengan Email',
                    ])
                    ->default('nim')
                    ->live()
                    ->required(),

                Select::make('group')
                    ->label('Kelompok')
                    ->options(function () {
                        $options = [];
                        if (\App\Models\User::whereIn('jabatan', ['Ketua Pelaksana', 'Steering Committee', 'PIC'])->exists()) {
                            $options['Pimpinan'] = 'Pimpinan (Ketua, SC, PIC)';
                        }
                        if (\App\Models\User::whereIn('jabatan', ['Sekretaris Umum', 'Sekretaris Kegiatan'])->exists()) {
                            $options['Sekretaris'] = 'Sekretaris';
                        }
                        if (\App\Models\User::whereIn('jabatan', ['Bendahara Umum', 'Bendahara Kegiatan'])->exists()) {
                            $options['Bendahara'] = 'Bendahara';
                        }
                        
                        $divisions = \App\Models\Division::whereHas('users')->orderBy('name')->pluck('name', 'id')->toArray();
                        foreach ($divisions as $id => $name) {
                            $options['div_' . $id] = 'Koor ' . $name;
                        }
                        return $options;
                    })
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('user_id', null))
                    ->required(fn (Get $get) => $get('login_method') === 'nim')
                    ->visible(fn (Get $get) => $get('login_method') === 'nim'),

                Select::make('user_id')
                    ->label('Nama')
                    ->options(function (Get $get) {
                        $group = $get('group');
                        if (!$group) return [];
                        
                        $query = User::query();
                        if ($group === 'Pimpinan') {
                            $query->whereIn('jabatan', ['Ketua Pelaksana', 'Steering Committee', 'PIC']);
                        } elseif ($group === 'Sekretaris') {
                            $query->whereIn('jabatan', ['Sekretaris Umum', 'Sekretaris Kegiatan']);
                        } elseif ($group === 'Bendahara') {
                            $query->whereIn('jabatan', ['Bendahara Umum', 'Bendahara Kegiatan']);
                        } elseif (str_starts_with($group, 'div_')) {
                            $divId = str_replace('div_', '', $group);
                            $query->where('division_id', $divId);
                        } else {
                            return [];
                        }
                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->live()
                    ->required(fn (Get $get) => $get('login_method') === 'nim')
                    ->visible(fn (Get $get) => $get('login_method') === 'nim'),

                TextInput::make('nim')
                    ->label('NIM')
                    ->required(fn (Get $get) => $get('login_method') === 'nim')
                    ->visible(fn (Get $get) => $get('login_method') === 'nim'),

                TextInput::make('password_nim')
                    ->label('Kata Sandi')
                    ->password()
                    ->required(fn (Get $get) => $get('login_method') === 'nim')
                    ->visible(fn (Get $get) => $get('login_method') === 'nim'),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(fn (Get $get) => $get('login_method') === 'email')
                    ->visible(fn (Get $get) => $get('login_method') === 'email'),

                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->required(fn (Get $get) => $get('login_method') === 'email')
                    ->visible(fn (Get $get) => $get('login_method') === 'email'),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->addError('nim', __('filament-panels::pages/auth/login.messages.throttled', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => ceil($exception->secondsUntilAvailable / 60),
            ]));
            
            return null;
        }

        $data = $this->form->getState();
        $user = null;

        if ($data['login_method'] === 'nim') {
            $user = User::where('id', $data['user_id'])->where('nim', $data['nim'])->first();
            
            if (!$user) {
                $this->addError('nim', 'Nama, NIM, atau kata sandi salah');
                return null;
            }

            if (empty($user->password)) {
                $this->addError('nim', 'Akun belum memiliki kata sandi. Silakan hubungi Ketua Pelaksana.');
                return null;
            }

            if (!Hash::check($data['password_nim'], $user->password)) {
                $this->addError('nim', 'Nama, NIM, atau kata sandi salah');
                return null;
            }
        } else {
            $user = User::where('email', $data['email'])->first();
            
            if (!$user || empty($user->password) || !Hash::check($data['password'], $user->password)) {
                $this->addError('email', 'Email atau kata sandi salah, atau belum mengatur kata sandi.');
                return null;
            }
        }

        if (!in_array($user->role->value ?? $user->role, ['admin', 'koor'])) {
            $this->addError('nim', 'Anda tidak memiliki akses ke panel ini');
            return null;
        }

        Filament::auth()->login($user, false); // No remember me

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
