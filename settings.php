<?php
session_start();
require 'config/koneksi.php';
require 'config/language.php';

if (!isset($_SESSION['login'])) { header("Location: index.php"); exit; }

$user_id = $_SESSION['user_id'] ?? 0;
$msg = "";

// 1. Ambil data user saat ini
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// 2. Proses Update
if (isset($_POST['update_profile'])) {
    $nama_baru = mysqli_real_escape_string($conn, $_POST['nama']);
    
    // Update Foto jika ada yang diupload
    if (!empty($_FILES['foto']['name'])) {
        $fotoName = time() . '_' . $_FILES['foto']['name'];
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/covers/' . $fotoName); // Kita simpan di folder yang sama biar rapi
        $query_foto = ", profile_photo = '$fotoName'";
    } else {
        $query_foto = "";
    }

    $update = "UPDATE users SET nama = '$nama_baru' $query_foto WHERE id = $user_id";
    
    if (mysqli_query($conn, $update)) {
        $_SESSION['nama'] = $nama_baru; // Update session nama
        $msg = t('success_update');
        // Refresh data
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
    } else {
        $msg = (current_lang() == 'id' ? 'Gagal update database.' : 'Failed to update database.');
    }
}
?>

<!DOCTYPE html>
<html lang="<?= current_lang() ?>" class="dark">
<head>
    <title><?= t('settings') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script> tailwind.config = { darkMode: 'class' } </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 min-h-screen flex items-center justify-center transition-colors duration-300">

    <div class="w-full max-w-md bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in duration-300">
        
        <div class="flex items-center gap-4 mb-6">
            <a href="dashboard.php" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 transition active:scale-90"><i data-lucide="arrow-left"></i></a>
            <h2 class="text-2xl font-bold"><?= current_lang() == 'id' ? 'Edit Profil' : 'Edit Profile' ?></h2>
        </div>

        <?php if($msg): ?>
            <div class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 p-3 rounded-xl mb-4 text-sm font-bold text-center">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="flex justify-center">
                <div class="relative group">
                    <img src="<?= ($user['profile_photo'] == 'default_user.png') ? 'https://ui-avatars.com/api/?name='.$user['nama'].'&background=random' : 'uploads/covers/'.$user['profile_photo'] ?>" 
                         class="w-24 h-24 rounded-full object-cover border-4 border-orange-500 shadow-lg">
                    <label for="fotoInput" class="absolute bottom-0 right-0 bg-slate-800 text-white p-2 rounded-full cursor-pointer hover:bg-orange-500 transition shadow-md active:scale-90">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                    </label>
                    <input type="file" name="foto" id="fotoInput" class="hidden" accept="image/*">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-slate-500 dark:text-slate-400"><?= t('full_name') ?></label>
                <input type="text" name="nama" value="<?= $user['nama'] ?>" 
                       class="w-full p-4 rounded-xl bg-slate-100 dark:bg-slate-700 border-transparent focus:bg-white dark:focus:bg-slate-600 focus:ring-2 focus:ring-orange-500 outline-none transition-all">
            </div>

            <button type="submit" name="update_profile" 
                    class="w-full py-4 bg-gradient-to-r from-orange-500 to-red-500 text-white font-bold rounded-xl shadow-lg shadow-orange-500/30 hover:scale-[1.02] active:scale-95 transition-all duration-200">
                <?= t('save_changes') ?>
            </button>
        </form>
    </div>

    <script>
        lucide.createIcons();
        // Cek tema biar konsisten sama dashboard
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</body>
</html>