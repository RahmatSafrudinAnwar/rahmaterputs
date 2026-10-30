<?php
require_once __DIR__ . '/layout.php';

/* Kebutuhan bruto/bersih dari BOM + stok + PO belum diterima (semua dari database) */
function mrp(int $qty, bool $pakaiPO): array {
    $r = [];
    foreach (rows("SELECT b.bahan, b.qty AS per, h.nama, h.stok, h.lead_time FROM bom b JOIN bahan h ON h.kode=b.bahan WHERE b.produk='PRD-X' ORDER BY b.bahan") as $x) {
        $po = $pakaiPO ? (int)val("SELECT COALESCE(SUM(qty-diterima),0) FROM po WHERE bahan=? AND status!='Closed' AND eta_hari<=?", [$x['bahan'], HORIZON]) : 0;
        $bruto = $x['per'] * $qty;
        $r[] = $x + ['bruto' => $bruto, 'po' => $po, 'neto' => max(0, $bruto - $x['stok'] - $po)];
    }
    return $r;
}
$qty = max(1, (int)($_GET['q'] ?? $_POST['q'] ?? 300));
$pakaiPO = isset($_GET['hitung']) ? !empty($_GET['oo']) : (isset($_POST['q']) ? !empty($_POST['oo']) : true);
$self = 'p5.php?hitung=1&q=' . $qty . ($pakaiPO ? '&oo=1' : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['aksi'] ?? '';
    if ($a === 'pr') {
        $n = 0;
        foreach (mrp($qty, $pakaiPO) as $x) {
            if ($x['neto'] > 0 && !val("SELECT COUNT(*) FROM pr WHERE bahan=? AND status='Diajukan'", [$x['bahan']])) {
                q("INSERT INTO pr(tanggal,bahan,qty,status) VALUES(?,?,?,'Diajukan')", [date('Y-m-d'), $x['bahan'], $x['neto']]); $n++;
            }
        }
        go($self, $n ? "$n Purchase Requisition dibuat dari kebutuhan bersih." : 'Tidak ada PR baru (kebutuhan nol atau PR masih diajukan).');
    }
    if ($a === 'po') {
        $p = row("SELECT * FROM pr WHERE id=? AND status='Diajukan'", [(int)$_POST['id']]);
        if (!$p) go($self, 'PR tidak ditemukan atau sudah diproses.');
        $b = row("SELECT * FROM bahan WHERE kode=?", [$p['bahan']]);
        q("INSERT INTO po(kode,pr_id,bahan,pemasok,qty,diterima,eta_hari,status) VALUES(?,?,?,?,?,0,?,'Open')", ['PO-' . str_pad($p['id'], 3, '0', STR_PAD_LEFT), $p['id'], $p['bahan'], $b['pemasok'], $p['qty'], $b['lead_time']]);
        q("UPDATE pr SET status='PO dibuat' WHERE id=?", [$p['id']]);
        go($self, 'PO dibuat ke ' . $b['pemasok'] . ', estimasi tiba ' . $b['lead_time'] . ' hari kerja.');
    }
    if ($a === 'terima') {
        $p = row("SELECT * FROM po WHERE id=?", [(int)$_POST['id']]); $n = (int)$_POST['qty'];
        if (!$p || $p['status'] === 'Closed') go($self, 'PO tidak valid.');
        $sisa = $p['qty'] - $p['diterima'];
        if ($n < 1 || $n > $sisa) go($self, "Penerimaan ditolak: jumlah harus 1–$sisa (tidak boleh melebihi PO, aturan R5).");
        $baru = $p['diterima'] + $n; $st = $baru >= $p['qty'] ? 'Closed' : 'Partially Received';
        q("UPDATE po SET diterima=?, status=? WHERE id=?", [$baru, $st, $p['id']]);
        q("INSERT INTO penerimaan(tanggal,po_id,bahan,qty) VALUES(?,?,?,?)", [date('Y-m-d'), $p['id'], $p['bahan'], $n]);
        q("UPDATE bahan SET stok=stok+? WHERE kode=?", [$n, $p['bahan']]);
        q("INSERT INTO mutasi_stok(tanggal,bahan,qty,keterangan,saldo) VALUES(?,?,?,?,(SELECT stok FROM bahan WHERE kode=?))", [date('Y-m-d'), $p['bahan'], $n, 'Penerimaan ' . $p['kode'] . " ($st)", $p['bahan']]);
        go($self, "Penerimaan $n unit dicatat; status {$p['kode']}: $st; stok bertambah.");
    }
    if ($a === 'telat') {
        q("UPDATE bahan SET lead_time=8 WHERE kode='MAT-B'"); q("UPDATE po SET eta_hari=8 WHERE bahan='MAT-B' AND status!='Closed'");
        go($self, 'Informasi pemasok B diterapkan: barang tiba dalam 8 hari kerja.');
    }
    go($self);
}
$m = mrp($qty, $pakaiPO);
head('Praktikum 5', 'p5.php'); ?>
<div class="card"><div class="card-body">
<h2 class="h4">Praktikum 5 – SCM dan Pembelian Berbasis Kebutuhan Material</h2>
<img class="diagram" src="assets/img/p5_alur.png" alt="Alur kebutuhan material">

