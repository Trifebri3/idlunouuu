<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\NfcDevice;
use App\Models\NfcOrder;
use App\Models\Profile;
use App\Models\ProfileComponent;
use App\Models\ProfileEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Display Super Admin platform overview.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Akses khusus Super Admin.');

        $totalUsers = User::count();
        $totalProfiles = Profile::count();
        $totalComponents = ProfileComponent::count();
        $totalNfc = NfcDevice::count();
        $activeNfc = NfcDevice::where('status', 'active')->count();

        $totalViews = ProfileEvent::where('event_type', 'view')->count();
        $totalClicks = ProfileEvent::where('event_type', 'click')->count();

        $users = User::with(['profile', 'nfcDevices'])
            ->latest()
            ->paginate(15);

        // All users list for card assignment dropdown
        $allUsers = User::select('id', 'name', 'email')
            ->with('profile:id,user_id,username')
            ->orderBy('name')
            ->get();

        $nfcDevices = NfcDevice::with(['user.profile'])
            ->latest()
            ->get();

        // Orders submitted by users
        $nfcOrders = NfcOrder::with(['user.profile', 'device'])
            ->latest()
            ->get();

        // Available unassigned cards in stock that Admin has already generated
        $unassignedCards = NfcDevice::whereNull('user_id')
            ->where('status', '!=', 'suspended')
            ->latest()
            ->get();

        $auditLogs = AuditLog::with('actor')
            ->latest()
            ->take(30)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'total_profiles' => $totalProfiles,
                'total_components' => $totalComponents,
                'total_nfc' => $totalNfc,
                'active_nfc' => $activeNfc,
                'total_orders' => $nfcOrders->count(),
                'total_views' => $totalViews,
                'total_clicks' => $totalClicks,
            ],
            'users' => $users,
            'allUsers' => $allUsers,
            'nfcDevices' => $nfcDevices,
            'nfcOrders' => $nfcOrders,
            'unassignedCards' => $unassignedCards,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Create single RFID/NFC card in advance.
     */
    public function createNfcCard(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'public_id' => ['nullable', 'string', 'max:64', 'unique:nfc_devices,public_id'],
            'label' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $publicId = ! empty($validated['public_id'])
            ? strtoupper(trim($validated['public_id']))
            : 'YOIMO-'.strtoupper(Str::random(7));

        $userId = $validated['user_id'] ?? null;
        $status = $userId ? 'active' : 'pending';

        $device = NfcDevice::create([
            'public_id' => $publicId,
            'label' => $validated['label'] ?: 'YOIMO Smart NFC Card',
            'user_id' => $userId,
            'status' => $status,
            'activated_at' => $userId ? now() : null,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_nfc_created',
            'target_type' => 'nfc_device',
            'target_id' => $device->id,
            'metadata' => [
                'public_id' => $publicId,
                'user_id' => $userId,
            ],
        ]);

        $msg = $userId
            ? "Kartu {$publicId} berhasil dibuat dan di-embed ke pengguna."
            : "Tautan kartu {$publicId} berhasil dibuat (status siap di-embed).";

        return back()->with('success', $msg);
    }

    /**
     * Batch create RFID/NFC cards for physical production.
     */
    public function batchCreateNfcCards(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        $count = (int) $validated['count'];
        $label = $validated['label'] ?: 'YOIMO Batch Card';

        for ($i = 0; $i < $count; $i++) {
            $publicId = 'YOIMO-'.strtoupper(Str::random(8));

            // ensure unique
            while (NfcDevice::where('public_id', $publicId)->exists()) {
                $publicId = 'YOIMO-'.strtoupper(Str::random(8));
            }

            NfcDevice::create([
                'public_id' => $publicId,
                'label' => $label,
                'user_id' => null,
                'status' => 'pending',
                'activated_at' => null,
            ]);
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_nfc_batch_created',
            'target_type' => 'nfc_device',
            'metadata' => ['count' => $count],
        ]);

        return back()->with('success', "Berhasil mencetak {$count} tautan kartu RFID/NFC baru.");
    }

    /**
     * Assign or unassign an RFID/NFC card to/from a user account.
     */
    public function assignNfcCard(Request $request, NfcDevice $device): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        $userId = $validated['user_id'] ?? null;
        $label = $validated['label'] ?? $device->label;

        $device->update([
            'user_id' => $userId,
            'label' => $label,
            'status' => $userId ? 'active' : 'pending',
            'activated_at' => $userId ? ($device->activated_at ?? now()) : null,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_nfc_assigned',
            'target_type' => 'nfc_device',
            'target_id' => $device->id,
            'metadata' => [
                'public_id' => $device->public_id,
                'user_id' => $userId,
            ],
        ]);

        $targetUser = $userId ? User::find($userId)->name : null;
        $msg = $userId
            ? "Kartu {$device->public_id} berhasil di-embed ke akun {$targetUser}."
            : "Kartu {$device->public_id} berhasil dilepas dari akun (status siap di-embed kembali).";

        return back()->with('success', $msg);
    }

    /**
     * Delete an RFID/NFC device.
     */
    public function deleteNfcCard(Request $request, NfcDevice $device): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $publicId = $device->public_id;
        $device->delete();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_nfc_deleted',
            'target_type' => 'nfc_device',
            'metadata' => ['public_id' => $publicId],
        ]);

        return back()->with('success', "Kartu {$publicId} berhasil dihapus.");
    }

    /**
     * Toggle user status (active <-> suspended).
     */
    public function toggleUserStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['error' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'user_status_changed',
            'target_type' => 'user',
            'target_id' => $user->id,
            'metadata' => [
                'email' => $user->email,
                'status' => $newStatus,
            ],
        ]);

        return back()->with('success', "Status akun {$user->name} diubah menjadi {$newStatus}.");
    }

    /**
     * Connect an unassigned RFID/NFC card to a user's order and activate it.
     */
    public function connectOrderToNfc(Request $request, NfcOrder $order): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

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

        return back()->with('success', "Kartu fisik {$device->public_id} berhasil disambungkan ke pesanan {$order->order_number} & akun {$order->user->name}!");
    }

    /**
     * Update NFC Order payment / delivery status.
     */
    public function updateOrderStatus(Request $request, NfcOrder $order): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,processing,completed,cancelled'],
            'payment_status' => ['required', 'string', 'in:pending,paid,waived'],
        ]);

        $order->update($validated);

        return back()->with('success', "Status pesanan {$order->order_number} berhasil diperbarui.");
    }

    /**
     * Create offline order (from WhatsApp, events, or external purchase) and issue an order code.
     */
    public function createOfflineOrder(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

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
     * Quickly verify payment and advance order to processing.
     */
    public function verifyPayment(Request $request, NfcOrder $order): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

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
}
