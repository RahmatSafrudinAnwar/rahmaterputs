<?php
require_once __DIR__ . '/layout.php';
$bdir = __DIR__ . '/data/backup';
$menu = ['Penjualan', 'Gudang', 'Produksi', 'Pembelian', 'Keuangan'];
$aksiMap = ['C' => 'Create', 'R' => 'Read', 'U' => 'Update', 'A' => 'Approve'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['aksi'] ?? '';
    if ($a === 'backup') {            // backup: ekspor seluruh tabel ke file .sql
        if (!is_dir($bdir)) mkdir($bdir, 0777, true);
        $f = 'erp_' . date('Ymd_His') . '.sql';
        file_put_contents($bdir . '/' . $f, dump_sql());
        go('p2.php', "Backup dibuat: $f");
    }
    if ($a === 'restore') {           // restore: jalankan ulang file .sql dari folder backup
        $f = basename($_POST['file'] ?? '');
        if ($f !== '' && is_file($bdir . '/' . $f)) { restore_sql(file_get_contents($bdir . '/' . $f)); go('p2.php', "Database dipulihkan dari $f"); }
        go('p2.php', 'File backup tidak ditemukan.');
    }
    if ($a === 'uji') {               // uji hak akses + catat di log
        $dep = $_POST['dep']; $mn = $_POST['menu']; $ak = $_POST['ak'];
        $hak = (string)val("SELECT hak FROM hak_akses WHERE departemen=? AND menu=?", [$dep, $mn]);
        $ok = $hak !== '' && strpos($hak, $ak) !== false;
        $hasil = $ok ? 'DIIZINKAN' : 'DITOLAK';
        q("INSERT INTO log_akses(waktu,departemen,menu,aksi,hasil) VALUES(?,?,?,?,?)", [date('Y-m-d H:i:s'), $dep, $mn, $aksiMap[$ak] ?? $ak, $hasil]);
        go('p2.php', "$dep → $mn ({$aksiMap[$ak]}): $hasil" . ($ok ? '' : '. Percobaan dicatat di log audit.'));
    }
    go('p2.php');
}
$gangguan = [
 ['Database tidak dapat diakses', 'Periksa layanan database dan disk/jaringan. Aktifkan replika sebagai pengganti; bila tidak ada, restore backup terakhir ditambah log transaksi ke server pengganti. Kunci input transaksi selama pemulihan, validasi saldo stok dan neraca saldo, lalu buka kembali. Catat sebagai insiden.'],
 ['Server aplikasi mengalami gangguan', 'Load balancer memindahkan lalu lintas ke server aplikasi 2 (standby). Data aman karena database tetap aktif. Server 1 diperbaiki (restart layanan, cek log) lalu dikembalikan ke kelompok.'],
 ['Koneksi internet terputus', 'Pengguna di kantor tetap bekerja lewat LAN; pengguna jarak jauh beralih ke jalur ISP cadangan untuk VPN. Transaksi penting dicatat sementara di formulir resmi, lalu diinput setelah koneksi pulih dengan referensi dokumen.'],
 ['Pengguna tidak berwenang membuka menu keuangan', 'Sistem menolak akses berdasarkan peran dan mencatatnya di log audit (coba di formulir uji akses di bawah). Administrator memeriksa akun, mereset kata sandi bila dicurigai bocor, dan mengaktifkan MFA.'],
 ['Data hilang karena backup tidak berjalan', 'Cari backup valid terakhir, restore ke lingkungan uji, lalu input ulang transaksi sejak backup dari dokumen sumber. Perbaiki penyebab backup gagal, aktifkan notifikasi, dan jadwalkan uji restore.']];
$g = (int)($_GET['g'] ?? 0); $g = isset($gangguan[$g]) ? $g : 0;
head('Praktikum 2', 'p2.php'); ?>
<div class="card"><div class="card-body">
<h2 class="h4">Praktikum 2 – Arsitektur Teknis ERP (100 pengguna)</h2>
<h5>1. Diagram arsitektur</h5>
<img class="diagram" src="assets/img/p2_arsitektur.png" alt="Arsitektur ERP">
<p class="mt-2"><b>Alasan rancangan:</b> tiga lapis (pengguna – aplikasi – database) agar pengguna tidak menyentuh database langsung. Dua server aplikasi menghilangkan single point of failure, dan backup tiga lapis (replika, NAS, luar kantor) melindungi dari kerusakan disk maupun bencana lokasi.</p>

<h5>2. Matriks hak akses (dari tabel <code>hak_akses</code>; C=Create, R=Read, U=Update, A=Approve, –=tidak ada)</h5>
<?php
$m = [];
foreach (rows("SELECT * FROM hak_akses") as $x) $m[$x['departemen']][$x['menu']] = $x['hak'];
$keb = ['Penjualan' => 'Tidak boleh ubah harga tanpa persetujuan', 'Gudang' => 'Penerimaan wajib mengacu PO', 'Produksi' => 'Hanya BOM yang disetujui dipakai',
        'Pembelian' => 'PO di atas batas butuh Approve', 'Keuangan' => 'Pemisahan tugas, log audit, MFA'];
$br = [];
foreach ($m as $dep => $r) { $l = [h($dep)]; foreach ($menu as $mn) $l[] = h($r[$mn] ?? '-'); $l[] = h($keb[$dep] ?? ''); $br[] = $l; }
tabel(array_merge(['Departemen'], $menu, ['Kebutuhan keamanan']), $br); ?>

