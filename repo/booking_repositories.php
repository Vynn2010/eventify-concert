<?php

class booking_repositories
{
    public static function CreateBooking($conn, $concert_id)
    {
        $stmt = mysqli_prepare($conn, "SELECT id, title, artist, venue, concert_date, img, price FROM concerts WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $concert_id);
        mysqli_stmt_execute($stmt);
        $konser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $konser;
    }

    public static function findSelectedSeats($conn, $seat_ids, $concert_id)
    {
        $placeholders = implode(',', array_fill(0, count($seat_ids), '?'));
        $tipe = 'i' . str_repeat('i', count($seat_ids));
        $sql  = "SELECT id, seat_number, price, seat_status FROM seats
                WHERE concert_id = ? AND id IN ($placeholders)
                ORDER BY LEFT(seat_number, 1), LENGTH(seat_number), seat_number";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $tipe, $concert_id, ...$seat_ids);
        mysqli_stmt_execute($stmt);
        $hasil_kursi = mysqli_stmt_get_result($stmt);
        return $hasil_kursi;
    }
}

class UserSeat
{
    public static function findAllUserConcert($conn, $id)
    {
        // ---------- KONSER ----------
        $stmt = mysqli_prepare($conn, "SELECT id, title, artist, venue, concert_date FROM concerts WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $konser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $konser;
    }

    public static function findAllUserSeat($conn, $id)
    {
        // ---------- KURSI (beserta nama kategori: VIP / Reguler / Tribune) ----------
        $stmt = mysqli_prepare($conn, "SELECT s.id, s.seat_number, s.category_id, s.price, s.seat_status,
                                COALESCE(ca.category_name, 'Umum') AS category_name
                                FROM seats s
                                LEFT JOIN categories ca ON ca.id = s.category_id
                                WHERE s.concert_id = ?
                                ORDER BY LEFT(s.seat_number, 1), LENGTH(s.seat_number), s.seat_number");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result_seats = mysqli_stmt_get_result($stmt);
        return $result_seats;
    }


}

class UserTicket
{
    public static function findAllUserTicket($conn, $ticket_id, $user_id)
    {
        $sql = "SELECT t.id, t.qr_token, t.status, s.seat_number,
                c.title, c.artist, c.venue, c.concert_date, c.img
            FROM tickets t
            JOIN seats s    ON s.id = t.seat_id
            JOIN concerts c ON c.id = t.concert_id
            WHERE t.user_id = ?";
        if ($ticket_id > 0) {
            $sql .= " AND t.id = ?";
        }
        $sql .= " ORDER BY t.id DESC";

        $stmt = mysqli_prepare($conn, $sql);
        if ($ticket_id > 0) {
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $ticket_id);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
        }
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        return $hasil;
    }

}

class AdminScan
{
    public static function findTicket($conn)
    {
        // Pilihan konser (opsional) agar tiket konser lain ditolak
        $concerts = mysqli_query($conn, "SELECT id, title, concert_date FROM concerts ORDER BY concert_date DESC");
        return $concerts;
    }

    public static function findUserTicketfromAdmin($conn)
    {
        // ---------- 5 TIKET TERBARU ----------
        $latest_tickets = mysqli_query($conn,
            "SELECT t.id, t.status, c.title, s.seat_number
            FROM tickets t
            JOIN concerts c ON c.id = t.concert_id
            JOIN seats s    ON s.id = t.seat_id
            ORDER BY t.id DESC
            LIMIT 5"
        );
        return $latest_tickets;
    }

    public static function findOrderSummary()
    {
        $revenue_sql = "SELECT COALESCE(SUM(c.price + s.price), 0)
                FROM tickets t
                JOIN concerts c ON c.id = t.concert_id
                JOIN seats s    ON s.id = t.seat_id";
        return $revenue_sql;
    }

    public static function findOrderData($from_sql, $where_sql, $per_page, $offset)
    {
        $sql = "SELECT t.id, t.status, t.created_at, u.email, c.title, s.seat_number,
               s.price AS seat_price, c.price AS concert_price, cat.category_name
        $from_sql $where_sql
        ORDER BY t.id DESC
        LIMIT $per_page OFFSET $offset";
        return $sql;
    }

    public static function createBookingTotal($conn)
    {
        $sql = "SELECT COUNT(*) FROM tickets";
        $res = mysqli_query($conn, $sql);
        $row = mysqli_fetch_row($res);
            return (float)($row[0] ?? 0);
    
    }

    public static function createRevenueTotal($conn, $revenue_sql)
    {
        // Helper: ambil satu angka dari query tanpa parameter
        function scalar(mysqli $conn, string $total_revenue): float {
            $res = mysqli_query($conn, $total_revenue);
            $row = mysqli_fetch_row($res);
            return (float)($row[0] ?? 0);
        }
        $total_revenue  = scalar($conn, $revenue_sql);
        return $total_revenue;
    }
}