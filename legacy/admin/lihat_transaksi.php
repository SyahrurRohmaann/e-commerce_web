<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require 'db.php';

// Fungsi Pencarian dan Pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "
    SELECT t.id_transaksi, t.tanggal, t.total, 
           p.nama AS nama_produk, p.ukuran, p.id_kategori, dt.jumlah, dt.harga, dt.gambar, dt.status_verifikasi,
           k.nama_kategori, a.nama_user
    FROM transaksi t
    JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    JOIN Produk p ON dt.id_produk = p.id_produk
    JOIN Kategori k ON p.id_kategori = k.id_kategori
    JOIN pengguna a ON t.id_user = a.id_user
    WHERE p.nama LIKE :search
    ORDER BY t.id_transaksi DESC
    LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
$stmt->execute();
$transaksiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_sql = "
    SELECT COUNT(*)
    FROM transaksi t
    JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    JOIN Produk p ON dt.id_produk = p.id_produk
    WHERE p.nama LIKE :search";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$count_stmt->execute();
$total_transaksi = $count_stmt->fetchColumn();
$total_pages = ceil($total_transaksi / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lihat Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 210px;
            margin-top: 30px;
            padding: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .btn-status {
            width: 150px;
        }
        .large-image-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .large-image-container img {
            width: 400px;
            height: auto;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 30px;
            color: white;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content container mt-5">
        <h2>Daftar Transaksi</h2>
        <h4>Admin: <?php echo htmlspecialchars($_SESSION['nama_admin']); ?></h4>

        <!-- Form Pencarian -->
        <form method="GET" action="lihat_transaksi.php" class="mb-3">
            <div class="input-group mb-3">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari Transaksi...">
                <button type="submit" class="btn btn-success">Cari</button>
            </div>
        </form>

        <table class="table table-striped table-hover table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID Transaksi</th>
                    <th>Tanggal</th>
                    <th>Nama Produk</th>
                    <th>Ukuran</th>
                    <th>Kategori</th>
                    <th>Jumlah</th>
                    <th>Harga</th>
                    <th>Total</th>
                    <th>Pembeli</th>
                    <th>Gambar</th>
                    <th>Aksi</th>
                    <th>Export</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transaksiList as $trans): ?>
                <tr>
                    <td><?php echo $trans['id_transaksi']; ?></td>
                    <td><?php echo $trans['tanggal']; ?></td>
                    <td><?php echo htmlspecialchars($trans['nama_produk']); ?></td>
                    <td><?php echo htmlspecialchars($trans['ukuran']); ?></td>
                    <td><?php echo htmlspecialchars($trans['nama_kategori']); ?></td>
                    <td><?php echo $trans['jumlah']; ?></td>
                    <td><?php echo number_format($trans['harga'], 0); ?></td>
                    <td><?php echo number_format($trans['total'], 0); ?></td>
                    <td><?php echo htmlspecialchars($trans['nama_user']); ?></td>
                    <td>
                        <?php if (!empty($trans['gambar'])): ?>
                            <img src="../bukti/<?php echo htmlspecialchars($trans['gambar']); ?>" alt="Bukti" width="100" onclick="showLargeImage('../bukti/<?php echo htmlspecialchars($trans['gambar']); ?>')">
                        <?php else: ?>
                            
                        <?php endif; ?>
                    </td>
                    <td>

    <button 
        class="btn btn-status <?= $trans['status_verifikasi'] == 1 ? 'btn-success' : 'btn-danger'; ?>" 
        onclick="toggleStatus(<?php echo $trans['id_transaksi']; ?>, this)">
        <?= $trans['status_verifikasi'] == 1 ? 'Terverifikasi' : 'Belum Terverifikasi'; ?>
    </button>
</td>
<td>
<a href="export_transaksi_pdf.php?id_transaksi=<?php echo $trans['id_transaksi']; ?>" 
       class="btn btn-primary btn-sm" target="_blank">
       Download PDF
    </a>
    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>


        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="lihat_transaksi.php?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <!-- Container untuk Gambar Besar -->
    <div id="largeImageContainer" class="large-image-container">
        <span class="close-btn" onclick="closeLargeImage()">×</span>
        <img id="largeImage" src="" alt="Gambar Bukti">
    </div>

    <script>
        function showLargeImage(src) {
            var container = document.getElementById('largeImageContainer');
            var image = document.getElementById('largeImage');
            container.style.display = 'flex'; // Menampilkan container gambar besar
            image.src = src; // Mengatur gambar besar
        }

        function closeLargeImage() {
            var container = document.getElementById('largeImageContainer');
            container.style.display = 'none'; // Menyembunyikan container gambar besar
        }

        function toggleStatus(idTransaksi, button) {
    // Menampilkan konfirmasi kepada pengguna
    const confirmAction = confirm("Yakin ingin diverifikasi?");
    
    // Jika pengguna memilih "OK", lanjutkan dengan perubahan status
    if (confirmAction) {
        // Mengirimkan permintaan ke server untuk memperbarui status
        fetch(`ubah_status.php?id_transaksi=${idTransaksi}`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Jika berhasil, perbarui tombol
                if (data.status_verifikasi == 1) {
                    button.textContent = 'Terverifikasi';
                    button.className = 'btn btn-status btn-success';
                    button.disabled = true;  // Menonaktifkan tombol setelah verifikasi
                } else {
                    button.textContent = 'Belum Terverifikasi';
                    button.className = 'btn btn-status btn-danger';
                }
            } else {
                // Jika gagal, tampilkan pesan error
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan saat mengubah status.');
        });
    } else {
        // Jika pengguna memilih "Batal", tidak melakukan apa-apa
        return;
    }
}


    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
