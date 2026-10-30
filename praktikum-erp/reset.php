<?php
require_once __DIR__ . '/config.php';
$p = (int)($_GET['p'] ?? 0);
$d = db();
if ($p === 1) { seed_master($d); seed_p1($d); }
elseif ($p === 2) seed_p2($d);
elseif ($p === 3) seed_p3($d);
elseif ($p === 4) seed_p4($d);
elseif ($p === 5) { seed_master($d); seed_p5($d); }
elseif ($p === 0) { seed_master($d); seed_p1($d); seed_p2($d); seed_p3($d); seed_p4($d); seed_p5($d); }
go($p ? "p$p.php" : 'index.php', 'Data praktikum dikembalikan ke kondisi awal.');
