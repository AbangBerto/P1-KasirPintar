<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Customer::withCount('transactions');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create(array_merge($validated, ['is_active' => true]));

        AuditLog::record('customer_created', $customer, null, $customer->toArray());

        return redirect()->route('customers.index')->with('success', "Pelanggan '{$customer->name}' berhasil ditambahkan.");
    }

    public function show($id)
    {
        $customer = Customer::with(['transactions.items'])->findOrFail($id);
        return view('customers.show', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldValues = $customer->toArray();
        $validated['is_active'] = $request->boolean('is_active', true);
        $customer->update($validated);

        AuditLog::record('customer_updated', $customer, $oldValues, $customer->toArray());

        return redirect()->route('customers.index')->with('success', "Data pelanggan '{$customer->name}' berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $customer = Customer::withCount('transactions')->findOrFail($id);

        if ($customer->transactions_count > 0) {
            // Soft deactivate rather than hard delete if has history
            $customer->update(['is_active' => false]);
            return back()->with('success', "Pelanggan dinonaktifkan karena telah memiliki riwayat belanja.");
        }

        $oldValues = $customer->toArray();
        $customer->delete();

        AuditLog::record('customer_deleted', null, $oldValues, null);

        return redirect()->route('customers.index')->with('success', "Pelanggan berhasil dihapus.");
    }
}
