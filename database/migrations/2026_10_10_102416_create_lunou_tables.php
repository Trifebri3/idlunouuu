<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('google_id');
            $table->string('role', 30)->default('user')->after('avatar');
            $table->string('status', 30)->default('active')->after('role');
            $table->string('password')->nullable()->change();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('username')->unique();
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('theme', 50)->default('midnight');
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('username');
        });

        Schema::create('profile_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40); // social_link, contact, text, heading, featured_link, video
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('url')->nullable();
            $table->json('settings')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['profile_id', 'sort_order']);
        });

        Schema::create('nfc_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_id', 64)->unique();
            $table->string('label')->nullable();
            $table->string('status', 30)->default('pending'); // pending, active, suspended
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('public_id');
        });

        Schema::create('profile_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained('profile_components')->nullOnDelete();
            $table->string('event_type', 30); // view, click
            $table->timestamp('created_at')->useCurrent();

            $table->index(['profile_id', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('profile_events');
        Schema::dropIfExists('nfc_devices');
        Schema::dropIfExists('profile_components');
        Schema::dropIfExists('profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar', 'role', 'status']);
        });
    }
};
