<?php
$host = "127.0.0.1";
$user = "root";      // Pakai user baru yang tadi dibuat
$pass = "";        // Password user baru
$db   = "epub_fst";
$port = 3306;         // Port default MySQL XAMPP

// Tambahkan parameter port di akhir
$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}