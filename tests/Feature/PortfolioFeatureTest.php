<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\PortfolioOrder;
use App\Models\PortfolioPayment;
use App\Models\PortfolioPaymentMethod;
use App\Models\PortfolioPlan;
use App\Models\PortfolioSetting;
use App\Models\PortfolioVoucher;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic plan and payment methods
        PortfolioPlan::create([
            'name' => 'Creator Pro',
            'slug' => 'creator-pro',
            'price' => 99000,
            'promo_price' => 89100,
            'is_promo' => true,
            'duration_days' => 30,
            'max_pdf_files' => 10,
            'max_pdf_size_mb' => 5,
            'is_active' => true,
        ]);

        PortfolioPaymentMethod::create([
            'bank_name' => 'Bank BCA',
            'account_number' => '8012345678',
            'account_holder' => 'PT LUNOU KARYA DIGITAL',
            'is_active' => true,
        ]);
    }

    public function test_terms_and_conditions_page_is_accessible(): void
    {
        $response1 = $this->get('/terms');
        $response1->assertStatus(200);

        $response2 = $this->get('/syarat-ketentuan');
        $response2->assertStatus(200);
    }

    public function test_non_subscribed_user_sees_paywall_on_portfolio_studio(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/studio/portfolio');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Studio/Portfolio/Index')
            ->where('hasAccess', false)
            ->where('accessStatus', 'not_subscribed')
        );
    }

    public function test_voucher_validation_computes_discount_correctly(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan = PortfolioPlan::first();

        // Create 10% voucher
        PortfolioVoucher::create([
            'code' => 'TEST10',
            'discount_type' => 'percent',
            'discount_amount' => 10,
            'quota' => 100,
            'is_active' => true,
        ]);

        // Valid voucher validation
        $response = $this->actingAs($user)->postJson('/studio/portfolio/validate-voucher', [
            'code' => 'TEST10',
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'code' => 'TEST10',
        ]);

        // Invalid voucher validation
        $invalidResponse = $this->actingAs($user)->postJson('/studio/portfolio/validate-voucher', [
            'code' => 'BOGUSCODE',
            'plan_id' => $plan->id,
        ]);

        $invalidResponse->assertStatus(422);
        $invalidResponse->assertJson(['valid' => false]);
    }

    public function test_user_can_checkout_portfolio_subscription(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan = PortfolioPlan::first();

        $response = $this->actingAs($user)->post('/studio/portfolio/checkout', [
            'plan_id' => $plan->id,
        ]);

        $response->assertRedirect('/studio/portfolio');

        $this->assertDatabaseHas('portfolio_orders', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_user_can_upload_payment_proof_and_sensitive_data_is_encrypted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        $plan = PortfolioPlan::first();

        $order = PortfolioOrder::create([
            'order_number' => 'ORD-TEST-001',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'original_price' => 99000,
            'discount_amount' => 9900,
            'final_amount' => 89100,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $file = UploadedFile::fake()->image('bukti_transfer.jpg');

        $response = $this->actingAs($user)->post("/studio/portfolio/orders/{$order->id}/payment-proof", [
            'sender_bank' => 'Bank Mandiri',
            'sender_account_name' => 'Budi Santoso',
            'proof_image' => $file,
            'notes' => 'Ref: 998877',
        ]);

        $response->assertRedirect('/studio/portfolio');

        // Check order status changed to under_review
        $this->assertDatabaseHas('portfolio_orders', [
            'id' => $order->id,
            'payment_status' => 'under_review',
        ]);

        // Check payment record exists
        $payment = PortfolioPayment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);

        // Verify model automatically decrypts
        $this->assertEquals('Bank Mandiri', $payment->sender_bank);
        $this->assertEquals('Budi Santoso', $payment->sender_account_name);

        // Cryptography Check: Verify raw database row does NOT contain plaintext!
        $rawRow = DB::table('portfolio_payments')->where('id', $payment->id)->first();
        $this->assertStringNotContainsString('Bank Mandiri', $rawRow->sender_bank);
        $this->assertStringNotContainsString('Budi Santoso', $rawRow->sender_account_name);
        $this->assertStringStartsWith('eyJpdiI', $rawRow->sender_bank);
    }

    public function test_admin_can_reject_order_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'user']);
        $plan = PortfolioPlan::first();

        $order = PortfolioOrder::create([
            'order_number' => 'ORD-REJECT-001',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'original_price' => 99000,
            'final_amount' => 89100,
            'payment_status' => 'under_review',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/admin/portfolio/orders/{$order->id}/verify", [
            'action' => 'reject',
            'rejection_reason' => 'Nominal transfer tidak sesuai resi.',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('portfolio_orders', [
            'id' => $order->id,
            'payment_status' => 'rejected',
        ]);
    }

    public function test_admin_can_approve_order_and_entitlement_activates_automatically(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'user']);
        $plan = PortfolioPlan::first();

        $order = PortfolioOrder::create([
            'order_number' => 'ORD-APPROVE-001',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'original_price' => 99000,
            'final_amount' => 89100,
            'payment_status' => 'under_review',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/admin/portfolio/orders/{$order->id}/verify", [
            'action' => 'approve',
        ]);

        $response->assertSessionHas('success');

        // Order marked as paid
        $this->assertDatabaseHas('portfolio_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        // Entitlement automatically created and active
        $this->assertDatabaseHas('portfolio_entitlements', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        // User now has active portfolio access!
        $this->assertTrue($user->fresh()->hasActivePortfolioAccess());
    }

    public function test_entitled_user_can_access_builder_and_publish_portfolio(): void
    {
        $user = User::factory()->create(['role' => 'user', 'name' => 'Kreator Indonesia']);
        Profile::create([
            'user_id' => $user->id,
            'username' => 'kreator',
            'display_name' => 'Kreator Indonesia',
            'is_published' => true,
        ]);

        // Give entitlement
        $user->portfolioEntitlement()->create([
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'max_pdf_files' => 10,
            'max_pdf_size_mb' => 5,
        ]);

        // Visit portfolio builder
        $response = $this->actingAs($user)->get('/studio/portfolio');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Studio/Portfolio/Index')
            ->where('hasAccess', true)
            ->where('accessStatus', 'active')
        );

        $portfolio = Portfolio::where('user_id', $user->id)->first();
        $this->assertNotNull($portfolio);

        // Add a page
        $pageResponse = $this->actingAs($user)->post('/studio/portfolio/pages', [
            'title' => 'Galeri Foto Desain',
            'page_type' => 'gallery',
            'sort_order' => 4,
            'content' => ['heading' => 'Galeri Desain'],
        ]);
        $pageResponse->assertSessionHasNoErrors();

        // Toggle publish
        $publishResponse = $this->actingAs($user)->post('/studio/portfolio/publish');
        $publishResponse->assertSessionHas('success');

        $this->assertTrue($portfolio->fresh()->is_published);

        // Check public portfolio URL
        $publicResponse = $this->get("/p/{$portfolio->slug}");
        $publicResponse->assertStatus(200);
        $publicResponse->assertInertia(fn ($page) => $page
            ->component('Public/Portfolio')
            ->where('portfolio.title', $portfolio->title)
        );
    }

    public function test_admin_can_update_portfolio_pricing_and_settings(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $plan = PortfolioPlan::first();

        // Update plan
        $planResponse = $this->actingAs($admin)->patch("/admin/portfolio/plans/{$plan->id}", [
            'price' => 129000,
            'promo_price' => 99000,
            'is_promo_active' => true,
            'duration_days' => 45,
            'max_pdf_files' => 15,
            'max_pdf_size_mb' => 10,
        ]);
        $planResponse->assertSessionHas('success');

        $this->assertDatabaseHas('portfolio_plans', [
            'id' => $plan->id,
            'price' => 129000,
            'promo_price' => 99000,
            'duration_days' => 45,
        ]);

        // Update settings
        $settingsResponse = $this->actingAs($admin)->post('/admin/portfolio/settings', [
            'max_pdf_files' => 20,
            'max_pdf_size_mb' => 8,
            'terms_and_conditions' => 'Ketentuan Layanan Portofolio Lunou Versi 2.0',
        ]);
        $settingsResponse->assertSessionHas('success');

        $this->assertEquals(20, PortfolioSetting::get('max_pdf_files'));
        $this->assertEquals(8, PortfolioSetting::get('max_pdf_size_mb'));
        $this->assertEquals('Ketentuan Layanan Portofolio Lunou Versi 2.0', PortfolioSetting::get('terms_and_conditions'));
    }
}
