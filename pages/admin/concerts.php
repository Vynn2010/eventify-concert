<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_admin.php";
include "../../repo/concertdetail.php";
 
if (!function_exists('rupiah')) {
    function rupiah($n) {
        return 'Rp ' . number_format((float)$n, 0, ',', '.');
    }
}
 
// Helper: ambil satu angka dari query COUNT(*)
function count_rows(mysqli $conn, string $sql): int {
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($res);
    return (int)($row[0] ?? 0);
}
 
// ---------- RINGKASAN ----------
$total_concerts    = count_rows($conn, "SELECT COUNT(*) FROM concerts");
$upcoming_concerts = count_rows($conn, "SELECT COUNT(*) FROM concerts WHERE concert_date >= CURDATE()");
$available_seats   = count_rows($conn, "SELECT COUNT(*) FROM seats WHERE seat_status = 'Available'");
$tickets_sold      = count_rows($conn, "SELECT COUNT(*) FROM tickets");
 
// ---------- DAFTAR KONSER (dengan pencarian) ----------
$q = trim($_GET['q'] ?? '');
 
$sql= Concertdetail::CreateConcertByAdmin();
 
if ($q !== '') {
    $sql .= " WHERE c.title LIKE ? OR c.artist LIKE ? OR c.venue LIKE ?";
}
$sql .= " ORDER BY c.concert_date DESC";
 
$stmt = mysqli_prepare($conn, $sql);
if ($q !== '') {
    $like = "%$q%";
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
 
$concerts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $concerts[] = $row;
}
 
// ---------- PESAN & DATA FORM TAMBAH KONSER ----------
$flash_status = $_GET['status'] ?? '';
$flash_msg    = $_GET['msg'] ?? '';
 
$has_genre_column = false;
$res = mysqli_query($conn, "SHOW COLUMNS FROM concerts LIKE 'genre_id'");
if ($res && mysqli_num_rows($res) > 0) {
    $has_genre_column = true;
}
 
$genre_options = [];
if ($has_genre_column) {
    $res = mysqli_query($conn, "SELECT id, genre_name FROM genres ORDER BY genre_name");
    while ($res && ($g = mysqli_fetch_assoc($res))) {
        $genre_options[] = $g;
    }
}
 
