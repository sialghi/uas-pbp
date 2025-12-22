<?php
session_start();
require 'config/koneksi.php';
require 'config/language.php';
if (!isset($_SESSION['login'])) { header("Location: index.php"); exit; }

// Filter Kategori & Search
$where = "1=1";
if (isset($_GET['kategori']) && $_GET['kategori'] != 'Semua') {
    $kategori = $_GET['kategori'];
    $where .= " AND kategori = '$kategori'";
}
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $where .= " AND (judul LIKE '%$search%' OR penulis LIKE '%$search%')";
}

$query = "SELECT * FROM books WHERE $where ORDER BY last_read DESC, id DESC";
$result = mysqli_query($conn, $query);

// Ambil Statistik User
$user_id = $_SESSION['user_id'] ?? 0;
$stats_query = mysqli_query($conn, "SELECT * FROM user_stats WHERE user_id = $user_id");
$stats = mysqli_fetch_assoc($stats_query);

$total_seconds = $stats['total_seconds'] ?? 0;
$books_opened = $stats['books_opened'] ?? 0;

// Format waktu baca
$hours = floor($total_seconds / 3600);
$minutes = floor(($total_seconds % 3600) / 60);
$seconds = $total_seconds % 60;

if ($hours > 0) {
    $time_display = current_lang() == 'id' ? "{$hours} Jam {$minutes} Menit" : "{$hours} Hours {$minutes} Minutes";
} else if ($minutes > 0) {
    $time_display = current_lang() == 'id' ? "{$minutes} Menit" : "{$minutes} Minutes";
} else {
    $time_display = current_lang() == 'id' ? "{$seconds} Detik" : "{$seconds} Seconds";
}
?>

<!DOCTYPE html>
<html lang="<?= current_lang() ?>" class="dark"> <head>
    <title><?= t('dashboard') ?> - FST Reader</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script> tailwind.config = { darkMode: 'class' } </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 font-sans antialiased transition-colors duration-300">
    
