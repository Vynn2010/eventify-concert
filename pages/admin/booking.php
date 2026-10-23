<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_admin.php";
include "../../repo/booking_repositories.php";
 
if (!function_exists('rupiah')) {
    function rupiah($n) {
        return 'Rp ' . number_format((float)$n, 0, ',', '.');
    }
}
 
// Buat URL halaman ini dengan filter aktif, lalu timpa sebagian parameter
function build_page_url(array $override): string {
    $params = array_merge($_GET, $override);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return 'booking.php' . ($params ? '?' . http_build_query($params) : '');
}
 
// ---------- RINGKASAN (semua pesanan) ----------
$revenue_sql = AdminScan::findOrderSummary();
 
$total_bookings = AdminScan::createBookingTotal($conn);
$total_revenue  = AdminScan::createRevenueTotal($conn, $revenue_sql);
$valid_tickets  = (int)scalar($conn, "SELECT COUNT(*) FROM tickets WHERE status = 'valid'");
$used_tickets   = (int)scalar($conn, "SELECT COUNT(*) FROM tickets WHERE status = 'used'");
 
// ---------- FILTER ----------
$q             = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
if (!in_array($status_filter, ['valid', 'used'], true)) {
    $status_filter = '';
}
$per_page = 15;
$page     = max(1, (int)($_GET['page'] ?? 1));
 
$from_sql = "FROM tickets t
             JOIN users u    ON u.id = t.user_id
             JOIN concerts c ON c.id = t.concert_id
             JOIN seats s    ON s.id = t.seat_id
             LEFT JOIN categories cat ON cat.id = s.category_id";
 
$where  = [];
$types  = '';
$params = [];
 
if ($q !== '') {
    $where[]  = "(u.email LIKE ? OR c.title LIKE ? OR s.seat_number LIKE ?)";
    $types   .= 'sss';
    $like     = "%$q%";
    array_push($params, $like, $like, $like);
}
if ($status_filter !== '') {
    $where[]  = "t.status = ?";
    $types   .= 's';
    $params[] = $status_filter;
}
$where_sql = $where ? " WHERE " . implode(' AND ', $where) : '';
 
// Jumlah hasil (untuk pagination)
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) $from_sql $where_sql");
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$filtered_total = (int)(mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0] ?? 0);
 
