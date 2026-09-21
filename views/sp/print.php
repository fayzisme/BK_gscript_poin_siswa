<?php
$sp = $sp;
$history = $history;
$totalPoin = $totalPoin;
$tanggalSekarang = $tanggalSekarang;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Peringatan — <?= e(APP_NAME) ?></title>
    <style>
        /* ══ Surat Peringatan Resmi (printable A4) ══ */
        @page { size: A4; margin: 2cm 1.8cm 2cm 1.8cm; }
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 13px; line-height: 1.5; color: #000; background:#f5f5f5; margin: 0; }

        /* Screen only */
        .screen-bar { position: fixed; top:0; left:0; right:0; z-index: 999; background:#0f172a; color:#fff; padding:8px 16px; }
        .screen-bar .btn { margin-left: 10px; }
        @media print { .screen-bar { display:none; } body { background:#fff; } .sheet { box-shadow:none; margin:0; } }

        .sheet {
            background: #fff;
            width: 21cm;
            min-height: 29.7cm;
            margin: 50px auto;
            padding: 2.5cm 2cm 2.5cm 2cm;
            box-shadow: 0 6px 24px rgba(0,0,0,.12);
        }

        /* ── KOP RESMI ── */
        .kop {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
        }
        .kop .flag { font-size: 11px; letter-spacing: .5px; }
        .kop .name { font-size: 18px; font-weight: bold; letter-spacing: 1px; margin-top: 6px; }
        .kop .address { font-size: 11px; margin-top: 4px; }
        .kop .emblem { font-size: 26px; }

        .reff { text-align: right; font-size: 12px; margin-top: 18px; }
        .reff .place { float: left; font-size: 12px; }
        .clear { clear: both; }

        h1.sp-title { text-align: center; font-size: 15px; font-weight: bold; letter-spacing: 2px; margin: 22px 0 4px; text-decoration: underline; }

        .dateline { text-align: justify; margin-top: 14px; }

        table.detail { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.detail th, table.detail td { border: 1px solid #000; padding: 6px 8px; font-size: 12px; }
        table.detail th { background: #eee; text-align: center; }

        .sign { width: 100%; margin-top: 40px; }
        .sign-left { float: left; width: 48%; text-align: center; }
        .sign-right { float: right; width: 48%; text-align: center; }

        .note { font-size: 10px; font-style: italic; margin-top: 30px; text-align: center; }

        .footer-kepaling { font-size: 9px; color: #444; text-align: center; margin-top: 8px; }
    </style>
</head>
<body>

<div class="screen-bar">
    <strong class="me-2">📄 Surat Peringatan — Preview</strong>
    <span class="me-3 badge">Siswa: <?= e($sp['siswa_nama']) ?> · <?= e($sp['siswa_kelas']) ?> · Tingkat <?= e($sp['tingkat_sp']) ?></span>
    <button class="btn btn-sm" style="background:#059669;color:#fff" onclick="window.print()">🖨️ Cetak / Save PDF</button>
    <button class="btn btn-sm" style="background:#64748b;color:#fff" onclick="window.close()">✖ Keluan</button>
</div>

<div class="sheet">

    <!-- ═══ KOP SURAT RESMI ═══ -->
    <div class="kop">
        <div class="emblem">🕋</div>
        <div class="flag">KEMENTERIAN AGAMA REPUBLIK INDONESIA</div>
        <div class="flag">DEPARTEMEN PENDIDIKAN</div>
        <div class="name"><?= e(APP_NAME) ?></div>
        <div class="address">Alamat &amp; telepon sekolah Anda</div>
    </div>

    <div class="reff">
        <span class="place">Dikeluarkan di sekolah, <?= e($tanggalSekarang) ?></span>
        <br>Nr. <?= e($sp['no_surat']) ?>
    </div>
    <div class="clear"></div>

    <h1 class="sp-title">SURAT PERINGATAN (<?= e($sp['tingkat_sp']) ?>)</h1>

    <div class="dateline">
        <p>Bimbingan Konseling (BK) — <?= e(APP_NAME) ?></p>
        <p>Murid: <strong><?= e($sp['siswa_nama']) ?></strong> &nbsp;&nbsp; Kelas: <strong><?= e($sp['siswa_kelas']) ?></strong> &nbsp;&nbsp; No. Absen: <strong><?= e($sp['siswa_absen']) ?></strong> &nbsp;&nbsp; NISN: <strong><?= e($sp['siswa_nisn']) ?></strong></p>
    </div>

    <p>Assalamu'alaikum Wr. Wb. Orang Tua/Wali Murid,</p>

    <p class="dateline">
        Dengan ini dimaklumkan dengan rasa berat hati, bahwa murid tersebut di atas telah melakukan pelanggaran TATA TERTIB sekolah berdasarkan catatan Tim Kedisiplinan &amp; Ketentuan Tata Tertib Siswa.
    </p>

        <p>Berdasarkan catatan Tim Kedisiplinan sekolah, murid tersebut telah mencapai akumulasi poin pelanggaran tata tertib sekolah dengan rincian tindakan dan sanksi sebagai berikut:</p>

    <!-- ═══ TABEL DETAIL VIOLATIONS ═══ -->
    <table class="detail">
        <thead>
            <tr>
                <th style="width:12%">Tanggal</th>
                <th style="width:58%">Bentuk Pelanggaran</th>
                <th style="width:15%">Kategori</th>
                <th style="width:15%">Poin</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= e($h['tanggal']) ?></td>
                    <td><?= e($h['jenis']) ?></td>
                    <td style="text-align:center"><?= e($h['sub_kategori']) ?></td>
                    <td style="text-align:center"><?= $h['poin'] > 0 ? '+' : '' ?><?= (int)$h['poin'] ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3" style="text-align:right"><strong>Total Akumulasi Poin:</strong></td>
                <td style="text-align:center"><strong><?= (int)$totalPoin ?> poin</strong></td>
            </tr>
        </tbody>
    </table>

    <p class="mt-2">
        Demikian Surat Peringatan ini diterbitkan agar dapat menjadi perhatian serius dan pembinaan bersama antara pihak madrasah dan Orang Tua/Wali Murid. Jika poin akumulasi terus ditambah, maka akan dihaktim sanksi lebih berat sesuai Tatib, dando kalau sudah kena SP-3 dan masih dipetik pemelanggaran, bisa dapat sanksi <strong>Dikembalikan ke Orang Tua</strong>.
    </p>

    <!-- ═══ SIGNATURE ═══ -->
    <div class="sign">
        <div class="sign-left">
            <br><br><br>
            <div class="sign-line">( ........................................ )</div>
            Orang Tua / Wali Murid,
        </div>
        <div class="sign-right">
            <br><br><br>
            <strong>Kepala Sekolah,</strong><br>
            <strong>(Nama Kepala Sekolah)</strong><br>
            <span style="font-size:10px">NIP. ……………………………</span>
        </div>
    </div>
    <div class="clear"></div>

    <p class="note">
        Surat ini diterbitkan berdasarkan pendataan log pelanggaran siswa via <?= e(APP_NAME) ?>.
        Catatan lengkap tersimpan di pendataan madrasah. Untuk pertanyaan, kontak Bimbingan Konseling.
    </p>

    <div class="footer-kepaling">
        MADRASAH ALIYAH NEGERI 1 PATI · Sistem BK &amp; SP © <?= date('Y') ?> · Dokumentum ini auto-generated dari database pendataan pelanggaran.
    </div>

</div>

<!-- Auto print di-disable supaya pengguna bisa preview dulu sebelum cetak -->
</body>
</html>