<?php
  session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?>VielaDefis</title>
    <!-- Fonts -->
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@300,400,500,700&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vollkorn:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet" />
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="../assets/styles/style.css" rel="stylesheet" />
</head>
<body>
    <div class="announcementBar">
        <p id="welcomeMessage">
            <?php if(isset($_SESSION['user_id'])): ?>
                Welcome, <span id="userName"><?= $_SESSION['user_name'] ?></span>!
                <a>Selamat berbelanja</a>
            <?php else: ?>
                <a href="signup.php">Sign up</a>
                <a>or</a>
                <a href="login.php">Login</a>
                First Please!
            <?php endif; ?>
        </p>
    </div>
    

    <div class="header-top">
    <a href="index.php" class="header-logo">
        <img src="../assets/image/logo.png" alt="logo" width="170" height="170" />
    </a>
    <div class="header-menu">
        <!-- Menu items -->
        <ul class="desktop-menu-category-list">
    <li class="menu-category">
        <a href="shop.php" class="menu-title" id="shopMenu">Shop</a>
        <ul class="dropdown-list" id="categoryDropdown"></ul>
    </li>
    <li class="menu-category">
        <a href="index.php#new-arrival-section" class="menu-title">New Arrival</a>
    </li>
    <li class="menu-category">
    <a href="index.php#top-selling-section" class="menu-title">Top Selling</a>
    </li>
    <li class="menu-category">
        <a href="#" class="menu-title">Brands</a>
    </li>
