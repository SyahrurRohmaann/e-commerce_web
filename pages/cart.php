<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit('Not logged in');
}
?>

<!-- Cart HTML -->
<div class="cart-overlay" onclick="closeCart()"></div>
<div class="cart-popup-content">
    <div class="cart-header">
        <h2>Shopping Cart</h2>
        <button class="close-btn" onclick="closeCart()">&times;</button>
    </div>
    
    <div id="cartItems" class="cart-items">
        <!-- Items will be loaded here -->
    </div>
    
    <div class="cart-summary">
        <div class="total-amount">Total: <span id="totalAmount">Rp 0</span></div>
        <button id="checkoutBtn" class="checkout-btn">Checkout</button>
    </div>
</div>

<style>
.cart-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
}

.cart-sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100vh;
    background: white;
    box-shadow: -2px 0 5px rgba(0,0,0,0.1);
    transition: right 0.3s ease;
    z-index: 999;
    overflow-y: auto;
}

.cart-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}

.cart-items {
    padding: 15px;
    max-height: calc(100vh - 150px);
    overflow-y: auto;
}

.cart-summary {
    padding: 15px;
    border-top: 1px solid #ddd;
    background: white;
    position: sticky;
    bottom: 0;
}

.checkout-btn {
    width: 100%;
    padding: 10px;
    background: #49749c;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.checkout-btn:hover {
    background: #3a5d7c;
}

.cart-item {
    display: flex;
    padding: 15px;
    border-bottom: 1px solid #eee;
    align-items: center;
}

.cart-item img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    margin-right: 15px;
}

.item-details {
    flex: 1;
}

.remove-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    padding: 5px;
    color: #ff4444;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.quantity-controls button {
    padding: 5px 10px;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
}
</style>

<script>
// Fungsi untuk menutup cart
function closeCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    const overlay = document.querySelector('.cart-overlay');
    if(cartSidebar && overlay) {
        cartSidebar.style.right = '-400px';
        overlay.style.display = 'none';
    }
}

// Fungsi untuk memuat items
function loadCartItems() {
    console.log('Loading cart items...'); // Debug
    fetch('../php/cart-api.php?action=get_cart_items')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayCartItems(result.data);
                updateTotalAmount(result.data);
            } else {
                console.error('Failed to load cart items:', result.message);
            }
        })
        .catch(error => console.error('Error loading cart items:', error));
}

// Fungsi untuk menampilkan items
function displayCartItems(items) {
    const container = document.getElementById('cartItems');
    if (!container) {
        console.error('Cart container not found');
        return;
    }
    
    if (!items || items.length === 0) {
        container.innerHTML = '<p>Your cart is empty</p>';
        return;
    }
    
    container.innerHTML = items.map(item => `
        <div class="cart-item">
            <img src="../uploads/${item.gambar}" alt="${item.nama}" onerror="this.src='../assets/image/product-1.jpg'">
            <div class="item-details">
                <h3>${item.nama}</h3>
                <p>Rp ${parseInt(item.harga).toLocaleString('id-ID')}</p>
                <div class="quantity-controls">
                    <button onclick="updateQuantity(${item.id_keranjang}, ${item.jumlah - 1})">-</button>
                    <span>${item.jumlah}</span>
                    <button onclick="updateQuantity(${item.id_keranjang}, ${item.jumlah + 1})">+</button>
                </div>
            </div>
            <button class="remove-btn" onclick="removeItem(${item.id_keranjang})">×</button>
        </div>
    `).join('');
}

// Fungsi untuk update total
function updateTotalAmount(items) {
    const totalElement = document.getElementById('totalAmount');
    if (!totalElement) return;
    
    const total = items.reduce((sum, item) => sum + (parseInt(item.harga) * item.jumlah), 0);
    totalElement.textContent = `Rp ${total.toLocaleString('id-ID')}`;
}

// Fungsi untuk update quantity
function updateQuantity(cartId, newQuantity) {
    if (newQuantity < 1) return;
    
    fetch('../php/cart-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_quantity',
            cart_id: cartId,
            quantity: newQuantity
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            loadCartItems();
        } else {
            alert(result.message || 'Gagal update quantity');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Fungsi untuk menghapus item
function removeItem(cartId) {
    if (!confirm('Apakah Anda yakin ingin menghapus item ini?')) return;
    
    fetch('../php/cart-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove_item',
            cart_id: cartId
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            loadCartItems();
        } else {
            alert(result.message || 'Gagal menghapus item');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Panggil loadCartItems saat cart dibuka
loadCartItems();
</script>
</body>
</html>