<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_admin.php";
include "../../repo/booking_repositories.php";
 
// Dropdown konser
    $concerts = mysqli_query($conn, "SELECT id, title FROM concerts ORDER BY id DESC");
// Dropdown kategori
    $categories = mysqli_query($conn, "SELECT id, category_name FROM categories ORDER BY id");
 
$status  = $_GET['status'] ?? '';
$message = $_GET['msg'] ?? '';
$total   = (int)($_GET['total'] ?? 0);
$disabled_total = (int)($_GET['disabled'] ?? 0);
 
$admin_email = $_SESSION["email"] ?? "admin";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Seats - Admin Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&display=swap">
    <style>body { font-family: 'Sora', sans-serif; }</style>
</head>
<body class="bg-[#F4F3FE] text-[#17143D]">
<div class="flex flex-col md:flex-row min-h-screen">
 
    <!-- SIDEBAR -->
    <aside class="md:w-60 shrink-0 bg-[#17143D] text-[#F4F3FE] p-6 flex flex-col gap-8">
        <div class="text-2xl font-bold">
            Eventify<span class="text-[#8F9BFB]">.</span>
            <div class="text-xs font-normal opacity-60 mt-1">Admin panel</div>
        </div>
 
        <nav class="flex flex-col gap-2 text-sm">
            <a href="home_page_admin.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Dashboard</a>
            <a href="seat_generate.php" class="px-4 py-3 rounded-xl bg-[#5835FF] text-white font-semibold">Generate Seats</a>
            <a href="booking.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Booking</a>
            <a href="concerts.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Concerts</a>
            <a href="genres.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Genres</a>
        </nav>
 
        <a href="../../actions/logout_admin.php"
           class="md:mt-auto text-center text-sm px-4 py-3 rounded-full border border-white/30 hover:bg-white/10">
            Log out
        </a>
    </aside>
 
    <!-- KONTEN -->
    <main class="flex-1 min-w-0 p-6 md:p-10 flex flex-col gap-8">
 
        <header class="flex items-center gap-4">
            <a href="home_page_admin.php" aria-label="Kembali ke dashboard"
               class="shrink-0 w-11 h-11 rounded-full bg-[#5835FF] hover:bg-[#4D4FFF] text-white flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold">Generate Seats</h1>
                <p class="text-sm opacity-65 mt-1">Welcome, <?= htmlspecialchars($admin_email) ?></p>
            </div>
        </header>
 
        <?php if ($status === 'ok'): ?>
            <div class="bg-green-100 text-green-800 text-sm rounded-xl px-4 py-3">
                Berhasil membuat <?= $total ?> kursi<?= $disabled_total > 0 ? " ($disabled_total di antaranya disabled)" : "" ?>.
            </div>
        <?php elseif ($status === 'error'): ?>
            <div class="bg-red-100 text-red-800 text-sm rounded-xl px-4 py-3">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
 
        <!-- FORM -->
        <section class="bg-white rounded-2xl p-6 md:p-8 w-full max-w-3xl">
            <h2 class="text-lg font-semibold mb-1">Seat details</h2>
            <p class="text-sm opacity-65 mb-6">Buat banyak kursi sekaligus untuk satu konser.</p>
 
            <form action="../../actions/seat_generate_process.php" method="POST"
                  class="grid grid-cols-1 md:grid-cols-2 gap-5">
 
                <div class="md:col-span-2">
                    <label for="concert_id" class="block text-sm mb-1.5">Concert</label>
                    <select id="concert_id" name="concert_id" required
                            class="w-full bg-[#F4F3FE] rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                        <option value="">-- choose concert --</option>
                        <?php while ($concert = mysqli_fetch_assoc($concerts)): ?>
                            <option value="<?= (int)$concert['id'] ?>"><?= htmlspecialchars($concert['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
 
                <div>
                    <label for="rows" class="block text-sm mb-1.5">Rows</label>
                    <input type="text" id="rows" name="rows" required placeholder="A-F or A,B,C"
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                    <p class="text-xs opacity-60 mt-1.5">Pakai rentang (A-F) atau daftar dipisah koma (A,C,E).</p>
                </div>
 
                <div>
                    <label for="seats_per_row" class="block text-sm mb-1.5">Seats per row</label>
                    <input type="number" id="seats_per_row" name="seats_per_row" min="1" max="100" value="20" required
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                    <p class="text-xs opacity-60 mt-1.5">Maksimal 100 kursi per baris.</p>
                </div>
 
                <div>
                    <label for="category_id" class="block text-sm mb-1.5">Category</label>
                    <select id="category_id" name="category_id" required
                            class="w-full bg-[#F4F3FE] rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                        <option value="">-- choose category --</option>
                        <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= (int)$category['id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
 
                <div>
                    <label for="price" class="block text-sm mb-1.5">Price per seat (Rp)</label>
                    <input type="number" id="price" name="price" min="0" step="1000" value="200000" required
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                </div>
 
                <!-- Disabled seats -->
                <div class="md:col-span-2">
                    <label for="disabled_seats" class="block text-sm mb-1.5">Disabled seats (optional)</label>
                    <input type="text" id="disabled_seats" name="disabled_seats" placeholder="A5, A6, B10"
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                    <p class="text-xs opacity-60 mt-1.5">Pisahkan dengan koma. Kursi ini tetap dibuat tapi tidak bisa dipesan.</p>
                </div>
 
                <!-- Ringkasan otomatis -->
                <div class="md:col-span-2 bg-[#F4F3FE] rounded-xl px-4 py-3 text-sm flex items-center justify-between gap-3">
                    <span class="opacity-65">Seats to be created</span>
                    <span id="preview" class="font-semibold text-[#5835FF]">0 seats</span>
                </div>
 
                <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                    <button type="submit"
                            class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold rounded-full px-8 py-3 transition">
                        Generate
                    </button>
                    <a href="home_page_admin.php" class="text-sm underline opacity-70 hover:opacity-100">Cancel</a>
                </div>
            </form>
        </section>
 
    </main>
</div>
 
<script>
    (function () {
        var rowsInput     = document.getElementById('rows');
        var perRow        = document.getElementById('seats_per_row');
        var disabledInput = document.getElementById('disabled_seats');
        var preview       = document.getElementById('preview');
 
        function countRows(value) {
            value = value.trim().toUpperCase();
            var range = value.match(/^([A-Z])\s*-\s*([A-Z])$/);
            if (range) {
                return Math.abs(range[2].charCodeAt(0) - range[1].charCodeAt(0)) + 1;
            }
            var list = value.split(',').map(function (v) { return v.trim(); }).filter(Boolean);
            return new Set(list).size;
        }
 
        function countDisabled(value) {
            var list = value.toUpperCase().split(',')
                .map(function (v) { return v.trim(); })
                .filter(function (v) { return /^[A-Z]\d+$/.test(v); });
            return new Set(list).size;
        }
 
        function update() {
            var rows     = countRows(rowsInput.value);
            var seats    = rows * (parseInt(perRow.value, 10) || 0);
            var disabled = countDisabled(disabledInput.value);
            preview.textContent = seats + ' seats (' + rows + ' row' + (rows === 1 ? '' : 's') + ')'
                + (disabled ? ', ' + disabled + ' disabled' : '');
        }
 
        rowsInput.addEventListener('input', update);
        perRow.addEventListener('input', update);
        disabledInput.addEventListener('input', update);
        update();
    })();
</script>