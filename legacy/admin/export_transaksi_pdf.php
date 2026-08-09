<?php
require '../fpdf/fpdf.php';
require 'db.php';

// Ambil parameter ID Transaksi dari URL
$id_transaksi = isset($_GET['id_transaksi']) ? $_GET['id_transaksi'] : 0;

// Query untuk mengambil data transaksi
$sql = "
    SELECT t.id_transaksi, t.tanggal, t.total, 
           p.id_produk, p.nama AS nama_produk, p.ukuran, k.nama_kategori, dt.jumlah, dt.harga,
           a.nama_user, p.gambar
    FROM transaksi t
    JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    JOIN Produk p ON dt.id_produk = p.id_produk
    JOIN Kategori k ON p.id_kategori = k.id_kategori
    JOIN pengguna a ON t.id_user = a.id_user
    WHERE t.id_transaksi = :id_transaksi";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id_transaksi', $id_transaksi, PDO::PARAM_INT);
$stmt->execute();
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jika data transaksi tidak ditemukan
if (empty($transaksi)) {
    die("Transaksi dengan ID $id_transaksi tidak ditemukan!");
}

// Inisialisasi FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Judul
$pdf->Cell(0, 10, 'Laporan Transaksi', 0, 1, 'C');
$pdf->Ln(10);

// Data Header
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(40, 10, 'ID Transaksi: ', 0, 0);
$pdf->Cell(50, 10, $transaksi[0]['id_transaksi'], 0, 1);
$pdf->Cell(40, 10, 'Tanggal: ', 0, 0);
$pdf->Cell(50, 10, $transaksi[0]['tanggal'], 0, 1);
$pdf->Cell(40, 10, 'Nama Pembeli: ', 0, 0);
$pdf->Cell(50, 10, $transaksi[0]['nama_user'], 0, 1);
$pdf->Ln(10);

// Data Detail Transaksi
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Produk', 1);
$pdf->Cell(30, 10, 'Kategori', 1);
$pdf->Cell(20, 10, 'Ukuran', 1);
$pdf->Cell(20, 10, 'Jumlah', 1);
$pdf->Cell(30, 10, 'Harga', 1);
$pdf->Cell(30, 10, 'Subtotal', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
foreach ($transaksi as $item) {
    $pdf->Cell(50, 10, $item['nama_produk'], 1);
    $pdf->Cell(30, 10, $item['nama_kategori'], 1);
    $pdf->Cell(20, 10, $item['ukuran'], 1);
    $pdf->Cell(20, 10, $item['jumlah'], 1);
    $pdf->Cell(30, 10, number_format($item['harga'], 0, ',', '.'), 1);
    $subtotal = $item['jumlah'] * $item['harga'];
    $pdf->Cell(30, 10, number_format($subtotal, 0, ',', '.'), 1);
    $pdf->Ln();

}

// Total
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(150, 10, 'Total', 1);
$pdf->Cell(30, 10, number_format($transaksi[0]['total'], 0, ',', '.'), 1);
$pdf->Ln();

// Output PDF
$pdf->Output();
?>
