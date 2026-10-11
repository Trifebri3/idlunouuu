<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\NfcDevice;
use App\Models\Profile;
use App\Models\ProfileComponent;
use App\Models\ProfileEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LunouSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@lunou.id'],
            [
                'name' => 'Lunou Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80',
                'email_verified_at' => now(),
            ]
        );

        $adminProfile = Profile::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'username' => 'lunou',
                'display_name' => 'LUNOU Official',
                'bio' => 'Platform identitas digital modern & kartu sosial NFC pintar. Satu identitas untuk segalanya.',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80',
                'theme' => 'clean-light',
                'is_published' => true,
            ]
        );

        ProfileComponent::updateOrCreate(
            ['profile_id' => $adminProfile->id, 'sort_order' => 1],
            [
                'type' => 'featured_link',
                'title' => 'Dapatkan Kartu YOIMO NFC',
                'content' => 'Kartu fisik eksklusif berteknologi tap-and-connect.',
                'url' => 'https://yoimo.lunou.id',
                'settings' => ['icon' => 'card', 'highlight' => true],
                'is_visible' => true,
            ]
        );

        ProfileComponent::updateOrCreate(
            ['profile_id' => $adminProfile->id, 'sort_order' => 2],
            [
                'type' => 'social_link',
                'title' => 'Instagram Official',
                'url' => 'https://instagram.com/lunou.id',
                'settings' => ['icon' => 'instagram'],
                'is_visible' => true,
            ]
        );

        ProfileComponent::updateOrCreate(
            ['profile_id' => $adminProfile->id, 'sort_order' => 3],
            [
                'type' => 'social_link',
                'title' => 'YouTube Channel',
                'url' => 'https://youtube.com/@lunou',
                'settings' => ['icon' => 'youtube'],
                'is_visible' => true,
            ]
        );

        ProfileComponent::updateOrCreate(
            ['profile_id' => $adminProfile->id, 'sort_order' => 4],
            [
                'type' => 'contact',
                'title' => 'WhatsApp Support',
                'content' => 'Hubungi Customer Care LUNOU',
                'url' => 'https://wa.me/6285862319524',
                'settings' => ['icon' => 'whatsapp'],
                'is_visible' => true,
            ]
        );

        // 2. Demo User (Tri)
        $user = User::updateOrCreate(
            ['email' => 'tri@lunou.id'],
            [
                'name' => 'Tri Lunou',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80',
                'email_verified_at' => now(),
            ]
        );

        $userProfile = Profile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'username' => 'tri',
                'display_name' => 'Tri Wicaksono',
                'bio' => 'Product Designer & Tech Explorer. Connecting minds through NFC & modern experiences.',
                'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80',
                'theme' => 'clean-light',
                'is_published' => true,
            ]
        );

        ProfileComponent::updateOrCreate(
            ['profile_id' => $userProfile->id, 'sort_order' => 1],
            [
                'type' => 'social_link',
                'title' => 'Instagram',
                'url' => 'https://instagram.com/tri',
                'settings' => ['icon' => 'instagram'],
                'is_visible' => true,
            ]
        );

        ProfileComponent::updateOrCreate(
            ['profile_id' => $userProfile->id, 'sort_order' => 2],
            [
                'type' => 'social_link',
                'title' => 'GitHub Projects',
                'url' => 'https://github.com/tri',
                'settings' => ['icon' => 'github'],
                'is_visible' => true,
            ]
        );

        ProfileComponent::updateOrCreate(
            ['profile_id' => $userProfile->id, 'sort_order' => 3],
            [
                'type' => 'social_link',
                'title' => 'LinkedIn Network',
                'url' => 'https://linkedin.com/in/tri',
                'settings' => ['icon' => 'linkedin'],
                'is_visible' => true,
            ]
        );

        ProfileComponent::updateOrCreate(
            ['profile_id' => $userProfile->id, 'sort_order' => 4],
            [
                'type' => 'contact',
                'title' => 'Kirim Email Kerjasama',
                'url' => 'mailto:tri@lunou.id',
                'settings' => ['icon' => 'email'],
                'is_visible' => true,
            ]
        );

        // 3. NFC Device for Tri
        NfcDevice::updateOrCreate(
            ['public_id' => 'LUNOU-TRI-001'],
            [
                'user_id' => $user->id,
                'label' => 'YOIMO Matte Black Series',
                'status' => 'active',
                'activated_at' => now(),
                'last_seen_at' => now(),
            ]
        );

        // 4. Sample Events
        ProfileEvent::create([
            'profile_id' => $userProfile->id,
            'event_type' => 'view',
            'created_at' => now()->subHours(2),
        ]);

        ProfileEvent::create([
            'profile_id' => $userProfile->id,
            'event_type' => 'view',
            'created_at' => now()->subHour(),
        ]);

        // 5. Sample Audit Log
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'seed_system_initialized',
            'target_type' => 'system',
            'target_id' => 1,
            'metadata' => ['seeded' => true],
            'created_at' => now(),
        ]);
    }
}
