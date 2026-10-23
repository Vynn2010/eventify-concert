<?php
require_once __DIR__ . "/cek_koneksi.php";
include __DIR__ . "/cek_login_admin.php"; // pastikan hanya admin yang bisa akses
 
$form_page = "../pages/admin/seat_generate.php";
 
function go_back(string $url, array $params): void {
    header("Location: " . $url . "?" . http_build_query($params));
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $form_page");
    exit;
}
 
// ---------- AMBIL & VALIDASI INPUT ----------
$concert_id     = (int)($_POST['concert_id'] ?? 0);
$category_id    = (int)($_POST['category_id'] ?? 0);
$price          = (int)($_POST['price'] ?? 0);
$seats_per_row  = (int)($_POST['seats_per_row'] ?? 0);
$rows_input     = strtoupper(trim($_POST['rows'] ?? ''));
$disabled_input = strtoupper(trim($_POST['disabled_seats'] ?? ''));
 
if ($concert_id <= 0 || $category_id <= 0 || $price < 0 || $seats_per_row < 1 || $seats_per_row > 100) {
    go_back($form_page, ['status' => 'error', 'msg' => 'Data form tidak valid.']);
}
 
// ---------- PARSE ROWS: "A-F" atau "A,B,C" ----------
$rows = [];
if (preg_match('/^([A-Z])\s*-\s*([A-Z])$/', $rows_input, $m)) {
    $rows = range($m[1], $m[2]);
} else {
    foreach (explode(',', $rows_input) as $r) {
        $r = trim($r);
        if ($r === '') continue;
        if (!preg_match('/^[A-Z]{1,2}$/', $r)) {
            go_back($form_page, ['status' => 'error', 'msg' => "Baris '$r' tidak valid."]);
        }
        $rows[] = $r;
    }
}
$rows = array_values(array_unique($rows));
 
if (empty($rows) || count($rows) * $seats_per_row > 1000) {
    go_back($form_page, ['status' => 'error', 'msg' => 'Baris kosong atau total kursi lebih dari 1000.']);
}
 
// ---------- PARSE DISABLED SEATS: "A5, A6, B10" ----------
$disabled_lookup = [];
foreach (explode(',', $disabled_input) as $d) {
    $d = trim($d);
    if ($d === '') continue;
    if (!preg_match('/^[A-Z]{1,2}\d+$/', $d)) {
        go_back($form_page, ['status' => 'error', 'msg' => "Kursi disabled '$d' tidak valid (contoh: A5)."]);
    }
    $disabled_lookup[$d] = true;
}
 
// ---------- INSERT DALAM SATU TRANSAKSI ----------
$sql  = "INSERT INTO seats (concert_id, seat_number, category_id, price, seat_status)
         VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
$total          = 0;
$disabled_total = 0;
 
mysqli_begin_transaction($conn);
try {
    foreach ($rows as $r) {
        for ($i = 1; $i <= $seats_per_row; $i++) {
            $seat_number = $r . $i;
            $seat_status = isset($disabled_lookup[$seat_number]) ? 'Disabled' : 'Available';
 
            mysqli_stmt_bind_param($stmt, "isiis", $concert_id, $seat_number, $category_id, $price, $seat_status);
            mysqli_stmt_execute($stmt);
 
            $total++;
            if ($seat_status === 'Disabled') $disabled_total++;
        }
    }
    mysqli_commit($conn);
    go_back($form_page, ['status' => 'ok', 'total' => $total, 'disabled' => $disabled_total]);
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    // Kode 1062 = duplikat (kena unique key concert_id + seat_number)
    $msg = ($e->getCode() === 1062)
        ? 'Sebagian kursi sudah ada untuk konser ini. Tidak ada yang disimpan.'
        : 'Gagal menyimpan kursi: ' . $e->getMessage();
    go_back($form_page, ['status' => 'error', 'msg' => $msg]);
}
 