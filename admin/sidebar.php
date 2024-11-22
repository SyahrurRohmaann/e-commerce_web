<!DOCTYPE html>
<html lang="id">
<head>
    <title>Sidebar dengan Ikon</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .sidebar {
            height: 100%;
            width: 200px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #111;
            padding-top: 20px;
        }
        .sidebar a, .dropdown-btn {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: #818181;
            display: flex;
            align-items: center;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            outline: none;
        }
        .sidebar a:hover, .dropdown-btn:hover {
            color: #f1f1f1;
        }
        .dropdown-container {
            display: none;
            background-color: #262626;
            padding-left: 8px;
        }
        .logout {
            position: absolute;
            bottom: 50px;
            width: 100%;
        }
        .sidebar i {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a class="dropdown-btn"><i class="fas fa-box"></i> Produk</a>
        <div class="dropdown-container">
            <a href="tambah_produk.php"><i class="fas fa-plus"></i> Tambah Produk</a>
            <a href="lihat_produk.php"><i class="fas fa-eye"></i> Lihat Produk</a>
        </div>
        <a href="kategori.php"><i class="fas fa-list"></i> Kategori</a>
        <a href="pengguna.php"><i class="fas fa-users"></i> Pengguna</a>
        
        <a class="dropdown-btn2"><i class="fas fa-file-invoice"></i> Transaksi</a>
        <div class="dropdown-container">
            <a href="tambah_transaksi.php"><i class="fas fa-plus"></i> Tambah Transaksi</a>
            <a href="lihat_transaksi.php"><i class="fas fa-eye"></i> Lihat Transaksi</a>
        </div>
        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </div>
    <script>
        document.querySelector('.dropdown-btn').addEventListener('click', function() {
            this.classList.toggle('active');
            var dropdownContent = this.nextElementSibling;
            if (dropdownContent.style.display === "block") {
                dropdownContent.style.display = "none";
            } else {
                dropdownContent.style.display = "block";
            }
        });

        document.querySelector('.dropdown-btn2').addEventListener('click', function() {
            this.classList.toggle('active');
            var dropdownContent = this.nextElementSibling;
            if (dropdownContent.style.display === "block") {
                dropdownContent.style.display = "none";
            } else {
                dropdownContent.style.display = "block";
            }
        });
    </script>
</body>
</html>
