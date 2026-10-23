<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
include "../../repo/homepage_user_repositories.php";
 
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
 
// ---------- CANCEL TICKETS: batalkan tiket yang dipilih, kursinya kembali Available ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_tickets'])) {
 
    // Ambil id tiket yang dicentang, pastikan angka positif dan tidak dobel
    $ticket_ids = array_values(array_unique(array_filter(
        array_map('intval', (array)($_POST['ticket_ids'] ?? [])),
        fn($id) => $id > 0
    )));
 
    if (empty($ticket_ids)) {
        header("Location: profile.php?cancel=none");
        exit;
    }
 
    $placeholders = implode(',', array_fill(0, count($ticket_ids), '?'));
    $types        = 'i' . str_repeat('i', count($ticket_ids)); // user_id + semua ticket id
 
//    mysqli_begin_transaction($conn);
    try {
        // 1. Kembalikan status kursi (hanya dari tiket milik user ini yang dipilih)
        $stmt = UserProfile::findUserStatusTicket($conn, $placeholders, $types, $user_id, $ticket_ids);
 
        // 2. Hapus tiket yang dipilih
        $cancelled_count = UserProfile::deleteUserStatusTicket($conn, $placeholders, $types, $user_id, $ticket_ids);
 
       // mysqli_commit($conn);
        header("Location: profile.php?cancel=ok&n=" . $cancelled_count);
    } catch (mysqli_sql_exception $e) {
   //     mysqli_rollback($conn);
        header("Location: profile.php?cancel=error");
    }
    exit;
}
$cancel_status   = $_GET['cancel'] ?? '';
$cancelled_count = (int)($_GET['n'] ?? 0);
 
function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
 
// ---------- DATA USER (password sengaja tidak diambil) ----------
$user = UserProfile::findUserPassword($conn, $user_id);
 
if (!$user) {
    $_SESSION = [];
    session_destroy();
    header("Location: loginpage_user.php");
    exit;
}
 
$email = $user['email'];
$nama  = ucfirst(strstr($email, '@', true) ?: $email); // tabel users belum punya kolom nama, pakai bagian depan email
 
// ---------- HISTORY PAYMENT (dari tiket yang sudah dibeli) ----------
 
$hasil = UserProfile::findUserHistory($conn, $user_id);
 
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
    $r = UserProfile::findUserProfile($conn);
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
 
                <!-- Pesan hasil cancel -->
                <?php if ($cancel_status === 'ok'): ?>
                    <p class="mt-5 text-sm bg-green-100 text-green-800 rounded-lg px-3 py-2">
                        <?= $cancelled_count ?> tiket dibatalkan dan kursinya dikembalikan.
                    </p>
                <?php elseif ($cancel_status === 'none'): ?>
                    <p class="mt-5 text-sm bg-yellow-100 text-yellow-800 rounded-lg px-3 py-2">
                        Pilih minimal satu tiket yang ingin dibatalkan.
                    </p>
                <?php elseif ($cancel_status === 'error'): ?>
                    <p class="mt-5 text-sm bg-red-100 text-red-800 rounded-lg px-3 py-2">
                        Gagal membatalkan tiket. Tidak ada data yang diubah.
                    </p>
                <?php endif; ?>
 
                <!-- History payment (bisa scroll) -->
                <div class="mt-5 flex flex-col flex-1 min-h-0">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-semibold">History Payment :</p>
                        <?php if (!empty($history)): ?>
                            <label class="text-xs flex items-center gap-1.5 cursor-pointer select-none">
                                <input type="checkbox" id="select-all" class="w-3.5 h-3.5 accent-red-600">
                                Select all
                            </label>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($history)): ?>
                        <p class="text-xs text-gray-600 mb-2">Klik tiket untuk melihat QR-nya.</p>
                    <?php endif; ?>
 
                    <form id="cancel-form" action="profile.php" method="POST"
                          class="flex-1 min-h-0 max-h-64 overflow-y-auto pr-1 flex flex-col gap-2">
                        <?php if (empty($history)): ?>
                            <p class="text-sm text-gray-600 py-4">Belum ada riwayat pembayaran.</p>
                        <?php endif; ?>
 
                        <?php foreach ($history as $h):
                            $total = (float)$h['harga_konser'] + (float)$h['harga_kursi'];
                            $waktu = !empty($h['created_at']) ? date('d M Y, H:i', strtotime($h['created_at'])) : '-';
                        ?>
                            <div class="bg-white/70 hover:bg-white rounded-xl text-sm flex items-center transition">
                                <!-- Checkbox terpisah dari link supaya mencentang tidak membuka tiket -->
                                <label class="pl-4 pr-2 py-3 flex items-center cursor-pointer self-stretch">
                                    <input type="checkbox" name="ticket_ids[]" value="<?= (int)$h['id'] ?>"
                                           class="ticket-check w-4 h-4 accent-red-600 shrink-0">
                                </label>
 
                                <!-- Klik area ini untuk melihat tiket + QR -->
                                <a href="ticket.php?id=<?= (int)$h['id'] ?>"
                                   title="Lihat tiket dan QR"
                                   class="min-w-0 flex-1 flex items-center gap-3 py-3 pr-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold truncate"><?= htmlspecialchars($h['title']) ?></p>
                                        <p class="text-xs text-gray-600">Kursi <?= htmlspecialchars($h['seat_number']) ?> · <?= htmlspecialchars($waktu) ?></p>
                                    </div>
                                    <p class="font-semibold shrink-0"><?= rupiah($total) ?></p>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </form>
                </div>
 
                <!-- Tombol -->
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <a href="../../actions/logout.php"
                       class="text-center bg-[#6b6b6b] hover:bg-[#555] text-white text-sm rounded-full py-2.5 transition">Log out</a>
                    <a href="profile.php?switch=1"
                       class="text-center bg-[#6b6b6b] hover:bg-[#555] text-white text-sm rounded-full py-2.5 transition">Switch account</a>
                </div>
 
                <!-- Cancel Tickets (hanya muncul kalau ada tiket; aktif setelah ada yang dicentang) -->
                <?php if (!empty($history)): ?>
                    <button type="submit" form="cancel-form" id="cancel-btn" name="cancel_tickets" value="1" disabled
                            class="mt-4 w-full text-center bg-red-600 hover:bg-red-700 text-white text-sm rounded-full py-2.5 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-red-600">
                        Cancel Tickets
                    </button>
                <?php endif; ?>
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
 
    <script>
    (function () {
        var form      = document.getElementById('cancel-form');
        var button    = document.getElementById('cancel-btn');
        var selectAll = document.getElementById('select-all');
        if (!form || !button) return;
 
        var boxes = form.querySelectorAll('.ticket-check');
 
        function checkedCount() {
            return form.querySelectorAll('.ticket-check:checked').length;
        }
 
        function refresh() {
            var n = checkedCount();
            button.disabled = (n === 0);
            button.textContent = n ? 'Cancel Tickets (' + n + ')' : 'Cancel Tickets';
            if (selectAll) selectAll.checked = (n === boxes.length && boxes.length > 0);
        }
 
        boxes.forEach(function (box) { box.addEventListener('change', refresh); });
 
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                boxes.forEach(function (box) { box.checked = selectAll.checked; });
                refresh();
            });
        }
 
        form.addEventListener('submit', function (e) {
            var n = checkedCount();
            if (n === 0 || !confirm('Batalkan ' + n + ' tiket yang dipilih? Kursinya akan dikembalikan dan tindakan ini tidak bisa dibatalkan.')) {
                e.preventDefault();
            }
        });
 
        refresh();
    })();
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>