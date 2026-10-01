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

    public function test_nim_only_login_success()
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
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_cannot_login_with_nim_if_password_is_set()
    {
        $user = User::factory()->create([
            'nim' => '12345678',
            'password' => Hash::make('secret'),
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

    public function test_wrong_nim_gives_generic_error()
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
            ->set('data.nim', '87654321')
            ->call('authenticate')
            ->assertHasErrors(['nim' => 'NIM atau kata sandi salah']);

        $this->assertGuest();
    }

    public function test_rate_limit_triggers()
    {
        $user = User::factory()->create([
            'nim' => '12345678',
            'password' => null,
            'role' => 'admin',
            'jabatan' => 'PIC',
        ]);

        $component = Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->set('data.login_method', 'nim')
            ->set('data.group', 'Pimpinan')
            ->set('data.user_id', $user->id)
            ->set('data.nim', 'wrong');

        for ($i = 0; $i < 5; $i++) {
            $component->call('authenticate');
        }

        $component->call('authenticate')
            ->assertHasErrors('nim');
            
        $this->assertStringContainsString('filament-panels::pages/auth/login.messages.throttled', $component->errors()->first('nim'));
    }

    // Roles other than admin/koor are currently impossible due to Enum casting,
    // but the logic in Login.php explicitly checks for 'admin' and 'koor'.
}
