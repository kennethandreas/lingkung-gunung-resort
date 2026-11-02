<?php

namespace App\Support;

/**
 * Palet warna resmi (disetujui F2, docs/DESIGN-DIRECTION.md).
 * Harus sama dengan token @theme di resources/css/app.css.
 * Dipakai tema admin Filament dan halaman /style-guide.
 */
class BrandColors
{
    public const HUTAN = [
        50 => '#f1f7f2', 100 => '#dcebdf', 200 => '#bbd7c1', 300 => '#8ebb9a', 400 => '#5f9a70', 500 => '#3f7d52',
        600 => '#2f6441', 700 => '#265036', 800 => '#1f402d', 900 => '#1a3526', 950 => '#0d1e15',
    ];

    public const EMAS = [
        50 => '#fbf7ec', 100 => '#f5ebcc', 200 => '#ebd596', 300 => '#dfbb5f', 400 => '#d4a43c', 500 => '#c08a2a',
        600 => '#a56d22', 700 => '#84521f', 800 => '#6d4320', 900 => '#5c381f', 950 => '#341c0e',
    ];

    public const TANAH = [
        50 => '#faf5ef', 100 => '#f1e6da', 200 => '#e2cbb3', 300 => '#c9a07c', 400 => '#ad7f59', 500 => '#8b5e3c',
        600 => '#734c31', 700 => '#5e3d26', 800 => '#4a3020', 900 => '#3a2618', 950 => '#20140c',
    ];

    public const KABUT = [
        50 => '#fbf9f4', 100 => '#f5f0e6', 200 => '#ebe3d3', 300 => '#d9cdb8', 400 => '#b7a98f', 500 => '#8f826b',
        600 => '#6e6452', 700 => '#544c3f', 800 => '#3a352d', 900 => '#26231e', 950 => '#161411',
    ];
}
