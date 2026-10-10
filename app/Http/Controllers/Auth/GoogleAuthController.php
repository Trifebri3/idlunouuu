<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            if (app()->environment('local')) {
                // If query has email or dev mode, allow simulated one-click login
                if ($request->has('email')) {
                    return $this->loginWithSimulatedGoogle($request->input('email'), $request->input('name'));
                }

                return redirect()->route('login')->withErrors([
                    'google' => 'Google Client ID belum diatur di file .env. Silakan tambahkan GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET dari Google Cloud Console untuk domain id.lunou.tech (atau localhost). Selama tahap dev lokal, Anda juga dapat menggunakan opsi masuk cepat di bawah.',
                ]);
            }

            return redirect()->route('login')->withErrors([
                'google' => 'Layanan Google Login belum dikonfigurasi oleh administrator.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google and handle instant onboarding.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('login')->withErrors([
                'google' => 'Gagal terhubung dengan akun Google: '.$e->getMessage(),
            ]);
        }

        $user = DB::transaction(function () use ($googleUser) {
            // Check if user exists by google_id or email
            $existingUser = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($existingUser) {
                // Ensure google_id and avatar updated if missing
                $existingUser->update([
                    'google_id' => $existingUser->google_id ?? $googleUser->getId(),
                    'avatar' => $existingUser->avatar ?? $googleUser->getAvatar(),
                    'email_verified_at' => $existingUser->email_verified_at ?? now(),
                ]);

                // Ensure profile exists
                if (! $existingUser->profile) {
                    $this->createProfileForUser($existingUser, $googleUser->getName(), $googleUser->getAvatar());
                }

                return $existingUser;
            }

            // Create new User (Never allow super_admin from OAuth)
            $newUser = User::create([
                'name' => $googleUser->getName() ?? 'User LUNOU',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $this->createProfileForUser($newUser, $googleUser->getName(), $googleUser->getAvatar());

            AuditLog::create([
                'actor_id' => $newUser->id,
                'action' => 'user_registered_google',
                'target_type' => 'user',
                'target_id' => $newUser->id,
                'metadata' => ['provider' => 'google'],
            ]);

            return $newUser;
        });

        if ($user->status === 'suspended') {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan oleh administrator.',
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Development mock login for testing local environments.
     */
    public function devLogin(Request $request, string $role = 'user'): RedirectResponse
    {
        if (! app()->environment('local')) {
            abort(403);
        }

        $email = $role === 'admin' ? 'admin@lunou.id' : 'tri@lunou.id';
        $user = User::where('email', $email)->firstOrFail();

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    /**
     * Local simulated Google login for fast testing (e.g., trygaming303@gmail.com).
     */
    public function devGoogleLogin(Request $request): RedirectResponse
    {
        if (! app()->environment('local')) {
            abort(403);
        }

        $email = $request->input('email', 'trygaming303@gmail.com');
        $name = $request->input('name', 'Tri Febriansah');

        return $this->loginWithSimulatedGoogle($email, $name);
    }

    /**
     * Helper to authenticate or register user as simulated Google account.
     */
    protected function loginWithSimulatedGoogle(string $email, ?string $name = null): RedirectResponse
    {
        $user = DB::transaction(function () use ($email, $name) {
            $existing = User::where('email', $email)->first();
            if ($existing) {
                if (! $existing->profile) {
                    $this->createProfileForUser($existing, $name ?? $existing->name, '/icon/1.png');
                }

                return $existing;
            }

            $newUser = User::create([
                'name' => $name ?? explode('@', $email)[0],
                'email' => $email,
                'google_id' => 'simulated_'.md5($email),
                'avatar' => '/icon/1.png',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $this->createProfileForUser($newUser, $name ?? $newUser->name, '/icon/1.png');

            AuditLog::create([
                'actor_id' => $newUser->id,
                'action' => 'user_registered_simulated_google',
                'target_type' => 'user',
                'target_id' => $newUser->id,
                'metadata' => ['provider' => 'simulated_google', 'email' => $email],
            ]);

            return $newUser;
        });

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Create a profile for a given user with a unique slug username.
     */
    protected function createProfileForUser(User $user, ?string $name, ?string $avatar): Profile
    {
        $baseUsername = Str::slug($name ?? explode('@', $user->email)[0], '');
        if (empty($baseUsername)) {
            $baseUsername = 'user';
        }

        $username = strtolower($baseUsername);
        $counter = 1;

        while (Profile::where('username', $username)->exists()) {
            $username = strtolower($baseUsername.$counter);
            $counter++;
        }

        return Profile::create([
            'user_id' => $user->id,
            'username' => $username,
            'display_name' => $name ?? 'Member LUNOU',
            'bio' => 'Selamat datang di profil LUNOU saya.',
            'avatar' => $avatar,
            'theme' => 'clean-light',
            'is_published' => true,
        ]);
    }
}
