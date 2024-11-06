<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    if ($password !== $cpassword) {
        echo "Password tidak sama";
        exit;
    }

    try {
        $db = new config();
        
        // Check if email already exists
        $checkEmail = $db->prepare("SELECT email FROM pengguna WHERE email = :email");
        $checkEmail->bindParam(':email', $email);
        $checkEmail->execute();
        
        if ($checkEmail->rowCount() > 0) {
            echo "Email sudah terdaftar";
            exit;
        }

        // If email doesn't exist, proceed with registration
        $stmt = $db->prepare("INSERT INTO pengguna (nama_user, email, pass) VALUES (:nama_user, :email, :password)");
        $stmt->bindParam(':nama_user', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        echo "Registrasi berhasil";
    } catch (Exception $e) {
        echo "Registrasi gagal";
    }
}
?>
