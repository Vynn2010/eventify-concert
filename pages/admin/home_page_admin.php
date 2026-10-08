<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_admin.php";
 
// Helper: ambil satu angka dari query COUNT(*)
function count_rows(mysqli $conn, string $sql): int {
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($res);
    return (int)($row[0] ?? 0);
}
 
// ---------- RINGKASAN (sesuaikan nama tabel/kolom jika berbeda) ----------
$active_concerts  = count_rows($conn, "SELECT COUNT(*) FROM concerts WHERE concert_date >= CURDATE()");
$available_seats  = count_rows($conn, "SELECT COUNT(*) FROM seats WHERE seat_status = 'Available'");
$tickets_sold     = count_rows($conn, "SELECT COUNT(*) FROM tickets");
$tickets_scanned  = count_rows($conn, "SELECT COUNT(*) FROM tickets WHERE is_scanned = 1");
 
// ---------- 5 TIKET TERBARU ----------
$latest_tickets = mysqli_query($conn,
    "SELECT t.id, t.is_scanned, c.title, s.seat_number
     FROM tickets t
     JOIN concerts c ON c.id = t.concert_id
     JOIN seats s    ON s.id = t.seat_id
     ORDER BY t.id DESC
     LIMIT 5"
);
 
$admin_email = $_SESSION["email"] ?? "admin";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Eventify</title>
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
            <a href="home_page_admin.php" class="px-4 py-3 rounded-xl bg-[#5835FF] text-white font-semibold">Dashboard</a>
            <a href="seat_generate.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Generate Seats</a>
            <a href="scan.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Scan Ticket</a>
            <a href="#" class="px-4 py-3 rounded-xl hover:bg-white/10">Concerts</a>
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
                <h1 class="text-3xl font-bold">Dashboard</h1>
                <p class="text-sm opacity-65 mt-1">Welcome, <?= htmlspecialchars($admin_email) ?></p>
            </div>
            <a href="seat_generate.php"
               class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold px-6 py-3 rounded-full">
                + Generate Seats
            </a>
        </header>
 
        <!-- KARTU RINGKASAN -->
        <section class="grid gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Active concerts</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $active_concerts ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Seats available</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $available_seats ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Tickets sold</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $tickets_sold ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Tickets scanned</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $tickets_scanned ?></div>
            </div>
        </section>
 
        <!-- TIKET TERBARU -->
        <section class="bg-white rounded-2xl p-6 overflow-x-auto">
            <h2 class="text-lg font-semibold mb-4">Latest tickets</h2>
 
            <table class="w-full min-w-[560px] text-sm text-left">
                <thead>
                    <tr class="border-b border-[#17143D]/15 opacity-60">
                        <th class="py-2 font-semibold">Ticket code</th>
                        <th class="py-2 font-semibold">Concert</th>
                        <th class="py-2 font-semibold">Seat</th>
                        <th class="py-2 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($latest_tickets) === 0): ?>
                    <tr><td colspan="4" class="py-6 text-center opacity-60">Belum ada tiket terjual.</td></tr>
                <?php else: ?>
                    <?php while ($t = mysqli_fetch_assoc($latest_tickets)): ?>
                    <tr class="border-b border-[#17143D]/10 last:border-0">
                        <td class="py-3">TKT-<?= str_pad($t['id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td class="py-3"><?= htmlspecialchars($t['title']) ?></td>
                        <td class="py-3"><?= htmlspecialchars($t['seat_number']) ?></td>
                        <td class="py-3">
                            <?php if ($t['is_scanned']): ?>
                                <span class="text-green-700">Scanned</span>
                            <?php else: ?>
                                <span class="text-amber-700">Not scanned</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
 
    </main>
</div>
</body>
</html>