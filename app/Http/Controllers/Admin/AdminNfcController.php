<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\NfcDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminNfcController extends Controller
{
    /**
     * Create single RFID/NFC card in advance.
     */
    public function store(Request $request): RedirectResponse
    {
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
    public function batchStore(Request $request): RedirectResponse
    {
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
    public function assign(Request $request, NfcDevice $device): RedirectResponse
    {
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

        $targetUser = $userId ? User::find($userId)?->name : null;
        $msg = $userId
            ? "Kartu {$device->public_id} berhasil di-embed ke akun {$targetUser}."
            : "Kartu {$device->public_id} berhasil dilepas dari akun (status siap di-embed kembali).";

        return back()->with('success', $msg);
    }

    /**
     * Delete an RFID/NFC device.
     */
    public function destroy(Request $request, NfcDevice $device): RedirectResponse
    {
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
}
