<?php

namespace Tests\Feature\Products;

use App\Models\Brand;
use App\Models\BrandSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_files_a_pending_suggestion_for_an_unknown_brand(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/brand-suggestions', ['name' => 'Totally New Brand'])
            ->assertCreated();

        $this->assertDatabaseHas('brand_suggestions', [
            'user_id' => $user->id,
            'name'    => 'Totally New Brand',
            'status'  => 'pending',
        ]);
    }

    public function test_it_skips_the_suggestion_when_the_brand_already_exists(): void
    {
        Brand::factory()->create(['name' => "Isabel Marant"]);
        $user = User::factory()->create();

        // Different punctuation / casing / spacing — still the same brand.
        $this->actingAs($user)
            ->postJson('/api/v1/brand-suggestions', ['name' => 'isabel-marant'])
            ->assertCreated();

        $this->assertSame(0, BrandSuggestion::count());
    }
}
