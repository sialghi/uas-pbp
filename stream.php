<?php
// Matikan semua output buffering
while (ob_get_level()) {
    ob_end_clean();
}

require 'config/koneksi.php';
session_start();

// 1. Cek Login
if (!isset($_SESSION['login'])) {
    http_response_code(403);
    die("Akses ditolak");
}

// 2. Validasi ID
if (!isset($_GET['id']) || empty($_GET['id'])) { 
    die("ID Kosong"); 
}
$id = (int)$_GET['id'];

// 3. Ambil Data dari Database
$query = mysqli_query($conn, "SELECT * FROM books WHERE id = $id");
$book = mysqli_fetch_assoc($query);

if (!$book) { die("Buku tidak ditemukan di Database"); }

$filepath = 'uploads/files/' . $book['file_path'];

// 4. Cek Fisik File
if (!file_exists($filepath)) {
    die("ERROR FATAL: File tidak ada di '$filepath'.");
}

// 5. KIRIM HEADER
$filesize = filesize($filepath);
$filename = basename($filepath);

header('Content-Type: application/epub+zip');
header('Content-Length: ' . $filesize);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');
header('Access-Control-Allow-Origin: *');

// 6. Kirim File
readfile($filepath);
exit;
?>