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
        // 1. Portfolio Subscription Plans
        Schema::create('portfolio_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price'); // normal price in IDR
            $table->unsignedInteger('promo_price')->nullable();
            $table->boolean('is_promo_active')->default(false);
            $table->timestamp('promo_ends_at')->nullable();
            $table->unsignedInteger('duration_days')->default(30);
            $table->unsignedInteger('max_pdf_files')->default(10);
            $table->unsignedInteger('max_pdf_size_mb')->default(5);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Discount Vouchers
        Schema::create('portfolio_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('discount_type', 20)->default('percent'); // 'percent' or 'fixed'
            $table->unsignedInteger('discount_amount'); // percentage (e.g. 10 for 10%) or IDR (e.g. 20000)
            $table->unsignedInteger('max_discount')->nullable(); // cap for percentage discount
            $table->unsignedInteger('quota')->default(100);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Admin Bank Accounts for Manual Transfers (Encrypted for Security)
        Schema::create('portfolio_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 100);
            $table->text('account_number'); // Encrypted
            $table->text('account_holder'); // Encrypted
            $table->text('instructions')->nullable();
            $table->string('qr_image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Portfolio Subscription Orders
        Schema::create('portfolio_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('portfolio_plans')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('portfolio_vouchers')->nullOnDelete();
            $table->unsignedInteger('original_price');
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('final_amount');
            $table->string('payment_status', 30)->default('pending'); // pending, under_review, paid, rejected, expired
            $table->string('status', 30)->default('pending'); // pending, completed, cancelled
            $table->timestamps();

            $table->index(['user_id', 'payment_status']);
        });

        // 5. Payment Proof Uploads & Verifications (Sensitive details encrypted)
        Schema::create('portfolio_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('portfolio_orders')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('portfolio_payment_methods')->nullOnDelete();
            $table->string('proof_file');
            $table->text('sender_bank')->nullable(); // Encrypted
            $table->text('sender_account_name')->nullable(); // Encrypted
            $table->unsignedInteger('amount_paid');
            $table->text('notes')->nullable(); // Encrypted
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // 6. User Entitlements (Premium Access Grants)
        Schema::create('portfolio_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('portfolio_orders')->nullOnDelete();
            $table->string('status', 30)->default('active'); // active, expired, suspended
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_pdf_files')->default(10);
            $table->unsignedInteger('max_pdf_size_mb')->default(5);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 7. Portfolios
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('slug', 100)->unique();
            $table->text('bio')->nullable();
            $table->string('theme', 50)->default('minimal-clean');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->json('settings')->nullable(); // theme colors, typography, flipbook default, allow_download, etc.
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();

            $table->index('slug');
        });

        // 8. Portfolio Pages (Page-based visual canvas: Page 1, 2, 3...)
        Schema::create('portfolio_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained('portfolios')->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('page_type', 50)->default('custom'); // cover, about, projects, gallery, pdf_book, contact, custom
            $table->integer('sort_order')->default(0);
            $table->json('content')->nullable(); // array of structured page elements / layout blocks
            $table->timestamps();

            $table->index(['portfolio_id', 'sort_order']);
        });

        // 9. Portfolio Assets (Uploaded Images, PDFs, Thumbnails)
        Schema::create('portfolio_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained('portfolios')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // 'image', 'pdf'
            $table->string('title', 150);
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('mime_type', 100);
            $table->timestamps();

            $table->index(['portfolio_id', 'type']);
        });

        // 10. Global Portfolio & Platform Settings (Key-Value)
        Schema::create('portfolio_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_settings');
        Schema::dropIfExists('portfolio_assets');
        Schema::dropIfExists('portfolio_pages');
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('portfolio_entitlements');
        Schema::dropIfExists('portfolio_payments');
        Schema::dropIfExists('portfolio_orders');
        Schema::dropIfExists('portfolio_payment_methods');
        Schema::dropIfExists('portfolio_vouchers');
        Schema::dropIfExists('portfolio_plans');
    }
};
