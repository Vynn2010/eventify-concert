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
 
// ---------- BUAT BOOKING + TIKET (satu kursi = satu QR) ----------
mysqli_begin_transaction($conn);
try {
 
    // ----- 1. HITUNG TOTAL dari database (bukan dari form, supaya tidak bisa dimanipulasi) -----
    $c = mysqli_prepare($conn, "SELECT price FROM concerts WHERE id = ?");
    mysqli_stmt_bind_param($c, "i", $concert_id);
    mysqli_stmt_execute($c);
    $concert = mysqli_fetch_assoc(mysqli_stmt_get_result($c));
    if (!$concert) {
        throw new Exception("Konser tidak ditemukan");
    }
 
    $placeholders = implode(',', array_fill(0, count($seat_ids), '?'));
    $s = mysqli_prepare($conn,
        "SELECT COUNT(*) AS n, COALESCE(SUM(price), 0) AS seat_total
         FROM seats WHERE concert_id = ? AND id IN ($placeholders)");
    mysqli_stmt_bind_param($s, 'i' . str_repeat('i', count($seat_ids)), $concert_id, ...$seat_ids);
    mysqli_stmt_execute($s);
    $seat_info = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    if ((int)$seat_info['n'] !== count($seat_ids)) {
        throw new Exception("Ada kursi yang tidak ditemukan");
    }
 
    $seat_amount  = count($seat_ids);
    $total_amount = (float)$concert['price'] * $seat_amount + (float)$seat_info['seat_total'];
 
    // ----- 2. SIMPAN DATA BOOKING (satu pembayaran = satu baris) -----
    // booking_status 'paid' = contoh nilai; sesuaikan dengan isi kolom booking_status di tabelmu
    $b = mysqli_prepare($conn,
        "INSERT INTO bookings (user_id, booking_date, seat_amount, total_amount, booking_status, payment_method, paid_at)
         VALUES (?, NOW(), ?, ?, 'paid', ?, NOW())");
    mysqli_stmt_bind_param($b, "iids", $user_id, $seat_amount, $total_amount, $metode);
    mysqli_stmt_execute($b);
    $booking_id = mysqli_insert_id($conn);
 
    // Hubungkan tiket ke booking kalau tabel tickets sudah punya kolom booking_id
    $has_booking_id = false;
    $col = mysqli_query($conn, "SHOW COLUMNS FROM tickets LIKE 'booking_id'");
    if ($col && mysqli_num_rows($col) > 0) {
        $has_booking_id = true;
    }
 
    // ----- 3. BUAT TIKET UNTUK TIAP KURSI -----
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
 
        if ($has_booking_id) {
            $i = mysqli_prepare($conn, "INSERT INTO tickets (user_id, concert_id, seat_id, qr_token, booking_id) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($i, "iiisi", $user_id, $concert_id, $seat_id, $qr_token, $booking_id);
        } else {
            $i = mysqli_prepare($conn, "INSERT INTO tickets (user_id, concert_id, seat_id, qr_token) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($i, "iiis", $user_id, $concert_id, $seat_id, $qr_token);
        }
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