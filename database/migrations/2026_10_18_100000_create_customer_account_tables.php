<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akun pelanggan (login website), terpisah dari akun staf (tabel users / admin panel)
 * dan dari tabel customers (data pemesan per nomor HP yang diperbarui tiap booking).
 * Tidak mengubah data lama: kolom baru di bookings boleh kosong (booking tamu tetap berjalan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Token reset password dipisah dari staf agar email yang sama tidak saling menimpa
        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('saved_unit_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_account_id', 'unit_type_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('customer_account_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_account_id');
        });

        Schema::dropIfExists('saved_unit_types');
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customer_accounts');
    }
};
