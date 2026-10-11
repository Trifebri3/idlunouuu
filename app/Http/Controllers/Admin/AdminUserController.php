<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * Toggle user status (active <-> suspended).
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
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
}
