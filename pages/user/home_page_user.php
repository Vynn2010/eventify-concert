<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
// include "../../actions/cek_login_user.php"; // aktifkan jika halaman ini harus login
include "../../repo/homepage_user_repositories.php";
 
if (!function_exists('rupiah')) {
    function rupiah($n) {
        return 'Rp ' . number_format((float)$n, 0, ',', '.');
    }
}
 
// Buat URL halaman ini dengan filter yang sedang aktif, lalu timpa sebagian parameter
function build_filter_url(array $override): string {
    $params = array_merge($_GET, $override);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null && $v !== '0' && $v !== 0);
    return 'home_page_user.php' . ($params ? '?' . http_build_query($params) : '');
}
 
// ---------- FILTER ----------
$q        = trim($_GET['q'] ?? '');
$min_date = $_GET['min_date'] ?? '';
$max_date = $_GET['max_date'] ?? '';
$genre    = (int)($_GET['genre'] ?? 0);
$location = trim($_GET['location'] ?? '');
 
$where  = [];
$types  = '';
$params = [];
 
if ($q !== '') {
    $where[]  = "(title LIKE ? OR artist LIKE ?)";
    $types   .= 'ss';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($min_date !== '') {
    $where[]  = "DATE(concert_date) >= ?";
    $types   .= 's';
    $params[] = $min_date;
}
if ($max_date !== '') {
    $where[]  = "DATE(concert_date) <= ?";
    $types   .= 's';
    $params[] = $max_date;
}
if ($genre > 0) {
    $where[]  = "genre_id = ?";                 // SESUAIKAN: nama kolom genre di tabel concerts
    $types   .= 'i';
    $params[] = $genre;
}
if ($location !== '') {
    $where[]  = "venue LIKE ?";
    $types   .= 's';
    $params[] = "%$location%";
}
 
$concerts = HomePageUser::findUserHomePage($conn, $where, $types, $params);

// ---------- DAFTAR GENRE ----------
$genres = HomePageUser::findGenres($conn);
 
// ---------- BANNER: konser terdekat yang belum lewat ----------
$next_concert = HomePageUser::CreateBannerHomePage($conn);
 
$header_search = false; // pencarian ada di dalam halaman ini; ubah ke true jika ingin search di navbar
$filter_keys   = ['min_date', 'max_date', 'genre', 'location'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased flex flex-col">
 
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-5 py-8 flex-grow flex flex-col gap-8">
 
        <!-- JUDUL + SEARCH -->
        <section class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl sm:text-3xl font-bold">Find your genres</h1>
 
            <form action="home_page_user.php" method="GET" class="w-full sm:w-72">
                <?php foreach ($filter_keys as $key): ?>
                    <?php if (!empty($_GET[$key])): ?>
                        <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($_GET[$key]) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search event"
                       class="w-full bg-[#d9d9d9] text-sm rounded-full px-5 py-3 outline-none focus:ring-2 focus:ring-gray-400">
            </form>
        </section>
 
        <!-- TOMBOL GENRE -->
        <section class="flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars(build_filter_url(['genre' => ''])) ?>"
               class="text-sm rounded-full px-5 py-2 transition <?= $genre === 0 ? 'bg-[#5c5c5c] text-white' : 'bg-[#d9d9d9] hover:bg-[#c4c4c4]' ?>">All</a>
            <?php foreach ($genres as $g): ?>
                <a href="<?= htmlspecialchars(build_filter_url(['genre' => (int)$g['id']])) ?>"
                   class="text-sm rounded-full px-5 py-2 transition <?= $genre === (int)$g['id'] ? 'bg-[#5c5c5c] text-white' : 'bg-[#d9d9d9] hover:bg-[#c4c4c4]' ?>">
                    <?= htmlspecialchars($g['genre_name']) ?>
                </a>
            <?php endforeach; ?>
        </section>
 
        <!-- KARTU KONSER (3 kolom) + ADVANCED FILTER (kanan) -->
        <section class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6 items-start">
 
            <!-- Kartu konser -->
            <div>
                <?php if (empty($concerts)): ?>
                    <p class="text-gray-600 py-10 text-center">Tidak ada konser yang cocok dengan filter.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                        <?php foreach ($concerts as $c): ?>
                            <a href="concertdetail.php?id=<?= (int)$c['id'] ?>"
                               class="bg-[#d9d9d9] rounded-[24px] overflow-hidden flex flex-col hover:shadow-md transition">
                                <div class="h-56 shrink-0 overflow-hidden bg-[#bdbdbd]">
                                    <?php if (!empty($c['img'])): ?>
                                        <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['title']) ?>"
                                             class="w-full h-full object-cover object-top">
                                    <?php endif; ?>
                                </div>
                                <div class="p-4 flex flex-col gap-1">
                                    <p class="font-bold truncate"><?= htmlspecialchars($c['title']) ?></p>
                                    <p class="text-sm text-gray-700 truncate"><?= htmlspecialchars($c['artist']) ?></p>
                                    <p class="text-xs text-gray-600 truncate">
                                        <?= htmlspecialchars($c['venue']) ?> ·
                                        <?= !empty($c['concert_date']) ? date('d M Y', strtotime($c['concert_date'])) : '-' ?>
                                    </p>
                                    <p class="text-sm font-semibold mt-1"><?= rupiah($c['price']) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
 
            <!-- Advanced Filter -->
            <form action="home_page_user.php" method="GET"
                  class="bg-[#d9d9d9] rounded-[28px] p-6 flex flex-col gap-4 lg:sticky lg:top-24">
                <h2 class="text-center font-bold text-lg">Advanced Filter</h2>
 
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
 
                <div>
                    <label for="min_date" class="block text-sm mb-2">Min date</label>
                    <input type="date" id="min_date" name="min_date" value="<?= htmlspecialchars($min_date) ?>"
                           class="w-full bg-[#b3b3b3] rounded-full px-5 py-3 text-sm outline-none">
                </div>
 
                <div>
                    <label for="max_date" class="block text-sm mb-2">Max date</label>
                    <input type="date" id="max_date" name="max_date" value="<?= htmlspecialchars($max_date) ?>"
                           class="w-full bg-[#b3b3b3] rounded-full px-5 py-3 text-sm outline-none">
                </div>
 
                <div>
                    <label for="location" class="block text-sm mb-2">Location</label>
                    <input type="text" id="location" name="location" placeholder="e.g. Jakarta"
                           value="<?= htmlspecialchars($location) ?>"
                           class="w-full bg-[#b3b3b3] rounded-full px-5 py-3 text-sm placeholder-gray-600 outline-none">
                </div>
 
                <div>
                    <label for="genre" class="block text-sm mb-2">Genre</label>
                    <select id="genre" name="genre"
                            class="w-full bg-[#b3b3b3] rounded-full px-5 py-3 text-sm outline-none">
                        <option value="0">All Genre</option>
                        <?php foreach ($genres as $g): ?>
                            <option value="<?= (int)$g['id'] ?>" <?= $genre === (int)$g['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['genre_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
 
                <div class="grid grid-cols-2 gap-3">
                    <button type="submit"
                            class="bg-[#6b6b6b] hover:bg-[#555] text-white text-sm rounded-full py-3 transition">Apply</button>
                    <a href="home_page_user.php"
                       class="text-center bg-[#b3b3b3] hover:bg-[#a0a0a0] text-sm rounded-full py-3 transition">Reset</a>
                </div>
            </form>
        </section>
 
        <!-- BANNER: konser mendekati tanggal -->
        <?php if ($next_concert): ?>
        <section class="bg-[#d9d9d9] rounded-[28px] overflow-hidden grid grid-cols-1 md:grid-cols-[300px_1fr] md:min-h-72">
            <div class="relative h-56 md:h-auto bg-[#bdbdbd]">
                <?php if (!empty($next_concert['img'])): ?>
                    <img src="<?= htmlspecialchars($next_concert['img']) ?>" alt="<?= htmlspecialchars($next_concert['title']) ?>"
                         class="absolute inset-0 w-full h-full object-cover object-top">
                <?php endif; ?>
            </div>
            <div class="p-6 sm:p-8 flex flex-col gap-4 justify-center">
                <div>
                    <p class="text-sm text-gray-600">Coming soon</p>
                    <h2 class="text-2xl font-bold"><?= htmlspecialchars($next_concert['title']) ?></h2>
                    <p class="text-sm text-gray-700 mt-1">
                        <?= htmlspecialchars($next_concert['artist']) ?> ·
                        <?= htmlspecialchars($next_concert['venue']) ?>
                    </p>
                </div>
 
                <?php if (!empty($next_concert['concert_description'])): ?>
                    <p class="text-sm leading-relaxed text-gray-700 line-clamp-3">
                        <?= htmlspecialchars($next_concert['concert_description']) ?>
                    </p>
                <?php endif; ?>
 
                <!-- Countdown -->
                <div id="countdown" class="flex gap-3 text-center"
                     data-target="<?= date('c', strtotime($next_concert['concert_date'])) ?>">
                    <?php foreach (['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Min', 'seconds' => 'Sec'] as $id => $label): ?>
                        <div class="bg-[#5c5c5c] text-white rounded-2xl px-4 py-3 min-w-16">
                            <div id="cd-<?= $id ?>" class="text-xl font-bold">--</div>
                            <div class="text-[11px] opacity-80"><?= $label ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
 
                <div>
                    <a href="seat.php?id=<?= (int)$next_concert['id'] ?>"
                       class="inline-block bg-[#5c5c5c] hover:bg-[#444] text-white text-sm font-semibold rounded-full px-8 py-3 transition">
                        Get Ticket
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
 
    </main>
 
    <?php include "../../components/landing/footer.php"; ?>
 
    <script>
    (function () {
        var box = document.getElementById('countdown');
        if (!box) return;
        var target = new Date(box.dataset.target).getTime();
 
        function pad(n) { return String(n).padStart(2, '0'); }
 
        function tick() {
            var diff = Math.max(0, target - Date.now());
            var s = Math.floor(diff / 1000);
            document.getElementById('cd-days').textContent    = pad(Math.floor(s / 86400));
            document.getElementById('cd-hours').textContent   = pad(Math.floor(s % 86400 / 3600));
            document.getElementById('cd-minutes').textContent = pad(Math.floor(s % 3600 / 60));
            document.getElementById('cd-seconds').textContent = pad(s % 60);
        }
        tick();
        setInterval(tick, 1000);
    })();
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>