import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
            // Google Fonts diunduh saat build lalu di-host sendiri (lebih cepat & tanpa request ke Google).
            fonts: [
                google('Plus Jakarta Sans', {
                    weights: [400, 500, 600, 700],
                    subsets: ['latin'],
                    preload: [{ weight: 400, style: 'normal' }, { weight: 600, style: 'normal' }],
                }),
                // Judul editorial: 400 (judul besar) + 400 italic (eyebrow/aksen) + 500 (judul kecil).
                // Kaushan Script (tulisan tangan) dihapus di tahap ux-redesign: dekoratif & sulit dibaca.
                google('Fraunces', {
                    weights: [400, 500],
                    styles: ['normal', 'italic'],
                    subsets: ['latin'],
                    preload: [{ weight: 400, style: 'normal' }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
