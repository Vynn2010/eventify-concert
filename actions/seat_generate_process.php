<?php
require_once __DIR__ . "/cek_koneksi.php";
include __DIR__ . "/cek_login_admin.php"; // pastikan hanya admin yang bisa akses
 
$halaman_form = "../pages/admin/seat_generate.php";
 
function kembali(string $url, array $params): void {
    header("Location: " . $url . "?" . http_build_query($params));
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $halaman_form");
    exit;
}
 
// ---------- AMBIL & VALIDASI INPUT ----------
$concert_id  = (int)($_POST['concert_id'] ?? 0);
$category_id = (int)($_POST['category_id'] ?? 0);
$harga       = (int)($_POST['harga'] ?? 0);
$jumlah      = (int)($_POST['jumlah'] ?? 0);
$input_baris = strtoupper(trim($_POST['baris'] ?? ''));
 
if ($concert_id <= 0 || $category_id <= 0 || $harga < 0 || $jumlah < 1 || $jumlah > 100) {
    kembali($halaman_form, ['status' => 'error', 'msg' => 'Data form tidak valid.']);
}
 
// ---------- PARSE BARIS: "A-F" atau "A,B,C" ----------
$baris = [];
if (preg_match('/^([A-Z])\s*-\s*([A-Z])$/', $input_baris, $m)) {
    $baris = range($m[1], $m[2]);
} else {
    foreach (explode(',', $input_baris) as $b) {
        $b = trim($b);
        if ($b === '') continue;
        if (!preg_match('/^[A-Z]{1,2}$/', $b)) {
            kembali($halaman_form, ['status' => 'error', 'msg' => "Baris '$b' tidak valid."]);
        }
        $baris[] = $b;
    }
}
$baris = array_values(array_unique($baris));
 
if (empty($baris) || count($baris) * $jumlah > 1000) {
    kembali($halaman_form, ['status' => 'error', 'msg' => 'Baris kosong atau total kursi lebih dari 1000.']);
}
 
// ---------- INSERT DALAM SATU TRANSAKSI ----------
$sql  = "INSERT INTO seats (concert_id, seat_number, category_id, price, seat_status)
         VALUES (?, ?, ?, ?, 'Available')";
$stmt = mysqli_prepare($conn, $sql);
$total = 0;
 
mysqli_begin_transaction($conn);
try {
    foreach ($baris as $b) {
        for ($i = 1; $i <= $jumlah; $i++) {
            $seat_number = $b . $i;
            mysqli_stmt_bind_param($stmt, "isii", $concert_id, $seat_number, $category_id, $harga);
            mysqli_stmt_execute($stmt);
            $total++;
        }
    }
    mysqli_commit($conn);
    kembali($halaman_form, ['status' => 'ok', 'total' => $total]);
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    // Kode 1062 = duplikat (kena unique key concert_id + seat_number)
    $msg = ($e->getCode() === 1062)
        ? 'Sebagian kursi sudah ada untuk konser ini. Tidak ada yang disimpan.'
        : 'Gagal menyimpan kursi: ' . $e->getMessage();
    kembali($halaman_form, ['status' => 'error', 'msg' => $msg]);
}