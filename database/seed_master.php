<?php
/**
 * Master Pelanggaran & Penghargaan — Katalog Default
 * Dipakai untuk seed pertama kali & restore master-default.
 */
function seedMasterPelanggaran(?PDO $db = null): int {
    if ($db === null) {
        require_once __DIR__ . '/../core/Database.php';
        Database::initSchema();
        $db = Database::getConnection();
    }

    $masterData = masterPelanggaranData();

    // ⚠️ JANGAN pakai INSERT OR REPLACE / REPLACE INTO di sini.
    // REPLACE = DELETE + INSERT, dan master_pelanggaran dirujuk log_pelanggaran
    // dgn ON DELETE CASCADE -> restore akan menghapus seluruh log pelanggaran.
    // Pakai INSERT ... ON CONFLICT (SQLite) / AS new ON DUPLICATE KEY (MySQL)
    // yang di-ubah otomatis oleh CompatPDO::transform().
    $stmt = $db->prepare("
        INSERT INTO master_pelanggaran (id, kategori, sub_kategori, jenis_pelanggaran, poin)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT(id) DO UPDATE SET
            kategori = excluded.kategori,
            sub_kategori = excluded.sub_kategori,
            jenis_pelanggaran = excluded.jenis_pelanggaran,
            poin = excluded.poin
    ");
    foreach ($masterData as $m) {
        $stmt->execute($m);
    }
    return count($masterData);
}

/**
 * Sumber tunggal (single source of truth) katalog tata tertib default bawaan.
 * Dipakai oleh seed.php (seed pertama) & seedMasterPelanggaran() (restore default).
 * Jangan duplikasi daftar ini di file lain.
 *
 * @return list<array{0:string,1:string,2:string,3:string,4:int}>
 */
function masterPelanggaranData(): array {
    return [
        // KEPRIBADIAN - Ketertiban
        ['P01', 'Kepribadian', 'Ketertiban', 'Kegaduhan/keributan dalam kelas saat PBM', 5],
        ['P02', 'Kepribadian', 'Ketertiban', 'Masuk lingkungan madrasah dengan loncat pagar', 10],
        ['P03', 'Kepribadian', 'Ketertiban', 'Keluar lingkungan madrasah dengan loncat pagar', 10],
        ['P04', 'Kepribadian', 'Ketertiban', 'Mengotori/mencorat-coret milik madrasah/guru/teman', 5],
        ['P05', 'Kepribadian', 'Ketertiban', 'Merusak/menghilangkan barang milik madrasah/guru/teman', 50],
        ['P06', 'Kepribadian', 'Ketertiban', 'Mencuri barang milik madrasah/guru/teman', 50],
        ['P07', 'Kepribadian', 'Ketertiban', 'Makan dan minum di dalam kelas saat PBM', 5],
        ['P08', 'Kepribadian', 'Ketertiban', 'Membuang sampah tidak pada tempatnya', 5],
        ['P09', 'Kepribadian', 'Ketertiban', 'Membawa benda yang tidak ada kaitannya dengan PBM', 5],
        ['P10', 'Kepribadian', 'Ketertiban', 'Makan dan minum di kantin luar madrasah', 15],
        ['P11', 'Kepribadian', 'Ketertiban', 'Makan dan minum di kantin saat PBM berlangsung', 10],
        ['P12', 'Kepribadian', 'Ketertiban', 'Membawa kendaraan R2 tidak standar pabrik', 10],
        ['P13', 'Kepribadian', 'Ketertiban', 'Memarkir kendaraan di luar lingkungan madrasah', 10],
        ['P14', 'Kepribadian', 'Ketertiban', 'Membawa alat make up / make up mencolok', 10],
        ['P15', 'Kepribadian', 'Ketertiban', 'Bercelana pendek saat masuk madrasah (putra)', 10],
        // KEPRIBADIAN - Rokok
        ['P16', 'Kepribadian', 'Rokok', 'Membawa rokok dan/atau rokok elektrik', 15],
        ['P17', 'Kepribadian', 'Rokok', 'Merokok/vape di lingkungan madrasah', 25],
        ['P18', 'Kepribadian', 'Rokok', 'Merokok/vape di luar madrasah dengan seragam', 25],
        // KEPRIBADIAN - Media & Senjata & Narkoba & Asusila
        ['P19', 'Kepribadian', 'Media & HP', 'Membawa/menyimpan gambar, film, atau media terlarang/porno', 25],
        ['P20', 'Kepribadian', 'Media & HP', 'Medsos mencemarkan nama baik madrasah/guru/teman', 50],
        ['P21', 'Kepribadian', 'Senjata', 'Membawa/memperjualbelikan senjata tajam/peledak tanpa izin', 25],
        ['P22', 'Kepribadian', 'Senjata', 'Menggunakan sajam/bahan peledak untuk mengancam/melukai', 100],
        ['P23', 'Kepribadian', 'Narkoba', 'Membawa/mengonsumsi/memperjualbelikan obat/minuman terlarang', 100],
        ['P24', 'Kepribadian', 'Asusila', 'Perkelahian sesama atau antar murid madrasah', 50],
        ['P25', 'Kepribadian', 'Asusila', 'Tersangkut kasus kriminal / perzinaan / ancaman guru', 100],
        ['P26', 'Kepribadian', 'Asusila', 'Melakukan tindakan pacaran', 10],
        // KERAJINAN - Keterlambatan & Kehadiran
        ['P27', 'Kerajinan', 'Keterlambatan', 'Terlambat masuk madrasah (>5 menit)', 5],
        ['P28', 'Kerajinan', 'Keterlambatan', 'Izin keluar saat PBM dan tidak kembali / Pulang tanpa izin', 10],
        ['P29', 'Kerajinan', 'Kehadiran', "Tidak mengikuti tadarus Al-Qur'an", 3],
        ['P30', 'Kerajinan', 'Kehadiran', 'Sakit / Izin tanpa keterangan sah', 3],
        ['P31', 'Kerajinan', 'Kehadiran', 'Tidak masuk sekolah tanpa keterangan (Alpha)', 10],
        ['P32', 'Kerajinan', 'Kehadiran', 'Tidak melaksanakan Shalat Dhuhur / Jumat berjamaah', 5],
        ['P33', 'Kerajinan', 'Kehadiran', 'Tidak mengikuti PBM (Membolos)', 5],
        ['P34', 'Kerajinan', 'Kehadiran', 'Tidak mengikuti upacara, apel, atau khitobah', 10],
        // KERAPIAN - Pakaian & Rambut
        ['P35', 'Kerapian', 'Pakaian', 'Seragam tidak rapi / tidak dimasukkan / ketat', 5],
        ['P36', 'Kerapian', 'Pakaian', 'Tidak memakai topi / dasi / atribut seragam sesuai ketentuan', 5],
        ['P37', 'Kerapian', 'Pakaian', 'Tidak memakai jas almamater saat upacara/khitobah', 5],
        ['P38', 'Kerapian', 'Rambut & Kuku', 'Rambut panjang >5 cm / tidak rapi / disemir (putra)', 5],
        ['P39', 'Kerapian', 'Rambut & Kuku', 'Memanjangkan/mewarnai kuku atau modifikasi alis/rambut', 5],
        // PENGHARGAAN
        ['H01', 'Penghargaan', 'Prestasi', 'Juara 1 / 2 / 3 Lomba Tingkat Nasional', -15],
        ['H02', 'Penghargaan', 'Prestasi', 'Juara 1 / 2 / 3 Lomba Tingkat Provinsi', -10],
        ['H03', 'Penghargaan', 'Prestasi', 'Juara 1 / 2 / 3 Lomba Tingkat Kabupaten', -5],
    ];
}

// Jika dieksekusi langsung (CLI)
if (isset($_SERVER['argv']) && count($_SERVER['argv']) > 0 && realpath($_SERVER['argv'][0]) === realpath(__FILE__)) {
    $n = seedMasterPelanggaran();
    echo "[✓] Seed Master Pelanggaran ($n item) selesai.\n";
}