# 📦 Legacy — Google Apps Script (Sebelum Migrasi PHP)

Folder ini berisi **artefak asli aplikasi** sebelum ditransformasi menjadi PHP monolith.
File-file ini **tidak dilayani** oleh aplikasi PHP (document root ada di `public/`)
dan **tidak direferensikan** oleh kode PHP mana pun.

Dipertahankan sebagai **dokumen sejarah & sumber rujukan logika bisnis asli**.

## Daftar File

| File | Peran |
|---|---|
| `Kode.js` | Backend Google Apps Script lama — router `doGet`, setup schema Spreadsheet, seed master tata tertib, logika ambang SP & perhitungan poin |
| `index.html` | Client GAS lama (Tailwind CDN + `google.script.run`) — tidak dilayani PHP, murni artefak |

## Catatan Penting

- **Nomor surat** di `Kode.js` memakai format `054/Ma.11.18/PP.00.6/...` — format ini
  yang sekarang dipakai aplikasi PHP (`app/Models/SpModel.php`).
- **Daftar katalog tata tertib** di `Kode.js` adalah duplikat manual dari
  `masterPelanggaranData()` di `database/seed_master.php`. Jika katalog diperbarui,
  update di **`seed_master.php`** (sumber kebenaran), bukan di sini.
- Jangan pernah meng-import file di folder ini ke aplikasi PHP.
