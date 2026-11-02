// Ubah docs/lingkung-gunung-website-user-manual-id.html menjadi PDF A4 dengan Chrome.
// Sekaligus memeriksa: isi halaman tidak meluap, semua gambar termuat, font termuat.
// Pakai: node buat-pdf.mjs   (PUPPETEER_DIR / CHROME_PATH opsional, lihat ambil-gambar.mjs)
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const docs = path.resolve(here, '../..');
const html = path.join(docs, 'lingkung-gunung-website-user-manual-id.html');
const pdf = path.join(docs, 'lingkung-gunung-website-user-manual-id.pdf');
const require = createRequire(process.env.PUPPETEER_DIR ? path.join(process.env.PUPPETEER_DIR, 'x.js') : import.meta.url);
const puppeteer = require('puppeteer-core');

const browser = await puppeteer.launch({
    executablePath: process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe',
    headless: true,
    args: ['--allow-file-access-from-files'],
});
const page = await browser.newPage();
await page.goto(pathToFileURL(html).href, { waitUntil: 'networkidle0' });
await page.evaluate(() => document.fonts.ready);
await page.emulateMediaType('print');

const report = await page.evaluate(() => {
    const pages = Array.from(document.querySelectorAll('section.page'));
    const overflow = [];
    pages.forEach((p, i) => {
        const footer = p.querySelector('.page-footer');
        const limit = footer ? footer.getBoundingClientRect().top : p.getBoundingClientRect().bottom;
        let lowest = 0;
        p.querySelectorAll(':scope > *:not(.page-footer):not(.foto):not(.gradasi)').forEach(el => { lowest = Math.max(lowest, el.getBoundingClientRect().bottom); });
        if (lowest > limit - 2) overflow.push(`halaman ${i + 1}: isi melewati batas ${Math.round(lowest - limit)} px`);
    });
    const broken = Array.from(document.images).filter(img => !img.complete || img.naturalWidth === 0).map(img => img.getAttribute('src'));
    const fonts = Array.from(document.fonts).filter(f => f.status === 'loaded').map(f => f.family);
    return { pages: pages.length, overflow, broken, fonts: [...new Set(fonts)] };
});
console.log(`halaman: ${report.pages}`);
console.log('font termuat:', report.fonts.join(', ') || '(tidak ada)');
console.log('gambar rusak:', report.broken.length ? report.broken : 'tidak ada');
console.log('isi meluap:', report.overflow.length ? report.overflow : 'tidak ada');

await page.pdf({ path: pdf, format: 'A4', printBackground: true, preferCSSPageSize: true, margin: { top: 0, right: 0, bottom: 0, left: 0 } });
console.log('PDF:', pdf);
await browser.close();
if (report.overflow.length || report.broken.length) process.exitCode = 1;
