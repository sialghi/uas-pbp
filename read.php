<?php
session_start();
require 'config/koneksi.php';
require 'config/language.php';
if (!isset($_SESSION['login'])) { header("Location: index.php"); exit; }

$id = $_GET['id'];
$id = intval($id);
$query = mysqli_query($conn, "SELECT * FROM books WHERE id = $id");
$book = mysqli_fetch_assoc($query);

// Update last_read immediately so dashboard ordering/status updates even on short reads
mysqli_query($conn, "UPDATE books SET last_read = NOW() WHERE id = $id");
?>

<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <title><?= current_lang() == 'id' ? 'Membaca' : 'Reading' ?>: <?= $book['judul'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script>
        // Initialize theme using same logic as dashboard
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    
    <!-- Preload EPUB file untuk loading lebih cepat -->
    <link rel="preload" href="uploads/files/<?= $book['file_path'] ?>" as="fetch" crossorigin>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.5/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/epubjs/dist/epub.min.js"></script>
    
    <style>
        #viewer { max-width: 1200px; margin: 0 auto; padding: 20px; }
        #viewer iframe { 
            border: none;
            background: transparent;
        }
        body { overflow: hidden; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-300 flex flex-col h-screen">
    
    <header class="h-16 border-b border-slate-700 flex items-center justify-between px-6 bg-slate-800 z-20 shrink-0">
        <div class="flex items-center gap-4">
            <a id="backToDashboard" href="dashboard.php" class="p-2 hover:bg-slate-700 rounded-full transition text-slate-400 hover:text-white">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="font-bold text-white truncate max-w-[200px] md:max-w-md text-sm md:text-base"><?= $book['judul'] ?></h1>
                <p class="text-xs text-slate-500"><?= current_lang() == 'id' ? 'Bab' : 'Chapter' ?>: <span id="chapter-name"><?= t('loading') ?></span></p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Timer Indicator -->
            <div class="hidden md:flex items-center gap-2 bg-slate-700/50 px-3 py-1.5 rounded-full text-xs">
                <i data-lucide="clock" class="w-4 h-4 text-green-400"></i>
                <span id="reading-timer" class="text-slate-300 font-mono">00:00</span>
            </div>
            
            <button id="prevBtn" class="p-2 hover:bg-slate-700 rounded-full transition text-slate-300">
                <i data-lucide="chevron-left"></i>
            </button>
            <button id="nextBtn" class="p-2 bg-orange-600 text-white rounded-full hover:bg-orange-700 transition shadow-lg">
                <i data-lucide="chevron-right"></i>
            </button>
        </div>
    </header>

    <div class="flex-1 relative bg-slate-50 dark:bg-slate-900 w-full flex flex-col justify-center">
        <div id="viewer" class="h-full w-full shadow-2xl"></div>
        
        <div id="loader" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-900 z-10">
            <i data-lucide="loader-2" class="animate-spin w-10 h-10 text-orange-500 mb-3"></i>
            <p class="text-slate-400 text-sm animate-pulse"><?= current_lang() == 'id' ? 'Membuka buku...' : 'Opening book...' ?></p>
            <p class="text-slate-600 text-xs mt-2"><?= current_lang() == 'id' ? 'Memuat' : 'Loading' ?>: <?= $book['judul'] ?></p>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Load langsung dari folder - lebih cepat!
        var bookUrl = "uploads/files/<?= $book['file_path'] ?>";
        console.log("[INFO] Loading EPUB from:", bookUrl);
        console.log("[INFO] Book title:", "<?= $book['judul'] ?>");

        // Inisialisasi dengan timeout handler
        var loadTimeout = setTimeout(function() {
            console.warn("[WARNING] Loading taking longer than expected...");
        }, 3000);

        try {
            // Inisialisasi book dengan options minimal
            var book = ePub(bookUrl);
            
            // Render
            var rendition = book.renderTo("viewer", {
                width: "100%", 
                height: "100%",
                flow: "paginated",
                spread: "none"
            });

            // Display immediately
            rendition.display();

            // Event: Book Ready
            book.ready.then(function() {
                clearTimeout(loadTimeout);
                console.log("[SUCCESS] Book loaded successfully!");
                setTimeout(function() {
                    document.getElementById('loader').style.display = 'none';
                }, 500);
            }).catch(function(err) {
                clearTimeout(loadTimeout);
                console.error("[ERROR] Failed to load book:", err);
                showError(err);
            });

            // Event: Book Opened
            book.opened.then(function() {
                console.log("[SUCCESS] Book opened");
            }).catch(function(err) {
                console.error("[ERROR] Failed to open book:", err);
            });

            // Update Chapter Name
            rendition.on("relocated", function(location){
                if(location && location.start && location.start.href) {
                    var chapter = book.navigation.get(location.start.href);
                    var lang = '<?= current_lang() ?>';
                    var pageTxt = lang === 'id' ? 'Halaman' : 'Page';
                    var chapterName = chapter ? chapter.label.trim() : pageTxt + " " + location.start.displayed.page;
                    document.getElementById("chapter-name").innerText = chapterName;
                }
            });

            // Navigation Buttons
            document.getElementById("nextBtn").addEventListener("click", function() {
                rendition.next();
            });
            
            document.getElementById("prevBtn").addEventListener("click", function() {
                rendition.prev();
            });

            // Keyboard Navigation
            document.addEventListener("keyup", function(e) {
                if (e.key === "ArrowLeft") rendition.prev();
                if (e.key === "ArrowRight") rendition.next();
            });

            // Register both dark and light EPUB themes and apply based on current theme
            var darkTheme = {
                'body, body *': { 
                    color: "#ffffff", 
                    background: "#0f172a",
                    'font-family': 'Georgia, "Times New Roman", serif',
                    'line-height': '1.8',
                    'padding': '20px 40px',
                    'font-size': '18px'
                },
                'p, li, div, span': {
                    color: "#ffffff",
                    'margin': '1em 0'
                },
                'h1, h2, h3': {
                    'color': '#ffd580'
                },
                'a': {
                    'color': '#93c5fd'
                }
            };
            var lightTheme = {
                body: { 
                    color: "#0f172a", 
                    background: "#ffffff",
                    'font-family': 'Georgia, "Times New Roman", serif',
                    'line-height': '1.8',
                    'padding': '20px 40px',
                    'font-size': '18px'
                },
                'p': {
                    'margin': '1em 0'
                },
                'h1, h2, h3': {
                    'color': '#c2410c'
                }
            };
            rendition.themes.register('dark', darkTheme);
            rendition.themes.register('light', lightTheme);
            // Apply initial theme
            if (document.documentElement.classList.contains('dark')) {
                rendition.themes.select('dark');
            } else {
                rendition.themes.select('light');
            }
            // Listen for theme changes from other tabs (storage event)
            window.addEventListener('storage', function(e) {
                if (e.key === 'theme') {
                    if (e.newValue === 'dark') {
                        document.documentElement.classList.add('dark');
                        rendition.themes.select('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        rendition.themes.select('light');
                    }
                }
            });

        } catch(err) {
            clearTimeout(loadTimeout);
            console.error("[FATAL ERROR] Exception during initialization:", err);
            showError(err);
        }

        // ========== TRACKING WAKTU MEMBACA ==========
        // Target behavior:
        // - Timer always runs while the tab is visible
        // - Saves frequently so reopening the book resumes from the last saved time
        // - Uses sendBeacon on hide/close for reliability

        var isPageVisible = !document.hidden;
        var totalReadingSeconds = 0; // persisted seconds loaded from DB
        var pendingSeconds = 0; // seconds in current session not yet persisted
        var lastTickMs = Date.now();
        var savingInFlight = false;

        function formatTime(totalSeconds) {
            var hours = Math.floor(totalSeconds / 3600);
            var minutes = Math.floor((totalSeconds % 3600) / 60);
            var seconds = totalSeconds % 60;

            if (hours > 0) {
                return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
            return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }

        function updateTimerDisplay() {
            document.getElementById('reading-timer').textContent = formatTime(totalReadingSeconds + pendingSeconds);
        }

        function tick() {
            if (!isPageVisible) return;

            var now = Date.now();
            var deltaSeconds = Math.floor((now - lastTickMs) / 1000);
            if (deltaSeconds <= 0) return;

            // advance in whole seconds to avoid drift
            pendingSeconds += deltaSeconds;
            lastTickMs += deltaSeconds * 1000;
            updateTimerDisplay();
        }

        // Runs every second
        var timerDisplay = setInterval(tick, 1000);
        updateTimerDisplay();

        // Load previous reading time for this book
        fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_time&book_id=<?= $id ?>'
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    totalReadingSeconds = parseInt(data.reading_seconds || 0, 10) || 0;
                    pendingSeconds = 0;
                    lastTickMs = Date.now();
                    console.log('[TRACKING] ✓ Loaded previous reading time:', totalReadingSeconds, 'seconds');
                    updateTimerDisplay();
                }
            })
            .catch(err => {
                console.warn('[TRACKING] ⚠ Could not load previous time (starting from 0):', err);
            });

        function sendUpdate(seconds, mode) {
            if (seconds <= 0) return;

            if (mode === 'beacon' && navigator.sendBeacon) {
                // Use FormData so PHP populates $_POST reliably
                var fd = new FormData();
                fd.append('action', 'update_time');
                fd.append('seconds', String(seconds));
                fd.append('book_id', String(<?= $id ?>));
                var ok = navigator.sendBeacon('api.php', fd);
                if (ok) {
                    // We cannot read the response, but this is the most reliable on page close.
                    totalReadingSeconds += seconds;
                    pendingSeconds -= seconds;
                    updateTimerDisplay();
                    return;
                }
                // fall through to sync if beacon fails
            }

            if (mode === 'sync') {
                try {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'api.php', false);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.send('action=update_time&seconds=' + encodeURIComponent(seconds) + '&book_id=' + encodeURIComponent(<?= $id ?>));
                    totalReadingSeconds += seconds;
                    pendingSeconds -= seconds;
                    updateTimerDisplay();
                } catch (e) {
                    // ignore
                }
                return;
            }

            // async
            savingInFlight = true;
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=update_time&seconds=' + encodeURIComponent(seconds) + '&book_id=' + encodeURIComponent(<?= $id ?>)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        totalReadingSeconds += seconds;
                        pendingSeconds -= seconds;
                        console.log('[TRACKING] ✓ Saved', seconds, 'seconds. Total:', totalReadingSeconds, 's');
                        updateTimerDisplay();
                    }
                })
                .catch(err => console.error('[TRACKING] Error saving time:', err))
                .finally(() => { savingInFlight = false; });
        }

        function flushPending(mode) {
            // Recompute last tick before flushing, so we don't lose up to 1s
            tick();
            var secondsToSave = pendingSeconds;
            if (secondsToSave > 0) {
                sendUpdate(secondsToSave, mode);
            }
        }

        // Autosave frequently so reopen resumes correctly.
        // Save every 10 seconds (if there is pending time).
        setInterval(function () {
            if (!isPageVisible) return;
            if (savingInFlight) return;
            if (pendingSeconds >= 10) {
                flushPending('async');
            }
        }, 2000);

        // Pause/resume + flush on hide
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                isPageVisible = false;
                flushPending('beacon');
                console.log('[TRACKING] Page hidden - flushed pending time');
            } else {
                isPageVisible = true;
                lastTickMs = Date.now();
                console.log('[TRACKING] Page visible - resuming tracker');
            }
        });

        // More reliable than beforeunload in modern browsers
        window.addEventListener('pagehide', function () {
            flushPending('beacon');
        });

        window.addEventListener('beforeunload', function () {
            // Fallback sync save
            flushPending('sync');
        });

        // If user leaves via the in-page back button, ensure dashboard sees the latest time
        var backLink = document.getElementById('backToDashboard');
        if (backLink) {
            backLink.addEventListener('click', function () {
                flushPending('sync');
            });
        }
        // ========== END TRACKING ==========

        // Function to show error
        function showError(err) {
            var lang = '<?= current_lang() ?>';
            var errTitle = lang === 'id' ? 'Gagal Memuat Buku' : 'Failed to Load Book';
            var errDesc = lang === 'id' ? 'Terjadi kesalahan saat membuka file EPUB' : 'An error occurred while opening the EPUB file';
            var errTry = lang === 'id' ? 'Coba Lagi' : 'Try Again';
            var errBack = lang === 'id' ? 'Kembali' : 'Go Back';
            
            document.getElementById('loader').innerHTML = `
                <div class="text-center p-8 bg-red-900/20 border-2 border-red-500 rounded-2xl max-w-md mx-auto">
                    <i data-lucide="alert-circle" class="w-16 h-16 mx-auto mb-4 text-red-500"></i>
                    <p class="text-red-400 font-bold text-xl mb-2">${errTitle}</p>
                    <p class="text-slate-400 text-sm mb-4">${errDesc}</p>
                    <p class="text-xs text-slate-500 mb-6 font-mono bg-slate-800 p-3 rounded">${err}</p>
                    <div class="flex gap-3 justify-center">
                        <button onclick="location.reload()" class="px-6 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition font-medium">${errTry}</button>
                        <a href="dashboard.php" class="px-6 py-3 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-medium">${errBack}</a>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
    </script>
</body>
</html>