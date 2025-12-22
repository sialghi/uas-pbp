<?php
session_start();
require 'config/koneksi.php';
require 'config/language.php';
if (!isset($_SESSION['login'])) { header("Location: index.php"); exit; }

if (isset($_POST['upload'])) {
    $judul = $_POST['judul'];
    $penulis = $_POST['penulis'];
    $kategori = $_POST['kategori'];

    // --- LOGIKA AUTO COVER ---
    if (empty($_FILES['cover']['name'])) {
        // Jika user tidak upload cover, pakai default
        $coverName = 'default_book.png'; 
    } else {
        // Jika user upload cover
        $coverName = time() . '_' . $_FILES['cover']['name'];
        move_uploaded_file($_FILES['cover']['tmp_name'], 'uploads/covers/' . $coverName);
    }

    // Upload Epub (Wajib)
    $epubName = time() . '_' . $_FILES['epub']['name'];
    $epubTmp = $_FILES['epub']['tmp_name'];
    $epubExt = strtolower(pathinfo($epubName, PATHINFO_EXTENSION));

    if($epubExt == 'epub') {
        move_uploaded_file($epubTmp, 'uploads/files/' . $epubName);
        
        $query = "INSERT INTO books (judul, penulis, kategori, cover, file_path) VALUES ('$judul', '$penulis', '$kategori', '$coverName', '$epubName')";
        
        if(mysqli_query($conn, $query)){
            header("Location: dashboard.php"); // Redirect sukses
        } else {
            echo "<script>alert('".t('error_upload')."');</script>";
        }
    } else {
        echo "<script>alert('".(current_lang() == 'id' ? 'File harus format .epub!' : 'File must be in .epub format!')."');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="<?= current_lang() ?>" class="dark">
<head>
    <title><?= t('upload_book') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script> tailwind.config = { darkMode: 'class' } </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 min-h-screen flex items-center justify-center transition-colors duration-300">
    
    <div class="w-full max-w-lg p-8">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in slide-in-from-bottom-10 duration-500">
            
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-orange-500 to-red-500 bg-clip-text text-transparent"><?= t('upload_new_book') ?></h2>
                    <p class="text-sm opacity-60"><?= current_lang() == 'id' ? 'Bagikan ilmu ke perpustakaan' : 'Share knowledge to library' ?></p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-slate-700 rounded-2xl text-orange-500">
                    <i data-lucide="cloud-upload" class="w-6 h-6"></i>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <div class="group">
                    <input type="text" name="judul" placeholder="<?= t('book_title') ?>" required
                           class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none transition-all placeholder:text-slate-400">
                </div>

                <div class="group">
                    <input type="text" name="penulis" placeholder="<?= t('author') ?>" required
                           class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none transition-all placeholder:text-slate-400">
                </div>
                
                <div class="relative">
                    <select name="kategori" class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:border-orange-500 outline-none appearance-none cursor-pointer text-slate-600 dark:text-slate-300">
                        <option value="Informatika">Informatika</option>
                        <option value="Biologi">Biologi</option>
                        <option value="Fisika">Fisika</option>
                        <option value="Matematika">Matematika</option>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 opacity-50 w-5 h-5"></i>
                </div>
                
                <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-6 text-center hover:border-orange-400 transition cursor-pointer group" onclick="document.getElementById('coverInput').click()">
                    <input type="file" name="cover" id="coverInput" class="hidden" accept="image/*" onchange="previewFile('coverInput', 'coverLabel')">
                    <i data-lucide="image" class="w-8 h-8 mx-auto mb-2 text-slate-400 group-hover:text-orange-500 transition"></i>
                    <p id="coverLabel" class="text-sm text-slate-500 dark:text-slate-400"><?= current_lang() == 'id' ? 'Pilih Cover (Opsional)' : 'Select Cover (Optional)' ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= current_lang() == 'id' ? 'Jika kosong, cover otomatis dibuat' : 'If empty, cover will be auto-generated' ?></p>
                </div>
                
                <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-6 text-center hover:border-orange-400 transition cursor-pointer group" onclick="document.getElementById('epubInput').click()">
                    <input type="file" name="epub" id="epubInput" class="hidden" accept=".epub" required onchange="previewFile('epubInput', 'epubLabel')">
                    <i data-lucide="file-text" class="w-8 h-8 mx-auto mb-2 text-slate-400 group-hover:text-orange-500 transition"></i>
                    <p id="epubLabel" class="text-sm text-slate-500 dark:text-slate-400"><?= current_lang() == 'id' ? 'Pilih File Buku (.epub)' : 'Select Book File (.epub)' ?></p>
                </div>

                <div class="flex gap-4 pt-4">
                    <a href="dashboard.php" class="flex-1 py-4 text-center text-slate-500 font-bold hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition active:scale-95"><?= current_lang() == 'id' ? 'Batal' : 'Cancel' ?></a>
                    <button type="submit" name="upload" class="flex-1 py-4 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-500/30 transition active:scale-95"><?= current_lang() == 'id' ? 'Simpan' : 'Save' ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Cek Tema (Copy logic dari dashboard)
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Script untuk menampilkan nama file yang dipilih
        function previewFile(inputId, labelId) {
            var input = document.getElementById(inputId);
            var label = document.getElementById(labelId);
            if(input.files && input.files[0]) {
                label.innerText = input.files[0].name;
                label.classList.add("text-orange-500", "font-bold");
            }
        }
    </script>
</body>
</html>