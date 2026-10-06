<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
 
// ---------- GENRE ----------
$result_genres = mysqli_query($conn, "SELECT id, genre_name FROM genres ORDER BY id ASC");
$selected_genre = isset($_GET['genre_id']) ? (int) $_GET['genre_id'] : 0;
 
// ---------- KONSER (filter dibuat SEBELUM query dijalankan) ----------
if ($selected_genre > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, title, artist, genre_id, venue, concert_date, concert_description, img
                                   FROM concerts WHERE genre_id = ? ORDER BY id ASC");
    mysqli_stmt_bind_param($stmt, "i", $selected_genre);
    mysqli_stmt_execute($stmt);
    $result_concerts = mysqli_stmt_get_result($stmt);
} else {
    $result_concerts = mysqli_query($conn, "SELECT id, title, artist, genre_id, venue, concert_date, concert_description, img
                                            FROM concerts ORDER BY id ASC");
}
 
// ---------- KONSER TERDEKAT (untuk banner bawah) ----------
$result_near = mysqli_query($conn, "SELECT id, title, artist, venue, concert_date, concert_description, img
                                    FROM concerts WHERE concert_date >= NOW()
                                    ORDER BY concert_date ASC LIMIT 1");
$near = $result_near ? mysqli_fetch_assoc($result_near) : null;
 
if (!$result_concerts || !$result_genres) {
    die("Gagal mengambil data database: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventify</title>
    <!-- Tailwind CSS (INI yang sebelumnya hilang, sehingga tampilan berantakan) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased flex flex-col">
 
    <!-- NAVBAR -->
    <?php include "../../components/landing/header.php"; ?>
 
    <!-- KONTEN UTAMA -->
    <main class="w-full max-w-7xl mx-auto mt-8 px-5 flex-grow">
 
        <!-- Judul + Search -->
        <header class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 mb-4">
            <div>
                <p class="text-gray-600 text-sm">Concert Categories</p>
                <h1 class="text-3xl font-medium">Find your genres</h1>
            </div>
            <input type="text" id="searchInput" placeholder="Search concert..."
                   class="w-full sm:w-72 h-9 bg-[#b5b5b5] placeholder-gray-600 text-sm text-gray-900 rounded-full px-4 border-0 focus:ring-2 focus:ring-gray-500 focus:outline-none">
        </header>
 
        <!-- Genre capsule -->
        <section class="flex flex-wrap gap-2.5 mb-8">
            <a href="?" class="px-4 py-1.5 rounded-full text-[11px] transition <?= $selected_genre === 0 ? 'bg-[#777] text-white' : 'bg-[#dcdcdc] text-gray-800 hover:bg-[#777] hover:text-white' ?>">All Genre</a>
            <?php while ($genre = mysqli_fetch_assoc($result_genres)) { ?>
                <a href="?genre_id=<?= $genre['id'] ?>"
                   class="px-4 py-1.5 rounded-full text-[11px] transition <?= $selected_genre === (int)$genre['id'] ? 'bg-[#777] text-white' : 'bg-[#dcdcdc] text-gray-800 hover:bg-[#777] hover:text-white' ?>">
                    <?= htmlspecialchars($genre['genre_name']) ?>
                </a>
            <?php } ?>
        </section>
 
        <!-- Grid kartu konser -->
        <section id="concertGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-12">
            <?php if (mysqli_num_rows($result_concerts) > 0) { ?>
                <?php while ($c = mysqli_fetch_assoc($result_concerts)) { ?>
                    <a href="concertdetail.php?id=<?= (int)$c['id'] ?>"
                       class="concert-card block rounded-[22px] overflow-hidden flex flex-col shadow-sm hover:shadow-lg hover:-translate-y-1 transition"
                       data-search="<?= htmlspecialchars(strtolower($c['title'] . ' ' . $c['artist'])) ?>">
 
                        <!-- Bagian atas (abu terang): gambar + judul -->
                        <div class="bg-[#d9d9d9] flex-grow flex flex-col">
                            <div class="w-full h-56 overflow-hidden">
                                <?php if (!empty($c['img'])): ?>
                                    <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['title']) ?>"
                                         class="w-full h-full object-cover object-top">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-500 text-sm">No Image</div>
                                <?php endif; ?>
                            </div>
                            <div class="px-4 py-4 text-center">
                                <h3 class="text-lg font-semibold text-gray-900 leading-tight"><?= htmlspecialchars($c['title']) ?></h3>
                                <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($c['artist']) ?></p>
                            </div>
                        </div>
 
                        <!-- Bagian bawah (abu gelap): deskripsi -->
                        <div class="bg-[#8f8f8f] text-gray-900 px-4 py-4 text-center h-24 flex flex-col justify-center">
                            <p class="text-sm line-clamp-2"><?= htmlspecialchars($c['concert_description']) ?></p>
                            <span class="text-xs mt-1 opacity-80">📍 <?= htmlspecialchars($c['venue']) ?></span>
                        </div>
                    </a>
                <?php } ?>
            <?php } else { ?>
                <p class="col-span-full text-center text-gray-500 py-10">Tidak ada konser yang tersedia untuk genre ini.</p>
            <?php } ?>
        </section>
    </main>
 
    <!-- BANNER KONSER TERDEKAT -->
    <?php if ($near): ?>
    <section class="bg-[#5c5c5c] px-5 pt-5 pb-3">
        <div class="max-w-7xl mx-auto bg-[#d9d9d9] rounded-[22px] overflow-hidden flex flex-col md:flex-row">
            <div class="md:w-1/4 h-40 md:h-auto bg-[#8f8f8f]">
                <?php if (!empty($near['img'])): ?>
                    <img src="<?= htmlspecialchars($near['img']) ?>" alt="<?= htmlspecialchars($near['title']) ?>" class="w-full h-full object-cover object-top">
                <?php endif; ?>
            </div>
            <div class="flex-1 flex flex-col justify-center text-center px-6 py-5">
                <h3 class="text-xl font-semibold"><?= htmlspecialchars($near['title']) ?> - <?= htmlspecialchars($near['artist']) ?></h3>
                <p class="text-sm text-gray-700 mt-1 line-clamp-2"><?= htmlspecialchars($near['concert_description']) ?></p>
                <p class="text-xs text-gray-600 mt-1">📍 <?= htmlspecialchars($near['venue']) ?></p>
            </div>
            <div class="hidden md:block w-px bg-gray-500 my-5"></div>
            <div class="md:w-1/4 flex flex-col items-center justify-center gap-3 px-6 py-5">
                <p class="text-2xl font-medium" id="countdown" data-date="<?= date('c', strtotime($near['concert_date'])) ?>">--</p>
                <a href="ticket.php?id=<?= $near['id'] ?>" class="bg-[#8f8f8f] hover:bg-[#777] text-white text-sm rounded-full px-10 py-2 transition">Get Ticket</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
 
    <!-- FOOTER -->
    <?php include "../../components/landing/footer.php"; ?>
 
    <script>
        // Search: filter kartu di sisi browser
        document.getElementById('searchInput').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.concert-card').forEach(card => {
                card.style.display = card.dataset.search.includes(q) ? '' : 'none';
            });
        });
 
        // Countdown
        const cd = document.getElementById('countdown');
        if (cd) {
            const target = new Date(cd.dataset.date).getTime();
            const tick = () => {
                const d = target - Date.now();
                if (d <= 0) { cd.textContent = 'Sedang berlangsung'; return; }
                const day = Math.floor(d / 864e5);
                const h = Math.floor(d % 864e5 / 36e5);
                const m = Math.floor(d % 36e5 / 6e4);
                const s = Math.floor(d % 6e4 / 1e3);
                cd.textContent = `${day}d ${h}h ${m}m ${s}s`;
            };
            tick(); setInterval(tick, 1000);
        }
    </script>
</body>
</html>
<?php
    mysqli_close($conn);
?>