<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProductMilestoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tavan.active_product_milestone' => 5]);
        Cache::flush();
    }

    private function milestoneActivity()
    {
        return Activity::where('log_name', 'milestone')
            ->where('description', 'milestone_5_active');
    }

    public function test_product_that_crosses_the_milestone_is_flagged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Product::factory()->count(4)->create(['status' => 'active']);

        $this->assertSame(0, $this->milestoneActivity()->count());

        $milestoneProduct = Product::factory()->create(['status' => 'active']);

        $activity = $this->milestoneActivity()->first();

        $this->assertNotNull($activity);
        $this->assertSame($milestoneProduct->id, $activity->subject_id);
        $this->assertSame(5, $activity->properties['active_count']);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
        ]);
    }

    public function test_milestone_fires_when_product_is_published_via_update(): void
    {
        Product::factory()->count(4)->create(['status' => 'active']);
        $draft = Product::factory()->create(['status' => 'draft']);

        $this->assertSame(0, $this->milestoneActivity()->count());

        $draft->update(['status' => 'active']);

        $activity = $this->milestoneActivity()->first();

        $this->assertNotNull($activity);
        $this->assertSame($draft->id, $activity->subject_id);
    }

    public function test_milestone_is_only_flagged_once(): void
    {
        Product::factory()->count(5)->create(['status' => 'active']);

        $this->assertSame(1, $this->milestoneActivity()->count());

        Product::factory()->create(['status' => 'active']);

        $this->assertSame(1, $this->milestoneActivity()->count());
    }

    public function test_milestone_is_not_flagged_twice_even_if_cache_is_flushed(): void
    {
        Product::factory()->count(5)->create(['status' => 'active']);

        Cache::flush();

        Product::factory()->create(['status' => 'active']);

        $this->assertSame(1, $this->milestoneActivity()->count());
    }

    public function test_non_active_products_do_not_trigger_the_milestone(): void
    {
        Product::factory()->count(4)->create(['status' => 'active']);
        Product::factory()->create(['status' => 'draft']);

        $this->assertSame(0, $this->milestoneActivity()->count());
    }
}
