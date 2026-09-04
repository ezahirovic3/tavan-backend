<?php

namespace Tests\Feature\Products;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_by_normalized_name_ignores_case_separators_and_spacing(): void
    {
        $brand = Brand::factory()->create(['name' => 'Isabel Marant']);

        foreach (['isabel marant', 'ISABEL-MARANT', 'Isabel  Marant', 'isabel.marant'] as $variant) {
            $this->assertTrue(
                Brand::findByNormalizedName($variant)?->is($brand) ?? false,
                "expected \"$variant\" to resolve to the brand",
            );
        }

        $this->assertNull(Brand::findByNormalizedName('Isabela Marantt'));
        $this->assertNull(Brand::findByNormalizedName('   '));
    }

    public function test_find_by_normalized_name_drops_apostrophes(): void
    {
        $brand = Brand::factory()->create(['name' => "Levi's"]);

        $this->assertTrue(Brand::findByNormalizedName('levis')?->is($brand) ?? false);
        $this->assertTrue(Brand::findByNormalizedName("LEVI'S")?->is($brand) ?? false);
    }

    public function test_active_only_scope_hides_inactive_and_other_brands_by_default(): void
    {
        Brand::factory()->create(['name' => 'Disabled Co', 'is_active' => false]);
        Brand::factory()->create(['name' => 'Ostali', 'is_other' => true]);

        $this->assertNull(Brand::findByNormalizedName('disabled co'));
        $this->assertNull(Brand::findByNormalizedName('ostali'));

        // The approval flow passes activeOnly: false to catch any near-duplicate.
        $this->assertNotNull(Brand::findByNormalizedName('disabled co', activeOnly: false));
        $this->assertNotNull(Brand::findByNormalizedName('ostali', activeOnly: false));
    }
}
