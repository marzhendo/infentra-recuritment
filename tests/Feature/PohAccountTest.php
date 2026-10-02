<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;
use App\Filament\Resources\PohAccounts\Pages\ManagePohAccounts;

class PohAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_head_interviewer_can_access()
    {
        $head = User::factory()->create(['role' => 'admin', 'is_head_interviewer' => true]);
        
        $this->actingAs($head)
            ->get(\App\Filament\Resources\PohAccounts\PohAccountResource::getUrl())
            ->assertSuccessful();
    }

    public function test_normal_admin_cannot_access()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_head_interviewer' => false]);
        
        $this->actingAs($admin)
            ->get(\App\Filament\Resources\PohAccounts\PohAccountResource::getUrl())
            ->assertForbidden();
    }

    public function test_koor_cannot_access()
    {
        $koor = User::factory()->create(['role' => 'koor', 'is_head_interviewer' => false]);
        
        $this->actingAs($koor)
            ->get(\App\Filament\Resources\PohAccounts\PohAccountResource::getUrl())
            ->assertForbidden();
    }

    public function test_generate_password_row_action()
    {
        $head = User::factory()->create(['role' => 'admin', 'is_head_interviewer' => true]);
        $target = User::factory()->create(['role' => 'koor', 'password' => null]);

        Livewire::actingAs($head)
            ->test(ManagePohAccounts::class)
            ->callTableAction('generate_password', $target)
            ->assertRedirect(); // We redirect back to referer

        $target->refresh();
        $this->assertNotNull($target->password);

        $this->assertTrue(session()->has('generated_passwords'));
        $passwords = session()->get('generated_passwords');
        $this->assertCount(1, $passwords);
        $this->assertEquals($target->name, $passwords[0]['name']);
        
        // Ensure password is not in livewire state (it's flashed in session)
    }

    public function test_generate_bulk_action()
    {
        $head = User::factory()->create(['role' => 'admin', 'is_head_interviewer' => true]);
        $target1 = User::factory()->create(['role' => 'koor', 'password' => null]);
        $target2 = User::factory()->create(['role' => 'admin', 'password' => null]);

        Livewire::actingAs($head)
            ->test(ManagePohAccounts::class)
            ->callTableBulkAction('generate_passwords_bulk', [$target1->id, $target2->id])
            ->assertRedirect(); // We redirect back to referer

        $target1->refresh();
        $target2->refresh();
        
        $this->assertNotNull($target1->password);
        $this->assertNotNull($target2->password);

        $this->assertTrue(session()->has('generated_passwords'));
        $passwords = session()->get('generated_passwords');
        $this->assertCount(2, $passwords);
    }
}
