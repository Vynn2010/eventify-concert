<?php
class HomePageUser 
{
    public static function findUserHomePage($conn, $where, $types, $params) {
        $sql = "SELECT id, title, artist, venue, concert_date, img, price FROM concerts";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY concert_date ASC";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $concerts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $concerts[] = $row;
        }
        return $concerts;
    }

    public static function findGenres($conn) {
        $genres = [];
        $res = mysqli_query($conn, "SELECT id, genre_name FROM genres ORDER BY genre_name"); // SESUAIKAN: tabel/kolom genre
        while ($res && ($g = mysqli_fetch_assoc($res))) {
            $genres[] = $g;
        }
        return $genres;
    }

    public static function CreateBannerHomePage($conn) {
        $res = mysqli_query($conn,
        "SELECT id, title, artist, venue, concert_date, img, concert_description
        FROM concerts WHERE concert_date >= NOW()
        ORDER BY concert_date ASC LIMIT 1");
        $next_concert = $res ? mysqli_fetch_assoc($res) : null;
        return $next_concert;
    }
}

class Company 
{
    public static function findCompanyInfo($conn) {
       $result_near = mysqli_query($conn, "SELECT id, title, artist, venue, concert_date, concert_description, img
                                    FROM concerts WHERE concert_date >= NOW()
                                    ORDER BY concert_date ASC LIMIT 1");
        $near = $result_near ? mysqli_fetch_assoc($result_near) : null;
        return $near;
    }
}

class UserLogin
{
    public static function findUserLogin($conn, $email, $passwords){
        $sql = mysqli_prepare($conn, "SELECT id, email FROM users WHERE email=? AND passwords=? AND role='user'");
        mysqli_stmt_bind_param($sql, "ss", $email, $passwords);
        mysqli_stmt_execute($sql);
        $result = mysqli_stmt_get_result($sql);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($sql);
        return $user;
    }
}

class UserRegister
{
    public static function findUserRegister($conn, $email){
        $cek = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
        mysqli_stmt_bind_param($cek, "s", $email);
        mysqli_stmt_execute($cek);
        $hasil = mysqli_stmt_get_result($cek);
        return $hasil;
    }

    public static function getUserRegister($conn, $email, $passwords){
        $role = "user";
        $ins = mysqli_prepare($conn, "INSERT INTO users (email, passwords, role) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins, "sss", $email, $passwords, $role);
        return $ins;
    }
}

class UserProfile
{
    public static function findUserStatusTicket($conn, $placeholders, $types, $user_id, $ticket_ids) {
        $stmt = mysqli_prepare($conn,
            "UPDATE seats s
             JOIN tickets t ON t.seat_id = s.id
             SET s.seat_status = 'Available'
             WHERE t.user_id = ? AND t.id IN ($placeholders)");
        mysqli_stmt_bind_param($stmt, $types, $user_id, ...$ticket_ids);
        mysqli_stmt_execute($stmt);
        return $stmt;
    }

    public static function deleteUserStatusTicket($conn, $placeholders, $types, $user_id, $ticket_ids) {
        $stmt = mysqli_prepare($conn,
            "DELETE FROM tickets WHERE user_id = ? AND id IN ($placeholders)");
        mysqli_stmt_bind_param($stmt, $types, $user_id, ...$ticket_ids);
        mysqli_stmt_execute($stmt);
        $cancelled_count = mysqli_stmt_affected_rows($stmt);
        return $cancelled_count;
    }

    public static function findUserPassword($conn, $user_id){
        $stmt = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $user;
    }

    public static function findUserHistory($conn, $user_id){
        $stmt = mysqli_prepare($conn, "SELECT t.id, t.created_at, s.seat_number, s.price AS harga_kursi,
                               c.title, c.price AS harga_konser, c.img FROM tickets t JOIN seats s    ON s.id = t.seat_id
                               JOIN concerts c ON c.id = t.concert_id WHERE t.user_id = ? ORDER BY t.id DESC");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);
        return $hasil;
    }

    public static function findUserProfile($conn){
        $r = mysqli_query($conn, "SELECT title, img FROM concerts
                              WHERE img IS NOT NULL AND img <> ''
                              ORDER BY (concert_date >= NOW()) DESC, concert_date ASC LIMIT 1");
        return $r;
    }
}

class AdminLogin
{
    public static function findAdminAccount($conn, $email, $password)
    {
        // Pastikan nama kolom sesuai tabel users kamu (role atau roles)
        $sql = mysqli_prepare($conn, "SELECT * FROM users WHERE email=? AND passwords=? AND role='admin'");
        mysqli_stmt_bind_param($sql, "ss", $email, $password);
        mysqli_stmt_execute($sql);
        
        return $sql;
       
    }
}