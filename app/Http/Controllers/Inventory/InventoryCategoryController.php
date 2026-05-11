<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryCategoryController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;

        $categories = InventoryCategory::where('inventory_categories.tenant_id', $tenant->id)
            ->withCount(['items' => fn($q) => $q->where('is_active', true)])
            ->withoutGlobalScope('tenant')
            ->orderBy('name')
            ->get();

        return view('inventory.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', InventoryCategory::class);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        InventoryCategory::create([
            ...$validated,
            'tenant_id' => auth()->user()->tenant_id,
            'is_active' => true,
        ]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, InventoryCategory $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(InventoryCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->items()->exists()) {
            return back()->with('error', 'Cannot delete a category that has items. Reassign the items first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
