<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role staf resort (dokumen teknis 4.4 & bab 7).
 * - owner        : super admin Shield, semua akses (tidak perlu daftar izin)
 * - admin        : semua fitur operasional & master data, kecuali mengatur role & pengguna
 * - sales        : lihat semua data, kelola booking & pelanggan; tidak bisa ubah harga / hapus
 * - front_office : walk-in, catat pembayaran, check-in; harga hanya bisa dilihat
 * Tidak ada role yang bisa menghapus pembayaran (pembayaran tidak punya aksi hapus).
 * Izin bisa diubah lagi dari menu Pengaturan > Role & Hak Akses.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan semua izin Shield sudah ada di database
        Artisan::call('shield:generate', ['--all' => true, '--panel' => 'admin', '--option' => 'permissions', '--no-interaction' => true]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = Permission::query()->pluck('name');
        $grant = fn (callable $filter) => $all->filter($filter)->values()->all();

        $readOnly = fn (string $name) => Str::startsWith($name, ['ViewAny:', 'View:']);
        $widgets = ['View:ResortStatsOverview', 'View:UpcomingArrivals', 'View:NeedsAttention'];
        // Pesan dari form kontak: semua staf boleh membaca & menindaklanjuti (bukan menghapus)
        $inquiries = ['ViewAny:Inquiry', 'View:Inquiry', 'Update:Inquiry'];

        Role::findOrCreate('owner', 'web');

        Role::findOrCreate('admin', 'web')->syncPermissions($grant(
            // tanpa :User supaya admin tidak bisa memberi dirinya role owner
            fn (string $name) => ! Str::endsWith($name, [':Role', ':User'])
                && ! Str::startsWith($name, ['ForceDelete', 'Restore', 'Replicate'])
        ));

        Role::findOrCreate('sales', 'web')->syncPermissions($grant(
            fn (string $name) => ($readOnly($name) && ! Str::endsWith($name, [':Role', ':User', ':ActivityLog', ':WebhookLog', ':ManageSettings', ':Reports']))
                || in_array($name, ['Create:Booking', 'Update:Booking', 'Create:Customer', 'Update:Customer', 'Update:Quotation', ...$inquiries], true)
        ));

        Role::findOrCreate('front_office', 'web')->syncPermissions(array_values(array_intersect($all->all(), [
            'ViewAny:Booking', 'View:Booking', 'Create:Booking', 'Update:Booking',
            'ViewAny:Customer', 'View:Customer', 'Create:Customer', 'Update:Customer',
            'ViewAny:UnitType', 'View:UnitType',
            'View:OccupancyCalendar',
            ...$inquiries,
            ...$widgets,
        ])));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
