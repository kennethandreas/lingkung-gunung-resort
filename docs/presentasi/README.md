# Presentasi website (untuk pemilik & manajemen resort)

- `Presentasi_Website_Resort.pptx`: 24 slide 16:9, font Cambria + Calibri (terpasang bersama Microsoft Office).
- `Presentasi_Website_Resort.pdf`: diekspor oleh Microsoft PowerPoint dari file PPTX di atas.
- Gambar: tangkapan layar asli dari `docs/panduan/gambar` (data contoh fiktif; data tamu lain disamarkan) dan foto resmi resort dari `public/images/resort`.

## Membuat ulang

Butuh Node 22, Microsoft PowerPoint (untuk PDF), dan paket `pptxgenjs sharp react react-dom react-icons` di folder terpisah (JANGAN dipasang ke package.json website):

```bash
mkdir -p /tmp/deck && (cd /tmp/deck && npm init -y && npm install pptxgenjs sharp react react-dom react-icons)
DECK_MODULES=/tmp/deck/node_modules NODE_PATH=/tmp/deck/node_modules APPLY_THEME=<skill pptx>/scripts/apply_theme.js \
  node docs/presentasi/scripts/buat-presentasi.cjs
powershell -ExecutionPolicy Bypass -File docs/presentasi/scripts/ekspor-pdf.ps1 [-GambarDir <folder PNG untuk pemeriksaan>]
```

`APPLY_THEME` menulis warna merek ke tema PowerPoint. Tanpa langkah itu, warna mengikuti palet bawaan Office.
Bila tampilan website berubah, ambil ulang tangkapan layar (lihat `docs/panduan/README.md`), lalu jalankan kedua perintah di atas.
