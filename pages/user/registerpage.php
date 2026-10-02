<?php
include "../../actions/cek_koneksi.php";
session_start();
 
$pesan = "";
$sukses = false;
 
if (isset($_POST["daftar"])) {
    $email     = trim($_POST["email"] ?? "");
    $passwords = $_POST["password"] ?? "";
 
    if ($email === "" || $passwords === "") {
        $pesan = "Email dan password tidak boleh kosong.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = "Format email tidak valid.";
    } else {
        // Cek email sudah terdaftar atau belum
        $cek = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
        mysqli_stmt_bind_param($cek, "s", $email);
        mysqli_stmt_execute($cek);
        $hasil = mysqli_stmt_get_result($cek);
 
        if (mysqli_num_rows($hasil) > 0) {
            $pesan = "Email sudah terdaftar.";
        } else {
            // Sesuaikan nama kolom dengan tabel users kamu
            $role = "user";
            $ins = mysqli_prepare($conn, "INSERT INTO users (email, passwords, role) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins, "sss", $email, $passwords, $role);
 
            if (mysqli_stmt_execute($ins)) {
                $sukses = true;
                $pesan = "Registrasi berhasil. Silakan login.";
            } else {
                $pesan = "Registrasi gagal, coba lagi.";
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($cek);
    }
}
 
$hide_auth = true; // sembunyikan tombol Login/Register di navbar
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased">
 
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="px-4 py-8 sm:py-12">
        <div class="w-full max-w-xl mx-auto bg-[#d9d9d9] rounded-[30px] px-6 sm:px-10 py-8 sm:py-10">
 
            <h1 class="text-2xl sm:text-3xl font-bold text-center text-black mb-8">Register Page</h1>
 
            <?php if ($pesan !== ""): ?>
                <p class="mb-4 text-center text-sm <?= $sukses ? 'text-green-700' : 'text-red-600' ?>"><?= htmlspecialchars($pesan) ?></p>
            <?php endif; ?>
 
            <form method="POST" class="space-y-5">
                <input type="email" name="email" placeholder="Insert Email" required
                       value="<?= $sukses ? '' : htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="w-full bg-[#6b6b6b] text-white placeholder-white font-semibold text-base sm:text-lg rounded-2xl px-4 py-2.5 border-0 focus:outline-none focus:ring-2 focus:ring-gray-400">
 
                <input type="password" name="password" placeholder="Insert Password" required
                       class="w-full bg-[#6b6b6b] text-white placeholder-white font-semibold text-base sm:text-lg rounded-2xl px-4 py-2.5 border-0 focus:outline-none focus:ring-2 focus:ring-gray-400">
 
                <div class="flex justify-center pt-4">
                    <button type="submit" name="daftar" value="daftar"
                            class="bg-[#6b6b6b] hover:bg-[#555] text-white font-semibold text-base sm:text-lg rounded-full px-10 py-1.5 transition">
                        Register
                    </button>
                </div>
 
                <p class="text-center text-sm sm:text-base text-gray-900">
                    already have account?
                    <a href="loginpage.php" class="text-[#8a8fb0] hover:underline">Login</a>
                </p>
            </form>
        </div>
    </main>
 
</body>
</html>
 