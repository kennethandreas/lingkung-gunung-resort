<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category');                 // umum | booking | pembayaran | rombongan | akses
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Isi halaman Play / Learn / Gather (foto via tabel media)
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('section');                  // play | learn | gather
            $table->string('group');                    // mis. Adventure & Adrenaline, School, Corporate
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['section', 'group']);
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->string('title');                    // juga dipakai sebagai teks alternatif (alt)
            $table->string('category');                 // akomodasi | aktivitas | kuliner | fasilitas | alam
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('meta_description')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('faqs');
    }
};
