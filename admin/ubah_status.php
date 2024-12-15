<?php
require 'db.php';

if (isset($_GET['id_transaksi'])) {
    $id_transaksi = $_GET['id_transaksi'];

    // Mendapatkan status saat ini dari detail_transaksi
    $stmt = $pdo->prepare("SELECT status_verifikasi FROM detail_transaksi WHERE id_transaksi = ?");
    $stmt->execute([$id_transaksi]);
    $current_status = $stmt->fetchColumn();

    // Cek apakah status sudah terverifikasi (status_verifikasi = 1)
    if ($current_status == 1) {
        // Jika sudah terverifikasi, tidak ubah status, kembalikan response error
        echo json_encode(['success' => false, 'message' => 'Status sudah terverifikasi, tidak dapat diubah.']);
        exit;
    }

    // Jika belum terverifikasi, balikkan status
    $new_status = $current_status == 0 ? 1 : 0;

    // Memperbarui status_verifikasi
    $stmt = $pdo->prepare("UPDATE detail_transaksi SET status_verifikasi = ? WHERE id_transaksi = ?");
    if ($stmt->execute([$new_status, $id_transaksi])) {
        echo json_encode(['success' => true, 'status_verifikasi' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status.']);
    }
}
?>
