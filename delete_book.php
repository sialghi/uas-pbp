<?php
session_start();
require 'config/koneksi.php';

if (!isset($_SESSION['login'])) { header("Location: index.php"); exit; }
if (($_SESSION['role'] ?? 'mahasiswa') !== 'admin') { header("Location: dashboard.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { header("Location: dashboard.php"); exit; }

$res = mysqli_query($conn, "SELECT * FROM books WHERE id = $id");
$book = mysqli_fetch_assoc($res);
if ($book) {
    // Delete files
    if (!empty($book['cover']) && $book['cover'] !== 'default_book.png' && $book['cover'] !== 'default_cover.jpg') {
        $coverPath = 'uploads/covers/' . $book['cover'];
        if (file_exists($coverPath)) { @unlink($coverPath); }
    }
    if (!empty($book['file_path'])) {
        $epubPath = 'uploads/files/' . $book['file_path'];
        if (file_exists($epubPath)) { @unlink($epubPath); }
    }

    mysqli_query($conn, "DELETE FROM books WHERE id = $id");
    // Optional cleanup (ignore errors if tables don't exist)
    mysqli_query($conn, "DELETE FROM reading_progress WHERE book_id = $id");
    mysqli_query($conn, "DELETE FROM bookmarks WHERE book_id = $id");
    mysqli_query($conn, "DELETE FROM notes WHERE book_id = $id");
}

header("Location: dashboard.php");
exit;
