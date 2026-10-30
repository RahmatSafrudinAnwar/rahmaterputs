<?php
require_once __DIR__ . '/layout.php';
const BATAS_KALAB = 5000000;
function level(int $n): string { return $n > BATAS_KALAB ? 'Pimpinan' : 'Kepala Lab'; }
function logp(int $id, string $aktor, string $aksi, string $cat = '') {
    q("INSERT INTO log_persetujuan(permintaan_id,waktu,aktor,aksi,catatan) VALUES(?,?,?,?,?)", [$id, date('Y-m-d H:i:s'), $aktor, $aksi, $cat]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['aksi'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    $p = $id ? row("SELECT * FROM permintaan WHERE id=?", [$id]) : null;
    if ($a === 'baru') {
        q("INSERT INTO permintaan(pengaju,lab,bahan,qty,nilai,status,dibuat) VALUES(?,?,?,?,0,'DIAJUKAN',?)", [trim($_POST['pengaju']), trim($_POST['lab']), trim($_POST['bahan']), max(1, (int)$_POST['qty']), date('Y-m-d H:i')]);
        $nid = (int)db()->lastInsertId();
        logp($nid, trim($_POST['pengaju']), 'Mengajukan', 'Permintaan pembelian baru');
        go('p3.php?id=' . $nid, 'Permintaan pembelian dibuat.');
    }
    if (!$p) go('p3.php', 'Permintaan tidak ditemukan.');
    $st = $p['status'];
    if ($a === 'verifikasi' && $st === 'DIAJUKAN') {
        q("UPDATE permintaan SET status='TERVERIFIKASI' WHERE id=?", [$id]); logp($id, 'Kepala Lab', 'Memverifikasi', 'Kebutuhan sesuai');
        $m = 'Diverifikasi Kepala Lab.';
    } elseif ($a === 'cek_stok' && $st === 'TERVERIFIKASI') {
        $s = max(0, (int)$_POST['stok']);
        if ($s >= $p['qty']) { q("UPDATE permintaan SET status='DIPENUHI_STOK' WHERE id=?", [$id]); logp($id, 'Gudang', 'Cek stok', "Stok $s cukup; dipenuhi dari gudang"); $m = 'Stok cukup, tidak perlu membeli.'; }
        else { q("UPDATE permintaan SET status='STOK_KURANG' WHERE id=?", [$id]); logp($id, 'Gudang', 'Cek stok', "Stok $s, kurang " . ($p['qty'] - $s) . '; perlu dibeli'); $m = 'Stok kurang, lanjut ke pembelian.'; }
    } elseif ($a === 'buat_po' && $st === 'STOK_KURANG') {
        $n = max(1, (int)$_POST['nilai']); q("UPDATE permintaan SET status='PO_DIBUAT', nilai=? WHERE id=?", [$n, $id]);
        logp($id, 'Pembelian', 'Membuat PO', 'Nilai ' . rp($n) . '; butuh persetujuan ' . level($n)); $m = 'PO dibuat, menunggu persetujuan ' . level($n) . '.';
    } elseif ($a === 'setuju' && $st === 'PO_DIBUAT') {
        q("UPDATE permintaan SET status='DISETUJUI', nilai_disetujui=nilai WHERE id=?", [$id]);
        logp($id, level((int)$p['nilai']), 'Menyetujui PO', 'Nilai disetujui ' . rp($p['nilai'])); $m = 'PO disetujui ' . level((int)$p['nilai']) . '.';
    } elseif ($a === 'ubah_nilai' && in_array($st, ['PO_DIBUAT', 'DISETUJUI'])) {
        $n = max(1, (int)$_POST['nilai']);
        $lama = (int)($p['nilai_disetujui'] ?: $p['nilai']);
        $ulang = $st === 'DISETUJUI' && ($n > $lama * 1.10 || level($n) !== level($lama));
        if ($ulang) { q("UPDATE permintaan SET nilai=?, status='PO_DIBUAT' WHERE id=?", [$n, $id]); logp($id, 'Pembelian', 'Nilai PO berubah', rp($lama) . ' → ' . rp($n) . ': PERSETUJUAN DIULANG (R4)'); $m = 'Nilai naik >10% atau melewati batas wewenang: persetujuan wajib diulang oleh ' . level($n) . '.'; }
        else { q("UPDATE permintaan SET nilai=? WHERE id=?", [$n, $id]); logp($id, 'Pembelian', 'Nilai PO berubah', rp($lama) . ' → ' . rp($n) . ': persetujuan lama masih berlaku'); $m = 'Perubahan dalam batas toleransi; persetujuan sebelumnya masih berlaku.'; }
    } elseif ($a === 'terima' && $st === 'DISETUJUI') {
        q("UPDATE permintaan SET status='DITERIMA' WHERE id=?", [$id]); logp($id, 'Gudang', 'Penerimaan barang', 'Mengacu pada PO'); $m = 'Barang diterima mengacu PO.';
    } elseif ($a === 'bayar' && $st === 'DITERIMA') {
        if (empty($_POST['cocok'])) $m = 'Pembayaran ditolak: faktur harus dicocokkan dengan PO dan penerimaan (aturan R6).';
        else { q("UPDATE permintaan SET status='DIBAYAR' WHERE id=?", [$id]); logp($id, 'Keuangan', 'Pembayaran', 'Three-way match sesuai; jurnal utang dan kas dibuat'); $m = 'Pembayaran diproses.'; }
    } else $m = 'Aksi tidak sesuai status permintaan.';
    go('p3.php?id=' . $id, $m);
}
function aksi_form(array $p): string {
    $id = $p['id']; $f = fn($a, $isi, $btn) => '<form method="post" class="d-flex gap-1"><input type="hidden" name="aksi" value="' . $a . '"><input type="hidden" name="id" value="' . $id . '">' . $isi . '<button class="btn btn-sm btn-primary">' . $btn . '</button></form>';
    switch ($p['status']) {
        case 'DIAJUKAN': return $f('verifikasi', '', 'Verifikasi (Kalab)');
        case 'TERVERIFIKASI': return $f('cek_stok', '<input name="stok" type="number" min="0" class="form-control form-control-sm" style="width:90px" placeholder="stok" required>', 'Cek stok');
        case 'STOK_KURANG': return $f('buat_po', '<input name="nilai" type="number" min="1" class="form-control form-control-sm" style="width:120px" placeholder="nilai PO" required>', 'Buat PO');
        case 'PO_DIBUAT': return $f('setuju', '', 'Setujui (' . level((int)$p['nilai']) . ')');
        case 'DISETUJUI': return $f('terima', '', 'Terima barang') . '<div class="mt-1">' . $f('ubah_nilai', '<input name="nilai" type="number" min="1" class="form-control form-control-sm" style="width:120px" placeholder="nilai baru" required>', 'Ubah nilai') . '</div>';
        case 'DITERIMA': return $f('bayar', '<label class="small me-1"><input type="checkbox" name="cocok" value="1"> faktur cocok</label>', 'Bayar');
        default: return '<span class="text-muted">Selesai</span>';
    }
}
$id = (int)($_GET['id'] ?? 0);
head('Praktikum 3', 'p3.php'); ?>
<div class="card"><div class="card-body">
<h2 class="h4">Praktikum 3 – Proses Bisnis dan Alur Persetujuan</h2>
<div class="row"><div class="col-12"><h5>1. Diagram as-is (merah = berisiko)</h5><img class="diagram" src="assets/img/p3_asis.png" alt="as-is"></div>
<div class="col-lg-7"><h5>2. Diagram to-be (dengan ERP)</h5><img class="diagram" src="assets/img/p3_tobe.png" alt="to-be"></div></div>
<p class="small mt-2">Risiko as-is: form hilang/tidak terlacak, stok tidak akurat sehingga bisa beli ganda, menunggu pimpinan tanpa notifikasi, dan pembayaran tanpa bukti penerimaan.</p>
<h5>3. Aturan bisnis</h5>
<?php tabel(['No', 'Aturan'], [['R1', 'Permintaan harus memiliki identitas pengaju, laboratorium, dan alasan kebutuhan.'], ['R2', 'Permintaan diperiksa terhadap stok; hanya selisih kekurangan yang dapat dibeli.'],
 ['R3', 'PO disetujui pihak berwenang sebelum dikirim ke pemasok: Kepala Lab ≤ Rp5.000.000, Pimpinan di atasnya.'], ['R4', 'Jika nilai PO naik >10% dari nilai disetujui, atau melewati batas wewenang, persetujuan wajib diulang.'],
 ['R5', 'Penerimaan barang wajib mengacu pada PO.'], ['R6', 'Pembayaran hanya diproses bila PO, penerimaan, dan faktur cocok (three-way match).'], ['R7', 'Pengaju tidak boleh menyetujui permintaannya sendiri (pemisahan tugas).']]); ?>
<h5>4. Aktor dan tanggung jawab</h5>
<?php tabel(['Aktor', 'Tanggung jawab', 'Dokumen'], [['Dosen', 'Mengajukan kebutuhan bahan', 'Permintaan Pembelian'], ['Kepala Lab', 'Memverifikasi kebutuhan, menyetujui PO ≤ Rp5 juta', 'Persetujuan tahap 1'], ['Gudang', 'Cek stok, menerima barang', 'Laporan stok, Goods Receipt'], ['Pembelian', 'Memilih pemasok, membuat PO', 'PO'], ['Pimpinan', 'Menyetujui PO > Rp5 juta', 'Persetujuan PO'], ['Keuangan', 'Mencocokkan dokumen dan membayar', 'Faktur, bukti bayar, jurnal']]); ?>

<h5>5. Simulasi proses (database)</h5>
<p class="small text-muted">Jalankan satu permintaan sampai dibayar. Kondisi khusus: setelah PO disetujui, klik <b>Ubah nilai</b>. Contoh: PO Rp4.500.000 diubah ke Rp5.200.000 (naik 15,6% dan melewati batas Kalab) → persetujuan diulang oleh Pimpinan.</p>
<form method="post" class="row g-2 mb-3"><input type="hidden" name="aksi" value="baru">
<div class="col-md-3"><input name="pengaju" class="form-control" placeholder="Pengaju (dosen)" required></div><div class="col-md-3"><input name="lab" class="form-control" placeholder="Laboratorium" required></div>
<div class="col-md-3"><input name="bahan" class="form-control" placeholder="Bahan yang diminta" required></div><div class="col-md-1"><input name="qty" type="number" min="1" value="10" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-success w-100">Ajukan</button></div></form>
<?php tabel(['No', 'Pengaju', 'Lab', 'Bahan', 'Qty', 'Nilai PO', 'Disetujui', 'Status', 'Tindakan', 'Log'],
 array_map(fn($x) => ['REQ-' . $x['id'], h($x['pengaju']), h($x['lab']), h($x['bahan']), $x['qty'], $x['nilai'] ? rp($x['nilai']) : '-', $x['nilai_disetujui'] ? rp($x['nilai_disetujui']) : '-', '<span class="badge bg-secondary">' . h($x['status']) . '</span>', aksi_form($x), '<a href="p3.php?id=' . $x['id'] . '">Lihat</a>'], rows("SELECT * FROM permintaan ORDER BY id DESC")), 'dtf'); ?>
<?php if ($id): ?><h6>Riwayat persetujuan REQ-<?= $id ?></h6>
<?php tabel(['Waktu', 'Aktor', 'Aksi', 'Catatan'], array_map(fn($x) => [h($x['waktu']), h($x['aktor']), h($x['aksi']), h($x['catatan'])], rows("SELECT * FROM log_persetujuan WHERE permintaan_id=? ORDER BY id", [$id]))); endif; ?>

<h5>6. Mengapa rancangan baru lebih baik</h5>
<p>Setiap langkah tercatat dan dapat dilacak (tabel log), stok diperiksa sebelum membeli, persetujuan punya batas wewenang yang jelas dan diulang saat nilai berubah, serta pembayaran hanya terjadi setelah dokumen cocok. Hasilnya: pembelian ganda berkurang, proses lebih cepat, dan risiko pembayaran tanpa dasar turun.</p>
<a class="btn btn-outline-danger btn-sm" href="reset.php?p=3">Reset data praktikum ini</a>
</div></div>
<?php foot();
