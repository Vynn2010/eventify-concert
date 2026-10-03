<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "concert_booking";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Koneksi Gagal". mysqli_connect_error());
}
?>