<?php
// Sistem Multi-Bahasa untuk FST Reader
// Mendukung Bahasa Indonesia dan Inggris

if (!isset($_SESSION)) {
    session_start();
}

// Set default language jika belum ada
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'id'; // Default: Indonesia
}

// Handler untuk switch bahasa
if (isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    // Redirect ke halaman yang sama tanpa parameter lang
    $redirect = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $redirect");
    exit;
}

// Array terjemahan
$lang = [
    'id' => [
        // Halaman Login/Register
        'welcome' => 'Selamat Datang',
        'login_subtitle' => 'Masuk atau Daftar akun baru',
        'login' => 'Masuk',
        'register' => 'Daftar',
        'nim' => 'NIM',
        'password' => 'Password',
        'role' => 'Role',
        'full_name' => 'Nama Lengkap',
        'register_account' => 'Daftar Akun',
        'error_login' => 'NIM atau Password salah!',
        'success_register' => 'Daftar berhasil! Silakan login.',
        'error_register' => 'NIM sudah terdaftar!',
        'app_title' => 'ePub Reader FST',
        'app_subtitle' => 'Platform baca buku digital interaktif untuk mahasiswa Fakultas Sains dan Teknologi.',
        
        // Sidebar
        'library' => 'Perpustakaan',
        'upload_book' => 'Upload Buku',
        'settings' => 'Pengaturan',
        'logout' => 'Keluar',
        'student' => 'Mahasiswa',
        'lecturer' => 'Dosen',
        
        // Dashboard
        'dashboard' => 'Dashboard',
        'search_books' => 'Cari buku...',
        'all_categories' => 'Semua',
        'books_count' => 'buku',
        'no_books' => 'Belum ada buku. Upload buku pertama Anda!',
        'read_book' => 'Baca Buku',
        'delete' => 'Hapus',
        'confirm_delete' => 'Yakin ingin menghapus buku ini?',
        
        // Statistik
        'reading_stats' => 'Statistik Membaca',
        'books_read' => 'Buku Dibaca',
        'reading_time' => 'Waktu Baca',
        'bookmarks' => 'Bookmark',
        'notes' => 'Catatan',
        'hours' => 'Jam',
        'minutes' => 'Menit',
        
        // Upload
        'upload_new_book' => 'Upload Buku Baru',
        'book_title' => 'Judul Buku',
        'author' => 'Penulis',
        'category' => 'Kategori',
        'cover_image' => 'Gambar Cover (opsional)',
        'epub_file' => 'File EPUB',
        'upload' => 'Upload',
        'success_upload' => 'Buku berhasil diupload!',
        'error_upload' => 'Gagal mengupload buku!',
        
        // Settings
        'account_settings' => 'Pengaturan Akun',
        'profile_photo' => 'Foto Profil',
        'change_photo' => 'Ganti Foto',
        'save_changes' => 'Simpan Perubahan',
        'success_update' => 'Profil berhasil diupdate!',
        'language' => 'Bahasa',
        'dark_mode' => 'Mode Gelap',
        
        // Reader
        'table_of_contents' => 'Daftar Isi',
        'my_bookmarks' => 'Bookmark Saya',
        'my_notes' => 'Catatan Saya',
        'add_bookmark' => 'Tambah Bookmark',
        'add_note' => 'Tambah Catatan',
        'font_size' => 'Ukuran Font',
        'loading' => 'Memuat buku...',
        
        // Kategori
        'fiction' => 'Fiksi',
        'non_fiction' => 'Non-Fiksi',
        'science' => 'Sains',
        'technology' => 'Teknologi',
        'history' => 'Sejarah',
        'religion' => 'Agama',
        'other' => 'Lainnya',
    ],
    
    'en' => [
        // Login/Register Page
        'welcome' => 'Welcome',
        'login_subtitle' => 'Sign in or Create a new account',
        'login' => 'Sign In',
        'register' => 'Sign Up',
        'nim' => 'Student ID',
        'password' => 'Password',
        'role' => 'Role',
        'full_name' => 'Full Name',
        'register_account' => 'Create Account',
        'error_login' => 'Student ID or Password is incorrect!',
        'success_register' => 'Registration successful! Please login.',
        'error_register' => 'Student ID already registered!',
        'app_title' => 'FST ePub Reader',
        'app_subtitle' => 'Interactive digital book reading platform for Faculty of Science and Technology students.',
        
        // Sidebar
        'library' => 'Library',
        'upload_book' => 'Upload Book',
        'settings' => 'Settings',
        'logout' => 'Logout',
        'student' => 'Student',
        'lecturer' => 'Lecturer',
        
        // Dashboard
        'dashboard' => 'Dashboard',
        'search_books' => 'Search books...',
        'all_categories' => 'All',
        'books_count' => 'books',
        'no_books' => 'No books yet. Upload your first book!',
        'read_book' => 'Read Book',
        'delete' => 'Delete',
        'confirm_delete' => 'Are you sure you want to delete this book?',
        
        // Statistics
        'reading_stats' => 'Reading Statistics',
        'books_read' => 'Books Read',
        'reading_time' => 'Reading Time',
        'bookmarks' => 'Bookmarks',
        'notes' => 'Notes',
        'hours' => 'Hours',
        'minutes' => 'Minutes',
        
        // Upload
        'upload_new_book' => 'Upload New Book',
        'book_title' => 'Book Title',
        'author' => 'Author',
        'category' => 'Category',
        'cover_image' => 'Cover Image (optional)',
        'epub_file' => 'EPUB File',
        'upload' => 'Upload',
        'success_upload' => 'Book uploaded successfully!',
        'error_upload' => 'Failed to upload book!',
        
        // Settings
        'account_settings' => 'Account Settings',
        'profile_photo' => 'Profile Photo',
        'change_photo' => 'Change Photo',
        'save_changes' => 'Save Changes',
        'success_update' => 'Profile updated successfully!',
        'language' => 'Language',
        'dark_mode' => 'Dark Mode',
        
        // Reader
        'table_of_contents' => 'Table of Contents',
        'my_bookmarks' => 'My Bookmarks',
        'my_notes' => 'My Notes',
        'add_bookmark' => 'Add Bookmark',
        'add_note' => 'Add Note',
        'font_size' => 'Font Size',
        'loading' => 'Loading book...',
        
        // Categories
        'fiction' => 'Fiction',
        'non_fiction' => 'Non-Fiction',
        'science' => 'Science',
        'technology' => 'Technology',
        'history' => 'History',
        'religion' => 'Religion',
        'other' => 'Other',
    ]
];

// Fungsi helper untuk mendapatkan teks terjemahan
function t($key) {
    global $lang;
    $current_lang = $_SESSION['lang'] ?? 'id';
    return $lang[$current_lang][$key] ?? $key;
}

// Fungsi untuk mendapatkan bahasa saat ini
function current_lang() {
    return $_SESSION['lang'] ?? 'id';
}

// Fungsi untuk mendapatkan nama bahasa
function lang_name($code = null) {
    $code = $code ?? current_lang();
    $names = [
        'id' => 'Indonesia',
        'en' => 'English'
    ];
    return $names[$code] ?? 'Indonesia';
}
?>