<h5>Tahap 1 – Data master dan BOM (dari database)</h5>
<div class="row"><div class="col-lg-7"><?php tabel(['Bahan', 'Nama', 'Satuan', 'Stok', 'Lead time', 'Pemasok', 'Gudang'], array_map(fn($x) => [h($x['kode']), h($x['nama']), h($x['satuan']), $x['stok'], $x['lead_time'] . ' hari kerja', h($x['pemasok']), h($x['gudang'])], rows("SELECT * FROM bahan"))); ?></div>
<div class="col-lg-5"><?php tabel(['Produk jadi', 'Bahan', 'Jumlah per unit'], array_map(fn($x) => [h($x['produk']), h($x['bahan']), $x['qty']], rows("SELECT * FROM bom"))); ?></div></div>
<p class="small text-muted">Aturan pemesanan: lot-for-lot (beli sebesar kebutuhan bersih), tanpa safety stock. Batas waktu produksi: <?= HORIZON ?> hari kerja.</p>

<h5>Tahap 2 – Kebutuhan bruto dan bersih</h5>
<form method="get" class="row g-2 mb-2"><input type="hidden" name="hitung" value="1">
<div class="col-md-3"><label class="form-label">Jumlah produksi (unit)</label><input type="number" name="q" min="1" value="<?= $qty ?>" class="form-control"></div>
<div class="col-md-5 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="oo" value="1" id="oo" <?= $pakaiPO ? 'checked' : '' ?>><label class="form-check-label" for="oo">Pesanan belum diterima ikut dihitung (jika tiba ≤ <?= HORIZON ?> hari)</label></div></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Hitung</button></div></form>
<?php tabel(['Bahan', 'Bruto', 'Stok', 'Pesanan belum diterima', 'Bersih', 'Lead time', 'Status jadwal'],
 array_map(fn($x) => [h($x['nama']), $x['bruto'], $x['stok'], $x['po'], '<b>' . $x['neto'] . '</b>', $x['lead_time'] . ' hari', $x['lead_time'] <= HORIZON ? '<span class="badge bg-success">Cukup waktu (pesan paling lambat hari ke-' . (HORIZON - $x['lead_time']) . ')</span>' : '<span class="badge bg-danger">Terlambat ' . ($x['lead_time'] - HORIZON) . ' hari</span>'], $m)); ?>
<p class="small"><b>Asumsi:</b> bruto = jumlah produksi × BOM; bersih = bruto − stok − pesanan belum diterima yang tiba tepat waktu. Untuk 300 unit: A = 900 − 400 − 100 = 400; B = 600 − 250 = 350.</p>

<h5>Tahap 3 – Permintaan pembelian (PR) dan PO</h5>
<form method="post" class="mb-2"><input type="hidden" name="aksi" value="pr"><input type="hidden" name="q" value="<?= $qty ?>"><?php if ($pakaiPO) echo '<input type="hidden" name="oo" value="1">'; ?><button class="btn btn-success">Buat PR dari kebutuhan bersih</button></form>
<?php tabel(['PR', 'Tanggal', 'Bahan', 'Jumlah', 'Status', ''], array_map(fn($x) => ['PR-' . str_pad($x['id'], 3, '0', STR_PAD_LEFT), h($x['tanggal']), h($x['bahan']), $x['qty'], h($x['status']),
 $x['status'] === 'Diajukan' ? '<form method="post"><input type="hidden" name="aksi" value="po"><input type="hidden" name="id" value="' . $x['id'] . '"><input type="hidden" name="q" value="' . $qty . '">' . ($pakaiPO ? '<input type="hidden" name="oo" value="1">' : '') . '<button class="btn btn-sm btn-primary">Buat PO</button></form>' : '-'], rows("SELECT * FROM pr ORDER BY id DESC")), 'dtf', 'tpr'); ?>

