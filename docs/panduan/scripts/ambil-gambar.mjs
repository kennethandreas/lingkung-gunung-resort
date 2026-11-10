// Ambil tangkapan layar asli website (lokal) untuk panduan.
// Syarat: server jalan (php artisan serve), data contoh sudah dibuat (data-contoh.php mode buat), puppeteer-core + Chrome terpasang.
// Pakai: node ambil-gambar.mjs [base-url]
// Nama/telepon/email pelanggan selain data contoh disamarkan (••••) sebelum dipotret.
import { execSync } from 'node:child_process';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../../..');
const out = path.resolve(here, '../gambar');
const base = process.argv[2] ?? 'http://localhost:8000';
// puppeteer-core boleh dipasang di luar proyek: set PUPPETEER_DIR ke folder node_modules-nya
const require = createRequire(process.env.PUPPETEER_DIR ? path.join(process.env.PUPPETEER_DIR, 'x.js') : import.meta.url);
const puppeteer = require('puppeteer-core');

const tinker = (file, env = {}) => execSync(`php artisan tinker docs/panduan/scripts/${file}`, { cwd: root, env: { ...process.env, ...env } }).toString().trim().split('\n').pop();
const otp = () => tinker('data-contoh.php', { PANDUAN_MODE: 'kode' });
const info = JSON.parse(tinker('info-gambar.php'));

