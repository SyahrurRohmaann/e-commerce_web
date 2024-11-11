<?php
session_start(); // Memastikan sesi dimulai

require 'db.php';

// Pastikan admin sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Mengambil daftar kategori
$stmt = $pdo->prepare("SELECT id_kategori, nama_kategori FROM kategori");
$stmt->execute();
$kategoriList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mengambil nama admin dari sesi
$nama_admin = $_SESSION['nama_admin'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $stock = $_POST['stock'];
    $nama_kategori = $_POST['nama_kategori'];
    $ukuran = $_POST['ukuran'];
    $keterangan = $_POST['keterangan'];
    $id_admin = $_SESSION['id_admin'];

    $stmt = $pdo->prepare("SELECT id_kategori FROM kategori WHERE nama_kategori = ?");
    $stmt->execute([$nama_kategori]);
    $kategori = $stmt->fetch();
    $id_kategori = $kategori['id_kategori'];

    $gambar_paths = [];
    if (!empty($_FILES['gambar']['name'][0])) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        foreach ($_FILES['gambar']['name'] as $key => $value) {
            $gambar = $_FILES['gambar']['name'][$key];
            $target_file = $target_dir . basename($gambar);
            move_uploaded_file($_FILES["gambar"]["tmp_name"][$key], $target_file);
            $gambar_paths[] = $gambar;
        }
    }

    $gambar_paths_json = json_encode($gambar_paths);

    $sql = "INSERT INTO Produk (nama, harga, stock, id_kategori, gambar, ukuran, keterangan, id_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $harga, $stock, $id_kategori, $gambar_paths_json, $ukuran, $keterangan, $id_admin]);
    echo "Produk berhasil ditambahkan!";
    header("Location: lihat_produk.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Produk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .main-content {
            margin-left: 200px;
            padding: 20px;
            flex-grow: 1;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
        input[readonly] {
            background-color: #e9ecef;
            color: #6c757d;
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
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1>Tambah Produk</h1>
        <form action="tambah_produk.php" method="post" enctype="multipart/form-data">
            <label for="nama">Nama:</label>
            <input type="text" id="nama" name="nama" required>

            <label for="harga">Harga:</label>
            <input type="number" id="harga" name="harga" required>

            <label for="stock">Stock:</label>
            <input type="number" id="stock" name="stock" required>

            <label for="nama_kategori">Kategori:</label>
            <select id="nama_kategori" name="nama_kategori" required>
                <?php foreach ($kategoriList as $kategori): ?>
                    <option value="<?= $kategori['nama_kategori'] ?>"><?= $kategori['nama_kategori'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="gambar">Gambar:</label>
            <input type="file" id="gambar" name="gambar[]" multiple required>

            <label for="ukuran">Ukuran:</label>
            <select id="ukuran" name="ukuran" required>
                <option value="S">S</option>
                <option value="M">M</option>
                <option value="L">L</option>
                <option value="XL">XL</option>
            </select>

            <label for="keterangan">Keterangan:</label>
            <textarea id="keterangan" name="keterangan" required></textarea>

            <label for="nama_admin">Admin:</label>
            <input type="text" id="nama_admin" name="nama_admin" value="<?= htmlspecialchars($nama_admin) ?>" readonly>

            <button type="submit">Tambah Produk</button>
        </form>
    </div>
</body>
</html>
