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

            <button id="addBookmarkBtn" title="Bookmark" class="p-2 hover:bg-slate-700 rounded-full transition text-slate-300">
                <i data-lucide="bookmark-plus"></i>
            </button>
            <button id="listBookmarksBtn" title="Daftar Bookmark" class="p-2 hover:bg-slate-700 rounded-full transition text-slate-300">
                <i data-lucide="book-marked"></i>
            </button>

            <button id="listNotesBtn" title="Daftar Notes" class="p-2 hover:bg-slate-700 rounded-full transition text-slate-300">
                <i data-lucide="sticky-note"></i>
            </button>

            <button id="searchBtn" title="Cari di Buku" class="p-2 hover:bg-slate-700 rounded-full transition text-slate-300">
                <i data-lucide="search"></i>
            </button>
            
            <button id="prevBtn" class="p-2 hover:bg-slate-700 rounded-full transition text-slate-300">
                <i data-lucide="chevron-left"></i>
            </button>
            <button id="nextBtn" class="p-2 bg-orange-600 text-white rounded-full hover:bg-orange-700 transition shadow-lg">
                <i data-lucide="chevron-right"></i>
            </button>
        </div>
    </header>

    <!-- Inline search panel (integrated, no prompt/alert) -->
    <div id="searchPanel" class="hidden absolute top-16 left-0 right-0 z-30 border-b border-slate-700 bg-slate-800/95 backdrop-blur">
        <div class="max-w-[1200px] mx-auto px-4 py-3">
            <div class="flex items-center gap-2">
                <div class="flex-1">
                    <input id="searchInput" type="text" placeholder="Cari di buku…" class="w-full px-3 py-2 rounded-lg bg-slate-900 text-slate-200 placeholder:text-slate-500 border border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500" />
                </div>
                <button id="searchGoBtn" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">Cari</button>
                <button id="searchCloseBtn" class="p-2 hover:bg-slate-700 rounded-full transition text-slate-300" title="Tutup">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs">
                <div id="searchStatus" class="text-slate-400">Ketik kata kunci lalu tekan Enter</div>
                <div class="text-slate-500">Shortcut: F untuk fokus</div>
            </div>
            <div id="searchResults" class="mt-3 max-h-56 overflow-auto rounded-lg border border-slate-700 bg-slate-900 hidden"></div>
        </div>
    </div>

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

        var BOOK_ID = <?= $id ?>;
        var lastCfi = '';
        var lastLabel = '';
        var savedCfiRanges = new Set();
        var pendingHighlightCfi = '';
        var pendingHighlightText = '';
        var pendingHighlightContents = null;
        var lastSearchQuery = '';
        var activeSearchMarkCfi = '';
        var loaderEl = null;
        var loaderOriginalHtml = '';

        var searchPanelEl = null;
        var searchInputEl = null;
        var searchGoBtnEl = null;
        var searchCloseBtnEl = null;
        var searchStatusEl = null;
        var searchResultsEl = null;
        var lastSearchResults = [];

        function initLoaderOverlay() {
            loaderEl = document.getElementById('loader');
            if (loaderEl) {
                loaderOriginalHtml = loaderEl.innerHTML;
            }
        }

        function initSearchPanel() {
            searchPanelEl = document.getElementById('searchPanel');
            searchInputEl = document.getElementById('searchInput');
            searchGoBtnEl = document.getElementById('searchGoBtn');
            searchCloseBtnEl = document.getElementById('searchCloseBtn');
            searchStatusEl = document.getElementById('searchStatus');
            searchResultsEl = document.getElementById('searchResults');
        }

        function openSearchPanel() {
            if (!searchPanelEl) return;
            searchPanelEl.classList.remove('hidden');
            lucide.createIcons();
            setTimeout(function () {
                if (searchInputEl) {
                    searchInputEl.focus();
                    searchInputEl.select();
                }
            }, 0);
        }

        function closeSearchPanel() {
            if (!searchPanelEl) return;
            searchPanelEl.classList.add('hidden');
        }

        function setSearchStatus(text) {
            if (searchStatusEl) searchStatusEl.textContent = text;
        }

        function clearSearchResults() {
            lastSearchResults = [];
            if (!searchResultsEl) return;
            searchResultsEl.innerHTML = '';
            searchResultsEl.classList.add('hidden');
        }

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function renderSearchResults(results, rendition) {
            if (!searchResultsEl) return;
            lastSearchResults = Array.isArray(results) ? results : [];
            searchResultsEl.innerHTML = '';
            if (lastSearchResults.length === 0) {
                searchResultsEl.classList.add('hidden');
                return;
            }

            var container = document.createElement('div');
            container.className = 'divide-y divide-slate-800';

            lastSearchResults.forEach(function (r, idx) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3 py-2 hover:bg-slate-800 transition';
                var chapter = r.chapter ? (escapeHtml(r.chapter) + ' — ') : '';
                var excerpt = escapeHtml((r.excerpt || '').replace(/\s+/g, ' ').trim());
                if (excerpt.length > 140) excerpt = excerpt.slice(0, 140) + '…';
                btn.innerHTML = '<div class="text-xs text-slate-400">' + (idx + 1) + '. ' + chapter + '</div>'
                    + '<div class="text-sm text-slate-200">' + (excerpt || '(tanpa cuplikan)') + '</div>';
                btn.addEventListener('click', function () {
                    if (!r || !r.cfi) return;

                    // Remove previous search mark
                    try {
                        if (activeSearchMarkCfi) {
                            rendition.annotations.remove(activeSearchMarkCfi, 'underline');
                        }
                    } catch (e) {}

                    activeSearchMarkCfi = r.cfi;
                    rendition.display(r.cfi).then(function () {
                        // Underline the found text (separate from user highlight)
                        try {
                            rendition.annotations.underline(r.cfi, {}, function () {}, 'epub-fst-search', {
                                stroke: 'orange',
                                'stroke-width': '2',
                                'stroke-opacity': '0.9'
                            });
                        } catch (e) {}
                    });
                });
                container.appendChild(btn);
            });

            searchResultsEl.appendChild(container);
            searchResultsEl.classList.remove('hidden');
        }

        function showBusy(message) {
            if (!loaderEl) return;
            loaderEl.style.display = 'flex';
            loaderEl.innerHTML = `
                <i data-lucide="loader-2" class="animate-spin w-10 h-10 text-orange-500 mb-3"></i>
                <p class="text-slate-400 text-sm animate-pulse">${message}</p>
            `;
            lucide.createIcons();
        }

        function hideBusy() {
            if (!loaderEl) return;
            loaderEl.innerHTML = loaderOriginalHtml;
            loaderEl.style.display = 'none';
            lucide.createIcons();
        }

        function postForm(body) {
            return fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function (r) { return r.json(); });
        }

        function safeStr(s) {
            return (typeof s === 'string') ? s : '';
        }

        function buildBookmarkLabel(location) {
            try {
                if (location && location.start && location.start.href) {
                    var chapter = book.navigation.get(location.start.href);
                    var base = chapter ? chapter.label.trim() : '';
                    var page = (location.start.displayed && location.start.displayed.page) ? location.start.displayed.page : '';
                    if (base && page) return base + ' — ' + page;
                    if (base) return base;
                    if (page) return 'Page ' + page;
                }
            } catch (e) {}
            var el = document.getElementById('chapter-name');
            return el ? String(el.innerText || 'Bookmark') : 'Bookmark';
        }

        function addBookmark() {
            if (!lastCfi) {
                alert('Posisi belum siap. Coba pindah halaman sekali.');
                return;
            }
            var label = prompt('Nama bookmark:', lastLabel || buildBookmarkLabel(null));
            if (label === null) return;

            postForm('action=save_bookmark&book_id=' + encodeURIComponent(BOOK_ID)
                + '&cfi=' + encodeURIComponent(lastCfi)
                + '&label=' + encodeURIComponent(label))
                .then(function (data) {
                    if (!data || data.status !== 'success') {
                        alert('Gagal menyimpan bookmark');
                    }
                })
                .catch(function () {
                    alert('Gagal menyimpan bookmark');
                });
        }

        function listBookmarks(rendition) {
            postForm('action=list_bookmarks&book_id=' + encodeURIComponent(BOOK_ID))
                .then(function (data) {
                    if (!data || data.status !== 'success') {
                        alert('Gagal mengambil bookmark');
                        return;
                    }
                    var bookmarks = Array.isArray(data.bookmarks) ? data.bookmarks : [];
                    if (bookmarks.length === 0) {
                        alert('Belum ada bookmark');
                        return;
                    }

                    var lines = bookmarks.map(function (b, idx) {
                        return (idx + 1) + '. ' + (safeStr(b.label) || 'Bookmark');
                    }).join('\n');
                    var picked = prompt('Pilih nomor bookmark untuk lompat:\n\n' + lines, '1');
                    if (picked === null) return;

                    var n = parseInt(picked, 10);
                    if (!n || n < 1 || n > bookmarks.length) return;
                    var cfi = safeStr(bookmarks[n - 1].cfi_range);
                    if (!cfi) return;
                    rendition.display(cfi);
                })
                .catch(function () {
                    alert('Gagal mengambil bookmark');
                });
        }

        function listNotes(rendition) {
            postForm('action=list_notes&book_id=' + encodeURIComponent(BOOK_ID))
                .then(function (data) {
                    if (!data || data.status !== 'success') {
                        alert('Gagal mengambil notes');
                        return;
                    }
                    var notes = Array.isArray(data.notes) ? data.notes : [];
                    var onlyNotes = notes.filter(function (n) {
                        var t = safeStr(n.note_text).trim();
                        return t.length > 0;
                    });
                    if (onlyNotes.length === 0) {
                        alert('Belum ada notes');
                        return;
                    }

                    var lines = onlyNotes.map(function (n, idx) {
                        var t = safeStr(n.note_text).trim();
                        if (t.length > 60) t = t.slice(0, 60) + '…';
                        return (idx + 1) + '. ' + t;
                    }).join('\n');
                    var picked = prompt('Pilih nomor note untuk lompat & lihat isi:\n\n' + lines, '1');
                    if (picked === null) return;
                    var nIdx = parseInt(picked, 10);
                    if (!nIdx || nIdx < 1 || nIdx > onlyNotes.length) return;

                    var item = onlyNotes[nIdx - 1];
                    var cfi = safeStr(item.cfi_range);
                    if (cfi) rendition.display(cfi);
                    var full = safeStr(item.note_text).trim();
                    if (full) setTimeout(function () { alert(full); }, 150);
                })
                .catch(function () {
                    alert('Gagal mengambil notes');
                });
        }

        function applyHighlight(rendition, cfiRange, color) {
            if (!cfiRange) return;
            var styles = {
                fill: (color === 'yellow' ? 'yellow' : color),
                'fill-opacity': '0.35',
                'mix-blend-mode': 'multiply'
            };
            try {
                rendition.annotations.highlight(cfiRange, {}, function () {}, 'epub-fst-highlight', styles);
            } catch (e) {
                console.warn('[HIGHLIGHT] Failed to apply highlight:', e);
            }
        }

        function loadHighlights(rendition) {
            return postForm('action=list_notes&book_id=' + encodeURIComponent(BOOK_ID))
                .then(function (data) {
                    if (!data || data.status !== 'success') return;
                    var notes = Array.isArray(data.notes) ? data.notes : [];
                    notes.forEach(function (n) {
                        var cfi = safeStr(n.cfi_range);
                        if (cfi) savedCfiRanges.add(cfi);
                        applyHighlight(rendition, cfi, safeStr(n.color) || 'yellow');
                    });
                })
                .catch(function (e) {
                    console.warn('[HIGHLIGHT] Could not load notes:', e);
                });
        }

        function getSpineItems(book) {
            if (!book || !book.spine) return [];
            return book.spine.spineItems || book.spine.items || [];
        }

        function getChapterLabelByHref(book, href) {
            try {
                var item = book && book.navigation ? book.navigation.get(href) : null;
                if (item && item.label) return String(item.label).trim();
            } catch (e) {}
            return '';
        }

        async function searchInBook(book, rendition, query) {
            query = (query || '').trim();
            clearSearchResults();

            if (!query) {
                setSearchStatus('Masukkan kata kunci pencarian');
                return;
            }

            lastSearchQuery = query;
            setSearchStatus('Mencari: ' + query + ' …');

            // Remove previous mark
            try {
                if (activeSearchMarkCfi) {
                    rendition.annotations.remove(activeSearchMarkCfi, 'underline');
                }
            } catch (e) {}
            activeSearchMarkCfi = '';

            var results = [];
            var spineItems = getSpineItems(book);

            for (var i = 0; i < spineItems.length; i++) {
                var section = spineItems[i];
                if (!section || typeof section.find !== 'function') continue;

                try {
                    var found = await Promise.resolve(section.find(query));
                    if (Array.isArray(found) && found.length) {
                        var href = section.href || '';
                        var chapterLabel = getChapterLabelByHref(book, href);
                        found.forEach(function (m) {
                            var cfi = (m && (m.cfi || m.cfiRange)) ? String(m.cfi || m.cfiRange) : '';
                            var excerpt = (m && m.excerpt) ? String(m.excerpt) : '';
                            if (!cfi) return;
                            results.push({ cfi: cfi, excerpt: excerpt, href: href, chapter: chapterLabel });
                        });
                    }
                } catch (e) {
                    // ignore per-section errors
                } finally {
                    try {
                        if (section && typeof section.unload === 'function') section.unload();
                    } catch (e) {}
                }
            }

            if (results.length === 0) {
                setSearchStatus('Tidak ada hasil untuk: ' + query);
                return;
            }

            setSearchStatus('Ditemukan ' + results.length + ' hasil untuk: ' + query);
            renderSearchResults(results.slice(0, 50), rendition);
        }

        // Load langsung dari folder - lebih cepat!
        var bookUrl = "uploads/files/<?= $book['file_path'] ?>";
        console.log("[INFO] Loading EPUB from:", bookUrl);
        console.log("[INFO] Book title:", "<?= $book['judul'] ?>");

        // Inisialisasi dengan timeout handler
        var loadTimeout = setTimeout(function() {
            console.warn("[WARNING] Loading taking longer than expected...");
        }, 3000);

        try {
            initLoaderOverlay();
            initSearchPanel();

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

                // Restore saved highlights for this book
                loadHighlights(rendition);
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

                    try {
                        lastCfi = location.start.cfi || '';
                        lastLabel = buildBookmarkLabel(location);
                    } catch (e) {}

                    // Clear any pending selection when page changes
                    pendingHighlightCfi = '';
                    pendingHighlightText = '';
                    pendingHighlightContents = null;
                }
            });

            // Highlight: select text first, then RIGHT-CLICK to confirm highlight
            rendition.on('selected', function (cfiRange, contents) {
                // Do not highlight yet; wait for right-click
                pendingHighlightCfi = cfiRange || '';
                pendingHighlightContents = contents || null;

                var selectedText = '';
                try {
                    selectedText = (contents && contents.window && contents.window.getSelection) ? String(contents.window.getSelection()) : '';
                } catch (e) {}
                pendingHighlightText = selectedText;
            });

            // Intercept right click inside iframe contents
            rendition.hooks.content.register(function (contents) {
                try {
                    if (!contents || !contents.document) return;
                    if (contents.document.__epubFstCtxMenuInstalled) return;
                    contents.document.__epubFstCtxMenuInstalled = true;

                    contents.document.addEventListener('contextmenu', function (e) {
                        // Only handle right-click if there is a pending selection
                        if (!pendingHighlightCfi) return;

                        var currentSelected = '';
                        try {
                            currentSelected = (contents.window && contents.window.getSelection) ? String(contents.window.getSelection()) : '';
                        } catch (err) {}

                        // If user right-clicked with no selection, let browser menu show
                        if (!currentSelected && !pendingHighlightText) return;

                        // Consume the context menu and treat it as "highlight"
                        e.preventDefault();
                        e.stopPropagation();

                        var cfiRange = pendingHighlightCfi;
                        var textForNote = currentSelected || pendingHighlightText || '';

                        // Reset pending early to avoid double triggers
                        pendingHighlightCfi = '';
                        pendingHighlightText = '';
                        pendingHighlightContents = null;

                        if (savedCfiRanges.has(cfiRange)) {
                            try {
                                if (contents.window && contents.window.getSelection) {
                                    contents.window.getSelection().removeAllRanges();
                                }
                            } catch (err) {}
                            return;
                        }
                        savedCfiRanges.add(cfiRange);

                        applyHighlight(rendition, cfiRange, 'yellow');

                        var noteText = prompt('Tulis catatan untuk teks ini (opsional):', '');
                        if (noteText === null) noteText = '';

                        postForm('action=save_note&book_id=' + encodeURIComponent(BOOK_ID)
                            + '&cfi=' + encodeURIComponent(cfiRange)
                            + '&text=' + encodeURIComponent(String(noteText || ''))
                            + '&color=' + encodeURIComponent('yellow'))
                            .then(function () {})
                            .catch(function (err) {
                                console.warn('[HIGHLIGHT] Save error:', err);
                            })
                            .finally(function () {
                                try {
                                    if (contents.window && contents.window.getSelection) {
                                        contents.window.getSelection().removeAllRanges();
                                    }
                                } catch (err) {}
                            });
                    }, { capture: true });
                } catch (e) {
                    // ignore
                }
            });

            // Navigation Buttons
            document.getElementById("nextBtn").addEventListener("click", function() {
                rendition.next();
            });
            
            document.getElementById("prevBtn").addEventListener("click", function() {
                rendition.prev();
            });

            // Bookmark buttons
            var addBookmarkBtn = document.getElementById('addBookmarkBtn');
            if (addBookmarkBtn) {
                addBookmarkBtn.addEventListener('click', function () { addBookmark(); });
            }
            var listBookmarksBtn = document.getElementById('listBookmarksBtn');
            if (listBookmarksBtn) {
                listBookmarksBtn.addEventListener('click', function () { listBookmarks(rendition); });
            }

            var listNotesBtn = document.getElementById('listNotesBtn');
            if (listNotesBtn) {
                listNotesBtn.addEventListener('click', function () { listNotes(rendition); });
            }

            var searchBtn = document.getElementById('searchBtn');
            if (searchBtn) {
                searchBtn.addEventListener('click', function () {
                    openSearchPanel();
                    if (searchInputEl) searchInputEl.value = lastSearchQuery || '';
                    clearSearchResults();
                    setSearchStatus('Ketik kata kunci lalu tekan Enter');
                });
            }

            if (searchCloseBtnEl) {
                searchCloseBtnEl.addEventListener('click', function () {
                    closeSearchPanel();
                });
            }

            if (searchGoBtnEl) {
                searchGoBtnEl.addEventListener('click', function () {
                    if (!searchInputEl) return;
                    searchInBook(book, rendition, searchInputEl.value);
                });
            }

            if (searchInputEl) {
                searchInputEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        searchInBook(book, rendition, searchInputEl.value);
                    }
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        closeSearchPanel();
                    }
                });
            }

            // Keyboard Navigation
            document.addEventListener("keyup", function(e) {
                if (e.key === "ArrowLeft") rendition.prev();
                if (e.key === "ArrowRight") rendition.next();
                if (e.key === "b" || e.key === "B") addBookmark();
                if (e.key === "n" || e.key === "N") listNotes(rendition);
                if (e.key === "f" || e.key === "F") {
                    openSearchPanel();
                }
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