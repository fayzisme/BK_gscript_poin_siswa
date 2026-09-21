<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Helpers.php';

echo "=== INITIALIZING DATABASE & SEEDING SAMPLE DATA ===\n";

Database::initSchema();
$db = Database::getConnection();

// 1. SEED DATA GURU
$guruData = [
    ['197001010000000001', 'Kepala Sekolah Contoh', '197001010000000001', 'Kepala Sekolah'],
    ['197001010000000002', 'Waka Kesiswaan Contoh', '197001010000000002', 'Koordinator BK'],
    ['197001010000000003', 'Guru BK Contoh 1', '197001010000000003', 'Guru BK / Kedisiplinan'],
    ['197001010000000004', 'Guru BK Contoh 2', '197001010000000004', 'Guru BK'],
    ['197001010000000005', 'Guru BK Contoh 3', '197001010000000005', 'Waka Kesiswaan'],
    ['197001010000000006', 'Guru BK Contoh 4', '197001010000000006', 'Guru Mapel Contoh'],
    ['197001010000000007', 'Wali Kelas Contoh', '197001010000000007', 'Wali Kelas X-1'],
    ['197001010000000008', 'Guru TI Contoh', '197001010000000008', 'Guru TI / Proktor'],
];

$stmtGuru = $db->prepare("INSERT OR REPLACE INTO guru (id, nama, nip, jabatan, username, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($guruData as $g) {
    $stmtGuru->execute([...$g, '', '']);
}
echo "[✓] Data Guru (" . count($guruData) . " orang) seeded.\n";

