<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_admin.php";
include "../../repo/concertdetail.php";
 
// Helper: ambil satu angka dari query COUNT(*)
function count_rows(mysqli $conn, string $sql): int {
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($res);
    return (int)($row[0] ?? 0);
}
 
// ---------- CEK: apakah tabel concerts punya kolom genre_id? ----------
// Kalau belum ada, daftar genre tetap tampil, hanya jumlah konser per genre yang disembunyikan.
$has_genre_column = false;
$res = mysqli_query($conn, "SHOW COLUMNS FROM concerts LIKE 'genre_id'");
if ($res && mysqli_num_rows($res) > 0) {
    $has_genre_column = true;
}

// ---------- PENCARIAN ----------
$q = trim($_GET['q'] ?? '');
$genres = AdminCreateGenre::Creategenres($conn, $has_genre_column,$q);
 
// ---------- RINGKASAN ----------
$total_genres   = count_rows($conn, "SELECT COUNT(*) FROM genres");
$total_concerts = count_rows($conn, "SELECT COUNT(*) FROM concerts");
 
$genres_in_use = 0;
$top_genre     = '-';
$max_concerts  = 0;
foreach ($genres as $g) {
    $n = (int)$g['concert_count'];
    if ($n > 0) {
        $genres_in_use++;
    }
    if ($n > $max_concerts) {
        $max_concerts = $n;
        $top_genre    = $g['genre_name'];
    }
}
// Ringkasan "genre dipakai" dan "terbanyak" dihitung dari semua genre, bukan hasil pencarian
if ($q !== '' && $has_genre_column) {
    $genres_in_use = count_rows($conn, "SELECT COUNT(DISTINCT genre_id) FROM concerts WHERE genre_id IS NOT NULL");
    $res = mysqli_query($conn, "SELECT g.genre_name FROM genres g JOIN concerts c ON c.genre_id = g.id
                                GROUP BY g.id ORDER BY COUNT(*) DESC LIMIT 1");
    $top_row   = $res ? mysqli_fetch_assoc($res) : null;
    $top_genre = $top_row['genre_name'] ?? '-';
}
 
$flash_status = $_GET['status'] ?? '';
$flash_msg    = $_GET['msg'] ?? '';
 
$admin_email = $_SESSION["email"] ?? "admin";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genres - Admin Eventify</title>
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
            <a href="concerts.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Concerts</a>
            <a href="genres.php" class="px-4 py-3 rounded-xl bg-[#5835FF] text-white font-semibold">Genres</a>
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
                <h1 class="text-3xl font-bold">Genres</h1>
                <p class="text-sm opacity-65 mt-1">Welcome, <?= htmlspecialchars($admin_email) ?></p>
            </div>
            <a href="concerts.php"
               class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold px-6 py-3 rounded-full">
                View Concerts
            </a>
        </header>
 
        <?php if (!$has_genre_column): ?>
            <div class="bg-amber-100 text-amber-800 text-sm rounded-xl px-4 py-3">
                Kolom <code>genre_id</code> tidak ditemukan di tabel <code>concerts</code>, jadi jumlah konser
                per genre belum bisa ditampilkan. Daftar genre tetap tampil di bawah.
            </div>
        <?php endif; ?>
 
        <!-- KARTU RINGKASAN -->
        <section class="grid gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Total genres</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $total_genres ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Total concerts</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $total_concerts ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Genres in use</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $has_genre_column ? $genres_in_use : '-' ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Most concerts</div>
                <div class="text-2xl xl:text-3xl font-bold text-[#5835FF] mt-2 truncate">
                    <?= $has_genre_column ? htmlspecialchars($top_genre) : '-' ?>
                </div>
            </div>
        </section>
 
        <?php if ($flash_status === 'ok'): ?>
            <div class="bg-green-100 text-green-800 text-sm rounded-xl px-4 py-3"><?= htmlspecialchars($flash_msg) ?></div>
        <?php elseif ($flash_status === 'error'): ?>
            <div class="bg-red-100 text-red-800 text-sm rounded-xl px-4 py-3"><?= htmlspecialchars($flash_msg) ?></div>
        <?php endif; ?>
 
        <!-- TAMBAH GENRE -->
        <section class="bg-white rounded-2xl p-6">
            <h2 class="text-lg font-semibold mb-3">Add genre</h2>
            <form action="../../actions/genre_add_process.php" method="POST" class="flex flex-wrap items-center gap-2">
                <input type="text" name="genre_name" required maxlength="50" placeholder="e.g. K-Pop"
                       class="w-64 bg-[#F4F3FE] text-sm rounded-full px-5 py-2.5 outline-none focus:ring-2 focus:ring-[#5835FF]">
                <button type="submit"
                        class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold rounded-full px-6 py-2.5">Add genre</button>
            </form>
        </section>
 
        <!-- TABEL GENRE -->
        <section class="bg-white rounded-2xl p-6 flex flex-col gap-4">
 
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">All genres</h2>
 
                <form action="genres.php" method="GET" class="flex items-center gap-2">
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search genre"
                           class="w-48 bg-[#F4F3FE] text-sm rounded-full px-5 py-2.5 outline-none focus:ring-2 focus:ring-[#5835FF]">
                    <button type="submit"
                            class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold rounded-full px-5 py-2.5">Search</button>
                    <?php if ($q !== ''): ?>
                        <a href="genres.php" class="text-sm underline opacity-70 hover:opacity-100">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
 
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-sm text-left">
                    <thead>
                        <tr class="border-b border-[#17143D]/15 opacity-60">
                            <th class="py-2 pr-3 font-semibold">ID</th>
                            <th class="py-2 pr-3 font-semibold">Genre</th>
                            <th class="py-2 pr-3 font-semibold">Concerts</th>
                            <th class="py-2 pr-3 font-semibold">Upcoming</th>
                            <th class="py-2 pr-3 font-semibold">Tickets sold</th>
                            <th class="py-2 font-semibold">On website</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($genres)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center opacity-60">
                                <?= $q !== '' ? 'Tidak ada genre yang cocok dengan pencarian.' : 'Belum ada data genre.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($genres as $g):
                            $count = (int)$g['concert_count'];
                            $share = $max_concerts > 0 ? round($count / $max_concerts * 100) : 0;
                        ?>
                        <tr class="border-b border-[#17143D]/10 last:border-0">
                            <td class="py-3 pr-3 opacity-60"><?= (int)$g['id'] ?></td>
 
                            <td class="py-3 pr-3 font-semibold"><?= htmlspecialchars($g['genre_name']) ?></td>
 
                            <td class="py-3 pr-3">
                                <?php if ($has_genre_column): ?>
                                    <div class="flex items-center gap-3">
                                        <span class="w-6"><?= $count ?></span>
                                        <div class="hidden sm:block w-28 h-2 rounded-full bg-[#F4F3FE] overflow-hidden">
                                            <div class="h-2 rounded-full bg-[#5835FF]" style="width: <?= $share ?>%"></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
 
                            <td class="py-3 pr-3"><?= $has_genre_column ? (int)$g['upcoming_count'] : '-' ?></td>
                            <td class="py-3 pr-3"><?= $has_genre_column ? (int)$g['sold_count'] : '-' ?></td>
 
                            <td class="py-3">
                                <a href="../user/home_page_user.php?genre=<?= (int)$g['id'] ?>" target="_blank"
                                   class="text-[#5835FF] underline">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
 
            <p class="text-sm opacity-65">Showing <?= count($genres) ?> of <?= $total_genres ?> genres</p>
        </section>
 
    </main>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
 




















