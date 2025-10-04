<?php
// File: api/get_settings.php
// Folder: api
// Deskripsi: API untuk mengambil pengaturan tarif.
// Waktu: Sabtu, 4 Oktober 2025 - 11:33

header('Content-Type: application/json');
$db_file = __DIR__ . '/iuran.db';
$response = ['status' => 'error', 'message' => 'Gagal memuat pengaturan.'];

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT kunci, nilai FROM pengaturan");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $response['status'] = 'success';
    $response['data'] = $settings;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
