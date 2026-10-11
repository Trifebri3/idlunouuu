<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\NfcDevice;
use App\Models\NfcOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminOrderController extends Controller
{
    /**
     * Create offline order (from WhatsApp, events, or external purchase) and issue an order code.
     */
    public function createOffline(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'card_printed_name' => ['required', 'string', 'max:100'],
            'phone_or_whatsapp' => ['required', 'string', 'max:50'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'custom_image' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $orderNumber = 'LUNOU-'.date('ymd').'-'.strtoupper(Str::random(4));

        $order = NfcOrder::create([
            'user_id' => $validated['user_id'] ?? null,
            'order_number' => $orderNumber,
            'card_variant' => 'custom_keychain',
            'card_printed_name' => $validated['card_printed_name'],
            'custom_image' => $validated['custom_image'] ?? '/icon/1.png',
            'phone_or_whatsapp' => $validated['phone_or_whatsapp'],
            'shipping_address' => $validated['shipping_address'] ?? 'Pesanan Luar Web / Offline',
            'notes' => $validated['notes'] ?? 'Dibuat oleh Admin (Offline / WhatsApp)',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'status' => 'processing',
            'amount' => 49000,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_offline_order_created',
            'target_type' => 'nfc_order',
            'target_id' => $order->id,
            'metadata' => ['order_number' => $orderNumber],
        ]);

        return back()->with('success', "Pesanan luar web berhasil dibuat dengan Kode Order {$orderNumber}! Pembeli dapat me-redeem kode ini di website.");
    }

    /**
     * Connect an unassigned RFID/NFC card to a user's order and activate it.
     */
    public function connectNfc(Request $request, NfcOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'nfc_device_id' => ['nullable', 'exists:nfc_devices,id'],
            'auto_generate' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['auto_generate'])) {
            $publicId = 'YOIMO-'.strtoupper(Str::random(7));
            while (NfcDevice::where('public_id', $publicId)->exists()) {
                $publicId = 'YOIMO-'.strtoupper(Str::random(7));
            }

            $device = NfcDevice::create([
                'public_id' => $publicId,
                'label' => 'YOIMO '.ucwords(str_replace('_', ' ', $order->card_variant))." ({$order->card_printed_name})",
                'user_id' => $order->user_id,
                'status' => 'active',
                'activated_at' => now(),
            ]);
        } else {
            $request->validate([
                'nfc_device_id' => ['required', 'exists:nfc_devices,id'],
            ]);

            $device = NfcDevice::findOrFail($validated['nfc_device_id']);

            $device->update([
                'user_id' => $order->user_id,
                'label' => 'YOIMO '.ucwords(str_replace('_', ' ', $order->card_variant))." ({$order->card_printed_name})",
                'status' => 'active',
                'activated_at' => now(),
            ]);
        }

        $order->update([
            'nfc_device_id' => $device->id,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_order_nfc_connected',
            'target_type' => 'nfc_order',
            'target_id' => $order->id,
            'metadata' => [
                'order_number' => $order->order_number,
                'public_id' => $device->public_id,
                'user_id' => $order->user_id,
            ],
        ]);

        return back()->with('success', "Kartu fisik {$device->public_id} berhasil disambungkan ke pesanan {$order->order_number} & akun {$order->user?->name}!");
    }

    /**
     * Quickly verify payment and advance order to processing.
     */
    public function verifyPayment(Request $request, NfcOrder $order): RedirectResponse
    {
        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_order_payment_verified',
            'target_type' => 'nfc_order',
            'target_id' => $order->id,
            'metadata' => ['order_number' => $order->order_number],
        ]);

        return back()->with('success', "Pembayaran untuk pesanan {$order->order_number} telah diverifikasi!");
    }

    /**
     * Update NFC Order payment / delivery status.
     */
    public function updateStatus(Request $request, NfcOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,processing,completed,cancelled'],
            'payment_status' => ['required', 'string', 'in:pending,paid,waived'],
        ]);

        $order->update($validated);

        return back()->with('success', "Status pesanan {$order->order_number} berhasil diperbarui.");
    }
}
