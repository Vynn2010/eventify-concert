<?php
session_start();
require_once __DIR__ . "/../../actions/cek_koneksi.php";
 
$message = "";
 
if (isset($_POST["masuk"])) {
 
    $email     = trim($_POST["email"]);
    $password = $_POST["password"];
 
    if ($email == "" || $password == "") {
        $message = "Email dan password tidak boleh kosong";
    } else {
        // Pastikan nama kolom sesuai tabel users kamu (role atau roles)
        $sql = mysqli_prepare($conn, "SELECT * FROM users WHERE email=? AND passwords=? AND role='admin'");
        mysqli_stmt_bind_param($sql, "ss", $email, $password);
        mysqli_stmt_execute($sql);
        $result = mysqli_stmt_get_result($sql);
 
        if (mysqli_num_rows($result) > 0) {
            $_SESSION["login_berhasil_admin"] = true;
            $_SESSION["email"] = $email;
            mysqli_stmt_close($sql);
            header("Location: home_page_admin.php");
            exit();
        } else {
            $_SESSION["login_berhasil_admin"] = false;
            $message = "Email atau password salah.";
        }
 
        mysqli_stmt_close($sql);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body>
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
            <a href="#" class="flex items-center mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
                Login
            </a>
            <div class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                        Sign in to your account
                    </h1>
 
                    <?php if ($message !== ""): ?>
                        <div class="bg-red-100 text-red-800 rounded-lg p-3 text-sm">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
 
                    <form class="space-y-4 md:space-y-6" method="POST">
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your email</label>
                            <input type="text" id="email" name="email" required
                                   class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   placeholder="userguest@11233">
                        </div>
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
                            <input type="password" id="password" name="password" required placeholder="••••••••"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div class="flex items-center">
                            <input id="remember" type="checkbox"
                                   class="w-4 h-4 border border-gray-300 rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600">
                            <label for="remember" class="ml-3 text-sm text-gray-500 dark:text-gray-300">Remember me</label>
                        </div>
                        <button type="submit" name="masuk" value="masuk"
                                class="w-full text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Login
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
 