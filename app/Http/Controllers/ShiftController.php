<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\CashShift;
use App\Models\StoreSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Cek shift aktif milik user saat ini
        $activeShift = CashShift::with(['movements.user', 'transactions'])
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        // Riwayat shift sebelumnya
        $shiftsQuery = CashShift::with('user');
        if (!$user->isAdmin()) {
            $shiftsQuery->where('user_id', $user->id);
        }
        $shifts = $shiftsQuery->latest('opened_at')->paginate(15);

        return view('shifts.index', compact('activeShift', 'shifts'));
    }

    public function open(Request $request)
    {
        $user = auth()->user();

        // Cegah membuka shift baru jika masih ada shift aktif
        $existing = CashShift::where('user_id', $user->id)->where('status', 'open')->first();
        if ($existing) {
            return back()->with('error', 'Anda masih memiliki shift kasir yang sedang aktif.');
        }

        $validated = $request->validate([
            'starting_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $shiftCountToday = CashShift::whereDate('opened_at', Carbon::today())->count() + 1;
        $shiftNumber = 'SHIFT-' . date('Ymd') . '-' . str_pad($shiftCountToday, 3, '0', STR_PAD_LEFT);

        $shift = CashShift::create([
            'user_id' => $user->id,
            'shift_number' => $shiftNumber,
            'opened_at' => Carbon::now(),
            'starting_cash' => $validated['starting_cash'],
            'cash_sales' => 0,
            'non_cash_sales' => 0,
            'cash_in' => 0,
            'cash_out' => 0,
            'cash_refunds' => 0,
            'expected_cash' => $validated['starting_cash'],
            'status' => 'open',
            'notes' => $validated['notes'],
        ]);

        AuditLog::record('shift_opened', $shift, null, [
            'shift_number' => $shift->shift_number,
            'starting_cash' => $shift->starting_cash,
        ]);

        return redirect()->route('pos.index')->with('success', "Shift baru ({$shift->shift_number}) berhasil dibuka dengan modal awal Rp " . number_format($shift->starting_cash, 0, ',', '.'));
    }

    public function addMovement(Request $request, $id)
    {
        $shift = CashShift::findOrFail($id);

        if (!$shift->isOpen()) {
            return back()->with('error', 'Shift ini sudah ditutup.');
        }

        $validated = $request->validate([
            'type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:100',
            'reason' => 'required|string|min:3|max:255',
        ]);

        $movement = CashMovement::create([
            'cash_shift_id' => $shift->id,
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
        ]);

        if ($validated['type'] === 'cash_in') {
            $shift->increment('cash_in', $validated['amount']);
        } else {
            $shift->increment('cash_out', $validated['amount']);
        }

        $shift->recalculateExpectedCash();
        $shift->save();

        AuditLog::record('cash_movement_recorded', $shift, null, [
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
        ]);

        $typeLabel = $validated['type'] === 'cash_in' ? 'Kas Masuk' : 'Kas Keluar (Petty Cash)';
        return back()->with('success', "{$typeLabel} sebesar Rp " . number_format($validated['amount'], 0, ',', '.') . " berhasil dicatat.");
    }

    public function close(Request $request, $id)
    {
        $shift = CashShift::findOrFail($id);

        if (!$shift->isOpen()) {
            return back()->with('error', 'Shift ini sudah ditutup sebelumnya.');
        }

        $validated = $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string|max:500',
        ]);

        $expectedCash = $shift->recalculateExpectedCash();
        $actualCash = (float) $validated['actual_cash'];
        $difference = $actualCash - $expectedCash;

        $notes = $shift->notes ? $shift->notes . " | Penutupan: " . $validated['closing_notes'] : $validated['closing_notes'];

        $shift->update([
            'closed_at' => Carbon::now(),
            'actual_cash' => $actualCash,
            'difference' => $difference,
            'status' => 'closed',
            'notes' => $notes,
        ]);

        AuditLog::record('shift_closed', $shift, null, [
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'difference' => $difference,
        ]);

        return redirect()->route('shifts.show', $shift->id)->with('success', "Shift {$shift->shift_number} berhasil ditutup dan direkonsiliasi.");
    }

    public function show($id)
    {
        $shift = CashShift::with(['user', 'movements.user', 'transactions.items'])->findOrFail($id);
        $storeName = StoreSetting::get('store_name', 'Kasir Pintar Minimarket');

        return view('shifts.show', compact('shift', 'storeName'));
    }
}
