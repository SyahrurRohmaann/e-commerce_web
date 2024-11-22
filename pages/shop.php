<?php
$pageTitle = 'Shop';
include '../templates/header.php';
?>   
    <div class="product-grid" id="productContainer"></div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        loadCategories();
        loadProducts();
    });

    function loadCategories() {
        fetch("../php/product.php?action=get_categories")
            .then(response => response.json())
            .then(result => {
                if(result.success) {
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

    function loadProducts(categoryId = "", sortBy = "") {
        let url = "../php/product.php?action=get_products";
        if (categoryId) url += `&category=${categoryId}`;
        if (sortBy) url += `&sort=${sortBy}`;

        fetch(url)
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    const container = document.getElementById("productContainer");
                    container.innerHTML = result.data.map(product => `
                        <div class="product-card">
                            <img src="../uploads/${product.gambar}" 
                                alt="${product.nama}" 
                                class="product-image"
                                onerror="this.src='../assets/image/product-1.jpg'">
                            <div class="product-details">
                                <h3 class="product-name">${product.nama}</h3>
                                <div class="product-price">Rp ${formatPrice(product.harga)}</div>
                                <div class="product-size">Size: ${product.ukuran}</div>
                                <div class="product-stock">Stock: ${product.stock}</div>
                                <button onclick="addToCart(${product.id_produk})" 
                                        class="add-to-cart-btn ${product.stock < 1 ? 'out-of-stock' : ''}"
                                        ${product.stock < 1 ? 'disabled' : ''}>
                                    ${product.stock < 1 ? "Out of Stock" : "Add to Cart"}
                                </button>
                            </div>
                        </div>
                    `).join("");
                } else {
                    showNotification(result.error || 'Error loading products');
                }
            })
            .catch(error => console.error("Error:", error));
    }

    function filterProducts() {
        const categoryId = document.getElementById("categoryFilter").value;
        const sortBy = document.getElementById("priceSort").value;
        loadProducts(categoryId, sortBy);
    }
    function updateCartCount() {
            const cartCount = document.getElementById("cartCount");
            if (!cartCount) return;
            
            // Tambahkan pengecekan sessionStorage
            const userData = sessionStorage.getItem('user');
            
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

    function formatPrice(price) {
        return new Intl.NumberFormat("id-ID").format(price);
    }

    function addToCart(productId) {
        if (!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?> || !sessionStorage.getItem('user')) {
            showNotification("Silakan login terlebih dahulu");
            return;
        }

        fetch("../php/product.php", {
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
          // .catch((error) => {
          //   console.error("Error:", error);
          //   showNotification("Error adding product to cart");
          // });
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