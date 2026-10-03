<?php
// Simpan di folder actions/. Pakai file ini di SEMUA tempat yang butuh sesi,
// jangan memanggil session_start() langsung.
if (session_status() === PHP_SESSION_NONE) {
    $lifetime = 60 * 60 * 24 * 30; // 30 hari
    ini_set('session.gc_maxlifetime', $lifetime);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // aktifkan kalau sudah memakai HTTPS
    ]);
    session_start();
}