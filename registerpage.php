<?php
include "cek_koneksi.php";

$pesan ="";
if(isset($_POST["simpan"])) {

    $email = $_POST["email"];
    $passwords = $_POST["passwords"];

    if ($email == "" && $passwords ==""){
        $pesan = "email dan password tidak boleh kosong";

    }   else {
            $sql = mysqli_prepare($conn,"INSERT INTO users (email,passwords) VALUES (?,?)");
            mysqli_stmt_bind_param($sql,"ss", $email, $passwords);

            if (mysqli_stmt_execute($sql)) {
                $pesan = "register berhasil.";
            } else {
                $pesan = "register gagal:" . mysqli_error($conn);
            }

            mysqli_stmt_close($sql);
    }
}
?>
     <h2>REGISTER</h2>

        <?php echo $pesan ?>

        <form method="POST">
            <label>Email:</label><br>
            <input type="text" name="email" required><br><br>
            <label>Password:</label><br>
            <input type="text" name="passwords" required><br><br>
            <input type="submit" name="simpan" value="simpan">
        </form>

<?php
mysqli_close($conn);
?>