// Akun pengguna default (password di-hash bcrypt, jangan disimpan plaintext)
$defaultUsers = [
    ['197001010000000001', 'admin', 'admin123', 'Admin / Kepala Sekolah', 'admin'],
    ['197001010000000002', 'bkkord', 'bkkord123', 'Koordinator BK', 'bkkord'],
    ['197001010000000003', 'gurubk', 'gurubk123', 'Guru BK', 'guru'],
];
$stmtUser = $db->prepare("UPDATE guru SET username = ?, password_hash = ?, role = ? WHERE id = ?");
foreach ($defaultUsers as $u) {
    $hash = password_hash($u[2], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmtUser->execute([$u[1], $hash, $u[4], $u[0]]);
    echo "  → Akun '{$u[1]}' dibuat (password: {$u[2]}, role: {$u[4]})\n";
}
echo "[✓] Akun pengguna default dibuat (password tersimpan hashed bcrypt).\n";

// 2. SEED MASTER PELANGGARAN & PENGHARGAAN (Katalog Default)
//    Sumber tunggal daftar katalog ada di seed_master.php -> masterPelanggaranData()
require_once __DIR__ . '/seed_master.php';
$masterPelanggaran = masterPelanggaranData();

$stmtMaster = $db->prepare("INSERT OR REPLACE INTO master_pelanggaran (id, kategori, sub_kategori, jenis_pelanggaran, poin) VALUES (?, ?, ?, ?, ?)");
foreach ($masterPelanggaran as $m) {
    $stmtMaster->execute($m);
}
echo "[✓] Master Pelanggaran & Penghargaan (" . count($masterPelanggaran) . " item) seeded.\n";

// 3. SEED DATA SISWA CONTOH
$siswaData = [
    // Kelas X-1
    ['00511223001', '0051122331', 'Siswa Contoh 1', 'X-1', 1, 'L'],
    ['00511223002', '0051122332', 'Siswa Contoh 2', 'X-1', 2, 'P'],
    ['00511223003', '0051122333', 'Siswa Contoh 3', 'X-1', 3, 'L'],
    ['00511223004', '0051122334', 'Siswa Contoh 4', 'X-1', 4, 'P'],
    ['00511223005', '0051122335', 'Siswa Contoh 5', 'X-1', 5, 'L'],
    ['00511223006', '0051122336', 'Siswa Contoh 6', 'X-1', 6, 'L'],
    
    // Kelas X-2
    ['00511223007', '0051122337', 'Siswa Contoh 7', 'X-2', 1, 'L'],
    ['00511223008', '0051122338', 'Siswa Contoh 8', 'X-2', 2, 'P'],
    ['00511223009', '0051122339', 'Siswa Contoh 9', 'X-2', 3, 'L'],
    ['00511223010', '0051122340', 'Siswa Contoh 10', 'X-2', 4, 'P'],

    // Kelas XI-IPA-1
    ['00511223011', '0041122341', 'Siswa Contoh 11', 'XI-IPA-1', 1, 'L'],
    ['00511223012', '0041122342', 'Siswa Contoh 12', 'XI-IPA-1', 2, 'L'],
    ['00511223013', '0041122343', 'Siswa Contoh 13', 'XI-IPA-1', 3, 'P'],
    ['00511223014', '0041122344', 'Siswa Contoh 14', 'XI-IPA-1', 4, 'L'],

    // Kelas XI-IPS-1
    ['00511223015', '0041122345', 'Siswa Contoh 15', 'XI-IPS-1', 1, 'L'],
    ['00511223016', '0041122346', 'Siswa Contoh 16', 'XI-IPS-1', 2, 'P'],
    ['00511223017', '0041122347', 'Siswa Contoh 17', 'XI-IPS-1', 3, 'L'],
    ['00511223018', '0041122348', 'Siswa Contoh 18', 'XI-IPS-1', 4, 'P'],

    // Kelas XII-IPA-1
    ['00511223019', '0031122349', 'Siswa Contoh 19', 'XII-IPA-1', 1, 'L'],
    ['00511223020', '0031122350', 'Siswa Contoh 20', 'XII-IPA-1', 2, 'L'],
    ['00511223021', '0031122351', 'Siswa Contoh 21', 'XII-IPA-1', 3, 'P'],
    ['00511223022', '0031122352', 'Siswa Contoh 22', 'XII-IPA-1', 4, 'L'],
];

$stmtSiswa = $db->prepare("INSERT OR REPLACE INTO siswa (id, nisn, nama, kelas, absen, jenis_kelamin) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($siswaData as $s) {
    $stmtSiswa->execute($s);
}
echo "[✓] Data Siswa (" . count($siswaData) . " siswa) seeded.\n";

// 4. SEED SAMPLE LOG PELANGGARAN & POIN AKUMULASI
$sampleLogs = [
    // Siswa Contoh 3 (X-1) - Kena P17 (Merokok 25pt) + P27 (Terlambat 5pt) = 30pt (SP-1)
    ['LOG-1001', '2026-08-10', '00511223003', 'P27', 5, 'Guru BK Contoh 1', 'Terlambat 15 menit', ''],
    ['LOG-1002', '2026-08-25', '00511223003', 'P17', 25, 'Guru BK Contoh 2', 'Ditemukan merokok di belakang kantin', 'https://example.com/foto1.jpg'],

    // Siswa Contoh 9 (X-2) - Kena P06 (Mencuri 50pt) + P18 (Merokok seragam 25pt) + P27 (Terlambat 5pt) = 80pt (SP-3)
    ['LOG-1003', '2026-08-05', '00511223009', 'P27', 5, 'Guru BK Contoh 3', 'Terlambat pintu gerbang', ''],
    ['LOG-1004', '2026-08-18', '00511223009', 'P06', 50, 'Guru BK Contoh 1', 'Mencuri HP di musholla sekolah', ''],
    ['LOG-1005', '2026-09-01', '00511223009', 'P18', 25, 'Guru BK Contoh 4', 'Merokok di warung depan sekolah berseragam', ''],

    // Siswa Contoh 17 (XI-IPS-1) - Kena P22 (Menggunakan sajam 100pt) = 100pt (Dikembalikan ke Ortum)
    ['LOG-1006', '2026-09-02', '00511223017', 'P22', 100, 'Waka Kesiswaan Contoh', 'Membawa dan mengancam siswa lain dengan sajam', 'https://example.com/sajam.jpg'],

    // Siswa Contoh 5 (X-1) - Kena P38 (Rambut 5pt) + H03 (Juara Kab -5pt) = 0pt (Bebas SP)
    ['LOG-1007', '2026-08-12', '00511223005', 'P38', 5, 'Guru BK Contoh 2', 'Rambut gondrong', ''],
    ['LOG-1008', '2026-08-30', '00511223005', 'H03', -5, 'Guru BK Contoh 3', 'Juara 2 Lomba Matematika Kabupaten', '']
];

$stmtLog = $db->prepare("INSERT OR REPLACE INTO log_pelanggaran (id, tanggal, siswa_id, pelanggaran_id, poin, guru_input, catatan, bukti_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($sampleLogs as $l) {
    $stmtLog->execute($l);
}
echo "[✓] Log Pelanggaran Sample seeded.\n";

// Recalculate Points & Generate SP Drafts automatically
// Catatan: setiap siswa wajib punya baris di poin_siswa (meski 0 poin) supaya
// rekap & cetak SP tidak melewatkannya. Ambang SP satu sumber: core/Helpers.php
$stmtPoin = $db->prepare("INSERT OR REPLACE INTO poin_siswa (siswa_id, total_poin, status_sp, updated_at) VALUES (?, ?, ?, ?)");
$stmtSp = $db->prepare("INSERT OR REPLACE INTO surat_peringatan (id, no_surat, tanggal, siswa_id, tingkat_sp, status) VALUES (?, ?, ?, ?, ?, ?)");

$totals = []; // siswa_id => total poin dari log
foreach ($db->query("SELECT siswa_id, COALESCE(SUM(poin), 0) AS t FROM log_pelanggaran GROUP BY siswa_id") as $r) {
    $totals[$r['siswa_id']] = max(0, (int)$r['t']);
}

$semuaSiswa = $db->query("SELECT id FROM siswa")->fetchAll();
foreach ($semuaSiswa as $s) {
    $siswaId = $s['id'];
    $totalPoin = $totals[$siswaId] ?? 0;

    $info = getSpStatusInfo($totalPoin);
    $statusSp = $info['status'];

    $stmtPoin->execute([$siswaId, $totalPoin, $statusSp, date('Y-m-d H:i:s')]);

    if ($info['level'] > 0) {
        $spId = 'SP-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        $noSurat = "054/Ma.11.18/PP.00.6/" . date('m/Y');
        $stmtSp->execute([$spId, $noSurat, date('Y-m-d'), $siswaId, $statusSp, 'Terbit']);
    }
}

echo "[✓] Recalculated Poin Siswa & Automated SP Drafts created.\n";
echo "=== SEEDING COMPLETED SUCCESSFULLY ===\n";
