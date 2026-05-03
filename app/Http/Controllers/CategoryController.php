<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = Category::orderBy('name')
            ->get(['id', 'name', 'description', 'type', 'parent_id', 'created_at']);

        $parentCandidates = Category::doesntHave('transactions')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'parent_id']);

        return Inertia::render('Categories', [
            'categories' => $categories,
            'parentCandidates' => $parentCandidates,
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:revenue,expense',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($value && Category::find($value)?->transactions()->exists()) {
                        $fail('The selected parent already has transactions and cannot be a parent.');
                    }
                },
            ],
        ]);

        Category::create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        // Optional - only if you need a detail page
        return Inertia::render('Categories/Show', [
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        // Not needed for Inertia - forms handled in frontend
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:revenue,expense',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    if (! $value) {
                        return;
                    }
                    if ((int) $value === $category->id) {
                        $fail('A category cannot be its own parent.');
                        return;
                    }
                    if (Category::find($value)?->transactions()->exists()) {
                        $fail('The selected parent already has transactions and cannot be a parent.');
                        return;
                    }
                    // Prevent circular references
                    $current = Category::find($value);
                    while ($current?->parent_id) {
                        if ($current->parent_id === $category->id) {
                            $fail('The selected parent would create a circular reference.');
                            return;
                        }
                        $current = Category::find($current->parent_id);
                    }
                },
            ],
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
