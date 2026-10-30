<?php
require_once __DIR__ . '/layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['aksi'] ?? '';
    if ($a === 'transaksi') {
        $d = $_POST['debit']; $k = $_POST['kredit']; $n = (int)$_POST['nilai'];
        if ($d === $k || $n < 1) go('p4.php', 'Transaksi ditolak: akun debit dan kredit harus berbeda dan nilai > 0.');
        q("INSERT INTO jurnal(tanggal,keterangan,debit,kredit,nilai,jenis) VALUES(?,?,?,?,?,'normal')", [$_POST['tanggal'], trim($_POST['ket']), $d, $k, $n]);
        go('p4.php', 'Jurnal tersimpan.');
    }
    $j = row("SELECT * FROM jurnal WHERE id=?", [(int)($_POST['id'] ?? 0)]);
    if (!$j) go('p4.php', 'Jurnal tidak ditemukan.');
    if ($a === 'dup') {   // simulasi kesalahan: transaksi yang sama dicatat dua kali
        q("INSERT INTO jurnal(tanggal,keterangan,debit,kredit,nilai,jenis) VALUES(?,?,?,?,?,'duplikat')", [$j['tanggal'], $j['keterangan'], $j['debit'], $j['kredit'], $j['nilai']]);
        go('p4.php', 'Transaksi dicatat ulang (duplikat) sebagai jurnal #' . db()->lastInsertId() . '. Lihat dampaknya pada saldo.');
    }
    if ($a === 'balik') { // koreksi: jurnal balik, jurnal asli tidak dihapus
        if (val("SELECT COUNT(*) FROM jurnal WHERE jenis='koreksi' AND ref_id=?", [$j['id']])) go('p4.php', 'Jurnal ini sudah dikoreksi.');
        if ($j['jenis'] === 'koreksi') go('p4.php', 'Jurnal koreksi tidak boleh dibalik lagi.');
        q("INSERT INTO jurnal(tanggal,keterangan,debit,kredit,nilai,jenis,ref_id) VALUES(?,?,?,?,?,'koreksi',?)",
          [date('Y-m-d'), 'KOREKSI (jurnal balik) atas #' . $j['id'] . ': ' . $j['keterangan'], $j['kredit'], $j['debit'], $j['nilai'], $j['id']]);
        go('p4.php', 'Jurnal balik dibuat untuk #' . $j['id'] . '. Jurnal asli tetap tersimpan sebagai jejak audit.');
    }
    go('p4.php');
}
$akun = rows("SELECT * FROM akun ORDER BY kode"); $nm = array_column($akun, 'nama', 'kode'); $nrm = array_column($akun, 'normal', 'kode');
$jur = rows("SELECT * FROM jurnal ORDER BY id");
$dibalik = array_column(array_filter($jur, fn($x) => $x['jenis'] === 'koreksi'), 'ref_id');
$bal = array_fill_keys(array_keys($nm), [0, 0]);
foreach ($jur as $x) { $bal[$x['debit']][0] += $x['nilai']; $bal[$x['kredit']][1] += $x['nilai']; }
// deteksi duplikat: transaksi aktif (bukan koreksi, belum dibalik) dengan isi identik
$grp = [];
foreach ($jur as $x) if ($x['jenis'] !== 'koreksi' && !in_array($x['id'], $dibalik)) $grp[$x['tanggal'] . '|' . $x['keterangan'] . '|' . $x['debit'] . '|' . $x['kredit'] . '|' . $x['nilai']][] = $x['id'];
$dup = array_filter($grp, fn($g) => count($g) > 1);
$sel = $_GET['akun'] ?? '1-101'; if (!isset($nm[$sel])) $sel = '1-101';
head('Praktikum 4', 'p4.php'); ?>
<div class="card"><div class="card-body">
<h2 class="h4">Praktikum 4 – Konfigurasi dan Simulasi Modul Keuangan</h2>
<h5>Tahap 1 – Konfigurasi akun</h5>
<?php tabel(['Kode', 'Akun', 'Tipe', 'Saldo normal'], array_map(fn($x) => [h($x['kode']), h($x['nama']), h($x['tipe']), $x['normal'] === 'D' ? 'Debit' : 'Kredit'], $akun)); ?>

