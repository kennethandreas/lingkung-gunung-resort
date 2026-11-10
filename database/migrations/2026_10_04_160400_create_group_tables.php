<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_sets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();       // A-E
            $table->string('name');
            $table->unsignedTinyInteger('coffee_break_choices')->default(2); // "pilih 2 macam menu"
            $table->timestamps();
        });

        // Opsi A-E per waktu makan di dalam satu set menu
        Schema::create('menu_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_set_id')->constrained()->cascadeOnDelete();
            $table->string('meal');                    // breakfast | lunch | dinner | coffee_break
            $table->string('option_code', 30);         // A-E, atau kelompok coffee break
            $table->string('label')->nullable();       // mis. "Goreng / Rebus"
            $table->json('items');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['menu_set_id', 'meal', 'option_code']);
        });

        Schema::create('group_packages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('accommodation');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('group_package_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_package_id')->constrained()->cascadeOnDelete();
            $table->string('code', 5);                 // A | B | C
            $table->string('name');
            $table->unsignedInteger('min_pax');
            $table->foreignId('menu_set_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('coffee_breaks')->default(0);
            $table->boolean('weekday_only')->default(false);
            $table->unsignedInteger('price_weekday');
            $table->unsignedInteger('price_weekend');
            $table->json('includes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['group_package_id', 'code']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category');                // adventure | sanggar_alam | konsumsi
            $table->unsignedInteger('price');
            $table->string('price_unit');              // pax | ekor
            $table->unsignedInteger('min_pax')->default(1);
            $table->string('includes')->nullable();
            $table->string('schedule')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('additional_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');                // aula | lapangan | perlengkapan | meeting | konsumsi | hiburan | utilitas
            $table->string('unit');                    // sett | area | pax | porsi | kursi | meja | paket
            $table->string('unit_label')->nullable();  // teks satuan asli dari brosur
            $table->unsignedInteger('capacity')->nullable();
            $table->string('layout')->nullable();      // U Shape | Lesehan | Classroom
            $table->string('includes')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('min_qty')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Proposal B2B dari Package Builder (diisi di F3)
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();        // nomor penawaran
            $table->string('code', 20)->unique();      // kode acak untuk URL proposal
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('group_package_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->date('event_date');
            $table->unsignedSmallInteger('days')->default(1);
            $table->unsignedInteger('pax');
            $table->string('status')->index();         // baru | dihubungi | deal | batal
            $table->unsignedBigInteger('total');
            $table->string('pdf_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');               // package | activity | additional_item | menu
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('description');
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('subtotal');
            $table->json('meta')->nullable();          // pilihan menu per hari, dll
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('additional_items');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('group_package_variants');
        Schema::dropIfExists('group_packages');
        Schema::dropIfExists('menu_options');
        Schema::dropIfExists('menu_sets');
    }
};
