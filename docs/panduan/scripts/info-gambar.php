<?php

// Dipakai ambil-gambar.mjs: daftar teks pribadi yang harus disamarkan + kode booking contoh (JSON satu baris).
use App\Models\Booking;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Review;
use App\Models\User;

$phones = ['089900000101', '089900000102', '089900000103', '089900000104'];

$mask = collect()
    ->merge(Customer::query()->whereNotIn('phone', $phones)->get(['name', 'phone', 'email'])->flatMap(fn ($c) => [$c->name, $c->phone, $c->email]))
    ->merge(CustomerAccount::query()->where('email', '!=', 'contoh.budi@example.com')->get(['name', 'email', 'phone'])->flatMap(fn ($a) => [$a->name, $a->email, $a->phone]))
    ->merge(User::query()->where('email', '!=', 'panduan.owner@example.com')->get(['name', 'email'])->flatMap(fn ($u) => [$u->name, $u->email]))
    // Kode booking tamu lain juga disamarkan (kode = tautan akses halaman booking)
    ->merge(Booking::query()->whereDoesntHave('customer', fn ($q) => $q->whereIn('phone', $phones))->where('code', '!=', 'LGCONTOH01')->pluck('code'))
    ->filter(fn ($s) => is_string($s) && mb_strlen($s) >= 3)
    ->unique()->values();

$codes = Booking::query()->whereHas('customer', fn ($q) => $q->whereIn('phone', $phones))->get()
    ->mapWithKeys(fn ($b) => [$b->status->value => $b->code]);

$review = Review::query()->where('status', 'pending')->whereHas('booking', fn ($q) => $q->where('code', 'LGCONTOH01'))->value('id');

echo json_encode(['mask' => $mask, 'codes' => $codes, 'review' => $review]), "\n";