$admin_email = $_SESSION["email"] ?? "admin";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Concerts - Admin Eventify</title>
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
            <a href="seat_generate.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Generate Seats</a>
            <a href="booking.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Booking</a>
            <a href="concerts.php" class="px-4 py-3 rounded-xl bg-[#5835FF] text-white font-semibold">Concerts</a>
            <a href="genres.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Genres</a>
        </nav>
 
        <a href="../../actions/logout_admin.php"
           class="md:mt-auto text-center text-sm px-4 py-3 rounded-full border border-white/30 hover:bg-white/10">
            Log out
        </a>
    </aside>
 
    <!-- KONTEN -->
    <main class="flex-1 min-w-0 p-6 md:p-10 flex flex-col gap-8">
 
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold">Concerts</h1>
                <p class="text-sm opacity-65 mt-1">Welcome, <?= htmlspecialchars($admin_email) ?></p>
            </div>
            <button type="button" id="add-toggle"
                    class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold px-6 py-3 rounded-full">
                + Add Concert
            </button>
        </header>
 
        <?php if ($flash_status === 'ok'): ?>
            <div class="bg-green-100 text-green-800 text-sm rounded-xl px-4 py-3"><?= htmlspecialchars($flash_msg) ?></div>
        <?php elseif ($flash_status === 'error'): ?>
            <div class="bg-red-100 text-red-800 text-sm rounded-xl px-4 py-3"><?= htmlspecialchars($flash_msg) ?></div>
        <?php endif; ?>
 
        <!-- FORM TAMBAH KONSER -->
        <section id="add-form" class="bg-white rounded-2xl p-6 <?= $flash_status === 'error' ? '' : 'hidden' ?>">
            <h2 class="text-lg font-semibold mb-4">Add concert</h2>
 
            <form action="../../actions/concert_add_process.php" method="POST" enctype="multipart/form-data"
                  class="grid grid-cols-1 md:grid-cols-2 gap-4">
 
                <div>
                    <label for="title" class="block text-sm mb-1.5">Title</label>
                    <input type="text" id="title" name="title" required maxlength="255"
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                </div>
 
                <div>
                    <label for="artist" class="block text-sm mb-1.5">Artist</label>
                    <input type="text" id="artist" name="artist" required maxlength="255"
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                </div>
 
                <div>
                    <label for="venue" class="block text-sm mb-1.5">Venue</label>
                    <input type="text" id="venue" name="venue" required maxlength="255" placeholder="e.g. Jakarta"
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                </div>
 
                <div>
                    <label for="concert_date" class="block text-sm mb-1.5">Date and time</label>
                    <input type="datetime-local" id="concert_date" name="concert_date" required
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                </div>
 
                <div>
                    <label for="price" class="block text-sm mb-1.5">Ticket price (Rp)</label>
                    <input type="number" id="price" name="price" required min="0" step="1000" value="250000"
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                </div>
 
                <?php if ($has_genre_column): ?>
                <div>
                    <label for="genre_id" class="block text-sm mb-1.5">Genre</label>
                    <select id="genre_id" name="genre_id" required
                            class="w-full bg-[#F4F3FE] rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                        <option value="">-- choose genre --</option>
                        <?php foreach ($genre_options as $g): ?>
                            <option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['genre_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
 
                <div>
                    <label for="concert_status" class="block text-sm mb-1.5">Status</label>
                    <input type="text" id="concert_status" name="concert_status" list="status-options" value="on going" maxlength="50"
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                    <datalist id="status-options">
                        <option value="on going">
                        <option value="sold out">
                        <option value="finished">
                        <option value="cancelled">
                    </datalist>
                </div>
 
                <div>
                    <label for="img" class="block text-sm mb-1.5">Poster (JPG, PNG, WEBP, max 2 MB)</label>
                    <input type="file" id="img" name="img" accept="image/jpeg,image/png,image/webp"
                           class="w-full bg-[#F4F3FE] rounded-xl px-4 py-2 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-[#5835FF] file:text-white file:px-4 file:py-1.5 file:text-xs">
                </div>
 
                <div class="md:col-span-2">
                    <label for="concert_description" class="block text-sm mb-1.5">Description</label>
                    <textarea id="concert_description" name="concert_description" rows="3" maxlength="5000"
                              class="w-full bg-[#F4F3FE] rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]"></textarea>
                </div>
 
                <div class="md:col-span-2 flex items-center gap-3">
                    <button type="submit"
                            class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold rounded-full px-8 py-3">Save concert</button>
                    <button type="button" id="add-cancel" class="text-sm underline opacity-70 hover:opacity-100">Cancel</button>
                </div>
            </form>
        </section>
 
        <!-- KARTU RINGKASAN -->
        <section class="grid gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Total concerts</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $total_concerts ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Upcoming concerts</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $upcoming_concerts ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Seats available</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $available_seats ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Tickets sold</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $tickets_sold ?></div>
            </div>
        </section>
 
        <!-- TABEL KONSER -->
        <section class="bg-white rounded-2xl p-6 flex flex-col gap-4">
 
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">All concerts</h2>
 
                <form action="concerts.php" method="GET" class="flex items-center gap-2">
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search title, artist, venue"
                           class="w-56 bg-[#F4F3FE] text-sm rounded-full px-5 py-2.5 outline-none focus:ring-2 focus:ring-[#5835FF]">
                    <button type="submit"
                            class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold rounded-full px-5 py-2.5">Search</button>
                    <?php if ($q !== ''): ?>
                        <a href="concerts.php" class="text-sm underline opacity-70 hover:opacity-100">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
 
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm text-left">
                    <thead>
                        <tr class="border-b border-[#17143D]/15 opacity-60">
                            <th class="py-2 pr-3 font-semibold">Concert</th>
                            <th class="py-2 pr-3 font-semibold">Venue</th>
                            <th class="py-2 pr-3 font-semibold">Date</th>
                            <th class="py-2 pr-3 font-semibold">Price</th>
                            <th class="py-2 pr-3 font-semibold">Seats (free / total)</th>
                            <th class="py-2 pr-3 font-semibold">Sold</th>
                            <th class="py-2 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($concerts)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center opacity-60">
                                <?= $q !== '' ? 'Tidak ada konser yang cocok dengan pencarian.' : 'Belum ada data konser.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($concerts as $c): ?>
                        <tr class="border-b border-[#17143D]/10 last:border-0 align-middle">
 
                            <!-- Poster + judul + artis -->
                            <td class="py-3 pr-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 shrink-0 rounded-xl overflow-hidden bg-[#8F9BFB]/30">
                                        <?php if (!empty($c['img'])): ?>
                                            <img src="<?= htmlspecialchars($c['img']) ?>" alt=""
                                                 class="w-full h-full object-cover object-top">
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold truncate"><?= htmlspecialchars($c['title']) ?></p>
                                        <p class="text-xs opacity-65 truncate"><?= htmlspecialchars($c['artist']) ?></p>
                                    </div>
                                </div>
                            </td>
 
                            <td class="py-3 pr-3"><?= htmlspecialchars($c['venue']) ?></td>
 
                            <td class="py-3 pr-3 whitespace-nowrap">
                                <?= !empty($c['concert_date']) ? date('d M Y, H:i', strtotime($c['concert_date'])) : '-' ?>
                            </td>
 
                            <td class="py-3 pr-3 whitespace-nowrap"><?= rupiah($c['price']) ?></td>
 
                            <td class="py-3 pr-3 whitespace-nowrap">
                                <?php if ((int)$c['total_seats'] === 0): ?>
                                    <a href="seat_generate.php" class="text-[#5835FF] underline">Generate</a>
                                <?php else: ?>
                                    <?= (int)$c['free_seats'] ?> / <?= (int)$c['total_seats'] ?>
                                <?php endif; ?>
                            </td>
 
                            <td class="py-3 pr-3"><?= (int)$c['sold'] ?></td>
 
                            <td class="py-3">
                                <?php
                                    $is_past = !empty($c['concert_date']) && strtotime($c['concert_date']) < time();
                                    $label   = trim((string)($c['concert_status'] ?? '')) !== ''
                                             ? $c['concert_status']
                                             : ($is_past ? 'Finished' : 'Upcoming');
                                ?>
                                <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap
                                             <?= $is_past ? 'bg-gray-100 text-gray-600' : 'bg-[#5835FF]/10 text-[#5835FF]' ?>">
                                    <?= htmlspecialchars($label) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
 
    </main>
</div>
<script>
    (function () {
        var panel  = document.getElementById('add-form');
        var toggle = document.getElementById('add-toggle');
        var cancel = document.getElementById('add-cancel');
        if (!panel || !toggle) return;
 
        toggle.addEventListener('click', function () {
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        if (cancel) {
            cancel.addEventListener('click', function () { panel.classList.add('hidden'); });
        }
    })();
</script>
</body>
</html>
<?php mysqli_close($conn); ?>