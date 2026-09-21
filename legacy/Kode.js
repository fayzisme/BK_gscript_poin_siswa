const SPREADSHEET_ID = '10Jms9uicJIaDof8pjQlmKiWBVTv42wYWGV9PdJil4Vc';

function doGet() {
  return HtmlService.createTemplateFromFile('Index')
    .evaluate()
    .setTitle('Sistem Pelanggaran & SP - Sekolah Anda')
    .addMetaTag('viewport', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

// 1. SETUP OTOMATIS TAB SPREADSHEET & MASTER DATA PELANGGARAN SEKOLAH
function setupDatabaseDanMaster() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  
  const sheets = ['DataSiswa', 'DataGuru', 'MasterPelanggaran', 'LogPelanggaran', 'PoinSiswa', 'SP'];
  sheets.forEach(shName => {
    if (!ss.getSheetByName(shName)) {
      ss.insertSheet(shName);
    }
  });

  // Setup Header DataSiswa & DataGuru jika kosong
  const shSiswa = ss.getSheetByName('DataSiswa');
  if (shSiswa.getLastRow() === 0) {
    shSiswa.appendRow(['NISN_ID', 'Nama', 'Kelas', 'Absen']);
  }
  
  const shGuru = ss.getSheetByName('DataGuru');
  if (shGuru.getLastRow() === 0) {
    shGuru.appendRow(['NIP_ID', 'NamaGuru']);
  }

  // Master Pelanggaran & Penghargaan Sesuai PDF Tatib Sekolah
  const shMaster = ss.getSheetByName('MasterPelanggaran');
  if (shMaster.getLastRow() <= 1) {
    shMaster.clear();
    shMaster.appendRow(['ID', 'Kategori', 'SubKategori', 'JenisPelanggaran', 'Poin']);
    
    const masterData = [
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
      
      // KEPRIBADIAN - Media & Senjata & Obat
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
      ['P29', 'Kerajinan', 'Kehadiran', 'Tidak mengikuti tadarus Al-Qur\'an', 3],
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
      
      // PENGHARGAAN (PEMUTIHAN POIN)
      ['H01', 'Penghargaan', 'Prestasi', 'Juara 1 / 2 / 3 Lomba Tingkat Nasional', -15],
      ['H02', 'Penghargaan', 'Prestasi', 'Juara 1 / 2 / 3 Lomba Tingkat Provinsi', -10],
      ['H03', 'Penghargaan', 'Prestasi', 'Juara 1 / 2 / 3 Lomba Tingkat Kabupaten', -5]
    ];
    
    shMaster.getRange(2, 1, masterData.length, 5).setValues(masterData);
  }
  return "Database & Master Pelanggaran Berhasil Disiapkan!";
}

// 2. AMBIL MASTER DATA INITIAL
function getInitialData() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  
  const siswa = ss.getSheetByName('DataSiswa').getDataRange().getValues();
  const guru = ss.getSheetByName('DataGuru').getDataRange().getValues();
  const pelanggaran = ss.getSheetByName('MasterPelanggaran').getDataRange().getValues();
  
  siswa.shift();
  guru.shift();
  pelanggaran.shift();
  
  const kelasSet = [...new Set(siswa.map(r => String(r[2])))].sort();
  
  return {
    kelas: kelasSet,
    guru: guru.map(r => ({ id: String(r[0]), nama: r[1] })),
    pelanggaran: pelanggaran.map(r => ({
      id: r[0], kategori: r[1], subKategori: r[2], jenis: r[3], poin: Number(r[4])
    }))
  };
}

// 3. AMBIL SISWA PER KELAS
function getSiswaByKelas(kelas) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const siswa = ss.getSheetByName('DataSiswa').getDataRange().getValues();
  siswa.shift();
  
  return siswa
    .filter(r => String(r[2]) === String(kelas))
    .map(r => ({ id: String(r[0]), nama: r[1], kelas: r[2], absen: r[3] }))
    .sort((a, b) => Number(a.absen) - Number(b.absen));
}

