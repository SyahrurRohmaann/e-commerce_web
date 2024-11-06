<?php
require 'db.php';

$sql = "SELECT p.*, k.nama_kategori, a.nama_admin
        FROM Produk p
        JOIN Kategori k ON p.id_kategori = k.id_kategori
        JOIN Admin a ON p.id_admin = a.id_admin";
$stmt = $pdo->query($sql);
$produks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['delete'])) {
    $id_produk = $_GET['delete'];
    $sql = "DELETE FROM Produk WHERE id_produk = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_produk]);
    header("Location: lihat_produk.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lihat Produk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .sidebar {
            height: 100%;
            width: 200px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #111;
            padding-top: 20px;
        }
        .sidebar a, .dropdown-btn {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: #818181;
            display: block;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            outline: none;
        }
        .sidebar a:hover, .dropdown-btn:hover {
            color: #f1f1f1;
        }
        .dropdown-container {
            display: none;
            background-color: #262626;
            padding-left: 8px;
        }
        .main-content {
            margin-left: 220px;
            padding: 20px;
            flex-grow: 1;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .img-thumbnail {
            max-width: 50px;
        }
        .actions a {
            padding: 8px 12px;
            margin: 0 4px;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
        }
        .actions a.edit {
            background-color: #4CAF50;
        }
        .actions a.delete {
            background-color: #f44336;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1>Lihat Produk</h1>
        <h2>Produk</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Kategori</th>
                    <th>Gambar</th>
                    <th>Ukuran</th>
                    <th>Keterangan</th>
                    <th>Admin</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produks as $produk): ?>
                <tr>
                    <td><?= htmlspecialchars($produk['id_produk']) ?></td>
                    <td><?= htmlspecialchars($produk['nama']) ?></td>
                    <td><?= htmlspecialchars($produk['harga']) ?></td>
                    <td><?= htmlspecialchars($produk['stock']) ?></td>
                    <td><?= htmlspecialchars($produk['nama_kategori']) ?></td>
                    <td><img src="../uploads/<?= htmlspecialchars($produk['gambar']) ?>" alt="Gambar Produk" class="img-thumbnail"></td>
                    <td><?= htmlspecialchars($produk['ukuran']) ?></td>
                    <td><?= htmlspecialchars($produk['keterangan']) ?></td>
                    <td><?= htmlspecialchars($produk['nama_admin']) ?></td>
                    <td class="actions">
                        <a href="edit_produk.php?id=<?= htmlspecialchars($produk['id_produk']) ?>" class="edit">Edit</a>
                        <a href="lihat_produk.php?delete=<?= htmlspecialchars($produk['id_produk']) ?>" class="delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
