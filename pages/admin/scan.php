<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";

$token = $_POST['token'] ?? '';
$stmt  = mysqli_prepare($conn, "SELECT id, status FROM tickets WHERE qr_token = ?");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$tiket = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$tiket)                        { echo "Tiket tidak ditemukan"; }
elseif ($tiket['status'] === 'used') { echo "Tiket sudah dipakai"; }
else {
    mysqli_query($conn, "UPDATE tickets SET status = 'used' WHERE id = " . (int)$tiket['id']);
    echo "Tiket valid, silakan masuk";
}
?>