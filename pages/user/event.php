<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
 
// ---------- HELPER ----------
function ambil_konser($conn, $sql) {
    $hasil = mysqli_query($conn, $sql);
    if (!$hasil) {
        die("Gagal mengambil data database: " . mysqli_error($conn));
    }
    $data = [];
    while ($row = mysqli_fetch_assoc($hasil)) {
        $data[] = $row;
    }
    return $data;
}
 
function kartu_konser(array $c) { ?>
    <a href="concertdetail.php?id=<?= (int)$c['id'] ?>"
       class="concert-card rounded-[22px] overflow-hidden flex flex-col shadow-sm hover:shadow-lg hover:-translate-y-1 transition"
       data-search="<?= htmlspecialchars(strtolower($c['title'] . ' ' . $c['artist'])) ?>">
 
        <!-- Bagian atas (abu terang): gambar + judul -->
        <div class="bg-[#d9d9d9] flex-grow flex flex-col">
            <div class="w-full h-44 overflow-hidden">
                <?php if (!empty($c['img'])): ?>
                    <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['title']) ?>"
                         class="w-full h-full object-cover object-top">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-500 text-sm">No Image</div>
                <?php endif; ?>
            </div>
            <div class="px-4 py-3 text-center">
                <h3 class="text-base font-semibold text-gray-900 leading-tight"><?= htmlspecialchars($c['title']) ?></h3>
                <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($c['artist']) ?></p>
            </div>
        </div>
 
        <!-- Bagian bawah (abu gelap): deskripsi -->
        <div class="bg-[#8f8f8f] text-gray-900 px-4 py-3 text-center h-20 flex flex-col justify-center">
            <p class="text-xs line-clamp-2"><?= htmlspecialchars($c['concert_description']) ?></p>
            <span class="text-[11px] mt-1 opacity-80">📍 <?= htmlspecialchars($c['venue']) ?></span>
        </div>
    </a>
<?php }
 
function section_konser($judul, array $daftar) { ?>
    <section class="event-section mb-10">
        <h2 class="text-2xl font-medium mb-4"><?= htmlspecialchars($judul) ?></h2>
        <?php if (empty($daftar)): ?>
            <p class="text-gray-500 text-sm py-6">Belum ada konser untuk kategori ini.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php foreach ($daftar as $c) { kartu_konser($c); } ?>
            </div>
        <?php endif; ?>
    </section>
<?php }
 
// ---------- DATA ----------
$kolom = "c.id, c.title, c.artist, c.venue, c.concert_date, c.concert_description, c.img";
 
// Recommendation: konser yang akan datang, paling dekat dulu
$recommendation = ambil_konser($conn, "SELECT $kolom FROM concerts c
                                       WHERE c.concert_date >= NOW()
                                       ORDER BY c.concert_date ASC LIMIT 4");
 
// Favorite: konser yang terakhir ditambahkan
$favorite = ambil_konser($conn, "SELECT $kolom FROM concerts c
                                 ORDER BY c.id DESC LIMIT 4");
 
// Hots: konser dengan kursi terjual (tidak Available) paling banyak
$hots = ambil_konser($conn, "SELECT $kolom, COUNT(s.id) AS terjual
                             FROM concerts c
                             LEFT JOIN seats s ON s.concert_id = c.id AND s.seat_status <> 'Available'
                             GROUP BY c.id
                             ORDER BY terjual DESC, c.concert_date ASC LIMIT 4");
 
// Banner atas: konser paling hot
$hero = $hots[0] ?? null;
 
// Banner bawah: konser terdekat (near due)
$near_list = ambil_konser($conn, "SELECT $kolom FROM concerts c
                                  WHERE c.concert_date >= NOW()
                                  ORDER BY c.concert_date ASC LIMIT 1");
$near = $near_list[0] ?? null;
 
// Info untuk navbar (header.php)
$halaman_aktif = 'event';
$header_search = false; // search ada di halaman ini (pil kanan atas)
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event - Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased flex flex-col">
 
    <!-- NAVBAR -->
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-5 pt-5 flex-grow">
 
        <!-- Search (pil abu kanan atas) -->
        <div class="flex justify-end mb-4">
            <input type="text" id="searchInput" placeholder="Search event..."
                   class="w-full sm:w-64 h-9 bg-[#b5b5b5] placeholder-gray-600 text-sm text-gray-900 rounded-full px-4 border-0 focus:ring-2 focus:ring-gray-500 focus:outline-none">
        </div>
 
        <!-- Banner konser -->
        <?php if ($hero): ?>
            <a href="concertdetail.php?id=<?= (int)$hero['id'] ?>"
               class="block relative bg-[#d9d9d9] rounded-xl overflow-hidden h-40 sm:h-56 mb-8 group">
                <?php if (!empty($hero['img'])): ?>
                    <img src="<?= htmlspecialchars($hero['img']) ?>" alt="<?= htmlspecialchars($hero['title']) ?>"
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-300">
                <?php endif; ?>
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-5 py-4">
                    <p class="text-white text-lg sm:text-xl font-semibold"><?= htmlspecialchars($hero['title']) ?></p>
                    <p class="text-gray-200 text-sm"><?= htmlspecialchars($hero['artist']) ?></p>
                </div>
            </a>
        <?php else: ?>
            <div class="bg-[#d9d9d9] rounded-xl h-40 sm:h-56 mb-8 flex items-center justify-center text-gray-600">
                Belum ada konser
            </div>
        <?php endif; ?>
 
        <!-- Section konser -->
        <?php section_konser('Recommendation', $recommendation); ?>
        <?php section_konser('Favorite', $favorite); ?>
        <?php section_konser('Hots', $hots); ?>
 
        <p id="noResult" class="hidden text-center text-gray-500 py-10">Tidak ada konser yang cocok dengan pencarian.</p>
    </main>
 
    <!-- BANNER KONSER TERDEKAT -->
    <?php if ($near): ?>
    <section class="bg-[#5c5c5c] px-5 pt-5 pb-5 mt-6">
        <div class="max-w-7xl mx-auto bg-[#d9d9d9] rounded-[22px] overflow-hidden flex flex-col md:flex-row">
            <div class="md:w-1/4 h-40 md:h-auto bg-[#8f8f8f] flex items-center justify-center">
                <?php if (!empty($near['img'])): ?>
                    <img src="<?= htmlspecialchars($near['img']) ?>" alt="<?= htmlspecialchars($near['title']) ?>"
                         class="w-full h-full object-cover object-top">
                <?php else: ?>
                    <span class="text-white text-lg">Concert Image</span>
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
                <a href="ticket.php?id=<?= (int)$near['id'] ?>"
                   class="bg-[#8f8f8f] hover:bg-[#777] text-white text-sm rounded-full px-10 py-2 transition">Get Ticket</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
 
    <!-- FOOTER -->
    <?php include "../../components/landing/footer.php"; ?>
 
    <script>
        // Search: filter kartu di semua section
        const searchInput = document.getElementById('searchInput');
        const noResult    = document.getElementById('noResult');
 
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            let totalTampil = 0;
 
            document.querySelectorAll('.event-section').forEach(section => {
                let tampil = 0;
                section.querySelectorAll('.concert-card').forEach(card => {
                    const cocok = card.dataset.search.includes(q);
                    card.style.display = cocok ? '' : 'none';
                    if (cocok) tampil++;
                });
                // sembunyikan section yang kartunya habis tersaring
                section.style.display = (tampil === 0 && q !== '') ? 'none' : '';
                totalTampil += tampil;
            });
 
            noResult.classList.toggle('hidden', totalTampil > 0 || q === '');
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
<?php mysqli_close($conn); ?>
 