</ul>


        <div class="header-search-container">
            <input type="search" name="search" class="search-field" placeholder="Search for products.." />
        </div>

        <div class="header-user-actions">
            <button class="action-btn" id="cartButton">
                <i data-feather="shopping-cart"></i>
                <span class="count" id="cartCount">0</span>
            </button>

            <button id="userButton" class="action-btn">
                <i data-feather="user"></i>
                <span class="userName" id="userStatus" style="color: #fff;">
                    <?= isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '' ?>
                </span>
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('shopMenu').addEventListener('mouseover', function() {
    fetch('../php/get_categories.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const dropdown = document.getElementById('categoryDropdown');
                dropdown.innerHTML = data.categories.map(category => `
                    <li class="dropdown-item">
                        <a href="shop.php?category=${category.id_kategori}">${category.nama_kategori}</a>
                    </li>
                `).join('');
            } else {
                console.error('Failed to load categories');
            }
        })
        .catch(error => console.error('Error:', error));
});
</script>


    <div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
    <div id="cartSidebar" class="cart-sidebar">
        <div class="cart-header">
            <h2>Shopping Cart</h2>
            <button class="close-btn" id="closeBtn">&times;</button>
        </div>
        <div id="cartItems" class="cart-items">
            <!-- Items will be loaded here -->
        </div>
        <div class="cart-summary">
            <div class="total-amount">Total: <span id="totalAmount">Rp 0</span></div>
            <button id="checkoutBtn" class="checkout-btn">Checkout</button>
        </div>
    </div>

    <div id="checkoutModal" class="modal">
    <div class="modal-content">
        <h3>Konfirmasi Checkout</h3>
        <p>Total Pembayaran: <span id="modalTotalAmount">Rp 0</span></p>
        <div class="modal-buttons">
            <button id="confirm-co" onclick="processCheckout()">Konfirmasi</button>
            <button id="cancel-co"onclick="closeModal('checkoutModal')">Batal</button>
        </div>
    </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    
        const cartButton = document.getElementById('cartButton');
        const closeButton = document.getElementById('closeBtn');
        const cartOverlay = document.getElementById('cartOverlay');
        const cartSidebar = document.getElementById('cartSidebar');
        const confirmBtn = document.getElementById('confirm-co');
        const cancelBtn = document.getElementById('cancel-co');

        cartOverlay.addEventListener('click', function() {
            closeCart();
        });
        closeButton.addEventListener('click', function() {
            closeCart();
        });
        cartButton.addEventListener('click', function() {
            cartOverlay.style.display = 'block';
            cartSidebar.style.right = '0';
            loadCartItems(); // Load cart items when sidebar opens
        });

        function closeCart() {
    const cartOverlay = document.getElementById('cartOverlay');
    const cartSidebar = document.getElementById('cartSidebar');
    
    if (cartOverlay && cartSidebar) {
        cartOverlay.style.display = 'none';
        cartSidebar.style.right = '-400px';
    } else {
        console.error('Element cartOverlay atau cartSidebar tidak ditemukan');
    }
    }


        if(userButton) {
            userButton.addEventListener('click', function() {
                const userData = sessionStorage.getItem('user');
                if (<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?> && userData) {
                    window.location.href = 'profile.php';
                } else {
                    window.location.href = "login.php";
                }
            });
        }

        // Memanggil fungsi untuk memeriksa status login
        checkLoginStatus();

        function checkLoginStatus() {
            if(sessionStorage.getItem('user')) {
                const user = JSON.parse(sessionStorage.getItem('user'));
                welcomeMessage.innerHTML = `
                    Welcome, <span id="userName">${user.nama_user}</span>!
                    <a>Selamat berbelanja</a>
                `;
                userStatus.textContent = user.nama_user;
                
                updateCartCount();
                loadCartItems();
            } else {
                welcomeMessage.innerHTML = `
                    Silahkan 
                    <a href="login.php">Login</a>
                    Terlebih Dahulu!
                `;
                userStatus.textContent = '';
            }
        }

        function loadCartItems() {
    const userData = JSON.parse(sessionStorage.getItem('user'));
    if (<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?> && userData) {
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
            .catch(error => console.error('Error:', error));
    } else {
        console.log('User not logged in or user data not found');
    }
    }

    function generateCartItemHTML(item) {
    let gambar = item.gambar;
    try {
        // Coba parse gambar sebagai JSON array
        const gambarArray = JSON.parse(gambar);
        if (Array.isArray(gambarArray) && gambarArray.length > 0) {
            gambar = gambarArray[0]; // Ambil gambar pertama dari array
        }
    } catch (e) {
        // Jika parsing gagal, gambar tetap sebagai string tunggal
    }

    // Tambahkan validasi untuk ukuran produk
    const size = item.ukuran ? item.ukuran : "Default Size";

    return `
        <div class="cart-item" data-id="${item.id_keranjang}">
            <img src="../uploads/${gambar}" alt="${item.nama}" 
                onerror="this.src='../assets/image/logo.png'">
            <div class="item-details">
                <h3>${item.nama}</h3>
                <p>Rp ${formatPrice(item.harga)}</p>
                <p>Size: ${size}</p> <!-- Tambahkan ukuran produk di sini -->
                <p>Stock: ${item.stock}</p>
                <div class="quantity-controls">
                    <button class="quantity-decrease" data-id="${item.id_keranjang}" data-quantity="${item.jumlah - 1}" 
                        data-stock="${item.stock}" ${item.jumlah <= 1 ? 'disabled' : ''}>-</button>
                    <span>${item.jumlah}</span>
                    <button class="quantity-increase" data-id="${item.id_keranjang}" data-quantity="${item.jumlah + 1}" 
                        data-stock="${item.stock}" ${item.jumlah >= item.stock ? 'disabled' : ''}>+</button>
                </div>
                <button class="remove-btn" data-id="${item.id_keranjang}">Hapus</button>
            </div>
        </div>
    `;
}



    function displayCartItems(items) {
    const container = document.getElementById('cartItems');

    if (items.length === 0) {
        container.innerHTML = '<p class="empty-cart">Keranjang belanja kosong</p>';
        document.getElementById('checkoutBtn').disabled = true;
        return;
    } else {
        document.getElementById('checkoutBtn').disabled = false;
    }

    container.innerHTML = items.map(generateCartItemHTML).join('');
    
    // Setelah item ditampilkan, tambahkan event listener ke tombol
    document.querySelectorAll('.quantity-decrease').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const newQuantity = this.dataset.quantity;
            const stock = this.dataset.stock;
            updateQuantity(id, newQuantity, stock);
        });
    });

    document.querySelectorAll('.quantity-increase').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const newQuantity = this.dataset.quantity;
            const stock = this.dataset.stock;
            updateQuantity(id, newQuantity, stock);
        });
    });

    document.querySelectorAll('.remove-btn').forEach(button => {
        button.addEventListener('click', function() {
            console.log('button clicked');
            const id = this.dataset.id;
            removeItem(id);
        });
    });
}


    function updateQuantity(cartId, quantity, stock) {
    if (quantity < 1 || quantity > stock) {
        showNotification('Invalid quantity');
        return;
    }

    fetch("../php/cart-api.php", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            action: 'update_quantity', 
            cart_id: cartId, 
            quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCartItems();
            updateCartCount();
        } else {
            showNotification(data.message);
        }
    })
    .catch(error => {
        console.error("Error updating quantity:", error);
        showNotification('Error updating quantity.');
    });
    }

    function removeItem(cartId) {
    if (!confirm('Apakah Anda yakin ingin menghapus item ini?')){
        return;
    }

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
            updateCartCount(); // Update badge count
        }
    })
    .catch(error => console.error('Error:', error));
    }

    function processCheckout() {
    fetch('../php/cart-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'checkout'
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Checkout berhasil!');
            closeModal('checkoutModal');
        } else {
            showNotification(result.message || 'Checkout gagal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat checkout');
    });
    }       

    function updateTotalAmount(items) {
    const total = items.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
    document.getElementById('totalAmount').textContent = `Rp ${formatPrice(total)}`;
    document.getElementById('modalTotalAmount').textContent = `Rp ${formatPrice(total)}`;
    }

    function formatPrice(price) {
    return new Intl.NumberFormat('id-ID').format(price);
    }   

    // Modal functions
    document.getElementById('checkoutBtn').addEventListener('click', function() {
    document.getElementById('checkoutModal').style.display = 'block';
    closeCart();
    });

    function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    }

        function updateCartCount() {
            const cartCount = document.getElementById("cartCount");
            if (!cartCount) return;
            const userData = JSON.parse(sessionStorage.getItem('user'));
            if (<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?> && userData) {
                fetch("../php/product.php?action=get_cart_count")
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            cartCount.textContent = data.count;
                            cartCount.style.display = data.count > 0 ? 'block' : 'none';
                        } else {
                            cartCount.textContent = '';
                            cartCount.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        cartCount.textContent = '';
                        cartCount.style.display = 'none';
                    });
            } else {
                cartCount.textContent = '';
                cartCount.style.display = 'none';
            }
        }
        confirmBtn.addEventListener('click', function() {
            processCheckout();
        });
        cancelBtn.addEventListener('click', function() {
            closeModal('checkoutModal');
        });

        // Initial cart count update
        updateCartCount();
    });

    // Fungsi helper
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    $('.increase-btn').on('click', function () {
    const itemId = $(this).data('item-id');

    $.post('cart-api.php', { item_id: itemId, action: 'increase' }, function (response) {
        if (response.error) {
            alert(response.error);
        } else {
            // Update tampilan keranjang atau stok
            updateCartView();
        }
    }, 'json');
});

