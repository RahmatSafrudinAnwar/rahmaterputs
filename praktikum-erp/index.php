<?php require_once __DIR__ . '/layout.php'; head('Beranda', 'index.php'); ?>
<div class="card mb-3"><div class="card-body">
<h1 class="h3">Jawaban Praktikum ERP (PL/4008) – UTS Ganjil 2026/2027</h1>
<p class="mb-1">Dosen: Adie Kusna Wibowo · Program Studi TRPL · Nama/NIM: <em>(isi)</em></p>
<p class="text-muted mb-0">Dibuat dengan PHP + PDO (database MySQL, otomatis dibuat), Bootstrap 5, dan DataTables.</p>
</div></div>
<?php
$m = [['p1.php', 'Praktikum 1 – Evolusi dan Integrasi ERP', 'Database: data master, pesanan pelanggan, dampak lintas departemen.'],
      ['p2.php', 'Praktikum 2 – Arsitektur Teknis ERP', 'Database: hak akses, log akses, backup dan restore database.'],
      ['p3.php', 'Praktikum 3 – Proses Bisnis dan Persetujuan', 'Database: permintaan pembelian, alur persetujuan, log, persetujuan ulang.'],
      ['p4.php', 'Praktikum 4 – Modul Keuangan', 'Database: akun, jurnal, buku besar, neraca saldo, jurnal balik.'],
      ['p5.php', 'Praktikum 5 – SCM dan Pembelian', 'Database: BOM, kebutuhan bersih, PR, PO, penerimaan sebagian, mutasi stok.']];
echo '<div class="row g-3">';
foreach ($m as $x) echo '<div class="col-md-6"><div class="card h-100"><div class="card-body"><h2 class="h5"><a href="' . $x[0] . '">' . h($x[1]) . '</a></h2><p class="mb-0">' . h($x[2]) . '</p></div></div></div>';
echo '</div>';
?>
<div class="card mt-3"><div class="card-body">
<h2 class="h5">Cara menjalankan</h2>
<ol class="mb-2"><li>Laragon/XAMPP: Start Apache dan MySQL, salin folder ke <code>www</code> (Laragon) atau <code>htdocs</code> (XAMPP), buka <code>http://localhost/praktikum-erp</code>.</li>
<li>Database <code>erp_praktikum</code> beserta tabel dan datanya dibuat otomatis (user <code>root</code>, tanpa password; ubah di <code>config.php</code> bila berbeda). Atau impor <code>database.sql</code> lewat phpMyAdmin.</li>
<li>Folder <code>data/backup</code> dipakai untuk demo backup di Praktikum 2 dan harus bisa ditulis.</li></ol>
<a class="btn btn-outline-danger btn-sm" href="reset.php?p=0" onclick="return confirm('Kembalikan semua data ke kondisi awal?')">Reset semua data</a>
</div></div>
<?php foot();
