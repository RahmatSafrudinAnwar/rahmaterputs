-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 30, 2026 at 03:16 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `erp_praktikum`
--

-- --------------------------------------------------------

--
-- Table structure for table `akun`
--

CREATE TABLE `akun` (
  `kode` varchar(30) NOT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `tipe` varchar(255) DEFAULT NULL,
  `normal` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `akun`
--

INSERT INTO `akun` (`kode`, `nama`, `tipe`, `normal`) VALUES
('1-101', 'Kas', 'Aset', 'D'),
('1-102', 'Piutang Usaha', 'Aset', 'D'),
('1-103', 'Persediaan', 'Aset', 'D'),
('2-101', 'Utang Usaha', 'Kewajiban', 'K'),
('3-101', 'Modal', 'Ekuitas', 'K'),
('4-101', 'Penjualan', 'Pendapatan', 'K'),
('6-101', 'Beban Operasional', 'Beban', 'D');

-- --------------------------------------------------------

--
-- Table structure for table `bahan`
--

CREATE TABLE `bahan` (
  `kode` varchar(30) NOT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `satuan` varchar(255) DEFAULT NULL,
  `stok` int DEFAULT NULL,
  `lead_time` int DEFAULT NULL,
  `pemasok` varchar(255) DEFAULT NULL,
  `gudang` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bahan`
--

INSERT INTO `bahan` (`kode`, `nama`, `satuan`, `stok`, `lead_time`, `pemasok`, `gudang`) VALUES
('MAT-A', 'Bahan A', 'unit', 400, 4, 'SUP-A', 'WH-01'),
('MAT-B', 'Bahan B', 'unit', 250, 7, 'SUP-B', 'WH-01');

-- --------------------------------------------------------

--
-- Table structure for table `bom`
--

CREATE TABLE `bom` (
  `produk` varchar(255) DEFAULT NULL,
  `bahan` varchar(255) DEFAULT NULL,
  `qty` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bom`
--

INSERT INTO `bom` (`produk`, `bahan`, `qty`) VALUES
('PRD-X', 'MAT-A', 3),
('PRD-X', 'MAT-B', 2);

-- --------------------------------------------------------

--
-- Table structure for table `gudang`
--

CREATE TABLE `gudang` (
  `kode` varchar(30) NOT NULL,
  `nama` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gudang`
--

INSERT INTO `gudang` (`kode`, `nama`) VALUES
('WH-01', 'Gudang Bahan Baku');

-- --------------------------------------------------------

--
-- Table structure for table `hak_akses`
--

CREATE TABLE `hak_akses` (
  `departemen` varchar(255) DEFAULT NULL,
  `menu` varchar(255) DEFAULT NULL,
  `hak` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hak_akses`
--

INSERT INTO `hak_akses` (`departemen`, `menu`, `hak`) VALUES
('Penjualan', 'Penjualan', 'CRU'),
('Penjualan', 'Gudang', 'R'),
('Penjualan', 'Produksi', 'R'),
('Penjualan', 'Pembelian', '-'),
('Penjualan', 'Keuangan', 'R'),
('Gudang', 'Penjualan', 'R'),
('Gudang', 'Gudang', 'CRU'),
('Gudang', 'Produksi', 'R'),
('Gudang', 'Pembelian', 'R'),
('Gudang', 'Keuangan', '-'),
('Produksi', 'Penjualan', 'R'),
('Produksi', 'Gudang', 'CR'),
('Produksi', 'Produksi', 'CRU'),
('Produksi', 'Pembelian', 'R'),
('Produksi', 'Keuangan', '-'),
('Pembelian', 'Penjualan', '-'),
('Pembelian', 'Gudang', 'R'),
('Pembelian', 'Produksi', 'R'),
('Pembelian', 'Pembelian', 'CRU'),
('Pembelian', 'Keuangan', 'R'),
('Keuangan', 'Penjualan', 'R'),
('Keuangan', 'Gudang', 'R'),
('Keuangan', 'Produksi', 'R'),
('Keuangan', 'Pembelian', 'R'),
('Keuangan', 'Keuangan', 'CRUA');

-- --------------------------------------------------------

--
-- Table structure for table `jurnal`
--

CREATE TABLE `jurnal` (
  `id` int NOT NULL,
  `tanggal` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `debit` varchar(255) DEFAULT NULL,
  `kredit` varchar(255) DEFAULT NULL,
  `nilai` int DEFAULT NULL,
  `jenis` varchar(255) DEFAULT NULL,
  `ref_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jurnal`
--

INSERT INTO `jurnal` (`id`, `tanggal`, `keterangan`, `debit`, `kredit`, `nilai`, `jenis`, `ref_id`) VALUES
(1, '2026-10-01', 'Saldo awal kas', '1-101', '3-101', 30000000, 'normal', NULL),
(2, '2026-10-02', 'Pembelian bahan baku secara kredit', '1-103', '2-101', 8000000, 'normal', NULL),
(3, '2026-10-03', 'Pembayaran utang pemasok', '2-101', '1-101', 3000000, 'normal', NULL),
(4, '2026-10-04', 'Penjualan produk secara kredit', '1-102', '4-101', 12000000, 'normal', NULL),
(5, '2026-10-05', 'Penerimaan pembayaran pelanggan', '1-101', '1-102', 5000000, 'normal', NULL),
(6, '2026-10-06', 'Pembayaran biaya operasional secara tunai', '6-101', '1-101', 1500000, 'normal', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `log_akses`
--

CREATE TABLE `log_akses` (
  `id` int NOT NULL,
  `waktu` varchar(255) DEFAULT NULL,
  `departemen` varchar(255) DEFAULT NULL,
  `menu` varchar(255) DEFAULT NULL,
  `aksi` varchar(255) DEFAULT NULL,
  `hasil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_persetujuan`
--

CREATE TABLE `log_persetujuan` (
  `id` int NOT NULL,
  `permintaan_id` int DEFAULT NULL,
  `waktu` varchar(255) DEFAULT NULL,
  `aktor` varchar(255) DEFAULT NULL,
  `aksi` varchar(255) DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `log_persetujuan`
--

INSERT INTO `log_persetujuan` (`id`, `permintaan_id`, `waktu`, `aktor`, `aksi`, `catatan`) VALUES
(1, 1, '2026-10-20 09:00', 'Dosen A', 'Mengajukan', 'Permintaan awal'),
(2, 1, '2026-10-27 03:39:13', 'Kepala Lab', 'Memverifikasi', 'Kebutuhan sesuai');

-- --------------------------------------------------------

--
-- Table structure for table `mutasi_stok`
--

CREATE TABLE `mutasi_stok` (
  `id` int NOT NULL,
  `tanggal` varchar(255) DEFAULT NULL,
  `bahan` varchar(255) DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `saldo` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mutasi_stok`
--

INSERT INTO `mutasi_stok` (`id`, `tanggal`, `bahan`, `qty`, `keterangan`, `saldo`) VALUES
(1, '2026-10-01', 'MAT-A', 400, 'Saldo awal', 400),
(2, '2026-10-01', 'MAT-B', 250, 'Saldo awal', 250);

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `kode` varchar(30) NOT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `syarat_bayar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`kode`, `nama`, `syarat_bayar`) VALUES
('C01', 'PT Maju Jaya', 'Kredit 30 hari'),
('C02', 'CV Sinar Abadi', 'Tunai');

-- --------------------------------------------------------

--
-- Table structure for table `pemasok`
--

CREATE TABLE `pemasok` (
  `kode` varchar(30) NOT NULL,
  `nama` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pemasok`
--

INSERT INTO `pemasok` (`kode`, `nama`) VALUES
('SUP-A', 'Pemasok Bahan A'),
('SUP-B', 'Pemasok Bahan B');

-- --------------------------------------------------------

--
-- Table structure for table `penerimaan`
--

CREATE TABLE `penerimaan` (
  `id` int NOT NULL,
  `tanggal` varchar(255) DEFAULT NULL,
  `po_id` int DEFAULT NULL,
  `bahan` varchar(255) DEFAULT NULL,
  `qty` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permintaan`
--

CREATE TABLE `permintaan` (
  `id` int NOT NULL,
  `pengaju` varchar(255) DEFAULT NULL,
  `lab` varchar(255) DEFAULT NULL,
  `bahan` varchar(255) DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `nilai` int DEFAULT NULL,
  `nilai_disetujui` int DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `dibuat` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permintaan`
--

INSERT INTO `permintaan` (`id`, `pengaju`, `lab`, `bahan`, `qty`, `nilai`, `nilai_disetujui`, `status`, `dibuat`) VALUES
(1, 'Dosen A', 'Lab Elektronika', 'Resistor 1K (pak)', 50, 0, NULL, 'TERVERIFIKASI', '2026-10-20 09:00');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int NOT NULL,
  `tanggal` varchar(255) DEFAULT NULL,
  `pelanggan` varchar(255) DEFAULT NULL,
  `produk` varchar(255) DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `nilai` int DEFAULT NULL,
  `kondisi` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id`, `tanggal`, `pelanggan`, `produk`, `qty`, `nilai`, `kondisi`, `status`) VALUES
(1, '2026-10-27 03:32', 'C01', 'PRD-X', 50, 6000000, 'ubah_jumlah', 'Siap diproduksi (direvisi)');

-- --------------------------------------------------------

--
-- Table structure for table `po`
--

CREATE TABLE `po` (
  `id` int NOT NULL,
  `kode` varchar(255) DEFAULT NULL,
  `pr_id` int DEFAULT NULL,
  `bahan` varchar(255) DEFAULT NULL,
  `pemasok` varchar(255) DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `diterima` int DEFAULT NULL,
  `eta_hari` int DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `po`
--

INSERT INTO `po` (`id`, `kode`, `pr_id`, `bahan`, `pemasok`, `qty`, `diterima`, `eta_hari`, `status`) VALUES
(1, 'PO-LAMA-A', NULL, 'MAT-A', 'SUP-A', 100, 0, 4, 'Open');

-- --------------------------------------------------------

--
-- Table structure for table `pr`
--

CREATE TABLE `pr` (
  `id` int NOT NULL,
  `tanggal` varchar(255) DEFAULT NULL,
  `bahan` varchar(255) DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `kode` varchar(30) NOT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `satuan` varchar(255) DEFAULT NULL,
  `harga` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`kode`, `nama`, `satuan`, `harga`) VALUES
('PRD-X', 'Produk X', 'unit', 120000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`kode`);

--
-- Indexes for table `bahan`
--
ALTER TABLE `bahan`
  ADD PRIMARY KEY (`kode`);

--
-- Indexes for table `gudang`
--
ALTER TABLE `gudang`
  ADD PRIMARY KEY (`kode`);

--
-- Indexes for table `jurnal`
--
ALTER TABLE `jurnal`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_akses`
--
ALTER TABLE `log_akses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_persetujuan`
--
ALTER TABLE `log_persetujuan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mutasi_stok`
--
ALTER TABLE `mutasi_stok`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`kode`);

--
-- Indexes for table `pemasok`
--
ALTER TABLE `pemasok`
  ADD PRIMARY KEY (`kode`);

--
-- Indexes for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permintaan`
--
ALTER TABLE `permintaan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `po`
--
ALTER TABLE `po`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pr`
--
ALTER TABLE `pr`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`kode`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jurnal`
--
ALTER TABLE `jurnal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `log_akses`
--
ALTER TABLE `log_akses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_persetujuan`
--
ALTER TABLE `log_persetujuan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mutasi_stok`
--
ALTER TABLE `mutasi_stok`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penerimaan`
--
ALTER TABLE `penerimaan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permintaan`
--
ALTER TABLE `permintaan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `po`
--
ALTER TABLE `po`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pr`
--
ALTER TABLE `pr`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
