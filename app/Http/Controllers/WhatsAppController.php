<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\WhatsappMessageLog;
use App\Models\WhatsappSetting;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function index()
    {
        $settings = [
            'api_endpoint' => WhatsappSetting::get('api_endpoint', 'https://graph.facebook.com/v19.0'),
            'phone_number_id' => WhatsappSetting::get('phone_number_id', ''),
            'access_token' => WhatsappSetting::get('access_token', ''),
            'recipient_number' => WhatsappSetting::get('recipient_number', '6281234567890'),
            'daily_report_enabled' => WhatsappSetting::get('daily_report_enabled', '1') === '1',
            'daily_report_time' => WhatsappSetting::get('daily_report_time', '22:05'),
            'stock_alert_enabled' => WhatsappSetting::get('stock_alert_enabled', '1') === '1',
            'demo_mode' => WhatsappSetting::get('demo_mode', '1') === '1',
        ];

        $logs = WhatsappMessageLog::latest()->paginate(15);

        return view('whatsapp.index', compact('settings', 'logs'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'api_endpoint' => 'required|url',
            'phone_number_id' => 'nullable|string',
            'access_token' => 'nullable|string',
            'recipient_number' => 'required|string',
            'daily_report_time' => 'required|string',
        ]);

        WhatsappSetting::set('api_endpoint', $validated['api_endpoint']);
        WhatsappSetting::set('phone_number_id', $validated['phone_number_id']);
        if (!empty($validated['access_token'])) {
            WhatsappSetting::set('access_token', $validated['access_token']);
        }
        WhatsappSetting::set('recipient_number', $validated['recipient_number']);
        WhatsappSetting::set('daily_report_time', $validated['daily_report_time']);
        WhatsappSetting::set('daily_report_enabled', $request->boolean('daily_report_enabled') ? '1' : '0');
        WhatsappSetting::set('stock_alert_enabled', $request->boolean('stock_alert_enabled') ? '1' : '0');
        WhatsappSetting::set('demo_mode', $request->boolean('demo_mode') ? '1' : '0');

        AuditLog::record('whatsapp_settings_updated');

        return back()->with('success', 'Pengaturan integrasi WhatsApp berhasil diperbarui.');
    }

    public function sendTest(Request $request)
    {
        $request->validate([
            'recipient_number' => 'required|string',
        ]);

        $recipient = $request->input('recipient_number');
        $testMsg = "✅ *UJI KONEKSI WHATSAPP — KASIR PINTAR*\n\nSistem kasir berhasil terhubung dengan gateway notifikasi WhatsApp.\n\nWaktu pengujian: " . now()->format('d/m/Y H:i:s') . "\nStatus: Aktif & Siap Digunakan.";

        $result = $this->whatsAppService->sendMessage($recipient, $testMsg, 'test');

        if ($result['success']) {
            return back()->with([
                'success' => $result['message'],
                'wa_direct_url' => $result['wa_url'] ?? null,
            ]);
        }

        return back()->with('error', 'Gagal mengirim pesan uji: ' . ($result['error'] ?? 'Terjadi kesalahan'));
    }

    public function triggerDailyReport(Request $request)
    {
        $date = $request->input('date');
        $result = $this->whatsAppService->sendDailyReport($date);

        if ($result['success']) {
            return back()->with([
                'success' => 'Laporan harian berhasil digenerate dan dikirim!',
                'wa_direct_url' => $result['wa_url'] ?? null,
            ]);
        }

        return back()->with('error', 'Gagal memproses laporan: ' . ($result['error'] ?? 'Terjadi kesalahan'));
    }

    public function retryLog($id)
    {
        $log = WhatsappMessageLog::findOrFail($id);
        $result = $this->whatsAppService->sendMessage($log->recipient_phone, $log->content, $log->message_type);

        return back()->with('success', 'Pesan diproses ulang. ' . ($result['message'] ?? ''));
    }
}
