<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            margin: 0;
        }
        .main-content {
            margin-left: 210px;
            padding: 20px;
            flex-grow: 1;
        }
        h1 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1>halooo, <?php echo htmlspecialchars($_SESSION['nama_admin']); ?></h1>
    </div>
</body>
</html>
