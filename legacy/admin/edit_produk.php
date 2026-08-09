<?php
session_start();
require 'db.php';

$id_produk = $_GET['id'];

// Query produk berdasarkan id_produk
$sql = "SELECT p.*, k.nama_kategori, a.nama_admin
        FROM Produk p
        JOIN Kategori k ON p.id_kategori = k.id_kategori
        JOIN Admin a ON p.id_admin = a.id_admin
        WHERE p.id_produk = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_produk]);
$produk = $stmt->fetch(PDO::FETCH_ASSOC);

// Query semua kategori
$stmt = $pdo->prepare("SELECT id_kategori, nama_kategori FROM kategori");
$stmt->execute();
$kategoriList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nama_admin = $_SESSION['nama_admin'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $stock = $_POST['stock'];
    $nama_kategori = $_POST['nama_kategori'];
    $ukuran = $_POST['ukuran'];
    $keterangan = $_POST['keterangan'];

    $stmt = $pdo->prepare("SELECT id_kategori FROM kategori WHERE nama_kategori = ?");
    $stmt->execute([$nama_kategori]);
    $kategori = $stmt->fetch();
    $id_kategori = $kategori['id_kategori'];

    $id_admin = $_SESSION['id_admin']; // Mengambil id_admin dari session

    if ($_FILES['gambar']['name']) {
        $gambar = $_FILES['gambar']['name'];
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($gambar);
        move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file);
        $sql = "UPDATE Produk SET nama = ?, harga = ?, stock = ?, id_kategori = ?, gambar = ?, ukuran = ?, keterangan = ?, id_admin = ? WHERE id_produk = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $harga, $stock, $id_kategori, $gambar, $ukuran, $keterangan, $id_admin, $id_produk]);
    } else {
        $sql = "UPDATE Produk SET nama = ?, harga = ?, stock = ?, id_kategori = ?, ukuran = ?, keterangan = ?, id_admin = ? WHERE id_produk = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $harga, $stock, $id_kategori, $ukuran, $keterangan, $id_admin, $id_produk]);
    }

    header("Location: lihat_produk.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Produk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: auto;
            margin: 0;
            background-color: #f5f5f5;
        }
        .main-content {
            margin-left: 220px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 70%;
        }
        h1 {
            margin-top: 0;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-top: 10px;
        }
        input[type="text"], input[type="number"], select, textarea, input[type="file"] {
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        img {
            margin-top: 10px;
        }
        input[readonly] { background-color: #e9ecef; color: #6c757d; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1>Edit Produk</h1>
        <form action="edit_produk.php?id=<?= $id_produk ?>" method="post" enctype="multipart/form-data">
            <label for="nama">Nama:</label>
            <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($produk['nama']) ?>" required>

            <label for="harga">Harga:</label>
            <input type="number" id="harga" name="harga" value="<?= htmlspecialchars($produk['harga']) ?>" required>

            <label for="stock">Stock:</label>
            <input type="number" id="stock" name="stock" value="<?= htmlspecialchars($produk['stock']) ?>" required>

            <label for="nama_kategori">Kategori:</label>
            <select id="nama_kategori" name="nama_kategori" required>
                <?php foreach ($kategoriList as $kategori): ?>
                    <option value="<?= htmlspecialchars($kategori['nama_kategori']) ?>" <?= ($kategori['id_kategori'] == $produk['id_kategori']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kategori['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="gambar">Gambar:</label>
            <input type="file" id="gambar" name="gambar">
            <?php if ($produk['gambar']): ?>
                <img src="../uploads/<?= htmlspecialchars($produk['gambar']) ?>" alt="Pratinjau Gambar" width="100">
            <?php endif; ?>

            <label for="ukuran">Ukuran:</label>
            <select id="ukuran" name="ukuran" required>
                <option value="S" <?= ($produk['ukuran'] == 'S') ? 'selected' : '' ?>>S</option>
                <option value="M" <?= ($produk['ukuran'] == 'M') ? 'selected' : '' ?>>M</option>
                <option value="L" <?= ($produk['ukuran'] == 'L') ? 'selected' : '' ?>>L</option>
                <option value="XL" <?= ($produk['ukuran'] == 'XL') ? 'selected' : '' ?>>XL</option>
            </select>

            <label for="keterangan">Keterangan:</label>
            <textarea id="keterangan" name="keterangan" required><?= htmlspecialchars($produk['keterangan']) ?></textarea>

            <label for="nama_admin">Admin:</label>
            <input type="text" id="nama_admin" name="nama_admin" value="<?= htmlspecialchars($nama_admin) ?>" readonly>

            <button type="submit">Update Produk</button>
        </form>
    </div>
</body>
</html>
