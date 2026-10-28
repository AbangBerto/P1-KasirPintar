<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function index(Request $request)
    {
        $productId = $request->input('product_id');
        $type = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = InventoryMovement::with(['product', 'user', 'transaction']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        $movements = $query->latest()->paginate(20)->withQueryString();

        // Data produk untuk filter & form adjustment
        $allProducts = Product::orderBy('name')->get();
        $lowStockProducts = Product::active()->lowStock()->with('category')->get();
        $outOfStockProducts = Product::active()->outOfStock()->with('category')->get();

        return view('inventory.index', compact(
            'movements',
            'allProducts',
            'lowStockProducts',
            'outOfStockProducts',
            'productId',
            'type',
            'startDate',
            'endDate'
        ));
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'action' => 'required|in:add,subtract,set',
            'quantity' => 'required|integer|min:1',
            'reason_type' => 'required|in:restock,damaged,return,adjustment',
            'notes' => 'required|string|max:255',
        ]);

        $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
        $beforeStock = $product->stock;
        $qtyInput = (int) $validated['quantity'];

        if ($validated['action'] === 'add') {
            $afterStock = $beforeStock + $qtyInput;
            $movementQty = $qtyInput;
        } elseif ($validated['action'] === 'subtract') {
            if ($beforeStock < $qtyInput) {
                return back()->with('error', "Gagal: Pengurangan ({$qtyInput}) melebihi stok yang ada ({$beforeStock}).");
            }
            $afterStock = $beforeStock - $qtyInput;
            $movementQty = -$qtyInput;
        } else {
            // Set exact stock
            $afterStock = $qtyInput;
            $movementQty = $afterStock - $beforeStock;
        }

        DB::transaction(function () use ($product, $beforeStock, $afterStock, $movementQty, $validated) {
            $product->update(['stock' => $afterStock]);

            $movement = InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => $validated['reason_type'],
                'quantity' => $movementQty,
                'before_stock' => $beforeStock,
                'after_stock' => $afterStock,
                'reason' => $validated['notes'],
            ]);

            AuditLog::record('stock_adjusted', $product, ['stock' => $beforeStock], [
                'stock' => $afterStock,
                'movement_id' => $movement->id,
                'reason' => $validated['notes'],
            ]);
        });

        // Trigger WhatsApp alert jika setelah penyesuaian stok menjadi menipis
        if ($product->fresh()->isLowStock()) {
            try {
                $this->whatsAppService->sendLowStockAlert($product);
            } catch (\Throwable $e) {
                // Ignore alert notification fail
            }
        }

        return redirect()->route('inventory.index')->with('success', "Stok {$product->name} berhasil disesuaikan menjadi {$afterStock} {$product->unit}.");
    }
}
