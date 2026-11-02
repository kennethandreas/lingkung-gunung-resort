<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pesan dari form "Kirim pertanyaan" di halaman Kontak (admin: Reservasi › Pesan Masuk). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->string('topic', 20)->index();
            $table->date('visit_date')->nullable();
            $table->unsignedInteger('participants')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('baru')->index();
            $table->text('staff_note')->nullable();
            $table->foreignId('customer_account_id')->nullable()->constrained('customer_accounts')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
