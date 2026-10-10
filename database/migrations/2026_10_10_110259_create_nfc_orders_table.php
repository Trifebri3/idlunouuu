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
        Schema::create('nfc_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nfc_device_id')->nullable()->constrained('nfc_devices')->nullOnDelete();
            $table->string('order_number', 64)->unique();
            $table->string('card_variant', 50)->default('matte_black');
            $table->string('card_printed_name', 100);
            $table->string('phone_or_whatsapp', 50);
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_method', 50)->default('qris');
            $table->string('payment_status', 30)->default('pending');
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('amount')->default(149000);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfc_orders');
    }
};
