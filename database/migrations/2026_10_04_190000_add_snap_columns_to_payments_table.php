<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Token Snap dipakai ulang selama belum kedaluwarsa (tamu klik "Bayar" berkali-kali)
            $table->string('snap_token')->nullable()->after('gateway_transaction_id');
            $table->timestamp('expires_at')->nullable()->after('snap_token');
            $table->string('payment_channel')->nullable()->after('method'); // qris, bank_transfer, gopay, ...
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['snap_token', 'expires_at', 'payment_channel']);
        });
    }
};
