-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Nov 2025 pada 10.23
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sosialmediadb`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `comment`
--

CREATE TABLE `comment` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `comment`
--

INSERT INTO `comment` (`comment_id`, `post_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 2, 'Fotomu bagus.', '2025-11-26 09:20:23'),
(2, 2, 3, 'Keren tempatnya.', '2025-11-26 09:20:23'),
(3, 3, 4, 'Pengen ke sini juga.', '2025-11-26 09:20:23'),
(4, 4, 5, 'Cakep banget.', '2025-11-26 09:20:23'),
(5, 5, 1, 'Ini favoritku.', '2025-11-26 09:20:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `location`
--

CREATE TABLE `location` (
  `location_id` int(11) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `location`
--

INSERT INTO `location` (`location_id`, `latitude`, `longitude`, `address`, `city`, `country`) VALUES
(1, -6.2000000, 106.8166600, 'Jl. Merdeka No. 10', 'Jakarta', 'Indonesia'),
(2, -6.9147440, 107.6098100, 'Jl. Asia Afrika No. 1', 'Bandung', 'Indonesia'),
(3, -7.2504450, 112.7688450, 'Jl. Tunjungan No. 5', 'Surabaya', 'Indonesia'),
(4, -0.9242800, 100.4034650, 'Jl. Sudirman No. 8', 'Padang', 'Indonesia'),
(5, -8.6500000, 115.2166670, 'Jl. Kuta Raya No. 12', 'Bali', 'Indonesia');

-- --------------------------------------------------------

--
-- Struktur dari tabel `post`
--

CREATE TABLE `post` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `post_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `post`
--

INSERT INTO `post` (`post_id`, `user_id`, `location_id`, `picture`, `title`, `caption`, `post_url`, `created_at`) VALUES
(1, 1, 1, 'jakarta1.jpg', 'Pagi di Jakarta', 'Jalanan masih sepi.', 'https://example.com/p/1', '2025-11-26 09:19:57'),
(2, 2, 2, 'bandung1.jpg', 'Ngopi di Bandung', 'Nyaman sekali suasananya.', 'https://example.com/p/2', '2025-11-26 09:19:57'),
(3, 3, 3, 'surabaya1.jpg', 'Tunjungan Night', 'Ramai seperti biasa.', 'https://example.com/p/3', '2025-11-26 09:19:57'),
(4, 4, 4, 'padang1.jpg', 'Langit Padang', 'Mendung tapi indah.', 'https://example.com/p/4', '2025-11-26 09:19:57'),
(5, 5, 5, 'bali1.jpg', 'Pantai Bali', 'Sunsetnya mantap.', 'https://example.com/p/5', '2025-11-26 09:19:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `token`
--

CREATE TABLE `token` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `token`
--

INSERT INTO `token` (`token_id`, `user_id`, `token`, `created_at`, `updated_at`) VALUES
(1, 1, 'token_001', '2025-11-26 09:21:49', '2025-11-26 09:21:49'),
(2, 2, 'token_002', '2025-11-26 09:21:49', '2025-11-26 09:21:49'),
(3, 3, 'token_003', '2025-11-26 09:21:49', '2025-11-26 09:21:49'),
(4, 4, 'token_004', '2025-11-26 09:21:49', '2025-11-26 09:21:49'),
(5, 5, 'token_005', '2025-11-26 09:21:49', '2025-11-26 09:21:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `verified_status` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `role`, `password`, `email`, `profile_picture`, `verification_code`, `verification_token`, `verified_status`, `created_at`, `username`) VALUES
(1, 'superadmin', 'admin123', 'admin@example.com', 'profile1.jpg', 'vc_001', 'token_001', 1, '2025-11-26 09:19:23', 'admin_master'),
(2, 'admin', 'password123', 'john@example.com', 'john.jpg', 'vc_002', 'token_002', 0, '2025-11-26 09:19:23', 'johndoe'),
(3, 'guest', 'maria123', 'maria@example.com', 'maria.png', 'vc_003', 'token_003', 1, '2025-11-26 09:19:23', 'maria'),
(4, 'guest', 'eko123', 'eko@example.com', 'eko.png', 'vc_004', 'token_004', 0, '2025-11-26 09:19:23', 'eko'),
(5, 'admin', 'sarah123', 'sarah@example.com', 'sarah.jpg', 'vc_005', 'token_005', 1, '2025-11-26 09:19:23', 'sarah');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `fk_comment_post` (`post_id`),
  ADD KEY `fk_comment_user` (`user_id`);

--
-- Indeks untuk tabel `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`location_id`);

--
-- Indeks untuk tabel `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `fk_post_user` (`user_id`),
  ADD KEY `fk_post_location` (`location_id`);

--
-- Indeks untuk tabel `token`
--
ALTER TABLE `token`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `fk_token_user` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `comment`
--
ALTER TABLE `comment`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `post`
--
ALTER TABLE `post`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `token`
--
ALTER TABLE `token`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `fk_comment_post` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `fk_post_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `token`
--
ALTER TABLE `token`
  ADD CONSTRAINT `fk_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
