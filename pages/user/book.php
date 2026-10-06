<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
 
// ---------- TERIMA DATA DARI HALAMAN PILIH KURSI ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: home_page_user.php");
    exit;
}
 
$concert_id = (int)($_POST['concert_id'] ?? 0);
$seat_ids   = array_values(array_unique(array_map('intval', (array)($_POST['seats'] ?? []))));
$seat_ids   = array_values(array_filter($seat_ids, fn($v) => $v > 0));
 
$halaman_kursi = "seat.php?id=" . $concert_id; // ganti jika nama halaman pilih kursi berbeda
 
if ($concert_id === 0 || empty($seat_ids)) {
    header("Location: " . ($concert_id ? $halaman_kursi : "home_page_user.php"));
    exit;
}
 
// ---------- KONSER ----------
$stmt = mysqli_prepare($conn, "SELECT id, title, artist, venue, concert_date, img, price FROM concerts WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $concert_id);
mysqli_stmt_execute($stmt);
$konser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
 
if (!$konser) { http_response_code(404); exit('Konser tidak ditemukan'); }
 
// ---------- KURSI TERPILIH (dicek ulang di server) ----------
$placeholders = implode(',', array_fill(0, count($seat_ids), '?'));
$tipe = 'i' . str_repeat('i', count($seat_ids));
$sql  = "SELECT id, seat_number, price, seat_status FROM seats
         WHERE concert_id = ? AND id IN ($placeholders)
         ORDER BY LEFT(seat_number, 1), LENGTH(seat_number), seat_number";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $tipe, $concert_id, ...$seat_ids);
mysqli_stmt_execute($stmt);
$hasil_kursi = mysqli_stmt_get_result($stmt);
 
$kursi = [];
while ($k = mysqli_fetch_assoc($hasil_kursi)) {
    $kursi[] = $k;
}
 
// Semua kursi harus ada dan masih Available
$semua_tersedia = count($kursi) === count($seat_ids);
foreach ($kursi as $k) {
    if (strcasecmp(trim($k['seat_status']), 'Available') !== 0) {
        $semua_tersedia = false;
    }
}
if (!$semua_tersedia) {
    header("Location: " . $halaman_kursi . "&error=kursi");
    exit;
}
 
// ---------- HITUNG HARGA ----------
$harga_konser    = (float)$konser['price'];   // harga tiket konser per kursi
$jumlah_kursi    = count($kursi);
$subtotal_konser = $harga_konser * $jumlah_kursi;
$subtotal_kursi  = 0;
foreach ($kursi as $k) {
    $subtotal_kursi += (float)$k['price'];
}
$total = $subtotal_konser + $subtotal_kursi;
 
function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
 
$metode_pembayaran = ['BCA', 'Dana', 'Gopay'];
 
$header_search = true; // navbar menampilkan kolom "Search event"
$halaman_aktif = 'event';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - <?= htmlspecialchars($konser['title']) ?> - Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased flex flex-col">
 
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-5 py-8 flex-grow">
 
        <form action="../../actions/proses_booking.php" method="POST" id="payForm">
            <input type="hidden" name="concert_id" value="<?= (int)$konser['id'] ?>">
            <?php foreach ($kursi as $k): ?>
                <input type="hidden" name="seats[]" value="<?= (int)$k['id'] ?>">
            <?php endforeach; ?>
 
            <div class="grid grid-cols-1 lg:grid-cols-[3fr_2fr] gap-5">
 
                <!-- KIRI: gambar konser + payment method -->
                <div class="flex flex-col gap-5">
 
                    <!-- Gambar konser yang dipilih -->
                    <div class="relative bg-[#d9d9d9] rounded-[28px] overflow-hidden h-56 sm:h-64">
                        <?php if (!empty($konser['img'])): ?>
                            <img src="<?= htmlspecialchars($konser['img']) ?>" alt="<?= htmlspecialchars($konser['title']) ?>"
                                 class="w-full h-full object-cover object-top">
                        <?php endif; ?>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-6 py-4">
                            <p class="text-white text-xl font-semibold"><?= htmlspecialchars($konser['title']) ?></p>
                            <p class="text-gray-200 text-sm"><?= htmlspecialchars($konser['artist']) ?></p>
                        </div>
                    </div>
 
                    <!-- Payment method -->
                    <section class="bg-[#6b6b6b] rounded-[28px] p-5 sm:p-6">
                        <h2 class="text-white text-lg font-semibold text-center mb-4">Payment method</h2>
 
                        <div class="flex flex-col gap-3">
                            <?php foreach ($metode_pembayaran as $i => $metode): ?>
                                <label class="flex items-center gap-4 cursor-pointer">
                                    <input type="radio" name="payment_method" value="<?= htmlspecialchars($metode) ?>"
                                           class="payment-input peer sr-only" <?= $i === 0 ? 'required' : '' ?>>
                                    <span class="flex-1 bg-[#d9d9d9] hover:bg-white text-gray-900 font-semibold rounded-full px-6 py-2 transition
                                                 peer-checked:bg-white peer-focus-visible:ring-2 peer-focus-visible:ring-white">
                                        <?= htmlspecialchars($metode) ?>
                                    </span>
                                    <!-- Lingkaran centang -->
                                    <span class="w-7 h-7 shrink-0 rounded-full bg-[#2b2b2b] border-2 border-[#2b2b2b] flex items-center justify-center transition
                                                 peer-checked:bg-white peer-checked:border-white">
                                        <svg class="w-4 h-4 text-[#2b2b2b] hidden check-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
 
                <!-- KANAN: detail harga (latar gambar) + confirm pay -->
                <aside class="flex flex-col gap-4">
                    <div class="relative rounded-[28px] overflow-hidden bg-[#6b6b6b] flex-grow min-h-80">
                        <?php if (!empty($konser['img'])): ?>
                            <img src="<?= htmlspecialchars($konser['img']) ?>" alt=""
                                 class="absolute inset-0 w-full h-full object-cover" aria-hidden="true">
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-black/65"></div>
 
                        <div class="relative p-5 sm:p-6 text-white">
                            <h2 class="text-lg font-semibold text-center mb-4">Detail Price</h2>
 
                            <div class="text-sm flex flex-col gap-2">
                                <div class="flex justify-between gap-3">
                                    <span>Harga konser (<?= $jumlah_kursi ?> × <?= rupiah($harga_konser) ?>)</span>
                                    <span class="shrink-0"><?= rupiah($subtotal_konser) ?></span>
                                </div>
 
                                <p class="mt-2 text-gray-300 text-xs uppercase tracking-wide">Tempat duduk</p>
                                <?php foreach ($kursi as $k): ?>
                                    <div class="flex justify-between gap-3">
                                        <span>Kursi <?= htmlspecialchars($k['seat_number']) ?></span>
                                        <span class="shrink-0"><?= rupiah($k['price']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
 
                            <div class="border-t border-white/40 mt-4 pt-4 flex justify-between text-base font-semibold">
                                <span>Total</span>
                                <span><?= rupiah($total) ?></span>
                            </div>
                        </div>
                    </div>
 
                    <button type="submit" id="payBtn" disabled
                            class="bg-[#6b6b6b] hover:bg-[#555] disabled:bg-[#9a9a9a] disabled:cursor-not-allowed text-white text-lg font-semibold rounded-2xl py-4 transition">
                        Confirm Pay
                    </button>
                </aside>
            </div>
        </form>
    </main>
 
    <?php include "././components/landing/footer.php"; ?>
 
    <script>
        const methods = document.querySelectorAll('.payment-input');
        const payBtn  = document.getElementById('payBtn');
 
        function update() {
            const dipilih = [...methods].some(m => m.checked);
            payBtn.disabled = !dipilih;
 
            // tampilkan ikon centang hanya pada metode yang dipilih
            methods.forEach(m => {
                const icon = m.closest('label').querySelector('.check-icon');
                icon.classList.toggle('hidden', !m.checked);
            });
        }
 
        methods.forEach(m => m.addEventListener('change', update));
        update();
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>