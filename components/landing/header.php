<?php /* components/landing/header.php
   Di halaman login/register, set sebelum include:  $hide_auth = true;  */ 
$base = "../../pages/user/";
?>

<header class="w-full bg-[#d9d9d9] sticky top-0 z-50">
    <nav class="max-w-7xl mx-auto px-4 sm:px-5 py-2.5 sm:py-3 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
 
        <!-- Kiri: tombol Back (opsional) + Logo -->
        <div class="col-start-1 row-start-1 justify-self-start flex items-center gap-2 sm:gap-3">
            <?php if (!empty($back_url)): ?>
            <a href="<?= htmlspecialchars($back_url) ?>" aria-label="Back"
               class="shrink-0 bg-[#5c5c5c] hover:bg-[#444] text-white rounded-full w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <?php endif; ?>
            <a href="<?= $base ?>mainpage.php" class="inline-block bg-[#5c5c5c] text-white font-bold text-sm sm:text-base rounded-full px-5 sm:px-8 py-2.5 sm:py-3">
                Eventify<span class="text-cyan-300">.</span>
            </a>
        </div>
 
        <!-- Menu tengah (tablet/desktop) -->
        <ul class="hidden md:flex items-center bg-[#5c5c5c] text-white text-sm rounded-full px-2 lg:px-3 col-start-2 row-start-1">
            <li><a href="<?= $base ?>home_page_user.php" class="block px-4 lg:px-6 py-2.5 lg:py-3 hover:text-gray-300 transition">Home</a></li>
            <li><a href="<?= $base ?>about.php" class="block px-4 lg:px-6 py-2.5 lg:py-3 hover:text-gray-300 transition">About</a></li>
            <li><a href="<?= $base ?>event.php" class="block px-4 lg:px-6 py-2.5 lg:py-3 hover:text-gray-300 transition">Event</a></li>
        </ul>
 
        <!-- Kanan: Search / Login-Profile + hamburger -->
        <div class="col-start-3 row-start-1 justify-self-end flex items-center gap-2">
            <?php if (!empty($header_search)): ?>
            <form action="<?= $base ?>home_page_user.php" method="GET" class="hidden sm:block">
                <input type="text" name="q" placeholder="Search event"
                       class="w-40 lg:w-56 bg-white text-[11px] text-gray-900 rounded-full px-4 py-3 border-0 focus:outline-none focus:ring-2 focus:ring-gray-400">
            </form>
            <?php elseif (empty($hide_auth)): ?>
            <div class="flex items-center bg-white rounded-full">
                <a href="<?= $base ?>loginpage_user.php" class="text-[11px] text-gray-900 px-3 lg:px-4 py-2 whitespace-nowrap">Login</a>
                <a href="<?= $base ?>profile.php" class="bg-[#5c5c5c] hover:bg-[#444] text-white text-xs sm:text-sm rounded-full px-4 sm:px-6 py-2.5 sm:py-3 transition">Profile</a>
            </div>
            <?php endif; ?>
 
            <button id="menuBtn" type="button" aria-label="Menu"
                    class="md:hidden bg-[#5c5c5c] text-white rounded-full w-10 h-10 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </nav>
 
    <!-- Dropdown mobile -->
    <div id="mobileMenu" class="hidden md:hidden max-w-7xl mx-auto px-4 sm:px-5 pb-3">
        <ul class="bg-[#5c5c5c] text-white text-sm rounded-2xl overflow-hidden">
            <li><a href="<?= $base ?>home_page_user.php" class="block px-5 py-3 hover:bg-[#444]">Home</a></li>
            <li><a href="<?= $base ?>about.php" class="block px-5 py-3 hover:bg-[#444]">About</a></li>
            <li><a href="<?= $base ?>event.php" class="block px-5 py-3 hover:bg-[#444]">Event</a></li>
        </ul>
        <?php if (!empty($header_search)): ?>
        <form action="<?= $base ?>home_page_user.php" method="GET" class="mt-2 sm:hidden">
            <input type="text" name="q" placeholder="Search event"
                   class="w-full bg-white text-sm text-gray-900 rounded-full px-4 py-2.5 border-0 focus:outline-none focus:ring-2 focus:ring-gray-400">
        </form>
        <?php endif; ?>
    </div>
</header>
 
<script>
    document.getElementById('menuBtn').addEventListener('click', function () {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });
</script>
 








