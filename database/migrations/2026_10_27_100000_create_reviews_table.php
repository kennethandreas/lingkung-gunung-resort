<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ulasan tamu (resort, akomodasi, aktivitas, paket rombongan).
 * Target memakai foreign key sungguhan per jenis (bukan polimorfik) agar integritas dijaga database.
 * target_ref (kolom terhitung) + unique index: satu booking hanya satu ulasan per target, juga saat klik ganda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('target_type', 20);         // resort | unit_type | activity | group_package
            // RESTRICT (bukan cascade/null): kolom dipakai kolom terhitung & CHECK; target berulasan dinonaktifkan, bukan dihapus
            $table->foreignId('unit_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('group_package_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('target_ref')->storedAs('coalesce(`unit_type_id`, `activity_id`, `group_package_id`, 0)');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('reviewer_name', 60);       // nama tampil (mis. "Budi S."), disimpan saat dikirim
            $table->string('status', 20)->default('pending');
            $table->timestamp('submitted_at');
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderation_note')->nullable();        // internal, tidak pernah ditampilkan ke pelanggan
            $table->string('customer_message', 500)->nullable(); // penjelasan untuk pelanggan bila ditolak (opsional)
            $table->timestamps();

            $table->unique(['booking_id', 'target_type', 'target_ref'], 'reviews_booking_target_unique');
            $table->index(['target_type', 'target_ref', 'status', 'published_at'], 'reviews_public_lookup');
            $table->index(['status', 'submitted_at']);
            $table->index(['customer_account_id', 'status']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_range CHECK (rating BETWEEN 1 AND 5)');
            DB::statement("ALTER TABLE reviews ADD CONSTRAINT reviews_status_valid CHECK (status IN ('pending', 'published', 'rejected', 'hidden'))");
            DB::statement("ALTER TABLE reviews ADD CONSTRAINT reviews_target_consistent CHECK (
                (target_type = 'resort' AND unit_type_id IS NULL AND activity_id IS NULL AND group_package_id IS NULL)
                OR (target_type = 'unit_type' AND unit_type_id IS NOT NULL AND activity_id IS NULL AND group_package_id IS NULL)
                OR (target_type = 'activity' AND activity_id IS NOT NULL AND unit_type_id IS NULL AND group_package_id IS NULL)
                OR (target_type = 'group_package' AND group_package_id IS NOT NULL AND unit_type_id IS NULL AND activity_id IS NULL)
            )");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
