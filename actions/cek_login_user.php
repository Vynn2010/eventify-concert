<?php
session_start();
$loginBerhasil = $_SESSION["login_berhasil"];
if(!isset($loginBerhasil) || $loginBerhasil !== true) {
    header("Location: ../pages/user/loginpage_user.php");
    exit();
}
?>