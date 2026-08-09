<?php
require 'config.php';

header('Content-Type: application/json');

try {
    $db = new config();
    $stmt = $db->prepare("SELECT id_kategori, nama_kategori FROM kategori");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
