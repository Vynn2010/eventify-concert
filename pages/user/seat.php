<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
 
// ---------- KONSER ----------
$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT id, title, artist, venue, concert_date FROM concerts WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$konser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
 
if (!$konser) { http_response_code(404); exit('Konser tidak ditemukan'); }
 
// ---------- KURSI ----------
$stmt = mysqli_prepare($conn, "SELECT id, seat_number, category_id, price, seat_status
                               FROM seats WHERE concert_id = ?
                               ORDER BY LEFT(seat_number, 1), LENGTH(seat_number), seat_number");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result_seats = mysqli_stmt_get_result($stmt);
 
// Kelompokkan kursi per baris (huruf pertama: A1, A2 -> baris A)
$rows = [];
while ($s = mysqli_fetch_assoc($result_seats)) {
    $rows[strtoupper(substr($s['seat_number'], 0, 1))][] = $s;
}
 
$header_search = true; // navbar menampilkan kolom "Search event"
$halaman_detail = "concertdetail.php?id=" . $id;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Kursi - <?= htmlspecialchars($konser['title']) ?> - Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased flex flex-col">
 
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-5 py-8 flex-grow">
 
        <!-- Pil abu: kembali + nama konser -->
        <a href="<?= htmlspecialchars($halaman_detail) ?>"
           class="inline-flex items-center gap-2 bg-[#d9d9d9] hover:bg-[#c8c8c8] text-gray-800 text-sm rounded-full px-5 py-2 mb-6 max-w-full transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="truncate"><?= htmlspecialchars($konser['title']) ?> - <?= htmlspecialchars($konser['artist']) ?></span>
        </a>
 
        <form action="book.php" method="POST" id="seatForm">
            <input type="hidden" name="concert_id" value="<?= (int)$konser['id'] ?>">
 
            <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-5">
 
                <!-- KIRI: Seat Selection Form -->
                <section class="bg-[#d9d9d9] rounded-[28px] p-5 sm:p-8">
                    <h2 class="text-lg font-semibold text-center mb-1">Seat Selection</h2>
                    <p class="text-xs text-gray-600 text-center mb-6">Centang kursi yang ingin kamu pesan</p>
 
                    <!-- Panggung -->
                    <div class="bg-[#6b6b6b] text-white text-xs tracking-widest text-center rounded-full py-2 mb-8 max-w-md mx-auto">STAGE</div>
 
                    <?php if (empty($rows)): ?>
                        <p class="text-center text-gray-600 py-10">Belum ada kursi untuk konser ini.</p>
                    <?php else: ?>
                        <div class="flex flex-col gap-3 items-center overflow-x-auto">
                            <?php foreach ($rows as $huruf => $kursi): ?>
                                <div class="flex items-center gap-3">
                                    <span class="w-5 text-sm font-semibold text-gray-700"><?= htmlspecialchars($huruf) ?></span>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($kursi as $k):
                                            $available = strcasecmp(trim($k['seat_status']), 'Available') === 0;
                                        ?>
                                            <label class="<?= $available ? 'cursor-pointer' : 'cursor-not-allowed' ?>"
                                                   title="<?= $available ? 'Tersedia' : 'Tidak tersedia' ?>">
                                                <input type="checkbox" name="seats[]"
                                                       value="<?= (int)$k['id'] ?>"
                                                       data-seat="<?= htmlspecialchars($k['seat_number']) ?>"
                                                       data-price="<?= (float)$k['price'] ?>"
                                                       class="seat-input peer sr-only"
                                                       <?= $available ? '' : 'disabled' ?>>
                                                <span class="flex items-center justify-center w-12 h-12 rounded-xl text-sm font-medium select-none transition
                                                             bg-white text-gray-800 hover:bg-gray-100
                                                             peer-checked:bg-[#6b6b6b] peer-checked:text-white
                                                             peer-focus-visible:ring-2 peer-focus-visible:ring-gray-700
                                                             peer-disabled:bg-[#9a9a9a] peer-disabled:text-gray-600 peer-disabled:line-through peer-disabled:hover:bg-[#9a9a9a]">
                                                    <?= htmlspecialchars($k['seat_number']) ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
 
                    <!-- Legenda -->
                    <div class="flex flex-wrap justify-center gap-5 mt-8 text-xs text-gray-700">
                        <span class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-white inline-block"></span>Tersedia</span>
                        <span class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-[#6b6b6b] inline-block"></span>Dipilih</span>
                        <span class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-[#9a9a9a] inline-block"></span>Tidak tersedia</span>
                    </div>
                </section>
 
                <!-- KANAN: Selection Detail + Confirm -->
                <aside class="flex flex-col gap-4">
                    <div class="bg-[#d9d9d9] rounded-[28px] p-5 sm:p-6 flex-grow min-h-64">
                        <h2 class="text-lg font-semibold text-center mb-4">Selection Detail</h2>
 
                        <p class="text-sm font-medium"><?= htmlspecialchars($konser['title']) ?></p>
                        <p class="text-xs text-gray-600 mb-1">📍 <?= htmlspecialchars($konser['venue']) ?></p>
                        <p class="text-xs text-gray-600 mb-4">
                            🗓 <?= !empty($konser['concert_date']) ? date('d M Y, H:i', strtotime($konser['concert_date'])) : '-' ?>
                        </p>
 
                        <p id="emptyText" class="text-sm text-gray-600 text-center py-6">Belum ada kursi dipilih</p>
                        <ul id="selectedList" class="text-sm flex flex-col gap-2"></ul>
 
                        <div class="border-t border-gray-500/40 mt-4 pt-4 flex justify-between text-sm">
                            <span>Total (<span id="totalSeats">0</span> kursi)</span>
                            <span id="totalPrice" class="font-semibold">Rp 0</span>
                        </div>
                    </div>
 
                    <button type="submit" id="confirmBtn" disabled
                            class="bg-[#6b6b6b] hover:bg-[#555] disabled:bg-[#9a9a9a] disabled:cursor-not-allowed text-white text-lg font-medium rounded-2xl py-4 transition">
                        Confirm
                    </button>
                </aside>
            </div>
        </form>
    </main>
 
    <?php include "../../components/landing/footer.php"; ?>
 
    <script>
        const inputs     = document.querySelectorAll('.seat-input');
        const list       = document.getElementById('selectedList');
        const emptyText  = document.getElementById('emptyText');
        const totalSeats = document.getElementById('totalSeats');
        const totalPrice = document.getElementById('totalPrice');
        const confirmBtn = document.getElementById('confirmBtn');
 
        const rupiah = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
 
        function update() {
            const checked = [...inputs].filter(i => i.checked);
            let total = 0;
 
            list.innerHTML = '';
            checked.forEach(i => {
                const price = parseFloat(i.dataset.price) || 0;
                total += price;
 
                const li = document.createElement('li');
                li.className = 'flex justify-between';
                const a = document.createElement('span');
                a.textContent = 'Kursi ' + i.dataset.seat;
                const b = document.createElement('span');
                b.textContent = rupiah(price);
                li.append(a, b);
                list.appendChild(li);
            });
 
            emptyText.classList.toggle('hidden', checked.length > 0);
            totalSeats.textContent = checked.length;
            totalPrice.textContent = rupiah(total);
            confirmBtn.disabled = checked.length === 0;
        }
 
        inputs.forEach(i => i.addEventListener('change', update));
        update();
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
 