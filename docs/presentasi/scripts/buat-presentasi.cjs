/*
 * Membuat docs/presentasi/Presentasi_Website_Resort.pptx dari tangkapan layar panduan (docs/panduan/gambar)
 * dan foto resmi resort (public/images/resort). Lihat docs/presentasi/README.md.
 *
 *   DECK_MODULES=<folder node_modules berisi pptxgenjs, sharp, react, react-dom, react-icons> node docs/presentasi/scripts/buat-presentasi.cjs
 *
 * Paket di atas sengaja TIDAK dipasang di package.json website.
 */
const path = require('path');
const fs = require('fs');
const os = require('os');

const MODS = process.env.DECK_MODULES || path.join(__dirname, 'node_modules');
const need = (name) => require(path.join(MODS, name));
const pptxgen = need('pptxgenjs');
const sharp = need('sharp');
const React = need('react');
const ReactDOMServer = need('react-dom/server');
const lu = need('react-icons/lu');

const ROOT = path.resolve(__dirname, '../../..');
const OUT_DIR = path.resolve(__dirname, '..');
const OUT = path.join(OUT_DIR, 'Presentasi_Website_Resort.pptx');
const SHOTS = path.join(ROOT, 'docs/panduan/gambar');
const PHOTOS = path.join(ROOT, 'public/images/resort');
const TMP = path.join(os.tmpdir(), 'lingkung-presentasi');
fs.mkdirSync(TMP, { recursive: true });

// Warna merek (resources/css/app.css)
const THEME = {
    name: 'Lingkung Gunung',
    headFontFace: 'Cambria',
    bodyFontFace: 'Calibri',
    colors: {
        dk1: '26231E', // kabut-900: teks
        lt1: 'FFFFFF',
        dk2: '1F402D', // hutan-800: warna utama
        lt2: 'F5F0E6', // kabut-100: panel
        accent1: '1F402D',
        accent2: '84521F', // emas-700: aksen teks
        accent3: 'D4A43C', // emas-400: aksen di latar gelap
        accent4: '8B5E3C', // tanah-500
        accent5: '3F7D52', // hutan-500
        accent6: '6E6452', // kabut-600: teks redup
        hlink: '265036',
        folHlink: '84521F',
    },
};
const HEX = { border: 'D9CDB8', muted: '6E6452', mist: 'EBD596', lightText: 'EBE3D3' };

// ---------- gambar ----------
// [nama, sumber, potong {top, height} dalam piksel asli (opsional)]
const SHOT_SPECS = {
    p01: ['p01-beranda.jpg'],
    p02: ['p02-beranda-hp.jpg'],
    p03: ['p03-akomodasi.jpg', { top: 0, height: 1180 }],
    p04: ['p04-akomodasi-detail.jpg', { top: 0, height: 1180 }],
    p05: ['p05-aktivitas.jpg'],
    p06: ['p06-rombongan.jpg', { top: 0, height: 902 }],
    p07: ['p07-rancang-paket.jpg'],
    p08: ['p08-pesan.jpg'],
    p10: ['p10-akun-dasbor.jpg', { top: 0, height: 1000 }],
    p12: ['p12-status-booking.jpg', { top: 0, height: 1290 }],
    p14: ['p14-ulasan-form.jpg', { top: 0, height: 1000 }],
    p15: ['p15-ulasan-publik.jpg'],
    p16: ['p16-kontak.jpg'],
    a01: ['a01-login.jpg'],
    a03: ['a03-dasbor.jpg', { top: 0, height: 1150 }],
    a04: ['a04-booking-daftar.jpg', { top: 0, height: 1000 }],
    a07: ['a07-input-manual.jpg', { top: 0, height: 957 }],
    a08: ['a08-kalender.jpg', { top: 0, height: 957 }],
    a09: ['a09-tipe-unit.jpg', { top: 0, height: 1000 }],
    a15: ['a15-pesan-masuk.jpg', { top: 0, height: 800 }],
    a18: ['a18-laporan.jpg', { top: 0, height: 1300 }],
    a20: ['a20-galeri.jpg', { top: 0, height: 917 }],
    a24: ['a24-artikel.jpg', { top: 0, height: 917 }],
};
// Foto dipotong tepat sesuai bidang di slide (lebar x tinggi dalam inci)
const PHOTO_SPECS = {
    sampul: ['glamping-lembah-1600.webp', 6.9, 7.5],
    manfaat: ['deck-lembah-1600.webp', 5.0, 7.5],
    penutup: ['kemping-api-unggun-1600.webp', 6.0, 7.5],
};
const img = {};

async function prepareImages() {
    for (const [key, [file, crop]] of Object.entries(SHOT_SPECS)) {
        const src = path.join(SHOTS, file);
        const meta = await sharp(src).metadata();
        let pipe = sharp(src);
        if (crop) pipe = pipe.extract({ left: 0, top: crop.top, width: meta.width, height: Math.min(crop.height, meta.height - crop.top) });
        const out = path.join(TMP, `${key}.jpg`);
        const info = await pipe.jpeg({ quality: 88 }).toFile(out);
        img[key] = { path: out, w: info.width, h: info.height };
    }
    for (const [key, [file, w, h]] of Object.entries(PHOTO_SPECS)) {
        const out = path.join(TMP, `foto-${key}.jpg`);
        const pxW = 1500;
        const info = await sharp(path.join(PHOTOS, file)).resize({ width: pxW, height: Math.round((pxW * h) / w), fit: 'cover' }).jpeg({ quality: 86 }).toFile(out);
        img[key] = { path: out, w: info.width, h: info.height };
    }
    img.logo = { path: path.join(ROOT, 'public/images/brand/logo-mark-terang.png') };
    const lm = await sharp(img.logo.path).metadata();
    img.logo.w = lm.width;
    img.logo.h = lm.height;
}

