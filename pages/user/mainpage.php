<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
?>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventify - Find Your Next Unforgettable Event</title>
    <!-- Hubungkan ke Tailwind CSS via CDN -->
    <script src="https://jsdelivr.net"></script>
</head>
<body class="h-full m-0 p-0 antialiased font-sans">

    <!-- HERO CONTAINER DENGAN LATAR BELAKANG GAMBAR PENUH -->
    <!-- Ganti 'https://unsplash.com' dengan path gambar konser Anda sendiri -->
    <div class="relative w-full min-h-screen bg-cover bg-center bg-no-repeat flex flex-col justify-between" 
         style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://unsplash.com?q=80&w=1974&auto=format&fit=crop');">
        
        <!-- 1. NAVBAR ATAS (Sesuai Mockup) -->
        <nav class="flex justify-between items-center bg-white/10 backdrop-blur-md px-10 py-3 border-b border-white/20">
            <!-- Logo -->
            <div class="bg-[#555555] text-white px-6 py-2 rounded-full font-bold text-sm tracking-wide">
                Eventify
            </div>
            <!-- Menu Tengah -->
            <div class="bg-[#777777]/80 px-6 py-2 rounded-full flex gap-6">
                <a href="#" class="text-white text-sm font-medium hover:opacity-80">Home</a>
                <a href="#" class="text-white text-sm font-medium hover:opacity-80">About</a>
                <a href="#" class="text-white text-sm font-medium hover:opacity-80">Event</a>
            </div>
            <!-- Akun Kanan -->
            <div class="flex items-center bg-white rounded-full pl-4 pr-0.5 py-0.5 gap-4 border border-gray-300">
                <a href="#" class="text-gray-700 text-xs font-medium">Login / Profile</a>
                <a href="#" class="bg-[#555555] text-white px-5 py-2 rounded-full text-xs font-medium">Register</a>
            </div>
        </nav>

        <!-- 2. KONTEN TENHGAH (HERO CONTENT) -->
        <main class="flex-grow flex flex-col items-center justify-center text-center px-4 max-w-3xl mx-auto gap-5">
            
            <!-- Live Badge Info -->
            <div class="bg-white/20 backdrop-blur-sm border border-white/30 text-white rounded-full px-5 py-1.5 text-xs font-medium tracking-wide">
                LIVE NOW: <span class="font-bold">Global Electronic Arena</span> – 18.4K tuned in
            </div>

            <!-- Judul Utama (Kapsul Putih/Abu Transparan Luas) -->
            <div class="bg-white/80 backdrop-blur-sm rounded-[24px] px-10 py-8 w-full shadow-lg">
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight">
                    Find Your Next Unforgettable Event
                </h1>
            </div>

            <!-- Sub-info Tambahan -->
            <div class="bg-[#777777]/70 backdrop-blur-sm text-white rounded-full px-8 py-2 w-full max-w-xl text-sm font-medium">
                Now Here
            </div>

            <!-- Deskripsi Detail Singkat -->
            <div class="bg-white/20 backdrop-blur-sm border border-white/20 text-white rounded-[20px] p-5 w-full max-w-xl text-xs md:text-sm leading-relaxed shadow-inner">
                Discover elite concert experiences, select prime seating in real-time, and purchase 100% verified premium tickets seamlessly.
            </div>

            <!-- TOMBOL UTAMA UNTUK DIKLIK PINDAH HALAMAN -->
            <!-- Sesuaikan href dengan lokasi file 'home_page_user.php' Anda -->
            <a href="pages/user/home_page_user.php" 
               class="mt-4 bg-[#555555] hover:bg-[#444444] text-white font-semibold px-10 py-3.5 rounded-full text-sm tracking-wide shadow-md transition-all transform hover:scale-105 active:scale-95 inline-block">
                Explore Events
            </a>

        </main>

        <!-- Footer / Ruang Kosong Bawah Agar Seimbang -->
        <footer class="py-4 text-center text-white/40 text-xs">
            &copy; 2026 Eventify. All rights reserved.
        </footer>

    </div>

</body>
</html>