<h5>Tahap 4 – Simulasi penerimaan barang (sebagian atau penuh)</h5>
<?php tabel(['PO', 'Bahan', 'Pemasok', 'Dipesan', 'Diterima', 'Sisa', 'ETA', 'Status', 'Terima'],
 array_map(fn($x) => [h($x['kode']), h($x['bahan']), h($x['pemasok']), $x['qty'], $x['diterima'], $x['qty'] - $x['diterima'], $x['eta_hari'] . ' hari', '<span class="badge bg-' . ($x['status'] === 'Closed' ? 'success' : ($x['status'] === 'Open' ? 'secondary' : 'warning text-dark')) . '">' . h($x['status']) . '</span>',
 $x['status'] === 'Closed' ? '-' : '<form method="post" class="d-flex gap-1"><input type="hidden" name="aksi" value="terima"><input type="hidden" name="id" value="' . $x['id'] . '"><input type="hidden" name="q" value="' . $qty . '">' . ($pakaiPO ? '<input type="hidden" name="oo" value="1">' : '') . '<input type="number" name="qty" min="1" max="' . ($x['qty'] - $x['diterima']) . '" value="' . ($x['qty'] - $x['diterima']) . '" class="form-control form-control-sm" style="width:90px"><button class="btn btn-sm btn-primary">Terima</button></form>'], rows("SELECT * FROM po ORDER BY id DESC")), 'dtf'); ?>
<h6>Laporan perubahan stok (mutasi)</h6>
<?php tabel(['Tanggal', 'Bahan', 'Perubahan', 'Keterangan', 'Saldo'], array_map(fn($x) => [h($x['tanggal']), h($x['bahan']), ($x['qty'] > 0 ? '+' : '') . $x['qty'], h($x['keterangan']), $x['saldo']], rows("SELECT * FROM mutasi_stok ORDER BY id DESC")), 'dtf'); ?>

<h5>Tahap 5 – Kondisi baru: pemasok B baru bisa mengirim dalam 8 hari kerja</h5>
<form method="post" class="mb-2"><input type="hidden" name="aksi" value="telat"><input type="hidden" name="q" value="<?= $qty ?>"><?php if ($pakaiPO) echo '<input type="hidden" name="oo" value="1">'; ?><button class="btn btn-warning">Terapkan informasi pemasok B (8 hari)</button></form>
<?php
$ltB = (int)val("SELECT lead_time FROM bahan WHERE kode='MAT-B'");
$bisa = PHP_INT_MAX; foreach (rows("SELECT b.qty per, h.stok FROM bom b JOIN bahan h ON h.kode=b.bahan WHERE b.produk='PRD-X'") as $x) $bisa = min($bisa, intdiv($x['stok'], $x['per']));
$mb = array_values(array_filter($m, fn($x) => $x['bahan'] === 'MAT-B'))[0] ?? null; ?>
<div class="alert alert-secondary">Lead time bahan B saat ini <b><?= $ltB ?> hari</b> vs batas produksi <b><?= HORIZON ?> hari</b> → terlambat <b><?= max(0, $ltB - HORIZON) ?> hari</b>.
Dengan stok sekarang, produksi dapat dimulai untuk <b><?= $bisa ?> unit</b>; sisa <b><?= max(0, $qty - $bisa) ?> unit</b> menunggu bahan.</div>
<?php tabel(['Opsi', 'Waktu', 'Biaya', 'Ketersediaan', 'Risiko produksi'], [
 ['Pemasok alternatif', 'Bisa ≤ 6 hari bila stok ready', 'Harga lebih tinggi', 'Perlu konfirmasi', 'Sedang (kualitas belum teruji)'],
 ['Transfer dari lokasi lain', '1–2 hari', 'Rendah (ongkos kirim)', 'Tergantung stok lokasi lain', 'Rendah'],
 ['Jadwal ulang produksi', 'Selesai hari ke-8 atau lebih', 'Rendah, risiko denda/komplain', 'Pasti', 'Sedang (pelanggan terlambat)'],
 ['Tetap menunggu pemasok B', 'Hari ke-8', 'Rendah', 'Pasti', 'Tinggi (terlambat 2 hari)']]); ?>
<div class="alert alert-success"><b>Keputusan (kombinasi):</b> (1) mulai produksi sekarang untuk <?= $bisa ?> unit dengan stok yang ada; (2) cari transfer bahan B dari lokasi lain atau pemasok alternatif untuk sisa kebutuhan (<?= $mb ? $mb['neto'] : 350 ?> unit B) agar tiba ≤ hari ke-5; (3) pertahankan PO ke pemasok semula sebagai cadangan; (4) bila (2) gagal sampai hari ke-2, jadwal ulang sisa <?= max(0, $qty - $bisa) ?> unit dan beri tahu pelanggan. Opsi ini menjaga waktu dan ketersediaan dengan biaya terkendali.</div>
<a class="btn btn-outline-danger btn-sm" href="reset.php?p=5">Reset data praktikum ini</a>
</div></div>
<?php foot();
