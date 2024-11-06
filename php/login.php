<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

try {
    require 'config.php';  // Gunakan path absolut

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        try {
            $db = new config();
            error_log("Database connection successful");
            
            $stmt = $db->prepare("SELECT * FROM pengguna WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            error_log("Query executed for email: " . $email);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($password == $user['pass']) {
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['user_name'] = $user['nama_user'];
                    echo "Login berhasil";
                    error_log("Login successful for user: " . $user['nama_user']);
                }else{
                    echo"Password salah";
                }
            } else {
                echo "Email tidak terdaftar";
                error_log("Email not found: " . $email);
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo "Login gagal";
        }
    }
} catch (Exception $e) {
    error_log("Critical error: " . $e->getMessage());
    echo "Login gagal";
}
?>