<?php
// File: api/init_db.php
// Folder: api
// Deskripsi: Skrip inisialisasi database yang lengkap dengan data dummy dan struktur yang benar.
// Waktu: Sabtu, 4 Oktober 2025 - 10:58

header('Content-Type: application/json');
$db_file = __DIR__ . '/iuran.db';
$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

try {
    // Hapus database lama jika ada untuk memastikan mulai dari awal
    if (file_exists($db_file)) {
        unlink($db_file);
    }

    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Buat tabel pelanggan dengan kolom alamat
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pelanggan (
            id_pelanggan INTEGER PRIMARY KEY AUTOINCREMENT,
            nama_pelanggan TEXT NOT NULL,
            no_hp TEXT NOT NULL UNIQUE,
            alamat TEXT,
            langganan_sampah INTEGER DEFAULT 0,
            langganan_ipal INTEGER DEFAULT 0
        );
    ");

    // 2. Buat tabel tagihan
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tagihan (
            id_tagihan INTEGER PRIMARY KEY AUTOINCREMENT,
            id_pelanggan INTEGER NOT NULL,
            bulan INTEGER NOT NULL,
            tahun INTEGER NOT NULL,
            status_sampah TEXT NOT NULL,
            status_ipal TEXT NOT NULL,
            FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan) ON DELETE CASCADE
        );
    ");

    // 3. Buat tabel pengaturan
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pengaturan (
            kunci TEXT PRIMARY KEY,
            nilai TEXT NOT NULL
        );
    ");
    
    // 4. Isi pengaturan default
    $pdo->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('tarif_sampah', '30000'), ('tarif_ipal', '10000');");

    // 5. Siapkan dan isi data dummy
    $dummy_pelanggan = [
        ['Ahmad Dahlan', '081211112222', 'Jl. Merdeka 1, Jakarta', 1, 1],
        ['Budi Santoso', '081322223333', 'Jl. Kemerdekaan 2, Bandung', 1, 0],
        ['Candra Kirana', '081433334444', 'Jl. Pahlawan 3, Surabaya', 0, 1],
        ['Dewi Lestari', '081544445555', 'Jl. Nusantara 4, Medan', 1, 1],
        ['Eka Kurniawan', '081655556666', 'Jl. Garuda 5, Yogyakarta', 1, 1],
        ['Fajar Nugroho', '081766667777', 'Jl. Elang 6, Semarang', 0, 0],
        ['Gita Gutawa', '081877778888', 'Jl. Merpati 7, Makassar', 1, 1],
        ['Hasanuddin', '081988889999', 'Jl. Rajawali 8, Palembang', 1, 0],
        ['Indah Permata', '082199990000', 'Jl. Cendrawasih 9, Bali', 1, 1],
        ['Joko Widodo', '082200001111', 'Jl. Bhinneka 10, Solo', 1, 1],
        ['Kartini', '082311112222', 'Jl. Emansipasi 11, Jepara', 0, 1],
        ['Lukman Sardi', '082422223333', 'Jl. Aktor 12, Jakarta', 1, 1]
    ];
    
    $stmt_pelanggan = $pdo->prepare("INSERT INTO pelanggan (nama_pelanggan, no_hp, alamat, langganan_sampah, langganan_ipal) VALUES (?, ?, ?, ?, ?)");
    $stmt_tagihan = $pdo->prepare("INSERT INTO tagihan (id_pelanggan, bulan, tahun, status_sampah, status_ipal) VALUES (?, ?, ?, ?, ?)");

    $pdo->beginTransaction();

    foreach ($dummy_pelanggan as $data) {
        $stmt_pelanggan->execute($data);
        $id_pelanggan = $pdo->lastInsertId();
        $langganan_sampah = $data[3];
        $langganan_ipal = $data[4];

        // Buat tagihan untuk 6 bulan terakhir
        for ($i = 5; $i >= 0; $i--) {
            $date = new DateTime("first day of -$i month");
            $bulan = (int)$date->format('m');
            $tahun = (int)$date->format('Y');

            // Status acak untuk data dummy
            $status_sampah = 'Belum Lunas';
            if ($langganan_sampah && rand(0, 5) > $i) { // Makin ke belakang, makin besar kemungkinan lunas
                $status_sampah = 'Lunas';
            }

            $status_ipal = 'Belum Lunas';
            if ($langganan_ipal && rand(0, 5) > $i) {
                $status_ipal = 'Lunas';
            }

            $stmt_tagihan->execute([
                $id_pelanggan,
                $bulan,
                $tahun,
                $status_sampah,
                $status_ipal
            ]);
        }
    }
    
    $pdo->commit();

    $response['status'] = 'success';
    $response['message'] = 'Database dan 12 data dummy pelanggan berhasil dibuat ulang.';

} catch (Exception $e) {
    $response['message'] = 'Gagal membuat database: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

