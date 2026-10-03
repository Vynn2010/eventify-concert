<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header("Location: loginpage_user.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];
 
// ---------- TIKET MILIK USER ----------
$stmt = mysqli_prepare($conn, "SELECT t.id, t.qr_token, t.status, s.seat_number,
                                      c.title, c.artist, c.venue, c.concert_date, c.img
                               FROM tickets t
                               JOIN seats s    ON s.id = t.seat_id
                               JOIN concerts c ON c.id = t.concert_id
                               WHERE t.user_id = ?
                               ORDER BY t.id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
 
$daftar_tiket = [];
while ($row = mysqli_fetch_assoc($hasil)) {
    $daftar_tiket[] = $row;
}
 
$header_search = true; // navbar menampilkan kolom "Search event"
$halaman_aktif = 'event';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased flex flex-col">
 
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="w-full max-w-5xl mx-auto px-4 sm:px-5 py-6 flex-grow">
 
        <!-- Kembali -->
        <a href="event.php"
           class="inline-block bg-[#d9d9d9] hover:bg-[#c8c8c8] text-gray-900 text-xs font-semibold rounded-full px-5 py-2 transition">
            Back to events
        </a>
 
        <!-- Payment Complete -->
        <div class="flex justify-center mt-4 mb-6">
            <div class="bg-[#d9d9d9] text-gray-900 font-semibold text-center rounded-full px-8 py-3 leading-tight">
                <span aria-hidden="true">✓</span> Payment<br>Complete
            </div>
        </div>
 
        <h1 class="text-xl font-bold mb-3">Ticket Result</h1>
 
        <?php if (empty($daftar_tiket)): ?>
            <div class="bg-[#d9d9d9] rounded-[28px] p-10 text-center">
                <p class="text-gray-700 mb-4">Kamu belum memiliki tiket.</p>
                <a href="event.php" class="inline-block bg-[#6b6b6b] hover:bg-[#555] text-white rounded-full px-8 py-2.5 text-sm transition">Lihat konser</a>
            </div>
        <?php endif; ?>
 
        <?php foreach ($daftar_tiket as $n => $t):
            $mulai   = strtotime($t['concert_date']);
            $tanggal = $mulai ? date('d M Y, H:i', $mulai) : '-';
            $ics_mulai = $mulai ? date('Ymd\THis', $mulai) : '';
            $ics_akhir = $mulai ? date('Ymd\THis', $mulai + 3 * 3600) : ''; // perkiraan durasi 3 jam
            $kartu_id  = 'ticket-card-' . (int)$t['id'];
            $gambar    = !empty($t['img']) ? "background-image:url('" . htmlspecialchars($t['img'], ENT_QUOTES) . "')" : '';
        ?>
        <section class="mb-10">
 
            <!-- Area yang disimpan sebagai gambar: gambar tiket + QR -->
            <div id="<?= $kartu_id ?>" class="grid grid-cols-1 sm:grid-cols-[3fr_1fr] gap-4 bg-white">
 
                <!-- Gambar tiket -->
                <div class="relative bg-[#d9d9d9] rounded-[28px] overflow-hidden min-h-56 sm:min-h-64 bg-cover bg-top"
                     style="<?= $gambar ?>">
                    <div class="absolute inset-x-0 bottom-0 px-6 py-5"
                         style="background:linear-gradient(to top, rgba(0,0,0,.75), rgba(0,0,0,0))">
                        <p class="text-white text-xl sm:text-2xl font-bold leading-tight"><?= htmlspecialchars($t['title']) ?></p>
                        <p class="text-gray-200 text-sm"><?= htmlspecialchars($t['artist']) ?></p>
                        <p class="text-gray-200 text-sm mt-2">
                            Kursi <strong><?= htmlspecialchars($t['seat_number']) ?></strong>
                            · 📍 <?= htmlspecialchars($t['venue']) ?>
                        </p>
                        <p class="text-gray-200 text-sm">🗓 <?= htmlspecialchars($tanggal) ?></p>
                    </div>
                </div>
 
                <!-- QR -->
                <div class="bg-[#d9d9d9] rounded-[28px] p-5 flex flex-col items-center justify-center text-center">
                    <p class="text-lg font-bold mb-3">QR</p>
                    <div class="bg-white p-3 rounded-xl">
                        <div class="qr" data-token="<?= htmlspecialchars($t['qr_token']) ?>"></div>
                    </div>
                    <p class="text-xs mt-3 font-medium <?= $t['status'] === 'used' ? 'text-red-700' : 'text-green-800' ?>">
                        <?= $t['status'] === 'used' ? 'Sudah dipakai' : 'Valid' ?>
                    </p>
                </div>
            </div>
 
            <!-- Tombol aksi -->
            <div class="flex flex-wrap gap-3 mt-5">
                <button type="button"
                        data-target="<?= $kartu_id ?>"
                        data-filename="tiket-<?= htmlspecialchars(preg_replace('/[^A-Za-z0-9_-]/', '', $t['seat_number'])) ?>-<?= (int)$t['id'] ?>.png"
                        onclick="saveTicket(this)"
                        class="bg-[#6b6b6b] hover:bg-[#555] disabled:opacity-60 text-white text-base sm:text-lg font-bold rounded-xl px-8 py-3 transition">
                    Save Ticket
                </button>
 
                <button type="button"
                        data-uid="<?= (int)$t['id'] ?>"
                        data-title="<?= htmlspecialchars($t['title']) ?>"
                        data-location="<?= htmlspecialchars($t['venue']) ?>"
                        data-desc="<?= htmlspecialchars('Kursi ' . $t['seat_number'] . ' - ' . $t['artist']) ?>"
                        data-start="<?= $ics_mulai ?>"
                        data-end="<?= $ics_akhir ?>"
                        onclick="addToCalendar(this)"
                        <?= $ics_mulai === '' ? 'disabled' : '' ?>
                        class="bg-[#6b6b6b] hover:bg-[#555] disabled:opacity-60 disabled:cursor-not-allowed text-white text-base sm:text-lg font-bold rounded-xl px-8 py-3 transition">
                    Add to Calender
                </button>
            </div>
        </section>
        <?php endforeach; ?>
    </main>
 
    <?php include "../../components/landing/footer.php"; ?>
 
    <script>
        // Gambar QR dari token
        document.querySelectorAll('.qr').forEach(el => {
            new QRCode(el, { text: el.dataset.token, width: 150, height: 150, correctLevel: QRCode.CorrectLevel.M });
        });
 
        // Save Ticket: simpan area tiket (gambar + QR) sebagai PNG
        async function saveTicket(btn) {
            const area = document.getElementById(btn.dataset.target);
            const teks = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';
            try {
                const canvas = await html2canvas(area, { scale: 2, backgroundColor: '#ffffff', useCORS: true });
                const a = document.createElement('a');
                a.download = btn.dataset.filename;
                a.href = canvas.toDataURL('image/png');
                a.click();
            } catch (e) {
                alert('Gagal menyimpan tiket. Coba lagi.');
            } finally {
                btn.disabled = false;
                btn.textContent = teks;
            }
        }
 
        // Add to Calendar: unduh file .ics (bisa dibuka di Google Calendar, Apple Calendar, Outlook)
        function icsEscape(s) {
            return String(s).replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/\r?\n/g, '\\n');
        }
        function addToCalendar(btn) {
            const d = btn.dataset;
            const stamp = new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
            const ics = [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'PRODID:-//Eventify//Ticket//ID',
                'BEGIN:VEVENT',
                'UID:ticket-' + d.uid + '@eventify',
                'DTSTAMP:' + stamp,
                'DTSTART:' + d.start,
                'DTEND:' + d.end,
                'SUMMARY:' + icsEscape(d.title),
                'LOCATION:' + icsEscape(d.location),
                'DESCRIPTION:' + icsEscape(d.desc),
                'END:VEVENT',
                'END:VCALENDAR'
            ].join('\r\n');
 
            const blob = new Blob([ics], { type: 'text/calendar;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'konser-' + d.uid + '.ics';
            a.click();
            URL.revokeObjectURL(a.href);
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
 