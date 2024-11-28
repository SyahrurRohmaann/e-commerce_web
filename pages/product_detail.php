<?php
require 'db.php';
include '../templates/header.php';

// Validasi parameter id_produk
$id_produk = isset($_GET['id_produk']) ? intval($_GET['id_produk']) : null;
if (!$id_produk) {
    echo "ID Produk tidak ditemukan.";
    exit();
}

// Query untuk mendapatkan data produk berdasarkan nama
$sql = "SELECT p.*, k.nama_kategori, a.nama_admin
        FROM Produk p
        JOIN Kategori k ON p.id_kategori = k.id_kategori
        JOIN Admin a ON p.id_admin = a.id_admin
        WHERE p.nama = (SELECT nama FROM Produk WHERE id_produk = ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_produk]);
$produkList = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$produkList) {
    echo "Produk tidak ditemukan.";
    exit();
}

// Ambil produk pertama sebagai produk utama
$produk = $produkList[0];

// Dekode gambar JSON dan hilangkan duplikat
$gambarArray = [];
foreach ($produkList as $prod) {
    $gambarProduk = json_decode($prod['gambar'], true);
    if (is_array($gambarProduk)) {
        $gambarArray = array_merge($gambarArray, $gambarProduk);
    } elseif (!empty($prod['gambar'])) {
        $gambarArray[] = $prod['gambar'];
    }
}
$gambarArray = array_unique($gambarArray);

// Kelompokkan ukuran dan stok produk
$ukuranStok = [];
foreach ($produkList as $prod) {
    $ukuranStok[$prod['ukuran']] = $prod['stock'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { margin: 20px; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .carousel { width: 400px; margin-right: 20px; }
        .carousel-item img { width: 100%; height: auto; }
        .product-details { flex-grow: 1; }
        .btn-buy, .btn-cart { color: white; padding: 10px 20px; border: none; margin-top: 10px; }
        .btn-buy { background-color: #007bff; }
        .btn-cart { background-color: #28a745; }
        .btn-buy:hover, .btn-cart:hover { opacity: 0.8; }
        #cartCount { font-weight: bold; color: white; }
    </style>
</head>
<body>
    <div class="main-content">
        <h1><?= htmlspecialchars($produk['nama']) ?></h1>
        <div class="d-flex">
            <!-- Carousel Gambar -->
            <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php foreach ($gambarArray as $index => $gambar): ?>
                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner">
                    <?php foreach ($gambarArray as $index => $gambar): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="../uploads/<?= htmlspecialchars($gambar) ?>" alt="Gambar Produk" class="d-block w-100">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </button>
            </div>

            <!-- Detail Produk -->
            <div class="product-details">
                <p><strong>Harga:</strong> Rp <?= number_format($produk['harga']) ?></p>
                <p><strong>Stok:</strong> <span id="stok"><?= reset($ukuranStok) ?></span></p>
                <p><strong>Kategori:</strong> <?= htmlspecialchars($produk['nama_kategori']) ?></p>
                <p><strong>Ukuran:</strong>
                    <select id="ukuranSelect" class="form-select" onchange="updateStock()">
                        <?php foreach ($ukuranStok as $ukuran => $stok): ?>
                            <option value="<?= htmlspecialchars($ukuran) ?>"><?= htmlspecialchars($ukuran) ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p><strong>Keterangan:</strong> <?= htmlspecialchars($produk['keterangan']) ?></p>
                <button  onclick="addToCart()">Tambah ke Keranjang</button>
                <div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ukuranStok = <?= json_encode($ukuranStok); ?>;

        // Update stok berdasarkan ukuran
        function updateStock() {
            const selectedUkuran = document.getElementById('ukuranSelect').value;
            document.getElementById('stok').innerText = ukuranStok[selectedUkuran] || 0;
        }

        // Tambah ke Keranjang
        function addToCart() {
            const ukuran = document.getElementById('ukuranSelect').value;
            const stok = ukuranStok[ukuran] || 0;

            if (stok <= 0) {
                alert('Ukuran ini tidak tersedia!');
                return;
            }

            fetch('../php/cart-api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add_to_cart',
                    product_id: <?= $id_produk ?>,
                    size: ukuran,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Berhasil ditambahkan ke keranjang!');
                    updateCartCount();
                } else {
                    alert('Gagal menambahkan ke keranjang.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan, coba lagi.');
            });
        }

        // Perbarui jumlah barang di keranjang
        function updateCartCount() {
            fetch('../php/cart-api.php?action=get_cart_count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cartCount').innerText = data.count;
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Sinkronisasi awal
        updateCartCount();
        updateStock();
    </script>
    <?php include '../templates/footer.php'; ?>
</body>
</html>
