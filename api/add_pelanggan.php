<?php
// File: api/add_pelanggan.php
// Folder: api
// Deskripsi: API untuk menambahkan pelanggan baru dan membuat tagihan bulan ini.
// Waktu: Sabtu, 4 Oktober 2025 - 11:21

header('Content-Type: application/json');
$db_file = __DIR__ . '/iuran.db';
$response = ['status' => 'error', 'message' => 'Terjadi kesalahan.'];

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Ambil dan validasi data
    $nama_pelanggan = trim($_POST['nama_pelanggan'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $langganan_sampah = isset($_POST['langganan_sampah']) ? 1 : 0;
    $langganan_ipal = isset($_POST['langganan_ipal']) ? 1 : 0;

    if (empty($nama_pelanggan) || empty($no_hp)) {
        throw new Exception("Nama dan Nomor HP tidak boleh kosong.");
    }
    
    // 2. Cek duplikasi Nomor HP
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pelanggan WHERE no_hp = ?");
    $stmt->execute([$no_hp]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Nomor HP sudah terdaftar.");
    }

    $pdo->beginTransaction();

    // 3. Masukkan ke tabel pelanggan
    $stmt = $pdo->prepare("INSERT INTO pelanggan (nama_pelanggan, no_hp, alamat, langganan_sampah, langganan_ipal) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nama_pelanggan, $no_hp, $alamat, $langganan_sampah, $langganan_ipal]);
    $id_pelanggan = $pdo->lastInsertId();

    // 4. Buat tagihan untuk bulan saat ini
    $bulan = (int)date('m');
    $tahun = (int)date('Y');
    
    // Cek apakah tagihan untuk periode ini sudah ada
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tagihan WHERE id_pelanggan = ? AND bulan = ? AND tahun = ?");
    $stmt_check->execute([$id_pelanggan, $bulan, $tahun]);
    
    if ($stmt_check->fetchColumn() == 0) {
        $status_sampah = $langganan_sampah ? 'Belum Lunas' : 'Tidak Langganan';
        $status_ipal = $langganan_ipal ? 'Belum Lunas' : 'Tidak Langganan';

        $stmt = $pdo->prepare("INSERT INTO tagihan (id_pelanggan, bulan, tahun, status_sampah, status_ipal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_pelanggan, $bulan, $tahun, $status_sampah, $status_ipal]);
    }

    $pdo->commit();

    $response['status'] = 'success';
    $response['message'] = 'Pelanggan berhasil ditambahkan.';

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>
