<?php
require_once 'db.php';

$message = '';

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

if (isset($_POST['delete'])) {
    $id_user = $_POST['id_user'];
    $sql = "DELETE FROM pengguna WHERE id_user = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$id_user])) {
        $message = "Pengguna berhasil dihapus";
    } else {
        $message = "Error: " . $stmt->errorInfo()[2];
    }
}

$sql = "SELECT id_user, nama_user, email FROM pengguna";
$stmt = $pdo->query($sql);
$penggunaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Pengguna</title>
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
            <div class="notification"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <h2>Daftar Pengguna</h2>
        <table>
            <thead>
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
                            <input type="text" name="nama_user" value="<?= htmlspecialchars($pengguna['nama_user']) ?>" style="font-size: 12px; width: 100px;">
                            <input type="text" name="email" value="<?= htmlspecialchars($pengguna['email']) ?>" style="font-size: 12px; width: 150px;">
                            <input type="submit" name="edit" value="Edit" style="background-color: #ff9800; color: white; border: none; padding: 3px 8px; border-radius: 5px; font-size: 12px; margin-right: 5px;">
                        </form>
                        <form method="post" action="pengguna.php" style="display:inline-block;">
                            <input type="hidden" name="id_user" value="<?= $pengguna['id_user'] ?>">
                            <input type="submit" name="delete" value="Hapus" onclick="return confirm('Yakin ingin menghapus pengguna ini?')" style="background-color: #f44336; color: white; border: none; padding: 3px 8px; border-radius: 5px; font-size: 12px;">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