// ---------- ikon (Lucide lewat react-icons) ----------
function iconComponent(...names) {
    for (const n of names) if (lu[n]) return lu[n];
    throw new Error('Ikon tidak ditemukan: ' + names.join(', '));
}
const ICONS = {
    search: iconComponent('LuSearch'),
    compass: iconComponent('LuCompass'),
    bed: iconComponent('LuBedDouble', 'LuBed'),
    calendar: iconComponent('LuCalendarCheck', 'LuCalendarCheck2'),
    card: iconComponent('LuCreditCard'),
    ticket: iconComponent('LuTicket'),
    star: iconComponent('LuStar'),
    monitor: iconComponent('LuMonitorSmartphone', 'LuMonitor'),
    settings: iconComponent('LuSettings'),
    users: iconComponent('LuUsers'),
    shield: iconComponent('LuShieldCheck'),
    chart: iconComponent('LuChartColumn', 'LuBarChart3', 'LuChartBar'),
    chat: iconComponent('LuMessageCircle'),
    image: iconComponent('LuImage'),
    mail: iconComponent('LuMail'),
    tag: iconComponent('LuTag'),
    dashboard: iconComponent('LuLayoutDashboard'),
    map: iconComponent('LuMapPin'),
    megaphone: iconComponent('LuMegaphone'),
    help: iconComponent('LuCircleHelp', 'LuHelpCircle'),
    heart: iconComponent('LuHeart'),
    history: iconComponent('LuHistory'),
    file: iconComponent('LuFileText'),
    database: iconComponent('LuDatabaseBackup', 'LuDatabase'),
    list: iconComponent('LuClipboardList'),
    wallet: iconComponent('LuWallet'),
    tent: iconComponent('LuTent'),
    mountain: iconComponent('LuMountain'),
    link: iconComponent('LuLink'),
    phone: iconComponent('LuPhone'),
    check: iconComponent('LuCircleCheck', 'LuCheckCircle2'),
    refresh: iconComponent('LuRefreshCw'),
    bell: iconComponent('LuBellRing', 'LuBell'),
    lock: iconComponent('LuLock'),
    receipt: iconComponent('LuReceipt'),
    edit: iconComponent('LuPencilLine', 'LuPencil'),
    swap: iconComponent('LuArrowRightLeft'),
    user: iconComponent('LuUserRound', 'LuUser'),
    alert: iconComponent('LuCircleAlert', 'LuAlertCircle'),
    info: iconComponent('LuInfo'),
    package: iconComponent('LuPackage'),
    coins: iconComponent('LuCoins'),
    sheet: iconComponent('LuSheet', 'LuFileSpreadsheet'),
    utensils: iconComponent('LuUtensils'),
    send: iconComponent('LuSend'),
    sparkles: iconComponent('LuSparkles'),
    clock: iconComponent('LuClock'),
};
const iconCache = {};
async function iconData(name, color) {
    const key = name + color;
    if (!iconCache[key]) {
        const svg = ReactDOMServer.renderToStaticMarkup(React.createElement(ICONS[name], { color: '#' + color, size: 256 }));
        const buf = await sharp(Buffer.from(svg)).resize(256, 256).png().toBuffer();
        iconCache[key] = 'image/png;base64,' + buf.toString('base64');
    }
    return iconCache[key];
}

