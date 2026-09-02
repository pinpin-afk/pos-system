<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ActivityLogger;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        $branchId = $location->branch($request->user())?->id;

        return Inertia::render('Expenses/Index', [
            'expenses' => Expense::query()
                ->forBranch($branchId)
                ->with(['branch:id,name', 'user:id,name'])
                ->latest('spent_on')
                ->latest('id')
                ->paginate(15),
            'creating' => $request->boolean('create'),
            'editingExpense' => $request->filled('edit')
                ? Expense::query()->forBranch($branchId)->find($request->integer('edit'))
                : null,
            'categories' => ['Operasional', 'Sewa', 'Gaji', 'Utilitas', 'Lainnya'],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('expenses.index', ['create' => 1]);
    }

    public function store(StoreExpenseRequest $request, LocationContext $location, ActivityLogger $logger): RedirectResponse
    {
        $expense = Expense::query()->create([
            ...$request->safe()->except(['branch_id']),
            'user_id' => $request->user()->id,
            'branch_id' => $location->branch($request->user())?->id,
        ]);

        $logger->log($request->user(), 'expense.created', $expense);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran dicatat.');
    }

    public function edit(Expense $expense): RedirectResponse
    {
        return redirect()->route('expenses.index', ['edit' => $expense->id]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, LocationContext $location): RedirectResponse
    {
        $this->ensureExpenseBelongsToCurrentBranch($expense, $location->branch($request->user())?->id);

        $expense->update($request->safe()->except(['branch_id']));

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran diperbarui.');
    }

    public function destroy(Request $request, Expense $expense, LocationContext $location): RedirectResponse
    {
        $this->ensureExpenseBelongsToCurrentBranch($expense, $location->branch($request->user())?->id);

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran dihapus.');
    }

    private function ensureExpenseBelongsToCurrentBranch(Expense $expense, ?int $branchId): void
    {
        abort_unless($expense->branch_id !== null && (int) $expense->branch_id === (int) $branchId, 404);
    }
}