const browser = await puppeteer.launch({ executablePath: process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
const problems = [];
const wait = ms => new Promise(r => setTimeout(r, ms));

async function newPage(width, height, mobile = false) {
    const page = await browser.newPage();
    await page.setViewport({ width, height, deviceScaleFactor: mobile ? 2 : 1.5, isMobile: mobile, hasTouch: mobile });
    page.on('pageerror', e => { if (!/ViewTransition/.test(e.message)) problems.push(`JS ${page.url()}: ${e.message}`); });
    page.on('response', r => { if (r.status() >= 500 && r.url().startsWith(base)) problems.push(`${r.status()} ${r.url()}`); });
    page.on('dialog', d => d.accept()); // peringatan "perubahan belum disimpan" saat pindah halaman
    // Matikan animasi agar tidak ada elemen setengah tampil
    await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
    return page;
}

async function mask(page) {
    await page.evaluate((words) => {
        const esc = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        if (!words.length) return;
        // Teks terpanjang dulu: email utuh tersamar sebelum nama yang menjadi bagian email
        const sorted = [...words].sort((a, b) => b.length - a.length);
        const re = new RegExp(`(?<![\\p{L}\\d])(${sorted.map(esc).join('|')})(?![\\p{L}\\d])`, 'giu');
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        for (let n = walker.nextNode(); n; n = walker.nextNode()) {
            if (re.test(n.nodeValue)) { n.nodeValue = n.nodeValue.replace(re, '••••'); }
            re.lastIndex = 0;
        }
        document.querySelectorAll('input').forEach(i => { if (re.test(i.value)) i.value = '••••'; re.lastIndex = 0; });
        document.querySelectorAll('.reveal, [class*="reveal"]').forEach(el => el.classList.add('is-visible', 'tampil'));
    }, info.mask);
}

// el = potret satu bagian halaman saja (mis. isi admin tanpa menu samping), dipotong maxHeight px
async function shot(page, name, { url, fullPage = false, el = null, scrollTo = null, maxHeight = null, before = null, waitMs = 600 } = {}) {
    if (url) await page.goto(base + url, { waitUntil: 'networkidle2', timeout: 90000 });
    if (before) await before(page);
    if (scrollTo) await page.$eval(scrollTo, el => el.scrollIntoView({ block: 'start' })).catch(() => problems.push(`${name}: ${scrollTo} tidak ada`));
    await wait(waitMs);
    await mask(page);
    const opts = { path: `${out}/${name}.jpg`, type: 'jpeg', quality: 82 };
    if (el) {
        const rect = await page.$eval(el, n => { const r = n.getBoundingClientRect(); return { x: r.left + scrollX, y: r.top + scrollY, width: r.width, height: r.height }; })
            .catch(() => { problems.push(`${name}: ${el} tidak ada`); return null; });
        if (rect) {
            opts.clip = { ...rect, height: Math.min(rect.height, maxHeight ?? rect.height) };
            opts.captureBeyondViewport = true;
        }
    } else if (fullPage) {
        const height = await page.evaluate(() => document.documentElement.scrollHeight);
        const vp = page.viewport();
        opts.clip = { x: 0, y: 0, width: vp.width, height: Math.min(height, maxHeight ?? height) };
        opts.captureBeyondViewport = true;
    }
    await page.screenshot(opts);
    console.log('✓', name);
}

// ---------------------------------------------------------------- publik (desktop & HP)
const pub = await newPage(1366, 860);
await shot(pub, 'p01-beranda', { url: '/' });
await shot(pub, 'p03-akomodasi', { url: '/akomodasi', el: 'main section.scroll-mt-32', maxHeight: 900 });
await shot(pub, 'p04-akomodasi-detail', { url: '/akomodasi/pangrango-deluxe', scrollTo: 'section.section' });
await shot(pub, 'p05-aktivitas', { url: '/play', el: '#aktivitas .container-lg', maxHeight: 700 });
await shot(pub, 'p06-rombongan', { url: '/gather', el: '#paket .container-lg', maxHeight: 620 });
await shot(pub, 'p07-rancang-paket', { url: '/rancang-paket', el: 'main', maxHeight: 820 });
await shot(pub, 'p08-pesan', { url: '/pesan?check_in=2026-11-25&check_out=2026-11-27&guests=2', waitMs: 1500 });
await shot(pub, 'p15-ulasan-publik', { url: '/ulasan', el: 'main .container-lg', maxHeight: 900 });
await shot(pub, 'p16-kontak', { url: '/kontak', scrollTo: '#kirim-pertanyaan' });
await shot(pub, 'p09-masuk', { url: '/akun/masuk' });

const hp = await newPage(390, 844, true);
await shot(hp, 'p02-beranda-hp', { url: '/' });

// ---------------------------------------------------------------- akun pelanggan contoh
await pub.goto(base + '/akun/masuk', { waitUntil: 'networkidle0' });
await pub.type('#email', 'contoh.budi@example.com');
await pub.type('#password', 'ContohTamu12345');
await Promise.all([pub.waitForNavigation({ waitUntil: 'networkidle0' }), pub.keyboard.press('Enter')]);
await shot(pub, 'p10-akun-dasbor', { url: '/akun', el: 'main .container-lg > div.grid > div.min-w-0', maxHeight: 900 });
await shot(pub, 'p11-akun-booking-detail', { url: `/akun/booking/${info.codes.dp_paid}`, el: 'main .container-lg > div.grid > div.min-w-0', maxHeight: 1000 });
await shot(pub, 'p11b-akun-ulasan', { url: '/akun/booking/LGCONTOH01', el: '#ulasan' });
await shot(pub, 'p12-status-booking', { url: `/booking/${info.codes.pending}`, el: 'main .container-lg, main', maxHeight: 1000 });
await shot(pub, 'p14-ulasan-form', { url: '/akun/booking/LGCONTOH01/ulasan/akomodasi-3', el: 'main .container-lg > div.grid > div.min-w-0', maxHeight: 1250 });

// Jendela pembayaran Midtrans (sandbox). Bila gagal terbuka, dicatat sebagai keterbatasan.
try {
    await pub.goto(base + `/booking/${info.codes.pending}`, { waitUntil: 'networkidle0' });
    const pay = await pub.$$('button');
    for (const b of pay) { if (/bayar/i.test(await b.evaluate(e => e.innerText))) { await b.click(); break; } }
    await pub.waitForSelector('iframe[src*="midtrans"], #snap-midtrans', { timeout: 20000 });
    await wait(6000);
    await shot(pub, 'p13-midtrans-sandbox', {});
} catch (e) {
    problems.push('Popup Midtrans tidak terbuka: ' + e.message);
}

// ---------------------------------------------------------------- admin panel
const adm = await newPage(1366, 860);
await shot(adm, 'a01-login', { url: '/kelola/login' });
await adm.type('input[type=email]', 'panduan.owner@example.com');
await adm.type('input[type=password]', 'PanduanContoh123');
await adm.keyboard.press('Enter');
await wait(3500);
const code = await adm.$('input[autocomplete="one-time-code"], input[inputmode="numeric"]');
if (code) {
    await shot(adm, 'a02-2fa', {});
    await code.type(otp());
    await adm.keyboard.press('Enter');
    await wait(4500);
}

await shot(adm, 'a03-dasbor', { url: '/kelola', el: '#fi-main-content', maxHeight: 1700, waitMs: 2500 });
await shot(adm, 'a04-booking-daftar', { url: '/kelola/bookings', el: '#fi-main-content', maxHeight: 1000, waitMs: 1500 });
await shot(adm, 'a05-booking-detail', { url: `/kelola/bookings/${info.codes.dp_paid}`, el: '#fi-main-content', maxHeight: 1080, waitMs: 1500 });
await shot(adm, 'a06-catat-pembayaran', {
    url: `/kelola/bookings/${info.codes.dp_paid}`, waitMs: 1500, el: '.fi-modal-window',
    before: async (p) => {
        await wait(1000);
        for (const b of await p.$$('button')) { if ((await b.evaluate(e => e.innerText.trim())) === 'Catat pembayaran') { await b.click(); break; } }
        await wait(1500);
    },
});
await shot(adm, 'a07-input-manual', { url: '/kelola/bookings/create', el: '#fi-main-content', maxHeight: 1500, waitMs: 1500 });
await shot(adm, 'a08-kalender', { url: '/kelola/occupancy-calendar', el: '#fi-main-content', maxHeight: 1000, waitMs: 1500 });
await shot(adm, 'a09-tipe-unit', { url: '/kelola/unit-types', el: '#fi-main-content', maxHeight: 1000, waitMs: 1500 });
await shot(adm, 'a10-tipe-unit-edit', { url: '/kelola/unit-types/pangrango-deluxe/edit', el: '#fi-main-content', maxHeight: 1120, waitMs: 1500 });
await shot(adm, 'a11-kode-promo', { url: '/kelola/promos', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });
await shot(adm, 'a12-paket-group', { url: '/kelola/group-packages', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });
await shot(adm, 'a13-aktivitas', { url: '/kelola/activities', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });
await shot(adm, 'a14-penawaran', { url: '/kelola/quotations', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });
await shot(adm, 'a15-pesan-masuk', { url: '/kelola/inquiries', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });
await shot(adm, 'a16-ulasan-daftar', { url: '/kelola/reviews', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });
await shot(adm, 'a17-ulasan-detail', { url: `/kelola/reviews/${info.review}`, el: '#fi-main-content', maxHeight: 1000, waitMs: 1500 });
await shot(adm, 'a18-laporan', { url: '/kelola/laporan', el: '#fi-main-content', maxHeight: 1800, waitMs: 3000 });
await shot(adm, 'a19-pengaturan', { url: '/kelola/manage-settings', el: '#fi-main-content', maxHeight: 1700, waitMs: 1500 });
await shot(adm, 'a20-galeri', { url: '/kelola/gallery-photos', el: '#fi-main-content', maxHeight: 1000, waitMs: 1500 });
await shot(adm, 'a21-halaman', { url: '/kelola/pages', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });
await shot(adm, 'a22-pengguna', { url: '/kelola/users', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });
await shot(adm, 'a23-pelanggan', { url: '/kelola/customers', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });
await shot(adm, 'a24-artikel', { url: '/kelola/articles', el: '#fi-main-content', maxHeight: 1000, waitMs: 1200 });

console.log('masalah:', problems.length ? problems : 'tidak ada');
await browser.close();
