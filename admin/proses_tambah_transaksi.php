<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_produk = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $ukuran = $_POST['ukuran'];
    $jumlah = $_POST['jumlah'];
    $total_harga = $_POST['total_harga'];
    $tanggal = $_POST['tanggal'];
    $id_admin = $_SESSION['id_admin'];

    // Mengambil id_produk berdasarkan nama_produk dan ukuran
    $stmt = $pdo->prepare("SELECT id_produk FROM Produk WHERE nama = ? AND ukuran = ?");
    $stmt->execute([$nama_produk, $ukuran]);
    $produk = $stmt->fetch();
    if (!$produk) {
        echo "Produk dengan nama dan ukuran tersebut tidak ditemukan.";
        exit;
    }
    $id_produk = $produk['id_produk'];

    try {
        $pdo->beginTransaction();

        // Memasukkan data ke tabel transaksi
        $stmt = $pdo->prepare("INSERT INTO transaksi (id_user, tanggal, total, id_admin) VALUES (NULL, ?, ?, ?)");
        $stmt->execute([$tanggal, $total_harga, $id_admin]);
        $id_transaksi = $pdo->lastInsertId();

        // Memasukkan data ke tabel detail_transaksi
        $stmt = $pdo->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, harga, jumlah) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_transaksi, $id_produk, $harga, $jumlah]);

        // Mengurangi stok produk
        $stmt = $pdo->prepare("UPDATE Produk SET stock = stock - ? WHERE id_produk = ?");
        $stmt->execute([$jumlah, $id_produk]);

        $pdo->commit();

        echo "Transaksi berhasil ditambahkan!";
        header("Location: lihat_transaksi.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Gagal menambahkan transaksi: " . $e->getMessage();
    }
}
?>
