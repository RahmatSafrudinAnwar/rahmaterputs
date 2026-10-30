<?php
require_once __DIR__ . '/config.php';
function head(string $judul, string $aktif = '') {
    $nav = ['index.php' => 'Beranda', 'p1.php' => 'Praktikum 1', 'p2.php' => 'Praktikum 2', 'p3.php' => 'Praktikum 3', 'p4.php' => 'Praktikum 4', 'p5.php' => 'Praktikum 5'];
    ?><!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($judul) ?> – Praktikum ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>body{background:#f4f6fa}.card{border:0;box-shadow:0 1px 4px rgba(0,0,0,.08)}h5{color:#0d3b66;margin-top:1.4rem}.diagram{max-width:100%;height:auto;border:1px solid #dee2e6;border-radius:6px;background:#fff;padding:6px}</style>
</head><body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:#0d3b66"><div class="container">
<a class="navbar-brand" href="index.php">Praktikum ERP</a>
<button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nv"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="nv"><ul class="navbar-nav">
<?php foreach ($nav as $u => $t): ?><li class="nav-item"><a class="nav-link <?= $aktif === $u ? 'active fw-bold' : '' ?>" href="<?= $u ?>"><?= $t ?></a></li><?php endforeach; ?>
</ul></div></div></nav>
<div class="container py-4">
<?php if (!empty($_GET['msg'])): ?><div class="alert alert-info alert-dismissible"><?= h($_GET['msg']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php }
function foot() { ?>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
const LANG={search:'Cari:',lengthMenu:'Tampil _MENU_',info:'_START_–_END_ dari _TOTAL_',emptyTable:'Belum ada data',zeroRecords:'Tidak ditemukan',paginate:{previous:'‹',next:'›'}};
$('table.dt').DataTable({language:LANG,paging:false,searching:false,info:false,ordering:false});
$('table.dtf').DataTable({language:LANG,pageLength:10,order:[]});
</script></body></html>
<?php }
/* Tabel generik: $kolom = ['Judul', ...], $baris = array of array (nilai sudah di-escape bila perlu) */
function tabel(array $kolom, array $baris, string $kelas = 'dt', string $id = '') {
    echo '<div class="table-responsive"><table ' . ($id ? 'id="' . $id . '" ' : '') . 'class="table table-striped table-bordered ' . $kelas . '" style="width:100%"><thead><tr>';
    foreach ($kolom as $k) echo '<th>' . $k . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($baris as $r) { echo '<tr>'; foreach ($r as $c) echo '<td>' . $c . '</td>'; echo '</tr>'; }
    echo '</tbody></table></div>';
}
