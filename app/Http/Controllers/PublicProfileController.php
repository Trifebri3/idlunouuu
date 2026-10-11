<?php

namespace App\Http\Controllers;

use App\Models\NfcDevice;
use App\Models\Profile;
use App\Models\ProfileComponent;
use App\Models\ProfileEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    /**
     * Show the public profile by username.
     */
    public function show(Request $request, string $username): Response|RedirectResponse
    {
        $username = strtolower(trim($username));

        $profile = Profile::where('username', $username)->first();

        if (! $profile) {
            abort(404, 'Profil LUNOU tidak ditemukan.');
        }

        // Check if unpublished and not the owner
        $isOwner = Auth::check() && Auth::id() === $profile->user_id;
        if (! $profile->is_published && ! $isOwner) {
            abort(404, 'Profil ini sedang dalam mode privat.');
        }

        // Track page view event (debounced per session)
        $sessionKey = 'viewed_profile_'.$profile->id;
        if (! $request->session()->has($sessionKey)) {
            ProfileEvent::create([
                'profile_id' => $profile->id,
                'event_type' => 'view',
                'created_at' => now(),
            ]);
            $request->session()->put($sessionKey, true);
        }

        $profile->load(['visibleComponents']);

        return Inertia::render('Public/Profile', [
            'profile' => [
                'id' => $profile->id,
                'username' => $profile->username,
                'display_name' => $profile->display_name,
                'bio' => $profile->bio,
                'avatar' => $profile->avatar,
                'theme' => $profile->theme,
                'is_published' => $profile->is_published,
                'is_owner' => $isOwner,
                'components' => $profile->visibleComponents,
            ],
            'portfolio' => ($profile->user->portfolio && $profile->user->portfolio->is_published) ? [
                'title' => $profile->user->portfolio->title,
                'slug' => $profile->user->portfolio->slug,
                'url' => url('/p/'.$profile->user->portfolio->slug),
                'views_count' => $profile->user->portfolio->views_count,
                'pages_count' => $profile->user->portfolio->pages()->count(),
            ] : null,
            'isNfcTap' => $request->query('from') === 'nfc',
            'nfcCardId' => $request->query('card'),
        ]);
    }

    /**
     * Resolve NFC tap and redirect to user's profile.
     */
    public function nfcTap(Request $request, string $publicId): RedirectResponse
    {
        $publicId = strtoupper(trim($publicId));
        $device = NfcDevice::where('public_id', $publicId)->first();

        if (! $device || ! $device->user_id) {
            // Unclaimed or pre-provisioned card: redirect to activation page
            return redirect()->route('nfc.activate', ['publicId' => $publicId]);
        }

        $device->update(['last_seen_at' => now()]);

        $profile = $device->user->profile;
        if (! $profile) {
            abort(404, 'Profil pemilik kartu belum siap.');
        }

        return redirect()->route('public.profile', [
            'username' => $profile->username,
            'from' => 'nfc',
            'card' => $device->public_id,
        ]);
    }

    /**
     * Track click on a profile component and redirect to destination.
     */
    public function trackClick(Request $request, string $username, ProfileComponent $component): JsonResponse|RedirectResponse
    {
        ProfileEvent::create([
            'profile_id' => $component->profile_id,
            'component_id' => $component->id,
            'event_type' => 'click',
            'created_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success']);
        }

        if ($component->url) {
            return redirect()->away($component->url);
        }

        return back();
    }
}
