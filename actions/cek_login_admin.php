<?php
session_start();
$loginBerhasil = $_SESSION["login_berhasil_admin"] ?? false;
if ($loginBerhasil !== true) {
    header("Location: /eventify/pages/admin/loginpage_admin.php");
    exit();
}
?>