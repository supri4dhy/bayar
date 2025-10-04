<?php
// File: api/api.php
// Folder: api
// Deskripsi: API untuk mengambil detail satu pelanggan, termasuk riwayat tagihan.
// Waktu: Sabtu, 4 Oktober 2025 - 11:33

header('Content-Type: application/json');
$db_file = __DIR__ . '/iuran.db';
$response = ['status' => 'error', 'message' => 'Parameter tidak valid.'];

if (isset($_GET['no_hp'])) {
    try {
        $pdo = new PDO('sqlite:' . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Ambil data pelanggan
        $stmt_pelanggan = $pdo->prepare("SELECT * FROM pelanggan WHERE no_hp = ?");
        $stmt_pelanggan->execute([$_GET['no_hp']]);
        $pelanggan = $stmt_pelanggan->fetch(PDO::FETCH_ASSOC);

        if (!$pelanggan) {
            throw new Exception("Pelanggan tidak ditemukan.");
        }

        // 2. Ambil tarif
        $stmt_settings = $pdo->query("SELECT kunci, nilai FROM pengaturan");
        $settings_raw = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
        $tarif_sampah = $settings_raw['tarif_sampah'] ?? 0;
        $tarif_ipal = $settings_raw['tarif_ipal'] ?? 0;
        
        // 3. Ambil semua tagihan pelanggan
        $stmt_tagihan = $pdo->prepare("SELECT * FROM tagihan WHERE id_pelanggan = ? ORDER BY tahun DESC, bulan DESC");
        $stmt_tagihan->execute([$pelanggan['id_pelanggan']]);
        $tagihan_list = $stmt_tagihan->fetchAll(PDO::FETCH_ASSOC);
        
        $tagihan_detail = [];
        $total_tunggakan = 0;
        
        $nama_bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

        foreach ($tagihan_list as $t) {
            $periode = $nama_bulan[$t['bulan']] . ' ' . $t['tahun'];
            $tagihan_detail[$periode] = [
                'sampah' => $pelanggan['langganan_sampah'] ? $t['status_sampah'] : 'Tidak Langganan',
                'ipal' => $pelanggan['langganan_ipal'] ? $t['status_ipal'] : 'Tidak Langganan'
            ];
            
            if ($pelanggan['langganan_sampah'] && $t['status_sampah'] === 'Belum Lunas') {
                $total_tunggakan += $tarif_sampah;
            }
            if ($pelanggan['langganan_ipal'] && $t['status_ipal'] === 'Belum Lunas') {
                $total_tunggakan += $tarif_ipal;
            }
        }
        
        $response['status'] = 'success';
        $response['data'] = [
            'pelanggan' => $pelanggan,
            'tagihan_detail' => $tagihan_detail,
            'total_tunggakan' => $total_tunggakan
        ];

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        http_response_code(404);
    }
}

echo json_encode($response);
