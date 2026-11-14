<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Product;
use Illuminate\Http\Request;

final class GlobalSearchController
{
    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $term = trim((string) ($data['q'] ?? ''));
        $leads = collect();
        $products = collect();

        if ($term !== '') {
            // Escape LIKE wildcards so the search text is always treated literally.
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
            $leads = Lead::visibleTo($request->user())
                ->with('owner:id,name')
                ->withCount(['activities', 'workItems'])
                ->where(function ($query) use ($like) {
                    foreach (['name', 'company', 'phone', 'alt_phone', 'whatsapp', 'email'] as $column) {
                        $query->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$like]);
                    }
                })
                ->orderBy('name')
                ->limit(25)
                ->get();

            if ($request->user()->allows('products.view')) {
                $products = Product::query()
                    ->where('active', true)
                    ->where(function ($query) use ($like) {
                        $query->whereRaw("name LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("item_code LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("category LIKE ? ESCAPE '!'", [$like]);
                    })
                    ->orderBy('name')
                    ->limit(15)
                    ->get(['id', 'name', 'item_code', 'category', 'unit']);
            }
        }

        return view('global-search.index', compact('term', 'leads', 'products'));
    }
}
