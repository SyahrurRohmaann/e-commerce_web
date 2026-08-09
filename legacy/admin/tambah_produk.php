<?php
session_start(); 

require 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

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
<html lang="id">
<head>
    <title>Tambah Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .form-label {
            margin-top: 10px;
        }
        .form-control, .form-select, .form-control-file {
            margin-top: 5px;
            margin-bottom: 15px;
        }
        input[readonly] {
            background-color: #e9ecef;
            color: #6c757d;
        }
        .btn-primary {
            padding: 10px 15px;
            background-color: #4CAF50;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1>Tambah Produk</h1>
        <form action="tambah_produk.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama:</label>
                <input type="text" id="nama" name="nama" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="harga" class="form-label">Harga:</label>
                <input type="number" id="harga" name="harga" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Stock:</label>
                <input type="number" id="stock" name="stock" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="nama_kategori" class="form-label">Kategori:</label>
                <select id="nama_kategori" name="nama_kategori" class="form-select" required>
                    <?php foreach ($kategoriList as $kategori): ?>
                        <option value="<?= $kategori['nama_kategori'] ?>"><?= $kategori['nama_kategori'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="gambar" class="form-label">Gambar:</label>
                <input type="file" id="gambar" name="gambar[]" class="form-control-file" multiple required>
            </div>
            <div class="mb-3">
                <label for="ukuran" class="form-label">Ukuran:</label>
                <select id="ukuran" name="ukuran" class="form-select" required>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan:</label>
                <textarea id="keterangan" name="keterangan" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="nama_admin" class="form-label">Admin:</label>
                <input type="text" id="nama_admin" name="nama_admin" value="<?= htmlspecialchars($nama_admin) ?>" class="form-control" readonly>
            </div>
            <button type="submit" class="btn btn-primary">Tambah Produk</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
