<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require 'db.php';

// Mengambil data jumlah stok yang terjual berdasarkan tanggal dan kategori dari database
$categoryDateStocks = $pdo->query("
    SELECT t.tanggal, k.nama_kategori, SUM(dt.jumlah) as terjual
    FROM transaksi t
    JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    JOIN Produk p ON dt.id_produk = p.id_produk
    JOIN Kategori k ON p.id_kategori = k.id_kategori
    GROUP BY t.tanggal, k.nama_kategori
    ORDER BY t.tanggal
")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($categoryDateStocks);
?>
