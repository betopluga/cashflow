<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Rules\IsLeafCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'category']);

        // Handle search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('category', function ($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Handle date range filtering
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Handle sorting
        $sortBy = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate results
        $perPage = $request->get('per_page', 10);
        $transactions = $query->paginate($perPage)->withQueryString();

        $categories = Category::select('id', 'name', 'type', 'parent_id')->orderBy('name')->get();

        return Inertia::render('Transactions', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not needed for Inertia - forms handled in frontend
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', new IsLeafCategory],
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        $validated['user_id'] = $request->user()->id;

        Transaction::create($validated);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {

        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction->load(['user', 'category']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        // Not needed for Inertia - forms handled in frontend
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {

        $validated = $request->validate([
            'category_id' => ['nullable', new IsLeafCategory],
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        $transaction->update($validated);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {

        $transaction->delete();

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }

    /**
     * Return total income and expenses for a given month/year.
     */
    public function totals(Request $request)
    {
        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        $totals = Transaction::join('categories', 'categories.id', '=', 'transactions.category_id')
            ->whereMonth('transactions.date', $month)
            ->whereYear('transactions.date', $year)
            ->selectRaw('categories.type, SUM(transactions.amount) as total')
            ->groupBy('categories.type')
            ->pluck('total', 'type');

        return response()->json([
            'income'   => round((float) ($totals['income']   ?? 0), 2),
            'expenses' => round((float) ($totals['expense'] ?? 0), 2),
        ]);
    }

    /**
     * Return distinct years that have transactions.
     */
    public function years()
    {
        $years = Transaction::get(['date'])
            ->map(fn($t) => (int) $t->date->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        return response()->json($years);
    }

    /**
     * Return monthly income and expenses for a given year.
     */
    public function graphByYear(Request $request)
    {
        $year = $request->integer('year', now()->year);

        $grouped = Transaction::with('category')
            ->whereYear('date', $year)
            ->get()
            ->groupBy(fn($t) => (int) $t->date->format('n'));

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $group   = $grouped->get($m, collect());
            $income  = $group->filter(fn($t) => $t->category?->type === 'income')->sum('amount');
            $expense = $group->filter(fn($t) => $t->category?->type === 'expense')->sum('amount');
            $months[] = [
                'month'    => $m,
                'income'   => round((float) $income,  2),
                'expenses' => round((float) $expense, 2),
            ];
        }

        return response()->json($months);
    }

    /**
     * Return per-category monthly totals for a given year and category type.
     */
    public function categoryBreakdown(Request $request)
    {
        $year = $request->integer('year', now()->year);
        $type = $request->string('type', 'expense')->toString();

        $transactions = Transaction::with('category')
            ->whereYear('date', $year)
            ->whereHas('category', fn($q) => $q->where('type', $type))
            ->get();

        $grouped = $transactions->groupBy('category_id');

        $result = $grouped->map(function ($items) {
            $category = $items->first()->category;
            $months   = array_fill(0, 12, 0.0);

            foreach ($items as $t) {
                $idx          = (int) $t->date->format('n') - 1;
                $months[$idx] = round($months[$idx] + (float) $t->amount, 2);
            }

            return [
                'id'     => $category->id,
                'name'   => $category->name,
                'months' => $months,
            ];
        })->values();

        return response()->json($result);
    }
}