// 4. SIMPAN PELANGGARAN & OTOMATISASI SP
function simpanPelanggaran(data) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const shLog = ss.getSheetByName('LogPelanggaran');
  const shPoin = ss.getSheetByName('PoinSiswa');
  const shSP = ss.getSheetByName('SP');
  
  const idLog = 'LOG-' + new Date().getTime();
  const tgl = new Date().toISOString().split('T')[0];
  
  shLog.appendRow([idLog, tgl, data.siswaId, data.pelanggaranId, data.poin, data.guruInput, data.buktiUrl || '-']);
  
  // Hitung ulang akumulasi poin siswa
  const dataPoin = shPoin.getDataRange().getValues();
  let rowIndex = -1;
  let totalPoin = 0;
  
  for (let i = 1; i < dataPoin.length; i++) {
    if (String(dataPoin[i][0]) === String(data.siswaId)) {
      rowIndex = i + 1;
      totalPoin = Number(dataPoin[i][1]);
      break;
    }
  }
  
  totalPoin += Number(data.poin);
  if (totalPoin < 0) totalPoin = 0;
  
  // Penentuan Status SP
  let statusSp = 'Bebas SP';
  if (totalPoin >= 100) statusSp = 'Dikembalikan ke Orang Tua';
  else if (totalPoin >= 75) statusSp = 'SP-3';
  else if (totalPoin >= 50) statusSp = 'SP-2';
  else if (totalPoin >= 25) statusSp = 'SP-1';
  
  if (rowIndex > -1) {
    shPoin.getRange(rowIndex, 2, 1, 2).setValues([[totalPoin, statusSp]]);
  } else {
    shPoin.appendRow([data.siswaId, totalPoin, statusSp]);
  }
  
  // Jika Ambang Batas Terlampaui -> Terbitkan Draf SP
  if (statusSp.startsWith('SP') || statusSp.includes('Dikembalikan')) {
    const noSurat = `054/Ma.11.18/PP.00.6/${new Date().getMonth()+1}/${new Date().getFullYear()}`;
    shSP.appendRow(['SP-' + new Date().getTime(), noSurat, tgl, data.siswaId, statusSp, 'Terbit']);
  }
  
  return { success: true, totalPoin: totalPoin, statusSp: statusSp };
}

// 5. AMBIL DATA REKAP SISWA LENGKAP
function getRekapSiswaData(kelasFilter) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const siswa = ss.getSheetByName('DataSiswa').getDataRange().getValues();
  const poin = ss.getSheetByName('PoinSiswa').getDataRange().getValues();
  
  siswa.shift();
  poin.shift();
  
  const mapPoin = {};
  poin.forEach(p => {
    mapPoin[String(p[0])] = { totalPoin: p[1], statusSp: p[2] };
  });
  
  let listSiswa = siswa.map(s => {
    const pInfo = mapPoin[String(s[0])] || { totalPoin: 0, statusSp: 'Bebas SP' };
    return {
      id: String(s[0]),
      nama: s[1],
      kelas: s[2],
      absen: s[3],
      poin: pInfo.totalPoin,
      statusSp: pInfo.statusSp
    };
  });
  
  if (kelasFilter && kelasFilter !== 'ALL') {
    listSiswa = listSiswa.filter(s => String(s.kelas) === String(kelasFilter));
  }
  
  return listSiswa.sort((a, b) => b.poin - a.poin);
}

// 6. AMBIL TIMELINE RIWAYAT PELANGGARAN SISWA
function getRiwayatSiswa(siswaId) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const log = ss.getSheetByName('LogPelanggaran').getDataRange().getValues();
  const master = ss.getSheetByName('MasterPelanggaran').getDataRange().getValues();
  const siswa = ss.getSheetByName('DataSiswa').getDataRange().getValues();
  
  log.shift();
  master.shift();
  siswa.shift();
  
  const mapMaster = {};
  master.forEach(m => mapMaster[String(m[0])] = { jenis: m[3], kategori: m[1] });
  
  const sMatch = siswa.find(s => String(s[0]) === String(siswaId));
  const infoSiswa = sMatch ? { nama: sMatch[1], kelas: sMatch[2], absen: sMatch[3] } : { nama: siswaId, kelas: '-', absen: '-' };
  
  const history = log
    .filter(l => String(l[2]) === String(siswaId))
    .map(l => {
      const mInfo = mapMaster[String(l[3])] || { jenis: l[3], kategori: 'Umum' };
      return {
        idLog: l[0],
        tanggal: l[1] instanceof Date ? l[1].toISOString().split('T')[0] : l[1],
        jenis: mInfo.jenis,
        kategori: mInfo.kategori,
        poin: l[4],
        guru: l[5],
        bukti: l[6]
      };
    }).reverse();
    
  return { siswa: infoSiswa, history: history };
}

// 7. AMBIL DAFTAR SURAT SP UNTUK DICETAK
function getDaftarSP() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sp = ss.getSheetByName('SP').getDataRange().getValues();
  const siswa = ss.getSheetByName('DataSiswa').getDataRange().getValues();
  
  sp.shift();
  siswa.shift();
  
  const mapSiswa = {};
  siswa.forEach(s => mapSiswa[String(s[0])] = { nama: s[1], kelas: s[2], absen: s[3] });
  
  return sp.map(r => {
    const sInfo = mapSiswa[String(r[3])] || { nama: r[3], kelas: '-', absen: '-' };
    return {
      idSp: r[0],
      noSurat: r[1],
      tanggal: r[2] instanceof Date ? r[2].toISOString().split('T')[0] : r[2],
      siswaId: r[3],
      namaSiswa: sInfo.nama,
      kelas: sInfo.kelas,
      absen: sInfo.absen,
      tingkatSp: r[4]
    };
  }).reverse();
}