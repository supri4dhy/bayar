<?php
// File: api/update_payment.php
// Folder: api
// Deskripsi: API untuk memproses dan menyimpan pembayaran baru.
// Waktu: Sabtu, 4 Oktober 2025 - 11:33

header('Content-Type: application/json');
$db_file = __DIR__ . '/iuran.db';
$response = ['status' => 'error', 'message' => 'Input tidak lengkap.'];

try {
    if (!isset($_POST['no_hp'], $_POST['bulan'], $_POST['tahun'], $_POST['status_sampah'], $_POST['status_ipal'])) {
        throw new Exception("Semua field pembayaran harus diisi.");
    }

    $no_hp = $_POST['no_hp'];
    $bulan = (int)$_POST['bulan'];
    $tahun = (int)$_POST['tahun'];
    $status_sampah = $_POST['status_sampah'];
    $status_ipal = $_POST['status_ipal'];

    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Dapatkan id_pelanggan dari no_hp
    $stmt = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE no_hp = ?");
    $stmt->execute([$no_hp]);
    $id_pelanggan = $stmt->fetchColumn();

    if (!$id_pelanggan) {
        throw new Exception("Pelanggan tidak ditemukan.");
    }

    // Cek apakah tagihan untuk periode ini sudah ada
    $stmt = $pdo->prepare("SELECT id_tagihan FROM tagihan WHERE id_pelanggan = ? AND bulan = ? AND tahun = ?");
    $stmt->execute([$id_pelanggan, $bulan, $tahun]);
    $id_tagihan = $stmt->fetchColumn();

    if ($id_tagihan) {
        // Jika ada, UPDATE
        $stmt_update = $pdo->prepare("UPDATE tagihan SET status_sampah = ?, status_ipal = ? WHERE id_tagihan = ?");
        $stmt_update->execute([$status_sampah, $status_ipal, $id_tagihan]);
    } else {
        // Jika tidak ada, INSERT
        $stmt_insert = $pdo->prepare("INSERT INTO tagihan (id_pelanggan, bulan, tahun, status_sampah, status_ipal) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->execute([$id_pelanggan, $bulan, $tahun, $status_sampah, $status_ipal]);
    }

    $response['status'] = 'success';
    $response['message'] = 'Pembayaran berhasil disimpan.';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
