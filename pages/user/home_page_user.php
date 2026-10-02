<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";

$sql_genres = "SELECT id,genre_name FROM genres ORDER BY id ASC";
$result_genres = mysqli_query($conn, $sql_genres);
$selected_genre = isset($_GET['genre_id']) ? $_GET['genre_id'] : '';

$sql_concerts = "SELECT id,title,artist,genre_id,venue,concert_date,concert_description,concert_status,img FROM concerts ORDER BY id ASC";
$result_concerts = mysqli_query($conn, $sql_concerts);

if ($selected_genre != '') {
    $sql_concerts = "SELECT id, title, artist, genre_id, venue, concert_description, img FROM concerts WHERE genre_id = '$selected_genre'";
} else {
    $sql_concerts = "SELECT id, title, artist, genre_id, venue, concert_description, img FROM concerts";
}

if (!$result_concerts || !$result_genres) {
    die("Gagal mengambil data database: " . mysqli_error($conn));
}
?>
<?php
$pesan = "";

?>

<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventify</title>
    <!-- WAJIB ADA: Script Tailwind CSS -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased overflow-y-auto pb-12">

    <!-- NAVBAR ATAS -->
    <nav class="flex justify-between items-center bg-[#e0e0e0] px-10 py-3 border-b-2 border-blue-600">
        <div class="bg-[#555555] text-white px-6 py-2 rounded-full font-bold">
            Eventify
        </div>
        <div class="bg-[#777777] px-5 py-1.5 rounded-full flex gap-5">
            <a href="home_page_user.php" class="text-white text-sm px-2 py-1 font-medium">Home</a>
            <a href="about.php" class="text-white text-sm px-2 py-1 font-medium">About</a>
            <a href="event.php" class="text-white text-sm px-2 py-1 font-medium">Event</a>
        </div>
        <div class="flex items-center bg-white rounded-full pl-4 pr-0.5 py-0.5 gap-4 border border-gray-300">
            <a href="profile.php" class="text-gray-700 text-xs font-medium">Login / Profile</a>
            <a href="#" class="bg-[#555555] text-white px-5 py-2 rounded-full text-xs font-medium">Register</a>
        </div>
    </nav>

    <!-- KONTEN UTAMA -->
    <main class="max-w-7xl mx-auto mt-10 px-5">
        <header class="flex justify-between items-end mb-6">
            <div>
                <p class="text-gray-500 text-sm mb-1">Concert Categories</p>
                <h1 class="text-3xl font-semibold">Find your genres</h1>
            </div>
            <!-- Search Bar Abu-abu -->
            <div class="w-64 h-9 bg-[#b5b5b5] rounded-full"></div>
        </header>

        <!-- KATEGORI GENRE (TOMBOL CAPSULE) -->
        <section class="flex flex-wrap gap-2.5 mb-9">
            <!-- Tombol All Genres -->
            <a href="?" class="px-4 py-1.5 rounded-full text-xs transition <?php echo (empty($selected_genre)) ? 'bg-[#777777] text-white' : 'bg-[#dcdcdc] text-gray-800 hover:bg-[#777777] hover:text-white'; ?>">
                All Genres
            </a>
            
            <!-- Loop Genre dari Database -->
            <?php while ($genre = mysqli_fetch_assoc($result_genres)) { ?>
                <a href="?genre_id=<?php echo $genre['id']; ?>" 
                   class="px-4 py-1.5 rounded-full text-xs transition <?php echo ($selected_genre == $genre['id']) ? 'bg-[#777777] text-white' : 'bg-[#dcdcdc] text-gray-800 hover:bg-[#777777] hover:text-white'; ?>">
                    <?php echo htmlspecialchars($genre['genre_name']); ?>
                </a>
            <?php } ?>
        </section>

        <!-- GRID KARTU KONSER (Taruh di dalam tag <main>) -->
        <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 w-full h-auto">
            <?php 
            if (mysqli_num_rows($result_concerts) > 0) {
                // Loop PHP dimulai di sini
                while ($concert = mysqli_fetch_assoc($result_concerts)) { 
            ?>
                <!-- TARUH KODE KARTU TAILWIND DI SINI -->
                <div class="bg-[#e0e0e0] rounded-[20px] overflow-hidden flex flex-col shadow-sm">
                    
                    <!-- 1. KOTAK PEMBUNGKUS GAMBAR -->
                    <div class="w-full h-60 overflow-hidden bg-gray-200">
                        <?php if (!empty($concert['img'])): ?>
                            <img src="<?php echo htmlspecialchars($concert['img']); ?>" 
                                alt="<?php echo htmlspecialchars($concert['title']); ?>" 
                                class="w-full h-full object-cover object-center">
                        <?php else: ?>
                            <div class="w-full h-full flex justify-center items-center text-gray-400 text-sm">
                                No Image
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 2. AREA JUDUL & ARTIS (ABU-ABU TERANG) -->
                    <div class="p-5 text-center flex-grow">
                        <h3 class="text-lg font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($concert['title']); ?></h3>
                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($concert['artist']); ?></p>
                    </div>

                    <!-- 3. AREA DESKRIPSI (ABU-ABU GELAP DI BAWAH) -->
                    <div class="bg-[#888888] text-white p-5 text-center">
                        <p class="text-xs line-clamp-2 opacity-90 mb-1.5"><?php echo htmlspecialchars($concert['concert_description']); ?></p>
                        <span class="text-xs font-semibold block opacity-80">📍 <?php echo htmlspecialchars($concert['venue']); ?></span>
                    </div>
                    
                </div>
            <?php 
                } // Loop PHP berakhir di sini
            } else {
                echo "<p class='col-span-full text-center text-gray-500 py-10'>Tidak ada konser yang tersedia untuk genre ini.</p>";
            }
            ?>
        </section>
    </main>

</body>
</html>