<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BranchLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_branch_creates_a_walk_in_customer_for_that_branch(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('branches.store'), [
            'name' => 'Cabang Bandung',
            'code' => 'BDG',
        ])->assertRedirect(route('branches.index'));

        $branch = Branch::query()->where('code', 'BDG')->first();
        $walkIn = Customer::walkInFor($branch->id);

        $this->assertNotNull($branch);
        $this->assertNotNull($walkIn);
        $this->assertSame($branch->id, $walkIn->branch_id);
        $this->assertTrue($walkIn->is_walk_in);
    }

    public function test_pos_hides_products_that_have_no_stock_at_the_active_branch(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        CashierShift::factory()->create(['user_id' => $owner->id]);
        $this->productWithStock(10);
        $bandung = $this->createBandungBranch($owner);
        $aqua = $this->aquaAt($bandung, 1);

        $this->actingAs($owner)
            ->post(route('branches.switch', $bandung))
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pos/Index')
                ->has('products', 1)
                ->where('products.0.id', $aqua->id)
                ->where('products.0.name', 'Aqua 600ml')
                ->has('customers', 1)
                ->where('customers.0.is_walk_in', true));
    }

    public function test_pos_search_omits_members_from_other_branches(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        CashierShift::factory()->create(['user_id' => $owner->id]);
        Customer::factory()->create([
            'name' => 'Member Pusat',
            'phone' => '081234567890',
        ]);
        $bandung = $this->createBandungBranch($owner);

        $this->actingAs($owner)
            ->post(route('branches.switch', $bandung))
            ->assertRedirect();

        $customers = $this->actingAs($owner)
            ->getJson(route('pos.customers.search', ['q' => 'Member']))
            ->assertOk()
            ->json('customers');

        $this->assertCount(1, $customers);
        $this->assertTrue($customers[0]['is_walk_in']);
        $this->assertSame('Walk-in Customer', $customers[0]['name']);
    }

    public function test_customer_index_lists_only_the_active_branch(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        Customer::factory()->create(['name' => 'Member Pusat']);
        $bandung = $this->createBandungBranch($owner);

        $this->actingAs($owner)
            ->post(route('branches.switch', $bandung))
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Index')
                ->has('customers.data', 1)
                ->where('customers.data.0.is_walk_in', true)
                ->where('customers.data.0.branch_id', $bandung->id));
    }

    public function test_stock_index_lists_only_quantities_at_the_active_warehouse(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $this->productWithStock(10);
        $bandung = $this->createBandungBranch($owner);
        $aqua = $this->aquaAt($bandung, 1);

        $this->actingAs($owner)
            ->post(route('branches.switch', $bandung))
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('stock.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Stock/Index')
                ->has('stocks.data', 1)
                ->where('stocks.data.0.product_id', $aqua->id));
    }

    public function test_product_index_lists_only_products_at_the_active_branch(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $pusatProduct = $this->productWithStock(10);
        $bandung = $this->createBandungBranch($owner);
        $aqua = $this->aquaAt($bandung, 1);

        $this->actingAs($owner)
            ->post(route('branches.switch', $bandung))
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->has('products.data', 1)
                ->where('products.data.0.id', $aqua->id)
                ->where('products.data.0.name', 'Aqua 600ml'));

        $this->actingAs($owner)
            ->get(route('products.edit', $pusatProduct))
            ->assertNotFound();
    }

    public function test_creating_a_pos_member_assigns_the_active_branch(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        CashierShift::factory()->create(['user_id' => $owner->id]);
        $bandung = $this->createBandungBranch($owner);

        $this->actingAs($owner)
            ->post(route('branches.switch', $bandung))
            ->assertRedirect();

        $this->actingAs($owner)
            ->from(route('pos.index'))
            ->post(route('pos.customers.store'), [
                'name' => 'Member Bandung',
                'phone' => '087700011122',
            ])
            ->assertRedirect(route('pos.index'));

        $member = Customer::query()->where('name', 'Member Bandung')->first();

        $this->assertNotNull($member);
        $this->assertSame($bandung->id, $member->branch_id);
        $this->assertFalse($member->is_walk_in);
    }

    public function test_cashier_keeps_assigned_branch_stock_when_session_points_elsewhere(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $pusatProduct = $this->productWithStock(10);
        $bandung = $this->createBandungBranch($owner);
        $this->aquaAt($bandung, 1);

        $this->actingAs($cashier)
            ->withSession(['current_branch_id' => $bandung->id])
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pos/Index')
                ->has('products', 1)
                ->where('products.0.id', $pusatProduct->id));
    }

    public function test_dashboard_and_sales_list_only_the_active_branch(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $pusat = Branch::query()->orderBy('id')->first();
        $bandung = $this->createBandungBranch($owner);
        $this->productWithStock(10);

        $pusatSale = Sale::factory()->completed()->create([
            'branch_id' => $pusat->id,
            'grand_total' => 8000,
            'completed_at' => now(),
        ]);
        $bandungSale = Sale::factory()->completed()->create([
            'branch_id' => $bandung->id,
            'grand_total' => 25000,
            'completed_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('branches.switch', $bandung))
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.revenue_today', 25000)
                ->where('stats.transactions_today', 1)
                ->where('stats.products', 0)
                ->where('stats.customers', 0));

        $this->actingAs($owner)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Index')
                ->has('sales.data', 1)
                ->where('sales.data.0.id', $bandungSale->id));

        $this->actingAs($owner)
            ->get(route('sales.index', ['sale' => $pusatSale->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Index')
                ->where('viewingSale', null));

        $this->actingAs($owner)
            ->get(route('sales.show', $pusatSale))
            ->assertNotFound();
    }

    public function test_reports_and_expenses_list_only_the_active_branch(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $pusat = Branch::query()->orderBy('id')->first();
        $bandung = $this->createBandungBranch($owner);
        $this->productWithStock(10);
        $aqua = $this->aquaAt($bandung, 1);

        Expense::factory()->create([
            'branch_id' => $pusat->id,
            'description' => 'Biaya pusat',
        ]);
        $bandungExpense = Expense::factory()->create([
            'branch_id' => $bandung->id,
            'description' => 'Biaya bandung',
        ]);

        $this->actingAs($owner)
            ->post(route('branches.switch', $bandung))
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->has('inventory', 1)
                ->where('inventory.0.product_id', $aqua->id)
                ->where('customerCount', 0));

        $this->actingAs($owner)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses/Index')
                ->has('expenses.data', 1)
                ->where('expenses.data.0.id', $bandungExpense->id));
    }

    public function test_shifts_index_lists_only_the_active_branch(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $pusat = Branch::query()->orderBy('id')->first();
        $bandung = $this->createBandungBranch($owner);

        CashierShift::factory()->create(['branch_id' => $pusat->id]);
        $bandungShift = CashierShift::factory()->create(['branch_id' => $bandung->id]);

        $this->actingAs($owner)
            ->post(route('branches.switch', $bandung))
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('shifts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shifts/Index')
                ->has('shifts.data', 1)
                ->where('shifts.data.0.id', $bandungShift->id));
    }

    private function createBandungBranch(User $owner): Branch
    {
        $this->actingAs($owner)->post(route('branches.store'), [
            'name' => 'Cabang Bandung',
            'code' => 'BDG',
        ])->assertRedirect(route('branches.index'));

        return Branch::query()->where('code', 'BDG')->firstOrFail();
    }

    private function aquaAt(Branch $branch, float $quantity): Product
    {
        $product = Product::factory()->create(['name' => 'Aqua 600ml']);

        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $branch->defaultWarehouse()->id,
            'quantity' => $quantity,
            'minimum_stock' => 0,
        ]);

        return $product;
    }
}
