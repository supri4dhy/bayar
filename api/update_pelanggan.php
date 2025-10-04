<?php
// File: api/update_pelanggan.php
// Folder: api
// Deskripsi: API untuk memperbarui data pelanggan.
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
    $original_no_hp = trim($_POST['original_no_hp'] ?? '');
    $langganan_sampah = isset($_POST['langganan_sampah']) ? 1 : 0;
    $langganan_ipal = isset($_POST['langganan_ipal']) ? 1 : 0;

    if (empty($nama_pelanggan) || empty($no_hp) || empty($original_no_hp)) {
        throw new Exception("Data tidak lengkap untuk pembaruan.");
    }
    
    // 2. Cek duplikasi Nomor HP jika diubah
    if ($no_hp !== $original_no_hp) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pelanggan WHERE no_hp = ?");
        $stmt->execute([$no_hp]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Nomor HP baru sudah digunakan oleh pelanggan lain.");
        }
    }
    
    // 3. Update data pelanggan
    $stmt = $pdo->prepare("UPDATE pelanggan SET nama_pelanggan = ?, no_hp = ?, alamat = ?, langganan_sampah = ?, langganan_ipal = ? WHERE no_hp = ?");
    $stmt->execute([$nama_pelanggan, $no_hp, $alamat, $langganan_sampah, $langganan_ipal, $original_no_hp]);
    
    // Catatan: Logika tambahan bisa ditambahkan di sini jika perubahan status langganan
    // harus segera mempengaruhi tagihan bulan ini. Untuk saat ini, diasumsikan
    // perubahan berlaku untuk pembuatan tagihan bulan berikutnya.

    $response['status'] = 'success';
    $response['message'] = 'Data pelanggan berhasil diperbarui.';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>
