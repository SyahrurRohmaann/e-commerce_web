<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require 'db.php';

$totalProduk = $pdo->query("SELECT COUNT(*) FROM Produk")->fetchColumn();
$totalKategori = $pdo->query("SELECT COUNT(*) FROM Kategori")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .main-content {
            margin-left: 190px;
            padding: 20px;
            flex-grow: 1;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
        }
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            flex: 1;
            text-align: center;
        }
        .stat-box h2 {
            margin: 0;
            font-size: 2rem;
        }
        .stat-box p {
            margin: 0;
            font-size: 1.2rem;
            color: #777;
        }
        .chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="stats-container">
            <div class="stat-box">
                <h2><?php echo $totalProduk; ?></h2>
                <p>Total Produk</p>
            </div>
            <div class="stat-box">
                <h2><?php echo $totalKategori; ?></h2>
                <p>Total Kategori</p>
            </div>
        </div>
        <h2>Halo, <?php echo htmlspecialchars($_SESSION['nama_admin']); ?></h2>
        
        <div class="chart-container">
            <canvas id="myChart" width="400" height="200"></canvas>
        </div>
    </div>

    <script>
        fetch('get_data.php')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data) && data.length > 0) {
                    // Warna untuk setiap kategori
                    const colors = [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)'
                    ];

                    const borderColors = [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ];

                    const groupedData = data.reduce((acc, item) => {
                        const date = item.tanggal;
                        if (!acc[date]) {
                            acc[date] = {};
                        }
                        acc[date][item.nama_kategori] = item.terjual;
                        return acc;
                    }, {});

                    const labels = Object.keys(groupedData);
                    const categories = Array.from(new Set(data.map(item => item.nama_kategori)));

                    const datasets = categories.map((category, index) => ({
                        label: category,
                        data: labels.map(date => groupedData[date][category] || 0),
                        backgroundColor: colors[index % colors.length],
                        borderColor: borderColors[index % borderColors.length],
                        borderWidth: 1
                    }));

                    const ctx = document.getElementById('myChart').getContext('2d');
                    const myChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                legend: {
                                    labels: {
                                        color: 'rgb(0, 0, 0)',
                                        font: {
                                            size: 14
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    console.error('Data format is incorrect or empty:', data);
                }
            })
            .catch(error => console.error('Error fetching data:', error));
    </script>
</body>
</html>
