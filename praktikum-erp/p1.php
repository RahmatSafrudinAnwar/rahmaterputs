<?php
require_once __DIR__ . '/layout.php';
$kond = ['normal' => 'Kondisi normal', 'stok_kurang' => 'Stok bahan baku tidak mencukupi', 'ubah_jumlah' => 'Pelanggan mengubah jumlah pesanan',
         'bayar_belum' => 'Pembayaran pelanggan belum diterima', 'produksi_telat' => 'Produksi mengalami keterlambatan'];

/* Hitung kebutuhan bahan dan dampak sebuah pesanan dari data di database */
function kebutuhan(string $produk, int $qty): array {
    $r = [];
    foreach (rows("SELECT b.bahan, b.qty, h.nama, h.stok FROM bom b JOIN bahan h ON h.kode=b.bahan WHERE b.produk=?", [$produk]) as $x)
        $r[] = ['kode' => $x['bahan'], 'nama' => $x['nama'], 'perlu' => $x['qty'] * $qty, 'stok' => (int)$x['stok'], 'kurang' => max(0, $x['qty'] * $qty - $x['stok'])];
    return $r;
}
function status_pesanan(array $keb, string $kondisi): string {
    $kurang = array_sum(array_column($keb, 'kurang'));
    if ($kondisi === 'produksi_telat') return 'Produksi terlambat';
    if ($kondisi === 'bayar_belum') return $kurang ? 'Menunggu bahan; piutang terbuka' : 'Dikirim; piutang terbuka';
    return $kurang ? 'Menunggu bahan baku' : 'Siap diproduksi';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['aksi'] ?? '';
    if ($a === 'order') {
        $qty = max(1, (int)$_POST['qty']);
        $k = isset($kond[$_POST['kondisi'] ?? '']) ? $_POST['kondisi'] : 'normal';
        $hrg = (int)val("SELECT harga FROM produk WHERE kode='PRD-X'");
        $st = status_pesanan(kebutuhan('PRD-X', $qty), $k);
        q("INSERT INTO pesanan(tanggal,pelanggan,produk,qty,nilai,kondisi,status) VALUES(?,?,?,?,?,?,?)",
          [date('Y-m-d H:i'), $_POST['pelanggan'], 'PRD-X', $qty, $qty * $hrg, $k, $st]);
        go('p1.php?id=' . db()->lastInsertId(), 'Sales Order tersimpan; modul gudang, produksi, dan keuangan diperbarui dari satu data.');
    }
    if ($a === 'ubah') {
        $id = (int)$_POST['id']; $qty = max(1, (int)$_POST['qty']);
        $o = row("SELECT * FROM pesanan WHERE id=?", [$id]);
        if ($o) {
            $hrg = (int)val("SELECT harga FROM produk WHERE kode=?", [$o['produk']]);
            q("UPDATE pesanan SET qty=?, nilai=?, kondisi='ubah_jumlah', status=? WHERE id=?",
              [$qty, $qty * $hrg, status_pesanan(kebutuhan($o['produk'], $qty), 'ubah_jumlah') . ' (direvisi)', $id]);
        }
        go('p1.php?id=' . $id, 'Jumlah pesanan direvisi; kebutuhan bahan, jadwal produksi, dan nilai piutang dihitung ulang.');
    }
    go('p1.php');
}
$id = (int)($_GET['id'] ?? 0) ?: (int)val("SELECT MAX(id) FROM pesanan");
$o = $id ? row("SELECT p.*, c.nama AS cust FROM pesanan p JOIN pelanggan c ON c.kode=p.pelanggan WHERE p.id=?", [$id]) : null;

