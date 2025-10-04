<?php
// File: api/update_settings.php
// Folder: api
// Deskripsi: API untuk memperbarui pengaturan tarif.
// Waktu: Sabtu, 4 Oktober 2025 - 11:33

header('Content-Type: application/json');
$db_file = __DIR__ . '/iuran.db';
$response = ['status' => 'error', 'message' => 'Input tidak valid.'];

try {
    if (!isset($_POST['tarif_sampah']) || !isset($_POST['tarif_ipal'])) {
        throw new Exception("Tarif sampah dan IPAL harus diisi.");
    }

    $tarif_sampah = $_POST['tarif_sampah'];
    $tarif_ipal = $_POST['tarif_ipal'];

    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    // Gunakan REPLACE INTO untuk update atau insert jika belum ada
    $stmt = $pdo->prepare("REPLACE INTO pengaturan (kunci, nilai) VALUES (?, ?)");
    $stmt->execute(['tarif_sampah', $tarif_sampah]);
    $stmt->execute(['tarif_ipal', $tarif_ipal]);

    $pdo->commit();

    $response['status'] = 'success';
    $response['message'] = 'Pengaturan berhasil disimpan.';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
