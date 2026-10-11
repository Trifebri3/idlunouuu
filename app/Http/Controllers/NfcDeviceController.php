<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\NfcDevice;
use App\Models\NfcOrder;
use App\Services\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NfcDeviceController extends Controller
{
    /**
     * Submit an order / request for a physical YOIMO NFC smart card.
     */
    public function orderNfcCard(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'card_variant' => ['nullable', 'string', 'max:50'],
            'card_printed_name' => ['required', 'string', 'max:100'],
            'custom_image' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
            'phone_or_whatsapp' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'string', 'in:qris,bank_transfer,pengajuan'],
        ]);

        $customImagePath = $validated['custom_image'] ?? null;
        if ($request->hasFile('image')) {
            $optimized = ImageOptimizer::optimizeAndStore(
                $request->file('image'),
                'keychains',
                maxWidth: 1200,
                maxHeight: 1200,
                quality: 85
            );
            $customImagePath = $optimized['url'];
        }

        $orderNumber = 'LUNOU-'.date('ymd').'-'.strtoupper(Str::random(4));

        $order = NfcOrder::create([
            'user_id' => $request->user()->id,
            'order_number' => $orderNumber,
            'card_variant' => $validated['card_variant'] ?? 'custom_keychain',
            'card_printed_name' => $validated['card_printed_name'],
            'custom_image' => $customImagePath,
            'phone_or_whatsapp' => $validated['phone_or_whatsapp'],
            'shipping_address' => $validated['shipping_address'],
            'notes' => $validated['notes'] ?? null,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'status' => 'pending',
            'amount' => 49000,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'nfc_keychain_order_submitted',
            'target_type' => 'nfc_order',
            'target_id' => $order->id,
            'metadata' => [
                'order_number' => $orderNumber,
                'card_variant' => $order->card_variant,
                'custom_image' => $customImagePath,
            ],
        ]);

        return back()
            ->with('latest_order_number', $orderNumber)
            ->with('success', "Pesanan gantungan kunci berhasil dibuat dengan Kode Order {$orderNumber}! Silakan transfer Rp 49.000 (Promo Spesial Lifetime) ke rekening blu BCA Digital dan konfirmasi bukti ke WhatsApp Admin.");
    }

    /**
     * Show NFC Activation landing page for an unclaimed card.
     */
    public function showActivation(Request $request, string $publicId): Response|RedirectResponse
    {
        $publicId = strtoupper(trim($publicId));
        $device = NfcDevice::firstOrCreate(
            ['public_id' => $publicId],
            [
                'label' => 'YOIMO Smart NFC Card',
                'status' => 'pending',
            ]
        );

        // If card already belongs to a user, redirect to their public profile
        if ($device->user_id && $device->user?->profile) {
            $device->update(['last_seen_at' => now()]);

            return redirect()->route('public.profile', [
                'username' => $device->user->profile->username,
                'from' => 'nfc',
                'card' => $device->public_id,
            ]);
        }

        return Inertia::render('Nfc/Activate', [
            'publicId' => $publicId,
            'device' => $device,
        ]);
    }

    /**
     * Direct 1-click claim from the activation landing page.
     */
    public function claimDirect(Request $request, string $publicId): RedirectResponse
    {
        if (! Auth::check()) {
            // Save intention in session and redirect to Google login
            session(['claim_nfc_id' => $publicId]);

            return redirect()->route('login')->with('info', 'Silakan masuk untuk mengaktifkan kartu ini.');
        }

        $publicId = strtoupper(trim($publicId));
        $device = NfcDevice::where('public_id', $publicId)->first();

        if ($device && $device->user_id && $device->user_id !== Auth::id()) {
            return redirect()->route('dashboard')->withErrors([
                'error' => 'Kartu NFC ini telah terhubung ke pengguna lain.',
            ]);
        }

        if (! $device) {
            $device = NfcDevice::create([
                'user_id' => Auth::id(),
                'public_id' => $publicId,
                'label' => 'YOIMO Smart NFC Card',
                'status' => 'active',
                'activated_at' => now(),
                'last_seen_at' => now(),
            ]);
        } else {
            $device->update([
                'user_id' => Auth::id(),
                'status' => 'active',
                'activated_at' => $device->activated_at ?? now(),
                'last_seen_at' => now(),
            ]);
        }

        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'nfc_device_claimed_via_tap',
            'target_type' => 'nfc_device',
            'target_id' => $device->id,
            'metadata' => ['public_id' => $publicId],
        ]);

        return redirect()->route('studio')->with('success', "Selamat! Kartu NFC {$publicId} sekarang terhubung ke profil Anda.");
    }

    /**
     * Claim / activate an NFC device from Studio form using its public_id.
     */
    public function claim(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'public_id' => ['required', 'string', 'max:64'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        $publicId = strtoupper(trim($validated['public_id']));

        // Check if device already claimed
        $device = NfcDevice::where('public_id', $publicId)->first();

        if ($device && $device->user_id && $device->user_id !== $request->user()->id) {
            return back()->withErrors([
                'public_id' => 'Kartu NFC ini sudah terhubung ke akun lain. Hubungi support jika ini milik Anda.',
            ]);
        }

        if (! $device) {
            // New card registration
            $device = NfcDevice::create([
                'user_id' => $request->user()->id,
                'public_id' => $publicId,
                'label' => $validated['label'] ?: 'YOIMO Smart Card',
                'status' => 'active',
                'activated_at' => now(),
                'last_seen_at' => now(),
            ]);
        } else {
            $device->update([
                'user_id' => $request->user()->id,
                'label' => $validated['label'] ?: ($device->label ?: 'YOIMO Smart Card'),
                'status' => 'active',
                'activated_at' => $device->activated_at ?? now(),
                'last_seen_at' => now(),
            ]);
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'nfc_device_claimed',
            'target_type' => 'nfc_device',
            'target_id' => $device->id,
            'metadata' => ['public_id' => $publicId],
        ]);

        return back()->with('success', "Kartu NFC {$publicId} berhasil dihubungkan ke profil Anda!");
    }

    /**
     * Redeem an order code or device code (both for orders placed inside or outside the web).
     */
    public function redeemOrderCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $user = $request->user();

        // 1. Try finding by order_number
        $order = NfcOrder::where('order_number', $code)->first();

        if ($order) {
            // Check if order is already claimed by someone else
            if ($order->user_id && $order->user_id !== $user->id) {
                // If it already has an active device belonging to another user
                if ($order->device && $order->device->user_id && $order->device->user_id !== $user->id) {
                    return back()->withErrors([
                        'code' => 'Pesanan dengan kode ini sudah terhubung ke akun lain. Hubungi WhatsApp Admin di 085862319524 jika Anda pemilik aslinya.',
                    ]);
                }
            }

            // Assign order to current user
            $order->update(['user_id' => $user->id]);

            // If order already has an NFC device, link it to user and activate
            if ($order->device) {
                $order->device->update([
                    'user_id' => $user->id,
                    'status' => 'active',
                    'activated_at' => $order->device->activated_at ?? now(),
                    'last_seen_at' => now(),
                ]);
            } else {
                // Create an active NFC device for this keychain order
                $deviceCode = 'LUNOU-KEY-'.strtoupper(Str::random(6));
                while (NfcDevice::where('public_id', $deviceCode)->exists()) {
                    $deviceCode = 'LUNOU-KEY-'.strtoupper(Str::random(6));
                }

                $device = NfcDevice::create([
                    'user_id' => $user->id,
                    'public_id' => $deviceCode,
                    'label' => 'Gantungan Kunci LUNOU ('.$order->card_printed_name.')',
                    'status' => 'active',
                    'activated_at' => now(),
                    'last_seen_at' => now(),
                ]);

                $order->update([
                    'nfc_device_id' => $device->id,
                    'status' => 'completed',
                ]);
            }

            AuditLog::create([
                'actor_id' => $user->id,
                'action' => 'nfc_order_redeemed',
                'target_type' => 'nfc_order',
                'target_id' => $order->id,
                'metadata' => ['order_number' => $order->order_number],
            ]);

            return back()->with('success', "Selamat! Gantungan kunci dengan Kode Order {$order->order_number} berhasil diaktivasi dan terhubung ke profil Anda!");
        }

        // 2. Try finding by direct NFC device public_id
        $device = NfcDevice::where('public_id', $code)->first();

        if ($device) {
            if ($device->user_id && $device->user_id !== $user->id) {
                return back()->withErrors([
                    'code' => 'Perangkat NFC ini sudah terhubung ke akun lain.',
                ]);
            }

            $device->update([
                'user_id' => $user->id,
                'status' => 'active',
                'activated_at' => $device->activated_at ?? now(),
                'last_seen_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => $user->id,
                'action' => 'nfc_device_redeemed_direct',
                'target_type' => 'nfc_device',
                'target_id' => $device->id,
                'metadata' => ['public_id' => $device->public_id],
            ]);

            return back()->with('success', "Gantungan kunci {$device->public_id} berhasil diaktifkan dan terhubung ke profil Anda!");
        }

        return back()->withErrors([
            'code' => "Kode pesanan atau kartu '{$code}' tidak ditemukan. Pastikan nomor order sudah benar atau hubungi admin di WhatsApp 085862319524.",
        ]);
    }

    /**
     * Generate a new test NFC card for local testing or demo.
     */
    public function generateDemo(Request $request): RedirectResponse
    {
        $code = 'YOIMO-'.strtoupper(Str::random(6));

        NfcDevice::create([
            'user_id' => $request->user()->id,
            'public_id' => $code,
            'label' => 'YOIMO Special Edition Card',
            'status' => 'active',
            'activated_at' => now(),
            'last_seen_at' => now(),
        ]);

        return back()->with('success', "Kartu demo {$code} berhasil dibuat dan dihubungkan.");
    }

    /**
     * Unlink NFC device from user account.
     */
    public function unbind(Request $request, NfcDevice $device): RedirectResponse
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403);
        }

        $device->update([
            'user_id' => null,
            'status' => 'pending',
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'nfc_device_unlinked',
            'target_type' => 'nfc_device',
            'target_id' => $device->id,
        ]);

        return back()->with('success', 'Kartu NFC berhasil dilepas dari profil.');
    }
}
