<?php
require_once __DIR__ . "/../../actions/cek_koneksi.php";
include "../../actions/cek_login_admin.php"; // sama seperti cek_login_user.php di book.php
 
// Dropdown konser
$konser = mysqli_query($conn, "SELECT id, title FROM concerts ORDER BY id DESC");
 
// Dropdown kategori (sesuaikan nama tabel/kolom jika berbeda)
$kategori = mysqli_query($conn, "SELECT id, category_name FROM categories ORDER BY id");
 
$status = $_GET['status'] ?? '';
$pesan  = $_GET['msg'] ?? '';
$total  = (int)($_GET['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Generate Kursi</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
 
  <form action="../../actions/seat_generate_process.php" method="POST"
        class="bg-white rounded-2xl shadow p-8 w-full max-w-lg space-y-4">
 
    <h1 class="text-2xl font-bold">Generate Kursi</h1>
 
    <?php if ($status === 'ok'): ?>
      <div class="bg-green-100 text-green-800 rounded-lg p-3">
        Berhasil membuat <?= $total ?> kursi.
      </div>
    <?php elseif ($status === 'error'): ?>
      <div class="bg-red-100 text-red-800 rounded-lg p-3">
        <?= htmlspecialchars($pesan) ?>
      </div>
    <?php endif; ?>
 
    <div>
      <label class="block text-sm font-medium mb-1">Konser</label>
      <select name="concert_id" required class="w-full border rounded-lg p-2">
        <option value="">-- pilih konser --</option>
        <?php while ($k = mysqli_fetch_assoc($konser)): ?>
          <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['title']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
 
    <div>
      <label class="block text-sm font-medium mb-1">Baris</label>
      <input type="text" name="baris" required placeholder="A-F atau A,B,C"
             class="w-full border rounded-lg p-2">
      <p class="text-xs text-gray-500 mt-1">Pakai rentang (A-F) atau daftar dipisah koma (A,C,E).</p>
    </div>
 
    <div>
      <label class="block text-sm font-medium mb-1">Jumlah kursi per baris</label>
      <input type="number" name="jumlah" min="1" max="100" value="20" required
             class="w-full border rounded-lg p-2">
    </div>
 
    <div>
      <label class="block text-sm font-medium mb-1">Kategori</label>
      <select name="category_id" required class="w-full border rounded-lg p-2">
        <option value="">-- pilih kategori --</option>
        <?php while ($c = mysqli_fetch_assoc($kategori)): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
 
    <div>
      <label class="block text-sm font-medium mb-1">Harga per kursi</label>
      <input type="number" name="harga" min="0" step="1000" value="200000" required
             class="w-full border rounded-lg p-2">
    </div>
 
    <button type="submit"
            class="w-full bg-black text-white rounded-full py-2 font-medium hover:opacity-90">
      Generate
    </button>
  </form>
 
</body>
</html>