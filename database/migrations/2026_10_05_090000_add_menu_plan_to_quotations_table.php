<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedSmallInteger('nights')->default(1)->after('days');
            $table->json('menu_plan')->nullable()->after('pax');     // pilihan menu per hari per waktu makan
            $table->unsignedBigInteger('subtotal')->default(0)->after('status');
            $table->timestamp('contacted_at')->nullable()->after('pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['nights', 'menu_plan', 'subtotal', 'contacted_at']);
        });
    }
};
