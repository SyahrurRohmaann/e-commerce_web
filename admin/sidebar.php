<!DOCTYPE html>
<html>
<head>
    <title>Sidebar</title>
    <style>
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
            display: block;
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
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard.php">Home</a>
        <a class="dropdown-btn">Produk</a>
        <div class="dropdown-container">
            <a href="tambah_produk.php">Tambah Produk</a>
            <a href="lihat_produk.php">Lihat Produk</a>
        </div>
        <a href="kategori.php">Kategori</a>
        <a href="pengguna.php">Pengguna</a>
        <a href="logout.php" class="logout">Log Out</a>
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
    </script>
</body>
</html>