head('Praktikum 1', 'p1.php'); ?>
<div class="card"><div class="card-body">
<h2 class="h4">Praktikum 1 – Evolusi dan Integrasi ERP</h2>
<h5>1. Hubungan data yang belum terintegrasi</h5>
<?php tabel(['Departemen', 'Sistem saat ini', 'Masalah', 'Data yang terputus'], [
 ['Penjualan', 'Spreadsheet', 'Data pesanan terlambat diperbarui', 'Pesanan tidak otomatis menjadi kebutuhan produksi dan stok'],
 ['Gudang', 'Aplikasi stok', 'Stok berbeda dengan catatan pembelian', 'Penerimaan dan pemakaian bahan tidak terhubung ke pembelian/produksi'],
 ['Produksi', 'Formulir manual', 'Jadwal produksi sering berubah', 'Jadwal tidak mengacu pada pesanan nyata dan stok bahan'],
 ['Keuangan', 'Aplikasi akuntansi', 'Transaksi harus dimasukkan ulang', 'Faktur, penerimaan, dan pembayaran dicatat ulang manual']]); ?>
<div class="row"><div class="col-lg-6"><h5>2. Diagram sebelum ERP</h5><img class="diagram" src="assets/img/p1_sebelum.png" alt="Diagram sebelum ERP"></div>
<div class="col-lg-6"><h5>3. Diagram sesudah ERP</h5><img class="diagram" src="assets/img/p1_sesudah.png" alt="Diagram sesudah ERP"></div></div>

<h5>4. Data master (dibaca dari database)</h5>
<div class="row"><div class="col-lg-6"><h6>Produk</h6><?php tabel(['Kode', 'Nama', 'Satuan', 'Harga'], array_map(fn($x) => [h($x['kode']), h($x['nama']), h($x['satuan']), rp($x['harga'])], rows("SELECT * FROM produk"))); ?>
<h6>Pelanggan</h6><?php tabel(['Kode', 'Nama', 'Syarat bayar'], array_map(fn($x) => [h($x['kode']), h($x['nama']), h($x['syarat_bayar'])], rows("SELECT * FROM pelanggan"))); ?>
<h6>Pemasok dan gudang</h6><?php tabel(['Kode', 'Nama'], array_map(fn($x) => [h($x['kode']), h($x['nama'])], rows("SELECT * FROM pemasok UNION ALL SELECT * FROM gudang"))); ?></div>
<div class="col-lg-6"><h6>Bahan baku</h6><?php tabel(['Kode', 'Nama', 'Stok', 'Lead time', 'Pemasok'], array_map(fn($x) => [h($x['kode']), h($x['nama']), $x['stok'], $x['lead_time'] . ' hari', h($x['pemasok'])], rows("SELECT * FROM bahan"))); ?>
<h6>Bill of Materials (1 unit Produk X)</h6><?php tabel(['Produk', 'Bahan', 'Jumlah'], array_map(fn($x) => [h($x['produk']), h($x['bahan']), $x['qty']], rows("SELECT * FROM bom"))); ?>
<p class="small text-muted">Data master dipakai bersama oleh Penjualan (produk, pelanggan), Gudang (bahan, gudang), Produksi (BOM), Pembelian (pemasok), dan Keuangan (pelanggan, produk). Satu sumber data mencegah kode dan harga ganda.</p></div></div>

<h5>5. Simulasi transaksi pesanan pelanggan</h5>
<form method="post" class="row g-2 mb-3"><input type="hidden" name="aksi" value="order">
<div class="col-md-3"><label class="form-label">Pelanggan</label><select name="pelanggan" class="form-select"><?php foreach (rows("SELECT * FROM pelanggan") as $c) echo '<option value="' . h($c['kode']) . '">' . h($c['nama']) . '</option>'; ?></select></div>
<div class="col-md-2"><label class="form-label">Jumlah (unit)</label><input type="number" name="qty" min="1" value="100" class="form-control" required></div>
<div class="col-md-5"><label class="form-label">Kondisi (tantangan)</label><select name="kondisi" class="form-select"><?php foreach ($kond as $k => $t) echo '<option value="' . $k . '">' . h($t) . '</option>'; ?></select></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Buat Sales Order</button></div></form>

<?php if ($o):
  $keb = kebutuhan($o['produk'], (int)$o['qty']); $kurang = array_sum(array_column($keb, 'kurang')); ?>
