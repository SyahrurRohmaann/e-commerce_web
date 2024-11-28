<?php
session_start();
require 'config.php';

header('Content-Type: application/json');







class Cart {
    private $db;

    public function __construct() {
        $this->db = new config();
    }

    public function addToCart($productId, $quantity, $size) {
        try {
            // Validasi input
            if (empty($productId) || empty($size) || $quantity <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid product ID, size, or quantity.'
                ];
            }

            // Validasi user login
            if (!isset($_SESSION['user_id'])) {
                return [
                    'success' => false,
                    'message' => 'User not logged in.'
                ];
            }

            $userId = $_SESSION['user_id'];

            // Validasi stok
            $stmt = $this->db->prepare("
                SELECT stock 
                FROM produk 
                WHERE id_produk = :product_id
            ");
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.'
                ];
            }

            if ($quantity > $product['stock']) {
                return [
                    'success' => false,
                    'message' => 'Quantity exceeds available stock.'
                ];
            }

            // Cek apakah produk sudah ada di keranjang
            $stmt = $this->db->prepare("
                SELECT id_keranjang, jumlah 
                FROM keranjang 
                WHERE id_user = :user_id 
                  AND id_produk = :product_id 
                  AND ukuran = :size
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->bindParam(':size', $size);
            $stmt->execute();
            $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cartItem) {
                // Update jumlah produk
                $newQuantity = $cartItem['jumlah'] + $quantity;

                if ($newQuantity > $product['stock']) {
                    return [
                        'success' => false,
                        'message' => 'Quantity exceeds available stock.'
                    ];
                }

                $stmt = $this->db->prepare("
                    UPDATE keranjang 
                    SET jumlah = :quantity 
                    WHERE id_keranjang = :cart_id
                ");
                $stmt->bindParam(':quantity', $newQuantity, PDO::PARAM_INT);
                $stmt->bindParam(':cart_id', $cartItem['id_keranjang']);
            } else {
                // Tambahkan produk baru ke keranjang
                $stmt = $this->db->prepare("
                    INSERT INTO keranjang (id_user, id_produk, jumlah, ukuran)
                    VALUES (:user_id, :product_id, :quantity, :size)
                ");
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':product_id', $productId);
                $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindParam(':size', $size);
            }

            $stmt->execute();

            return [
                'success' => true,
                'message' => 'Product added to cart successfully.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    public function getCartItems() {
        try {
            $stmt = $this->db->prepare("
                SELECT k.*, p.nama, p.harga, p.gambar, p.stock, k.ukuran 
                FROM keranjang k
                JOIN produk p ON k.id_produk = p.id_produk
                WHERE k.id_user = :user_id
            ");
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    

public function updateQuantity($cartId, $quantity) {
    try {
        // Ambil stok produk yang ada di keranjang
        $stmt = $this->db->prepare("
            SELECT p.stock 
            FROM keranjang k
            JOIN produk p ON k.id_produk = p.id_produk
            WHERE k.id_keranjang = :cart_id
        ");
        $stmt->bindParam(':cart_id', $cartId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jika produk tidak ditemukan di keranjang
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found in cart'
            ];
        }

        // Pastikan quantity yang diminta tidak melebihi stok
        if ($quantity < 1 || $quantity > $product['stock']) {
            return [
                'success' => false,
                'message' => 'Quantity is invalid or exceeds stock'
            ];
        }

        // Update jumlah di keranjang
        $stmt = $this->db->prepare("
            UPDATE keranjang 
            SET jumlah = :quantity 
            WHERE id_keranjang = :cart_id
        ");
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':cart_id', $cartId, PDO::PARAM_INT);
        $stmt->execute();
        
        return ['success' => true];
    } catch (Exception $e) {
        // Tangani error dan kirim pesan yang sesuai
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}



    public function removeItem($cartId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM keranjang 
                WHERE id_keranjang = :cart_id
            ");
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->execute();
            
            return ['success' => true];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function checkout() {
        try {
            $this->db->beginTransaction();

            // Get cart items
            $stmt = $this->db->prepare("
                SELECT k.*, p.harga, p.stock 
                FROM keranjang k
                JOIN produk p ON k.id_produk = p.id_produk
                WHERE k.id_user = :user_id
            ");
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                throw new Exception('Cart is empty');
            }

            // Calculate total
            $total = 0;
            foreach ($items as $item) {
                $total += $item['harga'] * $item['jumlah'];
            }

            // Create transaction
            $stmt = $this->db->prepare("
                INSERT INTO transaksi (id_user, total, tanggal)
                VALUES (:user_id, :total, NOW())
            ");
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':total', $total);
            $stmt->execute();
            
            $transactionId = $this->db->lastInsertId();

            // Create transaction details and update stock
            foreach ($items as $item) {
                // Validate stock
                if ($item['jumlah'] > $item['stock']) {
                    throw new Exception('Insufficient stock for some items');
                }

                // Create detail
                $stmt = $this->db->prepare("
                    INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, harga)
                    VALUES (:trans_id, :prod_id, :quantity, :price)
                ");
                $stmt->bindParam(':trans_id', $transactionId);
                $stmt->bindParam(':prod_id', $item['id_produk']);
                $stmt->bindParam(':quantity', $item['jumlah']);
                $stmt->bindParam(':price', $item['harga']);
                $stmt->execute();

                // Update stock
                $stmt = $this->db->prepare("
                    UPDATE produk 
                    SET stock = stock - :quantity 
                    WHERE id_produk = :prod_id
                ");
                $stmt->bindParam(':quantity', $item['jumlah']);
                $stmt->bindParam(':prod_id', $item['id_produk']);
                $stmt->execute();
            }

            // Clear cart
            $stmt = $this->db->prepare("
                DELETE FROM keranjang 
                WHERE id_user = :user_id
            ");
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();

            $this->db->commit();
            return ['success' => true];

        } catch(Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

// Handle requests
$cart = new Cart();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] === 'get_cart_items') {
            echo json_encode($cart->getCartItems());
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['action'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Action is required.'
            ]);
            exit;
        }

        switch ($data['action']) {
            case 'add_to_cart':
                if (!isset($data['product_id'], $data['quantity'], $data['size'])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Product ID, quantity, and size are required.'
                    ]);
                    exit;
                }
                echo json_encode($cart->addToCart($data['product_id'], $data['quantity'], $data['size']));
                break;

            case 'update_quantity':
                if (!isset($data['cart_id'], $data['quantity'])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Cart ID and quantity are required.'
                    ]);
                    exit;
                }
                echo json_encode($cart->updateQuantity($data['cart_id'], $data['quantity']));
                break;

            case 'remove_item':
                if (!isset($data['cart_id'])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Cart ID is required.'
                    ]);
                    exit;
                }
                echo json_encode($cart->removeItem($data['cart_id']));
                break;

            case 'checkout':
                echo json_encode($cart->checkout());
                break;

            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action.'
                ]);
        }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
