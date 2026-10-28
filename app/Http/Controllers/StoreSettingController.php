<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StoreSetting;
use Illuminate\Http\Request;

class StoreSettingController extends Controller
{
    public function index()
    {
        $settings = [
            'store_name' => StoreSetting::get('store_name', 'Minimarket Berkah Jaya'),
            'store_tagline' => StoreSetting::get('store_tagline', 'Belanja Hemat & Lengkap Setiap Hari'),
            'store_address' => StoreSetting::get('store_address', 'Jl. Merdeka No. 45 Jakarta'),
            'store_phone' => StoreSetting::get('store_phone', '0812-3456-7890'),
            'store_email' => StoreSetting::get('store_email', 'toko@berkahjaya.com'),
            'opening_hour' => StoreSetting::get('opening_hour', '08:00'),
            'closing_hour' => StoreSetting::get('closing_hour', '22:00'),
            'business_day_cutoff' => StoreSetting::get('business_day_cutoff', '04:00'),
            'timezone' => StoreSetting::get('timezone', 'Asia/Jakarta'),
            'tax_percentage' => StoreSetting::get('tax_percentage', '0'),
            'receipt_header' => StoreSetting::get('receipt_header', ''),
            'receipt_footer' => StoreSetting::get('receipt_footer', 'Terima kasih atas kunjungan Anda!'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:150',
            'store_tagline' => 'nullable|string|max:200',
            'store_address' => 'required|string|max:255',
            'store_phone' => 'required|string|max:50',
            'store_email' => 'nullable|email|max:100',
            'opening_hour' => 'required|string',
            'closing_hour' => 'required|string',
            'business_day_cutoff' => 'required|string',
            'timezone' => 'required|string',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'receipt_header' => 'nullable|string',
            'receipt_footer' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            StoreSetting::set($key, $value);
        }

        AuditLog::record('store_settings_updated');

        return back()->with('success', 'Pengaturan toko berhasil diperbarui.');
    }
}
