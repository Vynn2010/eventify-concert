<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_admin.php";
include "../../repo/booking_repositories.php";
 
$concerts = AdminScan::findTicket($conn);
 
$admin_email = $_SESSION["email"] ?? "admin";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Ticket - Admin Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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
 
        <header class="flex items-center gap-4">
            <a href="booking.php" aria-label="Kembali ke booking"
               class="shrink-0 w-11 h-11 rounded-full bg-[#5835FF] hover:bg-[#4D4FFF] text-white flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold">Scan Ticket</h1>
                <p class="text-sm opacity-65 mt-1">Welcome, <?= htmlspecialchars($admin_email) ?></p>
            </div>
        </header>
 
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
 
            <!-- KIRI: kamera -->
            <section class="bg-white rounded-2xl p-6 flex flex-col gap-4">
                <h2 class="text-lg font-semibold">Camera</h2>
 
                <div>
                    <label for="concert-filter" class="block text-sm mb-1.5">Concert being checked</label>
                    <select id="concert-filter"
                            class="w-full bg-[#F4F3FE] rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                        <option value="0">All concerts</option>
                        <?php while ($c = mysqli_fetch_assoc($concerts)): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
 
                <div id="reader" class="w-full max-w-md mx-auto rounded-2xl overflow-hidden bg-[#17143D] min-h-64"></div>
 
                <div class="flex flex-wrap gap-3">
                    <button type="button" id="start-btn"
                            class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold rounded-full px-6 py-3 transition">
                        Start camera
                    </button>
                    <button type="button" id="stop-btn" disabled
                            class="bg-[#F4F3FE] hover:bg-[#8F9BFB]/30 text-sm font-semibold rounded-full px-6 py-3 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Stop camera
                    </button>
                </div>
                <p class="text-xs opacity-60">Kamera hanya bisa dipakai lewat <code>localhost</code> atau HTTPS.</p>
 
                <!-- Input manual -->
                <form id="manual-form" class="border-t border-[#17143D]/10 pt-4 flex flex-col gap-2">
                    <label for="manual-token" class="block text-sm">Or enter the ticket token manually</label>
                    <div class="flex gap-2">
                        <input type="text" id="manual-token" placeholder="Paste qr_token"
                               class="flex-1 min-w-0 bg-[#F4F3FE] rounded-full px-5 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#5835FF]">
                        <button type="submit"
                                class="bg-[#5835FF] hover:bg-[#4D4FFF] text-white text-sm font-semibold rounded-full px-6 py-2.5 transition">Check</button>
                    </div>
                </form>
            </section>
 
            <!-- KANAN: hasil + riwayat -->
            <section class="flex flex-col gap-6">
 
                <div id="result" class="rounded-2xl p-6 bg-white">
                    <p class="text-lg font-semibold">Waiting for a ticket…</p>
                    <p class="text-sm opacity-65 mt-1">Arahkan kamera ke QR tiket atau masukkan token manual.</p>
                </div>
 
                <div class="bg-white rounded-2xl p-6">
                    <h2 class="text-lg font-semibold mb-3">Recent scans</h2>
                    <ul id="history" class="flex flex-col gap-2 text-sm">
                        <li class="opacity-60">Belum ada scan.</li>
                    </ul>
                </div>
            </section>
        </div>
    </main>
</div>
 