<div class="alert alert-secondary">Dampak <b>SO-<?= $o['id'] ?></b> – <?= h($o['cust']) ?>, <?= $o['qty'] ?> unit, nilai <?= rp($o['nilai']) ?>, kondisi: <b><?= h($kond[$o['kondisi']] ?? $o['kondisi']) ?></b></div>
<?php
 $bhn = implode('<br>', array_map(fn($b) => h($b['nama']) . ': perlu ' . $b['perlu'] . ', stok ' . $b['stok'] . ($b['kurang'] ? ' <b class="text-danger">(kurang ' . $b['kurang'] . ')</b>' : ' (cukup)'), $keb));
 $dmp = [['Penjualan', 'Sales Order ' . $o['qty'] . ' unit, nilai ' . rp($o['nilai']), 'SO-' . $o['id']],
         ['Gudang', $bhn . ($kurang ? '<br>Sistem membuat kebutuhan pembelian' : '<br>Bahan dialokasikan'), 'Reservasi stok / PR'],
         ['Produksi', 'Production Order ' . $o['qty'] . ' unit; status: ' . h($o['status']), 'PROD-' . $o['id']],
         ['Keuangan', 'Piutang ' . rp($o['nilai']) . ' dan pendapatan ' . rp($o['nilai']) . ($o['kondisi'] === 'bayar_belum' ? ' (belum dibayar, umur piutang dipantau)' : ''), 'Dr Piutang / Cr Penjualan']];
 tabel(['Modul', 'Dampak', 'Dokumen / jurnal'], $dmp);
 $info = ['normal' => 'Semua modul terhubung dari satu Sales Order tanpa input ulang.',
  'stok_kurang' => 'Terdampak: Gudang, Produksi, Pembelian. Produksi menunggu bahan, sistem memunculkan kebutuhan pembelian, dan tanggal janji ke pelanggan dihitung ulang.',
  'ubah_jumlah' => 'Terdampak: Penjualan, Gudang (reservasi), Produksi (jumlah order), Keuangan (nilai piutang). Semua menyesuaikan otomatis dari revisi satu Sales Order.',
  'bayar_belum' => 'Terdampak: Keuangan dan Penjualan. Piutang tetap terbuka dan pengiriman berikutnya dapat ditahan (credit hold). Stok dan produksi tidak berubah.',
  'produksi_telat' => 'Terdampak: Produksi, Penjualan, Gudang. Jadwal produksi diperbarui, tanggal kirim di Sales Order direvisi, dan pelanggan diberi tahu. Keuangan baru berubah saat barang dikirim.'];
 echo '<div class="alert alert-info">' . h($info[$o['kondisi']] ?? '') . '</div>'; ?>
<form method="post" class="row g-2 mb-3"><input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id" value="<?= $o['id'] ?>">
<div class="col-md-3"><label class="form-label">Revisi jumlah SO-<?= $o['id'] ?></label><input type="number" name="qty" min="1" value="<?= $o['qty'] ?>" class="form-control"></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100">Revisi</button></div></form>
<?php endif; ?>

<h6>Riwayat Sales Order (database)</h6>
<?php tabel(['No', 'Tanggal', 'Pelanggan', 'Jumlah', 'Nilai', 'Kondisi', 'Status', ''], array_map(fn($x) => ['SO-' . $x['id'], h($x['tanggal']), h($x['pelanggan']), $x['qty'], rp($x['nilai']), h($kond[$x['kondisi']] ?? ''), h($x['status']), '<a href="p1.php?id=' . $x['id'] . '">Lihat dampak</a>'], rows("SELECT * FROM pesanan ORDER BY id DESC")), 'dtf'); ?>

<h5>6. Satu masalah dan solusinya</h5>
<p><b>Masalah:</b> stok gudang berbeda dengan catatan pembelian karena penerimaan dan pemakaian bahan dicatat terpisah. <b>Solusi:</b> penerimaan barang wajib mengacu pada PO dan pengeluaran bahan wajib mengacu pada Production Order di ERP, sehingga stok hanya berubah lewat transaksi tercatat (lihat mutasi stok di Praktikum 5).</p>
<a class="btn btn-outline-danger btn-sm" href="reset.php?p=1">Reset data praktikum ini</a>
</div></div>
<?php foot();
