<?php
session_start();
require 'config/koneksi.php';

header('Content-Type: application/json');

// Some clients (e.g., navigator.sendBeacon) may send bodies that don't populate $_POST
// depending on Content-Type. If $_POST is empty, try parsing raw input as query string.
if (empty($_POST)) {
    $raw = file_get_contents('php://input');
    if (is_string($raw) && $raw !== '') {
        $parsed = [];
        parse_str($raw, $parsed);
        if (!empty($parsed) && is_array($parsed)) {
            $_POST = $parsed;
        }
    }
}

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = intval($_SESSION['user_id'] ?? 0);
$action = $_POST['action'] ?? '';

function ensure_reading_progress_table($conn) {
    // Create table if missing (prevents timer reset when table wasn't migrated)
    $sql = "CREATE TABLE IF NOT EXISTS reading_progress (\n"
         . "  id INT(11) NOT NULL AUTO_INCREMENT,\n"
         . "  user_id INT(11) NOT NULL,\n"
         . "  book_id INT(11) NOT NULL,\n"
         . "  reading_seconds INT(11) DEFAULT 0,\n"
         . "  last_position TEXT DEFAULT NULL,\n"
         . "  last_read TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n"
         . "  PRIMARY KEY (id),\n"
         . "  UNIQUE KEY user_book (user_id, book_id)\n"
         . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
}

// 0. Get Reading Time untuk buku tertentu (per book)
if ($action == 'get_time') {
    ensure_reading_progress_table($conn);
    $user_id = intval($user_id);
    $book_id = intval($_POST['book_id'] ?? 0);
    
    $query = mysqli_query($conn, "SELECT reading_seconds FROM reading_progress WHERE user_id = $user_id AND book_id = $book_id");
    if ($query === false) {
        echo json_encode(['status' => 'error', 'message' => 'Query failed']);
        exit;
    }
    $row = mysqli_fetch_assoc($query);
    $reading_seconds = $row ? intval($row['reading_seconds']) : 0;
    
    echo json_encode(['status' => 'success', 'reading_seconds' => $reading_seconds]);
    exit;
}

// 1. Simpan Waktu Baca PER BUKU (Update reading_progress)
if ($action == 'update_time') {
    ensure_reading_progress_table($conn);
    $seconds = intval($_POST['seconds'] ?? 30);
    $book_id = intval($_POST['book_id'] ?? 0);
    
    if ($book_id > 0) {
        // Keep the books table in sync for dashboard sorting/status
        // (dashboard.php orders by books.last_read and marks recently read)
        mysqli_query($conn, "UPDATE books SET last_read = NOW() WHERE id = $book_id");

        // Cek apakah sudah ada record
        $check = mysqli_query($conn, "SELECT * FROM reading_progress WHERE user_id = $user_id AND book_id = $book_id");
        
        if (mysqli_num_rows($check) == 0) {
            // Insert baru
            mysqli_query($conn, "INSERT INTO reading_progress (user_id, book_id, reading_seconds, last_read) VALUES ($user_id, $book_id, $seconds, NOW())");
        } else {
            // Update existing
            mysqli_query($conn, "UPDATE reading_progress SET reading_seconds = reading_seconds + $seconds, last_read = NOW() WHERE user_id = $user_id AND book_id = $book_id");
        }
        
        // Tetap update user_stats untuk total semua buku
        $check_stats = mysqli_query($conn, "SELECT * FROM user_stats WHERE user_id = $user_id");
        if (mysqli_num_rows($check_stats) == 0) {
            mysqli_query($conn, "INSERT INTO user_stats (user_id, total_seconds, books_opened) VALUES ($user_id, $seconds, 1)");
        } else {
            mysqli_query($conn, "UPDATE user_stats SET total_seconds = total_seconds + $seconds WHERE user_id = $user_id");
        }
    }
    
    echo json_encode(['status' => 'success', 'seconds_added' => $seconds]);
    exit;
}

// 2. Simpan Bookmark
if ($action == 'save_bookmark') {
    $book_id = $_POST['book_id'];
    $cfi = $_POST['cfi'];
    $label = $_POST['label'];
    
    $query = "INSERT INTO bookmarks (user_id, book_id, cfi_range, label) VALUES ($user_id, $book_id, '$cfi', '$label')";
    mysqli_query($conn, $query);
    echo json_encode(['status' => 'success']);
}

// 3. Simpan Catatan/Highlight
if ($action == 'save_note') {
    $book_id = $_POST['book_id'];
    $cfi = $_POST['cfi'];
    $text = $_POST['text'];
    
    $query = "INSERT INTO notes (user_id, book_id, cfi_range, note_text) VALUES ($user_id, $book_id, '$cfi', '$text')";
    mysqli_query($conn, $query);
    echo json_encode(['status' => 'success']);
}
?>