<script>
(function () {
    var endpoint    = '../../actions/scan_process.php';
    var resultBox   = document.getElementById('result');
    var historyList = document.getElementById('history');
    var filter      = document.getElementById('concert-filter');
    var startBtn    = document.getElementById('start-btn');
    var stopBtn     = document.getElementById('stop-btn');
    var manualForm  = document.getElementById('manual-form');
    var manualInput = document.getElementById('manual-token');
 
    var scanner = null, running = false, busy = false;
    var lastText = '', lastTime = 0;
    var history = [];
 
    var styles = {
        ok:            { box: 'bg-green-100 text-green-900', title: 'Ticket valid - entry allowed' },
        used:          { box: 'bg-amber-100 text-amber-900', title: 'Ticket already used' },
        wrong_concert: { box: 'bg-amber-100 text-amber-900', title: 'Wrong concert' },
        invalid:       { box: 'bg-red-100 text-red-900',     title: 'Invalid ticket' },
        error:         { box: 'bg-red-100 text-red-900',     title: 'Error' }
    };
 
    // QR bisa berisi token saja, atau URL yang memuat token
    function extractToken(text) {
        text = String(text).trim();
        try {
            var url = new URL(text);
            return url.searchParams.get('token')
                || url.searchParams.get('qr_token')
                || url.pathname.split('/').filter(Boolean).pop()
                || text;
        } catch (e) {
            return text;
        }
    }
 
    function line(parent, label, value) {
        var p = document.createElement('p');
        p.className = 'text-sm mt-1';
        var b = document.createElement('span');
        b.className = 'font-semibold';
        b.textContent = label + ': ';
        p.appendChild(b);
        p.appendChild(document.createTextNode(value));
        parent.appendChild(p);
    }
 
    function showResult(data) {
        var st = styles[data.result] || styles.error;
        resultBox.className = 'rounded-2xl p-6 ' + st.box;
        resultBox.textContent = '';
 
        var title = document.createElement('p');
        title.className = 'text-xl font-bold';
        title.textContent = st.title;
        resultBox.appendChild(title);
 
        var msg = document.createElement('p');
        msg.className = 'text-sm mt-1';
        msg.textContent = data.message || '';
        resultBox.appendChild(msg);
 
        if (data.ticket) {
            var box = document.createElement('div');
            box.className = 'mt-4 pt-3 border-t border-current/20';
            line(box, 'Ticket', data.ticket.code);
            line(box, 'Concert', data.ticket.concert);
            line(box, 'Seat', data.ticket.seat);
            line(box, 'Customer', data.ticket.email);
            resultBox.appendChild(box);
        }
    }
 
    function addHistory(data) {
        var now = new Date();
        var time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        history.unshift({
            time: time,
            code: data.ticket ? data.ticket.code : '-',
            result: (styles[data.result] || styles.error).title
        });
        history = history.slice(0, 8);
 
        historyList.textContent = '';
        history.forEach(function (h) {
            var li = document.createElement('li');
            li.className = 'flex justify-between gap-3 border-b border-[#17143D]/10 pb-2 last:border-0';
            var left = document.createElement('span');
            left.textContent = h.time + '  ' + h.code;
            var right = document.createElement('span');
            right.className = 'opacity-70 text-right';
            right.textContent = h.result;
            li.appendChild(left);
            li.appendChild(right);
            historyList.appendChild(li);
        });
    }
 
    function check(token) {
        if (busy || !token) return;
        busy = true;
 
        var body = new FormData();
        body.append('token', token);
        body.append('concert_id', filter.value);
 
        fetch(endpoint, { method: 'POST', body: body })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                showResult(data);
                addHistory(data);
            })
            .catch(function () {
                var data = { result: 'error', message: 'Gagal menghubungi server atau sesi admin berakhir. Muat ulang halaman.' };
                showResult(data);
            })
            .finally(function () { busy = false; });
    }
 
    function onScan(text) {
        // Abaikan QR yang sama dalam 4 detik supaya tidak diproses berulang
        var now = Date.now();
        if (text === lastText && now - lastTime < 4000) return;
        lastText = text;
        lastTime = now;
        check(extractToken(text));
    }
 
    function toggleButtons() {
        startBtn.disabled = running;
        stopBtn.disabled  = !running;
    }
 
    startBtn.addEventListener('click', function () {
        if (running) return;
        scanner = new Html5Qrcode('reader');
        scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            onScan,
            function () {}
        ).then(function () {
            running = true;
            toggleButtons();
        }).catch(function () {
            showResult({ result: 'error', message: 'Kamera tidak bisa dibuka. Izinkan akses kamera, atau pakai input token manual.' });
        });
    });
 
    stopBtn.addEventListener('click', function () {
        if (!running || !scanner) return;
        scanner.stop().then(function () {
            scanner.clear();
            running = false;
            toggleButtons();
        });
    });
 
    manualForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var token = extractToken(manualInput.value);
        if (token) {
            check(token);
            manualInput.value = '';
        }
    });
 
    toggleButtons();
})();
</script>
</body>
</html>