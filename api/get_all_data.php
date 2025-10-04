<?php
// File: api/get_all_data.php
// Folder: api
// Deskripsi: API untuk mengambil semua data pelanggan beserta total tunggakannya.
// Waktu: Sabtu, 4 Oktober 2025 - 11:33

header('Content-Type: application/json');
$db_file = __DIR__ . '/iuran.db';
$response = ['status' => 'error', 'message' => 'Gagal mengambil data.'];

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Ambil tarif dari pengaturan
    $stmt_settings = $pdo->query("SELECT kunci, nilai FROM pengaturan");
    $settings_raw = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
    $tarif_sampah = $settings_raw['tarif_sampah'] ?? 0;
    $tarif_ipal = $settings_raw['tarif_ipal'] ?? 0;

    // 2. Ambil semua pelanggan
    $stmt_pelanggan = $pdo->query("SELECT id_pelanggan, nama_pelanggan, no_hp, langganan_sampah, langganan_ipal FROM pelanggan ORDER BY nama_pelanggan ASC");
    $pelanggan_list = $stmt_pelanggan->fetchAll(PDO::FETCH_ASSOC);
    
    // Inisialisasi data pelanggan dengan tunggakan 0
    $data_pelanggan = [];
    foreach ($pelanggan_list as $p) {
        $data_pelanggan[$p['id_pelanggan']] = [
            'nama_pelanggan' => $p['nama_pelanggan'],
            'no_hp' => $p['no_hp'],
            'total_tunggakan' => 0,
            'langganan_sampah' => $p['langganan_sampah'],
            'langganan_ipal' => $p['langganan_ipal']
        ];
    }

    // 3. Ambil semua tagihan yang belum lunas
    $stmt_tagihan = $pdo->query("SELECT id_pelanggan, status_sampah, status_ipal FROM tagihan");
    $all_tagihan = $stmt_tagihan->fetchAll(PDO::FETCH_ASSOC);

    // 4. Hitung total tunggakan untuk setiap pelanggan
    foreach ($all_tagihan as $tagihan) {
        $id_pelanggan = $tagihan['id_pelanggan'];
        if (isset($data_pelanggan[$id_pelanggan])) {
            if ($tagihan['status_sampah'] === 'Belum Lunas' && $data_pelanggan[$id_pelanggan]['langganan_sampah']) {
                $data_pelanggan[$id_pelanggan]['total_tunggakan'] += $tarif_sampah;
            }
            if ($tagihan['status_ipal'] === 'Belum Lunas' && $data_pelanggan[$id_pelanggan]['langganan_ipal']) {
                $data_pelanggan[$id_pelanggan]['total_tunggakan'] += $tarif_ipal;
            }
        }
    }

    $response['status'] = 'success';
    $response['data'] = array_values($data_pelanggan); // Re-index array

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
