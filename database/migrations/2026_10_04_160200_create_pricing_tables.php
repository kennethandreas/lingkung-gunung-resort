<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harga per tipe unit per kategori hari (rupiah, bilangan bulat)
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_type_id')->constrained()->cascadeOnDelete();
            $table->string('day_type');                // weekday | weekend | high_season
            $table->unsignedInteger('price');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();       // null = berlaku seterusnya
            $table->timestamps();
            $table->index(['unit_type_id', 'day_type', 'valid_from']);
        });

        // Penanda hari libur dan musim ramai (menimpa aturan weekday/weekend)
        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('day_type');                // weekday | weekend | high_season
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type');                    // percent | fixed
            $table->unsignedInteger('value');          // persen (1-100) atau rupiah
            $table->unsignedInteger('max_discount')->nullable();
            $table->unsignedInteger('min_total')->default(0);
            $table->unsignedInteger('quota')->nullable(); // null = tanpa batas
            $table->unsignedInteger('used')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pengaturan umum yang bisa diubah dari admin panel (kunci-nilai)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('promos');
        Schema::dropIfExists('calendar_days');
        Schema::dropIfExists('rates');
    }
};
