<?php
session_start();
require 'config/koneksi.php';
require 'config/language.php';

if (!isset($_SESSION['login'])) { header("Location: index.php"); exit; }
if (($_SESSION['role'] ?? 'mahasiswa') !== 'admin') { header("Location: dashboard.php"); exit; }

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: dashboard.php"); exit; }

$bookRes = mysqli_query($conn, "SELECT * FROM books WHERE id = $id");
$book = mysqli_fetch_assoc($bookRes);
if (!$book) { header("Location: dashboard.php"); exit; }

if (isset($_POST['save'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
    $penulis = mysqli_real_escape_string($conn, $_POST['penulis'] ?? '');
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? '');

    if ($judul === '' || $penulis === '' || $kategori === '') {
        echo "<script>alert('" . (current_lang() == 'id' ? 'Data tidak lengkap.' : 'Missing fields.') . "');</script>";
    } else {
        $newCover = $book['cover'];
        if (!empty($_FILES['cover']['name'])) {
            $coverName = time() . '_' . basename($_FILES['cover']['name']);
            if (move_uploaded_file($_FILES['cover']['tmp_name'], 'uploads/covers/' . $coverName)) {
                $newCover = $coverName;
                if (!empty($book['cover']) && $book['cover'] !== 'default_book.png' && $book['cover'] !== 'default_cover.jpg') {
                    $old = 'uploads/covers/' . $book['cover'];
                    if (file_exists($old)) { @unlink($old); }
                }
            }
        }

        $newEpub = $book['file_path'];
        if (!empty($_FILES['epub']['name'])) {
            $epubName = time() . '_' . basename($_FILES['epub']['name']);
            $epubExt = strtolower(pathinfo($epubName, PATHINFO_EXTENSION));
            if ($epubExt !== 'epub') {
                echo "<script>alert('" . (current_lang() == 'id' ? 'File harus format .epub!' : 'File must be in .epub format!') . "');</script>";
            } else {
                if (move_uploaded_file($_FILES['epub']['tmp_name'], 'uploads/files/' . $epubName)) {
                    $newEpub = $epubName;
                    if (!empty($book['file_path'])) {
                        $old = 'uploads/files/' . $book['file_path'];
                        if (file_exists($old)) { @unlink($old); }
                    }
                }
            }
        }

        mysqli_query($conn, "UPDATE books SET judul='$judul', penulis='$penulis', kategori='$kategori', cover='$newCover', file_path='$newEpub' WHERE id = $id");
        header("Location: dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= current_lang() ?>" class="dark">
<head>
    <title><?= current_lang() == 'id' ? 'Edit Buku' : 'Edit Book' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script> tailwind.config = { darkMode: 'class' } </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 min-h-screen flex items-center justify-center transition-colors duration-300">
    <div class="w-full max-w-lg p-8">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-2xl border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-orange-500 to-red-500 bg-clip-text text-transparent"><?= current_lang() == 'id' ? 'Edit Buku' : 'Edit Book' ?></h2>
                    <p class="text-sm opacity-60"><?= $book['judul'] ?></p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-slate-700 rounded-2xl text-orange-500">
                    <i data-lucide="pencil" class="w-6 h-6"></i>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="text" name="judul" value="<?= htmlspecialchars($book['judul']) ?>" required
                       class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none transition-all">

                <input type="text" name="penulis" value="<?= htmlspecialchars($book['penulis']) ?>" required
                       class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none transition-all">

                <div class="relative">
                    <select name="kategori" class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:border-orange-500 outline-none appearance-none cursor-pointer text-slate-600 dark:text-slate-300">
                        <?php
                        $cats = ['Informatika','Biologi','Fisika','Matematika'];
                        foreach ($cats as $c) {
                            $sel = ($book['kategori'] === $c) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($c) . "\" $sel>" . htmlspecialchars($c) . "</option>";
                        }
                        ?>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 opacity-50 w-5 h-5"></i>
                </div>

                <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-6 text-center hover:border-orange-400 transition cursor-pointer group" onclick="document.getElementById('coverInput').click()">
                    <input type="file" name="cover" id="coverInput" class="hidden" accept="image/*" onchange="previewFile('coverInput', 'coverLabel')">
                    <i data-lucide="image" class="w-8 h-8 mx-auto mb-2 text-slate-400 group-hover:text-orange-500 transition"></i>
                    <p id="coverLabel" class="text-sm text-slate-500 dark:text-slate-400"><?= current_lang() == 'id' ? 'Ganti Cover (Opsional)' : 'Replace Cover (Optional)' ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= current_lang() == 'id' ? 'Kosongkan jika tidak diganti' : 'Leave empty to keep existing' ?></p>
                </div>

                <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-6 text-center hover:border-orange-400 transition cursor-pointer group" onclick="document.getElementById('epubInput').click()">
                    <input type="file" name="epub" id="epubInput" class="hidden" accept=".epub" onchange="previewFile('epubInput', 'epubLabel')">
                    <i data-lucide="file-text" class="w-8 h-8 mx-auto mb-2 text-slate-400 group-hover:text-orange-500 transition"></i>
                    <p id="epubLabel" class="text-sm text-slate-500 dark:text-slate-400"><?= current_lang() == 'id' ? 'Ganti File EPUB (Opsional)' : 'Replace EPUB (Optional)' ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= current_lang() == 'id' ? 'Kosongkan jika tidak diganti' : 'Leave empty to keep existing' ?></p>
                </div>

                <div class="flex gap-4 pt-4">
                    <a href="dashboard.php" class="flex-1 py-4 text-center text-slate-500 font-bold hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition active:scale-95"><?= current_lang() == 'id' ? 'Batal' : 'Cancel' ?></a>
                    <button type="submit" name="save" class="flex-1 py-4 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-500/30 transition active:scale-95"><?= current_lang() == 'id' ? 'Simpan' : 'Save' ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        function previewFile(inputId, labelId) {
            var input = document.getElementById(inputId);
            var label = document.getElementById(labelId);
            if (input.files && input.files[0]) {
                label.innerText = input.files[0].name;
                label.classList.add("text-orange-500", "font-bold");
            }
        }
    </script>
</body>
</html>
