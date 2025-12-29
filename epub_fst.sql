-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Des 2025 pada 05.36
-- Versi server: 10.4.27-MariaDB
-- Versi PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `epub_fst`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `cfi_range` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `penulis` varchar(100) NOT NULL,
  `last_read` datetime DEFAULT NULL,
  `kategori` varchar(50) NOT NULL,
  `cover` varchar(255) DEFAULT 'default_cover.jpg',
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `books`
--

INSERT INTO `books` (`id`, `judul`, `penulis`, `last_read`, `kategori`, `cover`, `file_path`, `uploaded_at`) VALUES
(6, 'test', 'alice', '2025-12-22 17:01:33', 'Informatika', '1765386730_WhatsApp Image 2025-11-13 at 12.04.45_05bb9bf9.png', '1765386730_pg11-images-3.epub', '2025-12-10 17:12:10'),
(7, 'FC25', 'algi', '2025-12-23 18:27:10', 'Biologi', 'default_book.png', '1766393394_shakespeare-hamlet.epub', '2025-12-22 08:49:54'),
(12, 'iphone', 'udin', '2025-12-23 18:28:56', 'Fisika', '1766489278_WIN_20241225_22_11_42_Pro.jpg', '1766489278_Alices Adventures in Wonderland.epub', '2025-12-23 11:27:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `cfi_range` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT 'yellow',
  `note_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `reading_progress`
--

CREATE TABLE `reading_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `reading_seconds` int(11) DEFAULT 0,
  `last_position` text DEFAULT NULL,
  `last_read` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reading_progress`
--

INSERT INTO `reading_progress` (`id`, `user_id`, `book_id`, `reading_seconds`, `last_position`, `last_read`) VALUES
(1, 2, 7, 124, NULL, '2025-12-22 10:20:31'),
(3, 2, 6, 4, NULL, '2025-12-22 10:01:33'),
(4, 4, 7, 4, NULL, '2025-12-23 10:37:12'),
(6, 6, 7, 7, NULL, '2025-12-22 10:12:33'),
(12, 10, 7, 26, NULL, '2025-12-23 11:27:10'),
(13, 10, 12, 10, NULL, '2025-12-23 11:28:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','admin') DEFAULT 'mahasiswa',
  `profile_photo` varchar(255) DEFAULT 'default_user.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nim`, `nama`, `password`, `role`, `profile_photo`) VALUES
(1, '11230910000064', 'Nizar Ahmad Barelvi', '$2y$10$84GdGYbd2OZ.kYdlSYnzn.E7waX3lcLYNiWMm40W3KPaZQCW67GCS', 'mahasiswa', '1765198514_WIN_20250707_15_03_38_Pro.jpg'),
(4, '123', 'trkto', '$2y$10$Qq1wm91XK.LpGvbbg.CHvOJSloGKoAAjgosWu22hO5gHDGEogieXC', 'admin', 'default_user.png'),
(6, '12333', 'udin', '$2y$10$e0hbXLHaWGSv2D2sRwDwCu3AVLEfREG3Vta0pRO0gMQclGAjXko9e', 'mahasiswa', 'default_user.png'),
(7, '11230910000100', 'Muhammad Ghaffar Rahmatullah', '$2y$10$QeHvWQ5iBJODHUOaGa7Yl.Vns4.7vh7C6U2.C64gKldEiLmrUpLZ.', 'mahasiswa', 'default_user.png'),
(8, '11230910000070', 'ali', '$2y$10$Elqf1Q3YB/XXQb69ARpf7eGWRkqqwxhQyokGMLyZZBbeTVg2kpP3O', 'mahasiswa', 'default_user.png'),
(10, '11230910000200', 'gofar', '$2y$10$b8lSYzCRSjvzQIid31iA1.v4l7kLZT9yu95z2d3c5fJ5deF7VB7Se', 'mahasiswa', 'default_user.png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_stats`
--

CREATE TABLE `user_stats` (
  `user_id` int(11) NOT NULL,
  `total_seconds` int(11) DEFAULT 0,
  `books_opened` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_stats`
--

INSERT INTO `user_stats` (`user_id`, `total_seconds`, `books_opened`) VALUES
(2, 193, 1),
(4, 44, 1),
(6, 18, 1),
(7, 13, 1),
(10, 36, 1);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_book` (`user_id`,`book_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`);

--
-- Indeks untuk tabel `user_stats`
--
ALTER TABLE `user_stats`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `reading_progress`
--
ALTER TABLE `reading_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
