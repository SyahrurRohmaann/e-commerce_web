<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

class Cart {
    private $db;

    public function __construct() {
        $this->db = new config();
    }

    public function getCartItems() {
        try {
            $stmt = $this->db->prepare("
                SELECT k.*, p.nama, p.harga, p.gambar, p.stock 
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
            // Validasi stok
            $stmt = $this->db->prepare("
                SELECT p.stock 
                FROM keranjang k
                JOIN produk p ON k.id_produk = p.id_produk
                WHERE k.id_keranjang = :cart_id
            ");
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($quantity > $product['stock']) {
                return [
                    'success' => false,
                    'message' => 'Quantity exceeds available stock'
                ];
            }

            $stmt = $this->db->prepare("
                UPDATE keranjang 
                SET jumlah = :quantity 
                WHERE id_keranjang = :cart_id
            ");
            $stmt->bindParam(':quantity', $quantity);
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_cart_items') {
        echo json_encode($cart->getCartItems());
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($data['action']) {
        case 'update_quantity':
            echo json_encode($cart->updateQuantity($data['cart_id'], $data['quantity']));
            break;
        case 'remove_item':
            echo json_encode($cart->removeItem($data['cart_id']));
            break;
        case 'checkout':
            echo json_encode($cart->checkout());
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
}
?>