<?php
include "../../actions/cek_koneksi.php";
include "../../actions/cek_login_user.php";

$sql = "SELECT id,  ,genre,venue,concert_date,concert_description,concert_status FROM concerts ORDER BY id ASC";
$result = mysqli_query($conn, $sql);

if(!$result){
    die("Gagal". mysqli_connect_error());
}
?>

<?php
$pesan = "";


?>