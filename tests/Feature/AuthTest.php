<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_noindex()
    {
        $response = $this->get('/admin/login');
        $response->assertSuccessful();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_nim_only_fails()
    {
        $user = User::factory()->create([
            'nim' => '12345678',
            'password' => null,
            'role' => 'admin',
            'jabatan' => 'PIC',
        ]);

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->set('data.login_method', 'nim')
            ->set('data.group', 'Pimpinan')
            ->set('data.user_id', $user->id)
            ->set('data.nim', '12345678')
            ->call('authenticate')
            ->assertHasErrors(['data.password_nim' => 'required']);

        $this->assertGuest();
    }

    public function test_nim_and_empty_password_fails()
    {
        $user = User::factory()->create([
            'nim' => '12345678',
            'password' => null,
            'role' => 'admin',
            'jabatan' => 'PIC',
        ]);

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->set('data.login_method', 'nim')
            ->set('data.group', 'Pimpinan')
            ->set('data.user_id', $user->id)
            ->set('data.nim', '12345678')
            ->set('data.password_nim', '')
            ->call('authenticate')
            ->assertHasErrors(['data.password_nim' => 'required']);

        $this->assertGuest();
    }

    public function test_nim_and_any_password_for_null_password_user_fails()
    {
        $user = User::factory()->create([
            'nim' => '12345678',
            'password' => null,
            'role' => 'admin',
            'jabatan' => 'PIC',
        ]);

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->set('data.login_method', 'nim')
            ->set('data.group', 'Pimpinan')
            ->set('data.user_id', $user->id)
            ->set('data.nim', '12345678')
            ->set('data.password_nim', 'randompassword')
            ->call('authenticate')
            ->assertHasErrors('nim');

        $this->assertGuest();
    }

    public function test_correct_nim_and_correct_password_succeeds()
    {
        $user = User::factory()->create([
            'nim' => '12345678',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'jabatan' => 'PIC',
        ]);

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->set('data.login_method', 'nim')
            ->set('data.group', 'Pimpinan')
            ->set('data.user_id', $user->id)
            ->set('data.nim', '12345678')
            ->set('data.password_nim', 'secret123')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_nim_gives_generic_error()
    {
        $user = User::factory()->create([
            'nim' => '12345678',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'jabatan' => 'PIC',
        ]);

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->set('data.login_method', 'nim')
            ->set('data.group', 'Pimpinan')
            ->set('data.user_id', $user->id)
            ->set('data.nim', '87654321')
            ->set('data.password_nim', 'secret123')
            ->call('authenticate')
            ->assertHasErrors(['nim' => 'Nama, NIM, atau kata sandi salah']);

        $this->assertGuest();
    }

    public function test_rate_limit_triggers()
    {
        $user = User::factory()->create([
            'nim' => '12345678',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'jabatan' => 'PIC',
        ]);

        $component = Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->set('data.login_method', 'nim')
            ->set('data.group', 'Pimpinan')
            ->set('data.user_id', $user->id)
            ->set('data.nim', 'wrong')
            ->set('data.password_nim', 'secret123');

        for ($i = 0; $i < 5; $i++) {
            $component->call('authenticate');
        }

        $component->call('authenticate')
            ->assertHasErrors('nim');
            
        $this->assertStringContainsString('filament-panels::pages/auth/login.messages.throttled', $component->errors()->first('nim'));
    }

    public function test_login_dropdown_only_shows_groups_with_users()
    {
        $divWithKoor = \App\Models\Division::factory()->create(['name' => 'Div With Koor']);
        $divNoKoor = \App\Models\Division::factory()->create(['name' => 'Div No Koor']);
        
        \App\Models\User::factory()->create([
            'jabatan' => 'Ketua Pelaksana',
            'division_id' => null,
        ]);
        \App\Models\User::factory()->create([
            'division_id' => $divWithKoor->id,
            'jabatan' => 'Koordinator',
        ]);
        
        \Livewire\Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->assertSeeHtml('Pimpinan (Ketua, SC, PIC)')
            ->assertSeeHtml('Koor Div With Koor')
            ->assertDontSeeHtml('Sekretaris')
            ->assertDontSeeHtml('Bendahara')
            ->assertDontSeeHtml('Koor Div No Koor');
    }
}
