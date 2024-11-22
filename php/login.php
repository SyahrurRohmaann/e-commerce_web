<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json');

try {
    require 'config.php';

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if(empty($email) || empty($password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Email dan password harus diisi'
            ]);
            exit;
        }

        try {
            $db = new config();
            
            $stmt = $db->prepare("SELECT * FROM pengguna WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($password == $user['pass']) {
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['user_name'] = $user['nama_user'];
                    
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Login berhasil',
                        'user' => [
                            'id_user' => $user['id_user'],
                            'nama_user' => $user['nama_user'],
                            'email' => $user['email']
                        ]
                    ]);
                    exit;
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Password salah'
                    ]);
                    exit;
                }
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Email tidak terdaftar'
                ]);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
            exit;
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
?>