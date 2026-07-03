<?php

namespace Tests\Feature\Products;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private function search(string $q): array
    {
        return $this->getJson('/api/v1/products?q='.urlencode($q))
            ->assertStatus(200)
            ->json('data');
    }

    public function test_multi_word_query_combines_brand_and_category(): void
    {
        $zara = Brand::factory()->create(['name' => 'ZARA']);

        $zaraDress = Product::factory()->create([
            'brand_id' => $zara->id,
            'category' => 'dresses',
            'title'    => 'Ljetna maksi',
        ]);
        // Same brand, wrong category — must not match "zara haljina"
        Product::factory()->create([
            'brand_id' => $zara->id,
            'category' => 'tops',
            'title'    => 'Basic majica',
        ]);

        $results = $this->search('zara haljina');

        $this->assertCount(1, $results);
        $this->assertEquals($zaraDress->id, $results[0]['id']);
    }

    public function test_apostrophe_brand_matches_unpunctuated_query(): void
    {
        $levis = Brand::factory()->create(['name' => "Levi's"]);
        $jeans = Product::factory()->create([
            'brand_id' => $levis->id,
            'category' => 'bottoms',
            'title'    => '501 model',
        ]);

        foreach (['levis', "levi's"] as $q) {
            $results = $this->search($q);
            $this->assertCount(1, $results, "query '{$q}' should match Levi's");
            $this->assertEquals($jeans->id, $results[0]['id']);
        }
    }

    public function test_four_letter_typo_falls_back_to_synonym_group(): void
    {
        Product::factory()->create([
            'category' => 'bags',
            'title'    => 'Kožna torba',
        ]);

        $this->assertCount(1, $this->search('trba'));
    }

    public function test_adjective_inflection_matches_via_stemming(): void
    {
        Product::factory()->create([
            'category' => 'dresses',
            'title'    => 'Duga svečana',
        ]);
        // Dress without "dug" anywhere — must not match "duge haljine"
        Product::factory()->create([
            'category' => 'dresses',
            'title'    => 'Kratka ljetna',
        ]);

        $results = $this->search('duge haljine');

        $this->assertCount(1, $results);
        $this->assertEquals('Duga svečana', $results[0]['title']);
    }

    public function test_bare_size_token_narrows_by_size(): void
    {
        Product::factory()->create([
            'category' => 'dresses',
            'title'    => 'Elegantna',
            'size'     => 'XL',
        ]);
        Product::factory()->create([
            'category' => 'dresses',
            'title'    => 'Elegantna',
            'size'     => 'S',
        ]);

        $results = $this->search('haljina xl');

        $this->assertCount(1, $results);
        $this->assertEquals('XL', $results[0]['size']);
    }

    public function test_single_token_brand_query_is_not_stemmed(): void
    {
        // "nike" must not become "nik" and match e.g. "tunika"
        Product::factory()->create([
            'category' => 'dresses',
            'title'    => 'Tunika za ljeto',
        ]);

        $this->assertCount(0, $this->search('nike'));
    }
}
