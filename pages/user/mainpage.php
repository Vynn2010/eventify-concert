<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
 
// Gambar latar hero: taruh file gambarmu di sini (atau ganti dengan URL gambar langsung, bukan URL halaman web)
$hero_img = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRO9_C3Ov7jtWV1rFxzYk4tFgyBNRrM9MEDMZRQC9zr2A&s=10";
 
// Halaman daftar konser (satu folder dengan file ini)
$halaman_event = "home_page_user.php";
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventify - Find Your Next Unforgettable Event</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="min-h-full m-0 p-0 antialiased font-sans bg-gray-800">
 
    <!-- HERO: gambar penuh + overlay gelap (bg-gray-700 jadi cadangan kalau gambar tidak ditemukan) -->
    <div class="relative w-full min-h-screen flex flex-col bg-gray-700 bg-cover bg-center bg-no-repeat"
         style="background-image: linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('<?= htmlspecialchars($hero_img) ?>');">
 
        <!-- 1. NAVBAR -->
        <?php include "../../components/landing/header.php"; ?>
 
        <!-- 2. KONTEN TENGAH -->
        <main class="flex-grow flex items-center justify-center px-4 py-10">
            <section class="w-full max-w-2xl flex flex-col items-center text-center gap-5 sm:gap-7">
 
                <!-- Badge LIVE NOW -->
                <p class="bg-[#d9d9d9] text-gray-800 rounded-full px-5 py-1.5 text-xs sm:text-sm font-bold shadow-sm">
                    LIVE NOW: <span class="font-normal text-gray-700">Global Electronic Arena – 18.4K tuned in</span>
                </p>
 
                <!-- Headline -->
                <div class="w-full bg-[#d9d9d9] rounded-[24px] px-6 sm:px-12 py-8 sm:py-10 shadow-md">
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-black tracking-tight leading-tight">
                        Find Your Next Unforgettable Event
                    </h1>
                </div>
 
                <!-- Deskripsi -->
                <p class="w-full max-w-md bg-[#d9d9d9]/90 backdrop-blur-sm text-gray-700 rounded-[20px] px-6 sm:px-10 py-5 text-xs sm:text-sm font-medium leading-relaxed shadow-sm">
                    Discover elite concert experiences, select prime seating in real-time, and purchase 100% verified premium tickets seamlessly.
                </p>
 
                <!-- Tombol -->
                <a href="<?= htmlspecialchars($halaman_event) ?>"
                   class="inline-block bg-[#595959] hover:bg-[#444] text-white font-semibold px-8 sm:px-10 py-3 rounded-full text-sm shadow-md transition hover:scale-105 active:scale-95">
                    Explore Events
                </a>
            </section>
        </main>
    </div>
 
</body>
</html>