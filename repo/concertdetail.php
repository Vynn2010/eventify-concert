<?php

class Concertdetail 
{
    public static function findConcertNameById($conn)
    {
        $id= $_GET['id'];
        $sql = "SELECT * FROM concerts WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }

    public static function findConcertById($conn)
    {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "SELECT * FROM concerts WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $konser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $konser;
    }

    public static function CreateConcertByAdmin()
    {
        $sql = "SELECT c.id, c.title, c.artist, c.venue, c.concert_date, c.img, c.price, c.concert_status,
               (SELECT COUNT(*) FROM seats s WHERE s.concert_id = c.id) AS total_seats,
               (SELECT COUNT(*) FROM seats s WHERE s.concert_id = c.id AND s.seat_status = 'Available') AS free_seats,
               (SELECT COUNT(*) FROM tickets t WHERE t.concert_id = c.id) AS sold
        FROM concerts c";
        return $sql;
    }

}

class recommendation 
{
    public static function createRecommendation($conn, $kolom)
    {
        $recommendation = ambil_konser($conn, "SELECT $kolom FROM concerts c
                                       WHERE c.concert_date >= NOW()
                                       ORDER BY c.concert_date ASC LIMIT 4");
        return $recommendation;
    }

    public static function createHots($conn, $kolom)
    {
        $hots = ambil_konser($conn, "SELECT $kolom, COUNT(s.id) AS terjual
                             FROM concerts c
                             LEFT JOIN seats s ON s.concert_id = c.id AND s.seat_status <> 'Available'
                             GROUP BY c.id
                             ORDER BY terjual DESC, c.concert_date ASC LIMIT 4");
        return $hots;
    }

    public static function createNear($conn, $kolom)
    {
        $near_list = ambil_konser($conn, "SELECT $kolom FROM concerts c
                                  WHERE c.concert_date >= NOW()
                                  ORDER BY c.concert_date ASC LIMIT 1");
        return $near_list;
    }
}

class AdminCreateGenre
{
    public static function Creategenres($conn, $has_genre_column, $q)
    {
        
        if ($has_genre_column) {
            $sql = "SELECT g.id, g.genre_name,
                        (SELECT COUNT(*) FROM concerts c WHERE c.genre_id = g.id) AS concert_count,
                        (SELECT COUNT(*) FROM concerts c WHERE c.genre_id = g.id AND c.concert_date >= CURDATE()) AS upcoming_count,
                        (SELECT COUNT(*) FROM tickets t JOIN concerts c ON c.id = t.concert_id WHERE c.genre_id = g.id) AS sold_count
                    FROM genres g";
        } else {
            $sql = "SELECT g.id, g.genre_name, 0 AS concert_count, 0 AS upcoming_count, 0 AS sold_count FROM genres g";
        }
        if ($q !== '') {
            $sql .= " WHERE g.genre_name LIKE ?";
        }
        $sql .= " ORDER BY g.genre_name";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($q !== '') {
            $like = "%$q%";
            mysqli_stmt_bind_param($stmt, "s", $like);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $genres = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $genres[] = $row;
        }
        return $genres;
    }

}