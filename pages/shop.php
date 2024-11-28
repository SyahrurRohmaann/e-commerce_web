<?php

$pageTitle = 'Shop';
include '../templates/header.php';
?>

<div class="container my-5">
    <div class="row mb-3">
        <div class="col-md-4">
            <select id="categoryFilter" class="form-select" onchange="filterProducts()">
                <option value="">All Categories</option>
                <!-- Kategori akan diisi melalui JavaScript -->
            </select>
        </div>
    </div>
    <div class="product-grid row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="productContainer"></div>
    <nav style="text-align:center;">
        <ul class="pagination justify-content-center" id="pagination"></ul>
    </nav>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadCategories();
    loadProducts();
});

function loadCategories() {
    fetch("../php/get_categories.php")
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const select = document.getElementById("categoryFilter");
                result.data.forEach(category => {
                    const option = document.createElement("option");
                    option.value = category.id_kategori;
                    option.textContent = category.nama_kategori;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error("Error:", error));
}

function loadProducts(categoryId = "", sortBy = "", page = 1) {
    let url = `../php/product.php?action=get_products&page=${page}&limit=9`;
    if (categoryId) url += `&category=${categoryId}`;
    if (sortBy) url += `&sort=${sortBy}`;

    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const groupedProducts = groupProductsByName(result.data.products);
                const totalPages = result.data.totalPages;

                const container = document.getElementById("productContainer");
                container.innerHTML = groupedProducts.map(product => {
                    let gambarArray = [];
                    try {
                        gambarArray = JSON.parse(product.gambar);
                    } catch (e) {
                        console.error("JSON parse error:", e);
                    }

                    let gambar = "";
                    if (Array.isArray(gambarArray) && gambarArray.length > 0) {
                        gambar = gambarArray[0];
                    } else {
                        gambar = product.gambar;
                    }

                    const sizes = product.sizes.map(size => size.ukuran).join(', ');

                    return `
                    <div class="product-card">
                        <a href="product_detail.php?id_produk=${product.id_produk}">
                            <img src="../uploads/${gambar}" alt="${product.nama}" class="product-image" onerror="this.src='../assets/image/product-1.jpg'">
                        </a>
                        <div class="product-details">
                            <h3 class="product-name">${product.nama}</h3>
                            <div class="product-price">Rp ${formatPrice(product.harga)}</div>
                            <div class="product-size">Sizes: ${sizes}</div>
                            <div class="product-stock">Total Stock: ${product.total_stock}</div>
                            <button onclick="addToCart(${product.id_produk})" 
                                    class="add-to-cart-btn ${product.total_stock < 1 ? 'out-of-stock' : ''}"
                                    ${product.total_stock < 1 ? 'disabled' : ''}>
                                ${product.total_stock < 1 ? "Out of Stock" : "Add to Cart(tidak bisa karena harus milih saize dulu)"}
                            </button>
                        </div>
                    </div>
                    `;
                }).join("");

                renderPagination(totalPages, page);
            } else {
                showNotification(result.error || 'Error loading products');
            }
        })
        .catch(error => console.error("Error:", error));
}

function groupProductsByName(products) {
    const grouped = {};

    products.forEach(product => {
        if (!grouped[product.nama]) {
            grouped[product.nama] = {
                ...product,
                sizes: [],
                total_stock: 0
            };
        }
        grouped[product.nama].sizes.push({
            ukuran: product.ukuran,
            stock: product.stock
        });
        grouped[product.nama].total_stock += product.stock;
    });

    return Object.values(grouped);
}

function filterProducts() {
    const categoryId = document.getElementById("categoryFilter").value;
    loadProducts(categoryId, "", 1);
}

function formatPrice(price) {
    return new Intl.NumberFormat("id-ID").format(price);
}

function addToCart(productId) {
    if (!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?> || !sessionStorage.getItem('user')) {
        showNotification("Silakan login terlebih dahulu");
        return;
    }

    fetch("../php/cart-api.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            action: 'add_to_cart',
            product_id: productId,
            quantity: 1
        }),
    })
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            showNotification("Product added to cart!");
            updateCartCount();
        } else {
            showNotification(data.message || "Failed to add product to cart");
        }
    })
    .catch((error) => {
        console.error("Error:", error);
        showNotification("Error adding product to cart");
    });
}

function updateCartCount() {
    const cartCount = document.getElementById("cartCount");
    if (!cartCount) return;

    const userData = sessionStorage.getItem('user');

    if (<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?> && userData) {
        fetch("../php/cart-api.php?action=get_cart_count")
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

function renderPagination(totalPages, currentPage) {
    const pagination = document.getElementById('pagination');
    const categoryId = document.getElementById("categoryFilter").value;
    let paginationHTML = '';

    for (let i = 1; i <= totalPages; i++) {
        paginationHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <button class="pagination-btn page-link" onclick="loadProducts('${categoryId}', '', ${i})">${i}</button> 
        </li>`;
    }

    pagination.innerHTML = paginationHTML;
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<?php 
include '../templates/footer.php'; 
?>
