<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\ProfileEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    /**
     * Display the main LUNOU Studio editor.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ensure user has a profile
        $profile = $user->profile ?? Profile::create([
            'user_id' => $user->id,
            'username' => strtolower(Str::slug($user->name ?: 'user'.$user->id, '')),
            'display_name' => $user->name,
            'bio' => 'Selamat datang di profil LUNOU saya.',
            'avatar' => $user->avatar,
            'theme' => 'midnight',
            'is_published' => true,
        ]);

        $user->load([
            'nfcDevices',
            'nfcOrders' => fn ($q) => $q->latest(),
            'nfcOrders.device',
        ]);

        // Analytics summary
        $totalViews = ProfileEvent::where('profile_id', $profile->id)
            ->where('event_type', 'view')
            ->count();

        $totalClicks = ProfileEvent::where('profile_id', $profile->id)
            ->where('event_type', 'click')
            ->count();

        $publicUrl = url('/'.$profile->username);

        $themes = [
            [
                'id' => 'midnight',
                'name' => 'Midnight Obsidian',
                'description' => 'Dark sleek, aksen neon cyan & purple modern',
                'bg' => 'from-slate-950 via-zinc-900 to-black',
                'card' => 'bg-zinc-900/80 border-zinc-800 text-white',
                'accent' => 'bg-cyan-500 hover:bg-cyan-400 text-black',
                'previewColor' => '#090d16',
            ],
            [
                'id' => 'sunset',
                'name' => 'Solar Flare',
                'description' => 'Gradasi warm amber, peach & coral energik',
                'bg' => 'from-orange-950 via-rose-950 to-neutral-950',
                'card' => 'bg-rose-950/40 border-rose-800/40 text-white',
                'accent' => 'bg-gradient-to-r from-amber-500 to-rose-500 text-white',
                'previewColor' => '#4c0519',
            ],
            [
                'id' => 'cyber',
                'name' => 'Cyberpunk Emerald',
                'description' => 'High contrast dark mode dengan aksen emerald',
                'bg' => 'from-emerald-950 via-neutral-950 to-black',
                'card' => 'bg-emerald-950/30 border-emerald-800/40 text-white',
                'accent' => 'bg-emerald-400 hover:bg-emerald-300 text-black',
                'previewColor' => '#022c22',
            ],
            [
                'id' => 'clean-light',
                'name' => 'Minimal Pearl',
                'description' => 'Monokrom elegan bersih dengan bayangan lembut',
                'bg' => 'from-slate-100 via-white to-zinc-100',
                'card' => 'bg-white border-zinc-200 text-zinc-900 shadow-sm',
                'accent' => 'bg-zinc-900 hover:bg-black text-white',
                'previewColor' => '#f8fafc',
            ],
        ];

        return Inertia::render('Studio/Index', [
            'profile' => $profile,
            'nfcDevices' => $user->nfcDevices,
            'nfcOrders' => $user->nfcOrders,
            'stats' => [
                'views' => $totalViews,
                'clicks' => $totalClicks,
            ],
            'themes' => $themes,
            'publicUrl' => $publicUrl,
        ]);
    }

    /**
     * Update profile content, theme, and publishing status.
     */
    public function update(Request $request): RedirectResponse
    {
        $profile = $request->user()->profile;

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'theme' => ['required', 'string', 'max:50'],
            'is_published' => ['required', 'boolean'],
        ]);

        $profile->update($validated);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Update username with uniqueness check and lowercase normalization.
     */
    public function updateUsername(Request $request): RedirectResponse
    {
        $profile = $request->user()->profile;

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:40',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
                'unique:profiles,username,'.$profile->id,
            ],
        ], [
            'username.regex' => 'Username hanya boleh memuat huruf, angka, tanda strip, titik, dan garis bawah.',
            'username.unique' => 'Username ini sudah digunakan oleh pengguna lain.',
        ]);

        $oldUsername = $profile->username;
        $profile->update([
            'username' => strtolower($validated['username']),
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'username_changed',
            'target_type' => 'profile',
            'target_id' => $profile->id,
            'metadata' => [
                'old' => $oldUsername,
                'new' => $profile->username,
            ],
        ]);

        return back()->with('success', 'Username berhasil diganti.');
    }

    /**
     * Instant username availability check API.
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $username = strtolower(trim((string) $request->query('username', '')));

        if (strlen($username) < 3) {
            return response()->json([
                'available' => false,
                'message' => 'Username minimal 3 karakter',
            ]);
        }

        $currentUserId = Auth::id();
        $isTaken = Profile::where('username', $username)
            ->where('user_id', '!=', $currentUserId)
            ->exists();

        return response()->json([
            'available' => ! $isTaken,
            'username' => $username,
            'message' => $isTaken ? 'Username sudah digunakan' : 'Username tersedia',
        ]);
    }
}
