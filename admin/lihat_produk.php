<?php require 'db.php'; $limit = 10;
 // Number of products per page
  $page = isset($_GET['page']) ? $_GET['page'] : 1;
   // Get the current page or default to 1
    $offset = ($page - 1) * $limit;
     // Calculate the offset
      $search = isset($_GET['search']) ? $_GET['search'] : '';
       // Get the search term
        $sql = "SELECT p.*, k.nama_kategori, a.nama_admin FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori JOIN admin a ON p.id_admin = a.id_admin WHERE p.nama LIKE :search ORDER BY p.id_produk DESC LIMIT :limit OFFSET :offset"; $stmt = $pdo->prepare($sql); $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR); $stmt->bindValue(':limit', $limit, PDO::PARAM_INT); $stmt->bindValue(':offset', $offset, PDO::PARAM_INT); $stmt->execute(); $produks = $stmt->fetchAll(PDO::FETCH_ASSOC);
         // Get the total number of products for pagination
          $count_sql = "SELECT COUNT(*) FROM produk WHERE nama LIKE :search"; $count_stmt = $pdo->prepare($count_sql); $count_stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR); $count_stmt->execute(); $total_products = $count_stmt->fetchColumn(); $total_pages = ceil($total_products / $limit); if (isset($_GET['delete'])) { $id_produk = $_GET['delete']; $sql = "DELETE FROM produk WHERE id_produk = ?"; $stmt = $pdo->prepare($sql); $stmt->execute([$id_produk]); header("Location: lihat_produk.php"); exit(); } ?>


<!DOCTYPE html>
<html lang="id">
<head>
    <title>Lihat Produk</title>
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
        .table th, .table td {
            vertical-align: middle;
        }
        .img-thumbnail {
            max-width: 50px;
            margin-right: 5px;
        }
        .actions a {
            padding: 8px 12px;
            margin: 0px;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .actions a.edit {
            background-color: #4CAF50;
            margin-left: 5px;
        }
        .actions a.delete {
            background-color: #f44336;
            margin-left: 5px;
        }

        .form-control{
        width: 70%;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1>Lihat Produk</h1>
        
        <!-- Search form -->
        <form method="GET" action="lihat_produk.php" class="mb-3">
        <div class="input-group mb-3">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari produk..." class="form-control">
            <button type="submit" class="btn btn-success">Cari</button>
        </div>
        </form>
        
        <br>
        <table class="table table-striped table-hover table-bordered">
            <thead class="table-dark">
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
                    <td>
                        <?php
                        // Decode JSON gambar menjadi array
                        $gambarArray = json_decode($produk['gambar'], true);
                        
                        // Jika gambar adalah array (format JSON)
                        if (is_array($gambarArray)) {
                            foreach ($gambarArray as $gambar) {
                                echo '<img src="../uploads/' . htmlspecialchars($gambar) . '" alt="Gambar Produk" class="img-thumbnail">';
                            }
                        } else {
                            // Jika gambar adalah string (satuan), tampilkan satu gambar
                            echo '<img src="../uploads/' . htmlspecialchars($produk['gambar']) . '" alt="Gambar Produk" class="img-thumbnail">';
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($produk['ukuran']) ?></td>
                    <td><?= htmlspecialchars($produk['keterangan']) ?></td>
                    <td><?= htmlspecialchars($produk['nama_admin']) ?></td>
                    <td class="actions">
                        <a href="edit_produk.php?id=<?= htmlspecialchars($produk['id_produk']) ?>" class="btn btn-success btn-sm edit">Edit</a>
                        <a href="lihat_produk.php?delete=<?= htmlspecialchars($produk['id_produk']) ?>" class="btn btn-danger btn-sm delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
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
                        <a class="page-link" href="lihat_produk.php?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
