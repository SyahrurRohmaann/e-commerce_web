<?php
require_once 'db.php';

$message = '';

// Menangani pembaruan pengguna
if (isset($_POST['edit'])) {
    $id_user = $_POST['id_user'];
    $nama_user = $_POST['nama_user'];
    $email = $_POST['email'];
    $sql = "UPDATE pengguna SET nama_user = ?, email = ? WHERE id_user = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$nama_user, $email, $id_user])) {
        $message = "Pengguna berhasil diperbarui";
    } else {
        $message = "Error: " . $stmt->errorInfo()[2];
    }
}

// Fungsi Pencarian dan Pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT id_user, nama_user, email FROM pengguna WHERE nama_user LIKE :search ORDER BY id_user DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
$stmt->execute();
$penggunaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_sql = "SELECT COUNT(*) FROM pengguna WHERE nama_user LIKE :search";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$count_stmt->execute();
$total_pengguna = $count_stmt->fetchColumn();
$total_pages = ceil($total_pengguna / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kelola Pengguna</title>
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
        <h1>Kelola Pengguna</h1>
        <?php if($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Form Pencarian -->
        <form method="GET" action="pengguna.php" class="mb-3">
            <div class="input-group mb-3">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari Pengguna...">
                <button type="submit" class="btn btn-success">Cari</button>
            </div>
        </form>
        
        <br>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nama User</th>
                    <th>Email</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($penggunaList as $pengguna): ?>
                <tr>
                    <td><?= htmlspecialchars($pengguna['id_user']) ?></td>
                    <td><?= htmlspecialchars($pengguna['nama_user']) ?></td>
                    <td><?= htmlspecialchars($pengguna['email']) ?></td>
                    <td>
                        <form method="post" action="pengguna.php" style="display:inline-block;">
                            <input type="hidden" name="id_user" value="<?= $pengguna['id_user'] ?>">
                            <input type="text" class="form-control d-inline-block" name="nama_user" value="<?= htmlspecialchars($pengguna['nama_user']) ?>" style="font-size: 12px; width: 100px;">
                            <input type="text" class="form-control d-inline-block" name="email" value="<?= htmlspecialchars($pengguna['email']) ?>" style="font-size: 12px; width: 150px;">
                            <button type="submit" name="edit" class="btn btn-warning btn-sm">Edit</button>
                        </form>
                        <form method="post" action="pengguna.php" style="display:inline-block;">
                            <input type="hidden" name="id_user" value="<?= $pengguna['id_user'] ?>">
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
                        <a class="page-link" href="pengguna.php?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
