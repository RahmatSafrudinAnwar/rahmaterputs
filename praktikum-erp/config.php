<?php
/* Koneksi database MySQL (Laragon: user root, password kosong). Database dan tabel dibuat otomatis
   saat halaman pertama dibuka. Alternatif manual: impor file database.sql lewat phpMyAdmin. */
const DB_HOST = '127.0.0.1';
const DB_NAME = 'erp_praktikum';
const DB_USER = 'root';
const DB_PASS = '';
const HORIZON = 6; // batas waktu produksi (hari kerja)
const TABEL = ['pelanggan','produk','gudang','pemasok','bahan','bom','pesanan','hak_akses','log_akses','permintaan','log_persetujuan','akun','jurnal','pr','po','penerimaan','mutasi_stok'];

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
    try {
        $awal = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
        $awal->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4');
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
    } catch (PDOException $e) {
        die('<h3>Tidak dapat terhubung ke MySQL</h3><p>Pastikan MySQL di Laragon sudah <b>Start</b> dan user/password di config.php benar.</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
    }
    if (!$pdo->query("SHOW TABLES LIKE 'jurnal'")->fetch()) {
        try { seed_master($pdo); seed_p1($pdo); seed_p2($pdo); seed_p3($pdo); seed_p4($pdo); seed_p5($pdo); }
        catch (Throwable $e) { $pdo = null; throw $e; }
    }
    return $pdo;
}
function run(PDO $d, string $sql) { foreach (explode(';', $sql) as $st) if (trim($st) !== '') $d->exec($st); }
/* Backup = file .sql berisi DELETE + INSERT semua tabel; restore = menjalankan ulang file tersebut */
function dump_sql(): string {
    $d = db(); $o = "-- Backup " . date('Y-m-d H:i:s') . "\n";
    foreach (TABEL as $t) {
        $o .= "DELETE FROM `$t`;\n";
        foreach ($d->query("SELECT * FROM `$t`") as $r) {
            $v = array_map(fn($x) => $x === null ? 'NULL' : $d->quote((string)$x), array_values($r));
            $o .= "INSERT INTO `$t` (`" . implode('`,`', array_keys($r)) . "`) VALUES (" . implode(',', $v) . ");\n";
        }
    }
    return $o;
}
function restore_sql(string $sql) { $d = db(); foreach (preg_split('/\R/', $sql) as $l) { $l = trim($l); if ($l !== '' && strpos($l, '--') !== 0) $d->exec($l); } }
function q($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s; }
function rows($sql, $p = []) { return q($sql, $p)->fetchAll(); }
function row($sql, $p = []) { return q($sql, $p)->fetch(); }
function val($sql, $p = []) { return q($sql, $p)->fetchColumn(); }
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n) { return 'Rp' . number_format((float)$n, 0, ',', '.'); }
function go($url, $msg = '') { header('Location: ' . $url . ($msg ? (strpos($url, '?') ? '&' : '?') . 'msg=' . urlencode($msg) : '')); exit; }

