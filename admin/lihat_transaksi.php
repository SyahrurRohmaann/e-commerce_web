<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require 'db.php';

// Mengambil data transaksi dari database
$transaksiList = $pdo->query("
    SELECT t.id_transaksi, t.tanggal, t.total, 
           p.nama AS nama_produk, p.ukuran, p.id_kategori, dt.jumlah, dt.harga,
           k.nama_kategori, a.nama_admin
    FROM transaksi t
    JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    JOIN Produk p ON dt.id_produk = p.id_produk
    JOIN Kategori k ON p.id_kategori = k.id_kategori
    JOIN admin a ON t.id_admin = a.id_admin
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lihat Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 210px;
            padding: 20px;
        }
        table {
            width: 100%;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content container mt-5">
        <h2>Daftar Transaksi</h2>
        <p>Admin: <?php echo htmlspecialchars($_SESSION['nama_admin']); ?></p>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Transaksi</th>
                    <th>Tanggal</th>
                    <th>Nama Produk</th>
                    <th>Ukuran</th>
                    <th>Kategori</th>
                    <th>Jumlah</th>
                    <th>Harga</th>
                    <th>Total</th>
                    <th>Admin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transaksiList as $trans): ?>
                <tr>
                    <td><?php echo $trans['id_transaksi']; ?></td>
                    <td><?php echo $trans['tanggal']; ?></td>
                    <td><?php echo htmlspecialchars($trans['nama_produk']); ?></td>
                    <td><?php echo htmlspecialchars($trans['ukuran']); ?></td>
                    <td><?php echo htmlspecialchars($trans['nama_kategori']); ?></td>
                    <td><?php echo $trans['jumlah']; ?></td>
                    <td><?php echo number_format($trans['harga'], 2); ?></td>
                    <td><?php echo number_format($trans['total'], 2); ?></td>
                    <td><?php echo htmlspecialchars($trans['nama_admin']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
