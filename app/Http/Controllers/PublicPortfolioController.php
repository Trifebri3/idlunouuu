<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioAsset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicPortfolioController extends Controller
{
    /**
     * Display public portfolio website at /p/{username}.
     */
    public function show(Request $request, string $username): Response
    {
        // 1. Try finding by user username first, fallback to direct slug
        $user = User::whereHas('profile', function ($q) use ($username) {
            $q->where('username', $username);
        })->first();

        if ($user) {
            $portfolio = Portfolio::where('user_id', $user->id)->first();
        } else {
            $portfolio = Portfolio::where('slug', $username)->first();
            $user = $portfolio?->user;
        }

        abort_unless($portfolio, 404, 'Portofolio tidak ditemukan.');

        // 2. Draft check: Only author or super admin can preview unpublished portfolio
        $currentUser = $request->user();
        $isAuthor = $currentUser && $currentUser->id === $portfolio->user_id;
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (! $portfolio->is_published && ! $isAuthor && ! $isSuperAdmin) {
            abort(404, 'Portofolio ini sedang dalam mode draf dan belum dipublikasikan.');
        }

        // 3. Increment views count on legitimate visitor view
        if (! $isAuthor) {
            $portfolio->increment('views_count');
        }

        $portfolio->load([
            'pages' => function ($q) {
                $q->orderBy('sort_order');
            },
            'assets',
        ]);

        $userProfile = $user->profile;

        return Inertia::render('Public/Portfolio', [
            'portfolio' => $portfolio,
            'pages' => $portfolio->pages,
            'assets' => $portfolio->assets,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $userProfile?->avatar ?? $user->avatar,
                'username' => $userProfile?->username ?? $portfolio->slug,
                'bio' => $userProfile?->bio ?? $portfolio->bio,
            ],
            'isOwner' => $isAuthor,
            'mainProfileUrl' => $userProfile ? url('/'.$userProfile->username) : null,
        ]);
    }

    /**
     * Direct download PDF asset from portfolio if enabled.
     */
    public function downloadPdf(Request $request, string $username, PortfolioAsset $asset): BinaryFileResponse
    {
        $portfolio = $asset->portfolio;
        abort_unless($portfolio, 404);

        if (! empty($portfolio->settings['allow_download']) && $portfolio->settings['allow_download'] === false) {
            abort(403, 'Fitur unduh dokumen dinonaktifkan oleh pemilik portofolio.');
        }

        $rawPath = str_replace('/storage/', '', $asset->file_path);
        abort_unless(Storage::disk('public')->exists($rawPath), 404, 'Berkas tidak ditemukan.');

        $fullPath = Storage::disk('public')->path($rawPath);

        return response()->download($fullPath, $asset->title.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
