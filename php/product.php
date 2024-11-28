<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

class Product {
    private $db;

    public function __construct() {
        $this->db = new config();
    }

    // Fungsi untuk mendapatkan produk dengan paginasi dan filter kategori
    public function getProducts($page = 1, $limit = 8, $category = null) {
        try {
            $offset = ($page - 1) * $limit;
            
            if ($category) {
                // Jika kategori ditentukan, ambil produk berdasarkan kategori
                $stmt = $this->db->prepare("
                    SELECT p.*, k.nama_kategori 
                    FROM produk p 
                    JOIN kategori k ON p.id_kategori = k.id_kategori
                    WHERE p.id_kategori = :category
                    ORDER BY p.id_produk DESC
                    LIMIT :limit OFFSET :offset
                ");
                $stmt->bindParam(':category', $category, PDO::PARAM_INT);
            } else {
                // Jika tidak ada kategori, ambil semua produk
                $stmt = $this->db->prepare("
                    SELECT p.*, k.nama_kategori 
                    FROM produk p 
                    JOIN kategori k ON p.id_kategori = k.id_kategori
                    ORDER BY p.id_produk DESC
                    LIMIT :limit OFFSET :offset
                ");
            }
            
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Dapatkan total produk untuk menghitung total halaman
            $totalStmt = $this->db->prepare("SELECT COUNT(*) as total FROM produk" . ($category ? " WHERE id_kategori = :category" : ""));
            if ($category) {
                $totalStmt->bindParam(':category', $category, PDO::PARAM_INT);
            }
            $totalStmt->execute();
            $totalProducts = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalProducts / $limit);

            return [
                'success' => true,
                'data' => [
                    'products' => $products,
                    'totalPages' => $totalPages,
                ]
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Fungsi untuk menambah ke keranjang
    public function addToCart($data) {
        try {
            // Validasi user login
            if (!isset($_SESSION['user_id'])) {
                return [
                    'success' => false,
                    'message' => 'User not logged in'
                ];
            }

            // Gunakan session user_id
            $userId = $_SESSION['user_id'];
            $productId = $data['product_id'] ?? null;
            $quantity = $data['quantity'] ?? 1;

            if (!$productId) {
                throw new Exception('Product ID required');
            }

            // Cek stock produk
            $stmt = $this->db->prepare("SELECT stock FROM produk WHERE id_produk = :product_id");
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product['stock'] < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Insufficient stock'
                ];
            }

            // Cek apakah produk sudah ada di keranjang
            $stmt = $this->db->prepare("
                SELECT id_keranjang, jumlah 
                FROM keranjang 
                WHERE id_user = :user_id AND id_produk = :product_id
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            $cart = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cart) {
                // Update jumlah
                $newQuantity = $cart['jumlah'] + $quantity;
                $stmt = $this->db->prepare("
                    UPDATE keranjang 
                    SET jumlah = :quantity 
                    WHERE id_keranjang = :cart_id
                ");
                $stmt->bindParam(':quantity', $newQuantity);
                $stmt->bindParam(':cart_id', $cart['id_keranjang']);
            } else {
                // Insert baru
                $stmt = $this->db->prepare("
                    INSERT INTO keranjang (id_user, id_produk, jumlah) 
                    VALUES (:user_id, :product_id, :quantity)
                ");
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':product_id', $productId);
                $stmt->bindParam(':quantity', $quantity);
            }

            $stmt->execute();
            return ['success' => true];

        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Fungsi untuk mendapatkan jumlah item di keranjang
    public function getCartCount($userId = null) {
        try {
            // Gunakan session user_id jika tidak ada parameter
            $userId = $userId ?? $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return [
                    'success' => false,
                    'message' => 'User not logged in',
                    'count' => 0
                ];
            }

            $stmt = $this->db->prepare("
                SELECT SUM(jumlah) as total_items 
                FROM keranjang 
                WHERE id_user = :user_id
            ");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
            return [
                'success' => true,
                'count' => (int)$result['total_items']
            ];

        } catch(Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'count' => 0
            ];
        }
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product = new Product();
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'add_to_cart':
                echo json_encode($product->addToCart($data));
                break;
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action'
                ]);
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $product = new Product();
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'get_products':
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 9;
                $category = $_GET['category'] ?? null;
                echo json_encode($product->getProducts($page, $limit, $category));
                break;
            case 'get_cart_count':
                $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
                echo json_encode($product->getCartCount($userId));
                break;
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action'
                ]);
        }
    }
}
?>
