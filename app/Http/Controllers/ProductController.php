<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $stockFilter = $request->input('stock_filter'); // 'all', 'low', 'out'
        $status = $request->input('status'); // 'all', 'active', 'inactive'
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = Product::with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%")
                    ->orWhere('barcode', 'LIKE', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($stockFilter === 'low') {
            $query->lowStock();
        } elseif ($stockFilter === 'out') {
            $query->outOfStock();
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $allowedSorts = ['name', 'sku', 'selling_price', 'stock', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        // Ringkasan stok
        $totalProducts = Product::count();
        $lowStockCount = Product::active()->lowStock()->count();
        $outOfStockCount = Product::active()->outOfStock()->count();

        return view('products.index', compact(
            'products',
            'categories',
            'search',
            'categoryId',
            'stockFilter',
            'status',
            'sortBy',
            'sortOrder',
            'totalProducts',
            'lowStockCount',
            'outOfStockCount'
        ));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sku' => 'required|string|max:50|unique:products,sku',
            'barcode' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'unit' => 'required|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $product = Product::create($validated);

        // Catat stok awal jika ada stok
        if ($product->stock > 0) {
            InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'initial',
                'quantity' => $product->stock,
                'before_stock' => 0,
                'after_stock' => $product->stock,
                'reason' => 'Stok awal penambahan produk baru',
            ]);
        }

        AuditLog::record('product_created', $product, null, $product->toArray());

        return redirect()->route('products.index')->with('success', "Produk '{$product->name}' berhasil ditambahkan.");
    }

    public function show($id)
    {
        $product = Product::with(['category', 'inventoryMovements.user'])->findOrFail($id);
        return view('products.show', compact('product'));
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sku' => "required|string|max:50|unique:products,sku,{$id}",
            'barcode' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
            'unit' => 'required|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $oldValues = $product->toArray();

        $product->update($validated);

        AuditLog::record('product_updated', $product, $oldValues, $product->toArray());

        return redirect()->route('products.index')->with('success', "Produk '{$product->name}' berhasil diperbarui.");
    }

    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();

        $statusStr = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';
        AuditLog::record('product_status_toggled', $product, null, ['is_active' => $product->is_active]);

        return back()->with('success', "Status produk '{$product->name}' berhasil {$statusStr}.");
    }

    public function exportCsv()
    {
        $products = Product::with('category')->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="data_produk_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'SKU', 'Barcode', 'Nama Produk', 'Kategori', 'Harga Modal (HPP)', 'Harga Jual', 'Stok', 'Batas Min', 'Satuan', 'Status']);

            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->sku,
                    $p->barcode,
                    $p->name,
                    $p->category->name ?? '-',
                    auth()->user()->isAdmin() ? $p->cost_price : '-',
                    $p->selling_price,
                    $p->stock,
                    $p->min_stock,
                    $p->unit,
                    $p->is_active ? 'Aktif' : 'Nonaktif',
                ]);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
