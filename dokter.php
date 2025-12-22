<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🏥 DOKTER DIAGNOSA EPUB</h1>";
echo "<hr>";

echo "<h3>1. Cek Kebocoran Spasi (Output Buffering)</h3>";
ob_start();
include 'config/koneksi.php';
$output = ob_get_contents();
ob_end_clean();

if (strlen($output) > 0) {
    echo "<div style='background:red; color:white; padding:10px;'>
        ❌ <strong>BAHAYA: Ditemukan " . strlen($output) . " karakter sampah (Spasi/Enter) di config/koneksi.php!</strong><br>
        Inilah penyebab file ePub Anda rusak (Corrupt).<br>
        PHP mengirim spasi ini sebelum mengirim file buku.
        </div>";
        
    echo "<p><strong>Solusi:</strong> Buka <code>config/koneksi.php</code>, hapus SEMUA spasi/enter sebelum <code>&lt;?php</code> dan setelah kurung kurawal terakhir <code>}</code>.</p>";
} else {
    echo "<div style='background:green; color:white; padding:10px;'>
        ✅ <strong>AMAN:</strong> Tidak ada spasi bocor di koneksi.php.
        </div>";
}


echo "<h3>2. Cek File Buku di Database</h3>";
$query = mysqli_query($conn, "SELECT * FROM books ORDER BY id DESC LIMIT 1");
$book = mysqli_fetch_assoc($query);

if ($book) {
    $realPath = 'uploads/files/' . $book['file_path'];
    echo "Buku Terakhir: <strong>" . $book['judul'] . "</strong><br>";
    echo "Lokasi File: <code>" . $realPath . "</code><br>";
    
    if (file_exists($realPath)) {
        $size = filesize($realPath);
        echo "Ukuran File: " . round($size / 1024, 2) . " KB<br>";
        
        if ($size < 1000) {
            echo "<span style='color:red'>❌ FILE RUSAK: Ukuran terlalu kecil (Mungkin 0 KB). Hapus dan upload ulang.</span>";
        } else {
            echo "<span style='color:green'>✅ FILE ADA & UKURAN WAJAR.</span>";
            
            
            echo "<br><br>👉 <a href='$realPath' target='_blank'>KLIK SINI UNTUK DOWNLOAD LANGSUNG (BYPASS PHP)</a>";
            echo "<p><em>Jika link di atas bisa didownload dan dibuka di laptop, berarti file SEHAT. Masalahnya di read.php.</em></p>";
        }
    } else {
        echo "<span style='color:red'>❌ FILE FISIK TIDAK DITEMUKAN DI FOLDER!</span>";
    }
} else {
    echo "Belum ada buku di database.";
}
?>