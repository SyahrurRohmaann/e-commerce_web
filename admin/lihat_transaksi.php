<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require 'db.php';

// Fungsi Pencarian dan Pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "
    SELECT t.id_transaksi, t.tanggal, t.total, 
           p.nama AS nama_produk, p.ukuran, p.id_kategori, dt.jumlah, dt.harga,
           k.nama_kategori, a.nama_admin
    FROM transaksi t
    JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    JOIN Produk p ON dt.id_produk = p.id_produk
    JOIN Kategori k ON p.id_kategori = k.id_kategori
    JOIN admin a ON t.id_admin = a.id_admin
    WHERE p.nama LIKE :search
    ORDER BY t.id_transaksi DESC
    LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
$stmt->execute();
$transaksiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_sql = "
    SELECT COUNT(*)
    FROM transaksi t
    JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    JOIN Produk p ON dt.id_produk = p.id_produk
    WHERE p.nama LIKE :search";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$count_stmt->execute();
$total_transaksi = $count_stmt->fetchColumn();
$total_pages = ceil($total_transaksi / $limit);
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
            margin-top:30px;
            padding: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content container mt-5">
        <h2>Daftar Transaksi</h2>
        <h4>Admin: <?php echo htmlspecialchars($_SESSION['nama_admin']); ?></h4>

        <!-- Form Pencarian -->
        <form method="GET" action="lihat_transaksi.php" class="mb-3">
            <div class="input-group mb-3">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari Transaksi...">
                <button type="submit" class="btn btn-success">Cari</button>
            </div>
        </form>

        <table class="table table-striped table-hover table-bordered">
            <thead class="table-dark">
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

        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="lihat_transaksi.php?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
