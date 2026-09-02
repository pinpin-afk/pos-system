<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_record_expense(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('expenses.store'), [
            'category' => 'Operasional',
            'amount' => 75000,
            'spent_on' => now()->toDateString(),
            'description' => 'Beli galon',
        ])->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'category' => 'Operasional',
            'amount' => 75000,
            'user_id' => $owner->id,
            'branch_id' => $owner->branch_id,
        ]);
        $this->assertSame(1, Expense::query()->count());
    }

    public function test_cashier_cannot_view_expenses(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('expenses.index'))->assertForbidden();
    }
}
