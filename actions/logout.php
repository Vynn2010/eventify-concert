<?php
    include "../../loginpage_admin.php";
    include "../../loginpage_user.php";
    session_start();
    $_SESSION = array();
    session_destroy();
    header("Location: ../pages/admin/loginpage_admin.php");
    header("Location: ../pages/user/loginpage_user.php");
    exit;
?>