/* ---------- Data master (dipakai Praktikum 1 dan 5) ---------- */
function seed_master(PDO $d) {
    run($d,"DROP TABLE IF EXISTS pelanggan; DROP TABLE IF EXISTS produk; DROP TABLE IF EXISTS gudang; DROP TABLE IF EXISTS pemasok;
    DROP TABLE IF EXISTS bahan; DROP TABLE IF EXISTS bom;
    CREATE TABLE pelanggan(kode VARCHAR(30) PRIMARY KEY, nama VARCHAR(255), syarat_bayar VARCHAR(255));
    CREATE TABLE produk(kode VARCHAR(30) PRIMARY KEY, nama VARCHAR(255), satuan VARCHAR(255), harga INT);
    CREATE TABLE gudang(kode VARCHAR(30) PRIMARY KEY, nama VARCHAR(255));
    CREATE TABLE pemasok(kode VARCHAR(30) PRIMARY KEY, nama VARCHAR(255));
    CREATE TABLE bahan(kode VARCHAR(30) PRIMARY KEY, nama VARCHAR(255), satuan VARCHAR(255), stok INT, lead_time INT, pemasok VARCHAR(255), gudang VARCHAR(255));
    CREATE TABLE bom(produk VARCHAR(255), bahan VARCHAR(255), qty INT);
    INSERT INTO pelanggan VALUES('C01','PT Maju Jaya','Kredit 30 hari'),('C02','CV Sinar Abadi','Tunai');
    INSERT INTO produk VALUES('PRD-X','Produk X','unit',120000);
    INSERT INTO gudang VALUES('WH-01','Gudang Bahan Baku');
    INSERT INTO pemasok VALUES('SUP-A','Pemasok Bahan A'),('SUP-B','Pemasok Bahan B');
    INSERT INTO bahan VALUES('MAT-A','Bahan A','unit',400,4,'SUP-A','WH-01'),('MAT-B','Bahan B','unit',250,7,'SUP-B','WH-01');
    INSERT INTO bom VALUES('PRD-X','MAT-A',3),('PRD-X','MAT-B',2);");
}
function seed_p1(PDO $d) {
    run($d,"DROP TABLE IF EXISTS pesanan;
    CREATE TABLE pesanan(id INT AUTO_INCREMENT PRIMARY KEY, tanggal VARCHAR(255), pelanggan VARCHAR(255), produk VARCHAR(255), qty INT, nilai INT, kondisi VARCHAR(255), status VARCHAR(255));");
}
function seed_p2(PDO $d) {
    run($d,"DROP TABLE IF EXISTS hak_akses; DROP TABLE IF EXISTS log_akses;
    CREATE TABLE hak_akses(departemen VARCHAR(255), menu VARCHAR(255), hak VARCHAR(255));
    CREATE TABLE log_akses(id INT AUTO_INCREMENT PRIMARY KEY, waktu VARCHAR(255), departemen VARCHAR(255), menu VARCHAR(255), aksi VARCHAR(255), hasil VARCHAR(255));");
    $m = ['Penjualan' => ['CRU', 'R', 'R', '-', 'R'], 'Gudang' => ['R', 'CRU', 'R', 'R', '-'], 'Produksi' => ['R', 'CR', 'CRU', 'R', '-'],
          'Pembelian' => ['-', 'R', 'R', 'CRU', 'R'], 'Keuangan' => ['R', 'R', 'R', 'R', 'CRUA']];
    $menu = ['Penjualan', 'Gudang', 'Produksi', 'Pembelian', 'Keuangan'];
    $s = $d->prepare("INSERT INTO hak_akses VALUES(?,?,?)");
    foreach ($m as $dep => $r) foreach ($r as $i => $hak) $s->execute([$dep, $menu[$i], $hak]);
}
function seed_p3(PDO $d) {
    run($d,"DROP TABLE IF EXISTS permintaan; DROP TABLE IF EXISTS log_persetujuan;
    CREATE TABLE permintaan(id INT AUTO_INCREMENT PRIMARY KEY, pengaju VARCHAR(255), lab VARCHAR(255), bahan VARCHAR(255), qty INT, nilai INT, nilai_disetujui INT, status VARCHAR(255), dibuat VARCHAR(255));
    CREATE TABLE log_persetujuan(id INT AUTO_INCREMENT PRIMARY KEY, permintaan_id INT, waktu VARCHAR(255), aktor VARCHAR(255), aksi VARCHAR(255), catatan VARCHAR(255));
    INSERT INTO permintaan(pengaju,lab,bahan,qty,nilai,status,dibuat) VALUES('Dosen A','Lab Elektronika','Resistor 1K (pak)',50,0,'DIAJUKAN','2026-10-20 09:00');
    INSERT INTO log_persetujuan(permintaan_id,waktu,aktor,aksi,catatan) VALUES(1,'2026-10-20 09:00','Dosen A','Mengajukan','Permintaan awal');");
}
function seed_p4(PDO $d) {
    run($d,"DROP TABLE IF EXISTS akun; DROP TABLE IF EXISTS jurnal;
    CREATE TABLE akun(kode VARCHAR(30) PRIMARY KEY, nama VARCHAR(255), tipe VARCHAR(255), normal VARCHAR(255));
    CREATE TABLE jurnal(id INT AUTO_INCREMENT PRIMARY KEY, tanggal VARCHAR(255), keterangan VARCHAR(255), debit VARCHAR(255), kredit VARCHAR(255), nilai INT, jenis VARCHAR(255), ref_id INT);
    INSERT INTO akun VALUES('1-101','Kas','Aset','D'),('1-102','Piutang Usaha','Aset','D'),('1-103','Persediaan','Aset','D'),('2-101','Utang Usaha','Kewajiban','K'),
      ('3-101','Modal','Ekuitas','K'),('4-101','Penjualan','Pendapatan','K'),('6-101','Beban Operasional','Beban','D');
    INSERT INTO jurnal(tanggal,keterangan,debit,kredit,nilai,jenis) VALUES
      ('2026-10-01','Saldo awal kas','1-101','3-101',30000000,'normal'),
      ('2026-10-02','Pembelian bahan baku secara kredit','1-103','2-101',8000000,'normal'),
      ('2026-10-03','Pembayaran utang pemasok','2-101','1-101',3000000,'normal'),
      ('2026-10-04','Penjualan produk secara kredit','1-102','4-101',12000000,'normal'),
      ('2026-10-05','Penerimaan pembayaran pelanggan','1-101','1-102',5000000,'normal'),
      ('2026-10-06','Pembayaran biaya operasional secara tunai','6-101','1-101',1500000,'normal');");
}
function seed_p5(PDO $d) {
    run($d,"DROP TABLE IF EXISTS pr; DROP TABLE IF EXISTS po; DROP TABLE IF EXISTS penerimaan; DROP TABLE IF EXISTS mutasi_stok;
    CREATE TABLE pr(id INT AUTO_INCREMENT PRIMARY KEY, tanggal VARCHAR(255), bahan VARCHAR(255), qty INT, status VARCHAR(255));
    CREATE TABLE po(id INT AUTO_INCREMENT PRIMARY KEY, kode VARCHAR(255), pr_id INT, bahan VARCHAR(255), pemasok VARCHAR(255), qty INT, diterima INT, eta_hari INT, status VARCHAR(255));
    CREATE TABLE penerimaan(id INT AUTO_INCREMENT PRIMARY KEY, tanggal VARCHAR(255), po_id INT, bahan VARCHAR(255), qty INT);
    CREATE TABLE mutasi_stok(id INT AUTO_INCREMENT PRIMARY KEY, tanggal VARCHAR(255), bahan VARCHAR(255), qty INT, keterangan VARCHAR(255), saldo INT);
    INSERT INTO po(kode,pr_id,bahan,pemasok,qty,diterima,eta_hari,status) VALUES('PO-LAMA-A',NULL,'MAT-A','SUP-A',100,0,4,'Open');
    INSERT INTO mutasi_stok(tanggal,bahan,qty,keterangan,saldo) VALUES('2026-10-01','MAT-A',400,'Saldo awal',400),('2026-10-01','MAT-B',250,'Saldo awal',250);");
}
