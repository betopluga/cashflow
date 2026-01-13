<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::with(['user', 'category'])
            ->latest('date')
            ->get();

        $categories = Category::all();

        return Inertia::render('Transactions', [
            'transactions' => $transactions,
            'categories' => $categories,
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
            'category_id' => 'nullable|exists:categories,id',
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
            'category_id' => 'nullable|exists:categories,id',
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
}
