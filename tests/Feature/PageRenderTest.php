<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_dashboard_and_cashier_pos_pages_render(): void
    {
        $this->seedCore();

        $owner = User::factory()->owner()->create();
        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Reports/Index'));

        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Pos/Index')->has('products'));
    }

    public function test_owner_create_edit_and_show_routes_open_index_modals(): void
    {
        $this->seedCore();

        $owner = User::factory()->owner()->create();
        $product = $this->productWithStock();
        $sale = Sale::factory()->completed()->create();

        $this->actingAs($owner)
            ->get(route('products.create'))
            ->assertRedirect(route('products.index', ['create' => 1]));

        $this->actingAs($owner)
            ->get(route('products.edit', $product))
            ->assertRedirect(route('products.index', ['edit' => $product->id]));

        $this->actingAs($owner)
            ->get(route('products.index', ['create' => 1]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('creating', true));

        $this->actingAs($owner)
            ->get(route('sales.show', $sale))
            ->assertRedirect(route('sales.index', ['sale' => $sale->id]));

        $this->actingAs($owner)
            ->get(route('sales.index', ['sale' => $sale->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Index')
                ->where('viewingSale.id', $sale->id));
    }
}
