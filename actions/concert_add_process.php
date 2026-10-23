<?php
require_once __DIR__ . "/cek_koneksi.php";
include __DIR__ . "/cek_login_admin.php"; // hanya admin yang boleh menambah konser
 
$form_page = "../pages/admin/concerts.php";
 
function redirect_back(string $url, array $params): void {
    header("Location: " . $url . "?" . http_build_query($params));
    exit;
}
 
function fail(string $url, string $message): void {
    redirect_back($url, ['status' => 'error', 'msg' => $message]);
}
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $form_page");
    exit;
}
 
// ---------- AMBIL INPUT ----------
$title        = trim($_POST['title'] ?? '');
$artist       = trim($_POST['artist'] ?? '');
$venue        = trim($_POST['venue'] ?? '');
$date_input   = trim($_POST['concert_date'] ?? '');
$price_input  = trim($_POST['price'] ?? '');
$description  = trim($_POST['concert_description'] ?? '');
$status_label = trim($_POST['concert_status'] ?? '');
$genre_id     = (int)($_POST['genre_id'] ?? 0);
 
if ($status_label === '') {
    $status_label = 'on going';
}
 
// ---------- VALIDASI ----------
if ($title === '' || $artist === '' || $venue === '' || $date_input === '') {
    fail($form_page, 'Judul, artis, venue, dan tanggal wajib diisi.');
}
if (mb_strlen($title) > 255 || mb_strlen($artist) > 255 || mb_strlen($venue) > 255 || mb_strlen($status_label) > 50) {
    fail($form_page, 'Teks terlalu panjang. Judul, artis, dan venue maksimal 255 karakter, status maksimal 50.');
}
if (mb_strlen($description) > 5000) {
    fail($form_page, 'Deskripsi maksimal 5000 karakter.');
}
if ($price_input === '' || !ctype_digit($price_input)) {
    fail($form_page, 'Harga harus berupa angka bulat dan tidak negatif.');
}
$price = (int)$price_input;
 
// Tanggal dari input datetime-local (format 2026-10-24T19:00)
$date = DateTime::createFromFormat('Y-m-d\TH:i', $date_input)
     ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $date_input);
if (!$date) {
    fail($form_page, 'Format tanggal tidak valid.');
}
$concert_date = $date->format('Y-m-d H:i:s');
 
// Genre: wajib diisi kalau tabel concerts punya kolom genre_id
$has_genre_column = false;
$res = mysqli_query($conn, "SHOW COLUMNS FROM concerts LIKE 'genre_id'");
if ($res && mysqli_num_rows($res) > 0) {
    $has_genre_column = true;
}
if ($has_genre_column) {
    if ($genre_id <= 0) {
        fail($form_page, 'Pilih genre konser.');
    }
    $stmt = mysqli_prepare($conn, "SELECT id FROM genres WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $genre_id);
    mysqli_stmt_execute($stmt);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        fail($form_page, 'Genre yang dipilih tidak ditemukan.');
    }
}
 
// Cegah konser ganda (judul dan waktu yang sama)
$stmt = mysqli_prepare($conn, "SELECT id FROM concerts WHERE title = ? AND concert_date = ?");
mysqli_stmt_bind_param($stmt, "ss", $title, $concert_date);
mysqli_stmt_execute($stmt);
if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    fail($form_page, 'Konser dengan judul dan waktu yang sama sudah ada.');
}
 
// ---------- UPLOAD POSTER (opsional) ----------
$img_path   = '';
$saved_file = null;
 
if (!empty($_FILES['img']['name'])) {
    $file = $_FILES['img'];
 
    if ($file['error'] !== UPLOAD_ERR_OK) {
        fail($form_page, 'Upload gambar gagal. Coba file lain.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        fail($form_page, 'Ukuran gambar maksimal 2 MB.');
    }
 
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        fail($form_page, 'Format gambar harus JPG, PNG, atau WEBP.');
    }
 
    // Folder tujuan: <root proyek>/assets/uploads/concerts/  (ubah di sini jika gambarmu disimpan di tempat lain)
    $upload_dir = __DIR__ . "/../assets/uploads/concerts/";
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        fail($form_page, 'Folder upload tidak bisa dibuat.');
    }
 
    $filename = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        fail($form_page, 'Gambar tidak bisa disimpan.');
    }
    $saved_file = $upload_dir . $filename;
 
    // Path relatif yang sama-sama berlaku dari pages/user/ dan pages/admin/
    $img_path = "../../assets/uploads/concerts/" . $filename;
}
 
// ---------- SIMPAN ----------
$columns = ['title', 'artist', 'venue', 'concert_date', 'price', 'concert_description', 'concert_status', 'img'];
$values  = [$title, $artist, $venue, $concert_date, $price, $description, $status_label, $img_path];
$types   = 'ssssisss';
 
if ($has_genre_column) {
    $columns[] = 'genre_id';
    $values[]  = $genre_id;
    $types    .= 'i';
}
 
$placeholders = implode(', ', array_fill(0, count($columns), '?'));
$sql = "INSERT INTO concerts (" . implode(', ', $columns) . ") VALUES ($placeholders)";
 
try {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    mysqli_stmt_execute($stmt);
} catch (mysqli_sql_exception $e) {
    if ($saved_file && is_file($saved_file)) {
        unlink($saved_file); // batalkan gambar yang sudah terlanjur diupload
    }
    fail($form_page, 'Gagal menyimpan konser: ' . $e->getMessage());
}
 
redirect_back($form_page, ['status' => 'ok', 'msg' => 'Konser "' . $title . '" berhasil ditambahkan.']);
 