<aside class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 p-6 z-10 transition-colors duration-300 flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/30">
                    <i data-lucide="book" class="text-white w-6 h-6"></i>
                </div>
                <span class="font-bold text-xl tracking-tight">FST Reader</span>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-orange-50 dark:bg-slate-700/50 text-orange-600 dark:text-orange-400 rounded-xl font-bold transition-all hover:scale-105 active:scale-95">
                    <i data-lucide="library" class="w-5 h-5"></i> <?= t('library') ?>
                </a>
                <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all hover:scale-105 active:scale-95">
                    <i data-lucide="upload" class="w-5 h-5"></i> <?= t('upload_book') ?>
                </a>
                <a href="settings.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all hover:scale-105 active:scale-95">
                    <i data-lucide="settings" class="w-5 h-5"></i> <?= t('settings') ?>
                </a>
            </nav>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-700 pt-6">
            <?php 
                // Ambil data user terbaru buat foto
                $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = {$_SESSION['user_id']}"));
                $foto = ($u['profile_photo'] == 'default_user.png') ? 'https://ui-avatars.com/api/?name='.$u['nama'].'&background=random' : 'uploads/covers/'.$u['profile_photo'];
            ?>
            <div class="flex items-center gap-3 mb-4">
                <img src="<?= $foto ?>" class="w-10 h-10 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600">
                <div class="overflow-hidden">
                    <p class="text-sm font-bold truncate"><?= $u['nama'] ?></p>
                    <p class="text-xs text-slate-400">
                        <?= (($_SESSION['role'] ?? 'mahasiswa') === 'admin') ? 'Admin' : t('student') ?>
                    </p>
                </div>
            </div>
            <a href="logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 transition-all active:scale-95 font-medium text-sm">
                <i data-lucide="log-out" class="w-4 h-4"></i> <?= t('logout') ?>
            </a>
        </div>
    </aside>

    <main class="ml-64 p-8">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold"><?= t('library') ?></h2>
                <p class="text-slate-500 dark:text-slate-400"><?= current_lang() == 'id' ? 'Halo' : 'Hello' ?>, <?= $_SESSION['nama']; ?></p>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Language Switcher -->
                <div class="flex gap-2 bg-white dark:bg-slate-800 p-1 rounded-full border border-slate-200 dark:border-slate-700">
                    <a href="?lang=id" class="px-3 py-1.5 rounded-full text-sm font-medium transition-all <?= current_lang() == 'id' ? 'bg-orange-500 text-white' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white' ?>">
                        🇮🇩 ID
                    </a>
                    <a href="?lang=en" class="px-3 py-1.5 rounded-full text-sm font-medium transition-all <?= current_lang() == 'en' ? 'bg-orange-500 text-white' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white' ?>">
                        🇬🇧 EN
                    </a>
                </div>
                
                <form class="flex gap-2">
                    <input type="text" name="search" placeholder="<?= t('search_books') ?>" class="px-4 py-2 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:border-orange-500 shadow-sm">
                    <button type="submit" class="bg-orange-500 text-white p-2 rounded-full shadow-lg shadow-orange-500/30 hover:bg-orange-600"><i data-lucide="search" class="w-5 h-5"></i></button>
                </form>

                <button onclick="toggleTheme()" class="p-2 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-yellow-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition shadow-sm">
                    <i data-lucide="sun" id="theme-icon"></i>
                </button>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-orange-500 to-red-500 p-6 rounded-2xl text-white shadow-lg shadow-orange-500/20">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-white/20 rounded-xl"><i data-lucide="clock" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm opacity-80"><?= t('reading_time') ?></p>
                        <h3 class="text-2xl font-bold"><?= $time_display ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 rounded-2xl text-slate-800 dark:text-white shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-slate-100 dark:bg-slate-700 rounded-xl text-orange-500"><i data-lucide="book-open" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= current_lang() == 'id' ? 'Status Akun' : 'Account Status' ?></p>
                        <h3 class="text-xl font-bold"><?= current_lang() == 'id' ? 'Aktif' : 'Active' ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mb-8 overflow-x-auto pb-2">
            <?php 
            $cats = [t('all_categories'), 'Informatika', 'Biologi', 'Fisika', 'Matematika'];
            foreach($cats as $c): 
                $active = (isset($_GET['kategori']) && $_GET['kategori'] == $c) ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/30' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700';
            ?>
                <a href="?kategori=<?= $c ?>" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition <?= $active ?>"><?= $c ?></a>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php while($row = mysqli_fetch_assoc($result)) : 
                // Cek apakah buku baru dibaca dalam 24 jam terakhir
                $is_recently_read = false;
                if ($row['last_read']) {
                    $last_read_time = strtotime($row['last_read']);
                    $now = time();
                    $diff_hours = ($now - $last_read_time) / 3600;
                    $is_recently_read = ($diff_hours < 24);
                }
            ?>
            <div class="group relative bg-white dark:bg-slate-800 rounded-2xl overflow-hidden hover:shadow-xl hover:shadow-orange-900/10 dark:hover:shadow-orange-900/20 transition-all cursor-pointer border border-slate-100 dark:border-slate-700">
                <?php if($is_recently_read): ?>
                <div class="absolute top-2 right-2 z-10 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                    <i data-lucide="clock" class="w-3 h-3 inline-block"></i> <?= current_lang() == 'id' ? 'Sedang Dibaca' : 'Reading' ?>
                </div>
                <?php endif; ?>
                
                <div class="aspect-[3/4] overflow-hidden relative">
                    <img src="uploads/covers/<?= $row['cover'] ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                        <a href="read.php?id=<?= $row['id'] ?>" class="px-6 py-2 bg-orange-500 rounded-full font-bold text-white hover:bg-orange-600 shadow-lg transform hover:scale-105 transition"><?= current_lang() == 'id' ? 'Baca Sekarang' : 'Read Now' ?></a>
                    </div>
                </div>
                <div class="p-4">
                    <span class="text-xs text-orange-500 border border-orange-200 dark:border-orange-500/30 bg-orange-50 dark:bg-orange-900/20 px-2 py-1 rounded-full"><?= $row['kategori'] ?></span>
                    <h3 class="font-bold text-lg mt-2 truncate text-slate-800 dark:text-slate-100"><?= $row['judul'] ?></h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= $row['penulis'] ?></p>

                    <?php if (($_SESSION['role'] ?? 'mahasiswa') === 'admin') : ?>
                        <div class="flex gap-2 mt-4">
                            <a href="edit_book.php?id=<?= $row['id'] ?>" class="flex-1 text-center py-2 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                                <?= current_lang() == 'id' ? 'Edit' : 'Edit' ?>
                            </a>
                            <form action="delete_book.php" method="POST" class="flex-1" onsubmit="return confirm('<?= current_lang() == 'id' ? 'Yakin ingin menghapus buku ini?' : 'Are you sure you want to delete this book?' ?>');">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="w-full py-2 rounded-xl bg-red-600 text-white font-semibold hover:bg-red-700 transition">
                                    <?= current_lang() == 'id' ? 'Hapus' : 'Delete' ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <script>
        lucide.createIcons(); // Load Icons

        // --- LOGIKA GANTI TEMA ---
        // Cek apakah user sebelumnya sudah set mode
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            document.getElementById('theme-icon').setAttribute('data-lucide', 'sun');
        } else {
            document.documentElement.classList.remove('dark');
            document.getElementById('theme-icon').setAttribute('data-lucide', 'moon');
        }
        lucide.createIcons(); // Refresh icon

        function toggleTheme() {
            var html = document.documentElement;
            var icon = document.getElementById('theme-icon');
            
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.theme = 'light';
                icon.setAttribute('data-lucide', 'moon');
            } else {
                html.classList.add('dark');
                localStorage.theme = 'dark';
                icon.setAttribute('data-lucide', 'sun');
            }
            lucide.createIcons(); // Refresh icon gambar
        }
    </script>
</body>
</html>