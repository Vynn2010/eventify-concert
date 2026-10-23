<?php
require_once __DIR__ . "/cek_koneksi.php";
include __DIR__ . "/cek_login_admin.php"; // hanya admin yang boleh menambah genre
 
$form_page = "../pages/admin/genres.php";
 
function redirect_back(string $url, array $params): void {
    header("Location: " . $url . "?" . http_build_query($params));
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $form_page");
    exit;
}
 
$genre_name = trim($_POST['genre_name'] ?? '');
 
if ($genre_name === '') {
    redirect_back($form_page, ['status' => 'error', 'msg' => 'Nama genre tidak boleh kosong.']);
}
if (mb_strlen($genre_name) > 50) {
    redirect_back($form_page, ['status' => 'error', 'msg' => 'Nama genre maksimal 50 karakter.']);
}
 
// Cegah genre ganda (tidak peduli huruf besar/kecil)
$stmt = mysqli_prepare($conn, "SELECT id FROM genres WHERE LOWER(genre_name) = LOWER(?)");
mysqli_stmt_bind_param($stmt, "s", $genre_name);
mysqli_stmt_execute($stmt);
if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    redirect_back($form_page, ['status' => 'error', 'msg' => 'Genre "' . $genre_name . '" sudah ada.']);
}
 
try {
    $stmt = mysqli_prepare($conn, "INSERT INTO genres (genre_name) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $genre_name);
    mysqli_stmt_execute($stmt);
} catch (mysqli_sql_exception $e) {
    redirect_back($form_page, ['status' => 'error', 'msg' => 'Gagal menyimpan genre: ' . $e->getMessage()]);
}
 
redirect_back($form_page, ['status' => 'ok', 'msg' => 'Genre "' . $genre_name . '" berhasil ditambahkan.']);
 