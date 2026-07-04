<?php

namespace Tests\Feature\Console;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeUnverifiedUsersTest extends TestCase
{
    use RefreshDatabase;

    private function oldUnverified(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'created_at'        => now()->subDays(30),
        ], $attributes));
    }

    public function test_purges_only_stale_unverified_regular_accounts(): void
    {
        $staleUnverified   = $this->oldUnverified();
        $stalePhoneOnly    = $this->oldUnverified(['email_verified_at' => now()]);
        $fullyVerified     = User::factory()->create(['created_at' => now()->subDays(30)]);
        $recentUnverified  = $this->oldUnverified(['created_at' => now()->subHours(12)]);
        $adminUnverified   = $this->oldUnverified(['role' => 'admin']);
        $systemUnverified  = $this->oldUnverified(['is_system' => true]);
        $sellerUnverified  = $this->oldUnverified();
        Product::factory()->create(['seller_id' => $sellerUnverified->id]);

        $this->artisan('users:purge-unverified', ['--before' => now()->subDay()->toDateString(), '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $staleUnverified->id]);
        $this->assertDatabaseMissing('users', ['id' => $stalePhoneOnly->id]);

        $this->assertDatabaseHas('users', ['id' => $fullyVerified->id]);
        $this->assertDatabaseHas('users', ['id' => $recentUnverified->id]);
        $this->assertDatabaseHas('users', ['id' => $adminUnverified->id]);
        $this->assertDatabaseHas('users', ['id' => $systemUnverified->id]);
        $this->assertDatabaseHas('users', ['id' => $sellerUnverified->id]);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $staleUnverified = $this->oldUnverified();

        $this->artisan('users:purge-unverified', ['--before' => now()->toDateString(), '--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $staleUnverified->id]);
    }

    public function test_requires_before_option(): void
    {
        $this->artisan('users:purge-unverified')->assertFailed();
    }
}
