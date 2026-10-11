<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PortfolioEntitlement;
use App\Models\PortfolioOrder;
use App\Models\PortfolioPaymentMethod;
use App\Models\PortfolioPlan;
use App\Models\PortfolioSetting;
use App\Models\PortfolioVoucher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminPortfolioController extends Controller
{
    /**
     * Update pricing, promo, or feature limits on a plan.
     */
    public function updatePlan(Request $request, PortfolioPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'price' => ['required', 'integer', 'min:0'],
            'promo_price' => ['nullable', 'integer', 'min:0'],
            'is_promo_active' => ['required', 'boolean'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'max_pdf_files' => ['required', 'integer', 'min:1', 'max:50'],
            'max_pdf_size_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'promo_ends_at' => ['nullable', 'date'],
        ]);

        $plan->update($validated);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_portfolio_plan_updated',
            'target_type' => 'portfolio_plan',
            'target_id' => $plan->id,
            'metadata' => [
                'price' => $validated['price'],
                'promo_price' => $validated['promo_price'] ?? null,
                'is_promo_active' => $validated['is_promo_active'],
            ],
        ]);

        return back()->with('success', 'Pengaturan harga & paket portofolio berhasil diperbarui.');
    }

    /**
     * Store new discount voucher.
     */
    public function storeVoucher(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:portfolio_vouchers,code'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_amount' => ['required', 'integer', 'min:1'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'quota' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $voucher = PortfolioVoucher::create([
            'code' => strtoupper(trim($validated['code'])),
            'discount_type' => $validated['discount_type'],
            'discount_amount' => $validated['discount_amount'],
            'max_discount' => $validated['max_discount'] ?? null,
            'quota' => $validated['quota'],
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_portfolio_voucher_created',
            'target_type' => 'portfolio_voucher',
            'target_id' => $voucher->id,
            'metadata' => ['code' => $voucher->code],
        ]);

        return back()->with('success', "Voucher {$voucher->code} berhasil diterbitkan.");
    }

    /**
     * Toggle voucher active status.
     */
    public function toggleVoucher(Request $request, PortfolioVoucher $voucher): RedirectResponse
    {
        $newStatus = ! $voucher->is_active;
        $voucher->update(['is_active' => $newStatus]);

        return back()->with('success', "Status voucher {$voucher->code} diubah menjadi ".($newStatus ? 'Aktif' : 'Nonaktif').'.');
    }

    /**
     * Delete voucher.
     */
    public function deleteVoucher(Request $request, PortfolioVoucher $voucher): RedirectResponse
    {
        $code = $voucher->code;
        $voucher->delete();

        return back()->with('success', "Voucher {$code} berhasil dihapus.");
    }

    /**
     * Store bank payment method.
     */
    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:100'],
            'account_holder' => ['required', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ]);

        PortfolioPaymentMethod::create($validated);

        return back()->with('success', "Rekening {$validated['bank_name']} berhasil ditambahkan.");
    }

    /**
     * Update bank payment method.
     */
    public function updatePaymentMethod(Request $request, PortfolioPaymentMethod $method): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:100'],
            'account_holder' => ['required', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        $method->update($validated);

        return back()->with('success', "Rekening {$method->bank_name} berhasil diperbarui.");
    }

    /**
     * Delete bank payment method.
     */
    public function deletePaymentMethod(Request $request, PortfolioPaymentMethod $method): RedirectResponse
    {
        $method->delete();

        return back()->with('success', 'Rekening pembayaran berhasil dihapus.');
    }

    /**
     * Verify payment transfer proof (Approve / Reject).
     */
    public function verifyPayment(Request $request, PortfolioOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $admin = $request->user();
        $targetUser = $order->user;
        $payment = $order->payment;

        if ($validated['action'] === 'approve') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'completed',
            ]);

            if ($payment) {
                $payment->update([
                    'verified_by' => $admin->id,
                    'verified_at' => now(),
                ]);
            }

            // Increment voucher used count if voucher was used
            if ($order->voucher) {
                $order->voucher->increment('used_count');
            }

            // Activate or extend entitlement
            $planDays = $order->plan?->duration_days ?? 30;
            $entitlement = PortfolioEntitlement::where('user_id', $targetUser->id)->first();

            if ($entitlement) {
                $baseDate = ($entitlement->isActive() && $entitlement->expires_at)
                    ? $entitlement->expires_at
                    : now();

                $entitlement->update([
                    'order_id' => $order->id,
                    'status' => 'active',
                    'expires_at' => $baseDate->copy()->addDays($planDays),
                    'max_pdf_files' => $order->plan?->max_pdf_files ?? 10,
                    'max_pdf_size_mb' => $order->plan?->max_pdf_size_mb ?? 5,
                ]);
            } else {
                PortfolioEntitlement::create([
                    'user_id' => $targetUser->id,
                    'order_id' => $order->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => now()->addDays($planDays),
                    'max_pdf_files' => $order->plan?->max_pdf_files ?? 10,
                    'max_pdf_size_mb' => $order->plan?->max_pdf_size_mb ?? 5,
                ]);
            }

            AuditLog::create([
                'actor_id' => $admin->id,
                'action' => 'portfolio_payment_approved',
                'target_type' => 'portfolio_order',
                'target_id' => $order->id,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'user_id' => $targetUser->id,
                    'duration_days' => $planDays,
                ],
            ]);

            return back()->with('success', "Pembayaran pesanan {$order->order_number} telah disetujui! Akses premium portofolio untuk {$targetUser->name} otomatis aktif selama {$planDays} hari.");
        }

        // Action: Reject
        $order->update([
            'payment_status' => 'rejected',
        ]);

        if ($payment) {
            $payment->update([
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'rejection_reason' => $validated['rejection_reason'] ?: 'Bukti transfer tidak valid atau dana belum masuk.',
            ]);
        }

        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'portfolio_payment_rejected',
            'target_type' => 'portfolio_order',
            'target_id' => $order->id,
            'metadata' => ['order_number' => $order->order_number],
        ]);

        return back()->with('success', "Pembayaran pesanan {$order->order_number} telah ditolak.");
    }

    /**
     * Manually grant, extend, or revoke access for a user.
     */
    public function manageEntitlement(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:grant,extend,revoke'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $entitlement = PortfolioEntitlement::where('user_id', $user->id)->first();

        if ($validated['action'] === 'revoke') {
            if ($entitlement) {
                $entitlement->update(['status' => 'suspended']);
            }

            return back()->with('success', "Akses portofolio untuk {$user->name} telah dinonaktifkan.");
        }

        if ($entitlement) {
            $baseDate = ($entitlement->isActive() && $entitlement->expires_at)
                ? $entitlement->expires_at
                : now();

            $entitlement->update([
                'status' => 'active',
                'expires_at' => $baseDate->copy()->addDays($days),
            ]);
        } else {
            PortfolioEntitlement::create([
                'user_id' => $user->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addDays($days),
                'max_pdf_files' => 10,
                'max_pdf_size_mb' => 5,
            ]);
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'portfolio_entitlement_manual_updated',
            'target_type' => 'user',
            'target_id' => $user->id,
            'metadata' => ['action' => $validated['action'], 'days' => $days],
        ]);

        return back()->with('success', "Akses portofolio untuk {$user->name} berhasil diperbarui (+{$days} hari).");
    }

    /**
     * Update global platform settings, Free Mode, and Terms of Service.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'max_pdf_files' => ['required', 'integer', 'min:1', 'max:50'],
            'max_pdf_size_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'terms_and_conditions' => ['nullable', 'string'],
            'free_mode_enabled' => ['nullable', 'boolean'],
            'saweria_url' => ['nullable', 'string', 'max:255'],
        ]);

        PortfolioSetting::set('max_pdf_files', $validated['max_pdf_files']);
        PortfolioSetting::set('max_pdf_size_mb', $validated['max_pdf_size_mb']);
        PortfolioSetting::set('terms_and_conditions', $validated['terms_and_conditions'] ?? '');
        PortfolioSetting::set('free_mode_enabled', (bool) ($validated['free_mode_enabled'] ?? false));

        $saweriaUrl = ! empty($validated['saweria_url'])
            ? $validated['saweria_url']
            : PortfolioSetting::get('saweria_url', 'https://saweria.co/Try3');
        PortfolioSetting::set('saweria_url', $saweriaUrl);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_portfolio_settings_updated',
            'target_type' => 'portfolio_setting',
            'target_id' => 1,
            'metadata' => [
                'free_mode_enabled' => (bool) ($validated['free_mode_enabled'] ?? false),
                'saweria_url' => $saweriaUrl,
            ],
        ]);

        return back()->with('success', 'Pengaturan global, mode gratis, dan donasi Saweria berhasil disimpan.');
    }

    /**
     * Quick 1-click toggle to bypass all prices (make everything free).
     */
    public function toggleFreeMode(Request $request): RedirectResponse
    {
        $current = (bool) PortfolioSetting::get('free_mode_enabled', false);
        $newStatus = ! $current;

        PortfolioSetting::set('free_mode_enabled', $newStatus);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_portfolio_toggle_free_mode',
            'target_type' => 'portfolio_setting',
            'target_id' => 1,
            'metadata' => ['free_mode_enabled' => $newStatus],
        ]);

        $msg = $newStatus
            ? 'MODE GRATIS DIAKTIFKAN: Seluruh harga kini Rp 0 (Bypass gratis). Semua pengguna dapat langsung mengakses portofolio pro!'
            : 'Mode gratis dinonaktifkan: Seluruh harga kembali normal sesuai paket.';

        return back()->with('success', $msg);
    }

    /**
     * Update public showcase slides (photos, titles, descriptions).
     */
    public function updateSlides(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slides' => ['required', 'array'],
            'slides.*.title' => ['required', 'string', 'max:200'],
            'slides.*.subtitle' => ['nullable', 'string', 'max:200'],
            'slides.*.image_url' => ['required', 'string', 'max:500'],
            'slides.*.description' => ['required', 'string', 'max:1000'],
            'slides.*.tag' => ['nullable', 'string', 'max:100'],
            'slides.*.link' => ['nullable', 'string', 'max:300'],
        ]);

        PortfolioSetting::set('showcase_slides', $validated['slides']);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_portfolio_slides_updated',
            'target_type' => 'portfolio_setting',
            'target_id' => 1,
            'metadata' => ['slide_count' => count($validated['slides'])],
        ]);

        return back()->with('success', 'Slide contoh karya & foto produk berhasil diperbarui untuk halaman utama.');
    }

    /**
     * Update order steps (cara pemesanan).
     */
    public function updateOrderSteps(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'steps' => ['required', 'array'],
            'steps.*.step_number' => ['required', 'integer', 'min:1'],
            'steps.*.title' => ['required', 'string', 'max:200'],
            'steps.*.description' => ['required', 'string', 'max:1000'],
            'steps.*.badge' => ['nullable', 'string', 'max:100'],
            'steps.*.icon' => ['nullable', 'string', 'max:50'],
        ]);

        PortfolioSetting::set('order_steps', $validated['steps']);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin_portfolio_order_steps_updated',
            'target_type' => 'portfolio_setting',
            'target_id' => 1,
            'metadata' => ['step_count' => count($validated['steps'])],
        ]);

        return back()->with('success', 'Alur Cara Pemesanan berhasil diperbarui untuk halaman utama.');
    }
}
