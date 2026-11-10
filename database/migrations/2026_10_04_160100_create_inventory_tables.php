<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tipe akomodasi (Bale Gunung, Glamourcamp, dll)
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');                       // nama di daftar harga brosur
            $table->string('marketing_name');             // nama yang tampil di website
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('bed_configuration')->nullable();
            $table->unsignedSmallInteger('capacity_pax');  // kapasitas per unit (per orang untuk pricing_unit=person)
            $table->unsignedSmallInteger('breakfast_pax')->default(0);
            $table->unsignedInteger('total_units');        // stok harian default (kamar/tenda/kavling, atau orang)
            $table->string('pricing_unit');                // room | tent | person | plot
            $table->json('includes')->nullable();
            $table->unsignedInteger('extra_pax_price')->nullable(); // harga pax tambahan (belum ada di brosur)
            $table->boolean('is_active')->default(true);
            $table->boolean('is_bookable_online')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Unit fisik untuk penugasan saat check-in (Bale 1-5, Tenda 1-35, ...)
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_type_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['unit_type_id', 'code']);
        });

        // Stok per tipe unit per tanggal (malam). Baris ini yang dikunci saat booking.
        Schema::create('daily_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_type_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('booked')->default(0);
            $table->unsignedInteger('held')->default(0);
            $table->boolean('is_blocked')->default(false);
            $table->string('block_note')->nullable();
            $table->timestamps();
            $table->unique(['unit_type_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_inventory');
        Schema::dropIfExists('units');
        Schema::dropIfExists('unit_types');
    }
};
