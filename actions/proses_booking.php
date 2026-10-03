<?php
// File ini ada di folder actions/, jadi include memakai __DIR__ (bukan ../../actions/)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/cek_koneksi.php';
 
// Biar error query (mis. duplikat kursi) dilempar sebagai exception dan memicu rollback
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
 
// Kunci rahasia untuk QR token. Ganti dengan string acak panjang, idealnya simpan di file config.
const QR_SECRET = 'GANTI_DENGAN_STRING_ACAK_PANJANG';
 
// ---------- PASTIKAN USER LOGIN ----------
// Sesuaikan key 'user_id' dengan yang dipakai saat login
if (empty($_SESSION['user_id'])) {
    header("Location: ../pages/user/loginpage_user.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];
 
// ---------- HANYA TERIMA POST DARI book.php ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/user/home_page_user.php");
    exit;
}
 
$concert_id = (int)($_POST['concert_id'] ?? 0);
$seat_ids   = array_values(array_unique(array_map('intval', (array)($_POST['seats'] ?? []))));
$seat_ids   = array_values(array_filter($seat_ids, fn($v) => $v > 0));
$metode     = $_POST['payment_method'] ?? '';
 
if ($concert_id === 0 || empty($seat_ids) || !in_array($metode, ['BCA', 'Dana', 'Gopay'], true)) {
    header("Location: ../pages/user/" . ($concert_id ? "seat.php?id=$concert_id" : "home_page_user.php"));
    exit;
}
 
// ---------- BUAT TIKET (satu kursi = satu QR) ----------
mysqli_begin_transaction($conn);
try {
    foreach ($seat_ids as $seat_id) {
        // Ambil kursi hanya jika masih Available (hindari dobel pesan)
        $u = mysqli_prepare($conn, "UPDATE seats SET seat_status = 'Not Available'
                                    WHERE id = ? AND concert_id = ? AND seat_status = 'Available'");
        mysqli_stmt_bind_param($u, "ii", $seat_id, $concert_id);
        mysqli_stmt_execute($u);
        if (mysqli_stmt_affected_rows($u) !== 1) {
            throw new Exception("Kursi $seat_id sudah tidak tersedia");
        }
 
        $qr       = $user_id . '#' . $concert_id . '#' . $seat_id;
        $qr_token = hash_hmac('sha256', $qr, QR_SECRET);
 
        $i = mysqli_prepare($conn, "INSERT INTO tickets (user_id, concert_id, seat_id, qr_token) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($i, "iiis", $user_id, $concert_id, $seat_id, $qr_token);
        mysqli_stmt_execute($i);
    }
    mysqli_commit($conn);
    header("Location: ../pages/user/ticket.php");
    exit;
} catch (Throwable $e) {
    mysqli_rollback($conn);
    // error_log($e->getMessage()); // aktifkan kalau perlu melihat penyebab gagal
    header("Location: ../pages/user/seat.php?id=$concert_id&error=kursi");
    exit;
}