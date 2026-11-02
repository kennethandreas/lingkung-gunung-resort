<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->index();
            $table->string('organization')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();        // kode acak untuk URL publik
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('channel');                   // web | manual | b2b
            $table->string('status')->index();           // lihat App\Enums\BookingStatus
            $table->string('payment_option')->default('dp'); // dp | full
            $table->date('check_in')->index();
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');
            $table->unsignedSmallInteger('guests');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('dp_amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->foreignId('promo_id')->nullable()->constrained()->nullOnDelete();
            $table->date('due_date')->nullable();        // batas pelunasan (H-3)
            $table->timestamp('hold_expires_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('fully_paid_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');                 // unit_type | activity | additional_item
            $table->unsignedBigInteger('item_id');
            $table->string('description');
            $table->unsignedInteger('qty');
            $table->unsignedSmallInteger('pax')->default(0);
            $table->unsignedBigInteger('unit_price');    // rata-rata per unit per malam (info)
            $table->unsignedBigInteger('subtotal');
            $table->json('breakdown')->nullable();       // rincian harga per malam
            $table->timestamps();
            $table->index(['item_type', 'item_id']);
        });

        // Penugasan unit fisik saat check-in
        Schema::create('booking_item_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['booking_item_id', 'unit_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('type');                      // dp | settlement | full
            $table->string('method');                    // midtrans | transfer
            $table->unsignedBigInteger('amount');
            $table->string('gateway_order_id')->nullable()->unique();
            $table->string('gateway_transaction_id')->nullable();
            $table->string('status')->index();           // pending | paid | failed | expired
            $table->json('raw_payload')->nullable();
            $table->string('proof_path')->nullable();    // bukti transfer manual
            $table->string('note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable()->index();
            $table->boolean('signature_valid')->default(false);
            $table->json('payload');
            $table->string('result')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_item_unit');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('customers');
    }
};
