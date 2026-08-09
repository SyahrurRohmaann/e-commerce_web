<?php
require 'config.php';

header('Content-Type: application/json');

if (isset($_GET['q'])) {
    $query = $_GET['q'];

    try {
        $db = new config();
        $stmt = $db->prepare("SELECT id_produk, nama, harga FROM produk WHERE nama LIKE :query LIMIT 10");
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No search query provided'
    ]);
}
?>
