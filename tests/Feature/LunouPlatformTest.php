<?php

namespace Tests\Feature;

use App\Models\NfcDevice;
use App\Models\NfcOrder;
use App\Models\Profile;
use App\Models\ProfileComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LunouPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_public_profile_can_be_viewed_by_username(): void
    {
        $user = User::factory()->create(['name' => 'Tri Wicaksono']);
        $profile = Profile::create([
            'user_id' => $user->id,
            'username' => 'tri',
            'display_name' => 'Tri Wicaksono',
            'bio' => 'My bio here',
            'theme' => 'midnight',
            'is_published' => true,
        ]);

        $response = $this->get('/tri');

        $response->assertStatus(200);
        $this->assertDatabaseHas('profile_events', [
            'profile_id' => $profile->id,
            'event_type' => 'view',
        ]);
    }

    public function test_component_click_can_be_tracked(): void
    {
        $user = User::factory()->create();
        $profile = Profile::create([
            'user_id' => $user->id,
            'username' => 'tri',
            'display_name' => 'Tri',
            'theme' => 'midnight',
            'is_published' => true,
        ]);
        $component = ProfileComponent::create([
            'profile_id' => $profile->id,
            'type' => 'social_link',
            'title' => 'Instagram',
            'url' => 'https://instagram.com/tri',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $response = $this->post("/tri/click/{$component->id}");

        $response->assertRedirect('https://instagram.com/tri');
        $this->assertDatabaseHas('profile_events', [
            'profile_id' => $profile->id,
            'component_id' => $component->id,
            'event_type' => 'click',
        ]);
    }

    public function test_authenticated_user_can_access_studio(): void
    {
        $user = User::factory()->create();
        Profile::create([
            'user_id' => $user->id,
            'username' => 'testuser',
            'display_name' => 'Test User',
            'theme' => 'midnight',
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->get('/studio');

        $response->assertStatus(200);
    }

    public function test_user_can_add_component(): void
    {
        $user = User::factory()->create();
        $profile = Profile::create([
            'user_id' => $user->id,
            'username' => 'testuser',
            'display_name' => 'Test User',
            'theme' => 'midnight',
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->post('/studio/components', [
            'type' => 'social_link',
            'title' => 'YouTube',
            'url' => 'https://youtube.com/@test',
            'settings' => ['icon' => 'youtube'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('profile_components', [
            'profile_id' => $profile->id,
            'title' => 'YouTube',
            'type' => 'social_link',
        ]);
    }

    public function test_user_can_update_username_and_validates_uniqueness(): void
    {
        $user1 = User::factory()->create();
        $profile1 = Profile::create([
            'user_id' => $user1->id,
            'username' => 'takenname',
            'display_name' => 'User 1',
            'theme' => 'midnight',
            'is_published' => true,
        ]);

        $user2 = User::factory()->create();
        $profile2 = Profile::create([
            'user_id' => $user2->id,
            'username' => 'myname',
            'display_name' => 'User 2',
            'theme' => 'midnight',
            'is_published' => true,
        ]);

        // Attempt to take existing username
        $response = $this->actingAs($user2)->post('/studio/username', [
            'username' => 'takenname',
        ]);

        $response->assertSessionHasErrors('username');

        // Valid new username
        $response = $this->actingAs($user2)->post('/studio/username', [
            'username' => 'newunique',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('profiles', [
            'id' => $profile2->id,
            'username' => 'newunique',
        ]);
    }

    public function test_nfc_tap_redirects_to_user_profile(): void
    {
        $user = User::factory()->create();
        Profile::create([
            'user_id' => $user->id,
            'username' => 'tri',
            'display_name' => 'Tri',
            'theme' => 'midnight',
            'is_published' => true,
        ]);

        NfcDevice::create([
            'user_id' => $user->id,
            'public_id' => 'YOIMO-CARD-01',
            'status' => 'active',
        ]);

        $response = $this->get('/nfc/YOIMO-CARD-01');

        $response->assertRedirect('/tri?from=nfc&card=YOIMO-CARD-01');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $regularUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regularUser)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_unassigned_nfc_card_in_advance(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post('/admin/nfc', [
            'public_id' => 'YOIMO-BATCH-99',
            'label' => 'YOIMO Test Batch',
            'user_id' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nfc_devices', [
            'public_id' => 'YOIMO-BATCH-99',
            'user_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_embed_card_to_user_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create();

        $card = NfcDevice::create([
            'public_id' => 'YOIMO-CARD-EMBED',
            'status' => 'pending',
            'user_id' => null,
        ]);

        $response = $this->actingAs($admin)->post("/admin/nfc/{$card->id}/assign", [
            'user_id' => $user->id,
            'label' => 'Kartu Pribadi Tri',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nfc_devices', [
            'id' => $card->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_unassigned_nfc_card_tap_redirects_to_activation_page(): void
    {
        NfcDevice::create([
            'public_id' => 'YOIMO-UNCLAIMED',
            'status' => 'pending',
            'user_id' => null,
        ]);

        $response = $this->get('/nfc/YOIMO-UNCLAIMED');

        $response->assertRedirect('/nfc/YOIMO-UNCLAIMED/activate');
    }

    public function test_user_can_claim_card_via_activation_page(): void
    {
        $user = User::factory()->create();
        NfcDevice::create([
            'public_id' => 'YOIMO-CLAIM-DIRECT',
            'status' => 'pending',
            'user_id' => null,
        ]);

        $response = $this->actingAs($user)->post('/nfc/YOIMO-CLAIM-DIRECT/activate');

        $response->assertRedirect('/studio');
        $this->assertDatabaseHas('nfc_devices', [
            'public_id' => 'YOIMO-CLAIM-DIRECT',
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_public_profile_contains_dynamic_meta_and_apple_touch_icon_for_nfc_popup(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        Profile::create([
            'user_id' => $user->id,
            'username' => 'budi',
            'display_name' => 'Budi Santoso',
            'bio' => 'Insinyur & Pengusaha Teknologi',
            'theme' => 'clean-light',
            'is_published' => true,
        ]);

        $response = $this->get('/budi');

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso (@budi) · LUNOU', false);
        $response->assertSee('apple-touch-icon', false);
        $response->assertSee('/icon/1.png', false);
        $response->assertSee('manifest.json', false);
    }

    public function test_user_can_submit_nfc_card_order(): void
    {
        $user = User::factory()->create(['name' => 'Siti Nurhaliza']);
        Profile::create([
            'user_id' => $user->id,
            'username' => 'siti',
            'display_name' => 'Siti Nurhaliza',
            'theme' => 'clean-light',
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->post('/studio/nfc/order', [
            'card_variant' => 'matte_black',
            'card_printed_name' => 'Siti Nurhaliza',
            'phone_or_whatsapp' => '081234567890',
            'shipping_address' => 'Jl. Sudirman No. 45, Jakarta',
            'notes' => 'Tolong cetak logo tegas',
            'payment_method' => 'qris',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nfc_orders', [
            'user_id' => $user->id,
            'card_printed_name' => 'Siti Nurhaliza',
            'card_variant' => 'matte_black',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    public function test_admin_can_connect_unassigned_stock_card_to_user_order(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['name' => 'Rian Hidayat']);
        Profile::create([
            'user_id' => $user->id,
            'username' => 'rian',
            'display_name' => 'Rian Hidayat',
            'theme' => 'clean-light',
            'is_published' => true,
        ]);

        // Pre-provisioned card created by admin
        $stockCard = NfcDevice::create([
            'public_id' => 'YOIMO-STOCK-77',
            'label' => 'YOIMO Stock Card',
            'user_id' => null,
            'status' => 'pending',
        ]);

        // User submits order
        $order = NfcOrder::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-001',
            'card_variant' => 'matte_black',
            'card_printed_name' => 'Rian Hidayat',
            'phone_or_whatsapp' => '081298765432',
            'shipping_address' => 'Bandung, Jawa Barat',
            'payment_method' => 'qris',
            'payment_status' => 'pending',
            'status' => 'pending',
            'amount' => 149000,
        ]);

        // Admin connects the stock card to this order
        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/connect", [
            'nfc_device_id' => $stockCard->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nfc_orders', [
            'id' => $order->id,
            'nfc_device_id' => $stockCard->id,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('nfc_devices', [
            'id' => $stockCard->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_redeem_order_code_for_keychain(): void
    {
        $user = User::factory()->create();
        Profile::create([
            'user_id' => $user->id,
            'username' => 'redeemuser',
            'display_name' => 'Redeem User',
            'theme' => 'clean-light',
            'is_published' => true,
        ]);

        // Pre-order created offline or via WhatsApp
        $order = NfcOrder::create([
            'user_id' => null,
            'order_number' => 'LUNOU-261010-OFFLINE',
            'card_variant' => 'custom_keychain',
            'card_printed_name' => 'Redeem User',
            'custom_image' => '/icon/1.png',
            'phone_or_whatsapp' => '085862319524',
            'shipping_address' => 'Jakarta Barat',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'status' => 'processing',
            'amount' => 49000,
        ]);

        $response = $this->actingAs($user)->post('/studio/nfc/redeem', [
            'code' => 'LUNOU-261010-OFFLINE',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nfc_orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('nfc_devices', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_verify_payment_and_create_offline_order(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        // 1. Create offline order
        $createResponse = $this->actingAs($admin)->post('/admin/orders/create-offline', [
            'card_printed_name' => 'Pelanggan WhatsApp',
            'phone_or_whatsapp' => '085862319524',
            'shipping_address' => 'Jl. Sudirman 10',
            'custom_image' => '/icon/3.png',
        ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('nfc_orders', [
            'card_printed_name' => 'Pelanggan WhatsApp',
            'phone_or_whatsapp' => '085862319524',
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        // 2. Verify payment for a pending order
        $order = NfcOrder::create([
            'user_id' => $admin->id,
            'order_number' => 'LUNOU-261010-PENDING',
            'card_variant' => 'custom_keychain',
            'card_printed_name' => 'Pelanggan Pending',
            'phone_or_whatsapp' => '08123456789',
            'shipping_address' => 'Surabaya',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'status' => 'pending',
            'amount' => 49000,
        ]);

        $verifyResponse = $this->actingAs($admin)->patch("/admin/orders/{$order->id}/verify-payment");

        $verifyResponse->assertRedirect();
        $this->assertDatabaseHas('nfc_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);
    }
}
