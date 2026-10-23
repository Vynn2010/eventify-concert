<?php
// Endpoint JSON untuk scan.php: cek token QR lalu tandai tiket sebagai 'used'
session_start();
header('Content-Type: application/json; charset=utf-8');
 
function respond(string $result, string $message, ?array $ticket = null, int $http_code = 200): void {
    http_response_code($http_code);
    echo json_encode(['result' => $result, 'message' => $message, 'ticket' => $ticket], JSON_UNESCAPED_UNICODE);
    exit;
}
 
// Hanya admin yang login
if (($_SESSION["login_berhasil_admin"] ?? false) !== true) {
    respond('error', 'Sesi admin berakhir. Silakan login ulang.', null, 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Metode tidak diizinkan.', null, 405);
}
 
require_once __DIR__ . "/cek_koneksi.php";
 
$token      = trim($_POST['token'] ?? '');
$concert_id = (int)($_POST['concert_id'] ?? 0); // 0 = semua konser
 
if ($token === '' || mb_strlen($token) > 255) {
    respond('invalid', 'QR tidak terbaca atau token kosong.');
}
 
try {
    // ---------- CARI TIKET ----------
    $stmt = mysqli_prepare($conn,
        "SELECT t.id, t.status, t.concert_id, c.title, s.seat_number, u.email
         FROM tickets t
         JOIN concerts c ON c.id = t.concert_id
         JOIN seats s    ON s.id = t.seat_id
         JOIN users u    ON u.id = t.user_id
         WHERE t.qr_token = ?");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
 
    if (!$row) {
        respond('invalid', 'Tiket tidak ditemukan. QR mungkin palsu atau sudah dibatalkan.');
    }
 
    $ticket = [
        'code'    => 'TKT-' . str_pad($row['id'], 5, '0', STR_PAD_LEFT),
        'concert' => $row['title'],
        'seat'    => $row['seat_number'],
        'email'   => $row['email'],
    ];
 
    // ---------- KONSER HARUS SESUAI (kalau admin memilih konser) ----------
    if ($concert_id > 0 && (int)$row['concert_id'] !== $concert_id) {
        respond('wrong_concert', 'Tiket ini untuk konser lain.', $ticket);
    }
 
    // ---------- STATUS ----------
    if ($row['status'] === 'used') {
        respond('used', 'Tiket ini sudah pernah dipakai.', $ticket);
    }
    if ($row['status'] !== 'valid') {
        respond('invalid', 'Status tiket tidak valid: ' . $row['status'], $ticket);
    }
 
    // ---------- TANDAI DIPAKAI (atomik: hanya berhasil kalau masih 'valid') ----------
    $has_scanned_at = false;
    $res = mysqli_query($conn, "SHOW COLUMNS FROM tickets LIKE 'scanned_at'");
    if ($res && mysqli_num_rows($res) > 0) {
        $has_scanned_at = true;
    }
 
    $sql = $has_scanned_at
        ? "UPDATE tickets SET status = 'used', scanned_at = NOW() WHERE id = ? AND status = 'valid'"
        : "UPDATE tickets SET status = 'used' WHERE id = ? AND status = 'valid'";
 
    $stmt = mysqli_prepare($conn, $sql);
    $ticket_id = (int)$row['id'];
    mysqli_stmt_bind_param($stmt, "i", $ticket_id);
    mysqli_stmt_execute($stmt);
 
    if (mysqli_stmt_affected_rows($stmt) === 1) {
        respond('ok', 'Tiket valid. Silakan masuk.', $ticket);
    }
 
    // Petugas lain sudah memindai tiket yang sama di detik yang sama
    respond('used', 'Tiket ini baru saja dipakai.', $ticket);
 
} catch (mysqli_sql_exception $e) {
    respond('error', 'Kesalahan database: ' . $e->getMessage(), null, 500);
}
 