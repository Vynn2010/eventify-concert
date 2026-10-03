<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
$id= $_GET['id'];
$sql = "SELECT * FROM concerts WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM concerts WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$konser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$result) {
    die("Gagal : " . mysqli_error($conn));
}
$row = mysqli_fetch_assoc($result);

$halaman_event = "home_page_user.php";
$header_search = true; // navbar menampilkan kolom "Search event"

if (!$konser) { http_response_code(404); exit('Konser tidak ditemukan'); }

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $row ? htmlspecialchars($row['title']) . ' - Eventify' : 'Konser tidak ditemukan - Eventify' ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased">
 
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="max-w-7xl mx-auto px-4 sm:px-5 py-8 sm:py-10">
 
            <!-- Tombol Back -->
        <a href="<?= htmlspecialchars($halaman_event) ?>"
        aria-label="Kembali ke daftar konser"
        class="inline-flex items-center justify-center w-10 h-10 mb-6 rounded-full bg-[#6b6b6b] hover:bg-[#555] text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        
        <?php if (!$row): ?>
            <!-- Konser tidak ditemukan -->
            <div class="text-center py-20">
                <p class="text-lg text-gray-600 mb-4">Konser tidak ditemukan.</p>
                <a href="<?= htmlspecialchars($halaman_event) ?>" class="inline-block bg-[#6b6b6b] hover:bg-[#555] text-white rounded-full px-8 py-2.5 text-sm transition">Kembali ke daftar konser</a>
            </div>
    
        <?php else: ?>
    
        <!-- BAGIAN ATAS: gambar + info singkat -->
        <section class="flex flex-col sm:flex-row gap-5 sm:gap-6 mb-8">
 
            <!-- GAMBAR -->
            <div class="w-full sm:w-64 lg:w-72 h-64 sm:h-56 shrink-0 bg-[#d9d9d9] rounded-[28px] overflow-hidden flex items-center justify-center">
                <?php if (!empty($row['img'])): ?>
                    <img src="<?= htmlspecialchars($row['img']) ?>" alt="<?= htmlspecialchars($row['title']) ?>"
                         class="w-full h-full object-cover object-top">
                <?php else: ?>
                    <span class="text-xl font-bold text-black">GAMBAR</span>
                <?php endif; ?>
            </div>
 
            <!-- INFO (pil-pil abu) -->
            <div class="flex flex-col items-start gap-3 min-w-0">
                <!-- Genre -->
                <span class="bg-[#d9d9d9] text-gray-700 text-xs rounded-full px-5 py-1">
                    <?= htmlspecialchars($row['genre_name'] ?? 'Genre') ?>
                </span>
 
                <!-- Judul -->
                <h1 class="bg-[#d9d9d9] text-black text-xl sm:text-2xl font-bold rounded-full px-6 py-2 max-w-full break-words">
                    <?= htmlspecialchars($row['title']) ?>
                </h1>
 
                <!-- Artis + Tanggal -->
                <div class="flex flex-wrap items-center gap-3">
                    <span class="bg-[#d9d9d9] text-gray-800 text-sm rounded-full px-6 py-2">
                        🎤 <?= htmlspecialchars($row['artist']) ?>
                    </span>
                    <span class="bg-[#d9d9d9] text-gray-800 text-sm rounded-full px-6 py-2">
                        🗓 <?= !empty($row['concert_date']) ? date('d M Y, H:i', strtotime($row['concert_date'])) : '-' ?>
                    </span>
                </div>
 
                <!-- Venue -->
                <span class="bg-[#6b6b6b] text-white text-sm rounded-full px-6 py-2">
                    📍 <?= htmlspecialchars($row['venue']) ?>
                </span>
            </div>
        </section>
 
        <!-- BAGIAN BAWAH: deskripsi, info, tombol tiket -->
        <section class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-5">
 
            <div class="flex flex-col gap-5">
                <!-- Deskripsi -->
                <div class="bg-[#6b6b6b] text-white rounded-2xl p-5 sm:p-6 min-h-32">
                    <h2 class="font-semibold mb-2">Description</h2>
                    <p class="text-sm leading-relaxed text-gray-100">
                        <?= nl2br(htmlspecialchars($row['concert_description'])) ?>
                    </p>
                </div>
 
                <!-- Informasi tambahan -->
                <div class="bg-[#6b6b6b] text-white rounded-2xl p-5 sm:p-6 min-h-32">
                    <h2 class="font-semibold mb-2">Event Info</h2>
                    <dl class="text-sm grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-gray-100">
                        <dt class="opacity-70">Artist</dt><dd><?= htmlspecialchars($row['artist']) ?></dd>
                        <dt class="opacity-70">Venue</dt><dd><?= htmlspecialchars($row['venue']) ?></dd>
                        <dt class="opacity-70">Date</dt><dd><?= !empty($row['concert_date']) ? date('d M Y, H:i', strtotime($row['concert_date'])) : '-' ?></dd>
                        <?php if (isset($row['concert_status']) && $row['concert_status'] !== ''): ?>
                            <dt class="opacity-70">Status</dt><dd><?= htmlspecialchars($row['concert_status']) ?></dd>
                        <?php endif; ?>
                        <dt class="opacity-70">Price</dt><dd>Rp <?= number_format((float)$row['price'], 0, ',', '.') ?></dd>
                    </dl>
                </div>
            </div>
 
            <!-- Tombol Get Ticket (kanan bawah) -->
            <div class="lg:self-end">
                <a href="seat.php?id=<?= (int) $row['id'] ?>"
                   class="block text-center bg-[#6b6b6b] hover:bg-[#555] text-white text-lg font-semibold rounded-2xl py-5 transition">
                    Get Ticket
                </a>
            </div>
        </section>
 
    <?php endif; ?>
    </main>
 
</body>
</html>
<?php
    mysqli_close($conn);
?>