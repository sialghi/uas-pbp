<?php
session_start();
require 'config/koneksi.php';
require 'config/language.php';

// Logika Register & Login Sederhana
if (isset($_POST['register'])) {
    $nim = $_POST['nim'];
    $nama = $_POST['nama'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $nim_esc = mysqli_real_escape_string($conn, $nim);
    $nama_esc = mysqli_real_escape_string($conn, $nama);
    $pass_esc = mysqli_real_escape_string($conn, $pass);

    $query = "INSERT INTO users (nim, nama, password, role) VALUES ('$nim_esc', '$nama_esc', '$pass_esc', 'mahasiswa')";
    if(mysqli_query($conn, $query)) {
        echo "<script>alert('".t('success_register')."');</script>";
    } else {
        echo "<script>alert('".t('error_register')."');</script>";
    }
}

if (isset($_POST['login'])) {
    $nim = $_POST['nim'];
    $pass = $_POST['password'];
    
    $nim_esc = mysqli_real_escape_string($conn, $nim);
    $result = mysqli_query($conn, "SELECT * FROM users WHERE nim = '$nim_esc'");
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($pass, $row['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['nama'] = $row['nama'];
            // ---> TAMBAHKAN BARIS INI <---
            $_SESSION['user_id'] = $row['id']; 
            $_SESSION['role'] = $row['role'] ?? 'mahasiswa';
            
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = true;
}
?>

<!DOCTYPE html>
<html lang="<?= current_lang() ?>" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('login') ?> - FST Reader</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex items-center justify-center p-4">
    <div class="flex w-full max-w-4xl bg-slate-800 rounded-2xl overflow-hidden shadow-2xl">
        <div class="hidden md:flex w-1/2 bg-gradient-to-br from-red-600 to-orange-500 p-12 flex-col justify-center relative">
            <h1 class="text-4xl font-bold mb-4"><?= t('app_title') ?></h1>
            <p class="opacity-90"><?= t('app_subtitle') ?></p>
            
            <!-- Language Switcher -->
            <div class="absolute top-4 right-4 flex gap-2">
                <a href="?lang=id" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all <?= current_lang() == 'id' ? 'bg-white/20 text-white' : 'text-white/60 hover:text-white hover:bg-white/10' ?>">
                    🇮🇩 ID
                </a>
                <a href="?lang=en" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all <?= current_lang() == 'en' ? 'bg-white/20 text-white' : 'text-white/60 hover:text-white hover:bg-white/10' ?>">
                    🇬🇧 EN
                </a>
            </div>
        </div>

        <div class="w-full md:w-1/2 p-8 md:p-12">
            <!-- Mobile Language Switcher -->
            <div class="md:hidden flex gap-2 justify-end mb-4">
                <a href="?lang=id" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all <?= current_lang() == 'id' ? 'bg-orange-500 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-700' ?>">
                    🇮🇩 ID
                </a>
                <a href="?lang=en" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all <?= current_lang() == 'en' ? 'bg-orange-500 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-700' ?>">
                    🇬🇧 EN
                </a>
            </div>
            
            <h2 class="text-3xl font-bold mb-2"><?= t('welcome') ?></h2>
            <p class="text-slate-400 mb-8"><?= t('login_subtitle') ?></p>

            <?php if(isset($error)) : ?>
                <p class="text-red-500 mb-4"><?= t('error_login') ?></p>
            <?php endif; ?>

            <div x-data="{ tab: 'login' }">
                <div class="flex gap-4 mb-6 border-b border-slate-700 pb-2" id="authTabs">
                    <button onclick="document.getElementById('formLogin').style.display='block'; document.getElementById('formReg').style.display='none'; this.classList.add('text-orange-500');" class="font-bold text-orange-500"><?= t('login') ?></button>
                    <button onclick="document.getElementById('formLogin').style.display='none'; document.getElementById('formReg').style.display='block';" class="font-bold text-slate-400 hover:text-white"><?= t('register') ?></button>
                </div>

                <form method="POST" id="formLogin">
                    <div class="space-y-4">
                        <input type="text" name="nim" placeholder="<?= t('nim') ?>" class="w-full p-4 rounded-xl bg-slate-700 border-none text-white focus:ring-2 focus:ring-orange-500" required>
                        <input type="password" name="password" placeholder="<?= t('password') ?>" class="w-full p-4 rounded-xl bg-slate-700 border-none text-white focus:ring-2 focus:ring-orange-500" required>
                        <button type="submit" name="login" class="w-full py-4 bg-gradient-to-r from-red-500 to-orange-500 rounded-xl font-bold shadow-lg hover:scale-[1.02] transition"><?= t('login') ?></button>
                    </div>
                </form>

                <form method="POST" id="formReg" style="display:none;">
                    <div class="space-y-4">
                        <input type="text" name="nama" placeholder="<?= t('full_name') ?>" class="w-full p-4 rounded-xl bg-slate-700 border-none text-white focus:ring-2 focus:ring-orange-500" required>
                        <input type="text" name="nim" placeholder="<?= t('nim') ?>" class="w-full p-4 rounded-xl bg-slate-700 border-none text-white focus:ring-2 focus:ring-orange-500" required>
                        <input type="password" name="password" placeholder="<?= t('password') ?>" class="w-full p-4 rounded-xl bg-slate-700 border-none text-white focus:ring-2 focus:ring-orange-500" required>
                        <button type="submit" name="register" class="w-full py-4 bg-slate-600 rounded-xl font-bold hover:bg-slate-500 transition"><?= t('register_account') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>