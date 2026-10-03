<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
// ---------- SWITCH ACCOUNT: akhiri sesi lalu ke halaman login ----------
if (isset($_GET['switch'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: loginpage_user.php");
    exit;
}
 
// ---------- PASTIKAN USER LOGIN ----------
// Sesuaikan key 'user_id' dengan yang diset saat login
if (empty($_SESSION['user_id'])) {
    header("Location: loginpage_user.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];
 
function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
 
// ---------- DATA USER (password sengaja tidak diambil) ----------
$stmt = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
 
if (!$user) {
    $_SESSION = [];
    session_destroy();
    header("Location: loginpage_user.php");
    exit;
}
 
$email = $user['email'];
$nama  = ucfirst(strstr($email, '@', true) ?: $email); // tabel users belum punya kolom nama, pakai bagian depan email
 
// ---------- HISTORY PAYMENT (dari tiket yang sudah dibeli) ----------
$stmt = mysqli_prepare($conn, "SELECT t.id, t.created_at, s.seat_number, s.price AS harga_kursi,
                                      c.title, c.price AS harga_konser, c.img
                               FROM tickets t
                               JOIN seats s    ON s.id = t.seat_id
                               JOIN concerts c ON c.id = t.concert_id
                               WHERE t.user_id = ?
                               ORDER BY t.id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
 
$history = [];
while ($row = mysqli_fetch_assoc($hasil)) {
    $history[] = $row;
}
 
// ---------- FOTO KONSER (kanan): konser tiket terbaru, kalau belum ada pakai konser terdekat ----------
$foto = null;
$judul_foto = '';
foreach ($history as $h) {
    if (!empty($h['img'])) { $foto = $h['img']; $judul_foto = $h['title']; break; }
}
if (!$foto) {
    $r = mysqli_query($conn, "SELECT title, img FROM concerts
                              WHERE img IS NOT NULL AND img <> ''
                              ORDER BY (concert_date >= NOW()) DESC, concert_date ASC LIMIT 1");
    if ($r && ($c = mysqli_fetch_assoc($r))) {
        $foto = $c['img'];
        $judul_foto = $c['title'];
    }
}
 
$header_search = false;
$halaman_aktif = 'profile';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased flex flex-col">
 
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="w-full max-w-6xl mx-auto px-4 sm:px-5 py-6 flex-grow">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 
            <!-- KIRI: info user -->
            <section class="bg-[#d9d9d9] rounded-[28px] p-6 sm:p-8 flex flex-col min-h-[30rem]">
                <h1 class="text-2xl sm:text-3xl font-bold mb-6">Welcome, <?= htmlspecialchars($nama) ?></h1>
 
                <div class="text-sm flex flex-col gap-5">
                    <p>
                        <span class="font-semibold">Email :</span>
                        <span class="break-all"><?= htmlspecialchars($email) ?></span>
                    </p>
                    <p>
                        <span class="font-semibold">Password :</span>
                        <span class="tracking-widest">••••••••••</span>
                    </p>
                </div>
 
                <!-- History payment (bisa scroll) -->
                <div class="mt-5 flex flex-col flex-1 min-h-0">
                    <p class="text-sm font-semibold mb-2">History Payment :</p>
 
                    <div class="flex-1 min-h-0 max-h-64 overflow-y-auto pr-1 flex flex-col gap-2">
                        <?php if (empty($history)): ?>
                            <p class="text-sm text-gray-600 py-4">Belum ada riwayat pembayaran.</p>
                        <?php endif; ?>
 
                        <?php foreach ($history as $h):
                            $total = (float)$h['harga_konser'] + (float)$h['harga_kursi'];
                            $waktu = !empty($h['created_at']) ? date('d M Y, H:i', strtotime($h['created_at'])) : '-';
                        ?>
                            <div class="bg-white/70 rounded-xl px-4 py-3 text-sm flex justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold truncate"><?= htmlspecialchars($h['title']) ?></p>
                                    <p class="text-xs text-gray-600">Kursi <?= htmlspecialchars($h['seat_number']) ?> · <?= htmlspecialchars($waktu) ?></p>
                                </div>
                                <p class="font-semibold shrink-0"><?= rupiah($total) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
 
                <!-- Tombol -->
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <a href="../../actions/logout.php"
                       class="text-center bg-[#6b6b6b] hover:bg-[#555] text-white text-sm rounded-full py-2.5 transition">Log out</a>
                    <a href="profile.php?switch=1"
                       class="text-center bg-[#6b6b6b] hover:bg-[#555] text-white text-sm rounded-full py-2.5 transition">Switch account</a>
                </div>
            </section>
 
            <!-- KANAN: concert photo -->
            <section class="relative bg-[#d9d9d9] rounded-[28px] overflow-hidden min-h-80 lg:min-h-0 flex items-center justify-center">
                <?php if ($foto): ?>
                    <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($judul_foto) ?>"
                         class="absolute inset-0 w-full h-full object-cover object-top">
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-6 py-4">
                        <p class="text-white font-semibold"><?= htmlspecialchars($judul_foto) ?></p>
                    </div>
                <?php else: ?>
                    <p class="text-3xl sm:text-4xl text-center leading-snug">Concert<br>Photo</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
 
    <?php include "../../components/landing/footer.php"; ?>
</body>
</html>
<?php mysqli_close($conn); ?>