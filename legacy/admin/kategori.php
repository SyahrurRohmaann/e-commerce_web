<?php
require_once 'db.php';

$message = '';

// Menangani penambahan kategori
if (isset($_POST['add'])) {
    $nama_kategori = $_POST['nama_kategori'];
    $sql = "INSERT INTO kategori (nama_kategori) VALUES (?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$nama_kategori])) {
        $message = "Kategori baru berhasil ditambahkan";
    } else {
        $message = "Error: " . $stmt->errorInfo()[2];
    }
}

// Menangani pembaruan kategori
if (isset($_POST['edit'])) {
    $id_kategori = $_POST['id_kategori'];
    $nama_kategori = $_POST['nama_kategori'];
    $sql = "UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$nama_kategori, $id_kategori])) {
        $message = "Kategori berhasil diperbarui";
    } else {
        $message = "Error: " . $stmt->errorInfo()[2];
    }
}

// Menangani penghapusan kategori
if (isset($_POST['delete'])) {
    $id_kategori = $_POST['id_kategori'];
    $sql = "DELETE FROM kategori WHERE id_kategori = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$id_kategori])) {
        $message = "Kategori berhasil dihapus";
    } else {
        $message = "Error: " . $stmt->errorInfo()[2];
    }
}

// Fungsi Pencarian dan Pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT id_kategori, nama_kategori FROM kategori WHERE nama_kategori LIKE :search ORDER BY id_kategori DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
$stmt->execute();
$kategoriList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_sql = "SELECT COUNT(*) FROM kategori WHERE nama_kategori LIKE :search";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$count_stmt->execute();
$total_kategori = $count_stmt->fetchColumn();
$total_pages = ceil($total_kategori / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kelola Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .notification {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e7f3fe;
            border: 1px solid #b3d4fc;
            border-radius: 5px;
            color: #31708f;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1>Kelola Kategori</h1>
        <?php if($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- Form Pencarian -->
        <form method="GET" action="kategori.php" class="mb-3">
            <div class="input-group mb-3">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari Kategori...">
                <button type="submit" class="btn btn-success">Cari</button>
            </div>
        </form>



        <h2>Daftar Kategori</h2>
                <!-- Form Tambah Kategori -->
                <form method="post" action="kategori.php" class="mb-3">
            <div class="input-group mb-3">
                <input type="text" name="nama_kategori" class="form-control" placeholder="Nama Kategori" required>
                <button type="submit" name="add" class="btn btn-success">Tambah Kategori</button>
            </div>
        </form>
        <table class="table table-striped table-hover table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nama Kategori</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kategoriList as $kategori): ?>
                <tr>
                    <td><?= htmlspecialchars($kategori['id_kategori']) ?></td>
                    <td><?= htmlspecialchars($kategori['nama_kategori']) ?></td>
                    <td class="actions">
                        <form method="post" action="kategori.php" class="d-inline-block">
                            <input type="hidden" name="id_kategori" value="<?= $kategori['id_kategori'] ?>">
                            <div class="input-group">
                                <input type="text" name="nama_kategori" value="<?= htmlspecialchars($kategori['nama_kategori']) ?>" class="form-control form-control-sm" required>
                                <button type="submit" name="edit" class="btn btn-warning btn-sm">Edit</button>
                            </div>
                        </form>
                        <form method="post" action="kategori.php" class="d-inline-block">
                            <input type="hidden" name="id_kategori" value="<?= $kategori['id_kategori'] ?>">
                            <button type="submit" name="delete" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kategori ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="kategori.php?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
