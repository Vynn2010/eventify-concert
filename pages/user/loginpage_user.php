<?php
require_once __DIR__ . '/../../actions/session_init.php';
include "../../actions/cek_koneksi.php";
 
// Sudah login sebelumnya? Langsung ke halaman utama, tidak perlu login lagi
if (!empty($_SESSION["user_id"])) {
    header("Location: mainpage.php");
    exit;
}
 
$pesan = "";
 
if (isset($_POST["masuk"])) {
    $email     = trim($_POST["email"] ?? "");
    $passwords = $_POST["password"] ?? "";
 
    if ($email === "" || $passwords === "") {
        $pesan = "Email dan password tidak boleh kosong.";
    } else {
        $sql = mysqli_prepare($conn, "SELECT id, email FROM users WHERE email=? AND passwords=? AND role='user'");
        mysqli_stmt_bind_param($sql, "ss", $email, $passwords);
        mysqli_stmt_execute($sql);
        $result = mysqli_stmt_get_result($sql);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($sql);
 
        if ($user) {
            session_regenerate_id(true);
            $_SESSION["login_berhasil"] = true;
            $_SESSION["user_id"]        = (int)$user["id"];   // dipakai profile, ticket, booking
            $_SESSION["email"]          = $user["email"];
            header("Location: mainpage.php");
            exit;
        } else {
            $_SESSION["login_berhasil"] = false;
            $pesan = "Email atau password salah.";
        }
    }
}
 
$hide_auth = true; // sembunyikan tombol Login/Register di navbar
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Eventify</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white min-h-screen text-gray-800 antialiased">
 
    <?php include "../../components/landing/header.php"; ?>
 
    <main class="px-4 py-8 sm:py-12">
        <div class="w-full max-w-xl mx-auto bg-[#d9d9d9] rounded-[30px] px-6 sm:px-10 py-8 sm:py-10">
 
            <h1 class="text-2xl sm:text-3xl font-bold text-center text-black mb-8">Login Page</h1>
 
            <?php if ($pesan !== ""): ?>
                <p class="mb-4 text-center text-sm text-red-600"><?= htmlspecialchars($pesan) ?></p>
            <?php endif; ?>
 
            <form method="POST" class="space-y-5">
                <input type="email" name="email" placeholder="Insert Email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="w-full bg-[#6b6b6b] text-white placeholder-white font-semibold text-base sm:text-lg rounded-2xl px-4 py-2.5 border-0 focus:outline-none focus:ring-2 focus:ring-gray-400">
 
                <input type="password" name="password" placeholder="Insert Password" required
                       class="w-full bg-[#6b6b6b] text-white placeholder-white font-semibold text-base sm:text-lg rounded-2xl px-4 py-2.5 border-0 focus:outline-none focus:ring-2 focus:ring-gray-400">
 
                <div class="flex justify-center pt-4">
                    <button type="submit" name="masuk" value="masuk"
                            class="bg-[#6b6b6b] hover:bg-[#555] text-white font-semibold text-base sm:text-lg rounded-full px-12 py-1.5 transition">
                        LOGIN
                    </button>
                </div>
 
                <p class="text-center text-sm sm:text-base text-gray-900">
                    didn't have account?
                    <a href="registerpage.php" class="text-[#8a8fb0] hover:underline">Register</a>
                </p>
            </form>
        </div>
    </main>
 
</body>
</html>
<?php
    mysqli_close($conn);
?>