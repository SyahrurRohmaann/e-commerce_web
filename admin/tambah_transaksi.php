<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require 'db.php';

// Mengambil data produk dari database
$produkList = $pdo->query("SELECT * FROM Produk")->fetchAll(PDO::FETCH_ASSOC);

// Mengambil data pengguna dari database
$penggunaList = $pdo->query("SELECT * FROM pengguna")->fetchAll(PDO::FETCH_ASSOC);

// Mengelompokkan produk berdasarkan nama
$produkGroupedByName = [];
foreach ($produkList as $produk) {
    $namaProduk = $produk['nama'];
    if (!isset($produkGroupedByName[$namaProduk])) {
        $produkGroupedByName[$namaProduk] = [];
    }
    $produkGroupedByName[$namaProduk][] = $produk;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 210px;
            padding: 20px;
        }
        .form-control, .form-select {
            max-width: 300px;
        }
        .form-control[readonly] {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content container mt-5">
        <h2>Tambah Transaksi</h2>
        <form id="transaksiForm" action="proses_tambah_transaksi.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nama_user" class="form-label">Pilih Pengguna</label>
                <select class="form-select" id="nama_user" name="nama_user" required>
                    <option value="">Pilih Pengguna</option>
                    <?php foreach ($penggunaList as $pengguna): ?>
                        <option value="<?= htmlspecialchars($pengguna['id_user']); ?>">
                            <?= htmlspecialchars($pengguna['nama_user']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="nama_produk" class="form-label">Pilih Produk</label>
                <select class="form-select" id="nama_produk" name="nama_produk" required>
                    <option value="">Pilih Produk</option>
                    <?php foreach ($produkGroupedByName as $nama => $produkGroup): ?>
                        <option value="<?= htmlspecialchars($nama); ?>"><?= htmlspecialchars($nama); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="harga" class="form-label">Harga</label>
                <input type="text" class="form-control" id="harga" name="harga" readonly>
            </div>
            <div class="mb-3">
                <label for="stok" class="form-label">Stok</label>
                <input type="text" class="form-control" id="stok" name="stok" readonly>
            </div>
            <div class="mb-3">
                <label for="ukuran" class="form-label">Ukuran</label>
                <select class="form-select" id="ukuran" name="ukuran" required>
                    <option value="">Pilih Ukuran</option>
                    <!-- Ukuran akan diisi dengan JavaScript -->
                </select>
            </div>
            <div class="mb-3">
                <label for="jumlah" class="form-label">Jumlah</label>
                <input type="number" class="form-control" id="jumlah" name="jumlah" required>
            </div>
            <div class="mb-3">
                <label for="total_harga" class="form-label">Total Harga</label>
                <input type="text" class="form-control" id="total_harga" name="total_harga" readonly>
            </div>
            <div class="mb-3">
                <label for="tanggal" class="form-label">Tanggal</label>
                <input type="date" class="form-control" id="tanggal" name="tanggal" required>
            </div>
            <div class="mb-3">
                <label for="gambar" class="form-label">Upload Bukti Transaksi</label>
                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*" required>
            </div>
            <input type="hidden" name="id_admin" value="<?= htmlspecialchars($_SESSION['id_admin']); ?>">
            <button type="submit" class="btn btn-primary">Tambah Transaksi</button>
        </form>
    </div>

    <script>
        const produkGroupedByName = <?= json_encode($produkGroupedByName); ?>;
        
        document.getElementById('nama_produk').addEventListener('change', function() {
            const selectedNama = this.value;
            const produkGroup = produkGroupedByName[selectedNama] || [];
            
            const harga = produkGroup.length ? produkGroup[0].harga : '';
            document.getElementById('harga').value = harga;

            const ukuranSelect = document.getElementById('ukuran');
            ukuranSelect.innerHTML = '<option value="">Pilih Ukuran</option>'; // Kosongkan isi dropdown ukuran

            produkGroup.forEach(function(produk) {
                const option = document.createElement('option');
                option.value = produk.ukuran;
                option.textContent = produk.ukuran;
                option.setAttribute('data-stok', produk.stock);
                ukuranSelect.appendChild(option);
            });

            document.getElementById('stok').value = '';
            document.getElementById('jumlah').value = '';
            document.getElementById('total_harga').value = '';
        });

        document.getElementById('ukuran').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('stok').value = selectedOption.getAttribute('data-stok');
            document.getElementById('jumlah').value = '';
            document.getElementById('total_harga').value = '';
        });

        document.getElementById('jumlah').addEventListener('input', function() {
            const harga = parseFloat(document.getElementById('harga').value) || 0;
            const jumlah = parseFloat(this.value) || 0;
            const stok = parseFloat(document.getElementById('stok').value) || 0;

            if (jumlah > stok) {
                alert('Jumlah tidak boleh lebih besar dari stok yang tersedia!');
                this.value = '';
                document.getElementById('total_harga').value = '';
            } else {
                const totalHarga = harga * jumlah;
                document.getElementById('total_harga').value = totalHarga.toFixed(0);
            }
        });

        document.getElementById('transaksiForm').addEventListener('submit', function(event) {
            const jumlah = parseFloat(document.getElementById('jumlah').value) || 0;
            const stok = parseFloat(document.getElementById('stok').value) || 0;

            if (jumlah > stok) {
                alert('Jumlah tidak boleh lebih besar dari stok yang tersedia!');
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