$('.decrease-btn').on('click', function () {
    const itemId = $(this).data('item-id');

    $.post('cart-api.php', { item_id: itemId, action: 'decrease' }, function (response) {
        if (response.error) {
            alert(response.error);
        } else {
            // Update tampilan keranjang atau stok
            updateCartView();
        }
    }, 'json');
});

    </script>

    <style>
    /* Style untuk notifikasi */
    html {
         scroll-behavior: smooth; 
        }
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #49749c;
        color: white;
        padding: 15px 25px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Tambahkan CSS untuk count badge */
    .count {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #49749c;
        color: white;
        font-size: 12px;
        padding: 2px 6px;
        border-radius: 50%;
        min-width: 18px;
        text-align: center;
        display: none; /* Hidden by default */
    }

    .action-btn {
        position: relative;
        /* ... style lainnya ... */
    }

    .cart-overlay {
        display: none;
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

    /* Style Slider */

    :root {
    --active-color: hsl(204 100 53);
    --bg-color: #e1e3e5;
    --icon-default: hsl(203 5 75);
    --icon-accent: hsl(203 15 98);
    --navigation-color: hsl(203 5 25 / 0.3);
    }

    .slider-awal {
    display: flex;
    margin-top: 20px;
    justify-content: center;
    align-items: center;
    }

    .image-slider {
    display: flex;
    flex-flow: column;
    width: clamp(360px, 96vw, 1200px);
    aspect-ratio: 16 / 9;
    min-height: 300px;
    overflow: hidden;
    border-radius: 8px;
    container-type: inline-size;
    contain: content;
    background-color: #0006;
    box-shadow: rgba(0, 0, 0, 0.2) 0px 1px 2px, rgba(0, 0, 0, 0.3) 0px 2px 4px,
        rgba(0, 0, 0, 0.25) 0px 4px 8px, rgba(0, 0, 0, 0.2) 0px 8px 16px,
        rgba(0, 0, 0, 0.15) 0px 16px 32px;
    }

    .slider__content {
    flex-grow: 1;
    display: flex;
    justify-content: space-between;
    }

    .slider-control--button {
    border: 0;
    background: 0;
    outline: 0;
    cursor: pointer;
    place-content: center;
    padding-inline: 3vw;
    z-index: 1;
    display: grid;
    }

    .icon {
    height: 2rem;
    width: 2rem;
    fill: var(--icon-default);
    border-radius: 50%;
    }

    .slider-control--button:where(:hover) {
    background-image: linear-gradient(
        to var(--position),
        #0000 0%,
        #0002,
        80%,
        #0006 100%
    );
    .icon {
        fill: var(--icon-accent);
        background: #0001;
    }
    }

    .slider-control--button:active {
    outline: 0.2em solid hsl(204 100 53);
    outline-offset: -0.5em;
    }

    .prev-button {
    --position: left;
    }
    .next-button {
    --position: right;
    }

    .image-display {
    position: fixed;
    inset: 0;
    }

    .slider-navigation {
    z-index: 10;
    display: grid;
    grid-auto-flow: column;
    grid-template-columns: repeat(6, 1fr);
    grid-auto-columns: 100%;
    gap: 1.25rem;
    padding: 1rem;
    place-content: center;
    background-color: var(--navigation-color);
    backdrop-filter: blur(6px);
    }

    .nav-button {
    display: grid;
    width: 100%;
    height: 100%;
    border-radius: 0.5em;
    overflow: hidden;
    align-items: center;
    justify-content: center;
    border: 0;
    aspect-ratio: 16 / 9;
    transition: filter 150ms linear, scale 266ms ease;
    }

    .thumbnail {
    display: block;
    max-width: 100%;
    width: 100%;
    object-fit: cover;
    height: 100%;
    }

    .nav-button[aria-selected="true"] {
    scale: 1.1;
    }

    .nav-button[aria-selected="true"],
    .nav-button:focus-visible {
    outline: 0.2em solid var(--active-color);
    outline-offset: 0.2em;
    }

    .nav-button[aria-selected="false"] {
    filter: opacity(0.7);
    }

    .nav-button[aria-selected="false"]:where(:hover, :focus-visible) {
    filter: opacity(1);
    }

    @container (max-width: 660px) {
    .nav-button:not(:has(img)) {
        background-color: rgb(241, 235, 232);
    }

    .slider-navigation {
        display: flex;
        justify-content: center;
        padding-block: 1.5em;
    }

    .nav-button {
        inline-size: 0.625rem;
        aspect-ratio: 1;
        border-radius: 50%;
    }

    .nav-button > .thumbnail {
        display: none;
    }

    .nav-button[aria-selected="true"] {
        background-color: black;
        scale: 1.5;
    }
    }

    </style>
</body>
</html>