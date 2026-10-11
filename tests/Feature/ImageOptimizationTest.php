<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\PortfolioOrder;
use App\Models\PortfolioPlan;
use App\Models\Profile;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

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
    }

    public function test_image_optimizer_compresses_png_to_webp(): void
    {
        $file = UploadedFile::fake()->image('avatar.png', 1200, 800);

        $result = ImageOptimizer::optimizeAndStore(
            $file,
            'test_avatars',
            maxWidth: 600,
            maxHeight: 600,
            quality: 80
        );

        $this->assertTrue($result['is_compressed']);
        $this->assertEquals('image/webp', $result['mime']);
        $this->assertStringEndsWith('.webp', $result['path']);
        $this->assertStringStartsWith('/storage/test_avatars/', $result['url']);

        Storage::disk('public')->assertExists($result['path']);
    }

    public function test_image_optimizer_stores_pdf_uncompressed(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 150, 'application/pdf');

        $result = ImageOptimizer::optimizeAndStore($file, 'test_documents');

        $this->assertFalse($result['is_compressed']);
        $this->assertEquals('application/pdf', $result['mime']);
        $this->assertStringEndsWith('.pdf', $result['path']);

        Storage::disk('public')->assertExists($result['path']);
    }

    public function test_authenticated_user_can_upload_and_compress_avatar_via_api(): void
    {
        $user = User::factory()->create();
        $profile = Profile::create([
            'user_id' => $user->id,
            'username' => 'testuser',
            'display_name' => 'Test User',
            'theme' => 'clean-light',
            'is_published' => true,
        ]);

        $avatarFile = UploadedFile::fake()->image('profile.jpg', 800, 800);

        $response = $this->actingAs($user)->postJson('/studio/avatar', [
            'avatar' => $avatarFile,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json();
        $this->assertStringContainsString('/storage/avatars/', $data['avatar_url']);
        $this->assertStringEndsWith('.webp', $data['avatar_url']);

        $this->assertEquals($data['avatar_url'], $profile->fresh()->avatar);
    }

    public function test_user_can_update_profile_with_avatar_file(): void
    {
        $user = User::factory()->create();
        $profile = Profile::create([
            'user_id' => $user->id,
            'username' => 'testuser2',
            'display_name' => 'Original Name',
            'theme' => 'clean-light',
            'is_published' => true,
        ]);

        $avatarFile = UploadedFile::fake()->image('new-avatar.png', 500, 500);

        $response = $this->actingAs($user)->post('/studio/profile', [
            'display_name' => 'Updated Name',
            'bio' => 'Updated Bio',
            'theme' => 'midnight',
            'is_published' => true,
            'avatar_file' => $avatarFile,
        ]);

        $response->assertSessionHas('success');

        $profile->refresh();
        $this->assertEquals('Updated Name', $profile->display_name);
        $this->assertStringContainsString('/storage/avatars/', $profile->avatar);
        $this->assertStringEndsWith('.webp', $profile->avatar);
    }

    public function test_portfolio_asset_image_upload_is_compressed_to_webp(): void
    {
        $user = User::factory()->create();
        $user->portfolioEntitlement()->create([
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'max_pdf_files' => 10,
            'max_pdf_size_mb' => 5,
        ]);

        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'title' => 'My Portfolio',
            'slug' => 'my-portfolio',
            'theme' => 'minimal-clean',
            'is_published' => false,
        ]);

        $imageFile = UploadedFile::fake()->image('design-sample.jpg', 2000, 1500);

        $response = $this->actingAs($user)->postJson('/studio/portfolio/assets', [
            'file' => $imageFile,
            'title' => 'Design Sample',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $asset = $portfolio->assets()->first();
        $this->assertNotNull($asset);
        $this->assertEquals('image', $asset->type);
        $this->assertStringEndsWith('.webp', $asset->file_path);
        $this->assertEquals('image/webp', $asset->mime_type);
    }

    public function test_payment_proof_upload_is_compressed_to_webp(): void
    {
        $user = User::factory()->create();
        $plan = PortfolioPlan::first();

        $order = PortfolioOrder::create([
            'order_number' => 'ORD-COMPRESS-01',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'original_price' => 99000,
            'final_amount' => 99000,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $proofFile = UploadedFile::fake()->image('receipt.jpg', 1600, 1200);

        $response = $this->actingAs($user)->post("/studio/portfolio/orders/{$order->id}/payment-proof", [
            'proof_image' => $proofFile,
            'sender_bank' => 'BCA',
            'sender_account_name' => 'Budi Santoso',
            'notes' => 'Transfer bukti pembayaran',
        ]);

        $response->assertSessionHas('success');

        $payment = $order->fresh()->payment;
        $this->assertNotNull($payment);
        $this->assertStringContainsString('/storage/portfolio_payments/', $payment->proof_file);
        $this->assertStringEndsWith('.webp', $payment->proof_file);
    }
}