<h6>Uji hak akses</h6>
<form method="post" class="row g-2 mb-3"><input type="hidden" name="aksi" value="uji">
<div class="col-md-3"><select name="dep" class="form-select"><?php foreach ($menu as $x) echo "<option>" . h($x) . "</option>"; ?></select></div>
<div class="col-md-3"><select name="menu" class="form-select"><?php foreach ($menu as $x) echo "<option>" . h($x) . "</option>"; ?></select></div>
<div class="col-md-3"><select name="ak" class="form-select"><?php foreach ($aksiMap as $k => $t) echo "<option value=\"$k\">$t</option>"; ?></select></div>
<div class="col-md-3"><button class="btn btn-primary w-100">Coba akses</button></div></form>
<?php tabel(['Waktu', 'Departemen', 'Menu', 'Aksi', 'Hasil'], array_map(fn($x) => [h($x['waktu']), h($x['departemen']), h($x['menu']), h($x['aksi']), $x['hasil'] === 'DITOLAK' ? '<span class="badge bg-danger">DITOLAK</span>' : '<span class="badge bg-success">DIIZINKAN</span>'], rows("SELECT * FROM log_akses ORDER BY id DESC")), 'dtf'); ?>

<h5>3. Pencadangan dan pemulihan</h5>
<?php tabel(['Jenis', 'Jadwal', 'Lokasi', 'Retensi'], [['Full backup database', 'Mingguan (Minggu malam)', 'NAS + cloud terenkripsi', '8 minggu'], ['Differential', 'Harian (23.00)', 'NAS + cloud', '14 hari'], ['Log transaksi', 'Tiap 15 menit', 'NAS', '7 hari']]); ?>
<p><b>Prosedur pemulihan:</b> (1) hentikan akses pengguna, (2) restore full backup terakhir ke server pengganti, (3) terapkan differential dan log transaksi sampai titik waktu sehat terakhir, (4) validasi saldo stok dan neraca saldo, (5) buka akses dan catat insiden. Target RPO ≤ 15 menit, RTO ≤ 4 jam.</p>
<h6>Demonstrasi backup dan restore database</h6>
<form method="post" class="mb-2"><input type="hidden" name="aksi" value="backup"><button class="btn btn-success">Buat backup sekarang</button></form>
<?php $bk = is_dir($bdir) ? array_reverse(glob($bdir . '/*.sql')) : [];
tabel(['File backup', 'Ukuran', 'Waktu', ''], array_map(fn($f) => [h(basename($f)), round(filesize($f) / 1024, 1) . ' KB', date('Y-m-d H:i:s', filemtime($f)),
  '<form method="post" onsubmit="return confirm(\'Pulihkan database dari backup ini? Data saat ini akan ditimpa.\')"><input type="hidden" name="aksi" value="restore"><input type="hidden" name="file" value="' . h(basename($f)) . '"><button class="btn btn-sm btn-warning">Restore</button></form>'], $bk), 'dtf'); ?>
<p class="small text-muted">Uji: buat backup, ubah data (misalnya tambah Sales Order di Praktikum 1), lalu restore dan lihat data kembali seperti saat backup.</p>

<h5>4. Aliran data transaksi pembelian</h5>
<p>Staf Pembelian membuka ERP lewat browser → Firewall/VPN → Load balancer → Server aplikasi (cek login dan hak akses, validasi pemasok dan anggaran) → Database menyimpan PO → Gudang membuat penerimaan barang mengacu PO (stok bertambah) → Keuangan mencocokkan PO, penerimaan, dan faktur lalu jurnal utang terbentuk → log transaksi dan backup mengamankan data (alur ini dijalankan di Praktikum 3 dan 5).</p>

<h5>5. Potensi kegagalan teknis</h5>
<?php tabel(['Kegagalan', 'Dampak operasional', 'Pencegahan'], [
 ['Server aplikasi mati', 'Semua departemen berhenti transaksi', 'Server standby + load balancer'],
 ['Database rusak / tidak dapat diakses', 'Seluruh data tidak tersedia, transaksi tertunda', 'Replika DB, backup teruji, monitoring'],
 ['Backup tidak berjalan', 'Data hilang permanen saat insiden', 'Notifikasi gagal backup, uji restore berkala'],
 ['Koneksi internet/VPN putus', 'Pengguna jarak jauh tidak bisa bekerja', 'Jalur ISP cadangan'],
 ['Akses tidak sah', 'Kebocoran data keuangan', 'Role-based access, MFA, log audit']]); ?>

<h5>6. Simulasi gangguan</h5>
<form method="get" class="row g-2 mb-2"><div class="col-md-8"><select name="g" class="form-select" onchange="this.form.submit()"><?php foreach ($gangguan as $i => $x) echo '<option value="' . $i . '"' . ($i === $g ? ' selected' : '') . '>' . h($x[0]) . '</option>'; ?></select></div></form>
<div class="alert alert-warning"><b><?= h($gangguan[$g][0]) ?></b><br><?= h($gangguan[$g][1]) ?></div>
<a class="btn btn-outline-danger btn-sm" href="reset.php?p=2">Reset data praktikum ini</a>
</div></div>
<?php foot();
