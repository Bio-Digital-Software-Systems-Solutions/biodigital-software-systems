<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Stock;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view stocks')->only(['index', 'show']);
        $this->middleware('can:manage stocks')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index()
    {
        $stocks = Stock::with(['category'])
            ->when(request('category'), function ($query, $category) {
                $query->where('category_id', $category);
            })
            ->when(request('status'), function ($query, $status) {
                if ($status === 'low_stock') {
                    $query->lowStock();
                } elseif ($status === 'out_of_stock') {
                    $query->outOfStock();
                } elseif ($status === 'expired') {
                    $query->expired();
                } elseif ($status === 'near_expiry') {
                    $query->nearExpiry();
                }
            })
            ->when(request('supplier'), function ($query, $supplier) {
                $query->bySupplier($supplier);
            })
            ->orderBy('name')
            ->paginate(10);

        $categories = Category::all();

        return Inertia::render('Stocks/Index', [
            'stocks' => $stocks,
            'categories' => $categories,
            'filters' => request()->only(['category', 'status', 'supplier']),
        ]);
    }

    public function create()
    {
        $categories = Category::all();

        return Inertia::render('Stocks/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:stocks',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'minimum_quantity' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('stocks', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Image has already been uploaded via TUS to stocks directory
            $validated['image'] = 'stocks/' . $request->image;
        }

        $stock = Stock::create($validated);

        return redirect()->route('stocks.index')
            ->with('success', 'Stock item created successfully.');
    }

    public function show(Stock $stock)
    {
        $stock->load(['category']);

        return Inertia::render('Stocks/Show', [
            'stock' => $stock,
        ]);
    }

    public function edit(Stock $stock)
    {
        $stock->load(['category']);
        $categories = Category::all();

        return Inertia::render('Stocks/Edit', [
            'stock' => $stock,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:stocks,sku,'.$stock->id,
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'minimum_quantity' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($stock->image) {
                \Storage::disk('public')->delete($stock->image);
            }
            $validated['image'] = $request->file('image')->store('stocks', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Delete old image if it exists
            if ($stock->image) {
                \Storage::disk('public')->delete($stock->image);
            }
            // Image has already been uploaded via TUS to stocks directory
            $validated['image'] = 'stocks/' . $request->image;
        }

        $stock->update($validated);

        return redirect()->route('stocks.index')
            ->with('success', 'Stock item updated successfully.');
    }

    public function destroy(Stock $stock)
    {
        $stock->delete();

        return redirect()->route('stocks.index')
            ->with('success', 'Stock item deleted successfully.');
    }
}
