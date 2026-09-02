<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PosCustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_search_redirects_to_cashier_login(): void
    {
        $this->get(route('pos.customers.search'))
            ->assertRedirect(route('cashier.login'));
    }

    public function test_search_is_blocked_when_shift_is_not_open(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('pos.customers.search'))
            ->assertRedirect(route('shifts.open'));
    }

    public function test_pos_page_includes_walk_in_customer_with_points(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $walkIn = Customer::query()->walkIn()->first();

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pos/Index')
                ->has('customers', 1)
                ->where('customers.0.id', $walkIn->id)
                ->where('customers.0.name', $walkIn->name)
                ->where('customers.0.phone', $walkIn->phone)
                ->where('customers.0.points', 0)
                ->where('customers.0.is_walk_in', true));
    }

    public function test_cashier_can_search_members_by_name_and_phone(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $match = Customer::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'points' => 120,
        ]);
        Customer::factory()->create([
            'name' => 'Siti Rahma',
            'phone' => '08999888777',
            'points' => 10,
        ]);

        $byName = $this->actingAs($cashier)
            ->getJson(route('pos.customers.search', ['q' => 'Budi']))
            ->assertOk()
            ->json('customers');

        $this->assertTrue($byName[0]['is_walk_in']);
        $this->assertSame($match->id, $byName[1]['id']);
        $this->assertSame('Budi Santoso', $byName[1]['name']);
        $this->assertSame('081234567890', $byName[1]['phone']);
        $this->assertSame(120, $byName[1]['points']);
        $this->assertCount(2, $byName);

        $byPhone = $this->actingAs($cashier)
            ->getJson(route('pos.customers.search', ['q' => '081234']))
            ->assertOk()
            ->json('customers');

        $this->assertSame($match->id, $byPhone[1]['id']);
        $this->assertCount(2, $byPhone);
    }

    public function test_empty_search_keeps_walk_in_first_and_limits_members(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        Customer::factory()->count(30)->create();

        $customers = $this->actingAs($cashier)
            ->getJson(route('pos.customers.search'))
            ->assertOk()
            ->json('customers');

        $this->assertTrue($customers[0]['is_walk_in']);
        $this->assertCount(26, $customers);
        $this->assertFalse($customers[1]['is_walk_in']);
        $this->assertArrayHasKey('phone', $customers[1]);
        $this->assertArrayHasKey('points', $customers[1]);
    }

    public function test_creating_a_pos_customer_flashes_the_new_member(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);

        $response = $this->actingAs($cashier)
            ->from(route('pos.index'))
            ->post(route('pos.customers.store'), [
                'name' => 'Andi Wijaya',
                'phone' => '087700011122',
            ]);

        $customer = Customer::query()->where('name', 'Andi Wijaya')->first();

        $this->assertNotNull($customer);
        $this->assertSame('087700011122', $customer->phone);
        $this->assertFalse($customer->is_walk_in);
        $this->assertSame($cashier->branch_id, $customer->branch_id);

        $response->assertRedirect(route('pos.index'))
            ->assertSessionHas('success', 'Pelanggan ditambahkan.')
            ->assertSessionHas('created_customer', function (array $payload) use ($customer): bool {
                return $payload['id'] === $customer->id
                    && $payload['name'] === 'Andi Wijaya'
                    && $payload['phone'] === '087700011122'
                    && $payload['points'] === 0
                    && $payload['is_walk_in'] === false;
            });
    }

    public function test_search_omits_members_from_another_branch(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        Customer::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
        ]);

        $otherBranch = Branch::factory()->create([
            'tenant_id' => $cashier->tenant_id,
        ]);
        Customer::factory()->create([
            'branch_id' => $otherBranch->id,
            'name' => 'Siti Bandung',
            'phone' => '08999888777',
        ]);

        $customers = $this->actingAs($cashier)
            ->getJson(route('pos.customers.search', ['q' => 'Siti']))
            ->assertOk()
            ->json('customers');

        $this->assertCount(1, $customers);
        $this->assertTrue($customers[0]['is_walk_in']);
    }
}
