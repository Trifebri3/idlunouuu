<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Portfolio;
use App\Models\PortfolioAsset;
use App\Models\PortfolioEntitlement;
use App\Models\PortfolioOrder;
use App\Models\PortfolioPage;
use App\Models\PortfolioPayment;
use App\Models\PortfolioPaymentMethod;
use App\Models\PortfolioPlan;
use App\Models\PortfolioSetting;
use App\Models\PortfolioVoucher;
use App\Services\ImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    /**
     * Display Portfolio Studio view with access state resolution.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $entitlement = $user->portfolioEntitlement;
        $hasAccess = $user->hasActivePortfolioAccess();

        // 1. Resolve Access Status
        $status = 'not_subscribed';
        $latestOrder = $user->portfolioOrders()
            ->with(['plan', 'voucher', 'payment'])
            ->first();

        if ($hasAccess) {
            $status = 'active';
        } elseif ($entitlement && ! $entitlement->isActive()) {
            $status = 'expired';
        } elseif ($latestOrder) {
            if ($latestOrder->payment_status === 'under_review') {
                $status = 'under_review';
            } elseif ($latestOrder->payment_status === 'pending') {
                $status = 'pending_payment';
            }
        }

        // 2. Pricing & Payment Data
        $plans = PortfolioPlan::where('is_active', true)->get();
        $activePlan = $plans->first();
        $paymentMethods = PortfolioPaymentMethod::where('is_active', true)->get();
        $termsAndConditions = PortfolioSetting::get('terms_and_conditions', '');

        // 3. User Portfolio (Created or retrieved if entitled)
        $portfolio = null;
        if ($hasAccess) {
            $portfolio = Portfolio::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'title' => ($user->profile?->display_name ?? $user->name).' · Portofolio Kreatif',
                    'slug' => $user->profile?->username ?? Str::slug($user->name.'-'.Str::random(4)),
                    'bio' => $user->profile?->bio ?? 'Koleksi karya kreatif, proyek, dan dokumen resmi.',
                    'theme' => 'minimal-clean',
                    'is_published' => false,
                    'settings' => [
                        'allow_download' => true,
                        'flipbook_default' => true,
                        'accent_color' => '#2563eb',
                        'font_family' => 'sans',
                    ],
                ]
            );

            // Ensure portfolio has default starter pages if empty
            if ($portfolio->pages()->count() === 0) {
                $this->seedStarterPages($portfolio, $user);
            }

            $portfolio->load(['pages', 'assets']);
        }

        return Inertia::render('Studio/Portfolio/Index', [
            'hasAccess' => $hasAccess,
            'accessStatus' => $status,
            'entitlement' => $entitlement ? [
                'status' => $entitlement->status,
                'starts_at' => $entitlement->starts_at?->toIso8601String(),
                'expires_at' => $entitlement->expires_at?->toIso8601String(),
                'days_remaining' => $entitlement->daysRemaining(),
                'max_pdf_files' => $entitlement->max_pdf_files,
                'max_pdf_size_mb' => $entitlement->max_pdf_size_mb,
            ] : null,
            'latestOrder' => $latestOrder,
            'plans' => $plans,
            'activePlan' => $activePlan,
            'paymentMethods' => $paymentMethods,
            'termsAndConditions' => $termsAndConditions,
            'portfolio' => $portfolio,
            'publicUrl' => $portfolio ? url('/p/'.$portfolio->slug) : null,
            'freeModeEnabled' => (bool) PortfolioSetting::get('free_mode_enabled', false),
            'saweriaUrl' => PortfolioSetting::get('saweria_url', 'https://saweria.co/Try3'),
        ]);
    }

    /**
     * Validate voucher code and compute server-side discount.
     */
    public function validateVoucher(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'plan_id' => ['required', 'exists:portfolio_plans,id'],
        ]);

        $plan = PortfolioPlan::findOrFail($validated['plan_id']);
        $basePrice = $plan->getEffectivePrice();

        $voucher = PortfolioVoucher::where('code', strtoupper(trim($validated['code'])))
            ->where('is_active', true)
            ->first();

        if (! $voucher || ! $voucher->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'Kode voucher tidak valid atau kuota telah habis.',
            ], 422);
        }

        $discount = $voucher->calculateDiscount($basePrice);
        $finalPrice = max(0, $basePrice - $discount);

        return response()->json([
            'valid' => true,
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
            'discount_amount' => $discount,
            'original_price' => $basePrice,
            'final_price' => $finalPrice,
            'discount_text' => $voucher->discount_type === 'percent'
                ? "Diskon {$voucher->discount_amount}%"
                : 'Potongan Rp '.number_format($voucher->discount_amount, 0, ',', '.'),
        ]);
    }

    /**
     * Checkout subscription order.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:portfolio_plans,id'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $plan = PortfolioPlan::findOrFail($validated['plan_id']);
        $basePrice = $plan->getEffectivePrice();

        $voucherId = null;
        $discountAmount = 0;

        if (! empty($validated['voucher_code'])) {
            $voucher = PortfolioVoucher::where('code', strtoupper(trim($validated['voucher_code'])))
                ->where('is_active', true)
                ->first();

            if ($voucher && $voucher->isValid()) {
                $voucherId = $voucher->id;
                $discountAmount = $voucher->calculateDiscount($basePrice);
            }
        }

        $isFreeMode = (bool) PortfolioSetting::get('free_mode_enabled', false);
        $finalAmount = $isFreeMode ? 0 : max(0, $basePrice - $discountAmount);
        $orderNumber = 'LP-'.date('ymd').'-'.strtoupper(Str::random(4));
        $isInstantFree = $isFreeMode || $finalAmount === 0;

        $order = PortfolioOrder::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'voucher_id' => $voucherId,
            'original_price' => $basePrice,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'payment_status' => $isInstantFree ? 'approved' : 'pending',
            'status' => $isInstantFree ? 'active' : 'pending',
        ]);

        if ($isInstantFree) {
            $durationDays = $plan->duration_days ?: 30;
            PortfolioEntitlement::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'order_id' => $order->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => now()->addDays($durationDays),
                    'max_pdf_files' => $plan->max_pdf_files ?: 10,
                    'max_pdf_size_mb' => $plan->max_pdf_size_mb ?: 5,
                ]
            );

            AuditLog::create([
                'actor_id' => $user->id,
                'action' => 'portfolio_order_activated_free',
                'target_type' => 'portfolio_order',
                'target_id' => $order->id,
                'metadata' => [
                    'order_number' => $orderNumber,
                    'final_amount' => 0,
                    'free_mode' => $isFreeMode,
                ],
            ]);

            return redirect()->route('studio.portfolio')->with('success', "Akses Portofolio Pro ({$orderNumber}) aktif secara instan (Mode Bebas Biaya/Gratis)! Selamat berkarya.");
        }

        AuditLog::create([
            'actor_id' => $user->id,
            'action' => 'portfolio_order_created',
            'target_type' => 'portfolio_order',
            'target_id' => $order->id,
            'metadata' => [
                'order_number' => $orderNumber,
                'final_amount' => $finalAmount,
            ],
        ]);

        return redirect()->route('studio.portfolio')->with('success', "Pesanan langganan {$orderNumber} berhasil dibuat. Silakan lakukan transfer dan unggah bukti.");
    }

    /**
     * Submit payment proof for an order.
     */
    public function submitPaymentProof(Request $request, PortfolioOrder $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'payment_method_id' => ['nullable', 'exists:portfolio_payment_methods,id'],
            'proof_image' => ['required', 'image', 'max:5120'], // max 5MB image
            'sender_bank' => ['required', 'string', 'max:100'],
            'sender_account_name' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $optimized = ImageOptimizer::optimizeAndStore(
            $request->file('proof_image'),
            'portfolio_payments',
            maxWidth: 1600,
            maxHeight: 1600,
            quality: 80
        );
        $paymentMethodId = $validated['payment_method_id'] ?? PortfolioPaymentMethod::where('is_active', true)->value('id');

        PortfolioPayment::create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethodId,
            'proof_file' => $optimized['url'],
            'sender_bank' => $validated['sender_bank'],
            'sender_account_name' => $validated['sender_account_name'],
            'amount_paid' => $order->final_amount,
            'notes' => $validated['notes'] ?? 'Bukti transfer diunggah oleh pengguna',
        ]);

        $order->update([
            'payment_status' => 'under_review',
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'portfolio_payment_submitted',
            'target_type' => 'portfolio_order',
            'target_id' => $order->id,
            'metadata' => ['order_number' => $order->order_number],
        ]);

        return redirect()->route('studio.portfolio')->with('success', 'Bukti transfer berhasil diunggah! Admin sedang memeriksa verifikasi pembayaran.');
    }

    /**
     * Save portfolio meta, styling, and settings.
     */
    public function savePortfolio(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasActivePortfolioAccess(), 403, 'Akses Web Portfolio Premium diperlukan.');

        $portfolio = Portfolio::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:500'],
            'theme' => ['required', 'string', 'max:50'],
            'settings' => ['nullable', 'array'],
        ]);

        $portfolio->update($validated);

        return back()->with('success', 'Pengaturan portofolio berhasil disimpan.');
    }

    /**
     * Toggle public publication of portfolio.
     */
    public function togglePublish(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasActivePortfolioAccess(), 403, 'Akses Web Portfolio Premium diperlukan.');

        $portfolio = Portfolio::where('user_id', $user->id)->firstOrFail();
        $newStatus = ! $portfolio->is_published;

        $portfolio->update([
            'is_published' => $newStatus,
            'published_at' => $newStatus ? ($portfolio->published_at ?? now()) : $portfolio->published_at,
        ]);

        $statusText = $newStatus ? 'berhasil dipublikasikan secara publik' : 'dialihkan ke mode draf';

        return back()->with('success', "Website portofolio {$statusText}.");
    }

    /**
     * Save or update an individual page on the canvas.
     */
    public function savePage(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasActivePortfolioAccess(), 403, 'Akses Web Portfolio Premium diperlukan.');

        $portfolio = Portfolio::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'id' => ['nullable', 'exists:portfolio_pages,id'],
            'page_id' => ['nullable', 'exists:portfolio_pages,id'],
            'title' => ['required', 'string', 'max:100'],
            'page_type' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
            'content' => ['nullable', 'array'],
        ]);

        $pageId = $request->input('id') ?? $request->input('page_id');

        if (! empty($pageId)) {
            $page = PortfolioPage::where('portfolio_id', $portfolio->id)
                ->where('id', $pageId)
                ->firstOrFail();

            $page->update([
                'title' => $validated['title'],
                'page_type' => $validated['page_type'],
                'content' => $validated['content'] ?? [],
            ]);
        } else {
            $highestOrder = $portfolio->pages()->max('sort_order') ?? 0;
            $page = PortfolioPage::create([
                'portfolio_id' => $portfolio->id,
                'title' => $validated['title'],
                'page_type' => $validated['page_type'],
                'sort_order' => $highestOrder + 1,
                'content' => $validated['content'] ?? [],
            ]);
        }

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Halaman berhasil disimpan.');
        }

        return response()->json([
            'success' => true,
            'page' => $page,
            'message' => 'Halaman berhasil disimpan.',
        ]);
    }

    /**
     * Reorder pages in the portfolio.
     */
    public function reorderPages(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasActivePortfolioAccess(), 403);

        $portfolio = Portfolio::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'page_ids' => ['required', 'array'],
            'page_ids.*' => ['integer', 'exists:portfolio_pages,id'],
        ]);

        foreach ($validated['page_ids'] as $index => $pageId) {
            PortfolioPage::where('portfolio_id', $portfolio->id)
                ->where('id', $pageId)
                ->update(['sort_order' => $index + 1]);
        }

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Urutan halaman berhasil diperbarui.');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete a page from the portfolio.
     */
    public function deletePage(Request $request, PortfolioPage $page): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasActivePortfolioAccess(), 403);

        $portfolio = Portfolio::where('user_id', $user->id)->firstOrFail();
        abort_unless($page->portfolio_id === $portfolio->id, 403);

        // Keep at least 1 page
        if ($portfolio->pages()->count() <= 1) {
            return back()->withErrors(['error' => 'Portofolio minimal harus memiliki 1 halaman aktif.']);
        }

        $page->delete();

        return back()->with('success', 'Halaman berhasil dihapus.');
    }

    /**
     * Upload asset (Image or PDF document).
     */
    public function uploadAsset(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasActivePortfolioAccess(), 403, 'Akses Web Portfolio Premium diperlukan.');

        $portfolio = Portfolio::where('user_id', $user->id)->firstOrFail();
        $entitlement = $user->portfolioEntitlement;

        $maxPdfCount = $entitlement?->max_pdf_files ?? 10;
        $maxPdfSizeMb = $entitlement?->max_pdf_size_mb ?? 5;
        $maxPdfSizeBytes = $maxPdfSizeMb * 1024 * 1024;

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'], // 10MB max upload constraint
            'title' => ['nullable', 'string', 'max:150'],
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();
        $isPdf = $mime === 'application/pdf' || strtolower($file->getClientOriginalExtension()) === 'pdf';
        $type = $isPdf ? 'pdf' : 'image';

        // Check PDF specific limits
        if ($isPdf) {
            $existingPdfsCount = $portfolio->assets()->where('type', 'pdf')->count();
            if ($existingPdfsCount >= $maxPdfCount) {
                return response()->json([
                    'success' => false,
                    'message' => "Batas maksimal {$maxPdfCount} dokumen PDF tercapai. Hapus PDF lama untuk mengunggah berkas baru.",
                ], 422);
            }

            if ($file->getSize() > $maxPdfSizeBytes) {
                return response()->json([
                    'success' => false,
                    'message' => "Ukuran berkas PDF melebihi batas maksimal {$maxPdfSizeMb} MB.",
                ], 422);
            }
        }

        $subfolder = $isPdf ? 'portfolio_pdfs' : 'portfolio_images';
        $optimized = ImageOptimizer::optimizeAndStore(
            $file,
            $subfolder,
            maxWidth: 1920,
            maxHeight: 1920,
            quality: 82
        );

        $title = ! empty($validated['title'])
            ? $validated['title']
            : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $asset = PortfolioAsset::create([
            'portfolio_id' => $portfolio->id,
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'file_path' => $optimized['url'],
            'file_size_bytes' => $optimized['size'],
            'mime_type' => $optimized['mime'],
        ]);

        $statusMsg = $optimized['is_compressed'] && $optimized['compression_ratio'] > 0
            ? "Berkas {$title} berhasil diunggah dan dikompresi ({$optimized['compression_ratio']}% lebih hemat)."
            : "Berkas {$title} berhasil diunggah.";

        if ($request->header('X-Inertia')) {
            return back()->with('success', $statusMsg);
        }

        return response()->json([
            'success' => true,
            'asset' => $asset,
            'optimized' => $optimized,
            'message' => $statusMsg,
        ]);
    }

    /**
     * Delete an uploaded asset.
     */
    public function deleteAsset(Request $request, PortfolioAsset $asset): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasActivePortfolioAccess(), 403);
        abort_unless($asset->user_id === $user->id, 403);

        $rawPath = str_replace('/storage/', '', $asset->file_path);
        Storage::disk('public')->delete($rawPath);

        $asset->delete();

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Berkas berhasil dihapus.');
        }

        return response()->json(['success' => true, 'message' => 'Berkas berhasil dihapus.']);
    }

    /**
     * Seed starter canvas pages for a newly created portfolio.
     */
    protected function seedStarterPages(Portfolio $portfolio, $user): void
    {
        PortfolioPage::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Cover & Hero',
            'page_type' => 'cover',
            'sort_order' => 1,
            'content' => [
                'headline' => 'Koleksi Portofolio & Karya Kreatif',
                'subheadline' => 'Selamat datang di ruang portofolio digital resmi saya.',
                'author_name' => $user->profile?->display_name ?? $user->name,
                'author_title' => 'Kreator & Profesional',
                'cover_image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80',
                'cta_text' => 'Lihat Proyek Saya',
                'location' => 'Indonesia',
            ],
        ]);

        PortfolioPage::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Tentang Saya',
            'page_type' => 'about',
            'sort_order' => 2,
            'content' => [
                'heading' => 'Cerita & Keahlian Saya',
                'bio_paragraph' => $user->profile?->bio ?? 'Mendedikasikan karya untuk memberikan dampak positif.',
                'skills' => ['Kreativitas', 'Pengembangan Ide', 'Manajemen Proyek', 'Komunikasi'],
                'experience' => [
                    ['role' => 'Kreator Independen', 'company' => 'Proyek Personal', 'period' => '2024 - Sekarang'],
                ],
            ],
        ]);

        PortfolioPage::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Proyek Unggulan',
            'page_type' => 'projects',
            'sort_order' => 3,
            'content' => [
                'heading' => 'Karya Terpilih',
                'description' => 'Berikut adalah beberapa proyek dan hasil karya yang telah saya selesaikan.',
                'items' => [
                    [
                        'title' => 'Proyek Perdana Kreatif',
                        'category' => 'Desain & Inovasi',
                        'description' => 'Eksplorasi visual dan solusi inovatif untuk memenuhi kebutuhan klien.',
                        'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=800&q=80',
                        'link' => '#',
                        'link_label' => 'Lihat Detail',
                    ],
                ],
            ],
        ]);
    }
}
