<?php
// File: api/delete_pelanggan.php
// Folder: api
// Deskripsi: API untuk menghapus data pelanggan dan semua tagihan terkait.
// Waktu: Sabtu, 4 Oktober 2025 - 10:35

header('Content-Type: application/json');
$db_file = __DIR__ . '/iuran.db';
$response = [];

try {
    if (empty($_POST['no_hp'])) {
        throw new Exception("Nomor HP diperlukan untuk menghapus data.");
    }
    
    $no_hp = $_POST['no_hp'];

    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    // 1. Dapatkan ID pelanggan dari nomor HP
    $stmt = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE no_hp = ?");
    $stmt->execute([$no_hp]);
    $id_pelanggan = $stmt->fetchColumn();

    if (!$id_pelanggan) {
        throw new Exception("Pelanggan tidak ditemukan.");
    }

    // 2. Hapus semua tagihan terkait
    $stmt = $pdo->prepare("DELETE FROM tagihan WHERE id_pelanggan = ?");
    $stmt->execute([$id_pelanggan]);
    
    // 3. Hapus pelanggan itu sendiri
    $stmt = $pdo->prepare("DELETE FROM pelanggan WHERE id_pelanggan = ?");
    $stmt->execute([$id_pelanggan]);
    
    $pdo->commit();
    
    $response['status'] = 'success';
    $response['message'] = 'Pelanggan berhasil dihapus.';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
