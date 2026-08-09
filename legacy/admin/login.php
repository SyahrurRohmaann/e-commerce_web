<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_admin = $_POST['nama_admin'];
    $pass = $_POST['pass'];

    $sql = "SELECT * FROM admin WHERE nama_admin = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama_admin]);
    $admin = $stmt->fetch();

    if ($admin && $pass === $admin['pass']) {
        $_SESSION['loggedin'] = true;
        $_SESSION['id_admin'] = $admin['id_admin'];  
        $_SESSION['nama_admin'] = $admin['nama_admin'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Nama admin atau password salah";
    }
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
            font-family: 'Arial', sans-serif;
        }
        .login-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 350px;
            transition: transform 0.8s ease-in-out;
        }
        .login-container:hover {
            transform: rotate(360deg);
        }
        .login-container h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .error {
            color: red;
            text-align: center;
        }
        input[type="text"], input[type="password"] {
            margin-bottom: 15px;
        }
        button {
            background: #007bff;
            color: #fff;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if(isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="nama_admin">Nama Admin:</label>
                <input type="text" class="form-control" name="nama_admin" id="nama_admin" required>
            </div>
            <div class="form-group">
                <label for="pass">Password:</label>
                <input type="password" class="form-control" name="pass" id="pass" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
    </div>
</body>
</html>