<h5>Tahap 2 – Jurnal umum</h5>
<p class="small text-muted">Saldo awal kas Rp30.000.000 dicatat sebagai modal. Lima transaksi pada soal sudah tercatat. Anda dapat menambah transaksi sendiri.</p>
<form method="post" class="row g-2 mb-3"><input type="hidden" name="aksi" value="transaksi">
<div class="col-md-2"><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="form-control" required></div>
<div class="col-md-3"><input name="ket" class="form-control" placeholder="Keterangan" required></div>
<div class="col-md-2"><select name="debit" class="form-select"><?php foreach ($akun as $x) echo '<option value="' . h($x['kode']) . '">Dr ' . h($x['nama']) . '</option>'; ?></select></div>
<div class="col-md-2"><select name="kredit" class="form-select"><?php foreach ($akun as $x) echo '<option value="' . h($x['kode']) . '">Cr ' . h($x['nama']) . '</option>'; ?></select></div>
<div class="col-md-2"><input type="number" name="nilai" min="1" class="form-control" placeholder="Nilai" required></div>
<div class="col-md-1"><button class="btn btn-success w-100">Simpan</button></div></form>
<?php if ($dup): ?><div class="alert alert-danger"><b>Terdeteksi transaksi duplikat</b> pada jurnal #<?= h(implode(', #', array_merge(...array_values($dup)))) ?> (tanggal, akun, keterangan, dan nilai identik). Koreksi dengan jurnal balik.</div><?php endif; ?>
<?php tabel(['#', 'Tanggal', 'Keterangan', 'Debit', 'Kredit', 'Nilai', 'Jenis', 'Ref', 'Tindakan'],
 array_map(function ($x) use ($nm, $dibalik) {
    $t = '<form method="post" class="d-inline"><input type="hidden" name="id" value="' . $x['id'] . '">';
    $btn = $t . '<button name="aksi" value="dup" class="btn btn-sm btn-outline-danger">Catat ulang</button></form> ';
    if ($x['jenis'] !== 'koreksi') $btn .= in_array($x['id'], $dibalik) ? '<span class="badge bg-secondary">sudah dikoreksi</span>' : $t . '<button name="aksi" value="balik" class="btn btn-sm btn-outline-success">Jurnal balik</button></form>';
    return [$x['id'], h($x['tanggal']), h($x['keterangan']), h($nm[$x['debit']]), h($nm[$x['kredit']]), rp($x['nilai']), h($x['jenis']), $x['ref_id'] ? '#' . $x['ref_id'] : '-', $btn];
 }, $jur), 'dtf'); ?>

<h5>Tahap 3 – Buku besar dan neraca saldo</h5>
<form method="get" class="row g-2 mb-2"><div class="col-md-4"><select name="akun" class="form-select" onchange="this.form.submit()"><?php foreach ($akun as $x) echo '<option value="' . h($x['kode']) . '"' . ($x['kode'] === $sel ? ' selected' : '') . '>Buku besar: ' . h($x['nama']) . '</option>'; ?></select></div></form>
<?php $run = 0; $bb = [];
foreach ($jur as $x) { if ($x['debit'] !== $sel && $x['kredit'] !== $sel) continue;
  $d = $x['debit'] === $sel ? $x['nilai'] : 0; $k = $x['kredit'] === $sel ? $x['nilai'] : 0;
  $run += $nrm[$sel] === 'D' ? $d - $k : $k - $d;
  $bb[] = ['#' . $x['id'], h($x['tanggal']), h($x['keterangan']), $d ? rp($d) : '-', $k ? rp($k) : '-', rp($run)]; }
tabel(['Jurnal', 'Tanggal', 'Keterangan', 'Debit', 'Kredit', 'Saldo'], $bb, 'dt'); ?>
<?php $ns = []; $td = 0; $tk = 0;
foreach ($akun as $x) { $n = $bal[$x['kode']][0] - $bal[$x['kode']][1]; if ($n >= 0) $td += $n; else $tk -= $n; $ns[] = [h($x['nama']), $n > 0 ? rp($n) : '-', $n < 0 ? rp(-$n) : '-']; }
?>
<h6>Neraca saldo</h6>
<?php tabel(['Akun', 'Debit', 'Kredit'], $ns); ?>
<div class="alert <?= $td === $tk ? 'alert-success' : 'alert-danger' ?>">Total debit <b><?= rp($td) ?></b> <?= $td === $tk ? '=' : '≠' ?> total kredit <b><?= rp($tk) ?></b> – <?= $td === $tk ? 'seimbang' : 'TIDAK seimbang' ?>.</div>

<h5>Tahap 4 – Verifikasi terhadap hasil yang diharapkan</h5>
<?php $exp = ['1-101' => 30500000, '1-102' => 7000000, '1-103' => 8000000, '2-101' => 5000000, '3-101' => 30000000, '4-101' => 12000000, '6-101' => 1500000];
$vr = []; foreach ($exp as $k => $e) { $n = abs($bal[$k][0] - $bal[$k][1]); $vr[] = [h($nm[$k]), rp($e), rp($n), $n == $e ? '<span class="badge bg-success">Sesuai</span>' : '<span class="badge bg-danger">Selisih ' . rp($n - $e) . '</span>']; }
tabel(['Akun', 'Saldo seharusnya', 'Saldo di sistem', 'Status'], $vr); ?>
<p><b>Analisis:</b> kas = 30.000.000 − 3.000.000 + 5.000.000 − 1.500.000 = <b>Rp30.500.000</b>; piutang = 12.000.000 − 5.000.000 = <b>Rp7.000.000</b>; utang = 8.000.000 − 3.000.000 = <b>Rp5.000.000</b>. Pembelian kredit menaikkan persediaan dan utang tanpa mengubah kas; penjualan kredit menaikkan piutang dan pendapatan, kas baru naik saat pelanggan membayar.</p>
<p><b>Uji kesalahan:</b> klik <i>Catat ulang</i> pada jurnal pembayaran utang (#3). Kas turun ke Rp27.500.000 dan utang menjadi Rp2.000.000 (terlalu kecil), tetapi neraca saldo <u>tetap seimbang</u>, jadi kesalahan ini tidak terlihat dari keseimbangan saja; terdeteksi lewat pemeriksaan duplikat dan tabel verifikasi. Koreksi memakai <i>Jurnal balik</i>, bukan menghapus jurnal, agar jejak audit utuh.</p>
<a class="btn btn-outline-danger btn-sm" href="reset.php?p=4">Reset data praktikum ini</a>
</div></div>
<?php foot();
