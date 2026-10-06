<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
 
// ---------- KONSER TERDEKAT (untuk banner bawah) ----------
$result_near = mysqli_query($conn, "SELECT id, title, artist, venue, concert_date, concert_description, img
                                    FROM concerts WHERE concert_date >= NOW()
                                    ORDER BY concert_date ASC LIMIT 1");
$near = $result_near ? mysqli_fetch_assoc($result_near) : null;
 
// Info untuk navbar (header.php)
$halaman_aktif = 'about';
$header_search = false;
 
// Teks Benefits (ubah sesuai kebutuhan)
$benefits = [
    ['judul' => 'Pemesanan Mudah',   'teks' => 'Pesan tiket konser favoritmu hanya dalam beberapa langkah.'],
    ['judul' => 'Pilih Kursi Sendiri', 'teks' => 'Lihat kursi yang tersedia dan tentukan tempat dudukmu.'],
    ['judul' => 'Info Terbaru',      'teks' => 'Temukan jadwal dan detail konser terbaru dalam satu tempat.'],
];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased flex flex-col">
 
    <!-- NAVBAR -->
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-5 pt-8 flex-grow">
 
        <!-- ABOUT US -->
        <section class="mb-12">
            <h1 class="text-2xl sm:text-3xl font-medium text-center mb-6">About Us</h1>
 
            <!-- Kotak foto (sengaja dikosongkan; isi dengan <img> nanti) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 
                <!-- Kolom kiri -->
                <div class="flex flex-col gap-5">
                    <div class="bg-[#d9d9d9] rounded-[22px] h-56 md:h-72 flex items-center justify-center">
                        <span class="text-2xl sm:text-3xl font-bold text-black">Concert</span>
                    </div>
                    <div class="bg-[#d9d9d9] rounded-[22px] h-40 md:h-44 flex items-center justify-center">
                        <span class="text-2xl sm:text-3xl font-bold text-black">Contact</span>
                    </div>
                </div>
 
                <!-- Kolom kanan (turun sedikit agar tampak bertingkat seperti wireframe) -->
                <div class="flex flex-col gap-5 md:pt-6">
                    <div class="bg-[#d9d9d9] rounded-[22px] h-40 md:h-48 flex items-center justify-center">
                        <span class="text-2xl sm:text-3xl font-bold text-black">Company</span>
                    </div>
                    <div class="bg-[#d9d9d9] rounded-[22px] h-56 md:h-72 flex items-center justify-center">
                        <span class="text-2xl sm:text-3xl font-bold text-black">Concert</span>
                    </div>
                </div>
            </div>
        </section>
 
        <!-- BENEFITS -->
        <section class="mb-12">
            <h2 class="text-center text-lg mb-4">Benefits</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <?php foreach ($benefits as $b): ?>
                    <div class="bg-[#d9d9d9] rounded-lg px-5 py-8 text-center">
                        <h3 class="font-semibold mb-1"><?= htmlspecialchars($b['judul']) ?></h3>
                        <p class="text-sm text-gray-700"><?= htmlspecialchars($b['teks']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
 
    <!-- BANNER KONSER TERDEKAT -->
    <?php if ($near): ?>
    <section class="bg-[#5c5c5c] px-5 pt-5 pb-5">
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