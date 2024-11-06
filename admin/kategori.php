<?php
require_once 'db.php';

$message = '';

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

$sql = "SELECT id_kategori, nama_kategori FROM kategori";
$stmt = $pdo->query($sql);
$kategoriList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Kategori</title>
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
            margin-left: 210px;
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
            margin-bottom: 20px;
        }
        input[type="text"], input[type="submit"] {
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
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
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #f2f2f2;
            border-bottom: 2px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .actions input[type="submit"] {
            padding: 5px 10px;
            margin: 2px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
        }
        .actions .edit {
            background-color: #ff9800;
            color: white;
        }
        .actions .delete {
            background-color: #f44336;
            color: white;
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
            <div class="notification"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" action="kategori.php">
            <input type="text" name="nama_kategori" placeholder="Nama Kategori" required>
            <input type="submit" name="add" value="Tambah Kategori">
        </form>
        <h2>Daftar Kategori</h2>
        <table>
            <thead>
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
                        <form method="post" action="kategori.php" style="display:inline-block;">
                            <input type="hidden" name="id_kategori" value="<?= $kategori['id_kategori'] ?>">
                            <input type="text" name="nama_kategori" value="<?= htmlspecialchars($kategori['nama_kategori']) ?>" style="font-size: 12px; width: 120px;">
                            <input type="submit" name="edit" value="Edit" class="edit">
                        </form>
                        <form method="post" action="kategori.php" style="display:inline-block;">
                            <input type="hidden" name="id_kategori" value="<?= $kategori['id_kategori'] ?>">
                            <input type="submit" name="delete" value="Hapus" onclick="return confirm('Yakin ingin menghapus kategori ini?')" class="delete">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
