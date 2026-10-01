<?php
session_start();
$loginBerhasil = $_SESSION["login_berhasil_admin"];
if(!isset($loginBerhasil) || $loginBerhasil !== true) {
    header("Location: ../pages/user/loginpage_admin.php");
    exit();
}
?>