// ---------- presentasi ----------
async function build() {
    await prepareImages();
    const icons = {};
    for (const name of Object.keys(ICONS)) {
        icons[name] = { light: await iconData(name, 'FFFFFF'), dark: await iconData(name, '1F402D'), gold: await iconData(name, 'D4A43C') };
    }

    const pres = new pptxgen();
    pres.layout = 'LAYOUT_WIDE'; // 13,333 x 7,5 inci
    pres.title = 'Presentasi Website Lingkung Gunung Adventure Camp Resort Bogor';
    pres.author = 'Lingkung Gunung Adventure Camp Resort Bogor';
    pres.subject = 'Pengenalan website resort dan fitur-fiturnya';
    pres.theme = { headFontFace: THEME.headFontFace, bodyFontFace: THEME.bodyFontFace };
    const C = pres.SchemeColor;

    const footer = (w) => [
        { text: { text: 'Lingkung Gunung Adventure Camp Resort Bogor  ·  Website resort', options: { x: 0.6, y: 7.0, w, h: 0.3, fontSize: 10, color: HEX.muted, margin: 0 } } },
    ];
    pres.defineSlideMaster({
        title: 'KONTEN',
        background: { color: 'FFFFFF' },
        objects: [
            { placeholder: { options: { name: 'label', type: 'body', x: 0.6, y: 0.42, w: 9, h: 0.3, fontSize: 11, bold: true, color: '84521F', margin: 0 }, text: '' } },
            { placeholder: { options: { name: 'title', type: 'title', x: 0.6, y: 0.72, w: 12.1, h: 0.75, fontFace: 'Cambria', fontSize: 30, color: '1F402D', margin: 0, valign: 'top', align: 'left' }, text: '' } },
            ...footer(8),
        ],
        slideNumber: { x: 12.15, y: 7.0, w: 0.6, h: 0.3, fontSize: 10, color: HEX.muted, align: 'right' },
    });
    pres.defineSlideMaster({
        title: 'BELAH',
        background: { color: 'FFFFFF' },
        objects: [
            { placeholder: { options: { name: 'label', type: 'body', x: 0.6, y: 0.42, w: 7, h: 0.3, fontSize: 11, bold: true, color: '84521F', margin: 0 }, text: '' } },
            { placeholder: { options: { name: 'title', type: 'title', x: 0.6, y: 0.72, w: 7.2, h: 0.75, fontFace: 'Cambria', fontSize: 30, color: '1F402D', margin: 0, valign: 'top', align: 'left' }, text: '' } },
            ...footer(6.4),
        ],
        slideNumber: { x: 7.2, y: 7.0, w: 0.6, h: 0.3, fontSize: 10, color: HEX.muted, align: 'right' },
    });
    pres.defineSlideMaster({ title: 'GELAP', background: { color: '1F402D' }, objects: [] });

    // ---- pembantu ----
    const T = (slide, text, opts) => slide.addText(text, { isTextBox: true, margin: 0, fontSize: 15, color: C.text1, valign: 'top', ...opts });
    const head = (slide, label, title, master = 'KONTEN') => {
        slide.addText(label, { placeholder: 'label' });
        slide.addText(title, { placeholder: 'title' });
    };
    const newSlide = (section, master = 'KONTEN') => pres.addSlide({ masterName: master, sectionTitle: section });

    // Tangkapan layar di dalam bidang (contain), rata atas & tengah horizontal, dengan bingkai tipis + bayangan lembut
    const shot = (slide, key, box, opts = {}) => {
        const im = img[key];
        let w = box.w;
        let h = (w * im.h) / im.w;
        if (h > box.h) {
            h = box.h;
            w = (h * im.w) / im.h;
        }
        const x = box.x + (opts.align === 'left' ? 0 : (box.w - w) / 2);
        const y = box.y;
        slide.addShape(pres.shapes.RECTANGLE, {
            x, y, w, h, fill: { color: 'FFFFFF' }, line: { color: HEX.border, width: 0.75 },
            shadow: { type: 'outer', color: '000000', opacity: 0.16, blur: 10, offset: 3, angle: 90 }, objectName: `bingkai-${key}`,
        });
        slide.addImage({ path: im.path, x, y, w, h, altText: opts.alt || '', objectName: `gambar-${key}` });
        if (opts.caption) T(slide, opts.caption, { x, y: y + h + 0.1, w, h: 0.3, fontSize: 11, color: C.accent6, italic: true });
        return { x, y, w, h };
    };
    // Kotak ikon persegi (motif: sudut tajam seperti website)
    const tile = (slide, name, x, y, s = 0.5, variant = 'solid') => {
        const solid = variant === 'solid';
        slide.addShape(pres.shapes.RECTANGLE, { x, y, w: s, h: s, fill: { color: solid ? C.accent1 : C.background2 }, line: { type: 'none' }, objectName: `ikon-${name}` });
        const p = s * 0.24;
        slide.addImage({ data: solid ? icons[name].light : icons[name].dark, x: x + p, y: y + p, w: s - 2 * p, h: s - 2 * p, altText: '' });
    };
    const numTile = (slide, n, x, y, s = 0.5) => {
        slide.addShape(pres.shapes.RECTANGLE, { x, y, w: s, h: s, fill: { color: C.accent1 }, line: { type: 'none' } });
        T(slide, String(n), { x, y, w: s, h: s, fontFace: 'Cambria', fontSize: 17, color: C.background1, align: 'center', valign: 'middle' });
    };
    // Baris ikon + teks
    const iconRows = (slide, rows, x, y, w, step, opts = {}) => {
        rows.forEach(([icon, text], i) => {
            const yy = y + i * step;
            tile(slide, icon, x, yy, 0.46, opts.variant);
            T(slide, text, { x: x + 0.66, y: yy - 0.02, w: w - 0.66, h: step - 0.08, fontSize: opts.size || 15 });
        });
    };
    const panel = (slide, x, y, w, h) => slide.addShape(pres.shapes.RECTANGLE, { x, y, w, h, fill: { color: C.background2 }, line: { type: 'none' } });
    const arrow = (slide, x1, y1, x2, y2, both = false) =>
        slide.addShape(pres.shapes.LINE, {
            x: Math.min(x1, x2), y: Math.min(y1, y2), w: Math.abs(x2 - x1) || 0.001, h: Math.abs(y2 - y1) || 0.001,
            flipV: y2 < y1, line: { color: '8F826B', width: 1.25, endArrowType: 'triangle', beginArrowType: both ? 'triangle' : undefined },
        });

    // =====================================================================
    // 1. Sampul
    pres.addSection({ title: 'Pembuka' });
    let s = newSlide('Pembuka', 'GELAP');
    s.addImage({ path: img.sampul.path, x: 6.433, y: 0, w: 6.9, h: 7.5, altText: 'Glamping Imah Lembah di Lingkung Gunung dengan latar perbukitan' });
    s.addImage({ path: img.logo.path, x: 0.75, y: 0.75, w: 0.62, h: (0.62 * img.logo.h) / img.logo.w, altText: 'Logo Lingkung Gunung' });
    T(s, 'PRESENTASI WEBSITE', { x: 0.75, y: 2.1, w: 5.3, h: 0.3, fontSize: 12, bold: true, color: C.accent3, charSpacing: 3 });
    T(s, 'Website Lingkung Gunung Adventure Camp Resort Bogor', { x: 0.75, y: 2.5, w: 5.3, h: 2.2, fontFace: 'Cambria', fontSize: 38, color: C.background1 });
    T(s, 'Mengenal website resort: cara tamu menjelajah dan memesan, serta cara tim resort mengelola semuanya.', {
        x: 0.75, y: 4.85, w: 5.1, h: 1.1, fontSize: 17, color: HEX.lightText,
    });
    T(s, 'Oktober 2026', { x: 0.75, y: 6.75, w: 3, h: 0.3, fontSize: 12, color: HEX.lightText });
    s.addNotes('Pembuka: presentasi ini memperkenalkan website resort dari sudut pandang tamu dan tim resort.');

    // 2. Gambaran umum
    s = newSlide('Pembuka');
    head(s, 'GAMBARAN UMUM', 'Satu website, tiga peran');
    T(s, 'Website ini menjadi etalase resort, tempat tamu memesan dan membayar, sekaligus ruang kerja tim resort.', { x: 0.6, y: 1.62, w: 11.5, h: 0.5, fontSize: 17 });
    [
        ['mountain', 'Etalase resort', 'Informasi akomodasi, aktivitas, paket rombongan, kuliner, galeri, dan lokasi, lengkap dengan foto asli resort.'],
        ['calendar', 'Pemesanan & pembayaran', 'Tamu mengecek ketersediaan, memesan, dan membayar langsung di website, lalu menerima e-ticket.'],
        ['dashboard', 'Halaman admin', 'Tim resort mengelola booking, harga, konten, ulasan, dan laporan dari satu tempat.'],
    ].forEach(([icon, title, desc], i) => {
        const x = 0.6 + i * 4.15;
        panel(s, x, 2.5, 3.85, 3.55);
        tile(s, icon, x + 0.35, 2.85, 0.7);
        T(s, `0${i + 1}`, { x: x + 2.9, y: 2.9, w: 0.6, h: 0.4, fontFace: 'Cambria', fontSize: 16, color: C.accent2, align: 'right' });
        T(s, title, { x: x + 0.35, y: 3.85, w: 3.2, h: 0.45, fontFace: 'Cambria', fontSize: 20, color: C.accent1 });
        T(s, desc, { x: x + 0.35, y: 4.4, w: 3.2, h: 1.5, fontSize: 15 });
    });
    T(s, [
        { text: 'Digunakan oleh  ', options: { bold: true, color: C.accent1 } },
        { text: 'tamu perorangan & keluarga  ·  rombongan, sekolah & perusahaan  ·  tim resort' },
    ], { x: 0.6, y: 6.35, w: 12, h: 0.35, fontSize: 14, color: C.accent6 });

    // 3. Perjalanan tamu
    s = newSlide('Pembuka');
    head(s, 'ALUR TAMU', 'Perjalanan tamu dari awal hingga akhir');
    const journey = [
        ['search', 'Menemukan', 'Membuka website dari HP atau laptop'],
        ['compass', 'Menjelajah', 'Melihat kamar, aktivitas, dan paket'],
        ['calendar', 'Memesan', 'Memilih tanggal dan akomodasi'],
        ['card', 'Membayar', 'DP atau lunas secara online'],
        ['ticket', 'Menginap', 'Menunjukkan e-ticket saat check-in'],
        ['star', 'Mengulas', 'Berbagi pengalaman setelah pulang'],
    ];
    const slotW = 12.13 / journey.length;
    s.addShape(pres.shapes.LINE, { x: 0.6 + slotW / 2, y: 2.3, w: slotW * 5, h: 0, line: { color: HEX.border, width: 1.5 } });
    journey.forEach(([icon, label, desc], i) => {
        const cx = 0.6 + slotW * i + slotW / 2;
        tile(s, icon, cx - 0.4, 1.9, 0.8);
        T(s, `LANGKAH ${i + 1}`, { x: cx - 0.95, y: 2.9, w: 1.9, h: 0.25, fontSize: 10, bold: true, color: C.accent2, align: 'center', charSpacing: 1 });
        T(s, label, { x: cx - 0.95, y: 3.18, w: 1.9, h: 0.4, fontFace: 'Cambria', fontSize: 18, color: C.accent1, align: 'center' });
        T(s, desc, { x: cx - 0.92, y: 3.62, w: 1.84, h: 0.8, fontSize: 14, color: C.text1, align: 'center' });
    });
    panel(s, 0.6, 4.85, 12.13, 1.6);
    tile(s, 'users', 0.95, 5.2, 0.7);
    T(s, 'Untuk rombongan', { x: 1.95, y: 5.15, w: 10, h: 0.4, fontFace: 'Cambria', fontSize: 18, color: C.accent1 });
    T(s, 'Calon klien merancang paket sendiri di website, proposal PDF terkirim otomatis ke email, lalu tim sales menindaklanjuti hingga menjadi booking.', {
        x: 1.95, y: 5.58, w: 10.4, h: 0.75, fontSize: 15,
    });

    // =====================================================================
    pres.addSection({ title: 'Untuk tamu' });
    // 4. Menjelajah
    s = newSlide('Untuk tamu');
    head(s, 'UNTUK TAMU', 'Menjelajah resort dari HP maupun laptop');
    shot(s, 'p01', { x: 0.6, y: 1.75, w: 6.3, h: 4.2 }, { caption: 'Beranda di laptop', alt: 'Tangkapan layar beranda website' });
    shot(s, 'p02', { x: 7.2, y: 1.75, w: 2.2, h: 4.65 }, { caption: 'Tampilan di HP', alt: 'Tangkapan layar beranda di HP' });
    T(s, 'Mudah dijelajahi', { x: 9.8, y: 1.75, w: 2.95, h: 0.4, fontFace: 'Cambria', fontSize: 19, color: C.accent1 });
    T(s, [
        { text: 'Menu: Menginap, Acara & Rombongan, Aktivitas, Kuliner, Galeri, Lokasi & Kontak', options: { bullet: { code: '25AA' }, breakLine: true } },
        { text: 'Foto asli resort di setiap halaman', options: { bullet: { code: '25AA' }, breakLine: true } },
        { text: 'Cek ketersediaan langsung dari beranda', options: { bullet: { code: '25AA' }, breakLine: true } },
        { text: 'Di HP, tombol Pesan dan WhatsApp selalu terlihat', options: { bullet: { code: '25AA' }, breakLine: true } },
        { text: 'Artikel, FAQ, serta info rute dan peta', options: { bullet: { code: '25AA' } } },
    ], { x: 9.8, y: 2.25, w: 2.95, h: 4.3, fontSize: 14, paraSpaceAfter: 8 });

    // 5. Akomodasi
    s = newSlide('Untuk tamu');
    head(s, 'UNTUK TAMU', 'Akomodasi lengkap dengan foto, fasilitas, dan harga');
    shot(s, 'p03', { x: 0.6, y: 1.7, w: 5.9, h: 3.6 }, { caption: 'Daftar akomodasi per kelompok', alt: 'Tangkapan layar daftar akomodasi' });
    shot(s, 'p04', { x: 6.83, y: 1.7, w: 5.9, h: 3.6 }, { caption: 'Halaman detail akomodasi', alt: 'Tangkapan layar detail akomodasi' });
    [
        ['tent', 'Kamar & glamping, tenda, area camping, dan campervan'],
        ['info', 'Foto, kapasitas, fasilitas, serta harga weekday & weekend'],
        ['heart', 'Tamu bisa menyimpan akomodasi favorit ke akunnya'],
    ].forEach(([icon, text], i) => {
        const x = 0.6 + i * 4.15;
        tile(s, icon, x, 5.95, 0.46, 'soft');
        T(s, text, { x: x + 0.66, y: 5.93, w: 3.25, h: 0.8, fontSize: 15 });
    });

    // 6. Aktivitas & paket
    s = newSlide('Untuk tamu');
    head(s, 'UNTUK TAMU', 'Aktivitas seru dan paket untuk rombongan');
    shot(s, 'p05', { x: 0.6, y: 1.7, w: 5.9, h: 2.8 }, { alt: 'Tangkapan layar daftar aktivitas dan harga' });
    shot(s, 'p06', { x: 6.83, y: 1.7, w: 5.9, h: 2.8 }, { alt: 'Tangkapan layar paket fullboard per orang' });
    [
        ['Aktivitas', 'Trekking, rafting, paintball, offroad, outbound, team building, hingga Sanggar Alam. Harga per orang dan jumlah minimal peserta tertulis jelas.'],
        ['Paket rombongan', 'Paket fullboard per orang dalam beberapa varian: akomodasi, makan sesuai set menu, dan fasilitas sudah termasuk. Pilihan set menu tampil di halaman Kuliner.'],
    ].forEach(([title, desc], i) => {
        const x = 0.6 + i * 6.23;
        T(s, title, { x, y: 4.85, w: 5.9, h: 0.4, fontFace: 'Cambria', fontSize: 19, color: C.accent1 });
        T(s, desc, { x, y: 5.32, w: 5.9, h: 1.3, fontSize: 15 });
    });

    // 7. Rancang paket
    s = newSlide('Untuk tamu');
    head(s, 'UNTUK ROMBONGAN', 'Rombongan bisa merancang paket sendiri');
    shot(s, 'p07', { x: 0.6, y: 1.75, w: 7.0, h: 4.3 }, { caption: 'Halaman Rancang Paket Rombongan', alt: 'Tangkapan layar halaman rancang paket rombongan' });
    ['Pilih paket dan jumlah peserta', 'Atur menu untuk setiap hari', 'Tambah aktivitas dan item tambahan', 'Isi data instansi, lalu kirim'].forEach((text, i) => {
        const y = 1.8 + i * 0.8;
        numTile(s, i + 1, 8.05, y);
        T(s, text, { x: 8.75, y: y + 0.08, w: 4.0, h: 0.4, fontSize: 16 });
    });
    panel(s, 8.05, 5.15, 4.68, 1.3);
    T(s, 'Estimasi biaya langsung terlihat saat memilih. Setelah dikirim, proposal PDF masuk ke email klien dan tim sales menindaklanjuti.', {
        x: 8.3, y: 5.3, w: 4.2, h: 1.05, fontSize: 15,
    });

    // 8. Pemesanan
    s = newSlide('Untuk tamu');
    head(s, 'UNTUK TAMU', 'Memesan penginapan dalam empat langkah');
    [
        ['Tanggal & tamu', 'Pilih tanggal menginap dan jumlah tamu'],
        ['Akomodasi', 'Hanya unit yang masih tersedia yang bisa dipilih'],
        ['Data pemesan', 'Masuk akun, isi kontak, lalu periksa ringkasan pesanan'],
        ['Pembayaran', 'Unit ditahan 60 menit selama menunggu pembayaran'],
    ].forEach(([title, desc], i) => {
        const y = 1.8 + i * 0.98;
        numTile(s, i + 1, 0.6, y);
        T(s, title, { x: 1.3, y: y - 0.02, w: 4.5, h: 0.35, fontFace: 'Cambria', fontSize: 17, color: C.accent1 });
        T(s, desc, { x: 1.3, y: y + 0.36, w: 4.6, h: 0.5, fontSize: 15 });
    });
    shot(s, 'p08', { x: 6.3, y: 1.75, w: 6.43, h: 3.8 }, { caption: 'Halaman pemesanan', alt: 'Tangkapan layar halaman pemesanan' });
    panel(s, 0.6, 6.05, 12.13, 0.75);
    tile(s, 'coins', 0.8, 6.19, 0.46);
    T(s, 'Harga dihitung otomatis per malam (weekday, weekend, atau musim ramai), termasuk potongan bila tamu memakai kode promo.', {
        x: 1.45, y: 6.2, w: 11.1, h: 0.5, fontSize: 15,
    });

    // 9. Pembayaran
    s = newSlide('Untuk tamu');
    head(s, 'UNTUK TAMU', 'Pembayaran yang jelas dan tercatat otomatis');
    shot(s, 'p12', { x: 0.6, y: 1.75, w: 6.2, h: 3.95 }, { caption: 'Halaman status booking: batas waktu, tombol bayar, tahapan', alt: 'Tangkapan layar halaman status booking' });
    iconRows(s, [
        ['wallet', 'Pilih DP (uang muka 25%) atau langsung lunas'],
        ['card', 'Bayar online lewat Midtrans dengan metode pembayaran yang diaktifkan resort'],
        ['refresh', 'Status booking berubah otomatis setelah pembayaran diterima'],
        ['ticket', 'E-ticket ber-QR dikirim ke email dan bisa diunduh kapan saja'],
        ['bell', 'Pengingat pelunasan lewat email H-7 dan H-4; lunas paling lambat H-3'],
    ], 7.25, 1.8, 5.48, 0.8);
    T(s, 'Besaran DP, lama penahanan unit, dan batas pelunasan bisa diubah di Pengaturan.', { x: 7.25, y: 6.05, w: 5.48, h: 0.6, fontSize: 14, color: C.accent6, italic: true });

    // 10. Akun pelanggan
    s = newSlide('Untuk tamu');
    head(s, 'UNTUK TAMU', 'Akun pelanggan: semua pesanan di satu tempat');
    iconRows(s, [
        ['user', 'Daftar dan masuk dengan email; lupa password bisa diatur ulang sendiri'],
        ['list', 'Status dan tahapan setiap booking dalam satu halaman'],
        ['alert', 'Bagian "Perlu tindakan" menampilkan tagihan yang harus dibayar'],
        ['ticket', 'Unduh e-ticket, simpan akomodasi favorit, dan ubah profil'],
        ['link', 'Booking lama bisa ditautkan dengan kode booking dan nomor WhatsApp'],
    ], 0.6, 1.8, 5.6, 0.82);
    panel(s, 0.6, 6.0, 5.6, 0.75);
    tile(s, 'shield', 0.78, 6.145, 0.46);
    T(s, 'Setiap booking hanya bisa dibuka oleh pemilik akunnya.', { x: 1.42, y: 6.2, w: 4.7, h: 0.45, fontSize: 15 });
    shot(s, 'p10', { x: 6.6, y: 1.75, w: 6.13, h: 4.4 }, { caption: 'Dasbor akun pelanggan', alt: 'Tangkapan layar dasbor akun pelanggan' });

    // 11. Ulasan
    s = newSlide('Untuk tamu');
    head(s, 'UNTUK TAMU', 'Ulasan dari tamu yang benar-benar menginap');
    const flow = [['check', 'Selesai menginap'], ['star', 'Menulis ulasan'], ['shield', 'Diperiksa tim'], ['monitor', 'Tayang di website']];
    flow.forEach(([icon, label], i) => {
        const x = 0.6 + i * 3.1;
        tile(s, icon, x, 1.75, 0.5);
        T(s, label, { x: x + 0.65, y: 1.83, w: 2.0, h: 0.35, fontSize: 16, color: C.accent1, bold: true });
        if (i < flow.length - 1) arrow(s, x + 2.55, 2.0, x + 3.0, 2.0);
    });
    shot(s, 'p14', { x: 0.6, y: 2.65, w: 5.0, h: 3.9 }, { align: 'left', caption: 'Form ulasan di akun tamu', alt: 'Tangkapan layar form ulasan' });
    shot(s, 'p15', { x: 6.0, y: 2.65, w: 6.73, h: 2.9 }, { caption: 'Halaman Ulasan di website', alt: 'Tangkapan layar halaman ulasan tamu' });
    T(s, 'Bintang 1–5, komentar, dan hingga 4 foto. Rating tampil di halaman akomodasi, aktivitas, paket rombongan, dan halaman Ulasan.', {
        x: 6.0, y: 5.95, w: 6.73, h: 0.8, fontSize: 15,
    });

    // 12. Kontak & promo
    s = newSlide('Untuk tamu');
    head(s, 'UNTUK TAMU', 'Kontak, promo, dan informasi pendukung');
    shot(s, 'p16', { x: 0.6, y: 1.75, w: 6.2, h: 3.95 }, { caption: 'Halaman Lokasi & Kontak dengan form pertanyaan', alt: 'Tangkapan layar halaman kontak' });
    [
        ['mail', 'Form pertanyaan', 'Masuk ke admin dan email resort'],
        ['phone', 'WhatsApp', 'Chat langsung dari website'],
        ['tag', 'Kode promo', 'Potongan harga saat memesan'],
        ['megaphone', 'Banner penawaran', 'Promo tampil di beranda'],
        ['help', 'FAQ & info', 'Cara bayar, syarat, dan privasi'],
        ['map', 'Rute & akses', 'Petunjuk arah dan peta'],
    ].forEach(([icon, title, desc], i) => {
        const x = 7.2 + (i % 2) * 2.83;
        const y = 1.75 + Math.floor(i / 2) * 1.6;
        panel(s, x, y, 2.7, 1.42);
        tile(s, icon, x + 0.2, y + 0.2, 0.42);
        T(s, title, { x: x + 0.75, y: y + 0.24, w: 1.9, h: 0.35, fontSize: 15, bold: true, color: C.accent1 });
        T(s, desc, { x: x + 0.2, y: y + 0.75, w: 2.35, h: 0.6, fontSize: 14 });
    });

    // =====================================================================
    pres.addSection({ title: 'Untuk tim resort' });
    // 13. Halaman admin
    s = newSlide('Untuk tim resort');
    head(s, 'UNTUK TIM RESORT', 'Halaman admin: ruang kerja tim resort');
    shot(s, 'a01', { x: 0.6, y: 1.75, w: 5.6, h: 3.6 }, { caption: 'Masuk admin dengan verifikasi dua langkah', alt: 'Tangkapan layar halaman masuk admin' });
    T(s, 'Halaman khusus staf untuk mengurus website tanpa mengubah kode program. Masuk dengan email, password, dan kode dari aplikasi di HP.', {
        x: 6.65, y: 1.75, w: 6.08, h: 0.95, fontSize: 15,
    });
    T(s, 'Menu dikelompokkan per pekerjaan', { x: 6.65, y: 2.85, w: 6, h: 0.35, fontFace: 'Cambria', fontSize: 17, color: C.accent1 });
    [
        ['Operasional Harian', 'booking, kalender, penawaran, pesan, ulasan, pelanggan'],
        ['Akomodasi & Harga', 'tipe unit, tanggal khusus, kode promo'],
        ['Paket Rombongan', 'paket, aktivitas, set menu, item tambahan'],
        ['Konten Website', 'halaman, galeri, artikel, FAQ, banner, program'],
        ['Laporan & Pengaturan', 'laporan, pengaturan, pengguna, catatan aktivitas'],
    ].forEach(([group, items], i) => {
        const y = 3.32 + i * 0.68;
        s.addShape(pres.shapes.RECTANGLE, { x: 6.65, y: y + 0.08, w: 0.16, h: 0.16, fill: { color: C.accent3 }, line: { type: 'none' } });
        T(s, [{ text: group, options: { bold: true, color: C.accent1, breakLine: true } }, { text: items, options: { color: C.text1 } }], {
            x: 6.98, y, w: 5.75, h: 0.62, fontSize: 14,
        });
    });

    // 14. Dasbor
    s = newSlide('Untuk tim resort');
    head(s, 'UNTUK TIM RESORT', 'Dasbor: hal yang perlu diurus hari ini');
    shot(s, 'a03', { x: 0.6, y: 1.75, w: 6.0, h: 4.45 }, { caption: 'Dasbor admin', alt: 'Tangkapan layar dasbor admin' });
    [
        ['alert', 'Perlu tindakan', 'Tamu tiba ≤ 3 hari tapi belum lunas, booking menunggu pembayaran, pesan dan penawaran baru, ulasan yang menunggu persetujuan.'],
        ['dashboard', 'Ringkasan', 'Check-in dan check-out hari ini, okupansi malam ini, uang diterima, serta sisa tagihan booking DP.'],
        ['calendar', 'Kedatangan 7 hari ke depan', 'Daftar tamu yang akan datang beserta status pembayarannya.'],
    ].forEach(([icon, title, desc], i) => {
        const y = 1.8 + i * 1.55;
        tile(s, icon, 7.05, y, 0.5);
        T(s, title, { x: 7.75, y: y + 0.04, w: 5, h: 0.38, fontFace: 'Cambria', fontSize: 18, color: C.accent1 });
        T(s, desc, { x: 7.75, y: y + 0.47, w: 4.98, h: 1.0, fontSize: 15 });
    });

    // 15. Booking & pembayaran
    s = newSlide('Untuk tim resort');
    head(s, 'UNTUK TIM RESORT', 'Mengelola booking dan pembayaran');
    shot(s, 'a04', { x: 0.6, y: 1.75, w: 6.3, h: 4.1 }, { caption: 'Daftar booking dengan tab status', alt: 'Tangkapan layar daftar booking di admin' });
    iconRows(s, [
        ['list', 'Daftar per status: menunggu bayar, menunggu pelunasan, check-in hari ini, mendatang'],
        ['file', 'Detail lengkap: data tamu, rincian harga, riwayat pembayaran'],
        ['receipt', 'Catat transfer manual beserta foto bukti transfer'],
        ['check', 'Check-in tamu, atau batalkan bila perlu (sesuai hak akses)'],
        ['lock', 'Booking hanya berubah lewat tombol aksi, dan setiap perubahan tercatat'],
    ], 7.3, 1.8, 5.43, 0.9);

    // 16. Kalender & reservasi manual
    s = newSlide('Untuk tim resort');
    head(s, 'UNTUK TIM RESORT', 'Kalender okupansi dan reservasi manual');
    shot(s, 'a08', { x: 0.6, y: 1.7, w: 5.9, h: 3.6 }, { alt: 'Tangkapan layar kalender okupansi' });
    shot(s, 'a07', { x: 6.83, y: 1.7, w: 5.9, h: 3.6 }, { alt: 'Tangkapan layar form reservasi manual' });
    [
        ['Kalender okupansi', 'Sisa stok setiap tipe unit per tanggal dalam satu layar. Tanggal bisa ditutup atau dibuka kembali.'],
        ['Reservasi manual', 'Tamu walk-in atau pemesan lewat telepon dicatat staf. Stok website ikut berkurang, jadi tidak dobel.'],
    ].forEach(([title, desc], i) => {
        const x = 0.6 + i * 6.23;
        T(s, title, { x, y: 5.5, w: 5.9, h: 0.38, fontFace: 'Cambria', fontSize: 18, color: C.accent1 });
        T(s, desc, { x, y: 5.92, w: 5.9, h: 0.85, fontSize: 15 });
    });

    // 17. Penawaran & pesan masuk
    s = newSlide('Untuk tim resort');
    head(s, 'UNTUK TIM RESORT', 'Penawaran rombongan dan pesan masuk');
    T(s, 'Penawaran rombongan', { x: 0.6, y: 1.75, w: 5.9, h: 0.4, fontFace: 'Cambria', fontSize: 19, color: C.accent1 });
    T(s, 'Paket yang dirancang calon klien masuk sebagai penawaran, lengkap dengan proposal PDF.', { x: 0.6, y: 2.2, w: 5.9, h: 0.7, fontSize: 15 });
    ['Baru', 'Dihubungi', 'Deal', 'Batal'].forEach((label, i) => {
        const x = 0.6 + i * 1.4;
        s.addShape(pres.shapes.RECTANGLE, { x, y: 3.0, w: 1.15, h: 0.42, fill: { color: i === 2 ? C.accent1 : C.background2 }, line: { type: 'none' } });
        T(s, label, { x, y: 3.0, w: 1.15, h: 0.42, fontSize: 14, align: 'center', valign: 'middle', color: i === 2 ? C.background1 : C.accent1, bold: true });
    });
    iconRows(s, [
        ['chat', 'Chat klien lewat WhatsApp sekali klik'],
        ['edit', 'Item bisa disesuaikan; total dan proposal diperbarui otomatis'],
        ['swap', 'Penawaran yang deal diubah menjadi booking, lalu dikirimi link pembayaran'],
    ], 0.6, 3.8, 5.9, 0.88);
    shot(s, 'a15', { x: 6.83, y: 1.75, w: 5.9, h: 3.1 }, { caption: 'Pesan masuk dari form kontak', alt: 'Tangkapan layar pesan masuk di admin' });
    T(s, 'Setiap pertanyaan dari website tercatat dengan status (Baru, Sedang dibalas, Selesai, Spam) dan catatan internal untuk tim.', {
        x: 6.83, y: 5.5, w: 5.9, h: 0.9, fontSize: 15,
    });

    // 18. Harga
    s = newSlide('Untuk tim resort');
    head(s, 'UNTUK TIM RESORT', 'Harga dan penawaran diatur dari admin');
    shot(s, 'a09', { x: 0.6, y: 1.75, w: 6.3, h: 4.1 }, { caption: 'Tipe unit & harga', alt: 'Tangkapan layar daftar tipe unit dan harga' });
    iconRows(s, [
        ['bed', 'Tipe unit: harga weekday & weekend, jumlah unit, kapasitas, foto, fasilitas'],
        ['calendar', 'Tanggal khusus: hari libur atau musim ramai bisa memakai tarif berbeda'],
        ['tag', 'Kode promo: besar potongan, masa berlaku, dan kuota pemakaian'],
        ['package', 'Paket rombongan, aktivitas, set menu, dan item tambahan'],
    ], 7.3, 1.8, 5.43, 0.88);
    panel(s, 7.3, 5.5, 5.43, 0.95);
    T(s, 'Perubahan langsung berlaku di website, dan setiap total pesanan dihitung ulang otomatis oleh sistem.', { x: 7.55, y: 5.65, w: 4.95, h: 0.7, fontSize: 15 });

    // 19. Konten
    s = newSlide('Untuk tim resort');
    head(s, 'UNTUK TIM RESORT', 'Konten website diperbarui tanpa menyentuh kode');
    shot(s, 'a20', { x: 0.6, y: 1.7, w: 5.9, h: 3.45 }, { caption: 'Galeri foto', alt: 'Tangkapan layar pengelolaan galeri' });
    shot(s, 'a24', { x: 6.83, y: 1.7, w: 5.9, h: 3.45 }, { caption: 'Artikel: terbit, terjadwal, atau draf', alt: 'Tangkapan layar pengelolaan artikel' });
    ['Halaman', 'Galeri', 'Artikel', 'FAQ', 'Banner promo', 'Program'].forEach((label, i) => {
        const x = 0.6 + i * 2.045;
        s.addShape(pres.shapes.RECTANGLE, { x, y: 5.68, w: 1.88, h: 0.42, fill: { color: C.background2 }, line: { type: 'none' } });
        T(s, label, { x, y: 5.68, w: 1.88, h: 0.42, fontSize: 14, bold: true, color: C.accent1, align: 'center', valign: 'middle' });
    });
    T(s, 'Foto yang diunggah otomatis diperkecil dan dikompres agar website tetap cepat dibuka di HP.', { x: 0.6, y: 6.28, w: 12.1, h: 0.4, fontSize: 15 });

    // 20. Laporan
    s = newSlide('Untuk tim resort');
    head(s, 'UNTUK TIM RESORT', 'Laporan untuk melihat perkembangan resort');
    shot(s, 'a18', { x: 0.6, y: 1.75, w: 5.4, h: 4.5 }, { caption: 'Halaman Laporan', alt: 'Tangkapan layar halaman laporan' });
    iconRows(s, [
        ['coins', 'Uang diterima, dihitung dari tanggal pembayaran diverifikasi'],
        ['list', 'Jumlah dan nilai booking per periode'],
        ['bed', 'Tingkat okupansi'],
        ['chart', 'Grafik tren harian booking dan uang diterima'],
        ['sheet', 'Ekspor ke Excel: ringkasan, pembayaran, booking, okupansi'],
    ], 6.5, 1.8, 6.23, 0.8);
    panel(s, 6.5, 5.95, 6.23, 0.75);
    tile(s, 'lock', 6.68, 6.095, 0.46);
    T(s, 'Laporan hanya bisa dibuka oleh Owner dan Admin.', { x: 7.32, y: 6.15, w: 5.3, h: 0.45, fontSize: 15 });

    // 21. Fitur lain
    s = newSlide('Untuk tim resort');
    head(s, 'UNTUK TIM RESORT', 'Fitur pengelolaan lainnya');
    [
        ['star', 'Moderasi ulasan', 'Setujui, tolak, atau sembunyikan ulasan sebelum tampil di website.'],
        ['users', 'Data pelanggan', 'Kontak dan riwayat booking setiap pelanggan tersimpan rapi.'],
        ['shield', 'Pengguna & hak akses', 'Owner, Admin, Sales, dan Front Office punya izin yang berbeda.'],
        ['settings', 'Pengaturan umum', 'Besaran DP, lama penahanan unit, malam weekend, kontak, dan rekening.'],
        ['history', 'Catatan aktivitas', 'Mencatat siapa mengubah data apa, dan kapan.'],
        ['database', 'Keamanan & backup', 'Verifikasi dua langkah untuk staf dan backup database otomatis setiap hari.'],
    ].forEach(([icon, title, desc], i) => {
        const x = 0.6 + (i % 3) * 4.15;
        const y = 1.75 + Math.floor(i / 3) * 2.5;
        panel(s, x, y, 3.85, 2.25);
        tile(s, icon, x + 0.3, y + 0.3, 0.55);
        T(s, title, { x: x + 1.05, y: y + 0.38, w: 2.65, h: 0.4, fontFace: 'Cambria', fontSize: 18, color: C.accent1 });
        T(s, desc, { x: x + 0.3, y: y + 1.05, w: 3.3, h: 1.1, fontSize: 15 });
    });

    // =====================================================================
    pres.addSection({ title: 'Gambaran utuh' });
    // 22. Keterhubungan
    s = newSlide('Gambaran utuh');
    head(s, 'GAMBARAN UTUH', 'Bagaimana semuanya saling terhubung');
    // Tamu
    panel(s, 0.6, 1.95, 2.75, 3.6);
    tile(s, 'user', 0.9, 2.25, 0.6);
    T(s, 'Tamu', { x: 0.9, y: 3.0, w: 2.3, h: 0.4, fontFace: 'Cambria', fontSize: 20, color: C.accent1 });
    T(s, 'Menjelajah, memesan, membayar, merancang paket, dan mengulas', { x: 0.9, y: 3.5, w: 2.25, h: 1.8, fontSize: 15 });
    // Website (tengah)
    s.addShape(pres.shapes.RECTANGLE, { x: 4.05, y: 1.75, w: 5.25, h: 4.0, fill: { color: 'FFFFFF' }, line: { color: HEX.border, width: 1 } });
    T(s, 'WEBSITE LINGKUNG GUNUNG', { x: 4.3, y: 1.95, w: 4.8, h: 0.3, fontSize: 12, bold: true, color: C.accent2, charSpacing: 2 });
    [
        ['mountain', 'Informasi & foto'],
        ['calendar', 'Pemesanan & stok harian'],
        ['user', 'Akun & ulasan'],
        ['users', 'Rancang paket & proposal'],
    ].forEach(([icon, label], i) => {
        const x = 4.3 + (i % 2) * 2.45;
        const y = 2.45 + Math.floor(i / 2) * 1.55;
        panel(s, x, y, 2.3, 1.35);
        tile(s, icon, x + 0.2, y + 0.2, 0.42);
        T(s, label, { x: x + 0.2, y: y + 0.72, w: 1.95, h: 0.55, fontSize: 14, bold: true, color: C.accent1 });
    });
    // Tim resort
    s.addShape(pres.shapes.RECTANGLE, { x: 9.98, y: 1.95, w: 2.75, h: 3.6, fill: { color: C.accent1 }, line: { type: 'none' } });
    s.addImage({ data: icons.dashboard.gold, x: 10.28, y: 2.25, w: 0.55, h: 0.55, altText: '' });
    T(s, 'Tim resort', { x: 10.28, y: 3.0, w: 2.3, h: 0.4, fontFace: 'Cambria', fontSize: 20, color: C.background1 });
    T(s, 'Mengelola booking, harga, konten, ulasan, dan laporan lewat halaman admin', { x: 10.28, y: 3.5, w: 2.25, h: 1.8, fontSize: 15, color: C.background1 });
    arrow(s, 3.4, 3.75, 4.0, 3.75, true);
    arrow(s, 9.35, 3.75, 9.93, 3.75, true);
    // Layanan pendukung
    [['card', 'Midtrans', 'pembayaran online'], ['mail', 'Email otomatis', 'e-ticket, pengingat, proposal']].forEach(([icon, title, desc], i) => {
        const x = 4.05 + i * 2.7;
        panel(s, x, 6.0, 2.55, 0.8);
        tile(s, icon, x + 0.15, 6.17, 0.46, 'solid');
        T(s, [{ text: title, options: { bold: true, color: C.accent1, breakLine: true } }, { text: desc }], { x: x + 0.75, y: 6.07, w: 1.75, h: 0.68, fontSize: 13 });
    });
    arrow(s, 6.675, 5.78, 6.675, 5.97, true);
    T(s, 'Berjalan otomatis: unit yang tidak dibayar dilepas, status pembayaran dicek berkala, pengingat dan backup terjadwal.', {
        x: 9.98, y: 5.85, w: 2.75, h: 1.0, fontSize: 13, color: C.accent6, italic: true,
    });

    // 23. Manfaat
    s = newSlide('Gambaran utuh', 'BELAH');
    head(s, 'GAMBARAN UTUH', 'Manfaat bagi resort');
    s.addImage({ path: img.manfaat.path, x: 8.333, y: 0, w: 5.0, h: 7.5, altText: 'Dek pandang menghadap lembah di Lingkung Gunung' });
    [
        'Tamu bisa mencari informasi dan memesan kapan saja, tanpa menunggu balasan chat',
        'Stok terpusat: website dan staf memakai data yang sama, risiko pemesanan ganda berkurang',
        'Pembayaran dan status booking tercatat rapi dan diperbarui otomatis',
        'Penawaran rombongan lebih cepat dengan estimasi dan proposal otomatis',
        'Harga, promo, dan konten bisa diperbarui sendiri oleh tim resort',
        'Ulasan berasal dari tamu yang benar-benar menginap dan diperiksa sebelum tayang',
        'Data pelanggan (nama, email, nomor WhatsApp) terkumpul rapi, sebagai bekal mengajak tamu menginap lagi atau menawarkan promo',
    ].forEach((text, i) => {
        const y = 1.75 + i * 0.72;
        T(s, `0${i + 1}`, { x: 0.6, y, w: 0.6, h: 0.45, fontFace: 'Cambria', fontSize: 20, color: C.accent2 });
        T(s, text, { x: 1.3, y: y + 0.03, w: 6.55, h: 0.66, fontSize: 15 });
    });

    // 24. Penutup
    s = newSlide('Gambaran utuh', 'GELAP');
    s.addImage({ path: img.penutup.path, x: 0, y: 0, w: 6.0, h: 7.5, altText: 'Api unggun di area kemping Lingkung Gunung' });
    s.addImage({ path: img.logo.path, x: 6.75, y: 0.75, w: 0.62, h: (0.62 * img.logo.h) / img.logo.w, altText: 'Logo Lingkung Gunung' });
    T(s, 'Satu website untuk menyambut tamu dan mengelola resort', { x: 6.75, y: 2.2, w: 5.9, h: 2.0, fontFace: 'Cambria', fontSize: 34, color: C.background1 });
    T(s, 'Informasi, pemesanan, pembayaran, ulasan, hingga laporan saling terhubung dalam satu tempat, dari HP tamu sampai meja tim resort.', {
        x: 6.75, y: 4.35, w: 5.7, h: 1.2, fontSize: 17, color: HEX.lightText,
    });
    T(s, 'Terima kasih', { x: 6.75, y: 5.95, w: 5, h: 0.6, fontFace: 'Cambria', fontSize: 26, italic: true, color: C.accent3 });

    await pres.writeFile({ fileName: OUT });
    if (process.env.APPLY_THEME) {
        // Opsional: modul eksternal yang menulis warna merek ke tema PowerPoint.
        const { applyTheme } = require(process.env.APPLY_THEME);
        await applyTheme(OUT, THEME);
    }
    console.log('Dibuat: ' + OUT);
}

build().catch((e) => {
    console.error(e);
    process.exit(1);
});
