<?php

namespace App\Http\Controllers;

use App\Models\StorefrontCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;

        $categories = StorefrontCategory::where('storefront_categories.tenant_id', $tenant->id)
            ->withoutGlobalScope('tenant')
            ->withCount(['products', 'services'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('storefront_admin.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        StorefrontCategory::withoutGlobalScope('tenant')->create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
            'is_active' => true,
        ]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, StorefrontCategory $storefrontCategory): RedirectResponse
    {
        $this->authorizeCategory($request, $storefrontCategory);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ]);

        $storefrontCategory->update($validated);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Request $request, StorefrontCategory $storefrontCategory): RedirectResponse
    {
        $this->authorizeCategory($request, $storefrontCategory);

        $storefrontCategory->delete();

        return back()->with('success', 'Category deleted. Its products and services are now uncategorized.');
    }

    private function authorizeCategory(Request $request, StorefrontCategory $storefrontCategory): void
    {
        abort_unless($storefrontCategory->tenant_id === $request->user()->tenant_id, 403);
    }
}