$total_pages = max(1, (int)ceil($filtered_total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;
 
// Data pesanan
$sql = AdminScan::findOrderData($from_sql, $where_sql, $per_page, $offset);

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
 
$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bookings[] = $row;
}
 
$showing_from = $filtered_total ? $offset + 1 : 0;
$showing_to   = $offset + count($bookings);
$admin_email  = $_SESSION["email"] ?? "admin";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking - Admin Eventify</title>
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
            <a href="home_page_admin.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Dashboard</a>
            <a href="seat_generate.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Generate Seats</a>
            <a href="booking.php" class="px-4 py-3 rounded-xl bg-[#5835FF] text-white font-semibold">Booking</a>
            <a href="concerts.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Concerts</a>
            <a href="genres.php" class="px-4 py-3 rounded-xl hover:bg-white/10">Genres</a>
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
                <h1 class="text-3xl font-bold">Booking</h1>
                <p class="text-sm opacity-65 mt-1">Welcome, <?= htmlspecialchars($admin_email) ?></p>
            </div>
            <a href="scan.php"
               class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold px-6 py-3 rounded-full">
                Scan Ticket
            </a>
        </header>
 
        <!-- KARTU RINGKASAN -->
        <section class="grid gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Total bookings</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $total_bookings ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Total revenue</div>
                <div class="text-2xl xl:text-3xl font-bold text-[#5835FF] mt-2"><?= rupiah($total_revenue) ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Valid (not scanned)</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $valid_tickets ?></div>
            </div>
            <div class="bg-white rounded-2xl p-6">
                <div class="text-sm opacity-65">Used (scanned)</div>
                <div class="text-3xl font-bold text-[#5835FF] mt-2"><?= $used_tickets ?></div>
            </div>
        </section>
 
        <!-- TABEL PESANAN -->
        <section class="bg-white rounded-2xl p-6 flex flex-col gap-4">
 
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">All bookings</h2>
 
                <form action="booking.php" method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search email, concert, seat"
                           class="w-56 bg-[#F4F3FE] text-sm rounded-full px-5 py-2.5 outline-none focus:ring-2 focus:ring-[#5835FF]">
                    <select name="status"
                            class="bg-[#F4F3FE] text-sm rounded-full px-4 py-2.5 outline-none focus:ring-2 focus:ring-[#5835FF]">
                        <option value="">All status</option>
                        <option value="valid" <?= $status_filter === 'valid' ? 'selected' : '' ?>>Valid</option>
                        <option value="used"  <?= $status_filter === 'used'  ? 'selected' : '' ?>>Used</option>
                    </select>
                    <button type="submit"
                            class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold rounded-full px-5 py-2.5">Filter</button>
                    <?php if ($q !== '' || $status_filter !== ''): ?>
                        <a href="booking.php" class="text-sm underline opacity-70 hover:opacity-100">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
 
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm text-left">
                    <thead>
                        <tr class="border-b border-[#17143D]/15 opacity-60">
                            <th class="py-2 pr-3 font-semibold">Booking</th>
                            <th class="py-2 pr-3 font-semibold">Customer</th>
                            <th class="py-2 pr-3 font-semibold">Concert</th>
                            <th class="py-2 pr-3 font-semibold">Seat</th>
                            <th class="py-2 pr-3 font-semibold">Total</th>
                            <th class="py-2 pr-3 font-semibold">Booked at</th>
                            <th class="py-2 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center opacity-60">
                                <?= ($q !== '' || $status_filter !== '') ? 'Tidak ada pesanan yang cocok dengan filter.' : 'Belum ada pesanan.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b):
                            $total  = (float)$b['concert_price'] + (float)$b['seat_price'];
                            $is_used = $b['status'] === 'used';
                        ?>
                        <tr class="border-b border-[#17143D]/10 last:border-0">
                            <td class="py-3 pr-3 whitespace-nowrap font-semibold">
                                TKT-<?= str_pad($b['id'], 5, '0', STR_PAD_LEFT) ?>
                            </td>
 
                            <td class="py-3 pr-3 break-all"><?= htmlspecialchars($b['email']) ?></td>
 
                            <td class="py-3 pr-3"><?= htmlspecialchars($b['title']) ?></td>
 
                            <td class="py-3 pr-3 whitespace-nowrap">
                                <?= htmlspecialchars($b['seat_number']) ?>
                                <?php if (!empty($b['category_name'])): ?>
                                    <span class="text-xs opacity-60">· <?= htmlspecialchars($b['category_name']) ?></span>
                                <?php endif; ?>
                            </td>
 
                            <td class="py-3 pr-3 whitespace-nowrap"><?= rupiah($total) ?></td>
 
                            <td class="py-3 pr-3 whitespace-nowrap">
                                <?= !empty($b['created_at']) ? date('d M Y, H:i', strtotime($b['created_at'])) : '-' ?>
                            </td>
 
                            <td class="py-3">
                                <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap
                                             <?= $is_used ? 'bg-gray-100 text-gray-600' : 'bg-[#5835FF]/10 text-[#5835FF]' ?>">
                                    <?= $is_used ? 'Used' : 'Valid' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
 
            <!-- Pagination -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                <p class="opacity-65">
                    Showing <?= $showing_from ?>–<?= $showing_to ?> of <?= $filtered_total ?> bookings
                </p>
 
                <?php if ($total_pages > 1): ?>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?= htmlspecialchars(build_page_url(['page' => $page - 1])) ?>"
                           class="px-4 py-2 rounded-full bg-[#F4F3FE] hover:bg-[#8F9BFB]/30">Prev</a>
                    <?php endif; ?>
                    <span class="px-2 opacity-65">Page <?= $page ?> of <?= $total_pages ?></span>
                    <?php if ($page < $total_pages): ?>
                        <a href="<?= htmlspecialchars(build_page_url(['page' => $page + 1])) ?>"
                           class="px-4 py-2 rounded-full bg-[#F4F3FE] hover:bg-[#8F9BFB]/30">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
 
    </main>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>