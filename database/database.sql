-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 05 Jul 2025 pada 04.44
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
-- Database: `creative`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `accounts`
--

INSERT INTO `accounts` (`id`, `name`, `created_by`, `created_at`) VALUES
(1, 'Inilah.com', 1, '2025-06-04 11:10:10'),
(2, 'Inilah Arena', 1, '2025-06-04 11:10:16'),
(3, 'Inilah Politik', 1, '2025-06-04 11:10:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `agenda_items`
--

CREATE TABLE `agenda_items` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `type` enum('redaksi','settings') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `agenda_items`
--

INSERT INTO `agenda_items` (`id`, `title`, `date`, `type`, `created_at`, `updated_at`) VALUES
(1, 'Kementerian Perdagangan', '2025-06-22', 'settings', '2025-06-22 01:27:41', '2025-06-22 01:27:41'),
(2, 'Amankan Isu Haji Isam', '2025-06-22', 'redaksi', '2025-06-22 01:27:52', '2025-06-22 01:27:52'),
(3, 'Kementerian Perhubungan', '2025-06-22', 'settings', '2025-06-22 01:51:26', '2025-06-22 01:51:26'),
(4, 'Kementerian PUPR', '2025-06-22', 'settings', '2025-06-22 01:51:38', '2025-06-22 01:51:38'),
(5, 'Kemenko Pangan', '2025-06-22', 'settings', '2025-06-22 01:52:00', '2025-06-22 01:52:00'),
(6, 'Kemendikdasmen', '2025-06-22', 'settings', '2025-06-22 01:52:21', '2025-06-22 01:52:21'),
(7, 'Kementerian Perhutanan', '2025-06-22', 'settings', '2025-06-22 01:52:40', '2025-06-22 01:52:40'),
(8, 'Kementerian Lingkungan Hidup', '2025-06-22', 'settings', '2025-06-22 01:53:16', '2025-06-22 01:53:16'),
(9, 'Pantai Indah Kapuk', '2025-06-23', 'settings', '2025-06-22 02:04:29', '2025-06-22 02:04:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_important` tinyint(1) DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `target_role` varchar(50) DEFAULT NULL COMMENT 'NULL berarti untuk semua role'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_by`, `created_at`, `is_important`, `image_path`, `target_role`) VALUES
(1, 'Halo ini pengumuman loh', 'hahaha hihihi huhuhu', 1, '2025-06-19 08:25:46', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `announcement_comments`
--

CREATE TABLE `announcement_comments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `announcement_comments`
--

INSERT INTO `announcement_comments` (`id`, `announcement_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 1, 13, 'siap bos', '2025-06-19 08:27:13'),
(2, 1, 9, 'okeh siappp', '2025-06-19 08:27:47');

-- --------------------------------------------------------

--
-- Struktur dari tabel `announcement_reactions`
--

CREATE TABLE `announcement_reactions` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `announcement_reactions`
--

INSERT INTO `announcement_reactions` (`id`, `announcement_id`, `user_id`, `reaction_type`, `created_at`) VALUES
(1, 1, 13, 'like', '2025-06-19 08:27:05'),
(2, 1, 9, 'like', '2025-06-19 08:27:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Daily Content', 'Konten harian untuk media sosial'),
(2, 'Program', 'Konten untuk program khusus'),
(3, 'Produksi', 'Konten untuk produksi internal'),
(4, 'Distribusi', 'Konten untuk distribusi eksternal'),
(5, 'Publikasi', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_last_checked`
--

CREATE TABLE `chat_last_checked` (
  `user_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `last_checked_id` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
(1, 9, 2, 'cek', 1, '2025-06-22 18:46:22'),
(2, 2, 1, 'hai', 1, '2025-06-22 18:46:45'),
(3, 2, 9, 'hai', 1, '2025-06-22 18:47:25'),
(4, 2, 9, 'gmn kabarnya?', 1, '2025-06-22 18:47:31'),
(5, 9, 2, 'oke aman', 1, '2025-06-22 18:47:41'),
(6, 2, 9, 'mantap juga nih', 1, '2025-06-22 18:48:05'),
(7, 2, 9, 'hai', 1, '2025-06-22 18:57:44'),
(8, 9, 2, 'apa', 1, '2025-06-22 18:57:56'),
(9, 2, 1, 'kak', 1, '2025-06-22 18:58:14'),
(10, 1, 2, 'iya', 1, '2025-06-22 18:58:23'),
(11, 9, 2, 'hai', 1, '2025-06-22 19:00:23'),
(12, 2, 1, 'oke', 1, '2025-06-22 19:00:30'),
(13, 2, 9, 'apaan', 1, '2025-06-22 19:00:32'),
(14, 9, 2, 'haha', 1, '2025-06-22 19:01:24'),
(15, 9, 4, 'hai', 1, '2025-06-22 19:01:33'),
(16, 2, 9, 'apah', 1, '2025-06-22 19:01:55'),
(17, 2, 9, 'hai bang', 1, '2025-06-22 19:14:16'),
(18, 9, 2, 'apaan?', 1, '2025-06-22 19:14:30'),
(19, 9, 2, 'iya?', 1, '2025-06-22 19:14:53'),
(20, 9, 2, 'hahaha', 1, '2025-06-22 19:15:15'),
(21, 2, 9, 'siap', 1, '2025-06-22 19:15:20'),
(22, 9, 2, 'mantap', 1, '2025-06-22 19:15:24'),
(23, 2, 9, 'okeh', 1, '2025-06-22 19:15:28'),
(24, 9, 2, 'hai', 1, '2025-06-22 19:17:00'),
(25, 9, 2, 'cek', 1, '2025-06-22 19:22:09'),
(26, 2, 9, 'oke', 1, '2025-06-22 19:22:21'),
(27, 9, 2, 'halo', 1, '2025-06-22 19:28:29'),
(28, 2, 9, 'iya?', 1, '2025-06-22 19:28:41'),
(29, 9, 2, 'kamu siapa?', 1, '2025-06-22 19:29:19'),
(30, 9, 2, 'cek', 1, '2025-06-22 19:42:36'),
(31, 2, 9, 'oke', 1, '2025-06-22 19:42:45'),
(32, 9, 2, 'hah', 1, '2025-06-22 19:42:55'),
(33, 2, 9, 'siap', 1, '2025-06-22 19:43:05'),
(34, 9, 2, 'mantap', 1, '2025-06-22 19:43:12'),
(35, 9, 2, 'mantap', 1, '2025-06-22 19:57:33'),
(36, 9, 2, 'hah', 1, '2025-06-22 19:57:37'),
(37, 9, 2, 'cek', 1, '2025-06-22 20:00:41'),
(38, 2, 9, 'apaan', 1, '2025-06-22 20:00:52'),
(39, 9, 2, 'enggak', 1, '2025-06-22 20:01:22'),
(40, 2, 9, 'cek', 1, '2025-06-22 20:58:28'),
(41, 9, 2, 'oke', 1, '2025-06-22 20:58:50'),
(42, 9, 2, 'hah', 1, '2025-06-22 21:00:47'),
(43, 9, 2, 'cek', 1, '2025-06-22 21:06:32'),
(44, 2, 9, 'iya', 1, '2025-06-22 21:06:56'),
(45, 9, 2, 'afadsgasg', 1, '2025-06-22 21:16:56'),
(46, 2, 9, 'sdfsdfdas aaa', 1, '2025-06-22 21:17:04'),
(47, 2, 9, 'asfasfsafasf', 1, '2025-06-22 21:31:26'),
(48, 2, 9, 'dsgsagsa', 1, '2025-06-22 21:32:19'),
(49, 2, 9, 'dassaga', 1, '2025-06-22 21:38:26'),
(50, 9, 2, 'sgdasag', 1, '2025-06-22 21:38:31'),
(51, 2, 9, 'cek sound', 1, '2025-06-22 21:40:45'),
(52, 2, 9, 'hahah\nhahaha', 1, '2025-06-22 21:40:50'),
(53, 9, 2, 'mantap asli', 1, '2025-06-22 21:41:11'),
(54, 2, 1, 'kak', 1, '2025-06-23 07:08:06'),
(55, 1, 2, 'okeh', 1, '2025-06-23 07:08:20'),
(56, 2, 9, 'ntaps', 1, '2025-06-23 07:15:54'),
(57, 2, 9, 'hahaha', 1, '2025-06-23 07:16:05'),
(58, 2, 1, 'mantap', 1, '2025-06-23 07:16:13'),
(59, 2, 9, 'hah', 1, '2025-06-23 07:16:23'),
(60, 2, 9, 'cek', 1, '2025-06-23 07:19:24'),
(61, 2, 9, 'haha', 1, '2025-06-23 07:19:39'),
(62, 2, 9, 'haha', 1, '2025-06-23 07:21:04'),
(63, 2, 9, 'waduh', 1, '2025-06-23 07:21:09'),
(64, 2, 9, 'cek', 1, '2025-06-23 07:22:12'),
(65, 2, 9, 'nah ini mantap', 1, '2025-06-23 07:22:19'),
(66, 2, 9, 'hai', 1, '2025-06-23 07:29:17'),
(67, 2, 9, 'okeh', 1, '2025-06-23 07:36:25'),
(68, 9, 2, 'apaan nih', 1, '2025-06-23 07:36:32'),
(69, 4, 9, 'apaan?', 1, '2025-06-23 07:38:13'),
(70, 4, 9, 'jawab', 1, '2025-06-23 07:38:21'),
(71, 9, 4, 'oke', 1, '2025-06-23 07:39:31'),
(72, 9, 2, 'udah dong', 1, '2025-06-23 07:39:38'),
(73, 9, 2, 'oke', 1, '2025-06-23 07:48:21'),
(74, 9, 1, 'bang', 1, '2025-06-23 07:48:48'),
(75, 1, 9, 'yoii', 1, '2025-06-23 07:49:18'),
(76, 8, 1, 'cek cek', 1, '2025-06-23 14:50:26'),
(77, 1, 8, 'yoiixxx', 1, '2025-06-23 14:50:34'),
(78, 3, 8, 'Man', 1, '2025-06-23 14:52:01'),
(79, 8, 3, 'ouy', 1, '2025-06-23 14:52:15'),
(80, 1, 8, 'cocok', 1, '2025-06-23 14:59:12'),
(81, 1, 3, 'hai cowok', 0, '2025-06-23 14:59:16'),
(82, 1, 8, 'man', 1, '2025-06-23 15:55:45'),
(83, 1, 8, 'woy', 1, '2025-06-23 16:09:08'),
(84, 8, 1, 'yoyoy', 1, '2025-06-23 16:09:15'),
(85, 1, 8, 'mantap', 1, '2025-06-23 16:09:26'),
(86, 8, 1, 'tihati ada mbak olga', 1, '2025-06-23 16:09:35'),
(87, 1, 6, 'hai ganteng', 1, '2025-06-23 16:23:46'),
(88, 6, 1, 'anjay', 1, '2025-06-23 16:23:55'),
(89, 6, 8, 'oi kang', 1, '2025-06-23 16:24:55'),
(90, 9, 6, 'hai tampan', 1, '2025-06-23 16:26:17'),
(91, 8, 6, 'bau bau bau', 1, '2025-06-23 16:26:51'),
(92, 13, 1, 'mas', 1, '2025-06-25 12:01:24'),
(93, 1, 13, 'iya?', 1, '2025-06-25 12:01:33'),
(94, 13, 1, 'cek doang', 1, '2025-06-25 12:01:48'),
(95, 13, 1, 'okeh', 1, '2025-06-25 12:03:18'),
(96, 13, 1, 'cek', 1, '2025-06-25 12:23:39'),
(97, 1, 13, 'oke', 1, '2025-06-25 12:23:46'),
(98, 1, 13, 'oke', 1, '2025-06-25 12:23:51'),
(99, 13, 1, 'cek', 1, '2025-06-25 12:27:30'),
(100, 1, 13, 'nah gini dong', 1, '2025-06-25 12:27:38'),
(101, 13, 1, 'sip', 1, '2025-06-25 12:27:41'),
(102, 2, 9, 'ok', 1, '2025-06-25 12:44:14'),
(103, 9, 1, 'cek', 1, '2025-06-25 13:44:42'),
(104, 1, 9, 'yoi', 1, '2025-06-25 13:44:49'),
(105, 1, 2, 'hai', 1, '2025-06-25 14:25:35'),
(106, 1, 2, 'cek', 1, '2025-06-26 12:27:16'),
(107, 2, 1, 'siap', 1, '2025-06-26 12:27:27'),
(108, 20, 2, 'hai', 1, '2025-07-01 17:50:17'),
(109, 2, 20, 'oke', 1, '2025-07-01 17:50:23'),
(110, 2, 1, 'cek', 1, '2025-07-04 13:31:37'),
(111, 1, 2, 'oke', 1, '2025-07-04 13:31:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `content_pillars`
--

CREATE TABLE `content_pillars` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `content_pillars`
--

INSERT INTO `content_pillars` (`id`, `category_id`, `name`) VALUES
(1, 1, 'News & Information'),
(2, 1, 'Follow The Trend'),
(3, 2, 'Tukar Pikiran'),
(4, 2, 'Kick-Off'),
(5, 2, 'Suara dari Rimba'),
(7, 3, 'Marketing'),
(8, 3, 'Event'),
(9, 3, 'Operasional'),
(12, 2, 'Garis Besar'),
(13, 4, 'Konten Berbayar'),
(14, 4, 'Media Partner'),
(15, 3, 'Redaksi'),
(16, 3, 'Program'),
(17, 2, 'Ucapan Hari Besar'),
(18, 2, 'Jurnalisik'),
(19, 2, 'Podcast Jurnalisik'),
(20, 5, 'Artikel Berbayar'),
(21, 5, 'Media Partner');

-- --------------------------------------------------------

--
-- Struktur dari tabel `content_types`
--

CREATE TABLE `content_types` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `content_types`
--

INSERT INTO `content_types` (`id`, `category_id`, `name`) VALUES
(1, 1, 'Single Images'),
(2, 1, 'Carousel'),
(3, 1, 'Infografis'),
(4, 1, 'Ilustrasi'),
(5, 1, 'Reels'),
(6, 1, 'Story'),
(7, 2, 'Single Images'),
(13, 3, 'Images'),
(14, 3, 'Video'),
(15, 3, 'Ilustrasi'),
(16, 3, 'Infografis'),
(17, 3, 'Pitchdeck'),
(18, 4, 'Images'),
(19, 4, 'Video'),
(20, 4, 'Link'),
(22, 2, 'Reels'),
(23, 2, 'Ilustrasi'),
(24, 2, 'Youtube Video'),
(28, 5, 'Press Release'),
(29, 5, 'Advertorial');

-- --------------------------------------------------------

--
-- Struktur dari tabel `distribution_platforms`
--

CREATE TABLE `distribution_platforms` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `distribution_platforms`
--

INSERT INTO `distribution_platforms` (`id`, `task_id`, `platform_id`, `created_at`) VALUES
(1, 35, 1, '2025-06-09 07:33:57'),
(2, 35, 2, '2025-06-09 07:33:57'),
(3, 35, 3, '2025-06-09 07:33:57'),
(4, 35, 4, '2025-06-09 07:33:57'),
(5, 35, 5, '2025-06-09 07:33:57'),
(6, 35, 6, '2025-06-09 07:33:57'),
(7, 34, 1, '2025-06-09 07:34:09'),
(8, 34, 2, '2025-06-09 07:34:09'),
(9, 34, 3, '2025-06-09 07:34:09'),
(10, 34, 4, '2025-06-09 07:34:09'),
(11, 34, 5, '2025-06-09 07:34:09'),
(12, 34, 6, '2025-06-09 07:34:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `general_info`
--

CREATE TABLE `general_info` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `general_info`
--

INSERT INTO `general_info` (`id`, `content`, `updated_at`) VALUES
(1, '<h3> Jangan lupa absen gaes, kalo gak mau dipotong gajinya. nanti kalo dipotong, nangeeeesss :P</h3>', '2025-06-22 05:36:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=3', 1, '2025-06-04 12:17:33'),
(2, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=3', 1, '2025-06-04 12:29:30'),
(3, 2, 'Task telah siap direview', 'view_task.php?id=3', 1, '2025-06-04 12:37:56'),
(4, 9, 'Task memerlukan revisi', 'view_task.php?id=3', 1, '2025-06-04 12:40:50'),
(5, 2, 'Task telah siap direview', 'view_task.php?id=3', 1, '2025-06-04 12:41:15'),
(6, 1, 'Task baru menunggu verifikasi', 'view_task.php?id=3', 1, '2025-06-04 12:41:57'),
(7, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=3', 1, '2025-06-04 12:51:36'),
(8, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=3', 1, '2025-06-04 12:51:36'),
(9, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=4', 1, '2025-06-04 13:10:42'),
(10, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=4', 1, '2025-06-04 13:14:46'),
(11, 2, 'Task telah siap direview', 'view_task.php?id=4', 1, '2025-06-04 13:17:38'),
(12, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=5', 1, '2025-06-04 13:21:50'),
(13, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=5', 1, '2025-06-04 13:21:57'),
(14, 2, 'Task telah siap direview', 'view_task.php?id=5', 1, '2025-06-04 13:24:26'),
(15, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=6', 1, '2025-06-04 13:31:45'),
(16, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=6', 1, '2025-06-04 13:31:50'),
(17, 2, 'Task telah siap direview', 'view_task.php?id=6', 1, '2025-06-04 13:32:24'),
(18, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=7', 1, '2025-06-04 13:47:01'),
(19, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=7', 1, '2025-06-04 14:01:02'),
(20, 2, 'Task telah siap direview', 'view_task.php?id=7', 1, '2025-06-04 14:02:46'),
(21, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=8', 1, '2025-06-04 14:09:12'),
(22, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=8', 1, '2025-06-04 14:09:20'),
(23, 2, 'Task telah siap direview', 'view_task.php?id=8', 1, '2025-06-04 14:10:23'),
(24, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=9', 1, '2025-06-04 14:19:31'),
(25, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=9', 1, '2025-06-04 14:19:38'),
(26, 2, 'Task telah siap direview', 'view_task.php?id=9', 1, '2025-06-04 14:20:31'),
(27, 9, 'Task memerlukan revisi', 'view_task.php?id=9', 1, '2025-06-04 14:24:38'),
(28, 9, 'Task memerlukan revisi', 'view_task.php?id=4', 1, '2025-06-04 14:25:01'),
(29, 2, 'Task telah siap direview', 'view_task.php?id=4', 1, '2025-06-04 14:26:11'),
(30, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=10', 1, '2025-06-04 14:28:26'),
(31, 2, 'Task telah siap direview', 'view_task.php?id=9', 1, '2025-06-04 14:29:38'),
(32, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=10', 1, '2025-06-04 14:29:53'),
(33, 2, 'Task telah siap direview', 'view_task.php?id=10', 1, '2025-06-04 14:35:04'),
(34, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=11', 1, '2025-06-04 14:35:43'),
(35, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=11', 1, '2025-06-04 14:35:56'),
(36, 2, 'Task telah siap direview', 'view_task.php?id=11', 1, '2025-06-04 14:36:41'),
(37, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=12', 1, '2025-06-04 14:37:16'),
(38, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=12', 1, '2025-06-04 14:37:27'),
(39, 2, 'Task telah siap direview', 'view_task.php?id=12', 1, '2025-06-04 14:38:14'),
(40, 10, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=13', 0, '2025-06-04 14:47:39'),
(41, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=13', 1, '2025-06-04 14:47:59'),
(42, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=13', 1, '2025-06-04 14:48:21'),
(43, 2, 'Task #13 telah selesai dikerjakan dan siap untuk direview', '../content/review_task.php?id=13', 1, '2025-06-04 14:58:17'),
(44, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=14', 1, '2025-06-04 14:59:24'),
(45, 2, 'Task siap untuk direview: Megawati dan Prabowo Makin Hangat di Hari Pancasila, Ini Kata Pengamat', '../content/view_task.php?id=14', 1, '2025-06-04 15:37:46'),
(46, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=15', 1, '2025-06-04 15:39:23'),
(47, 2, 'Task siap untuk direview: Visa Furoda tak Terbit! Wendy Cagur dan Istri Batal Berangkat Haji', '../content/view_task.php?id=15', 1, '2025-06-04 15:42:44'),
(48, 9, 'Task telah disetujui: Visa Furoda tak Terbit! Wendy Cagur dan Istri Batal Berangkat Haji', '../production/view_task.php?id=15', 1, '2025-06-04 15:52:49'),
(49, 9, 'Task perlu direvisi: Megawati dan Prabowo Makin Hangat di Hari Pancasila, Ini Kata Pengamat', '../production/view_task.php?id=14', 1, '2025-06-04 15:54:49'),
(50, 2, 'Task siap untuk direview: Megawati dan Prabowo Makin Hangat di Hari Pancasila, Ini Kata Pengamat', '../content/view_task.php?id=14', 1, '2025-06-04 15:55:21'),
(51, 9, 'Task perlu direvisi: Megawati dan Prabowo Makin Hangat di Hari Pancasila, Ini Kata Pengamat', '../production/view_task.php?id=14', 1, '2025-06-04 15:59:42'),
(52, 2, 'Task siap untuk direview: Megawati dan Prabowo Makin Hangat di Hari Pancasila, Ini Kata Pengamat', '../content/view_task.php?id=14', 1, '2025-06-04 16:00:02'),
(53, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=16', 1, '2025-06-04 16:02:39'),
(54, 2, 'Task siap untuk direview: Curhat Jadi Ketum Parpol di Indonesia Susah, AHY Bandingkan dengan Sistem Single Party China', '../content/view_task.php?id=16', 1, '2025-06-04 16:02:55'),
(55, 9, 'Task perlu direvisi: Curhat Jadi Ketum Parpol di Indonesia Susah, AHY Bandingkan dengan Sistem Single Party China', '../production/view_task.php?id=16', 1, '2025-06-04 16:04:47'),
(56, 2, 'Task siap untuk direview: Curhat Jadi Ketum Parpol di Indonesia Susah, AHY Bandingkan dengan Sistem Single Party China', '../content/view_task.php?id=16', 1, '2025-06-04 16:10:46'),
(57, 9, 'Task telah disetujui: Curhat Jadi Ketum Parpol di Indonesia Susah, AHY Bandingkan dengan Sistem Single Party China', '../production/view_task.php?id=16', 1, '2025-06-04 16:23:04'),
(58, 9, 'Task telah disetujui: Sri Mulyani Tetapkan Tarif Hotel Menteri saat Perjalanan Dinas, Maksimal Rp9,3 Juta per Malam', '../production/view_task.php?id=7', 1, '2025-06-04 16:24:07'),
(59, 9, 'Task telah disetujui: Megawati dan Prabowo Makin Hangat di Hari Pancasila, Ini Kata Pengamat', '../production/view_task.php?id=14', 1, '2025-06-04 16:24:12'),
(60, 9, 'Task telah disetujui: Ngeri! Truk Tabrak Pembatas Gate Tol Jagorawi, Diduga Kelebihan Muatan', '../production/view_task.php?id=9', 1, '2025-06-04 16:24:28'),
(61, 9, 'Task telah disetujui: Visa Furoda tak Terbit! Wendy Cagur dan Istri Batal Berangkat Haji', '../production/view_task.php?id=8', 1, '2025-06-04 16:24:32'),
(62, 9, 'Task telah disetujui: *Gaji ke-13 ASN Cair!* Sri Mulyani Siapkan hingga Rp49,3 Triliun', '../production/view_task.php?id=13', 1, '2025-06-04 16:24:35'),
(63, 9, 'Task telah disetujui: Sri Mulyani Tetapkan Biaya Konsumsi Rapat Menteri Rp171.000 per Orang', '../production/view_task.php?id=12', 1, '2025-06-04 16:24:44'),
(64, 9, 'Task telah disetujui: Brutal! 19 Napi Lapas Nabire Kabur dan Serang Petugas, 11 Diantaranya Anggota KKB', '../production/view_task.php?id=11', 1, '2025-06-04 16:24:52'),
(65, 9, 'Task telah disetujui: Terjadi kesalahan saat memulai tracking', '../production/view_task.php?id=10', 1, '2025-06-04 16:24:56'),
(66, 9, 'Task telah disetujui: Pengumuman! Pemerintah Batal Beri Diskon Tarif Listrik 50% pada Juni-Juli', '../production/view_task.php?id=6', 1, '2025-06-04 16:25:00'),
(67, 9, 'Task telah disetujui: Prabowo Singkirkan Pejabat yang tak Bisa Kerja, *PKB Dukung Penuh!*', '../production/view_task.php?id=5', 1, '2025-06-04 16:25:03'),
(68, 9, 'Task telah disetujui: Candaan Prabowo saat Bertemu Megawati: Ibu Kurus, Dietnya Berhasil', '../production/view_task.php?id=4', 1, '2025-06-04 16:25:06'),
(69, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=17', 1, '2025-06-04 16:25:41'),
(70, 2, 'Task siap untuk direview: Inter Milan dan Simone Inzaghi Resmi Berpisah, Ini Alasan di Baliknya', '../content/view_task.php?id=17', 1, '2025-06-04 16:26:16'),
(71, 9, 'Task telah disetujui: Inter Milan dan Simone Inzaghi Resmi Berpisah, Ini Alasan di Baliknya', '../production/view_task.php?id=17', 1, '2025-06-04 16:26:50'),
(72, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=18', 1, '2025-06-04 16:28:03'),
(73, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=18', 1, '2025-06-04 16:28:37'),
(74, 2, 'Task siap untuk direview: Perempat Final Roland Garros 2025 Djokovic Vs Zverev, Duel Dua Sahabat di Tanah Liat', '../content/view_task.php?id=18', 1, '2025-06-04 16:28:58'),
(75, 9, 'Task telah disetujui: Perempat Final Roland Garros 2025 Djokovic Vs Zverev, Duel Dua Sahabat di Tanah Liat', '../production/view_task.php?id=18', 1, '2025-06-04 16:29:15'),
(76, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=4', 1, '2025-06-04 17:01:44'),
(77, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=4', 1, '2025-06-04 17:01:44'),
(78, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=5', 1, '2025-06-04 17:01:49'),
(79, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=5', 1, '2025-06-04 17:01:49'),
(80, 2, 'Task memerlukan revisi dari Creative Director', 'view_task.php?id=6', 1, '2025-06-04 17:02:03'),
(81, 9, 'Task memerlukan revisi dari Creative Director', 'view_task.php?id=6', 1, '2025-06-04 17:02:03'),
(83, 2, 'Task siap untuk direview: Pengumuman! Pemerintah Batal Beri Diskon Tarif Listrik 50% pada Juni-Juli', '../content/view_task.php?id=6', 1, '2025-06-04 17:02:20'),
(84, 9, 'Task telah disetujui: Pengumuman! Pemerintah Batal Beri Diskon Tarif Listrik 50% pada Juni-Juli', '../production/view_task.php?id=6', 1, '2025-06-04 17:02:36'),
(85, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=6', 1, '2025-06-04 17:02:44'),
(86, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=6', 1, '2025-06-04 17:02:44'),
(87, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=10', 1, '2025-06-04 17:02:49'),
(88, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=10', 1, '2025-06-04 17:02:49'),
(89, 2, 'Task telah diverifikasi dengan rating 3 ★', 'view_task.php?id=11', 1, '2025-06-04 17:02:51'),
(90, 9, 'Task telah diverifikasi dengan rating 3 ★', 'view_task.php?id=11', 1, '2025-06-04 17:02:51'),
(91, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=12', 1, '2025-06-04 17:02:55'),
(92, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=12', 1, '2025-06-04 17:02:55'),
(93, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=9', 1, '2025-06-04 17:06:38'),
(94, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=9', 1, '2025-06-04 17:06:38'),
(95, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=7', 1, '2025-06-04 17:06:47'),
(96, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=7', 1, '2025-06-04 17:06:47'),
(97, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=13', 1, '2025-06-04 17:06:51'),
(98, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=13', 1, '2025-06-04 17:06:51'),
(99, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=8', 1, '2025-06-04 17:06:54'),
(100, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=8', 1, '2025-06-04 17:06:54'),
(101, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=14', 1, '2025-06-04 17:06:58'),
(102, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=14', 1, '2025-06-04 17:06:58'),
(103, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=16', 1, '2025-06-04 17:07:02'),
(104, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=16', 1, '2025-06-04 17:07:02'),
(105, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=17', 1, '2025-06-04 17:07:05'),
(106, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=17', 1, '2025-06-04 17:07:05'),
(107, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=15', 1, '2025-06-04 17:07:09'),
(108, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=15', 1, '2025-06-04 17:07:09'),
(109, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=18', 1, '2025-06-04 17:07:12'),
(110, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=18', 1, '2025-06-04 17:07:12'),
(111, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=19', 1, '2025-06-04 17:18:35'),
(112, 2, 'Task telah dikonfirmasi oleh production team', 'view_task.php?id=19', 1, '2025-06-04 17:18:57'),
(113, 2, 'Task siap untuk direview: Bukan \'Omon-omon\', Kluivert Siap Tebus Janji Kemenangan Lawan China', '../content/view_task.php?id=19', 1, '2025-06-04 17:19:21'),
(114, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=20', 1, '2025-06-04 17:23:39'),
(115, 2, 'Task siap untuk direview: Keangkeran Stadion GBK Bukan Ancaman', '../content/view_task.php?id=20', 1, '2025-06-04 17:24:06'),
(116, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=21', 1, '2025-06-04 17:25:11'),
(117, 2, 'Task siap untuk direview: Rayakan 30 Tahun Kemenangan Le Mans', '../content/view_task.php?id=21', 1, '2025-06-04 17:27:44'),
(118, 9, 'Task perlu direvisi: Rayakan 30 Tahun Kemenangan Le Mans', '../production/view_task.php?id=21', 1, '2025-06-04 17:27:56'),
(119, 2, 'Task siap untuk direview: Rayakan 30 Tahun Kemenangan Le Mans', '../content/view_task.php?id=21', 1, '2025-06-04 17:28:10'),
(120, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=22', 1, '2025-06-04 17:28:54'),
(121, 8, 'Anda mendapat tugas baru: Jokowi-Gibran Diminta Dukung Kedekatan Prabowo-Megawati', '../production/view_task.php?id=22', 1, '2025-06-04 17:40:02'),
(122, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=23', 1, '2025-06-04 17:42:03'),
(123, 2, 'Task siap untuk direview: Golkar Ingatkan Ada Syarat dan Ketentuannya', '../content/view_task.php?id=23', 1, '2025-06-04 17:52:00'),
(124, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=24', 1, '2025-06-04 17:56:18'),
(125, 2, 'Task siap untuk direview: Golkar Harap Hubungan Prabowo-Megawati Semakin Hangat', '../content/view_task.php?id=24', 1, '2025-06-04 18:04:36'),
(126, 9, 'Task telah disetujui: Golkar Harap Hubungan Prabowo-Megawati Semakin Hangat', '../production/view_task.php?id=24', 1, '2025-06-04 18:05:10'),
(127, 9, 'Task telah disetujui: Bukan \'Omon-omon\', Kluivert Siap Tebus Janji Kemenangan Lawan China', '../production/view_task.php?id=19', 1, '2025-06-04 18:05:15'),
(128, 9, 'Task telah disetujui: Golkar Ingatkan Ada Syarat dan Ketentuannya', '../production/view_task.php?id=23', 1, '2025-06-04 18:05:17'),
(129, 9, 'Task telah disetujui: Keangkeran Stadion GBK Bukan Ancaman', '../production/view_task.php?id=20', 1, '2025-06-04 18:05:20'),
(130, 9, 'Task telah disetujui: Rayakan 30 Tahun Kemenangan Le Mans', '../production/view_task.php?id=21', 1, '2025-06-04 18:05:22'),
(131, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=25', 1, '2025-06-04 19:07:04'),
(132, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=19', 1, '2025-06-04 19:24:36'),
(133, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=19', 1, '2025-06-04 19:24:36'),
(134, 2, 'Task siap untuk direview: Saksi Teguh Ungkap Dapat Perintah Khusus Ini dari Budi Arie', '../content/view_task.php?id=25', 1, '2025-06-04 19:46:03'),
(135, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=26', 1, '2025-06-04 19:52:36'),
(136, 2, 'Task siap untuk direview: Hyundai Indonesia Buka Pre-booking untuk Palisade Hybrid', '../content/view_task.php?id=26', 1, '2025-06-04 19:53:05'),
(138, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=20', 1, '2025-06-05 05:13:03'),
(139, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=20', 1, '2025-06-05 05:13:03'),
(140, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=23', 1, '2025-06-05 05:13:15'),
(141, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=23', 1, '2025-06-05 05:13:15'),
(142, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=21', 1, '2025-06-05 05:13:19'),
(143, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=21', 1, '2025-06-05 05:13:19'),
(144, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=24', 1, '2025-06-05 05:13:28'),
(145, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=24', 1, '2025-06-05 05:13:28'),
(146, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=26', 1, '2025-06-05 05:13:32'),
(147, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=26', 1, '2025-06-05 05:13:32'),
(148, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=27', 1, '2025-06-05 05:25:33'),
(149, 2, 'Task Anda ditolak. Alasan: lagi gak mood', '../content/view_task.php?id=27', 1, '2025-06-05 05:26:09'),
(150, 8, 'Anda mendapat tugas baru: Inter Milan dan Simone Inzaghi Resmi Berpisah, Ini Alasan di Baliknya', '../production/view_task.php?id=27', 1, '2025-06-05 05:26:41'),
(151, 2, 'Task siap untuk direview: Inter Milan dan Simone Inzaghi Resmi Berpisah, Ini Alasan di Baliknya', '../content/view_task.php?id=27', 1, '2025-06-05 05:27:34'),
(152, 8, 'Task perlu direvisi: Inter Milan dan Simone Inzaghi Resmi Berpisah, Ini Alasan di Baliknya', '../production/view_task.php?id=27', 1, '2025-06-05 05:28:01'),
(153, 2, 'Task siap untuk direview: Inter Milan dan Simone Inzaghi Resmi Berpisah, Ini Alasan di Baliknya', '../content/view_task.php?id=27', 1, '2025-06-05 05:28:23'),
(154, 8, 'Task telah disetujui: Inter Milan dan Simone Inzaghi Resmi Berpisah, Ini Alasan di Baliknya', '../production/view_task.php?id=27', 1, '2025-06-05 05:28:37'),
(155, 9, 'Task telah disetujui: Saksi Teguh Ungkap Dapat Perintah Khusus Ini dari Budi Arie', '../production/view_task.php?id=25', 1, '2025-06-05 05:28:52'),
(156, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=25', 1, '2025-06-05 05:29:29'),
(157, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=25', 1, '2025-06-05 05:29:29'),
(158, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=27', 1, '2025-06-05 05:29:47'),
(159, 8, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=27', 1, '2025-06-05 05:29:47'),
(160, 2, 'Task siap untuk direview: Jokowi-Gibran Diminta Dukung Kedekatan Prabowo-Megawati', '../content/view_task.php?id=22', 1, '2025-06-05 05:30:10'),
(161, 8, 'Task telah disetujui: Jokowi-Gibran Diminta Dukung Kedekatan Prabowo-Megawati', '../production/view_task.php?id=22', 1, '2025-06-05 05:48:21'),
(162, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=22', 1, '2025-06-05 06:19:32'),
(163, 8, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=22', 1, '2025-06-05 06:19:32'),
(164, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=28', 1, '2025-06-05 06:50:01'),
(165, 2, 'Task siap untuk direview: Soal Kapolri bakal Dicopot, Seskab: Jenderal Listyo Sudah Menghadap ke Prabowo', '../content/view_task.php?id=28', 1, '2025-06-05 08:24:35'),
(166, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=29', 1, '2025-06-08 15:25:58'),
(167, 2, 'Task siap untuk direview: Nyoba', '../content/view_task.php?id=29', 1, '2025-06-08 15:27:21'),
(168, 9, 'Task telah disetujui: Nyoba', '../production/view_task.php?id=29', 1, '2025-06-08 15:29:15'),
(169, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=34', 1, '2025-06-09 04:49:57'),
(170, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=35', 1, '2025-06-09 05:04:49'),
(171, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=36', 1, '2025-06-09 05:09:30'),
(172, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=38', 1, '2025-06-09 05:24:50'),
(173, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=38', 1, '2025-06-09 05:32:10'),
(174, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=36', 1, '2025-06-09 05:33:04'),
(175, 9, 'Task telah disetujui: Soal Kapolri bakal Dicopot, Seskab: Jenderal Listyo Sudah Menghadap ke Prabowo', '../production/view_task.php?id=28', 1, '2025-06-09 05:35:35'),
(176, 13, 'Task distribusi telah selesai: Nyoba 3 hahaha', '../marketing/view_task.php?id=36', 1, '2025-06-09 06:13:11'),
(177, 13, 'Task distribusi telah selesai: hahaha okay okay', '../marketing/view_task.php?id=38', 1, '2025-06-09 06:13:24'),
(178, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=39', 1, '2025-06-09 06:13:57'),
(179, 2, 'Task siap untuk direview: nyoab aki', '../content/view_task.php?id=39', 1, '2025-06-09 06:16:16'),
(180, 9, 'Task perlu direvisi: nyoab aki', '../production/view_task.php?id=39', 1, '2025-06-09 06:17:07'),
(181, 2, 'Task siap untuk direview: nyoab aki', '../content/view_task.php?id=39', 1, '2025-06-09 06:17:17'),
(182, 9, 'Task telah disetujui: nyoab aki', '../production/view_task.php?id=39', 1, '2025-06-09 06:17:32'),
(183, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=35', 1, '2025-06-09 06:18:28'),
(184, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=34', 1, '2025-06-09 06:18:33'),
(185, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=39', 1, '2025-06-09 06:18:41'),
(186, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=39', 1, '2025-06-09 06:18:41'),
(187, 13, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=38', 1, '2025-06-09 06:18:49'),
(188, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=38', 1, '2025-06-09 06:18:49'),
(189, 13, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=36', 1, '2025-06-09 06:19:08'),
(190, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=36', 1, '2025-06-09 06:19:08'),
(191, 2, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=29', 1, '2025-06-09 06:19:21'),
(192, 9, 'Task telah diverifikasi dengan rating 5 ★', 'view_task.php?id=29', 1, '2025-06-09 06:19:21'),
(193, 2, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=28', 1, '2025-06-09 06:19:26'),
(194, 9, 'Task telah diverifikasi dengan rating 4 ★', 'view_task.php?id=28', 1, '2025-06-09 06:19:26'),
(195, 13, 'Task distribusi telah selesai: nyoba ke 2', '../marketing/view_task.php?id=35', 1, '2025-06-09 06:30:54'),
(196, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=40', 1, '2025-06-09 06:31:15'),
(197, 2, 'Task siap untuk direview: sdfzsdg', '../content/view_task.php?id=40', 1, '2025-06-09 06:32:01'),
(198, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=41', 1, '2025-06-09 06:32:57'),
(199, 9, 'Anda mendapat task baru yang perlu dikerjakan', '../production/view_task.php?id=41', 1, '2025-06-09 06:33:24'),
(200, 13, 'Task siap untuk direview: halo gaes', '../content/view_task.php?id=41', 1, '2025-06-09 06:34:33'),
(201, 9, 'Task perlu direvisi: halo gaes', '../production/view_task.php?id=41', 1, '2025-06-09 06:57:02'),
(202, 13, 'Task siap untuk direview: halo gaes', '../content/view_task.php?id=41', 1, '2025-06-09 06:57:25'),
(203, 1, 'Task menunggu verifikasi akhir: halo gaes', '../admin/verify_task.php?id=41', 1, '2025-06-09 06:57:55'),
(204, 9, 'Task telah disetujui: sdfzsdg', '../production/view_task.php?id=40', 1, '2025-06-09 07:04:18'),
(205, 13, 'Task yang Anda buat telah selesai dan diverifikasi oleh Creative Director', 'view_task.php?id=41', 1, '2025-06-09 07:12:33'),
(206, 2, 'Task yang Anda buat telah selesai dan diverifikasi oleh Creative Director', 'view_task.php?id=40', 1, '2025-06-09 07:12:41'),
(207, 2, 'Task Anda memerlukan revisi dari Creative Director', 'view_task.php?id=35', 1, '2025-06-09 07:12:50'),
(208, 1, 'Link distribusi telah direvisi dan menunggu verifikasi: nyoba ke 2', '../director/view_task.php?id=35', 1, '2025-06-09 07:46:15'),
(209, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Nyoba task dari marketing, apakah berhasil?', '../director/view_task.php?id=34', 1, '2025-06-09 07:46:34'),
(210, 13, 'Task yang Anda buat telah selesai dan diverifikasi oleh Creative Director', 'view_task.php?id=35', 1, '2025-06-09 07:46:51'),
(211, 13, 'Task yang Anda buat telah selesai dan diverifikasi oleh Creative Director', 'view_task.php?id=34', 1, '2025-06-09 07:47:09'),
(212, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=42', 1, '2025-06-09 11:47:14'),
(213, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=42', 1, '2025-06-09 11:47:43'),
(214, 1, 'Task distribusi telah diupload dan menunggu verifikasi: KONTEN MARKETING 1', '../director/view_task.php?id=42', 1, '2025-06-09 11:48:11'),
(215, 13, 'Task yang Anda buat telah selesai dan diverifikasi oleh Creative Director', 'view_task.php?id=42', 1, '2025-06-09 11:49:07'),
(216, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=43', 1, '2025-06-09 12:00:30'),
(217, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=44', 1, '2025-06-09 12:03:56'),
(218, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=45', 1, '2025-06-09 14:02:16'),
(219, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=45', 1, '2025-06-09 14:02:47'),
(220, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Nyoba lagi gaes konten berbayar', '../director/view_task.php?id=45', 1, '2025-06-09 14:08:07'),
(221, 13, 'Task yang Anda buat telah selesai dan diverifikasi oleh Creative Director', 'view_task.php?id=45', 1, '2025-06-09 14:08:29'),
(222, 2, 'Task siap untuk direview: ddadf', '../content/view_task.php?id=43', 1, '2025-06-09 14:10:01'),
(223, 2, 'Task siap untuk direview: nyoba lagi konten lainnya', '../content/view_task.php?id=44', 1, '2025-06-09 14:10:20'),
(224, 9, 'Task telah disetujui: nyoba lagi konten lainnya', '../production/view_task.php?id=44', 1, '2025-06-09 14:10:48'),
(225, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=44', 1, '2025-06-09 14:21:17'),
(226, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=44', 1, '2025-06-09 14:21:17'),
(227, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=46', 1, '2025-06-10 01:39:25'),
(228, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=48', 1, '2025-06-10 01:41:47'),
(229, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=46', 1, '2025-06-10 01:42:35'),
(230, 9, 'Anda mendapat task baru yang perlu dikerjakan', '../production/view_task.php?id=48', 1, '2025-06-10 01:42:45'),
(231, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Nyoba Marketing Promo Film', '../director/view_task.php?id=46', 1, '2025-06-10 01:44:13'),
(232, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=49', 1, '2025-06-10 01:44:57'),
(233, 13, 'Task siap untuk direview: Nyoba Marketing Desain Proposal 2', '../content/view_task.php?id=48', 1, '2025-06-10 01:46:08'),
(234, 2, 'Task siap untuk direview: Judul konten biasa hahaha', '../content/view_task.php?id=49', 1, '2025-06-10 01:46:48'),
(235, 1, 'Task menunggu verifikasi akhir: Nyoba Marketing Desain Proposal 2', '../admin/verify_task.php?id=48', 1, '2025-06-10 01:57:59'),
(236, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=46', 1, '2025-06-10 01:58:24'),
(237, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=46', 1, '2025-06-10 01:58:24'),
(238, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=48', 1, '2025-06-10 01:58:30'),
(239, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=48', 1, '2025-06-10 01:58:30'),
(240, 9, 'Task telah disetujui: Judul konten biasa hahaha', '../production/view_task.php?id=49', 1, '2025-06-10 09:11:16'),
(241, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=49', 1, '2025-06-10 13:04:12'),
(242, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=49', 1, '2025-06-10 13:04:12'),
(243, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=50', 1, '2025-06-10 14:56:05'),
(244, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=50', 1, '2025-06-10 14:56:25'),
(245, 1, 'Task distribusi telah diupload dan menunggu verifikasi: nyoba final baru 2', '../director/view_task.php?id=50', 1, '2025-06-10 14:57:01'),
(246, 2, 'Task Anda memerlukan revisi dari Creative Director', 'view_task.php?id=50', 1, '2025-06-10 14:57:37'),
(247, 1, 'Link distribusi telah direvisi dan menunggu verifikasi: nyoba final baru 2', '../director/view_task.php?id=50', 1, '2025-06-10 15:02:51'),
(248, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=51', 1, '2025-06-10 15:17:14'),
(249, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=52', 1, '2025-06-10 15:17:30'),
(250, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=53', 1, '2025-06-10 15:17:48'),
(251, 2, 'Task siap untuk direview: Multiple Task 2', '../content/view_task.php?id=52', 1, '2025-06-10 15:19:44'),
(252, 2, 'Task siap untuk direview: Multiple Task 1', '../content/view_task.php?id=51', 1, '2025-06-10 15:20:26'),
(253, 2, 'Task siap untuk direview: Multiple Task 3', '../content/view_task.php?id=53', 1, '2025-06-10 15:20:45'),
(254, 9, 'Task telah disetujui: Multiple Task 3', '../production/view_task.php?id=53', 1, '2025-06-10 15:34:14'),
(255, 9, 'Task telah disetujui: Multiple Task 2', '../production/view_task.php?id=52', 1, '2025-06-10 15:34:23'),
(256, 9, 'Task telah disetujui: Multiple Task 1', '../production/view_task.php?id=51', 1, '2025-06-10 15:34:39'),
(257, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=53', 1, '2025-06-10 15:34:52'),
(258, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=53', 1, '2025-06-10 15:34:52'),
(259, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=54', 1, '2025-06-10 15:55:35'),
(260, 1, 'Task siap untuk direview: Cover Tukar Pikiran Eps 4', '../content/view_task.php?id=54', 1, '2025-06-10 15:55:59'),
(261, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=55', 1, '2025-06-10 15:57:35'),
(262, 9, 'Anda mendapat task baru yang perlu dikerjakan', '../production/view_task.php?id=55', 1, '2025-06-10 15:58:12'),
(263, 13, 'Task siap untuk direview: produksi 5', '../content/view_task.php?id=55', 1, '2025-06-10 15:58:32'),
(264, 1, 'Task menunggu verifikasi akhir: produksi 5', '../admin/verify_task.php?id=55', 1, '2025-06-10 15:58:53'),
(265, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=56', 1, '2025-06-10 16:07:07'),
(266, 1, 'Task siap untuk direview: Nyoba Produksi 3', '../content/view_task.php?id=56', 1, '2025-06-10 16:07:23'),
(267, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=52', 1, '2025-06-10 16:10:46'),
(268, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=52', 1, '2025-06-10 16:10:46'),
(269, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=51', 1, '2025-06-10 16:10:53'),
(270, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=51', 1, '2025-06-10 16:10:53'),
(271, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=50', 1, '2025-06-10 16:10:59'),
(272, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=50', 1, '2025-06-10 16:10:59'),
(273, 9, 'Task \'Nyoba Produksi 3\' telah disetujui dan diselesaikan', 'view_task.php?id=56', 1, '2025-06-10 16:11:02'),
(274, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=55', 1, '2025-06-10 16:11:13'),
(275, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=55', 1, '2025-06-10 16:11:13'),
(276, 9, 'Task \'Cover Tukar Pikiran Eps 4\' telah disetujui dan diselesaikan', 'view_task.php?id=54', 1, '2025-06-10 16:11:21'),
(277, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=57', 1, '2025-06-11 06:36:19'),
(278, 2, 'Task siap untuk direview: Nyoba ada gak ya 1', '../content/view_task.php?id=57', 1, '2025-06-11 06:36:49'),
(279, 9, 'Task telah disetujui: Nyoba ada gak ya 1', '../production/view_task.php?id=57', 1, '2025-06-11 06:37:12'),
(280, 9, 'Task \'Nyoba ada gak ya 1\' telah disetujui dan diselesaikan', 'view_task.php?id=57', 1, '2025-06-11 12:42:37'),
(281, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=58', 1, '2025-06-11 14:42:26'),
(282, 2, 'Task siap untuk direview: Nyoba point', '../content/view_task.php?id=58', 1, '2025-06-11 14:42:46'),
(283, 9, 'Task telah disetujui: Nyoba point', '../production/view_task.php?id=58', 1, '2025-06-11 14:46:17'),
(284, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=58', 1, '2025-06-11 14:46:37'),
(285, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=58', 1, '2025-06-11 14:46:37'),
(286, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=59', 1, '2025-06-11 15:05:59'),
(287, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=60', 1, '2025-06-11 15:06:14'),
(288, 2, 'Task siap untuk direview: Nyoba point 2', '../content/view_task.php?id=59', 1, '2025-06-11 15:06:32'),
(289, 2, 'Task siap untuk direview: nyoba task 3', '../content/view_task.php?id=60', 1, '2025-06-11 15:06:41'),
(290, 9, 'Task telah disetujui: Nyoba point 2', '../production/view_task.php?id=59', 1, '2025-06-11 15:07:05'),
(291, 9, 'Task telah disetujui: nyoba task 3', '../production/view_task.php?id=60', 1, '2025-06-11 15:07:19'),
(292, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=60', 1, '2025-06-11 15:07:35'),
(293, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=60', 1, '2025-06-11 15:07:35'),
(294, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=59', 1, '2025-06-11 15:07:42'),
(295, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=59', 1, '2025-06-11 15:07:42'),
(296, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=61', 1, '2025-06-11 15:18:37'),
(297, 2, 'Task siap untuk direview: nyoba point 4', '../content/view_task.php?id=61', 1, '2025-06-11 15:18:57'),
(298, 9, 'Task telah disetujui: nyoba point 4', '../production/view_task.php?id=61', 1, '2025-06-11 15:19:14'),
(299, 2, 'Task telah selesai dan diverifikasi dengan rating: 4/5', 'view_task.php?id=61', 1, '2025-06-11 15:19:38'),
(300, 9, 'Task telah selesai dan diverifikasi dengan rating: 4/5', 'view_task.php?id=61', 1, '2025-06-11 15:19:38'),
(301, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=62', 1, '2025-06-11 15:37:58'),
(302, 2, 'Task siap untuk direview: nyoba point 6', '../content/view_task.php?id=62', 1, '2025-06-11 15:38:23'),
(303, 9, 'Task telah disetujui: nyoba point 6', '../production/view_task.php?id=62', 1, '2025-06-11 15:38:47'),
(304, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=62', 1, '2025-06-11 15:39:00'),
(305, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=62', 1, '2025-06-11 15:39:00'),
(306, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=63', 1, '2025-06-12 06:21:04'),
(307, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=63', 1, '2025-06-12 06:21:51'),
(308, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Pop ini judunya hahaha', '../director/view_task.php?id=63', 1, '2025-06-12 06:23:15'),
(309, 2, 'Task Anda memerlukan revisi dari Creative Director', 'view_task.php?id=63', 1, '2025-06-12 06:24:43'),
(310, 1, 'Link distribusi telah direvisi dan menunggu verifikasi: Pop ini judunya hahaha', '../director/view_task.php?id=63', 1, '2025-06-12 06:25:02'),
(311, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=63', 1, '2025-06-12 06:25:09'),
(312, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=63', 1, '2025-06-12 06:25:09'),
(313, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=64', 1, '2025-06-12 06:27:57'),
(314, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=64', 1, '2025-06-12 06:28:07'),
(315, 1, 'Task distribusi telah diupload dan menunggu verifikasi: dsgdsfh', '../director/view_task.php?id=64', 1, '2025-06-12 06:28:26'),
(316, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=64', 1, '2025-06-12 06:28:52'),
(317, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=64', 1, '2025-06-12 06:28:52'),
(318, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=65', 1, '2025-06-12 06:29:53'),
(319, 9, 'Anda mendapat task baru yang perlu dikerjakan', '../production/view_task.php?id=65', 1, '2025-06-12 06:30:19'),
(320, 13, 'Task siap untuk direview: dgsdag', '../content/view_task.php?id=65', 1, '2025-06-12 06:30:33'),
(321, 1, 'Task menunggu verifikasi akhir: dgsdag', '../admin/verify_task.php?id=65', 1, '2025-06-12 06:30:47'),
(322, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=66', 1, '2025-06-12 10:47:08'),
(323, 2, 'Task siap untuk direview: Haha hihi nyoba point', '../content/view_task.php?id=66', 1, '2025-06-12 10:47:26'),
(324, 9, 'Task telah disetujui: Haha hihi nyoba point', '../production/view_task.php?id=66', 1, '2025-06-12 10:59:58'),
(325, 13, 'Task telah selesai dan diverifikasi dengan rating: 4/5', 'view_task.php?id=65', 1, '2025-06-12 11:00:13'),
(326, 9, 'Task telah selesai dan diverifikasi dengan rating: 4/5', 'view_task.php?id=65', 1, '2025-06-12 11:00:13'),
(327, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=66', 1, '2025-06-12 11:00:17'),
(328, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=66', 1, '2025-06-12 11:00:17'),
(329, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=67', 1, '2025-06-12 11:03:09'),
(330, 9, 'Anda mendapat task baru yang perlu dikerjakan', '../production/view_task.php?id=67', 1, '2025-06-12 11:03:27'),
(331, 13, 'Task siap untuk direview: Ilustrasi Suara Dari rimba', '../content/view_task.php?id=67', 1, '2025-06-12 11:07:52'),
(332, 1, 'Task menunggu verifikasi akhir: Ilustrasi Suara Dari rimba', '../admin/verify_task.php?id=67', 1, '2025-06-12 11:08:05'),
(333, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=67', 1, '2025-06-12 11:08:10'),
(334, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=67', 1, '2025-06-12 11:08:10'),
(335, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=68', 1, '2025-06-12 11:10:18'),
(336, 2, 'Task siap untuk direview: dsfgdfgfd', '../content/view_task.php?id=68', 1, '2025-06-12 11:10:28'),
(337, 9, 'Task telah disetujui: dsfgdfgfd', '../production/view_task.php?id=68', 1, '2025-06-12 11:10:37'),
(338, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=69', 1, '2025-06-12 11:17:08'),
(339, 2, 'Task siap untuk direview: asdasgdsa point', '../content/view_task.php?id=69', 1, '2025-06-12 11:17:21'),
(340, 9, 'Task telah disetujui: asdasgdsa point', '../production/view_task.php?id=69', 1, '2025-06-12 11:17:35'),
(341, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=68', 1, '2025-06-12 11:17:51'),
(342, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=68', 1, '2025-06-12 11:17:51'),
(343, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=69', 1, '2025-06-12 11:17:54'),
(344, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=69', 1, '2025-06-12 11:17:54'),
(345, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=70', 1, '2025-06-12 12:52:47'),
(346, 2, 'Task siap untuk direview: asgasdas point 9', '../content/view_task.php?id=70', 1, '2025-06-12 12:52:58'),
(347, 9, 'Task telah disetujui: asgasdas point 9', '../production/view_task.php?id=70', 1, '2025-06-12 12:53:21'),
(348, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=70', 1, '2025-06-12 12:53:41'),
(349, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=70', 1, '2025-06-12 12:53:41'),
(350, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=71', 1, '2025-06-12 13:10:49'),
(351, 2, 'Task siap untuk direview: NYOBA POINT 1', '../content/view_task.php?id=71', 1, '2025-06-12 13:11:03'),
(352, 9, 'Task telah disetujui: NYOBA POINT 1', '../production/view_task.php?id=71', 1, '2025-06-12 13:11:13'),
(353, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=71', 1, '2025-06-12 13:11:47'),
(354, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=71', 1, '2025-06-12 13:11:47'),
(355, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=72', 1, '2025-06-12 13:14:52'),
(356, 2, 'Task siap untuk direview: NYOBA POINT 2', '../content/view_task.php?id=72', 1, '2025-06-12 13:15:01'),
(357, 9, 'Task telah disetujui: NYOBA POINT 2', '../production/view_task.php?id=72', 1, '2025-06-12 13:15:13'),
(358, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=72', 1, '2025-06-12 13:15:23'),
(359, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=72', 1, '2025-06-12 13:15:23'),
(360, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=73', 1, '2025-06-12 13:15:49'),
(361, 2, 'Task siap untuk direview: NYOBA POINT 3', '../content/view_task.php?id=73', 1, '2025-06-12 13:15:58'),
(362, 9, 'Task telah disetujui: NYOBA POINT 3', '../production/view_task.php?id=73', 1, '2025-06-12 13:16:07'),
(363, 2, 'Task telah selesai dan diverifikasi: NYOBA POINT 3', '../marketing/view_task.php?id=73', 1, '2025-06-12 13:17:14'),
(364, 9, 'Task telah selesai dan diverifikasi: NYOBA POINT 3', '../production/view_task.php?id=73', 1, '2025-06-12 13:17:14'),
(365, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=74', 1, '2025-06-12 13:17:57'),
(366, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=75', 1, '2025-06-12 13:19:35'),
(367, 2, 'Task siap untuk direview: NYOBA POINT 4', '../content/view_task.php?id=75', 1, '2025-06-12 13:19:53'),
(368, 2, 'Task siap untuk direview: NYOBA POINT 4', '../content/view_task.php?id=74', 1, '2025-06-12 13:20:01'),
(369, 9, 'Task telah disetujui: NYOBA POINT 4', '../production/view_task.php?id=75', 1, '2025-06-12 13:20:12'),
(370, 9, 'Task telah disetujui: NYOBA POINT 4', '../production/view_task.php?id=74', 1, '2025-06-12 13:20:23'),
(371, 2, 'Task telah selesai dan diverifikasi: NYOBA POINT 4', '../marketing/view_task.php?id=74', 1, '2025-06-12 13:20:29'),
(372, 9, 'Task telah selesai dan diverifikasi: NYOBA POINT 4', '../production/view_task.php?id=74', 1, '2025-06-12 13:20:29'),
(373, 2, 'Task telah selesai dan diverifikasi: NYOBA POINT 4', '../marketing/view_task.php?id=75', 1, '2025-06-12 13:20:32'),
(374, 9, 'Task telah selesai dan diverifikasi: NYOBA POINT 4', '../production/view_task.php?id=75', 1, '2025-06-12 13:20:32'),
(375, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=76', 1, '2025-06-12 13:21:44'),
(376, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=76', 1, '2025-06-12 13:22:07'),
(377, 1, 'Task distribusi telah diupload dan menunggu verifikasi: NYOBA POINT 5', '../director/view_task.php?id=76', 1, '2025-06-12 13:23:02'),
(378, 13, 'Task telah selesai dan diverifikasi: NYOBA POINT 5', '../marketing/view_task.php?id=76', 1, '2025-06-12 13:23:23'),
(379, 2, 'Task telah selesai dan diverifikasi: NYOBA POINT 5', '../production/view_task.php?id=76', 1, '2025-06-12 13:23:23'),
(380, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=77', 1, '2025-06-12 13:24:50'),
(381, 2, 'Task siap untuk direview: NYOBA POINT 6', '../content/view_task.php?id=77', 1, '2025-06-12 13:25:01'),
(382, 9, 'Task telah disetujui: NYOBA POINT 6', '../production/view_task.php?id=77', 1, '2025-06-12 13:25:13'),
(383, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=77', 1, '2025-06-12 13:25:26'),
(384, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=77', 1, '2025-06-12 13:25:26'),
(385, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=78', 1, '2025-06-12 13:37:37'),
(386, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=78', 1, '2025-06-12 13:56:55'),
(387, 1, 'Task distribusi telah diupload dan menunggu verifikasi: sdagasdgd', '../director/view_task.php?id=78', 1, '2025-06-12 13:57:39'),
(388, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=78', 1, '2025-06-12 14:04:14'),
(389, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=78', 1, '2025-06-12 14:04:14'),
(390, 2, 'Anda mendapatkan 4 poin tambahan: Brief ilustrasi idul adha', 'points.php', 1, '2025-06-12 15:33:10'),
(391, 2, 'Anda mendapatkan 0.5 poin tambahan: buletin point', 'points.php', 1, '2025-06-12 15:33:57'),
(392, 9, 'Anda mendapatkan 1.5 poin tambahan: bonus', 'points.php', 1, '2025-06-12 15:34:05'),
(393, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=79', 1, '2025-06-12 15:43:08'),
(394, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=79', 1, '2025-06-12 15:43:17'),
(395, 1, 'Task distribusi telah diupload dan menunggu verifikasi: sdagdagfa', '../director/view_task.php?id=79', 1, '2025-06-12 15:51:48'),
(396, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=80', 1, '2025-06-12 15:52:20'),
(397, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=79', 1, '2025-06-12 15:52:40'),
(398, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=79', 1, '2025-06-12 15:52:40'),
(399, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=80', 1, '2025-06-12 15:52:57'),
(400, 1, 'Task distribusi telah diupload dan menunggu verifikasi: dsgs', '../director/view_task.php?id=80', 1, '2025-06-12 15:53:10'),
(401, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=80', 1, '2025-06-12 15:53:18'),
(402, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=80', 1, '2025-06-12 15:53:18'),
(403, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=81', 1, '2025-06-12 15:54:12'),
(404, 2, 'Task siap untuk direview: rdfs', '../content/view_task.php?id=81', 1, '2025-06-12 15:54:22'),
(405, 9, 'Task telah disetujui: rdfs', '../production/view_task.php?id=81', 1, '2025-06-12 15:54:41'),
(406, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=81', 1, '2025-06-12 15:54:51'),
(407, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=81', 1, '2025-06-12 15:54:51'),
(408, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=82', 1, '2025-06-12 16:05:37'),
(409, 2, 'Task siap untuk direview: rag', '../content/view_task.php?id=82', 1, '2025-06-12 16:05:59'),
(410, 9, 'Task telah disetujui: rag', '../production/view_task.php?id=82', 1, '2025-06-12 16:06:35'),
(411, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=82', 1, '2025-06-12 16:06:43'),
(412, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=82', 1, '2025-06-12 16:06:43'),
(413, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=83', 1, '2025-06-12 16:07:16'),
(414, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=83', 1, '2025-06-12 16:07:23'),
(415, 1, 'Task distribusi telah diupload dan menunggu verifikasi: daga', '../director/view_task.php?id=83', 1, '2025-06-12 16:07:47'),
(416, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=83', 1, '2025-06-12 16:07:55'),
(417, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=83', 1, '2025-06-12 16:07:55'),
(418, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=84', 1, '2025-06-12 16:08:42'),
(419, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=84', 1, '2025-06-12 16:08:52'),
(420, 1, 'Task distribusi telah diupload dan menunggu verifikasi: dsfsdg', '../director/view_task.php?id=84', 1, '2025-06-12 16:09:06'),
(421, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=84', 1, '2025-06-12 16:09:15'),
(422, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=84', 1, '2025-06-12 16:09:15'),
(423, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=85', 1, '2025-06-12 16:10:18'),
(424, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=85', 1, '2025-06-12 16:10:25'),
(425, 1, 'Task distribusi telah diupload dan menunggu verifikasi: xzzdbah', '../director/view_task.php?id=85', 1, '2025-06-12 16:10:45'),
(426, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=85', 1, '2025-06-12 16:10:58');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(427, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=85', 1, '2025-06-12 16:10:58'),
(428, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=86', 1, '2025-06-12 16:11:22'),
(429, 2, 'Task siap untuk direview: dsaavd', '../content/view_task.php?id=86', 1, '2025-06-12 16:11:35'),
(430, 9, 'Task telah disetujui: dsaavd', '../production/view_task.php?id=86', 1, '2025-06-12 16:11:52'),
(431, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=86', 1, '2025-06-12 16:12:08'),
(432, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=86', 1, '2025-06-12 16:12:08'),
(433, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=87', 1, '2025-06-15 04:32:45'),
(434, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=87', 1, '2025-06-15 04:33:11'),
(435, 1, 'Task distribusi telah diupload dan menunggu verifikasi: sfsadf', '../director/view_task.php?id=87', 1, '2025-06-15 04:34:58'),
(436, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=87', 1, '2025-06-15 04:35:13'),
(437, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=87', 1, '2025-06-15 04:35:13'),
(438, 9, 'Task telah disetujui: ddadf', '../production/view_task.php?id=43', 1, '2025-06-15 08:52:25'),
(439, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=43', 1, '2025-06-15 08:52:48'),
(440, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=43', 1, '2025-06-15 08:52:48'),
(441, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=88', 1, '2025-06-15 08:53:34'),
(442, 2, 'Task siap untuk direview: sadgdasg', '../content/view_task.php?id=88', 1, '2025-06-16 00:31:31'),
(443, 9, 'Task telah disetujui: sadgdasg', '../production/view_task.php?id=88', 1, '2025-06-16 00:31:52'),
(444, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=88', 1, '2025-06-16 00:32:04'),
(445, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=88', 1, '2025-06-16 00:32:04'),
(446, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=89', 1, '2025-06-16 00:32:54'),
(447, 2, 'Task siap untuk direview: sdgasg', '../content/view_task.php?id=89', 1, '2025-06-16 00:33:04'),
(448, 9, 'Task telah disetujui: sdgasg', '../production/view_task.php?id=89', 1, '2025-06-16 00:33:22'),
(449, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=89', 1, '2025-06-16 00:33:36'),
(450, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=89', 1, '2025-06-16 00:33:36'),
(451, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=90', 1, '2025-06-16 05:11:14'),
(452, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=90', 1, '2025-06-16 05:11:53'),
(453, 5, 'Task siap untuk direview: dsagdasg', '../content/view_task.php?id=90', 1, '2025-06-16 05:12:04'),
(454, 7, 'Task telah disetujui: dsagdasg', '../production/view_task.php?id=90', 1, '2025-06-16 05:12:22'),
(455, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=90', 1, '2025-06-16 05:12:29'),
(456, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=90', 1, '2025-06-16 05:12:29'),
(457, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=91', 1, '2025-06-16 05:13:06'),
(458, 5, 'Task siap untuk direview: safasdg', '../content/view_task.php?id=91', 1, '2025-06-16 05:13:15'),
(459, 7, 'Task telah disetujui: safasdg', '../production/view_task.php?id=91', 1, '2025-06-16 05:13:34'),
(460, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=91', 1, '2025-06-16 05:13:46'),
(461, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=91', 1, '2025-06-16 05:13:46'),
(462, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=92', 1, '2025-06-16 05:39:08'),
(463, 5, 'Task siap untuk direview: asdagf', '../content/view_task.php?id=92', 1, '2025-06-16 05:39:17'),
(464, 7, 'Task telah disetujui: asdagf', '../production/view_task.php?id=92', 1, '2025-06-16 05:39:39'),
(465, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=92', 1, '2025-06-16 05:39:46'),
(466, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=92', 1, '2025-06-16 05:39:46'),
(467, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=93', 1, '2025-06-16 05:40:27'),
(468, 5, 'Task siap untuk direview: asdgads', '../content/view_task.php?id=93', 1, '2025-06-16 05:40:38'),
(469, 7, 'Task telah disetujui: asdgads', '../production/view_task.php?id=93', 1, '2025-06-16 05:40:53'),
(470, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=93', 1, '2025-06-16 05:41:05'),
(471, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=93', 1, '2025-06-16 05:41:05'),
(472, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=94', 1, '2025-06-16 05:49:56'),
(473, 5, 'Task siap untuk direview: adsfasg', '../content/view_task.php?id=94', 1, '2025-06-16 05:50:05'),
(474, 7, 'Task telah disetujui: adsfasg', '../production/view_task.php?id=94', 1, '2025-06-16 05:50:23'),
(475, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=94', 1, '2025-06-16 05:50:32'),
(476, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=94', 1, '2025-06-16 05:50:32'),
(477, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=95', 1, '2025-06-16 05:53:39'),
(478, 5, 'Task siap untuk direview: asdgasgas', '../content/view_task.php?id=95', 1, '2025-06-16 05:53:47'),
(479, 7, 'Task telah disetujui: asdgasgas', '../production/view_task.php?id=95', 1, '2025-06-16 05:54:01'),
(480, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=95', 1, '2025-06-16 05:54:11'),
(481, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=95', 1, '2025-06-16 05:54:11'),
(482, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=96', 1, '2025-06-16 05:54:55'),
(483, 5, 'Task siap untuk direview: adsgszgaa', '../content/view_task.php?id=96', 1, '2025-06-16 05:55:02'),
(484, 7, 'Task telah disetujui: adsgszgaa', '../production/view_task.php?id=96', 1, '2025-06-16 05:55:21'),
(485, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=96', 1, '2025-06-16 05:55:27'),
(486, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=96', 1, '2025-06-16 05:55:27'),
(487, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=97', 1, '2025-06-16 05:56:39'),
(488, 5, 'Task siap untuk direview: dsaga', '../content/view_task.php?id=97', 1, '2025-06-16 05:56:47'),
(489, 7, 'Task telah disetujui: dsaga', '../production/view_task.php?id=97', 1, '2025-06-16 05:57:00'),
(490, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=97', 1, '2025-06-16 05:57:12'),
(491, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=97', 1, '2025-06-16 05:57:12'),
(492, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=98', 1, '2025-06-16 06:06:20'),
(493, 5, 'Task siap untuk direview: asdgasg', '../content/view_task.php?id=98', 1, '2025-06-16 06:06:27'),
(494, 7, 'Task telah disetujui: asdgasg', '../production/view_task.php?id=98', 1, '2025-06-16 06:06:46'),
(495, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=98', 1, '2025-06-16 06:06:53'),
(496, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=98', 1, '2025-06-16 06:06:53'),
(497, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=99', 1, '2025-06-16 06:08:07'),
(498, 5, 'Task siap untuk direview: asdgas', '../content/view_task.php?id=99', 1, '2025-06-16 06:08:15'),
(499, 7, 'Task telah disetujui: asdgas', '../production/view_task.php?id=99', 1, '2025-06-16 06:08:30'),
(500, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=99', 1, '2025-06-16 06:08:35'),
(501, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=99', 1, '2025-06-16 06:08:35'),
(502, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=100', 1, '2025-06-16 06:09:05'),
(503, 5, 'Task siap untuk direview: sfdsg', '../content/view_task.php?id=100', 1, '2025-06-16 06:09:14'),
(504, 7, 'Task telah disetujui: sfdsg', '../production/view_task.php?id=100', 1, '2025-06-16 06:09:29'),
(505, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=100', 1, '2025-06-16 06:09:37'),
(506, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=100', 1, '2025-06-16 06:09:37'),
(507, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=101', 1, '2025-06-16 06:11:42'),
(508, 5, 'Task siap untuk direview: agads', '../content/view_task.php?id=101', 1, '2025-06-16 06:11:54'),
(509, 7, 'Task telah disetujui: agads', '../production/view_task.php?id=101', 1, '2025-06-16 06:12:09'),
(510, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=101', 1, '2025-06-16 06:12:15'),
(511, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=101', 1, '2025-06-16 06:12:15'),
(512, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=102', 1, '2025-06-16 06:14:44'),
(513, 5, 'Task siap untuk direview: dsfbdf', '../content/view_task.php?id=102', 1, '2025-06-16 06:14:55'),
(514, 7, 'Task telah disetujui: dsfbdf', '../production/view_task.php?id=102', 1, '2025-06-16 06:15:10'),
(515, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=102', 1, '2025-06-16 06:15:16'),
(516, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=102', 1, '2025-06-16 06:15:16'),
(517, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=103', 1, '2025-06-16 06:16:22'),
(518, 5, 'Task siap untuk direview: asDGDS', '../content/view_task.php?id=103', 1, '2025-06-16 06:16:29'),
(519, 7, 'Task telah disetujui: asDGDS', '../production/view_task.php?id=103', 1, '2025-06-16 06:16:42'),
(520, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=103', 1, '2025-06-16 06:16:51'),
(521, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=103', 1, '2025-06-16 06:16:51'),
(522, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=104', 1, '2025-06-16 06:20:09'),
(523, 5, 'Task siap untuk direview: sdaf', '../content/view_task.php?id=104', 1, '2025-06-16 06:20:16'),
(524, 7, 'Task telah disetujui: sdaf', '../production/view_task.php?id=104', 1, '2025-06-16 06:20:31'),
(525, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=104', 1, '2025-06-16 06:20:38'),
(526, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=104', 1, '2025-06-16 06:20:38'),
(527, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=105', 1, '2025-06-16 07:02:37'),
(528, 5, 'Task siap untuk direview: zdga', '../content/view_task.php?id=105', 1, '2025-06-16 07:02:44'),
(529, 7, 'Task telah disetujui: zdga', '../production/view_task.php?id=105', 1, '2025-06-16 07:03:03'),
(530, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=105', 1, '2025-06-16 07:03:09'),
(531, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=105', 1, '2025-06-16 07:03:09'),
(532, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=106', 1, '2025-06-16 07:04:28'),
(533, 5, 'Task siap untuk direview: sdag', '../content/view_task.php?id=106', 1, '2025-06-16 07:04:36'),
(534, 7, 'Task telah disetujui: sdag', '../production/view_task.php?id=106', 1, '2025-06-16 07:04:52'),
(535, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=106', 1, '2025-06-16 07:04:59'),
(536, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=106', 1, '2025-06-16 07:04:59'),
(537, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=107', 1, '2025-06-16 07:07:42'),
(538, 5, 'Task siap untuk direview: sdgsa', '../content/view_task.php?id=107', 1, '2025-06-16 07:07:50'),
(539, 7, 'Task telah disetujui: sdgsa', '../production/view_task.php?id=107', 1, '2025-06-16 07:08:04'),
(540, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=107', 1, '2025-06-16 07:08:09'),
(541, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=107', 1, '2025-06-16 07:08:09'),
(542, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=108', 1, '2025-06-16 07:08:27'),
(543, 5, 'Task siap untuk direview: dsfasdf', '../content/view_task.php?id=108', 1, '2025-06-16 07:08:35'),
(544, 7, 'Task telah disetujui: dsfasdf', '../production/view_task.php?id=108', 1, '2025-06-16 07:08:50'),
(545, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=108', 1, '2025-06-16 07:08:59'),
(546, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=108', 1, '2025-06-16 07:08:59'),
(547, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=109', 1, '2025-06-16 07:09:37'),
(548, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=109', 1, '2025-06-16 07:09:44'),
(549, 1, 'Task distribusi telah diupload dan menunggu verifikasi: sdgsadg', '../director/view_task.php?id=109', 1, '2025-06-16 07:09:57'),
(550, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=109', 1, '2025-06-16 07:10:06'),
(551, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=109', 1, '2025-06-16 07:10:06'),
(552, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=110', 1, '2025-06-16 07:18:13'),
(553, 5, 'Task siap untuk direview: xzvxzv', '../content/view_task.php?id=110', 1, '2025-06-16 07:18:23'),
(554, 7, 'Task telah disetujui: xzvxzv', '../production/view_task.php?id=110', 1, '2025-06-16 07:18:45'),
(555, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=110', 1, '2025-06-16 07:18:53'),
(556, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=110', 1, '2025-06-16 07:18:53'),
(557, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=111', 1, '2025-06-16 07:26:32'),
(558, 5, 'Task siap untuk direview: sadg', '../content/view_task.php?id=111', 1, '2025-06-16 07:26:49'),
(559, 7, 'Task telah disetujui: sadg', '../production/view_task.php?id=111', 1, '2025-06-16 07:27:03'),
(560, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=111', 1, '2025-06-16 07:27:09'),
(561, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=111', 1, '2025-06-16 07:27:09'),
(562, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=112', 1, '2025-06-16 07:27:42'),
(563, 5, 'Task siap untuk direview: sadfgadsg', '../content/view_task.php?id=112', 1, '2025-06-16 07:27:49'),
(564, 7, 'Task telah disetujui: sadfgadsg', '../production/view_task.php?id=112', 1, '2025-06-16 07:28:03'),
(565, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=112', 1, '2025-06-16 07:28:09'),
(566, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=112', 1, '2025-06-16 07:28:09'),
(567, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=113', 1, '2025-06-16 07:28:31'),
(568, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=113', 1, '2025-06-16 07:28:45'),
(569, 1, 'Task distribusi telah diupload dan menunggu verifikasi: zgzxc', '../director/view_task.php?id=113', 1, '2025-06-16 07:28:58'),
(570, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=113', 1, '2025-06-16 07:29:05'),
(571, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=113', 1, '2025-06-16 07:29:05'),
(572, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=114', 1, '2025-06-16 07:32:26'),
(573, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=114', 1, '2025-06-16 07:32:33'),
(574, 1, 'Task distribusi telah diupload dan menunggu verifikasi: asfsag', '../director/view_task.php?id=114', 1, '2025-06-16 07:32:48'),
(575, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=114', 1, '2025-06-16 07:32:55'),
(576, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=114', 1, '2025-06-16 07:32:55'),
(577, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=115', 1, '2025-06-16 07:37:19'),
(578, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=115', 1, '2025-06-16 07:37:30'),
(579, 1, 'Task distribusi telah diupload dan menunggu verifikasi: egaadg', '../director/view_task.php?id=115', 1, '2025-06-16 07:37:43'),
(580, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=115', 1, '2025-06-16 07:37:50'),
(581, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=115', 1, '2025-06-16 07:37:50'),
(582, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=116', 1, '2025-06-16 07:39:05'),
(583, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=116', 1, '2025-06-16 07:39:12'),
(584, 1, 'Task distribusi telah diupload dan menunggu verifikasi: sdafdasf', '../director/view_task.php?id=116', 1, '2025-06-16 07:39:28'),
(585, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=116', 1, '2025-06-16 07:39:34'),
(586, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=116', 1, '2025-06-16 07:39:34'),
(587, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=117', 1, '2025-06-16 07:39:53'),
(588, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=117', 1, '2025-06-16 07:40:00'),
(589, 1, 'Task distribusi telah diupload dan menunggu verifikasi: fsdfgds', '../director/view_task.php?id=117', 1, '2025-06-16 07:40:19'),
(590, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=117', 1, '2025-06-16 07:40:26'),
(591, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=117', 1, '2025-06-16 07:40:26'),
(592, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=118', 1, '2025-06-16 07:40:50'),
(593, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=118', 1, '2025-06-16 07:40:58'),
(594, 1, 'Task distribusi telah diupload dan menunggu verifikasi: asdfasd', '../director/view_task.php?id=118', 1, '2025-06-16 07:41:12'),
(595, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=118', 1, '2025-06-16 07:41:23'),
(596, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=118', 1, '2025-06-16 07:41:23'),
(597, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=119', 1, '2025-06-16 07:45:54'),
(598, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=119', 1, '2025-06-16 07:46:03'),
(599, 1, 'Task distribusi telah diupload dan menunggu verifikasi: dsfhsd', '../director/view_task.php?id=119', 1, '2025-06-16 07:46:16'),
(600, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=119', 1, '2025-06-16 07:46:30'),
(601, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=119', 1, '2025-06-16 07:46:30'),
(602, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=120', 1, '2025-06-16 07:48:10'),
(603, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=120', 1, '2025-06-16 07:48:17'),
(604, 1, 'Task distribusi telah diupload dan menunggu verifikasi: safadsg', '../director/view_task.php?id=120', 1, '2025-06-16 07:48:39'),
(605, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=120', 1, '2025-06-16 07:48:47'),
(606, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=120', 1, '2025-06-16 07:48:47'),
(607, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=121', 1, '2025-06-16 07:49:00'),
(608, 5, 'Task siap untuk direview: dfgdfh', '../content/view_task.php?id=121', 1, '2025-06-16 07:49:08'),
(609, 7, 'Task telah disetujui: dfgdfh', '../production/view_task.php?id=121', 1, '2025-06-16 07:49:32'),
(610, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=121', 1, '2025-06-16 07:49:47'),
(611, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=121', 1, '2025-06-16 07:49:47'),
(612, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=122', 1, '2025-06-16 07:50:18'),
(613, 5, 'Task siap untuk direview: sdgsda', '../content/view_task.php?id=122', 1, '2025-06-16 07:50:27'),
(614, 7, 'Task telah disetujui: sdgsda', '../production/view_task.php?id=122', 1, '2025-06-16 07:50:43'),
(615, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=122', 1, '2025-06-16 07:50:49'),
(616, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=122', 1, '2025-06-16 07:50:49'),
(617, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=123', 1, '2025-06-16 07:51:41'),
(618, 5, 'Task siap untuk direview: sdgagfhasdg', '../content/view_task.php?id=123', 1, '2025-06-16 07:51:51'),
(619, 7, 'Task telah disetujui: sdgagfhasdg', '../production/view_task.php?id=123', 1, '2025-06-16 07:52:05'),
(620, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=123', 1, '2025-06-16 07:52:13'),
(621, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=123', 1, '2025-06-16 07:52:13'),
(622, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=124', 1, '2025-06-16 07:52:36'),
(623, 5, 'Task siap untuk direview: sdfgafg', '../content/view_task.php?id=124', 1, '2025-06-16 07:52:42'),
(624, 7, 'Task telah disetujui: sdfgafg', '../production/view_task.php?id=124', 1, '2025-06-16 07:52:57'),
(625, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=124', 1, '2025-06-16 07:53:04'),
(626, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=124', 1, '2025-06-16 07:53:04'),
(627, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=125', 1, '2025-06-16 07:53:22'),
(628, 5, 'Task siap untuk direview: dSGzds', '../content/view_task.php?id=125', 1, '2025-06-16 07:53:33'),
(629, 7, 'Task telah disetujui: dSGzds', '../production/view_task.php?id=125', 1, '2025-06-16 07:53:55'),
(630, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=125', 1, '2025-06-16 07:54:01'),
(631, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=125', 1, '2025-06-16 07:54:01'),
(632, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=126', 1, '2025-06-16 07:54:32'),
(633, 5, 'Task siap untuk direview: ethert', '../content/view_task.php?id=126', 1, '2025-06-16 07:54:39'),
(634, 7, 'Task telah disetujui: ethert', '../production/view_task.php?id=126', 1, '2025-06-16 07:55:07'),
(635, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=126', 1, '2025-06-16 07:55:13'),
(636, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=126', 1, '2025-06-16 07:55:13'),
(637, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=127', 1, '2025-06-16 07:57:42'),
(638, 5, 'Task siap untuk direview: dfgdfh', '../content/view_task.php?id=127', 1, '2025-06-16 07:57:52'),
(639, 7, 'Task telah disetujui: dfgdfh', '../production/view_task.php?id=127', 1, '2025-06-16 07:58:07'),
(640, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=127', 1, '2025-06-16 07:58:13'),
(641, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=127', 1, '2025-06-16 07:58:13'),
(642, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=128', 1, '2025-06-16 07:58:29'),
(643, 5, 'Task siap untuk direview: fdghdfgh', '../content/view_task.php?id=128', 1, '2025-06-16 07:58:37'),
(644, 7, 'Task telah disetujui: fdghdfgh', '../production/view_task.php?id=128', 1, '2025-06-16 07:58:46'),
(645, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=128', 1, '2025-06-16 07:58:52'),
(646, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=128', 1, '2025-06-16 07:58:52'),
(647, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=129', 1, '2025-06-16 09:13:45'),
(648, 3, 'Task siap untuk direview: dsagasdg', '../content/view_task.php?id=129', 0, '2025-06-16 09:13:54'),
(649, 7, 'Task telah disetujui: dsagasdg', '../production/view_task.php?id=129', 1, '2025-06-16 09:14:12'),
(650, 3, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=129', 0, '2025-06-16 09:14:19'),
(651, 7, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=129', 1, '2025-06-16 09:14:19'),
(652, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=130', 1, '2025-06-17 05:30:51'),
(653, 5, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=130', 1, '2025-06-17 05:31:48'),
(654, 1, 'Task distribusi telah diupload dan menunggu verifikasi: adsgs', '../director/view_task.php?id=130', 1, '2025-06-17 05:32:37'),
(655, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=130', 1, '2025-06-17 05:32:44'),
(656, 5, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=130', 1, '2025-06-17 05:32:44'),
(657, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=137', 1, '2025-06-18 10:21:10'),
(658, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=138', 1, '2025-06-18 10:26:53'),
(659, 2, 'Task siap untuk direview: dasgasg', '../content/view_task.php?id=134', 1, '2025-06-18 10:44:15'),
(660, 2, 'Task siap untuk direview: safSG', '../content/view_task.php?id=137', 1, '2025-06-18 11:54:19'),
(661, 2, 'Task siap untuk direview: asgasd', '../content/view_task.php?id=138', 1, '2025-06-18 11:54:28'),
(662, 9, 'Task telah disetujui: dasgasg', '../production/view_task.php?id=134', 1, '2025-06-18 11:54:43'),
(663, 9, 'Task telah disetujui: safSG', '../production/view_task.php?id=137', 1, '2025-06-18 11:54:55'),
(664, 9, 'Task telah disetujui: asgasd', '../production/view_task.php?id=138', 1, '2025-06-18 11:55:08'),
(665, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=134', 1, '2025-06-18 11:55:16'),
(666, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=134', 1, '2025-06-18 11:55:16'),
(667, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=137', 1, '2025-06-18 11:55:19'),
(668, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=137', 1, '2025-06-18 11:55:19'),
(669, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=138', 1, '2025-06-18 11:55:22'),
(670, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=138', 1, '2025-06-18 11:55:22'),
(671, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=139', 1, '2025-06-18 12:22:24'),
(672, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=139', 1, '2025-06-18 12:22:51'),
(673, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Postingan Marketing', '../director/view_task.php?id=139', 1, '2025-06-18 12:23:25'),
(674, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=139', 1, '2025-06-18 12:23:59'),
(675, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=139', 1, '2025-06-18 12:23:59'),
(676, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=140', 1, '2025-06-18 12:25:02'),
(677, 9, 'Anda mendapat task baru yang perlu dikerjakan', '../production/view_task.php?id=140', 1, '2025-06-18 12:25:24'),
(678, 13, 'Task Anda ditolak. Alasan: lagi sibuk', '../content/view_task.php?id=140', 1, '2025-06-18 12:25:39'),
(679, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=141', 1, '2025-06-18 12:26:09'),
(680, 9, 'Anda mendapat task baru yang perlu dikerjakan', '../production/view_task.php?id=141', 1, '2025-06-18 12:26:14'),
(681, 13, 'Task siap untuk direview: r3g34g', '../content/view_task.php?id=141', 1, '2025-06-18 12:26:31'),
(682, 9, 'Task perlu direvisi: r3g34g', '../production/view_task.php?id=141', 1, '2025-06-18 12:26:48'),
(683, 13, 'Task siap untuk direview: r3g34g', '../content/view_task.php?id=141', 1, '2025-06-18 12:27:11'),
(684, 1, 'Task menunggu verifikasi akhir: r3g34g', '../admin/verify_task.php?id=141', 1, '2025-06-18 12:27:19'),
(685, 13, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=141', 1, '2025-06-18 12:27:29'),
(686, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=141', 1, '2025-06-18 12:27:29'),
(687, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=142', 1, '2025-06-19 05:28:20'),
(688, 2, 'Task siap untuk direview: Nyoba Notifikasi Desktop 1', '../content/view_task.php?id=142', 1, '2025-06-19 05:29:39'),
(689, 9, 'Task telah disetujui: Nyoba Notifikasi Desktop 1', '../production/view_task.php?id=142', 1, '2025-06-19 05:32:50'),
(690, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=142', 1, '2025-06-19 05:33:09'),
(691, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=142', 1, '2025-06-19 05:33:09'),
(692, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=143', 1, '2025-06-19 05:36:13'),
(693, 2, 'Task siap untuk direview: sgsdag', '../content/view_task.php?id=143', 1, '2025-06-19 05:39:00'),
(694, 9, 'Task telah disetujui: sgsdag', '../production/view_task.php?id=143', 1, '2025-06-19 05:39:55'),
(695, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=144', 1, '2025-06-19 05:53:47'),
(696, 2, 'Task siap untuk direview: sgsadgd', '../content/view_task.php?id=144', 1, '2025-06-19 05:58:03'),
(697, 9, 'Task telah disetujui: sgsadgd', '../production/view_task.php?id=144', 1, '2025-06-19 06:06:42'),
(698, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=144', 1, '2025-06-19 06:07:19'),
(699, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=144', 1, '2025-06-19 06:07:19'),
(700, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=145', 1, '2025-06-19 06:11:01'),
(701, 2, 'Task siap untuk direview: dsfag', '../content/view_task.php?id=145', 1, '2025-06-19 06:12:45'),
(702, 9, 'Task telah disetujui: dsfag', '../production/view_task.php?id=145', 1, '2025-06-19 06:14:14'),
(703, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=143', 1, '2025-06-19 06:14:31'),
(704, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=143', 1, '2025-06-19 06:14:31'),
(705, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=146', 1, '2025-06-19 06:15:01'),
(706, 2, 'Task siap untuk direview: sadgadsg', '../content/view_task.php?id=146', 1, '2025-06-19 06:15:10'),
(707, 9, 'Task telah disetujui: sadgadsg', '../production/view_task.php?id=146', 1, '2025-06-19 06:15:46'),
(708, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=146', 1, '2025-06-19 06:16:04'),
(709, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=146', 1, '2025-06-19 06:16:04'),
(710, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=147', 1, '2025-06-19 06:16:30'),
(711, 2, 'Task siap untuk direview: asfsaG', '../content/view_task.php?id=147', 1, '2025-06-19 06:16:59'),
(712, 9, 'Task telah disetujui: asfsaG', '../production/view_task.php?id=147', 1, '2025-06-19 06:17:08'),
(713, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=147', 1, '2025-06-19 06:17:22'),
(714, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=147', 1, '2025-06-19 06:17:22'),
(715, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=148', 1, '2025-06-19 06:17:42'),
(716, 2, 'Task siap untuk direview: SDFAG', '../content/view_task.php?id=148', 1, '2025-06-19 06:18:01'),
(717, 9, 'Task telah disetujui: SDFAG', '../production/view_task.php?id=148', 1, '2025-06-19 06:18:12'),
(718, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=149', 1, '2025-06-19 06:21:39'),
(719, 2, 'Task siap untuk direview: dsafdasgasd', '../content/view_task.php?id=149', 1, '2025-06-19 06:21:53'),
(720, 9, 'Task telah disetujui: dsafdasgasd', '../production/view_task.php?id=149', 1, '2025-06-19 06:22:12'),
(721, 2, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=149', 1, '2025-06-19 06:22:48'),
(722, 9, 'Task telah selesai dan diverifikasi dengan rating: 5/5', 'view_task.php?id=149', 1, '2025-06-19 06:22:48'),
(723, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=150', 1, '2025-06-19 06:25:31'),
(724, 2, 'Task siap untuk direview: Nyoba Notif Desktop', '../content/view_task.php?id=150', 1, '2025-06-19 06:39:25'),
(725, 9, 'Task telah disetujui: Nyoba Notif Desktop', '../production/view_task.php?id=150', 1, '2025-06-19 06:39:40'),
(726, 4, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 1, '2025-06-19 08:25:46'),
(727, 7, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 1, '2025-06-19 08:25:46'),
(728, 10, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 0, '2025-06-19 08:25:46'),
(729, 9, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 1, '2025-06-19 08:25:46'),
(730, 5, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 1, '2025-06-19 08:25:46'),
(731, 8, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 1, '2025-06-19 08:25:46'),
(732, 2, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 1, '2025-06-19 08:25:46'),
(733, 11, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 0, '2025-06-19 08:25:46'),
(734, 13, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 1, '2025-06-19 08:25:46'),
(735, 6, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 0, '2025-06-19 08:25:46'),
(736, 3, 'Pengumuman baru - Halo ini pengumuman loh', '../shared/view_announcement.php?id=1', 0, '2025-06-19 08:25:46'),
(737, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=151', 1, '2025-06-19 08:53:55'),
(738, 2, 'Task siap untuk direview: sagdg', '../content/view_task.php?id=151', 1, '2025-06-19 08:54:46'),
(739, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=152', 1, '2025-06-19 09:02:53'),
(740, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=153', 1, '2025-06-19 09:12:54'),
(741, 2, 'Task siap untuk direview: adsasdgdasg', '../content/view_task.php?id=152', 1, '2025-06-19 09:13:43'),
(742, 2, 'Task siap untuk direview: adsgasg', '../content/view_task.php?id=153', 1, '2025-06-19 09:16:37'),
(743, 9, 'Task telah disetujui: adsgasg', '../production/view_task.php?id=153', 1, '2025-06-19 09:16:50'),
(744, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=154', 1, '2025-06-19 10:32:00'),
(745, 5, 'Task telah selesai dan menunggu konfirmasi', 'view_task.php?id=154', 1, '2025-06-19 10:45:43'),
(746, 5, 'Task Anda ditolak. Alasan: safasf', '../content/view_task.php?id=154', 1, '2025-06-19 10:47:33'),
(747, 7, 'Anda mendapat tugas baru: dsgadsgad', '../production/view_task.php?id=154', 1, '2025-06-19 10:48:10'),
(748, 5, 'Task telah selesai dan menunggu konfirmasi', 'view_task.php?id=154', 1, '2025-06-19 10:48:55'),
(749, 7, 'Task telah disetujui: dsgadsgad', '../production/view_task.php?id=154', 1, '2025-06-19 10:50:45'),
(750, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=155', 0, '2025-06-19 10:55:32'),
(751, 5, 'Task siap untuk direview: asddsg', '../content/view_task.php?id=155', 1, '2025-06-19 10:55:53'),
(752, 7, 'Task telah disetujui: asddsg', '../production/view_task.php?id=155', 0, '2025-06-19 10:58:32'),
(753, 7, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=156', 0, '2025-06-19 10:59:07'),
(754, 5, 'Task siap untuk direview: sadfgasgsdga', '../content/view_task.php?id=156', 1, '2025-06-19 11:04:07'),
(755, 7, 'Task telah disetujui: sadfgasgsdga', '../production/view_task.php?id=156', 0, '2025-06-19 11:06:03'),
(756, 7, 'Task Anda memerlukan revisi dari Creative Director', 'view_task.php?id=156', 0, '2025-06-19 11:25:07'),
(757, 5, 'Task siap untuk direview: sadfgasgsdga', '../content/view_task.php?id=156', 1, '2025-06-19 13:05:39'),
(758, 7, 'Task telah disetujui: sadfgasgsdga', '../production/view_task.php?id=156', 0, '2025-06-19 13:05:55'),
(759, 9, 'Anda mendapatkan 30 poin tambahan: bonus', 'points.php', 1, '2025-06-20 06:25:28'),
(760, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=157', 1, '2025-06-20 08:04:04'),
(761, 2, 'Task siap untuk direview: dsafdsgdsagsdgsda', '../content/view_task.php?id=157', 1, '2025-06-20 08:04:24'),
(763, 2, 'Anda mendapatkan 30 poin tambahan: bonus', 'points.php', 1, '2025-06-20 08:05:44'),
(764, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=158', 1, '2025-06-22 00:53:10'),
(765, 2, 'Task siap untuk direview: Nyoba konten minggu', '../content/view_task.php?id=158', 1, '2025-06-22 00:55:54'),
(766, 9, 'Task telah disetujui: Nyoba konten minggu', '../production/view_task.php?id=158', 1, '2025-06-22 00:57:09'),
(767, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=159', 1, '2025-06-22 05:43:22'),
(768, 2, 'Task siap untuk direview: sdsdgsa', '../content/view_task.php?id=159', 1, '2025-06-22 05:43:40'),
(769, 9, 'Task telah disetujui: sdsdgsa', '../production/view_task.php?id=159', 1, '2025-06-22 05:44:05'),
(770, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=160', 1, '2025-06-22 05:56:12'),
(771, 2, 'Anda mendapat task baru yang perlu dikerjakan', '../content/view_task.php?id=160', 1, '2025-06-22 05:56:30'),
(772, 1, 'Task distribusi telah diupload dan menunggu verifikasi: sfsdafdss', '../director/view_task.php?id=160', 1, '2025-06-22 05:57:10'),
(773, 9, 'Task telah disetujui: adsasdgdasg', '../production/view_task.php?id=152', 1, '2025-06-22 11:31:05'),
(774, 9, 'Task telah disetujui: sagdg', '../production/view_task.php?id=151', 1, '2025-06-22 11:31:49'),
(775, 8, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=161', 1, '2025-06-23 07:53:30'),
(776, 3, 'Task siap untuk direview: Gila! Trump Gaungkan Make Iran Great Again, Isyaratkan Tumbangkan Rezim Republik Islam di Teheran', '../content/view_task.php?id=161', 0, '2025-06-23 07:54:01'),
(777, 8, 'Task telah disetujui: Gila! Trump Gaungkan Make Iran Great Again, Isyaratkan Tumbangkan Rezim Republik Islam di Teheran', '../production/view_task.php?id=161', 1, '2025-06-23 07:55:19'),
(778, 8, 'Anda mendapatkan 30 poin tambahan: Bonus point karena lukman anak baik', 'points.php', 1, '2025-06-23 07:56:45'),
(779, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=162', 1, '2025-06-23 08:59:38'),
(780, 1, 'Task siap untuk direview: asdgsgd', '../content/view_task.php?id=162', 1, '2025-06-23 09:01:26'),
(781, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=163', 1, '2025-06-23 09:15:16'),
(782, 1, 'Task siap untuk direview: zfdnzdfnzd', '../content/view_task.php?id=163', 1, '2025-06-23 09:19:38'),
(783, 6, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=164', 0, '2025-06-23 09:24:48'),
(784, 1, 'Task siap untuk direview: Kerjaan Topan', '../content/view_task.php?id=164', 1, '2025-06-23 09:25:30'),
(785, 7, 'Anda mendapat tugas baru: tbgtrbrt', '../production/view_task.php?id=140', 0, '2025-06-25 05:00:43'),
(786, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=165', 1, '2025-06-25 05:34:39'),
(787, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 25/06/2025', '../content/view_task.php?id=165', 1, '2025-06-25 05:38:35'),
(788, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Nyoba task baru', '../director/view_task.php?id=165', 1, '2025-06-25 05:44:45'),
(789, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=166', 1, '2025-06-25 07:22:42'),
(790, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 25/06/2025', '../content/view_task.php?id=166', 1, '2025-06-25 07:24:06'),
(791, 1, 'Task distribusi telah diupload dan menunggu verifikasi: sdgdsfhdfh', '../director/view_task.php?id=166', 1, '2025-06-26 05:25:17'),
(792, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=167', 1, '2025-06-26 05:51:27'),
(793, 6, 'Anda ditambahkan sebagai tim produksi tambahan untuk task: Podcast Jurnalisik #1', '../production/view_task.php?id=167', 0, '2025-06-26 05:51:47'),
(794, 6, 'Anda telah dihapus dari tim produksi tambahan untuk task: Podcast Jurnalisik #1', '../production/index.php', 0, '2025-06-26 06:01:12'),
(795, 6, 'Anda ditambahkan sebagai tim produksi tambahan untuk task: Podcast Jurnalisik #1', '../production/view_task.php?id=167', 0, '2025-06-26 06:01:17'),
(796, 6, 'Anda ditambahkan sebagai tim produksi tambahan untuk task: Podcast Jurnalisik #1', '../production/view_task.php?id=167', 0, '2025-06-26 06:20:59'),
(797, 2, 'Task siap untuk direview: Podcast Jurnalisik #1', '../content/view_task.php?id=167', 1, '2025-06-26 06:21:31'),
(798, 9, 'Task telah disetujui: Podcast Jurnalisik #1', '../production/view_task.php?id=167', 1, '2025-06-26 06:30:40'),
(799, 6, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=168', 0, '2025-06-27 03:34:44'),
(800, 11, 'Anda ditambahkan sebagai tim produksi tambahan untuk task: Tukpir 1', '../production/view_task.php?id=168', 0, '2025-06-27 03:35:19'),
(801, 2, 'Task siap untuk direview: Tukpir 1', '../content/view_task.php?id=168', 1, '2025-06-27 03:35:37'),
(802, 6, 'Task telah disetujui: Tukpir 1', '../production/view_task.php?id=168', 0, '2025-06-27 03:35:56'),
(803, 6, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=169', 0, '2025-06-27 03:37:09'),
(804, 2, 'Task siap untuk direview: Tukpir 2', '../content/view_task.php?id=169', 1, '2025-06-27 03:37:22'),
(805, 6, 'Task telah disetujui: Tukpir 2', '../production/view_task.php?id=169', 0, '2025-06-27 03:37:42'),
(806, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=170', 1, '2025-06-30 13:08:22'),
(807, 2, 'Ada task baru dari tim marketing yang telah disetujui', 'view_task.php?id=170', 1, '2025-06-30 13:09:08'),
(808, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=171', 1, '2025-06-30 13:13:44'),
(809, 20, 'Task publikasi baru memerlukan persetujuan Anda', '../redaksi/view_task.php?id=172', 1, '2025-06-30 13:14:51'),
(810, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=173', 1, '2025-06-30 13:18:17'),
(811, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 30/06/2025', '../content/view_task.php?id=173', 1, '2025-06-30 13:18:39'),
(812, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 30/06/2025', '../content/view_task.php?id=171', 1, '2025-06-30 13:19:04'),
(813, 1, 'Task baru memerlukan persetujuan Anda', 'approve_marketing_task.php?id=174', 1, '2025-06-30 13:23:44'),
(814, 20, 'Task publikasi baru memerlukan persetujuan Anda', '../redaksi/view_task.php?id=175', 1, '2025-06-30 13:27:28'),
(815, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 30/06/2025', '../content/view_task.php?id=174', 1, '2025-06-30 13:30:11'),
(817, 18, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 30/06/2025', '../redaksi/view_task.php?id=175', 0, '2025-06-30 13:41:40'),
(818, 18, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 30/06/2025', '../redaksi/view_task.php?id=172', 0, '2025-06-30 13:45:04'),
(819, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=176', 1, '2025-06-30 13:47:02'),
(820, 2, 'Task siap untuk direview: Putusan MK soal Pemilu Timbulkan 2 Masalah Besar', '../content/view_task.php?id=176', 1, '2025-06-30 13:47:39'),
(821, 9, 'Task telah disetujui: Putusan MK soal Pemilu Timbulkan 2 Masalah Besar', '../production/view_task.php?id=176', 1, '2025-06-30 13:48:03'),
(822, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=177', 1, '2025-06-30 13:48:41'),
(823, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=729', 1, '2025-06-30 13:52:44'),
(824, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=730', 1, '2025-06-30 13:54:14'),
(825, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=180', 1, '2025-06-30 13:57:23'),
(826, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=181', 1, '2025-06-30 14:05:06'),
(827, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=182', 1, '2025-06-30 14:09:47'),
(828, 6, 'Anda ditambahkan sebagai tim produksi tambahan untuk task: Kapolri Mutasi 26 Jenderal Bintang Satu, Ini Daftarnya', '../production/view_task.php?id=182', 0, '2025-06-30 14:11:29'),
(829, 2, 'Task siap untuk direview: Kapolri Mutasi 26 Jenderal Bintang Satu, Ini Daftarnya', '../content/view_task.php?id=182', 1, '2025-06-30 14:11:42'),
(830, 1, 'Task baru memerlukan persetujuan Anda', 'view_task.php?id=183', 1, '2025-07-01 07:03:44'),
(831, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 01/07/2025', '../content/view_task.php?id=183', 1, '2025-07-01 07:04:41'),
(832, 1, 'Task baru memerlukan persetujuan Anda', 'view_task.php?id=184', 1, '2025-07-01 07:11:52'),
(833, 20, 'Task publikasi baru memerlukan persetujuan Anda', '../redaksi/view_task.php?id=185', 0, '2025-07-01 07:12:32'),
(834, 1, 'Task baru dari marketing memerlukan persetujuan Anda', 'view_task.php?id=186', 1, '2025-07-01 07:14:44'),
(835, 20, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=187', 0, '2025-07-01 07:15:36'),
(836, 20, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=188', 0, '2025-07-01 07:16:21'),
(837, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 01/07/2025', '../content/view_task.php?id=186', 1, '2025-07-01 07:56:57'),
(838, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 01/07/2025', '../content/view_task.php?id=184', 1, '2025-07-01 07:57:06'),
(839, 20, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=189', 0, '2025-07-01 07:57:35'),
(840, 21, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=189', 0, '2025-07-01 07:57:35'),
(841, 1, 'Task baru dari marketing memerlukan persetujuan Anda', 'view_task.php?id=190', 1, '2025-07-01 08:05:46'),
(842, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=191', 1, '2025-07-01 08:32:40'),
(843, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=192', 1, '2025-07-01 08:39:37'),
(844, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=193', 1, '2025-07-01 08:43:45'),
(845, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=194', 1, '2025-07-01 08:52:49'),
(846, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=195', 1, '2025-07-01 08:58:12'),
(847, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=196', 1, '2025-07-01 09:00:01'),
(848, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=197', 1, '2025-07-01 09:17:17'),
(849, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=198', 1, '2025-07-01 09:45:57'),
(850, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=199', 1, '2025-07-01 09:51:54'),
(851, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=200', 1, '2025-07-01 09:54:16'),
(852, 20, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=201', 0, '2025-07-01 09:57:53'),
(853, 21, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=201', 0, '2025-07-01 09:57:53'),
(854, 20, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=202', 0, '2025-07-01 10:34:26'),
(855, 21, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=202', 0, '2025-07-01 10:34:26'),
(856, 20, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=203', 0, '2025-07-01 10:40:32'),
(857, 21, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=203', 0, '2025-07-01 10:40:33'),
(858, 20, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=204', 0, '2025-07-01 10:47:32'),
(859, 21, 'Task publikasi baru dari marketing memerlukan persetujuan Anda', '../redaktur/view_task.php?id=204', 0, '2025-07-01 10:47:33'),
(860, 18, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 01/07/2025', '../redaksi/view_task.php?id=201', 0, '2025-07-01 10:49:23'),
(861, 1, 'Task baru dari marketing memerlukan persetujuan Anda', 'view_task.php?id=205', 1, '2025-07-01 10:52:03');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(862, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 01/07/2025', '../content/view_task.php?id=205', 1, '2025-07-01 10:52:30'),
(863, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Dari TikTok ke Brain Rot, Otak Remaja \'Emas\' Indonesia Kian Tumpul', '../director/view_task.php?id=205', 1, '2025-07-01 10:54:27'),
(864, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=206', 1, '2025-07-01 16:04:47'),
(865, 1, 'Task baru dari marketing memerlukan persetujuan Anda', 'view_task.php?id=207', 1, '2025-07-02 04:54:58'),
(866, 1, 'Task baru dari marketing memerlukan persetujuan Anda', 'view_task.php?id=209', 1, '2025-07-02 11:53:10'),
(867, 1, 'Task baru dari marketing memerlukan persetujuan Anda', 'view_task.php?id=210', 1, '2025-07-02 12:21:07'),
(868, 9, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 02/07/2025', '../production/view_task.php?id=210', 1, '2025-07-02 12:21:18'),
(869, 2, 'Task siap untuk direview: Semen Padang FC Resmi Datangkan Pemain Timnas Indonesia, Ronaldo Kwateh', '../content/view_task.php?id=180', 1, '2025-07-02 12:37:36'),
(870, 20, 'Task siap untuk diverifikasi: dasgasg', '../redaktur/view_task.php?id=210', 0, '2025-07-02 12:44:15'),
(871, 21, 'Task siap untuk diverifikasi: dasgasg', '../redaktur/view_task.php?id=210', 0, '2025-07-02 12:44:15'),
(873, 1, 'Task baru dari marketing memerlukan persetujuan Anda', 'view_task.php?id=211', 1, '2025-07-02 13:43:28'),
(874, 9, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 02/07/2025', '../production/view_task.php?id=211', 1, '2025-07-02 13:43:35'),
(875, 20, 'Task siap untuk diverifikasi: Nyoba lagi', '../redaktur/view_task.php?id=211', 0, '2025-07-02 13:43:59'),
(876, 21, 'Task siap untuk diverifikasi: Nyoba lagi', '../redaktur/view_task.php?id=211', 0, '2025-07-02 13:43:59'),
(878, 20, 'Task siap untuk diverifikasi: zsadgag', '../redaktur/view_task.php?id=206', 0, '2025-07-02 14:26:01'),
(879, 21, 'Task siap untuk diverifikasi: zsadgag', '../redaktur/view_task.php?id=206', 0, '2025-07-02 14:26:01'),
(881, 20, 'Task siap untuk diverifikasi: Diresmikan Prabowo, Wisma Danantara Jadi Rumah Besar Pengelolaan Investasi', '../redaktur/view_task.php?id=178', 0, '2025-07-02 14:49:58'),
(882, 21, 'Task siap untuk diverifikasi: Diresmikan Prabowo, Wisma Danantara Jadi Rumah Besar Pengelolaan Investasi', '../redaktur/view_task.php?id=178', 0, '2025-07-02 14:49:58'),
(884, 1, 'Task menunggu verifikasi akhir: Nyoba lagi', '../admin/verify_task.php?id=211', 1, '2025-07-02 15:08:04'),
(885, 2, 'Anda mendapat task baru yang perlu dikerjakan dengan deadline 02/07/2025', '../content/view_task.php?id=209', 1, '2025-07-03 04:56:34'),
(886, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Nyoba lagi sekarang jadi gak bisa huhu sedih', '../director/view_task.php?id=184', 1, '2025-07-03 06:37:48'),
(887, 1, 'Task distribusi telah diupload dan menunggu verifikasi: sdgsad', '../director/view_task.php?id=209', 1, '2025-07-03 06:38:00'),
(888, 1, 'Task distribusi telah diupload dan menunggu verifikasi: sagasdg', '../director/view_task.php?id=171', 1, '2025-07-03 06:38:19'),
(889, 1, 'Task distribusi telah diupload dan menunggu verifikasi: asdgsdg', '../director/view_task.php?id=173', 1, '2025-07-03 06:38:33'),
(890, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Nyoba Notif WA 1', '../director/view_task.php?id=174', 1, '2025-07-03 06:38:47'),
(891, 1, 'Task distribusi telah diupload dan menunggu verifikasi: Nyoba task baru selasa', '../director/view_task.php?id=183', 1, '2025-07-03 06:43:22'),
(892, 1, 'Task distribusi telah diupload dan menunggu verifikasi: nyoba lagi deh', '../director/view_task.php?id=186', 1, '2025-07-03 06:43:35'),
(893, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=212', 1, '2025-07-03 07:54:46'),
(894, 2, 'Anda mendapatkan 10 poin tambahan: bonus', 'points.php', 1, '2025-07-03 07:56:14'),
(895, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=213', 1, '2025-07-03 08:03:23'),
(896, 9, 'Anda memiliki task baru yang menunggu konfirmasi', 'view_task.php?id=214', 1, '2025-07-03 09:48:29'),
(897, 20, 'Task siap untuk diverifikasi: sdagsad', '../redaktur/view_task.php?id=214', 0, '2025-07-03 13:03:40'),
(898, 21, 'Task siap untuk diverifikasi: sdagsad', '../redaktur/view_task.php?id=214', 0, '2025-07-03 13:03:40'),
(899, 9, 'Task telah disetujui: sdagsad', '../production/view_task.php?id=214', 0, '2025-07-04 06:37:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `platforms`
--

CREATE TABLE `platforms` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `platforms`
--

INSERT INTO `platforms` (`id`, `name`, `icon`, `created_at`) VALUES
(1, 'Instagram', NULL, '2025-06-09 07:33:57'),
(2, 'Facebook', NULL, '2025-06-09 07:33:57'),
(3, 'X', NULL, '2025-06-09 07:33:57'),
(4, 'TikTok', NULL, '2025-06-09 07:33:57'),
(5, 'YouTube', NULL, '2025-06-09 07:33:57'),
(6, 'Threads', NULL, '2025-06-09 07:33:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `point_settings`
--

CREATE TABLE `point_settings` (
  `id` int(11) NOT NULL,
  `team` varchar(50) NOT NULL,
  `category` varchar(100) NOT NULL,
  `content_type` varchar(100) NOT NULL,
  `points` decimal(10,1) NOT NULL DEFAULT 1.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `program_schedules`
--

CREATE TABLE `program_schedules` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `target_count` int(11) NOT NULL DEFAULT 1,
  `pic_id` int(11) DEFAULT NULL,
  `editor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `program_schedules`
--

INSERT INTO `program_schedules` (`id`, `program_id`, `day_of_week`, `target_count`, `pic_id`, `editor_id`, `created_at`) VALUES
(1, 18, 'Monday', 1, 2, 6, '2025-06-27 03:32:43'),
(2, 12, 'Tuesday', 1, 2, 11, '2025-06-27 03:32:54'),
(3, 3, 'Tuesday', 1, 5, 11, '2025-06-27 03:33:10'),
(4, 19, 'Wednesday', 1, 4, 6, '2025-06-27 03:33:27'),
(5, 3, 'Friday', 2, 2, 6, '2025-06-27 03:34:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `resources`
--

INSERT INTO `resources` (`id`, `name`, `file_path`, `type`, `uploaded_by`, `created_at`) VALUES
(1, 'Bank BCA', '684bc417d1f4c.png', 'Logo', 1, '2025-06-13 06:24:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `revisions`
--

CREATE TABLE `revisions` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `revised_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `revisions`
--

INSERT INTO `revisions` (`id`, `task_id`, `note`, `revised_by`, `created_at`) VALUES
(1, 3, 'judulnya typo', 2, '2025-06-04 12:40:50'),
(3, 9, 'jelek', 2, '2025-06-04 14:24:38'),
(4, 4, 'jelek juga', 2, '2025-06-04 14:25:01'),
(7, 6, 'kurang bagus', 1, '2025-06-04 17:02:03'),
(8, 35, 'perbaiki lagi', 1, '2025-06-09 07:12:50'),
(9, 50, 'upload ulang', 1, '2025-06-10 14:57:37'),
(10, 63, 'ada revisi salah konten, upload ulang', 1, '2025-06-12 06:24:43'),
(11, 156, 'benerin lagi', 1, '2025-06-19 11:25:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_type` enum('morning','afternoon','long','off') NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `shifts`
--

INSERT INTO `shifts` (`id`, `user_id`, `shift_date`, `shift_type`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 7, '2025-06-10', 'afternoon', 1, '2025-06-10 13:46:27', '2025-06-10 13:46:38'),
(2, 10, '2025-06-10', 'morning', 1, '2025-06-10 13:46:45', '2025-06-10 13:46:45'),
(3, 9, '2025-06-10', 'morning', 1, '2025-06-10 13:46:49', '2025-06-10 13:46:49'),
(4, 8, '2025-06-10', 'afternoon', 1, '2025-06-10 13:46:54', '2025-06-10 13:47:00'),
(5, 11, '2025-06-10', 'morning', 1, '2025-06-10 13:47:10', '2025-06-10 13:47:10'),
(6, 6, '2025-06-10', 'afternoon', 1, '2025-06-10 13:47:16', '2025-06-10 13:47:16'),
(7, 4, '2025-06-10', 'morning', 1, '2025-06-10 13:49:50', '2025-06-10 13:49:50'),
(8, 5, '2025-06-10', 'morning', 1, '2025-06-10 13:49:54', '2025-06-10 13:49:54'),
(9, 2, '2025-06-10', 'morning', 1, '2025-06-10 13:49:56', '2025-06-10 13:49:56'),
(10, 3, '2025-06-10', 'afternoon', 1, '2025-06-10 13:49:59', '2025-06-10 13:49:59'),
(11, 4, '2025-06-11', 'morning', 1, '2025-06-10 13:50:45', '2025-06-10 13:50:45'),
(12, 5, '2025-06-11', 'morning', 1, '2025-06-10 13:50:56', '2025-06-10 13:50:56'),
(13, 2, '2025-06-11', 'morning', 1, '2025-06-10 13:51:01', '2025-06-10 13:51:01'),
(14, 3, '2025-06-11', 'afternoon', 1, '2025-06-10 13:51:07', '2025-06-10 13:51:07'),
(15, 4, '2025-06-12', 'morning', 1, '2025-06-10 14:30:06', '2025-06-10 14:30:06'),
(16, 5, '2025-06-12', 'morning', 1, '2025-06-10 14:36:13', '2025-06-10 14:36:13'),
(17, 2, '2025-06-12', 'morning', 1, '2025-06-10 14:36:22', '2025-06-10 14:36:22'),
(18, 3, '2025-06-12', 'afternoon', 1, '2025-06-10 14:36:29', '2025-06-10 14:36:29'),
(19, 7, '2025-06-11', 'morning', 1, '2025-06-10 14:37:17', '2025-06-10 14:37:17'),
(20, 10, '2025-06-11', 'morning', 1, '2025-06-10 14:38:34', '2025-06-10 14:38:34'),
(21, 9, '2025-06-11', 'morning', 1, '2025-06-10 14:38:37', '2025-06-10 14:38:37'),
(22, 8, '2025-06-11', 'morning', 1, '2025-06-10 14:38:39', '2025-06-10 14:38:39'),
(23, 11, '2025-06-11', 'afternoon', 1, '2025-06-10 14:38:46', '2025-06-10 14:38:46'),
(24, 6, '2025-06-11', 'afternoon', 1, '2025-06-10 14:38:49', '2025-06-10 14:38:49'),
(25, 7, '2025-06-12', 'afternoon', 1, '2025-06-10 14:38:59', '2025-06-10 14:38:59'),
(26, 10, '2025-06-12', 'afternoon', 1, '2025-06-10 14:39:01', '2025-06-10 14:39:01'),
(27, 9, '2025-06-12', 'morning', 1, '2025-06-10 14:39:04', '2025-06-10 14:39:04'),
(28, 8, '2025-06-12', 'morning', 1, '2025-06-10 14:39:06', '2025-06-10 14:39:06'),
(29, 11, '2025-06-12', 'morning', 1, '2025-06-10 14:39:12', '2025-06-10 14:39:12'),
(30, 6, '2025-06-12', 'afternoon', 1, '2025-06-10 14:39:15', '2025-06-10 14:39:15'),
(31, 7, '2025-06-13', 'morning', 1, '2025-06-13 04:29:19', '2025-06-13 04:29:19'),
(32, 10, '2025-06-13', 'afternoon', 1, '2025-06-13 04:29:24', '2025-06-13 04:29:24'),
(33, 11, '2025-06-13', 'off', 1, '2025-06-13 04:29:29', '2025-06-13 04:29:29'),
(34, 7, '2025-06-22', 'morning', 1, '2025-06-22 00:26:12', '2025-06-22 00:26:12'),
(35, 9, '2025-06-22', 'afternoon', 1, '2025-06-22 00:26:22', '2025-06-22 00:26:22'),
(36, 6, '2025-06-22', 'morning', 1, '2025-06-22 00:26:28', '2025-06-22 00:28:07'),
(37, 11, '2025-06-22', 'off', 1, '2025-06-22 00:26:40', '2025-06-22 00:26:40'),
(38, 8, '2025-06-22', 'off', 1, '2025-06-22 00:26:46', '2025-06-22 00:26:46'),
(39, 10, '2025-06-22', 'off', 1, '2025-06-22 00:27:05', '2025-06-22 00:27:05'),
(40, 4, '2025-06-22', 'morning', 1, '2025-06-22 00:27:17', '2025-06-22 00:28:33'),
(41, 5, '2025-06-22', 'off', 1, '2025-06-22 00:27:23', '2025-06-22 00:27:23'),
(42, 3, '2025-06-22', 'off', 1, '2025-06-22 00:27:26', '2025-06-22 00:27:26'),
(43, 2, '2025-06-22', 'off', 1, '2025-06-22 00:27:30', '2025-06-22 00:27:30'),
(44, 7, '2025-06-23', 'morning', 1, '2025-06-22 03:27:16', '2025-06-22 03:27:16'),
(45, 10, '2025-06-23', 'morning', 1, '2025-06-22 03:27:20', '2025-06-22 03:27:20'),
(46, 4, '2025-06-23', 'morning', 1, '2025-06-22 03:27:32', '2025-06-22 03:27:32'),
(47, 5, '2025-06-23', 'morning', 1, '2025-06-22 03:27:36', '2025-06-22 03:27:36'),
(48, 2, '2025-06-23', 'morning', 1, '2025-06-22 03:27:41', '2025-06-22 03:27:41'),
(49, 3, '2025-06-23', 'afternoon', 1, '2025-06-22 03:27:47', '2025-06-22 03:27:47'),
(50, 8, '2025-06-23', 'afternoon', 1, '2025-06-22 03:28:02', '2025-06-22 03:28:02'),
(51, 11, '2025-06-23', 'afternoon', 1, '2025-06-22 03:28:07', '2025-06-22 03:28:07'),
(52, 9, '2025-06-23', 'afternoon', 1, '2025-06-22 03:28:15', '2025-06-22 03:28:15'),
(53, 6, '2025-06-23', 'morning', 1, '2025-06-22 03:28:19', '2025-06-22 03:28:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `target_schedule`
--

CREATE TABLE `target_schedule` (
  `id` int(11) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `target_date` date NOT NULL,
  `target_value` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `target_settings`
--

CREATE TABLE `target_settings` (
  `id` int(11) NOT NULL,
  `target_type` varchar(50) DEFAULT 'points',
  `setting_key` varchar(50) NOT NULL,
  `setting_value` float NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `target_settings`
--

INSERT INTO `target_settings` (`id`, `target_type`, `setting_key`, `setting_value`, `updated_at`, `updated_by`) VALUES
(1, 'points', 'daily_points_target_production', 30, '2025-06-16 05:10:33', 1),
(2, 'points', 'daily_points_target_content', 30, '2025-06-16 05:10:29', 1),
(3, 'points', 'daily_views_target_instagram', 4, '2025-06-22 03:36:55', 1),
(4, 'points', 'daily_views_target_tiktok', 4, '2025-06-22 03:36:55', 1),
(5, 'points', 'weekly_views_target_instagram', 50000, '2025-06-22 03:47:15', 1),
(6, 'points', 'weekly_views_target_tiktok', 100000, '2025-06-22 03:46:24', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `content_type_id` int(11) NOT NULL,
  `content_pillar_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `status` enum('draft','waiting_head_confirmation','waiting_redaktur_confirmation','waiting_confirmation','in_production','ready_for_review','uploaded','completed','revision','rejected','cancelled','overdue') DEFAULT 'draft',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `points` decimal(5,2) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `deadline` datetime DEFAULT NULL,
  `uploaded_link` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `rating` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `platform_count` int(11) DEFAULT 0,
  `source_link` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `category_id`, `content_type_id`, `content_pillar_id`, `account_id`, `status`, `priority`, `points`, `created_by`, `assigned_to`, `deadline`, `uploaded_link`, `notes`, `rejection_reason`, `is_verified`, `rating`, `created_at`, `updated_at`, `file_path`, `verified_at`, `client_name`, `platform_count`, `source_link`) VALUES
(1, 'Viral Emak-emak Diduga Turis dari Indonesia Joget-joget di Kuil Suci Bangkok', '#', 1, 5, 1, 1, 'draft', 'medium', NULL, 2, 6, '2025-06-04 18:25:00', NULL, NULL, NULL, 0, NULL, '2025-06-04 11:25:38', '2025-06-04 11:25:38', NULL, NULL, NULL, 0, NULL),
(2, 'Inilah 5 Pemain Timnas Indonesia yang Absen Lawan China', '#', 1, 3, 1, 1, 'draft', 'medium', NULL, 2, 9, '2025-06-04 19:45:00', NULL, NULL, NULL, 0, NULL, '2025-06-04 11:43:17', '2025-06-04 11:43:17', NULL, NULL, NULL, 0, NULL),
(3, 'TikTok Shop Bakal PHK Massal Ratusan Karyawan di Indonesia pada Juli 2025', '#', 1, 7, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 19:17:00', 'https://www.instagram.com/p/DKeoLmbzhJS/', NULL, NULL, 1, 4, '2025-06-04 12:17:33', '2025-06-04 12:51:36', '68403eebb615a.jpg', '2025-06-04 19:51:36', NULL, 0, NULL),
(4, 'Candaan Prabowo saat Bertemu Megawati: Ibu Kurus, Dietnya Berhasil', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 21:10:00', NULL, NULL, NULL, 1, 5, '2025-06-04 13:10:42', '2025-06-04 17:01:44', '68405783e6e08.jpg', '2025-06-05 00:01:44', NULL, 0, NULL),
(5, 'Prabowo Singkirkan Pejabat yang tak Bisa Kerja, *PKB Dukung Penuh!*', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 21:21:00', NULL, NULL, NULL, 1, 5, '2025-06-04 13:21:50', '2025-06-04 17:01:49', NULL, '2025-06-05 00:01:49', NULL, 0, NULL),
(6, 'Pengumuman! Pemerintah Batal Beri Diskon Tarif Listrik 50% pada Juni-Juli', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 21:31:00', NULL, NULL, NULL, 1, 5, '2025-06-04 13:31:45', '2025-06-04 17:02:44', 'tasks/task_68407c1c75067_6.jpg', '2025-06-05 00:02:44', NULL, 0, NULL),
(7, 'Sri Mulyani Tetapkan Tarif Hotel Menteri saat Perjalanan Dinas, Maksimal Rp9,3 Juta per Malam', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 21:46:00', NULL, NULL, NULL, 1, 4, '2025-06-04 13:47:01', '2025-06-04 17:06:47', NULL, '2025-06-05 00:06:47', NULL, 0, NULL),
(8, 'Visa Furoda tak Terbit! Wendy Cagur dan Istri Batal Berangkat Haji', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 22:09:00', NULL, NULL, NULL, 1, 4, '2025-06-04 14:09:12', '2025-06-04 17:06:54', NULL, '2025-06-05 00:06:54', NULL, 0, NULL),
(9, 'Ngeri! Truk Tabrak Pembatas Gate Tol Jagorawi, Diduga Kelebihan Muatan', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 22:19:00', NULL, NULL, NULL, 1, 5, '2025-06-04 14:19:31', '2025-06-04 17:06:38', '6840585261638.jpg', '2025-06-05 00:06:38', NULL, 0, NULL),
(10, 'Terjadi kesalahan saat memulai tracking', '#', 1, 1, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 21:33:00', NULL, NULL, NULL, 1, 5, '2025-06-04 14:28:26', '2025-06-04 17:02:49', NULL, '2025-06-05 00:02:49', NULL, 0, NULL),
(11, 'Brutal! 19 Napi Lapas Nabire Kabur dan Serang Petugas, 11 Diantaranya Anggota KKB', '#', 1, 1, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 21:40:00', NULL, NULL, NULL, 1, 3, '2025-06-04 14:35:43', '2025-06-04 17:02:51', NULL, '2025-06-05 00:02:51', NULL, 0, NULL),
(12, 'Sri Mulyani Tetapkan Biaya Konsumsi Rapat Menteri Rp171.000 per Orang', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 21:40:00', NULL, NULL, NULL, 1, 4, '2025-06-04 14:37:16', '2025-06-04 17:02:55', NULL, '2025-06-05 00:02:55', NULL, 0, NULL),
(13, '*Gaji ke-13 ASN Cair!* Sri Mulyani Siapkan hingga Rp49,3 Triliun', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 21:53:00', NULL, NULL, NULL, 1, 4, '2025-06-04 14:47:39', '2025-06-04 17:06:51', 'task_13_20250604165817.jpg', '2025-06-05 00:06:51', NULL, 0, NULL),
(14, 'Megawati dan Prabowo Makin Hangat di Hari Pancasila, Ini Kata Pengamat', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 22:20:00', NULL, NULL, NULL, 1, 4, '2025-06-04 14:59:24', '2025-06-04 17:06:58', 'tasks/task_68406d82bb7bc_14.jpg', '2025-06-05 00:06:58', NULL, 0, NULL),
(15, 'Visa Furoda tak Terbit! Wendy Cagur dan Istri Batal Berangkat Haji', '#', 1, 1, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 23:39:00', NULL, NULL, NULL, 1, 4, '2025-06-04 15:39:23', '2025-06-04 17:07:09', 'tasks/task_6840697416075_15.jpg', '2025-06-05 00:07:09', NULL, 0, NULL),
(16, 'Curhat Jadi Ketum Parpol di Indonesia Susah, AHY Bandingkan dengan Sistem Single Party China', '#', 1, 1, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 23:05:00', NULL, NULL, NULL, 1, 4, '2025-06-04 16:02:39', '2025-06-04 17:07:02', 'tasks/task_6840700689c03_16.jpg', '2025-06-05 00:07:02', NULL, 0, NULL),
(17, 'Inter Milan dan Simone Inzaghi Resmi Berpisah, Ini Alasan di Baliknya', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-04 23:31:00', NULL, NULL, NULL, 1, 4, '2025-06-04 16:25:41', '2025-06-04 17:07:05', 'tasks/task_684073a89746e_17.jpg', '2025-06-05 00:07:05', NULL, 0, NULL),
(18, 'Perempat Final Roland Garros 2025 Djokovic Vs Zverev, Duel Dua Sahabat di Tanah Liat', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-05 11:27:00', NULL, NULL, NULL, 1, 4, '2025-06-04 16:28:03', '2025-06-04 17:07:12', 'tasks/task_6840744a8bcbb_18.jpg', '2025-06-05 00:07:12', NULL, 0, NULL),
(19, 'Bukan \'Omon-omon\', Kluivert Siap Tebus Janji Kemenangan Lawan China', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-05 00:24:00', NULL, NULL, NULL, 1, 5, '2025-06-04 17:18:35', '2025-06-04 19:24:36', 'tasks/task_68408019e0379_19.jpg', '2025-06-05 02:24:36', NULL, 0, NULL),
(20, 'Keangkeran Stadion GBK Bukan Ancaman', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-05 00:29:00', NULL, NULL, NULL, 1, 5, '2025-06-04 17:23:39', '2025-06-05 05:13:03', 'tasks/task_68408136d6364_20.jpg', '2025-06-05 12:13:03', NULL, 0, NULL),
(21, 'Rayakan 30 Tahun Kemenangan Le Mans', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-05 01:25:00', NULL, NULL, NULL, 1, 4, '2025-06-04 17:25:11', '2025-06-05 05:13:19', 'tasks/task_6840822ab7d8f_21.jpg', '2025-06-05 12:13:19', NULL, 0, NULL),
(22, 'Jokowi-Gibran Diminta Dukung Kedekatan Prabowo-Megawati', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 8, '2025-06-05 02:28:00', NULL, NULL, NULL, 1, 4, '2025-06-04 17:28:54', '2025-06-05 06:19:32', 'tasks/task_68412b6213218_22.jpg', '2025-06-05 13:19:32', NULL, 0, NULL),
(23, 'Golkar Ingatkan Ada Syarat dan Ketentuannya', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-05 00:56:00', NULL, NULL, NULL, 1, 4, '2025-06-04 17:42:03', '2025-06-05 05:13:15', 'tasks/task_684087c07d355_23.jpg', '2025-06-05 12:13:15', NULL, 0, NULL),
(24, 'Golkar Harap Hubungan Prabowo-Megawati Semakin Hangat', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-05 01:56:00', NULL, NULL, NULL, 1, 4, '2025-06-04 17:56:18', '2025-06-05 05:13:28', 'tasks/task_68408ab4e1c75_24.png', '2025-06-05 12:13:28', NULL, 0, NULL),
(25, 'Saksi Teguh Ungkap Dapat Perintah Khusus Ini dari Budi Arie', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-05 03:07:00', NULL, NULL, NULL, 1, 5, '2025-06-04 19:07:04', '2025-06-05 05:29:29', 'tasks/task_6840a27ba1d4d_25.mp4', '2025-06-05 12:29:29', NULL, 0, NULL),
(26, 'Hyundai Indonesia Buka Pre-booking untuk Palisade Hybrid', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-05 03:52:00', NULL, NULL, NULL, 1, 4, '2025-06-04 19:52:36', '2025-06-05 05:13:32', 'tasks/task_6840a421014fc_26.png', '2025-06-05 12:13:32', NULL, 0, NULL),
(27, 'Inter Milan dan Simone Inzaghi Resmi Berpisah, Ini Alasan di Baliknya', 'Kekalahan memalukan 0-5 dari Paris Saint-Germain di final Liga Champions 2025 rupanya jadi laga terakhir Simone Inzaghi bersama Inter Milan. Klub secara resmi mengumumkan perpisahan dengan sang pelatih pada Senin (3/6) waktu setempat.\r\n\r\nLewat pernyataan resmi, Inter menyebut keputusan itu diambil berdasarkan kesepakatan bersama setelah pertemuan internal klub dan Inzaghi. Namun, alasan di baliknya jelas: Inzaghi akan melanjutkan karier ke Arab Saudi, tepatnya ke klub tajir Al-Hilal.', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 8, '2025-06-05 12:30:00', NULL, NULL, NULL, 1, 5, '2025-06-05 05:25:33', '2025-06-05 05:29:47', 'tasks/task_68412af748de5_27.jpg', '2025-06-05 12:29:47', NULL, 0, NULL),
(28, 'Soal Kapolri bakal Dicopot, Seskab: Jenderal Listyo Sudah Menghadap ke Prabowo', '#', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-05 13:52:00', NULL, NULL, NULL, 1, 4, '2025-06-05 06:50:01', '2025-06-09 06:19:26', 'tasks/task_68415443af71f_28.mp4', '2025-06-09 13:19:26', NULL, 0, NULL),
(29, 'Nyoba', '#', 1, 2, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-08 22:55:00', NULL, NULL, NULL, 1, 5, '2025-06-08 15:25:58', '2025-06-09 06:19:21', 'tasks/task_6845abd98a5bb_29.png', '2025-06-09 13:19:21', NULL, 0, NULL),
(34, 'Nyoba task dari marketing, apakah berhasil?', 'halo halo', 4, 18, 7, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-09 14:49:00', NULL, NULL, NULL, 0, NULL, '2025-06-09 04:49:57', '2025-06-09 07:47:09', '1749444597_task_68412ac69afa8_27.jpg', NULL, NULL, 0, NULL),
(35, 'nyoba ke 2', 'halo haloi', 4, 18, 7, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-09 17:04:00', NULL, NULL, NULL, 0, NULL, '2025-06-09 05:04:49', '2025-06-09 07:46:51', '1749445489_task_68408019e0379_19.jpg', NULL, NULL, 0, NULL),
(36, 'Nyoba 3 hahaha', 'halonhai hai hai', 4, 18, 7, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-09 18:09:00', NULL, NULL, NULL, 1, 4, '2025-06-09 05:09:30', '2025-06-09 06:19:08', '1749445770_task_68412af748de5_27.jpg', '2025-06-09 13:19:08', NULL, 0, NULL),
(38, 'hahaha okay okay', 'siapppp', 4, 18, 7, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-09 18:24:00', NULL, NULL, NULL, 1, 5, '2025-06-09 05:24:50', '2025-06-09 06:18:49', '1749446690_task_68412ac69afa8_27.jpg', '2025-06-09 13:18:49', NULL, 0, NULL),
(39, 'nyoab aki', 'sfsdfsa', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-09 13:43:00', NULL, NULL, NULL, 1, 5, '2025-06-09 06:13:57', '2025-06-09 06:18:41', 'tasks/task_68467c6daf9ff_39.jpg', '2025-06-09 13:18:41', NULL, 0, NULL),
(40, 'sdfzsdg', 'adsgasdgas', 1, 3, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-09 14:00:00', NULL, NULL, NULL, 0, NULL, '2025-06-09 06:31:15', '2025-06-09 07:12:41', 'tasks/task_68467fe12226a_40.jpg', NULL, NULL, 0, NULL),
(41, 'halo gaes', 'ini ngedit apa ya\r\n\r\njsjsjs', 3, 17, 7, 1, 'completed', 'medium', NULL, 13, 9, '2025-06-09 18:32:00', NULL, NULL, NULL, 0, NULL, '2025-06-09 06:32:57', '2025-06-09 07:12:33', 'tasks/task_684685d550ea2_41.pptx', NULL, NULL, 0, NULL),
(42, 'KONTEN MARKETING 1', 'hahaha nyoba doang', 4, 18, 7, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-09 21:46:00', NULL, NULL, NULL, 0, NULL, '2025-06-09 11:47:14', '2025-06-09 11:49:07', '1749469634_WhatsApp Image 2025-06-01 at 14.21.46_f29f2214.jpg', NULL, NULL, 0, NULL),
(43, 'ddadf', 'agasgas', 1, 2, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-09 19:30:00', NULL, NULL, NULL, 1, 5, '2025-06-09 12:00:30', '2025-06-15 08:52:48', 'tasks/task_6846eb390aeb5_43.jpg', '2025-06-15 15:52:48', NULL, 0, NULL),
(44, 'nyoba lagi konten lainnya', 'hahaha nyoa bodaasd', 1, 5, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-09 19:33:00', NULL, NULL, NULL, 1, 5, '2025-06-09 12:03:56', '2025-06-09 14:21:17', 'tasks/task_6846eb4cbe50e_44.jpg', '2025-06-09 21:21:17', NULL, 0, NULL),
(45, 'Nyoba lagi gaes konten berbayar', 'hahaha', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-09 03:01:00', NULL, NULL, NULL, 0, NULL, '2025-06-09 14:02:16', '2025-06-09 14:08:29', '1749477736_task_6840697416075_15.jpg', NULL, 'Pertamina', 0, NULL),
(46, 'Nyoba Marketing Promo Film', 'Halo halo gaes', 4, 18, 14, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-10 12:38:00', NULL, NULL, NULL, 1, 5, '2025-06-10 01:39:25', '2025-06-10 01:58:24', '1749519565_1749446690_task_68412ac69afa8_27.jpg', '2025-06-10 08:58:24', 'MVP', 0, NULL),
(48, 'Nyoba Marketing Desain Proposal 2', 'halo gaes', 3, 17, 7, 1, 'completed', 'medium', NULL, 13, 9, '2025-06-10 13:39:00', NULL, NULL, NULL, 1, 5, '2025-06-10 01:41:47', '2025-06-10 01:58:30', 'tasks/task_68478e60e9f56_48.pptx', '2025-06-10 08:58:30', NULL, 0, NULL),
(49, 'Judul konten biasa hahaha', 'oke sippp', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-10 09:14:00', NULL, NULL, NULL, 1, 5, '2025-06-10 01:44:57', '2025-06-10 13:04:12', 'tasks/task_68478e88c0e04_49.jpg', '2025-06-10 20:04:12', NULL, 0, NULL),
(50, 'nyoba final baru 2', 'hahaha', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-10 03:55:00', NULL, NULL, NULL, 1, 5, '2025-06-10 14:56:05', '2025-06-10 16:10:59', '1749567365_1749446690_task_68412ac69afa8_27.jpg', '2025-06-10 23:10:59', 'Pertamina', 0, NULL),
(51, 'Multiple Task 1', 'aaaa', 1, 3, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-10 22:46:00', NULL, NULL, NULL, 1, 5, '2025-06-10 15:17:14', '2025-06-10 16:10:53', 'tasks/task_68484d3ad84b2_51.jpg', '2025-06-10 23:10:53', NULL, 0, NULL),
(52, 'Multiple Task 2', 'hahaha', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-10 22:47:00', NULL, NULL, NULL, 1, 5, '2025-06-10 15:17:30', '2025-06-10 16:10:46', 'tasks/task_68484d1021dd9_52.jpg', '2025-06-10 23:10:46', NULL, 0, NULL),
(53, 'Multiple Task 3', 'hihihihi', 1, 2, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-10 22:47:00', NULL, NULL, NULL, 1, 5, '2025-06-10 15:17:48', '2025-06-10 15:34:52', 'tasks/task_68484d4d1a450_53.jpg', '2025-06-10 22:34:52', NULL, 0, NULL),
(54, 'Cover Tukar Pikiran Eps 4', 'haha', 3, 13, 16, 1, 'completed', 'medium', NULL, 1, 9, '2025-06-10 23:25:00', NULL, NULL, NULL, 1, NULL, '2025-06-10 15:55:35', '2025-06-10 16:11:21', 'tasks/task_6848558f68eb9_54.jpg', '2025-06-10 23:11:21', NULL, 0, NULL),
(55, 'produksi 5', 'oke', 3, 13, 7, 1, 'completed', 'medium', NULL, 13, 9, '2025-06-10 04:57:00', NULL, NULL, NULL, 1, 5, '2025-06-10 15:57:35', '2025-06-10 16:11:13', 'tasks/task_6848562865639_55.jpg', '2025-06-10 23:11:13', NULL, 0, NULL),
(56, 'Nyoba Produksi 3', 'sfSA', 3, 13, 16, 1, 'completed', 'medium', NULL, 1, 9, '2025-06-10 23:36:00', NULL, NULL, NULL, 1, NULL, '2025-06-10 16:07:07', '2025-06-10 16:11:02', 'tasks/task_6848583b2c3c2_56.jpg', '2025-06-10 23:11:02', NULL, 0, NULL),
(57, 'Nyoba ada gak ya 1', 'hihi', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-11 14:06:00', NULL, NULL, NULL, 1, NULL, '2025-06-11 06:36:19', '2025-06-11 12:42:37', 'tasks/task_684924017b8dc_57.jpg', '2025-06-11 19:42:37', NULL, 0, NULL),
(58, 'Nyoba point', 'hahaha', 1, 2, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-11 22:12:00', NULL, NULL, NULL, 1, 5, '2025-06-11 14:42:26', '2025-06-11 14:46:37', 'tasks/task_684995e640957_58.png', '2025-06-11 21:46:37', NULL, 0, NULL),
(59, 'Nyoba point 2', 'haha', 1, 3, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-11 22:35:00', NULL, NULL, NULL, 1, 5, '2025-06-11 15:05:59', '2025-06-11 15:07:42', 'tasks/task_68499b78db09e_59.png', '2025-06-11 22:07:42', NULL, 0, NULL),
(60, 'nyoba task 3', 'assf', 1, 2, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-11 22:36:00', NULL, NULL, NULL, 1, 5, '2025-06-11 15:06:14', '2025-06-11 15:07:35', 'tasks/task_68499b81c5692_60.png', '2025-06-11 22:07:35', NULL, 0, NULL),
(61, 'nyoba point 4', 'dsfsad', 1, 2, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-11 22:48:00', NULL, NULL, NULL, 1, 4, '2025-06-11 15:18:37', '2025-06-11 15:19:38', 'tasks/task_68499e61a949c_61.png', '2025-06-11 22:19:38', NULL, 0, NULL),
(62, 'nyoba point 6', 'dfsgsdfg', 1, 2, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-11 23:07:00', NULL, NULL, NULL, 1, 5, '2025-06-11 15:37:58', '2025-06-11 15:39:00', 'tasks/task_6849a2ef7ba39_62.png', '2025-06-11 22:39:00', NULL, 0, NULL),
(63, 'Pop ini judunya hahaha', 'itosbjsdjhbsdufdsa', 4, 19, 13, 1, 'completed', 'high', NULL, 13, 2, '2025-06-12 17:30:00', NULL, NULL, NULL, 1, 5, '2025-06-12 06:21:04', '2025-06-12 06:25:09', '1749709264_Jaksa_Banner 600x500.png', '2025-06-12 13:25:09', 'Pop Mie', 0, NULL),
(64, 'dsgdsfh', 'dsfhsdfh', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-12 15:27:00', NULL, NULL, NULL, 1, 5, '2025-06-12 06:27:57', '2025-06-12 06:28:52', '1749709677_Jaksa_Banner 600x500.png', '2025-06-12 13:28:52', 'Pop Mie', 0, NULL),
(65, 'dgsdag', 'dsggsdag', 3, 17, 7, 1, 'completed', 'medium', NULL, 13, 9, '2025-06-12 13:29:00', NULL, NULL, NULL, 1, 4, '2025-06-12 06:29:53', '2025-06-12 11:00:13', 'tasks/task_684a740973116_65.png', '2025-06-12 18:00:13', NULL, 0, NULL),
(66, 'Haha hihi nyoba point', 'okeh', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 18:16:00', NULL, NULL, NULL, 1, 5, '2025-06-12 10:47:08', '2025-06-12 11:00:17', 'tasks/task_684ab03e1717f_66.png', '2025-06-12 18:00:17', NULL, 0, NULL),
(67, 'Ilustrasi Suara Dari rimba', 'udfaa', 3, 15, 7, 1, 'completed', 'medium', NULL, 13, 9, '2025-06-12 20:10:00', NULL, NULL, NULL, 1, 5, '2025-06-12 11:03:08', '2025-06-12 11:08:10', 'tasks/task_684ab5084108a_67.png', '2025-06-12 18:08:10', NULL, 0, NULL),
(68, 'dsfgdfgfd', 'fhdshsfdhds', 1, 3, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 18:40:00', NULL, NULL, NULL, 1, 5, '2025-06-12 11:10:18', '2025-06-12 11:17:51', 'tasks/task_684ab5a4684b5_68.png', '2025-06-12 18:17:51', NULL, 0, NULL),
(69, 'asdasgdsa point', 'dsgasgasdgag', 1, 3, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 18:46:00', NULL, NULL, NULL, 1, 5, '2025-06-12 11:17:08', '2025-06-12 11:17:54', 'tasks/task_684ab74109569_69.png', '2025-06-12 18:17:54', NULL, 0, NULL),
(70, 'asgasdas point 9', 'fghsh', 1, 3, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 20:22:00', NULL, NULL, NULL, 1, 5, '2025-06-12 12:52:47', '2025-06-12 12:53:41', 'tasks/task_684acdaabcf3e_70.png', '2025-06-12 19:53:41', NULL, 0, NULL),
(71, 'NYOBA POINT 1', 'HAHAHA', 1, 3, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 20:40:00', NULL, NULL, NULL, 1, 5, '2025-06-12 13:10:49', '2025-06-12 13:11:47', 'tasks/task_684ad1e705339_71.png', '2025-06-12 20:11:47', NULL, 0, NULL),
(72, 'NYOBA POINT 2', 'HAHAHA', 1, 3, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 20:44:00', NULL, NULL, NULL, 1, 5, '2025-06-12 13:14:52', '2025-06-12 13:15:23', 'tasks/task_684ad2d5cc1df_72.png', '2025-06-12 20:15:23', NULL, 0, NULL),
(73, 'NYOBA POINT 3', 'SADGASGSA', 1, 3, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 20:45:00', NULL, NULL, NULL, 0, 5, '2025-06-12 13:15:49', '2025-06-12 13:17:14', 'tasks/task_684ad30edc376_73.png', '2025-06-12 20:17:14', NULL, 0, NULL),
(74, 'NYOBA POINT 4', 'hahaha', 1, 2, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 20:47:00', NULL, NULL, NULL, 0, 5, '2025-06-12 13:17:57', '2025-06-12 13:20:29', 'tasks/task_684ad401bd8c9_74.png', '2025-06-12 20:20:29', NULL, 0, NULL),
(75, 'NYOBA POINT 4', 'HAHAHA', 1, 2, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 20:49:00', NULL, NULL, NULL, 0, 5, '2025-06-12 13:19:35', '2025-06-12 13:20:32', 'tasks/task_684ad3f94c8c1_75.png', '2025-06-12 20:20:32', NULL, 0, NULL),
(76, 'NYOBA POINT 5', 'DSAGASDG', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-12 20:26:00', NULL, NULL, NULL, 0, 5, '2025-06-12 13:21:44', '2025-06-12 13:23:23', '1749734504_Jaksa_Banner 600x500.png', '2025-06-12 20:23:23', 'Pop Mie', 0, NULL),
(77, 'NYOBA POINT 6', 'HAHA', 1, 2, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 20:54:00', NULL, NULL, NULL, 1, 5, '2025-06-12 13:24:50', '2025-06-12 13:25:26', 'tasks/task_684ad52db961d_77.png', '2025-06-12 20:25:26', NULL, 0, NULL),
(78, 'sdagasdgd', 'asgasg', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-12 02:37:00', NULL, NULL, NULL, 1, 5, '2025-06-12 13:37:37', '2025-06-12 14:04:14', '1749735457_Jaksa_Banner 600x500.png', '2025-06-12 21:04:14', 'Pop Mie', 0, NULL),
(79, 'sdagdagfa', 'fhaha', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-12 23:43:00', NULL, NULL, NULL, 1, 5, '2025-06-12 15:43:08', '2025-06-12 15:52:40', '1749742988_Jaksa_Banner 600x500.png', '2025-06-12 22:52:40', 'Pop Mie', 0, NULL),
(80, 'dsgs', 'dgdsad', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-12 22:54:00', NULL, NULL, NULL, 1, 5, '2025-06-12 15:52:20', '2025-06-12 15:53:18', '1749743540_Jaksa_Banner 600x500.png', '2025-06-12 22:53:18', 'Pop Mie', 0, NULL),
(81, 'rdfs', 'hdfshfdshds', 1, 1, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 23:24:00', NULL, NULL, NULL, 1, 5, '2025-06-12 15:54:12', '2025-06-12 15:54:51', 'tasks/task_684af82ea8512_81.png', '2025-06-12 22:54:51', NULL, 0, NULL),
(82, 'rag', 'dahafdhsd', 1, 1, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 23:35:00', NULL, NULL, NULL, 1, 5, '2025-06-12 16:05:37', '2025-06-12 16:06:43', 'tasks/task_684afae718daa_82.png', '2025-06-12 23:06:43', NULL, 0, NULL),
(83, 'daga', 'dgadsga', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-12 23:12:00', NULL, NULL, NULL, 1, 5, '2025-06-12 16:07:15', '2025-06-12 16:07:55', '1749744435_Jaksa_Banner 600x500.png', '2025-06-12 23:07:55', 'Pop Mie', 4, NULL),
(84, 'dsfsdg', 'dsgadsg', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-12 23:11:00', NULL, NULL, NULL, 1, 5, '2025-06-12 16:08:42', '2025-06-12 16:09:15', '1749744522_Jaksa_Banner 600x500.png', '2025-06-12 23:09:15', 'Pop Mie', 3, NULL),
(85, 'xzzdbah', 'sdfhadfhsd', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-12 23:15:00', NULL, NULL, NULL, 1, 5, '2025-06-12 16:10:18', '2025-06-12 16:10:58', '1749744618_Jaksa_Banner 600x500.png', '2025-06-12 23:10:58', 'Pop Mie', 3, NULL),
(86, 'dsaavd', 'zxcvzxv', 1, 1, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-12 23:41:00', NULL, NULL, NULL, 1, 5, '2025-06-12 16:11:22', '2025-06-12 16:12:08', 'tasks/task_684afc375b457_86.png', '2025-06-12 23:12:08', NULL, 0, NULL),
(87, 'sfsadf', 'asdfafsaf', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-15 14:00:00', NULL, NULL, NULL, 1, 5, '2025-06-15 04:32:45', '2025-06-15 04:35:13', NULL, '2025-06-15 11:35:13', 'MVP', 3, NULL),
(88, 'sadgdasg', 'agag', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-15 16:30:00', NULL, NULL, NULL, 1, 5, '2025-06-15 08:53:34', '2025-06-16 00:32:04', 'tasks/task_684f65e305402_88.png', '2025-06-16 07:32:04', NULL, 0, NULL),
(89, 'sdgasg', 'dgasgaa', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-16 09:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 00:32:54', '2025-06-16 00:33:36', 'tasks/task_684f66409bda3_89.png', '2025-06-16 07:33:36', NULL, 0, NULL),
(90, 'dsagdasg', 'asdgdasg', 1, 1, 2, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 12:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 05:11:14', '2025-06-16 05:12:29', 'tasks/task_684fa7a4be28c_90.png', '2025-06-16 12:12:29', NULL, 0, NULL),
(91, 'safasdg', 'dsga', 1, 1, 2, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 12:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 05:13:06', '2025-06-16 05:13:46', 'tasks/task_684fa7ebc71b8_91.png', '2025-06-16 12:13:46', NULL, 0, NULL),
(92, 'asdagf', 'gasdgas', 1, 3, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 05:39:08', '2025-06-16 05:39:46', 'tasks/task_684fae05ee802_92.png', '2025-06-16 12:39:46', NULL, 0, NULL),
(93, 'asdgads', 'gasdg', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 05:40:27', '2025-06-16 05:41:05', 'tasks/task_684fae564acf9_93.png', '2025-06-16 12:41:05', NULL, 0, NULL),
(94, 'adsfasg', 'asadfds', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 05:49:56', '2025-06-16 05:50:32', 'tasks/task_684fb08d5fa58_94.jpg', '2025-06-16 12:50:32', NULL, 0, NULL),
(95, 'asdgasgas', 'gdasgas', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 05:53:39', '2025-06-16 05:54:11', 'tasks/task_684fb16b7e230_95.jpg', '2025-06-16 12:54:11', NULL, 0, NULL),
(96, 'adsgszgaa', 'hahdadsh', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 05:54:55', '2025-06-16 05:55:27', 'tasks/task_684fb1b6cd7e8_96.jpg', '2025-06-16 12:55:27', NULL, 0, NULL),
(97, 'dsaga', 'dgasga', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 05:56:39', '2025-06-16 05:57:12', 'tasks/task_684fb21f93c98_97.jpg', '2025-06-16 12:57:12', NULL, 0, NULL),
(98, 'asdgasg', 'asgasgd', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 06:06:20', '2025-06-16 06:06:53', 'tasks/task_684fb46361d7f_98.jpg', '2025-06-16 13:06:53', NULL, 0, NULL),
(99, 'asdgas', 'gadsga', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 06:08:07', '2025-06-16 06:08:35', 'tasks/task_684fb4cf5ca2f_99.jpg', '2025-06-16 13:08:35', NULL, 0, NULL),
(100, 'sfdsg', 'adsgasdg', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 06:09:05', '2025-06-16 06:09:37', 'tasks/task_684fb50a9b656_100.png', '2025-06-16 13:09:37', NULL, 0, NULL),
(101, 'agads', 'gdasga', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 06:11:42', '2025-06-16 06:12:15', 'tasks/task_684fb5aa0da9b_101.jpg', '2025-06-16 13:12:15', NULL, 0, NULL),
(102, 'dsfbdf', 'bsdfbsdfb', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 06:14:44', '2025-06-16 06:15:16', 'tasks/task_684fb65f37c32_102.jpg', '2025-06-16 13:15:16', NULL, 0, NULL),
(103, 'asDGDS', 'GDAFGDAF', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 14:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 06:16:22', '2025-06-16 06:16:51', 'tasks/task_684fb6bd3bc84_103.jpg', '2025-06-16 13:16:51', NULL, 0, NULL),
(104, 'sdaf', 'asdfasf', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 14:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 06:20:09', '2025-06-16 06:20:38', 'tasks/task_684fb7a0af7e8_104.jpg', '2025-06-16 13:20:38', NULL, 0, NULL),
(105, 'zdga', 'gadsaga', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 14:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:02:37', '2025-06-16 07:03:09', 'tasks/task_684fc1943f26d_105.jpg', '2025-06-16 14:03:09', NULL, 0, NULL),
(106, 'sdag', 'dsfhds', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 14:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:04:28', '2025-06-16 07:04:59', 'tasks/task_684fc204e6364_106.jpg', '2025-06-16 14:04:59', NULL, 0, NULL),
(107, 'sdgsa', 'gasgasg', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 14:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:07:42', '2025-06-16 07:08:09', 'tasks/task_684fc2c69b09d_107.jpg', '2025-06-16 14:08:09', NULL, 0, NULL),
(108, 'dsfasdf', 'asdfasdf', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 14:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:08:27', '2025-06-16 07:08:59', 'tasks/task_684fc2f3983d2_108.jpg', '2025-06-16 14:08:59', NULL, 0, NULL),
(109, 'sdgsadg', 'asdga', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-16 14:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:09:37', '2025-06-16 07:10:06', NULL, '2025-06-16 14:10:06', 'MVP', 3, NULL),
(110, 'xzvxzv', 'xczvzx', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:18:13', '2025-06-16 07:18:53', 'tasks/task_684fc53fb3c51_110.jpg', '2025-06-16 14:18:53', NULL, 0, NULL),
(111, 'sadg', 'asgasga', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:26:32', '2025-06-16 07:27:09', 'tasks/task_684fc739a8458_111.jpg', '2025-06-16 14:27:09', NULL, 0, NULL),
(112, 'sadfgadsg', 'asdgasg', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:27:42', '2025-06-16 07:28:09', 'tasks/task_684fc7751e7ce_112.jpg', '2025-06-16 14:28:09', NULL, 0, NULL),
(113, 'zgzxc', 'gxzhz', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-16 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:28:31', '2025-06-16 07:29:05', NULL, '2025-06-16 14:29:05', 'MVP', 4, NULL),
(114, 'asfsag', 'sdgasdg', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-16 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:32:26', '2025-06-16 07:32:55', NULL, '2025-06-16 14:32:55', 'MVP', 5, NULL),
(115, 'egaadg', 'adgdsag', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-16 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:37:19', '2025-06-16 07:37:50', NULL, '2025-06-16 14:37:50', 'MVP', 4, NULL),
(116, 'sdafdasf', 'afsdaf', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-16 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:39:05', '2025-06-16 07:39:34', NULL, '2025-06-16 14:39:34', 'MVP', 4, NULL),
(117, 'fsdfgds', 'asdgas', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-16 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:39:53', '2025-06-16 07:40:26', NULL, '2025-06-16 14:40:26', 'MVP', 3, NULL),
(118, 'asdfasd', 'dsagsdag', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-16 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:40:50', '2025-06-16 07:41:23', NULL, '2025-06-16 14:41:23', 'MVP', 2, NULL),
(119, 'dsfhsd', 'hsdfhsd', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:45:54', '2025-06-16 07:46:30', NULL, '2025-06-16 14:46:30', 'MVP', 2, NULL),
(120, 'safadsg', 'adsgasgd', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:48:10', '2025-06-16 07:48:47', NULL, '2025-06-16 14:48:47', 'MVP', 0, NULL),
(121, 'dfgdfh', 'fdhd', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:49:00', '2025-06-16 07:49:47', 'tasks/task_684fcc747c7a3_121.jpg', '2025-06-16 14:49:47', NULL, 0, NULL),
(122, 'sdgsda', 'gdhafdh', 1, 1, 2, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:50:18', '2025-06-16 07:50:49', 'tasks/task_684fccc37c9e6_122.jpg', '2025-06-16 14:50:49', NULL, 0, NULL),
(123, 'sdgagfhasdg', 'fhfdhdh', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:51:41', '2025-06-16 07:52:13', 'tasks/task_684fcd174b0ea_123.jpg', '2025-06-16 14:52:13', NULL, 0, NULL),
(124, 'sdfgafg', 'adsgas', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:52:36', '2025-06-16 07:53:04', 'tasks/task_684fcd4adee8c_124.jpg', '2025-06-16 14:53:04', NULL, 0, NULL),
(125, 'dSGzds', 'ghdfhadfh', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:53:22', '2025-06-16 07:54:01', 'tasks/task_684fcd7d2d800_125.jpg', '2025-06-16 14:54:01', NULL, 0, NULL),
(126, 'ethert', 'herther', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:54:32', '2025-06-16 07:55:13', 'tasks/task_684fcdbfb28aa_126.jpg', '2025-06-16 14:55:13', NULL, 0, NULL),
(127, 'dfgdfh', 'dffhfdh', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:57:42', '2025-06-16 07:58:13', 'tasks/task_684fce808d42e_127.jpg', '2025-06-16 14:58:13', NULL, 0, NULL),
(128, 'fdghdfgh', 'fdhggdfhd', 1, 1, 2, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-16 15:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 07:58:29', '2025-06-16 07:58:52', 'tasks/task_684fceadae198_128.jpg', '2025-06-16 14:58:52', NULL, 0, NULL),
(129, 'dsagasdg', 'asdgsadas', 1, 1, 1, 1, 'completed', 'medium', NULL, 3, 7, '2025-06-16 16:30:00', NULL, NULL, NULL, 1, 5, '2025-06-16 09:13:45', '2025-06-16 09:14:19', 'tasks/task_684fe052a465c_129.jpg', '2025-06-16 16:14:19', NULL, 0, NULL),
(130, 'adsgs', 'gadsga', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 5, '2025-06-17 13:00:00', NULL, NULL, NULL, 1, 5, '2025-06-17 05:30:51', '2025-06-17 05:32:44', NULL, '2025-06-17 12:32:44', 'MVP', 4, NULL),
(134, 'dasgasg', 'asdgasdg', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-18 17:00:00', NULL, NULL, NULL, 1, 5, '2025-06-18 09:21:20', '2025-06-18 11:55:16', 'tasks/task_6852987f11db1_134.jpg', '2025-06-18 18:55:16', NULL, 0, NULL),
(137, 'safSG', 'Dsagag', 1, 1, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-18 18:00:00', NULL, NULL, NULL, 1, 5, '2025-06-18 10:21:10', '2025-06-18 11:55:19', 'tasks/task_6852a8ebf263d_137.jpg', '2025-06-18 18:55:19', NULL, 0, NULL),
(138, 'asgasd', 'gdsgasg', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-18 18:00:00', NULL, NULL, NULL, 1, 5, '2025-06-18 10:26:53', '2025-06-18 11:55:22', 'tasks/task_6852a8f448bb4_138.png', '2025-06-18 18:55:22', NULL, 0, NULL),
(139, 'Postingan Marketing', 'fv grb gbhbbtrbr', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-18 22:00:00', NULL, NULL, NULL, 1, 5, '2025-06-18 12:22:24', '2025-06-18 12:23:59', '1750249344_Screenshot_20231031_155650_Chrome_Beta.jpg', '2025-06-18 19:23:59', 'MVP', 2, NULL),
(140, 'tbgtrbrt', 'btrbgrtgb', 3, 14, 7, 1, 'waiting_confirmation', 'medium', NULL, 13, 7, '2025-06-18 20:00:00', NULL, NULL, NULL, 0, NULL, '2025-06-18 12:25:02', '2025-06-25 05:00:43', NULL, NULL, NULL, 0, NULL),
(141, 'r3g34g', '34g34g', 3, 17, 7, 1, 'completed', 'medium', NULL, 13, 9, '2025-06-18 20:00:00', NULL, NULL, NULL, 1, 5, '2025-06-18 12:26:09', '2025-06-18 12:27:29', 'tasks/task_6852b09f094ab_141.jpg', '2025-06-18 19:27:29', NULL, 0, NULL),
(142, 'Nyoba Notifikasi Desktop 1', 'gsdagsdagads', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 13:00:00', NULL, NULL, NULL, 1, 5, '2025-06-19 05:28:20', '2025-06-19 05:33:09', 'tasks/task_6853a04326492_142.jpg', '2025-06-19 12:33:09', NULL, 0, NULL),
(143, 'sgsdag', 'adsgasdg', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 13:00:00', NULL, NULL, NULL, 1, 5, '2025-06-19 05:36:13', '2025-06-19 06:14:31', 'tasks/task_6853a2741dc2a_143.jpg', '2025-06-19 13:14:31', NULL, 0, NULL),
(144, 'sgsadgd', 'agdahd', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-19 05:53:47', '2025-06-19 06:07:19', 'tasks/task_6853a6eb46ab2_144.jpg', '2025-06-19 13:07:19', NULL, 0, NULL),
(145, 'dsfag', 'adsgsadgsadg', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-19 06:11:01', '2025-06-19 06:25:03', 'tasks/task_6853aa5dae419_145.jpg', '2025-06-19 13:25:03', NULL, 0, NULL),
(146, 'sadgadsg', 'sadgasg', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-19 06:15:01', '2025-06-19 06:16:04', 'tasks/task_6853aaeed67db_146.jpg', '2025-06-19 13:16:04', NULL, 0, NULL),
(147, 'asfsaG', 'ASDGASDG', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 14:00:00', NULL, NULL, NULL, 1, 5, '2025-06-19 06:16:30', '2025-06-19 06:17:22', 'tasks/task_6853ab5be5737_147.png', '2025-06-19 13:17:22', NULL, 0, NULL),
(148, 'SDFAG', 'ASDGDSA', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 14:00:00', NULL, NULL, NULL, 1, 5, '2025-06-19 06:17:42', '2025-06-19 06:24:56', 'tasks/task_6853ab999e420_148.jpg', '2025-06-19 13:24:56', NULL, 0, NULL),
(149, 'dsafdasgasd', 'gsdagsadg', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 14:00:00', NULL, NULL, NULL, 1, 5, '2025-06-19 06:21:39', '2025-06-19 06:22:48', 'tasks/task_6853ac81184b9_149.jpg', '2025-06-19 13:22:48', NULL, 0, NULL),
(150, 'Nyoba Notif Desktop', 'saddfdsagsad', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 14:00:00', NULL, NULL, NULL, 1, 5, '2025-06-19 06:25:31', '2025-06-19 06:39:57', 'tasks/task_6853b09d86a6c_150.jpg', '2025-06-19 13:39:57', NULL, 0, NULL),
(151, 'sagdg', 'adsgadsga', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 16:30:00', NULL, NULL, NULL, 1, 5, '2025-06-19 08:53:55', '2025-06-22 11:32:00', 'tasks/task_6853d05681449_151.jpg', '2025-06-22 18:32:00', NULL, 0, NULL),
(152, 'adsasdgdasg', 'gasgasdgs', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 16:30:00', NULL, NULL, NULL, 1, 5, '2025-06-19 09:02:53', '2025-06-22 11:31:25', 'tasks/task_6853d4c7c5407_152.jpg', '2025-06-22 18:31:25', NULL, 0, NULL),
(153, 'adsgasg', 'sadgasdgas', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-19 16:30:00', NULL, NULL, NULL, 1, 5, '2025-06-19 09:12:54', '2025-06-19 13:06:24', 'tasks/task_6853d57578f95_153.jpg', '2025-06-19 20:06:24', NULL, 0, NULL),
(155, 'asddsg', 'dasgasg', 1, 1, 1, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-19 18:30:00', NULL, NULL, NULL, 1, 5, '2025-06-19 10:55:32', '2025-06-19 10:58:45', 'tasks/task_6853ecb990259_155.png', '2025-06-19 17:58:45', NULL, 0, NULL),
(156, 'sadfgasgsdga', 'asdgasdgas', 1, 1, 2, 1, 'completed', 'medium', NULL, 5, 7, '2025-06-19 18:30:00', '', '', NULL, 1, 5, '2025-06-19 10:59:07', '2025-06-19 13:06:16', 'tasks/task_68540b23eab82_156.jpg', '2025-06-19 20:06:16', NULL, 0, NULL),
(157, 'dsafdsgdsagsdgsda', 'sdagsdagsad', 1, 3, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-20 15:30:00', '', '', NULL, 1, 5, '2025-06-20 08:04:04', '2025-06-20 08:05:09', 'tasks/task_68551608eb9ec_157.jpg', '2025-06-20 15:05:09', NULL, 0, NULL),
(158, 'Nyoba konten minggu', 'hahaha hihihi', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-22 09:30:00', '', '', NULL, 1, 5, '2025-06-22 00:53:10', '2025-06-22 00:57:37', 'tasks/task_6857549aec950_158.jpg', '2025-06-22 07:57:37', NULL, 0, NULL),
(159, 'sdsdgsa', 'sdagds', 1, 1, 1, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-22 13:00:00', '', '', NULL, 1, 5, '2025-06-22 05:43:22', '2025-06-22 05:44:16', 'tasks/task_6857980c388b0_159.jpg', '2025-06-22 12:44:16', NULL, 0, NULL),
(160, 'sfsdafdss', 'adgsasg', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-22 13:30:00', NULL, NULL, NULL, 1, 5, '2025-06-22 05:56:12', '2025-06-22 05:57:20', NULL, '2025-06-22 12:57:20', 'MVP', 4, NULL),
(161, 'Gila! Trump Gaungkan Make Iran Great Again, Isyaratkan Tumbangkan Rezim Republik Islam di Teheran', 'Single image dengan foto Trump', 1, 1, 1, 1, 'completed', 'medium', NULL, 3, 8, '2025-06-23 15:30:00', '', '', NULL, 1, 5, '2025-06-23 07:53:30', '2025-06-23 07:55:59', 'tasks/task_68590819563e6_161.jpg', '2025-06-23 14:55:59', NULL, 0, NULL),
(162, 'asdgsgd', 'sdgasaga', 1, 1, 1, 1, 'completed', 'medium', NULL, 1, 9, '2025-06-23 19:00:00', '', '', NULL, 1, 5, '2025-06-23 08:59:38', '2025-06-25 05:43:41', 'tasks/task_685917e6208a9_162.png', '2025-06-25 12:43:41', NULL, 0, NULL),
(163, 'zfdnzdfnzd', 'zncvxnxcv', 1, 1, 1, 1, 'completed', 'medium', NULL, 1, 9, '2025-06-23 17:00:00', '', '', NULL, 1, 5, '2025-06-23 09:15:16', '2025-06-25 05:43:38', 'tasks/task_68591c2a3be13_163.jpg', '2025-06-25 12:43:38', NULL, 0, NULL),
(164, 'Kerjaan Topan', 'dsgdfgdfgf', 1, 5, 1, 1, 'completed', 'medium', NULL, 1, 6, '2025-06-23 17:00:00', '', '', NULL, 1, 5, '2025-06-23 09:24:48', '2025-06-25 05:43:33', 'tasks/task_68591d8af0202_164.jpg', '2025-06-25 12:43:33', NULL, 0, NULL),
(165, 'Nyoba task baru', 'dsdsagdsa', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-25 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-25 05:34:39', '2025-06-25 05:45:05', NULL, '2025-06-25 12:45:05', 'Garuda', 3, NULL),
(166, 'sdgdsfhdfh', 'dsfhdsfhsfd', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-25 15:00:00', NULL, NULL, NULL, 1, 5, '2025-06-25 07:22:42', '2025-06-26 05:25:30', NULL, '2025-06-26 12:25:30', 'Pertamina', 4, NULL),
(167, 'Podcast Jurnalisik #1', 'Narasumber 1', 2, 24, 19, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-26 13:30:00', '', '', NULL, 1, 5, '2025-06-26 05:51:27', '2025-06-26 06:35:05', 'tasks/task_685ce6ebb634f_167.png', '2025-06-26 13:35:05', NULL, 0, NULL),
(168, 'Tukpir 1', 'sdf', 2, 22, 3, 1, 'completed', 'medium', NULL, 2, 6, '2025-06-27 11:00:00', '', '', NULL, 1, 5, '2025-06-27 03:34:44', '2025-06-27 03:36:05', 'tasks/task_685e1189b6655_168.jpg', '2025-06-27 10:36:05', NULL, 0, NULL),
(169, 'Tukpir 2', 'Hahaha', 2, 22, 3, 1, 'completed', 'medium', NULL, 2, 6, '2025-06-27 11:00:00', '', '', NULL, 1, 5, '2025-06-27 03:37:09', '2025-06-27 03:37:48', 'tasks/task_685e11f29b931_169.jpg', '2025-06-27 10:37:48', NULL, 0, NULL),
(170, 'Proposal Baru', 'nyoba doang', 3, 17, 7, 1, 'waiting_confirmation', 'medium', NULL, 13, 13, '2025-06-30 20:30:00', NULL, NULL, NULL, 0, NULL, '2025-06-30 13:08:22', '2025-06-30 13:09:08', NULL, NULL, NULL, 0, NULL),
(171, 'sagasdg', 'asghdasha', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-30 20:30:00', NULL, NULL, NULL, 1, 5, '2025-06-30 13:13:44', '2025-07-03 06:44:19', NULL, '2025-07-03 13:44:19', 'Pop Mie', 4, NULL),
(172, 'sdags', 'adgasdg', 5, 29, 20, 1, 'waiting_confirmation', 'medium', NULL, 13, 18, '2025-06-30 20:30:00', NULL, NULL, NULL, 0, NULL, '2025-06-30 13:14:51', '2025-06-30 13:45:04', '1751289291_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Pop Mie', 0, NULL),
(173, 'asdgsdg', 'asdgadsg', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-30 21:00:00', NULL, NULL, NULL, 1, 5, '2025-06-30 13:18:17', '2025-07-03 06:44:27', NULL, '2025-07-03 13:44:27', 'Pop Mie', 4, NULL),
(174, 'Nyoba Notif WA 1', 'hahaha', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-06-30 21:00:00', NULL, NULL, NULL, 1, 5, '2025-06-30 13:23:44', '2025-07-03 06:44:35', NULL, '2025-07-03 13:44:35', 'Pop Mie', 4, NULL),
(175, 'Nyoba Publikasi WA 1', 'asgasdg', 5, 29, 20, 1, 'waiting_confirmation', 'medium', NULL, 13, 18, '2025-06-30 21:00:00', NULL, NULL, NULL, 0, NULL, '2025-06-30 13:27:28', '2025-06-30 13:41:40', '1751290048_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Pop Mie', 0, NULL),
(176, 'Putusan MK soal Pemilu Timbulkan 2 Masalah Besar', 'Ketua Komisi II DPR RI, M Rifqinizamy Karsayuda menyebut pihaknya menemukan dua persoalan yuridis atau hukum yang sangat serius dari putusan MK soal pemisahan jadwal antara pemilu nasional dan lokal. Meski begitu, Komisi II masih akan melakukan kajian soal tindak lanjut putusan tersebut.\r\n\r\nRifqi mengatakan, persoalan hukum pertama yakni putusan ini mendahului pembentuk Undang-Undang Dasar (UUD). Yang menyebutkan bahwa gubernur, bupati, wali kota masing-masing sebagai kepala pemerintahan provinsi, kabupaten, kota dipilih secara demokratis. Artinya, bisa langsung atau tidak langsung.\r\n\r\n\"Tapi kemudian MK dalam tanda kutip menyimpulkan bahwa harus dilakukan pemilu yang itu artinya dipilih secara langsung, itu satu. Jadi ini bukan soal kita mau atau nggak mau, ini kan soal bagaimana kita bernegara tetap dalam koridor negara hukum,\" kata Rifqi di Kompleks Parlemen Senayan, Jakarta Pusat, Senin (30/6/2025).', 1, 1, 2, 1, 'completed', 'medium', NULL, 2, 9, '2025-06-30 21:30:00', '', '', NULL, 1, 5, '2025-06-30 13:47:02', '2025-06-30 13:48:10', 'tasks/task_6862957b5b273_176.png', '2025-06-30 20:48:10', NULL, 0, NULL),
(177, 'Diresmikan Prabowo, Wisma Danantara Jadi Rumah Besar Pengelolaan Investasi', 'Ketua Komisi II DPR RI, M Rifqinizamy Karsayuda menyebut pihaknya menemukan dua persoalan yuridis atau hukum yang sangat serius dari putusan MK soal pemisahan jadwal antara pemilu nasional dan lokal. Meski begitu, Komisi II masih akan melakukan kajian soal tindak lanjut putusan tersebut.\r\n\r\nRifqi mengatakan, persoalan hukum pertama yakni putusan ini mendahului pembentuk Undang-Undang Dasar (UUD). Yang menyebutkan bahwa gubernur, bupati, wali kota masing-masing sebagai kepala pemerintahan provinsi, kabupaten, kota dipilih secara demokratis. Artinya, bisa langsung atau tidak langsung.\r\n\r\n\"Tapi kemudian MK dalam tanda kutip menyimpulkan bahwa harus dilakukan pemilu yang itu artinya dipilih secara langsung, itu satu. Jadi ini bukan soal kita mau atau nggak mau, ini kan soal bagaimana kita bernegara tetap dalam koridor negara hukum,\" kata Rifqi di Kompleks Parlemen Senayan, Jakarta Pusat, Senin (30/6/2025).', 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-06-30 21:30:00', NULL, NULL, NULL, 0, NULL, '2025-06-30 13:48:41', '2025-06-30 13:48:41', NULL, NULL, NULL, 0, NULL),
(178, 'Diresmikan Prabowo, Wisma Danantara Jadi Rumah Besar Pengelolaan Investasi', 'hhh', 1, 1, 1, 1, 'ready_for_review', 'medium', NULL, 2, 9, '2025-06-30 21:30:00', '', '', NULL, 0, NULL, '2025-06-30 13:52:44', '2025-07-02 14:49:58', '[\"tasks\\/task_68654716bb562_178_0.png\",\"tasks\\/task_68654716bb797_178_1.png\",\"tasks\\/task_68654716bb94a_178_2.png\",\"tasks\\/task_68654716bbaea_178_3.pdf\",\"tasks\\/task_68654716bbcb9_178_4.png\"]', NULL, NULL, 0, NULL),
(179, '10 Pemilik Klub Liga 1, Deretan Sultan Sepak Bola yang Tajir Banget!', 'aaa', 1, 2, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-06-30 21:30:00', NULL, NULL, NULL, 0, NULL, '2025-06-30 13:54:14', '2025-06-30 13:54:14', NULL, NULL, NULL, 0, NULL),
(180, 'Semen Padang FC Resmi Datangkan Pemain Timnas Indonesia, Ronaldo Kwateh', 'aaaa', 1, 1, 1, 1, 'ready_for_review', 'medium', NULL, 2, 9, '2025-06-30 21:30:00', '', '', NULL, 0, NULL, '2025-06-30 13:57:23', '2025-07-02 12:37:36', 'tasks/task_6865281077a9a_180_1.png', NULL, NULL, 0, NULL),
(181, 'Mbak Ita Minta Hadiah Lomba Nasi Goreng Pribadinya Dibiayai dari Kas Pegawai Pemkot Semarang', 'hahaha', 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-06-30 21:30:00', NULL, NULL, NULL, 0, NULL, '2025-06-30 14:05:06', '2025-06-30 14:05:06', NULL, NULL, NULL, 0, NULL),
(182, 'Kapolri Mutasi 26 Jenderal Bintang Satu, Ini Daftarnya', 'dd', 1, 1, 1, 1, 'ready_for_review', 'medium', NULL, 2, 9, '2025-06-30 21:30:00', '', '', NULL, 0, NULL, '2025-06-30 14:09:46', '2025-06-30 14:11:42', 'tasks/task_68629b1eecdef_182.png', NULL, NULL, 0, NULL),
(183, 'Nyoba task baru selasa', 'halo gais', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-07-01 14:30:00', NULL, NULL, NULL, 1, 5, '2025-07-01 07:03:44', '2025-07-03 06:44:42', NULL, '2025-07-03 13:44:42', 'Garuda', 4, NULL),
(184, 'Nyoba lagi sekarang jadi gak bisa huhu sedih', 'dsaasdg', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-07-01 14:30:00', NULL, NULL, NULL, 1, 5, '2025-07-01 07:11:52', '2025-07-03 06:44:48', NULL, '2025-07-03 13:44:48', 'Garuda', 4, NULL),
(185, 'Nyoba ke redaksi deh', 'sadgasg', 5, 29, 20, 1, 'waiting_redaktur_confirmation', 'medium', NULL, 13, 13, '2025-07-01 14:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 07:12:32', '2025-07-01 07:12:32', '1751353952_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Garuda', 0, NULL),
(186, 'nyoba lagi deh', 'dsagsad', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-07-01 14:30:00', NULL, NULL, NULL, 1, 5, '2025-07-01 07:14:44', '2025-07-03 06:44:54', NULL, '2025-07-03 13:44:54', 'Garuda', 4, NULL),
(187, 'nyoba lagi terakhir', 'dagsgag', 5, 29, 20, 1, 'waiting_redaktur_confirmation', 'medium', NULL, 13, 13, '2025-07-01 15:00:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 07:15:36', '2025-07-01 07:15:36', '1751354136_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Garuda', 0, NULL),
(188, 'asdgasg', 'asgasggas', 5, 29, 20, 1, 'waiting_redaktur_confirmation', 'medium', NULL, 13, 13, '2025-07-01 15:00:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 07:16:21', '2025-07-01 07:16:21', '1751354181_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Garuda', 0, NULL),
(189, 'Nyoba bang selasa', 'sadgg', 5, 29, 20, 1, 'waiting_redaktur_confirmation', 'medium', NULL, 13, 13, '2025-07-01 15:00:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 07:57:35', '2025-07-01 07:57:35', '1751356655_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Garuda', 0, NULL),
(190, 'Prabowo Anugerahkan Tanda Kehormatan kepada Jajaran Polri pada HUT ke-79 Bhayangkara', 'aaa', 4, 20, 13, 1, 'waiting_head_confirmation', 'medium', NULL, 13, 13, '2025-07-01 15:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 08:05:46', '2025-07-01 08:05:46', NULL, NULL, 'Garuda', 0, NULL),
(191, 'MUI Dukung Safari Dakwah Zakir Naik 2025, Erick Yusuf: Banyak yang Tercerahkan Lewat Perbandingan Agama', 'hahaha hihihi', 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 16:00:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 08:32:40', '2025-07-01 08:32:40', NULL, NULL, NULL, 0, NULL),
(192, 'Prabowo Anugerahkan Tanda Kehormatan kepada Jajaran Polri pada HUT ke-79 Bhayangkara', 'aaaa', 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 16:00:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 08:39:36', '2025-07-01 08:39:36', NULL, NULL, NULL, 0, NULL),
(193, 'Suap Proyek Jalan Sumut, KPK Telisik Rumah Mewah Diduga Milik Orang Dekat Bobby Nasution', 'aaaa', 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 16:00:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 08:43:45', '2025-07-01 08:43:45', NULL, NULL, NULL, 0, NULL),
(194, '*Polri Berhasil Dorong* 8.315 Eks Anggota JI Ikrar Setia ke NKRI', 'aadasf', 1, 5, 2, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 16:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 08:52:48', '2025-07-01 08:52:48', NULL, NULL, NULL, 0, NULL),
(195, '*Dari TikTok ke Brain Rot, *Otak Remaja \'Emas\' Indonesia* Kian Tumpul*', 'hahaha', 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 16:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 08:58:12', '2025-07-01 08:58:12', NULL, NULL, NULL, 0, NULL),
(196, 'Ternyata Segini *Gaji Pemain Asing di Liga 1*, Jomplang dengan Lokal?', 'hahaha', 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 16:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 09:00:01', '2025-07-01 09:00:01', NULL, NULL, NULL, 0, NULL),
(197, 'Polda Sumut Buru Dua Orang *Pengendali Pabrik Liquid Narkoba* di Medan', NULL, 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 16:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 09:17:16', '2025-07-01 09:17:16', NULL, NULL, NULL, 0, 'https://www.inilah.com/polda-sumut-buru-dua-orang-pengendali-pabrik-liquid-narkoba-di-medan');
INSERT INTO `tasks` (`id`, `title`, `description`, `category_id`, `content_type_id`, `content_pillar_id`, `account_id`, `status`, `priority`, `points`, `created_by`, `assigned_to`, `deadline`, `uploaded_link`, `notes`, `rejection_reason`, `is_verified`, `rating`, `created_at`, `updated_at`, `file_path`, `verified_at`, `client_name`, `platform_count`, `source_link`) VALUES
(198, 'Kejagung Geledah Kantor *Sritex dan Anak Perusahaannya* di Jawa Tengah', NULL, 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 17:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 09:45:56', '2025-07-01 09:45:56', NULL, NULL, NULL, 0, 'https://www.inilah.com/kejagung-geledah-kantor-sritex-dan-anak-perusahaannya-di-jawa-tengah'),
(199, 'Kejagung *Sita Uang Rp2 Miliar* dari Rumah Bos Sritex Iwan Kurniawan', NULL, 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 17:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 09:51:53', '2025-07-01 09:51:53', NULL, NULL, NULL, 0, 'https://www.inilah.com/kejagung-sita-uang-rp2-miliar-dari-rumah-bos-sritex-iwan-kurniawan'),
(200, 'Wimbledon 2025: *Petenis Unggulan Berguguran*, Medvedev dan Rune Jadi Korban', NULL, 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-01 17:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 09:54:16', '2025-07-01 09:54:16', NULL, NULL, NULL, 0, 'https://www.inilah.com/wimbledon-2025-petenis-unggulan-berguguran-medvedev-dan-rune-jadi-korban'),
(201, 'Polri Berhasil Dorong 8.315 Eks Anggota JI Ikrar Setia ke NKRI', 'sghh', 5, 29, 20, 1, 'waiting_confirmation', 'medium', NULL, 13, 18, '2025-07-01 17:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 09:57:53', '2025-07-01 10:49:23', '1751363873_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Garuda', 0, NULL),
(202, 'Alcaraz Puji Fabio Fognini di Wimbledon Terakhirnya', 'egasd', 5, 29, 20, 1, 'waiting_redaktur_confirmation', 'medium', NULL, 13, 13, '2025-07-01 18:00:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 10:34:26', '2025-07-01 10:34:26', '1751366066_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Garuda', 0, NULL),
(203, 'Belum Ambil Sikap, Gerindra Klaim Masih Kaji Putusan MK soal Pemilu', 'aaaa', 5, 29, 20, 1, 'waiting_redaktur_confirmation', 'medium', NULL, 13, 13, '2025-07-01 18:00:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 10:40:32', '2025-07-01 10:40:32', '1751366432_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Garuda', 0, NULL),
(204, '6 Tempat Makan Halal Favorit di Tokyo, Cocok untuk Traveler Muslim!', 'Menjelajahi kuliner Tokyo di Jepang kini semakin mudah dan menyenangkan bagi wisatawan Muslim, berkat semakin banyaknya restoran yang menawarkan menu halal. \r\n\r\nTak hanya menyajikan cita rasa autentik Jepang, deretan restoran ini juga menjamin kehalalan bahan dan proses memasaknya. Berikut 6 rekomendasi tempat makan halal-friendly di Tokyo yang patut Anda coba saat berkunjung ke Negeri Sakura. Mengutip laman Arab News, Selasa (1/7) berikut adalah daftarnya:\r\n\r\n1. Ayam-ya Halal Ramen – Ramen Halal dengan Rasa Autentik Jepang\r\nTerletak di kawasan Okachimachi, antara Asakusa dan Ueno, Ayam-ya Halal Ramen menawarkan semangkuk ramen yang nikmat dan menenangkan. \r\n\r\nDengan suasana sederhana namun nyaman, restoran ini menjadi tempat ideal bagi Anda yang ingin merasakan ramen khas Jepang tanpa khawatir soal kehalalan. Kuah kaldunya gurih dan mie-nya kenyal—kombinasi sempurna yang memuaskan.\r\n\r\n2. Ramen Honolu Shibuya – Ramen Ayam dan Camilan Ringan Halal\r\nBagi Anda yang sedang berkeliling di area trendi Shibuya, sempatkan mampir ke Ramen Honolu.', 5, 29, 20, 1, 'waiting_redaktur_confirmation', 'medium', NULL, 13, 13, '2025-07-01 18:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-01 10:47:32', '2025-07-01 10:47:32', '1751366852_1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png', NULL, 'Garuda', 0, NULL),
(205, 'Dari TikTok ke Brain Rot, Otak Remaja \'Emas\' Indonesia Kian Tumpul', 'tolong diposting facebook ya dan twitter:\r\n\r\nhttps://www.inilah.com/dari-tiktok-ke-brainrot-otak-remaja-emas-indonesia-kian-tumpul', 4, 20, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-07-01 19:30:00', NULL, NULL, NULL, 1, 5, '2025-07-01 10:52:03', '2025-07-01 10:54:32', NULL, '2025-07-01 17:54:32', 'Garuda', 1, NULL),
(206, 'zsadgag', 'sdagasgad', 1, 2, 2, 1, 'ready_for_review', 'medium', NULL, 2, 9, '2025-07-02 09:30:00', '', '', NULL, 0, NULL, '2025-07-01 16:04:47', '2025-07-02 14:26:01', '[\"tasks\\/task_68654179db963_206_0.png\",\"tasks\\/task_68654179dbc68_206_1.png\",\"tasks\\/task_68654179dbf13_206_2.png\"]', NULL, NULL, 0, 'https://www.inilah.com'),
(207, 'adhfsf', 'hdsfhsdh', 4, 18, 13, 1, 'waiting_head_confirmation', 'medium', NULL, 13, 13, '2025-07-02 12:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-02 04:54:58', '2025-07-02 04:54:58', NULL, NULL, 'Pertamina', 0, NULL),
(208, 'sadgasg', 'sgsa', 4, 18, 13, 1, 'draft', 'medium', NULL, 13, 13, '2025-07-02 20:42:00', NULL, NULL, NULL, 0, NULL, '2025-07-02 11:42:16', '2025-07-02 11:42:16', NULL, NULL, 'Pop Mie', 0, NULL),
(209, 'sdgsad', 'hafhah', 4, 18, 13, 1, 'completed', 'medium', NULL, 13, 2, '2025-07-02 19:30:00', NULL, NULL, NULL, 1, 5, '2025-07-02 11:53:10', '2025-07-03 06:44:59', '[{\"original_name\":\"1751099462_KEMENTERIAN [ISI NAMA KEMENTERIAN] RI - IMAGE POWER COMMUNICATION.png\",\"file_path\":\"tasks\\/68651d90e8c26_1751457168.png\",\"file_size\":61848,\"file_type\":\"image\\/png\"},{\"original_name\":\"COMPANY PROFILE - INILAH MEDIA NETWORK (20', '2025-07-03 13:44:59', 'Pop Mie', 4, NULL),
(210, 'dasgasg', 'asdgas', 3, 13, 7, 1, 'completed', 'medium', NULL, 13, 9, '2025-07-02 20:00:00', '', '', NULL, 1, 5, '2025-07-02 12:21:07', '2025-07-02 14:38:34', '[\"tasks\\/task_6865299f77ba1_210_0.png\",\"tasks\\/task_6865299f77e0b_210_1.png\",\"tasks\\/task_6865299f7803e_210_2.pdf\"]', '2025-07-02 21:38:34', NULL, 0, NULL),
(211, 'Nyoba lagi', 'saddsg', 3, 16, 7, 1, 'completed', 'medium', NULL, 13, 9, '2025-07-02 21:00:00', '', '', NULL, 1, 5, '2025-07-02 13:43:28', '2025-07-03 04:56:26', '[\"tasks\\/task_6865379fbde0c_211_0.png\",\"tasks\\/task_6865379fbe0bb_211_1.jpg\",\"tasks\\/task_6865379fbe2f9_211_2.jpg\"]', '2025-07-03 11:56:26', NULL, 0, NULL),
(212, 'dzgsdg', 'asdgsadga', 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-03 15:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-03 07:54:46', '2025-07-03 07:54:46', NULL, NULL, NULL, 0, 'https://www.inilah.com/polda-sumut-buru-dua-orang-pengendali-pabrik-liquid-narkoba-di-medan'),
(213, 'dsgsasg', 'sagsdag', 1, 1, 1, 1, 'waiting_confirmation', 'medium', NULL, 2, 9, '2025-07-03 15:30:00', NULL, NULL, NULL, 0, NULL, '2025-07-03 08:03:23', '2025-07-03 08:03:23', NULL, NULL, NULL, 0, 'https://www.inilah.com/polda-sumut-buru-dua-orang-pengendali-pabrik-liquid-narkoba-di-medan'),
(214, 'sdagsad', NULL, 1, 1, 2, 1, 'uploaded', 'medium', NULL, 2, 9, '2025-07-03 17:30:00', '', '', NULL, 0, NULL, '2025-07-03 09:48:29', '2025-07-04 06:37:02', '[\"tasks\\/task_68667faca6743_214_0.jpg\",\"tasks\\/task_68667faca69b1_214_1.png\",\"tasks\\/task_68667faca6bb0_214_2.png\"]', NULL, NULL, 0, 'https://www.inilah.com/polda-sumut-buru-dua-orang-pengendali-pabrik-liquid-narkoba-di-medan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `task_assistance`
--

CREATE TABLE `task_assistance` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `task_assistance`
--

INSERT INTO `task_assistance` (`id`, `task_id`, `user_id`, `added_by`, `note`, `created_at`) VALUES
(1, 167, 6, 9, 'buat bantuin syuting', '2025-06-26 06:20:59'),
(2, 168, 11, 6, 'bantu syuting', '2025-06-27 03:35:19'),
(3, 182, 6, 9, 'desain cover', '2025-06-30 14:11:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `task_links`
--

CREATE TABLE `task_links` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `link` text NOT NULL,
  `added_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `task_links`
--

INSERT INTO `task_links` (`id`, `task_id`, `platform`, `link`, `added_by`, `created_at`) VALUES
(1, 22, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-05 05:48:21'),
(2, 22, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-05 05:48:21'),
(3, 22, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-05 05:48:21'),
(4, 22, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-05 05:48:21'),
(5, 22, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-05 05:48:21'),
(6, 29, 'instagram', 'https://instagram.com/', 2, '2025-06-08 15:29:15'),
(7, 28, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 05:35:35'),
(10, 36, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 06:13:11'),
(11, 38, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 06:13:24'),
(12, 38, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 06:13:24'),
(13, 39, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 06:17:32'),
(14, 39, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 06:17:32'),
(15, 39, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 06:17:32'),
(16, 39, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 06:17:32'),
(17, 39, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 06:17:32'),
(23, 40, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 07:04:18'),
(25, 35, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0', 2, '2025-06-09 07:46:15'),
(26, 35, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0', 2, '2025-06-09 07:46:15'),
(27, 35, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0', 2, '2025-06-09 07:46:15'),
(28, 35, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0', 2, '2025-06-09 07:46:15'),
(29, 34, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 07:46:34'),
(30, 34, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-09 07:46:34'),
(31, 42, 'instagram', 'https://instagram.com/', 2, '2025-06-09 11:48:11'),
(32, 42, 'tiktok', 'https://instagram.com/', 2, '2025-06-09 11:48:11'),
(33, 45, 'instagram', 'https://instagram.com/', 2, '2025-06-09 14:08:07'),
(34, 44, 'instagram', 'https://instagram.com/', 2, '2025-06-09 14:10:48'),
(35, 44, 'tiktok', 'https://instagram.com/', 2, '2025-06-09 14:10:48'),
(36, 46, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-10 01:44:13'),
(37, 46, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-10 01:44:13'),
(38, 46, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-10 01:44:13'),
(39, 46, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-10 01:44:13'),
(40, 49, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-10 09:11:16'),
(41, 49, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-10 09:11:16'),
(42, 49, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0', 2, '2025-06-10 09:11:16'),
(45, 50, 'instagram', 'https://instagram.com/', 2, '2025-06-10 15:02:51'),
(46, 50, 'tiktok', 'https://instagram.com/', 2, '2025-06-10 15:02:51'),
(47, 53, 'instagram', 'https://instagram.com/', 2, '2025-06-10 15:34:14'),
(48, 52, 'instagram', 'https://instagram.com/', 2, '2025-06-10 15:34:23'),
(49, 51, 'instagram', 'https://instagram.com/', 2, '2025-06-10 15:34:39'),
(50, 51, 'tiktok', 'https://instagram.com/', 2, '2025-06-10 15:34:39'),
(51, 57, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 06:37:12'),
(52, 57, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 06:37:12'),
(53, 57, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 06:37:12'),
(54, 57, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 06:37:12'),
(55, 57, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 06:37:12'),
(56, 58, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 14:46:17'),
(57, 58, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 14:46:17'),
(58, 59, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:07:05'),
(59, 59, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:07:05'),
(60, 59, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:07:05'),
(61, 59, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:07:05'),
(62, 59, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:07:05'),
(63, 60, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:07:19'),
(64, 60, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:07:19'),
(65, 60, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:07:19'),
(66, 60, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0', 2, '2025-06-11 15:07:19'),
(67, 61, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:19:14'),
(68, 61, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:19:14'),
(69, 61, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:19:14'),
(70, 62, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:38:47'),
(71, 62, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:38:47'),
(72, 62, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:38:47'),
(73, 62, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-11 15:38:47'),
(78, 63, 'instagram', 'https://instagram.com', 2, '2025-06-12 06:25:02'),
(79, 63, 'tiktok', 'https://instagram.com', 2, '2025-06-12 06:25:02'),
(80, 63, 'facebook', 'https://instagram.com', 2, '2025-06-12 06:25:02'),
(81, 63, 'twitter', 'https://instagram.com', 2, '2025-06-12 06:25:02'),
(82, 64, 'instagram', 'https://instagram.com', 2, '2025-06-12 06:28:26'),
(83, 64, 'tiktok', 'https://instagram.com', 2, '2025-06-12 06:28:26'),
(84, 64, 'facebook', 'https://instagram.com', 2, '2025-06-12 06:28:26'),
(85, 66, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 10:59:58'),
(86, 66, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 10:59:58'),
(87, 66, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 10:59:58'),
(88, 68, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 11:10:37'),
(89, 68, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 11:10:37'),
(90, 69, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 11:17:35'),
(91, 69, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 11:17:35'),
(92, 69, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 11:17:35'),
(93, 70, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 12:53:21'),
(94, 70, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 12:53:21'),
(95, 71, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 13:11:13'),
(96, 71, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 13:11:13'),
(97, 72, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 13:15:13'),
(98, 72, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 13:15:13'),
(99, 73, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 13:16:07'),
(100, 73, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 13:16:07'),
(101, 75, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 13:20:12'),
(102, 75, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 13:20:12'),
(103, 74, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 13:20:23'),
(104, 74, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 13:20:23'),
(105, 76, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 13:23:02'),
(106, 76, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 13:23:02'),
(107, 77, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 13:25:13'),
(108, 77, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 13:25:13'),
(109, 77, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 13:25:13'),
(110, 78, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 13:57:39'),
(111, 78, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 13:57:39'),
(112, 78, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 13:57:39'),
(113, 79, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 15:51:48'),
(114, 79, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 15:51:48'),
(115, 79, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 15:51:48'),
(116, 80, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 15:53:10'),
(117, 80, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 15:53:10'),
(118, 80, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 15:53:10'),
(119, 81, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 15:54:41'),
(120, 81, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 15:54:41'),
(121, 81, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 15:54:41'),
(122, 82, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 16:06:35'),
(123, 82, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 16:06:35'),
(124, 82, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 16:06:35'),
(125, 83, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 16:07:47'),
(126, 83, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 16:07:47'),
(127, 83, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 16:07:47'),
(128, 83, 'twitter', 'https://twitter.com', 2, '2025-06-12 16:07:47'),
(129, 84, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 16:09:06'),
(130, 84, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 16:09:06'),
(131, 84, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 16:09:06'),
(132, 85, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 16:10:45'),
(133, 85, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 16:10:45'),
(134, 85, 'facebook', 'https://www.instagram.com/', 2, '2025-06-12 16:10:45'),
(135, 86, 'instagram', 'https://www.instagram.com/', 2, '2025-06-12 16:11:52'),
(136, 86, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-12 16:11:52'),
(137, 87, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-15 04:34:58'),
(138, 87, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-15 04:34:58'),
(139, 87, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-15 04:34:58'),
(140, 43, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-15 08:52:25'),
(141, 43, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-15 08:52:25'),
(142, 88, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:31:52'),
(143, 88, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:31:52'),
(144, 88, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:31:52'),
(145, 88, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:31:52'),
(146, 88, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:31:52'),
(147, 89, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:33:22'),
(148, 89, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:33:22'),
(149, 89, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:33:22'),
(150, 89, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:33:22'),
(151, 89, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-16 00:33:22'),
(152, 90, 'instagram', 'https://instagram.com/', 5, '2025-06-16 05:12:22'),
(153, 90, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 05:12:22'),
(154, 91, 'instagram', 'https://instagram.com/', 5, '2025-06-16 05:13:34'),
(155, 91, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 05:13:34'),
(156, 91, 'facebook', 'https://instagram.com/', 5, '2025-06-16 05:13:34'),
(157, 91, 'twitter', 'https://instagram.com/', 5, '2025-06-16 05:13:34'),
(158, 91, 'threads', 'https://instagram.com/', 5, '2025-06-16 05:13:34'),
(159, 92, 'instagram', 'https://instagram.com/', 5, '2025-06-16 05:39:39'),
(160, 92, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 05:39:39'),
(161, 92, 'facebook', 'https://instagram.com/', 5, '2025-06-16 05:39:39'),
(162, 92, 'twitter', 'https://instagram.com/', 5, '2025-06-16 05:39:39'),
(163, 92, 'threads', 'https://instagram.com/', 5, '2025-06-16 05:39:39'),
(164, 93, 'instagram', 'https://instagram.com/', 5, '2025-06-16 05:40:53'),
(165, 93, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 05:40:53'),
(166, 93, 'facebook', 'https://instagram.com/', 5, '2025-06-16 05:40:53'),
(167, 93, 'twitter', 'https://instagram.com/', 5, '2025-06-16 05:40:53'),
(168, 93, 'threads', 'https://instagram.com/', 5, '2025-06-16 05:40:53'),
(169, 94, 'instagram', 'https://instagram.com/', 5, '2025-06-16 05:50:23'),
(170, 94, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 05:50:23'),
(171, 94, 'facebook', 'https://instagram.com/', 5, '2025-06-16 05:50:23'),
(172, 94, 'twitter', 'https://instagram.com/', 5, '2025-06-16 05:50:23'),
(173, 94, 'threads', 'https://instagram.com/', 5, '2025-06-16 05:50:23'),
(174, 95, 'instagram', 'https://instagram.com/', 5, '2025-06-16 05:54:01'),
(175, 95, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 05:54:01'),
(176, 95, 'facebook', 'https://instagram.com/', 5, '2025-06-16 05:54:01'),
(177, 95, 'twitter', 'https://instagram.com/', 5, '2025-06-16 05:54:01'),
(178, 95, 'threads', 'https://instagram.com/', 5, '2025-06-16 05:54:01'),
(179, 96, 'instagram', 'https://instagram.com/', 5, '2025-06-16 05:55:21'),
(180, 96, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 05:55:21'),
(181, 96, 'facebook', 'https://instagram.com/', 5, '2025-06-16 05:55:21'),
(182, 96, 'twitter', 'https://instagram.com/', 5, '2025-06-16 05:55:21'),
(183, 96, 'threads', 'https://instagram.com/', 5, '2025-06-16 05:55:21'),
(184, 97, 'instagram', 'https://instagram.com/', 5, '2025-06-16 05:57:00'),
(185, 97, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 05:57:00'),
(186, 97, 'facebook', 'https://instagram.com/', 5, '2025-06-16 05:57:00'),
(187, 97, 'twitter', 'https://instagram.com/', 5, '2025-06-16 05:57:00'),
(188, 97, 'threads', 'https://instagram.com/', 5, '2025-06-16 05:57:00'),
(189, 98, 'instagram', 'https://instagram.com/', 5, '2025-06-16 06:06:46'),
(190, 98, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 06:06:46'),
(191, 98, 'facebook', 'https://instagram.com/', 5, '2025-06-16 06:06:46'),
(192, 98, 'twitter', 'https://instagram.com/', 5, '2025-06-16 06:06:46'),
(193, 98, 'threads', 'https://instagram.com/', 5, '2025-06-16 06:06:46'),
(194, 99, 'instagram', 'https://instagram.com/', 5, '2025-06-16 06:08:30'),
(195, 99, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 06:08:30'),
(196, 99, 'facebook', 'https://instagram.com/', 5, '2025-06-16 06:08:30'),
(197, 99, 'twitter', 'https://instagram.com/', 5, '2025-06-16 06:08:30'),
(198, 99, 'threads', 'https://instagram.com/', 5, '2025-06-16 06:08:30'),
(199, 100, 'instagram', 'https://instagram.com/', 5, '2025-06-16 06:09:29'),
(200, 100, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 06:09:29'),
(201, 100, 'facebook', 'https://instagram.com/', 5, '2025-06-16 06:09:29'),
(202, 100, 'twitter', 'https://instagram.com/', 5, '2025-06-16 06:09:29'),
(203, 100, 'threads', 'https://instagram.com/', 5, '2025-06-16 06:09:29'),
(204, 101, 'instagram', 'https://instagram.com/', 5, '2025-06-16 06:12:09'),
(205, 101, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 06:12:09'),
(206, 101, 'facebook', 'https://instagram.com/', 5, '2025-06-16 06:12:09'),
(207, 101, 'twitter', 'https://instagram.com/', 5, '2025-06-16 06:12:09'),
(208, 101, 'threads', 'https://instagram.com/', 5, '2025-06-16 06:12:09'),
(209, 102, 'instagram', 'https://instagram.com/', 5, '2025-06-16 06:15:10'),
(210, 102, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 06:15:10'),
(211, 102, 'facebook', 'https://instagram.com/', 5, '2025-06-16 06:15:10'),
(212, 102, 'twitter', 'https://instagram.com/', 5, '2025-06-16 06:15:10'),
(213, 102, 'threads', 'https://instagram.com/', 5, '2025-06-16 06:15:10'),
(214, 103, 'instagram', 'https://instagram.com/', 5, '2025-06-16 06:16:42'),
(215, 103, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 06:16:42'),
(216, 103, 'facebook', 'https://instagram.com/', 5, '2025-06-16 06:16:42'),
(217, 103, 'twitter', 'https://instagram.com/', 5, '2025-06-16 06:16:42'),
(218, 103, 'threads', 'https://instagram.com/', 5, '2025-06-16 06:16:42'),
(219, 104, 'instagram', 'https://instagram.com/', 5, '2025-06-16 06:20:31'),
(220, 104, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 06:20:31'),
(221, 104, 'facebook', 'https://instagram.com/', 5, '2025-06-16 06:20:31'),
(222, 104, 'twitter', 'https://instagram.com/', 5, '2025-06-16 06:20:31'),
(223, 104, 'threads', 'https://instagram.com/', 5, '2025-06-16 06:20:31'),
(224, 105, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:03:03'),
(225, 105, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:03:03'),
(226, 105, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:03:03'),
(227, 105, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:03:03'),
(228, 105, 'threads', 'https://instagram.com/', 5, '2025-06-16 07:03:03'),
(229, 106, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:04:52'),
(230, 106, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:04:52'),
(231, 106, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:04:52'),
(232, 106, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:04:52'),
(233, 106, 'threads', 'https://instagram.com/', 5, '2025-06-16 07:04:52'),
(234, 107, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:08:04'),
(235, 107, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:08:04'),
(236, 107, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:08:04'),
(237, 107, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:08:04'),
(238, 107, 'threads', 'https://instagram.com/', 5, '2025-06-16 07:08:04'),
(239, 108, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:08:50'),
(240, 108, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:08:50'),
(241, 108, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:08:50'),
(242, 108, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:08:50'),
(243, 109, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:09:57'),
(244, 109, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:09:57'),
(245, 109, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:09:57'),
(246, 110, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:18:45'),
(247, 110, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:18:45'),
(248, 110, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:18:45'),
(249, 110, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:18:45'),
(250, 111, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:27:03'),
(251, 111, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:27:03'),
(252, 111, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:27:03'),
(253, 111, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:27:03'),
(254, 111, 'threads', 'https://instagram.com/', 5, '2025-06-16 07:27:03'),
(255, 112, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:28:03'),
(256, 112, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:28:03'),
(257, 112, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:28:03'),
(258, 112, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:28:03'),
(259, 112, 'threads', 'https://instagram.com/', 5, '2025-06-16 07:28:03'),
(260, 113, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:28:58'),
(261, 113, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:28:58'),
(262, 113, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:28:58'),
(263, 113, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:28:58'),
(264, 113, 'threads', 'https://instagram.com/', 5, '2025-06-16 07:28:58'),
(265, 114, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:32:48'),
(266, 114, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:32:48'),
(267, 114, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:32:48'),
(268, 114, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:32:48'),
(269, 114, 'threads', 'https://instagram.com/', 5, '2025-06-16 07:32:48'),
(270, 115, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:37:43'),
(271, 115, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:37:43'),
(272, 115, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:37:43'),
(273, 115, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:37:43'),
(274, 115, 'threads', 'https://instagram.com/', 5, '2025-06-16 07:37:43'),
(275, 116, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:39:28'),
(276, 116, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:39:28'),
(277, 116, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:39:28'),
(278, 116, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:39:28'),
(279, 116, 'threads', 'https://instagram.com/', 5, '2025-06-16 07:39:28'),
(280, 117, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:40:19'),
(281, 117, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:40:19'),
(282, 117, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:40:19'),
(283, 117, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:40:19'),
(284, 118, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:41:12'),
(285, 118, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:41:12'),
(286, 118, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:41:12'),
(287, 119, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:46:15'),
(288, 119, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:46:15'),
(289, 119, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:46:15'),
(290, 120, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:48:39'),
(291, 121, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:49:32'),
(292, 121, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:49:32'),
(293, 122, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:50:43'),
(294, 122, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:50:43'),
(295, 122, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:50:43'),
(296, 122, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:50:43'),
(297, 123, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:52:05'),
(298, 123, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:52:05'),
(299, 123, 'facebook', 'https://instagram.com/', 5, '2025-06-16 07:52:05'),
(300, 123, 'twitter', 'https://instagram.com/', 5, '2025-06-16 07:52:05'),
(301, 124, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:52:57'),
(302, 124, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:52:57'),
(303, 125, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:53:55'),
(304, 125, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:53:55'),
(305, 126, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:55:07'),
(306, 126, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:55:07'),
(307, 127, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:58:07'),
(308, 127, 'tiktok', 'https://instagram.com/', 5, '2025-06-16 07:58:07'),
(309, 128, 'instagram', 'https://instagram.com/', 5, '2025-06-16 07:58:46'),
(310, 129, 'instagram', 'https://instagram.com/', 3, '2025-06-16 09:14:12'),
(311, 129, 'tiktok', 'https://instagram.com/', 3, '2025-06-16 09:14:12'),
(312, 129, 'facebook', 'https://instagram.com/', 3, '2025-06-16 09:14:12'),
(313, 129, 'twitter', 'https://instagram.com/', 3, '2025-06-16 09:14:12'),
(314, 129, 'threads', 'https://instagram.com/', 3, '2025-06-16 09:14:12'),
(315, 130, 'instagram', 'https://instagram.com/', 5, '2025-06-17 05:32:37'),
(316, 130, 'tiktok', 'https://instagram.com/', 5, '2025-06-17 05:32:37'),
(317, 130, 'facebook', 'https://instagram.com/', 5, '2025-06-17 05:32:37'),
(318, 130, 'twitter', 'https://instagram.com/', 5, '2025-06-17 05:32:37'),
(319, 130, 'threads', 'https://instagram.com/', 5, '2025-06-17 05:32:37'),
(320, 134, 'instagram', 'https://instagram.com/', 2, '2025-06-18 11:54:43'),
(321, 134, 'tiktok', 'https://instagram.com/', 2, '2025-06-18 11:54:43'),
(322, 134, 'facebook', 'https://instagram.com/', 2, '2025-06-18 11:54:43'),
(323, 134, 'twitter', 'https://instagram.com/', 2, '2025-06-18 11:54:43'),
(324, 134, 'threads', 'https://instagram.com/', 2, '2025-06-18 11:54:43'),
(325, 137, 'instagram', 'https://instagram.com/', 2, '2025-06-18 11:54:55'),
(326, 137, 'tiktok', 'https://instagram.com/', 2, '2025-06-18 11:54:55'),
(327, 137, 'facebook', 'https://instagram.com/', 2, '2025-06-18 11:54:55'),
(328, 137, 'twitter', 'https://instagram.com/', 2, '2025-06-18 11:54:55'),
(329, 137, 'threads', 'https://instagram.com/', 2, '2025-06-18 11:54:55'),
(330, 138, 'instagram', 'https://instagram.com/', 2, '2025-06-18 11:55:08'),
(331, 138, 'tiktok', 'https://instagram.com/', 2, '2025-06-18 11:55:08'),
(332, 138, 'facebook', 'https://instagram.com/', 2, '2025-06-18 11:55:08'),
(333, 138, 'twitter', 'https://instagram.com/', 2, '2025-06-18 11:55:08'),
(334, 138, 'threads', 'https://instagram.com/', 2, '2025-06-18 11:55:08'),
(335, 139, 'instagram', 'https://instagram.com/', 2, '2025-06-18 12:23:25'),
(336, 139, 'tiktok', 'https://instagram.com/', 2, '2025-06-18 12:23:25'),
(337, 139, 'facebook', 'https://instagram.com/', 2, '2025-06-18 12:23:25'),
(338, 142, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:32:50'),
(339, 142, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:32:50'),
(340, 142, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:32:50'),
(341, 142, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:32:50'),
(342, 142, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:32:50'),
(343, 143, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:39:55'),
(344, 143, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:39:55'),
(345, 143, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:39:55'),
(346, 143, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:39:55'),
(347, 143, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 05:39:55'),
(348, 144, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:06:42'),
(349, 144, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:06:42'),
(350, 144, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:06:42'),
(351, 144, 'twitter', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:06:42'),
(352, 144, 'threads', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:06:42'),
(353, 145, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:14:14'),
(354, 146, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:15:46'),
(355, 147, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:17:08'),
(356, 148, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:18:12'),
(357, 148, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:18:12'),
(358, 149, 'instagram', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:22:12'),
(359, 149, 'tiktok', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:22:12'),
(360, 149, 'facebook', 'https://www.instagram.com/p/DKV6GhXyuR0/', 2, '2025-06-19 06:22:12'),
(361, 150, 'instagram', 'https://instagram.com/', 2, '2025-06-19 06:39:40'),
(362, 150, 'tiktok', 'https://instagram.com/', 2, '2025-06-19 06:39:40'),
(363, 150, 'facebook', 'https://instagram.com/', 2, '2025-06-19 06:39:40'),
(364, 153, 'instagram', 'https://instagram.com/', 2, '2025-06-19 09:16:50'),
(365, 153, 'tiktok', 'https://instagram.com/', 2, '2025-06-19 09:16:50'),
(370, 155, 'instagram', 'https://www.instagram.com/', 5, '2025-06-19 10:58:32'),
(371, 155, 'tiktok', 'https://www.instagram.com/', 5, '2025-06-19 10:58:32'),
(372, 155, 'facebook', 'https://www.instagram.com/', 5, '2025-06-19 10:58:32'),
(373, 155, 'twitter', 'https://twitter.com', 5, '2025-06-19 10:58:32'),
(374, 156, 'instagram', 'https://www.instagram.com/', 5, '2025-06-19 11:06:03'),
(375, 156, 'tiktok', 'https://www.instagram.com/', 5, '2025-06-19 11:06:03'),
(376, 156, 'facebook', 'https://www.instagram.com/', 5, '2025-06-19 11:06:03'),
(377, 156, 'twitter', 'https://twitter.com', 5, '2025-06-19 11:06:03'),
(378, 156, 'threads', 'https://www.threads.com', 5, '2025-06-19 11:06:03'),
(379, 156, 'instagram', 'https://www.instagram.com/', 5, '2025-06-19 13:05:55'),
(380, 156, 'tiktok', 'https://www.instagram.com/', 5, '2025-06-19 13:05:55'),
(381, 156, 'facebook', 'https://www.instagram.com/', 5, '2025-06-19 13:05:55'),
(382, 157, 'instagram', 'https://instagram.com/', 2, '2025-06-20 08:04:46'),
(383, 157, 'tiktok', 'https://instagram.com/', 2, '2025-06-20 08:04:46'),
(384, 157, 'facebook', 'https://instagram.com/', 2, '2025-06-20 08:04:46'),
(385, 157, 'twitter', 'https://instagram.com/', 2, '2025-06-20 08:04:46'),
(386, 157, 'threads', 'https://instagram.com/', 2, '2025-06-20 08:04:46'),
(387, 158, 'instagram', 'https://instagram.com/', 2, '2025-06-22 00:57:09'),
(388, 158, 'tiktok', 'https://instagram.com/', 2, '2025-06-22 00:57:09'),
(389, 158, 'facebook', 'https://instagram.com/', 2, '2025-06-22 00:57:09'),
(390, 158, 'twitter', 'https://instagram.com/', 2, '2025-06-22 00:57:09'),
(391, 158, 'threads', 'https://instagram.com/', 2, '2025-06-22 00:57:09'),
(392, 159, 'instagram', 'https://instagram.com/', 2, '2025-06-22 05:44:05'),
(393, 159, 'tiktok', 'https://instagram.com/', 2, '2025-06-22 05:44:05'),
(394, 159, 'facebook', 'https://instagram.com/', 2, '2025-06-22 05:44:05'),
(395, 159, 'twitter', 'https://instagram.com/', 2, '2025-06-22 05:44:05'),
(396, 159, 'threads', 'https://instagram.com/', 2, '2025-06-22 05:44:05'),
(397, 160, 'instagram', 'https://instagram.com/', 2, '2025-06-22 05:57:10'),
(398, 160, 'tiktok', 'https://instagram.com/', 2, '2025-06-22 05:57:10'),
(399, 160, 'facebook', 'https://instagram.com/', 2, '2025-06-22 05:57:10'),
(400, 160, 'twitter', 'https://instagram.com/', 2, '2025-06-22 05:57:10'),
(401, 160, 'threads', 'https://instagram.com/', 2, '2025-06-22 05:57:10'),
(402, 152, 'instagram', 'https://instagram.com/', 2, '2025-06-22 11:31:05'),
(403, 152, 'tiktok', 'https://instagram.com/', 2, '2025-06-22 11:31:05'),
(404, 151, 'instagram', 'https://instagram.com/', 2, '2025-06-22 11:31:49'),
(405, 151, 'tiktok', 'https://instagram.com/', 2, '2025-06-22 11:31:49'),
(406, 161, 'instagram', 'https://www.instagram.com/p/DLO8dB3x8ut/', 3, '2025-06-23 07:55:19'),
(407, 165, 'instagram', 'https://instagram.com', 2, '2025-06-25 05:44:45'),
(408, 165, 'tiktok', 'https://instagram.com', 2, '2025-06-25 05:44:45'),
(409, 165, 'facebook', 'https://instagram.com', 2, '2025-06-25 05:44:45'),
(410, 165, 'twitter', 'https://instagram.com', 2, '2025-06-25 05:44:45'),
(411, 166, 'instagram', 'https://instagram.com/', 2, '2025-06-26 05:25:17'),
(412, 166, 'tiktok', 'https://instagram.com/', 2, '2025-06-26 05:25:17'),
(413, 166, 'facebook', 'https://instagram.com/', 2, '2025-06-26 05:25:17'),
(414, 166, 'twitter', 'https://instagram.com/', 2, '2025-06-26 05:25:17'),
(415, 166, 'threads', 'https://instagram.com/', 2, '2025-06-26 05:25:17'),
(416, 167, 'instagram', 'https://instagram.com/', 2, '2025-06-26 06:30:40'),
(417, 167, 'tiktok', 'https://instagram.com/', 2, '2025-06-26 06:30:40'),
(418, 167, 'facebook', 'https://instagram.com/', 2, '2025-06-26 06:30:40'),
(419, 167, 'twitter', 'https://instagram.com/', 2, '2025-06-26 06:30:40'),
(420, 167, 'threads', 'https://instagram.com/', 2, '2025-06-26 06:30:40'),
(421, 168, 'instagram', 'https://instagram.com/', 2, '2025-06-27 03:35:56'),
(422, 168, 'tiktok', 'https://instagram.com/', 2, '2025-06-27 03:35:56'),
(423, 168, 'facebook', 'https://instagram.com/', 2, '2025-06-27 03:35:56'),
(424, 168, 'twitter', 'https://instagram.com/', 2, '2025-06-27 03:35:56'),
(425, 168, 'threads', 'https://instagram.com/', 2, '2025-06-27 03:35:56'),
(426, 169, 'instagram', 'https://instagram.com/', 2, '2025-06-27 03:37:42'),
(427, 169, 'tiktok', 'https://instagram.com/', 2, '2025-06-27 03:37:42'),
(428, 169, 'facebook', 'https://instagram.com/', 2, '2025-06-27 03:37:42'),
(429, 169, 'twitter', 'https://instagram.com/', 2, '2025-06-27 03:37:42'),
(430, 169, 'threads', 'https://instagram.com/', 2, '2025-06-27 03:37:42'),
(431, 176, 'instagram', 'https://www.instagram.com/', 2, '2025-06-30 13:48:03'),
(432, 176, 'tiktok', 'https://www.instagram.com/', 2, '2025-06-30 13:48:03'),
(433, 176, 'facebook', 'https://www.instagram.com/', 2, '2025-06-30 13:48:03'),
(434, 176, 'twitter', 'https://twitter.com', 2, '2025-06-30 13:48:03'),
(435, 176, 'threads', 'https://www.threads.com', 2, '2025-06-30 13:48:03'),
(436, 205, 'facebook', 'https://instagram.com/', 2, '2025-07-01 10:54:27'),
(437, 205, 'twitter', 'https://instagram.com/', 2, '2025-07-01 10:54:27'),
(438, 184, 'instagram', 'https://instagram.com/', 2, '2025-07-03 06:37:48'),
(439, 184, 'tiktok', 'https://instagram.com/', 2, '2025-07-03 06:37:48'),
(440, 184, 'facebook', 'https://instagram.com/', 2, '2025-07-03 06:37:48'),
(441, 184, 'twitter', 'https://instagram.com/', 2, '2025-07-03 06:37:48'),
(442, 184, 'threads', 'https://instagram.com/', 2, '2025-07-03 06:37:48'),
(443, 209, 'instagram', 'https://instagram.com/', 2, '2025-07-03 06:38:00'),
(444, 209, 'tiktok', 'https://instagram.com/', 2, '2025-07-03 06:38:00'),
(445, 209, 'facebook', 'https://instagram.com/', 2, '2025-07-03 06:38:00'),
(446, 209, 'twitter', 'https://instagram.com/', 2, '2025-07-03 06:38:00'),
(447, 209, 'threads', 'https://instagram.com/', 2, '2025-07-03 06:38:00'),
(448, 171, 'instagram', 'https://instagram.com/', 2, '2025-07-03 06:38:19'),
(449, 171, 'tiktok', 'https://instagram.com/', 2, '2025-07-03 06:38:19'),
(450, 171, 'facebook', 'https://instagram.com/', 2, '2025-07-03 06:38:19'),
(451, 171, 'twitter', 'https://instagram.com/', 2, '2025-07-03 06:38:19'),
(452, 171, 'threads', 'https://instagram.com/', 2, '2025-07-03 06:38:19'),
(453, 173, 'instagram', 'https://instagram.com/', 2, '2025-07-03 06:38:33'),
(454, 173, 'tiktok', 'https://instagram.com/', 2, '2025-07-03 06:38:33'),
(455, 173, 'facebook', 'https://instagram.com/', 2, '2025-07-03 06:38:33'),
(456, 173, 'twitter', 'https://instagram.com/', 2, '2025-07-03 06:38:33'),
(457, 173, 'threads', 'https://instagram.com/', 2, '2025-07-03 06:38:33'),
(458, 174, 'instagram', 'https://instagram.com/', 2, '2025-07-03 06:38:47'),
(459, 174, 'tiktok', 'https://instagram.com/', 2, '2025-07-03 06:38:47'),
(460, 174, 'facebook', 'https://instagram.com/', 2, '2025-07-03 06:38:47'),
(461, 174, 'twitter', 'https://instagram.com/', 2, '2025-07-03 06:38:47'),
(462, 174, 'threads', 'https://instagram.com/', 2, '2025-07-03 06:38:47'),
(463, 183, 'instagram', 'https://instagram.com/', 2, '2025-07-03 06:43:22'),
(464, 183, 'tiktok', 'https://instagram.com/', 2, '2025-07-03 06:43:22'),
(465, 183, 'facebook', 'https://instagram.com/', 2, '2025-07-03 06:43:22'),
(466, 183, 'twitter', 'https://instagram.com/', 2, '2025-07-03 06:43:22'),
(467, 183, 'threads', 'https://instagram.com/', 2, '2025-07-03 06:43:22'),
(468, 186, 'instagram', 'https://instagram.com/', 2, '2025-07-03 06:43:35'),
(469, 186, 'tiktok', 'https://instagram.com/', 2, '2025-07-03 06:43:35'),
(470, 186, 'facebook', 'https://instagram.com/', 2, '2025-07-03 06:43:35'),
(471, 186, 'twitter', 'https://instagram.com/', 2, '2025-07-03 06:43:35'),
(472, 186, 'threads', 'https://instagram.com/', 2, '2025-07-03 06:43:35'),
(473, 214, 'instagram', 'https://www.instagram.com', 2, '2025-07-04 06:37:02'),
(474, 214, 'tiktok', 'https://www.instagram.com', 2, '2025-07-04 06:37:02'),
(475, 214, 'facebook', 'https://www.instagram.com', 2, '2025-07-04 06:37:02'),
(476, 214, 'twitter', 'https://www.instagram.com', 2, '2025-07-04 06:37:02'),
(477, 214, 'threads', 'https://www.instagram.com', 2, '2025-07-04 06:37:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `task_point_settings`
--

CREATE TABLE `task_point_settings` (
  `id` int(11) NOT NULL,
  `team` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `task_type` varchar(50) NOT NULL,
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `task_point_settings`
--

INSERT INTO `task_point_settings` (`id`, `team`, `category`, `task_type`, `points`, `created_at`, `updated_at`) VALUES
(1, 'production_team', 'Daily Content', 'Carousel', 1.50, '2025-06-11 14:40:11', '2025-06-11 14:40:23'),
(2, 'production_team', 'Daily Content', 'Ilustrasi', 1.50, '2025-06-11 14:40:11', '2025-06-12 14:50:09'),
(3, 'production_team', 'Daily Content', 'Infografis', 2.00, '2025-06-11 14:40:11', '2025-06-11 14:40:39'),
(4, 'production_team', 'Daily Content', 'Reels', 1.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(5, 'production_team', 'Daily Content', 'Single Images', 1.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(6, 'production_team', 'Daily Content', 'Story', 1.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(7, 'production_team', 'Produksi', 'Ilustrasi', 3.00, '2025-06-11 14:40:11', '2025-06-12 14:50:09'),
(8, 'production_team', 'Produksi', 'Images', 1.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(9, 'production_team', 'Produksi', 'Infografis', 2.00, '2025-06-11 14:40:11', '2025-06-11 15:18:11'),
(10, 'production_team', 'Produksi', 'Pitchdeck', 3.00, '2025-06-11 14:40:11', '2025-06-11 15:18:11'),
(11, 'production_team', 'Produksi', 'Video', 3.00, '2025-06-11 14:40:11', '2025-06-11 15:18:11'),
(12, 'production_team', 'Program', 'Carousel', 2.00, '2025-06-11 14:40:11', '2025-06-12 14:50:09'),
(13, 'production_team', 'Program', 'Ilustrasi', 2.00, '2025-06-11 14:40:11', '2025-06-12 14:50:09'),
(14, 'production_team', 'Program', 'Infografis', 3.00, '2025-06-11 14:40:11', '2025-06-12 14:50:09'),
(15, 'production_team', 'Program', 'Reels', 2.00, '2025-06-11 14:40:11', '2025-06-12 14:50:09'),
(16, 'production_team', 'Program', 'Single Images', 1.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(17, 'production_team', 'Program', 'Story', 1.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(18, 'content_team', 'Daily Content', 'Carousel', 1.50, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(19, 'content_team', 'Daily Content', 'Ilustrasi', 2.50, '2025-06-11 14:40:11', '2025-06-11 15:17:37'),
(20, 'content_team', 'Daily Content', 'Infografis', 2.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(21, 'content_team', 'Daily Content', 'Reels', 2.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(22, 'content_team', 'Daily Content', 'Single Images', 1.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(23, 'content_team', 'Daily Content', 'Story', 1.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(24, 'content_team', 'Distribusi', 'Link', 1.50, '2025-06-11 14:40:11', '2025-06-15 04:34:31'),
(25, 'content_team', 'Distribusi', 'Video', 1.50, '2025-06-11 14:40:11', '2025-06-15 04:34:31'),
(26, 'content_team', 'Distribusi', 'Images', 1.50, '2025-06-11 14:40:11', '2025-06-15 04:34:31'),
(27, 'content_team', 'Program', 'Garis Besar', 3.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(28, 'content_team', 'Program', 'Kick-Off', 3.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(29, 'content_team', 'Program', 'Suara dari Rimba', 0.50, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(30, 'content_team', 'Program', 'Tukar Pikiran', 3.00, '2025-06-11 14:40:11', '2025-06-11 14:40:11'),
(31, 'production_team', 'Default', 'Default', 1.00, '2025-06-12 11:16:34', '2025-06-12 11:16:34'),
(32, 'content_team', 'Default', 'Default', 1.00, '2025-06-12 11:16:34', '2025-06-12 11:16:34'),
(33, 'marketing_team', 'Default', 'Default', 1.00, '2025-06-12 11:16:34', '2025-06-12 11:16:34'),
(34, 'production_team', 'Konten', 'Video', 2.00, '2025-06-12 11:16:34', '2025-06-12 11:16:34'),
(35, 'production_team', 'Konten', 'Foto', 1.50, '2025-06-12 11:16:34', '2025-06-12 11:16:34'),
(36, 'content_team', 'Konten', 'Artikel', 1.50, '2025-06-12 11:16:34', '2025-06-12 11:16:34'),
(37, 'content_team', 'Distribusi', 'Default', 0.50, '2025-06-12 11:16:34', '2025-06-12 11:16:34'),
(38, 'content_team', 'Program', 'Ucapan Hari Besar', 2.00, '2025-06-12 14:54:43', '2025-06-12 14:54:43'),
(39, 'content_team', 'Program', 'Reels', 3.00, '2025-06-12 14:57:09', '2025-06-12 14:57:09'),
(40, 'content_team', 'Program', 'Single Images', 1.00, '2025-06-12 14:57:09', '2025-06-12 14:57:36'),
(41, 'content_team', 'Program', 'Ilustrasi', 2.00, '2025-06-12 14:57:09', '2025-06-12 14:57:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `task_rejections`
--

CREATE TABLE `task_rejections` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `rejected_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `task_rejections`
--

INSERT INTO `task_rejections` (`id`, `task_id`, `rejected_by`, `reason`, `created_at`) VALUES
(1, 27, 9, 'lagi gak mood', '2025-06-05 05:26:09'),
(2, 140, 9, 'lagi sibuk', '2025-06-18 12:25:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `task_revisions`
--

CREATE TABLE `task_revisions` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `revised_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `task_revisions`
--

INSERT INTO `task_revisions` (`id`, `task_id`, `note`, `revised_by`, `created_at`) VALUES
(1, 14, 'jelek', 2, '2025-06-04 15:54:49'),
(2, 14, 'jelek', 2, '2025-06-04 15:59:42'),
(3, 16, 'masih kurang bagus', 2, '2025-06-04 16:04:47'),
(4, 21, 'jelek', 2, '2025-06-04 17:27:56'),
(5, 27, 'jelek', 2, '2025-06-05 05:28:01'),
(6, 39, 'kurang oke', 2, '2025-06-09 06:17:07'),
(7, 41, 'ada yang kurang pada bagian slide 2', 13, '2025-06-09 06:57:02'),
(8, 141, 'jelek', 13, '2025-06-18 12:26:48'),
(9, 3, 'judulnya typo', 2, '2025-06-04 12:40:50'),
(10, 9, 'jelek', 2, '2025-06-04 14:24:38'),
(11, 4, 'jelek juga', 2, '2025-06-04 14:25:01'),
(12, 6, 'kurang bagus', 1, '2025-06-04 17:02:03'),
(13, 35, 'perbaiki lagi', 1, '2025-06-09 07:12:50'),
(14, 50, 'upload ulang', 1, '2025-06-10 14:57:37'),
(15, 63, 'ada revisi salah konten, upload ulang', 1, '2025-06-12 06:24:43'),
(16, 156, 'benerin lagi', 1, '2025-06-19 11:25:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `task_status_logs`
--

CREATE TABLE `task_status_logs` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `task_status_logs`
--

INSERT INTO `task_status_logs` (`id`, `task_id`, `status`, `updated_by`, `notes`, `timestamp`) VALUES
(1, 1, 'draft', 2, NULL, '2025-06-04 11:25:38'),
(2, 2, 'draft', 2, NULL, '2025-06-04 11:43:17'),
(3, 3, 'waiting_confirmation', 2, NULL, '2025-06-04 12:17:33'),
(6, 3, 'in_production', 9, NULL, '2025-06-04 12:29:30'),
(7, 3, 'ready_for_review', 9, NULL, '2025-06-04 12:37:56'),
(8, 3, 'revision', 2, NULL, '2025-06-04 12:40:50'),
(9, 3, 'ready_for_review', 9, NULL, '2025-06-04 12:41:15'),
(10, 3, 'uploaded', 2, NULL, '2025-06-04 12:41:57'),
(12, 3, 'completed', 1, NULL, '2025-06-04 12:51:36'),
(13, 4, 'waiting_confirmation', 2, NULL, '2025-06-04 13:10:42'),
(17, 4, 'in_production', 9, NULL, '2025-06-04 13:14:46'),
(19, 4, 'ready_for_review', 9, NULL, '2025-06-04 13:17:38'),
(20, 5, 'waiting_confirmation', 2, NULL, '2025-06-04 13:21:50'),
(21, 5, 'in_production', 9, NULL, '2025-06-04 13:21:57'),
(22, 5, 'ready_for_review', 9, NULL, '2025-06-04 13:24:26'),
(23, 6, 'waiting_confirmation', 2, NULL, '2025-06-04 13:31:45'),
(24, 6, 'in_production', 9, NULL, '2025-06-04 13:31:50'),
(25, 6, 'ready_for_review', 9, NULL, '2025-06-04 13:32:24'),
(26, 7, 'waiting_confirmation', 2, NULL, '2025-06-04 13:47:01'),
(27, 7, 'in_production', 9, NULL, '2025-06-04 14:01:02'),
(28, 7, 'ready_for_review', 9, NULL, '2025-06-04 14:02:46'),
(29, 8, 'waiting_confirmation', 2, NULL, '2025-06-04 14:09:12'),
(30, 8, 'in_production', 9, NULL, '2025-06-04 14:09:20'),
(31, 8, 'ready_for_review', 9, NULL, '2025-06-04 14:10:23'),
(32, 9, 'waiting_confirmation', 2, NULL, '2025-06-04 14:19:31'),
(33, 9, 'in_production', 9, NULL, '2025-06-04 14:19:38'),
(34, 9, 'ready_for_review', 9, NULL, '2025-06-04 14:20:31'),
(36, 9, 'revision', 2, NULL, '2025-06-04 14:24:38'),
(37, 4, 'revision', 2, NULL, '2025-06-04 14:25:01'),
(38, 4, 'ready_for_review', 9, NULL, '2025-06-04 14:26:11'),
(39, 10, 'waiting_confirmation', 2, NULL, '2025-06-04 14:28:26'),
(40, 9, 'ready_for_review', 9, NULL, '2025-06-04 14:29:38'),
(41, 10, 'in_production', 9, NULL, '2025-06-04 14:29:53'),
(42, 10, 'ready_for_review', 9, NULL, '2025-06-04 14:35:04'),
(43, 11, 'waiting_confirmation', 2, NULL, '2025-06-04 14:35:43'),
(44, 11, 'in_production', 9, NULL, '2025-06-04 14:35:56'),
(45, 11, 'ready_for_review', 9, NULL, '2025-06-04 14:36:41'),
(46, 12, 'waiting_confirmation', 2, NULL, '2025-06-04 14:37:16'),
(47, 12, 'in_production', 9, NULL, '2025-06-04 14:37:27'),
(48, 12, 'ready_for_review', 9, NULL, '2025-06-04 14:38:14'),
(49, 13, 'waiting_confirmation', 2, NULL, '2025-06-04 14:47:39'),
(50, 13, 'in_production', 9, NULL, '2025-06-04 14:48:21'),
(52, 13, 'ready_for_review', 9, NULL, '2025-06-04 14:58:17'),
(54, 14, 'waiting_confirmation', 2, NULL, '2025-06-04 14:59:24'),
(55, 14, 'in_production', 9, NULL, '2025-06-04 15:31:29'),
(56, 14, 'ready_for_review', 9, NULL, '2025-06-04 15:37:46'),
(57, 15, 'waiting_confirmation', 2, NULL, '2025-06-04 15:39:23'),
(58, 15, 'in_production', 9, NULL, '2025-06-04 15:39:35'),
(59, 15, 'ready_for_review', 9, NULL, '2025-06-04 15:42:44'),
(63, 15, 'uploaded', 2, NULL, '2025-06-04 15:52:49'),
(65, 14, 'revision', 2, NULL, '2025-06-04 15:54:49'),
(66, 14, 'ready_for_review', 9, NULL, '2025-06-04 15:55:21'),
(67, 14, 'revision', 2, NULL, '2025-06-04 15:59:42'),
(68, 14, 'ready_for_review', 9, NULL, '2025-06-04 16:00:02'),
(69, 16, 'waiting_confirmation', 2, NULL, '2025-06-04 16:02:39'),
(70, 16, 'in_production', 9, NULL, '2025-06-04 16:02:49'),
(71, 16, 'ready_for_review', 9, NULL, '2025-06-04 16:02:55'),
(72, 16, 'revision', 2, NULL, '2025-06-04 16:04:47'),
(73, 16, 'ready_for_review', 9, NULL, '2025-06-04 16:10:46'),
(74, 16, 'uploaded', 2, NULL, '2025-06-04 16:23:04'),
(75, 7, 'uploaded', 2, NULL, '2025-06-04 16:24:07'),
(76, 14, 'uploaded', 2, NULL, '2025-06-04 16:24:12'),
(77, 9, 'uploaded', 2, NULL, '2025-06-04 16:24:28'),
(78, 8, 'uploaded', 2, NULL, '2025-06-04 16:24:32'),
(79, 13, 'uploaded', 2, NULL, '2025-06-04 16:24:35'),
(80, 12, 'uploaded', 2, NULL, '2025-06-04 16:24:44'),
(81, 11, 'uploaded', 2, NULL, '2025-06-04 16:24:52'),
(82, 10, 'uploaded', 2, NULL, '2025-06-04 16:24:56'),
(83, 6, 'uploaded', 2, NULL, '2025-06-04 16:25:00'),
(84, 5, 'uploaded', 2, NULL, '2025-06-04 16:25:03'),
(85, 4, 'uploaded', 2, NULL, '2025-06-04 16:25:06'),
(86, 17, 'waiting_confirmation', 2, NULL, '2025-06-04 16:25:41'),
(87, 17, 'in_production', 9, NULL, '2025-06-04 16:26:09'),
(88, 17, 'ready_for_review', 9, NULL, '2025-06-04 16:26:16'),
(89, 17, 'uploaded', 2, NULL, '2025-06-04 16:26:50'),
(90, 18, 'waiting_confirmation', 2, NULL, '2025-06-04 16:28:03'),
(91, 18, 'in_production', 9, NULL, '2025-06-04 16:28:37'),
(92, 18, 'ready_for_review', 9, NULL, '2025-06-04 16:28:58'),
(93, 18, 'uploaded', 2, NULL, '2025-06-04 16:29:15'),
(94, 4, 'completed', 1, NULL, '2025-06-04 17:01:44'),
(95, 5, 'completed', 1, NULL, '2025-06-04 17:01:49'),
(96, 6, 'revision', 1, NULL, '2025-06-04 17:02:03'),
(97, 6, 'ready_for_review', 9, NULL, '2025-06-04 17:02:20'),
(98, 6, 'uploaded', 2, NULL, '2025-06-04 17:02:36'),
(99, 6, 'completed', 1, NULL, '2025-06-04 17:02:44'),
(100, 10, 'completed', 1, NULL, '2025-06-04 17:02:49'),
(101, 11, 'completed', 1, NULL, '2025-06-04 17:02:51'),
(102, 12, 'completed', 1, NULL, '2025-06-04 17:02:55'),
(103, 9, 'completed', 1, NULL, '2025-06-04 17:06:38'),
(104, 7, 'completed', 1, NULL, '2025-06-04 17:06:47'),
(105, 13, 'completed', 1, NULL, '2025-06-04 17:06:51'),
(106, 8, 'completed', 1, NULL, '2025-06-04 17:06:54'),
(107, 14, 'completed', 1, NULL, '2025-06-04 17:06:58'),
(108, 16, 'completed', 1, NULL, '2025-06-04 17:07:02'),
(109, 17, 'completed', 1, NULL, '2025-06-04 17:07:05'),
(110, 15, 'completed', 1, NULL, '2025-06-04 17:07:09'),
(111, 18, 'completed', 1, NULL, '2025-06-04 17:07:12'),
(112, 19, 'waiting_confirmation', 2, NULL, '2025-06-04 17:18:35'),
(113, 19, 'in_production', 9, NULL, '2025-06-04 17:18:57'),
(114, 19, 'ready_for_review', 9, NULL, '2025-06-04 17:19:21'),
(115, 20, 'waiting_confirmation', 2, NULL, '2025-06-04 17:23:39'),
(116, 20, 'in_production', 9, NULL, '2025-06-04 17:23:52'),
(117, 20, 'ready_for_review', 9, NULL, '2025-06-04 17:24:06'),
(118, 21, 'waiting_confirmation', 2, NULL, '2025-06-04 17:25:11'),
(119, 21, 'in_production', 9, NULL, '2025-06-04 17:26:00'),
(120, 21, 'ready_for_review', 9, NULL, '2025-06-04 17:27:44'),
(121, 21, 'revision', 2, NULL, '2025-06-04 17:27:56'),
(122, 21, 'ready_for_review', 9, NULL, '2025-06-04 17:28:10'),
(123, 22, 'waiting_confirmation', 2, NULL, '2025-06-04 17:28:54'),
(125, 22, 'rejected', 9, 'lagi ngerjain yang lain', '2025-06-04 17:34:07'),
(126, 22, 'waiting_confirmation', 2, 'Reassigned after rejection', '2025-06-04 17:40:02'),
(127, 23, 'waiting_confirmation', 2, NULL, '2025-06-04 17:42:03'),
(128, 23, 'in_production', 9, NULL, '2025-06-04 17:42:17'),
(129, 23, 'ready_for_review', 9, NULL, '2025-06-04 17:52:00'),
(130, 24, 'waiting_confirmation', 2, NULL, '2025-06-04 17:56:18'),
(131, 24, 'in_production', 9, NULL, '2025-06-04 17:56:30'),
(132, 24, 'ready_for_review', 9, NULL, '2025-06-04 18:04:36'),
(133, 24, 'uploaded', 2, NULL, '2025-06-04 18:05:10'),
(134, 19, 'uploaded', 2, NULL, '2025-06-04 18:05:15'),
(135, 23, 'uploaded', 2, NULL, '2025-06-04 18:05:17'),
(136, 20, 'uploaded', 2, NULL, '2025-06-04 18:05:20'),
(137, 21, 'uploaded', 2, NULL, '2025-06-04 18:05:22'),
(138, 25, 'waiting_confirmation', 2, NULL, '2025-06-04 19:07:04'),
(139, 25, 'in_production', 9, NULL, '2025-06-04 19:07:14'),
(140, 19, 'completed', 1, NULL, '2025-06-04 19:24:36'),
(141, 25, 'ready_for_review', 9, NULL, '2025-06-04 19:46:03'),
(142, 26, 'ready_for_review', 9, NULL, '2025-06-04 19:53:05'),
(143, 26, 'uploaded', 2, NULL, '2025-06-04 19:53:21'),
(144, 20, 'completed', 1, NULL, '2025-06-05 05:13:03'),
(145, 23, 'completed', 1, NULL, '2025-06-05 05:13:15'),
(146, 21, 'completed', 1, NULL, '2025-06-05 05:13:19'),
(147, 24, 'completed', 1, NULL, '2025-06-05 05:13:28'),
(148, 26, 'completed', 1, NULL, '2025-06-05 05:13:32'),
(149, 27, 'rejected', 9, 'lagi gak mood', '2025-06-05 05:26:09'),
(150, 27, 'waiting_confirmation', 2, 'Reassigned after rejection', '2025-06-05 05:26:41'),
(151, 27, 'ready_for_review', 8, NULL, '2025-06-05 05:27:34'),
(152, 27, 'revision', 2, NULL, '2025-06-05 05:28:01'),
(153, 27, 'ready_for_review', 8, NULL, '2025-06-05 05:28:23'),
(154, 27, 'uploaded', 2, NULL, '2025-06-05 05:28:37'),
(155, 25, 'uploaded', 2, NULL, '2025-06-05 05:28:52'),
(156, 25, 'completed', 1, NULL, '2025-06-05 05:29:29'),
(157, 27, 'completed', 1, NULL, '2025-06-05 05:29:47'),
(158, 22, 'ready_for_review', 8, NULL, '2025-06-05 05:30:10'),
(161, 22, 'uploaded', 2, NULL, '2025-06-05 05:48:21'),
(162, 22, 'completed', 1, NULL, '2025-06-05 06:19:32'),
(163, 28, 'ready_for_review', 9, NULL, '2025-06-05 08:24:35'),
(164, 29, 'ready_for_review', 9, NULL, '2025-06-08 15:27:21'),
(165, 29, 'uploaded', 2, NULL, '2025-06-08 15:29:15'),
(166, 34, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-09 04:49:57'),
(167, 35, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-09 05:04:49'),
(168, 36, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-09 05:09:30'),
(169, 38, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-09 05:24:50'),
(170, 38, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-09 05:32:10'),
(171, 36, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-09 05:33:04'),
(172, 28, 'uploaded', 2, NULL, '2025-06-09 05:35:35'),
(175, 36, 'uploaded', 2, NULL, '2025-06-09 06:13:11'),
(176, 38, 'uploaded', 2, NULL, '2025-06-09 06:13:24'),
(177, 39, 'ready_for_review', 9, NULL, '2025-06-09 06:16:16'),
(178, 39, 'revision', 2, NULL, '2025-06-09 06:17:07'),
(179, 39, 'ready_for_review', 9, NULL, '2025-06-09 06:17:17'),
(180, 39, 'uploaded', 2, NULL, '2025-06-09 06:17:32'),
(181, 35, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-09 06:18:28'),
(182, 34, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-09 06:18:33'),
(183, 39, 'completed', 1, NULL, '2025-06-09 06:18:41'),
(184, 38, 'completed', 1, NULL, '2025-06-09 06:18:49'),
(185, 36, 'completed', 1, NULL, '2025-06-09 06:19:08'),
(186, 29, 'completed', 1, NULL, '2025-06-09 06:19:21'),
(187, 28, 'completed', 1, NULL, '2025-06-09 06:19:26'),
(188, 35, 'uploaded', 2, NULL, '2025-06-09 06:30:54'),
(189, 40, 'ready_for_review', 9, NULL, '2025-06-09 06:32:01'),
(190, 41, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-09 06:32:57'),
(191, 41, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-09 06:33:24'),
(192, 41, 'ready_for_review', 9, NULL, '2025-06-09 06:34:33'),
(193, 41, 'revision', 13, NULL, '2025-06-09 06:57:02'),
(194, 41, 'ready_for_review', 9, NULL, '2025-06-09 06:57:25'),
(195, 41, 'uploaded', 13, NULL, '2025-06-09 06:57:55'),
(196, 40, 'uploaded', 2, NULL, '2025-06-09 07:04:18'),
(197, 41, 'completed', 1, NULL, '2025-06-09 07:12:33'),
(198, 40, 'completed', 1, NULL, '2025-06-09 07:12:41'),
(199, 35, 'revision', 1, NULL, '2025-06-09 07:12:50'),
(201, 35, 'uploaded', 2, 'Link distribusi telah direvisi', '2025-06-09 07:46:15'),
(202, 34, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-09 07:46:34'),
(203, 35, 'completed', 1, NULL, '2025-06-09 07:46:51'),
(204, 34, 'completed', 1, NULL, '2025-06-09 07:47:09'),
(205, 42, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-09 11:47:14'),
(206, 42, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-09 11:47:43'),
(207, 42, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-09 11:48:11'),
(208, 42, 'completed', 1, NULL, '2025-06-09 11:49:07'),
(209, 44, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-09 12:03:56'),
(210, 45, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-09 14:02:16'),
(211, 45, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-09 14:02:47'),
(212, 45, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-09 14:08:07'),
(213, 45, 'completed', 1, NULL, '2025-06-09 14:08:29'),
(214, 43, 'ready_for_review', 9, NULL, '2025-06-09 14:10:01'),
(215, 44, 'ready_for_review', 9, NULL, '2025-06-09 14:10:20'),
(216, 44, 'uploaded', 2, NULL, '2025-06-09 14:10:48'),
(217, 44, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-09 14:21:17'),
(218, 46, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-10 01:39:25'),
(219, 48, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-10 01:41:47'),
(220, 46, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-10 01:42:35'),
(221, 48, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-10 01:42:45'),
(222, 46, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-10 01:44:13'),
(223, 49, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-10 01:44:57'),
(224, 48, 'ready_for_review', 9, NULL, '2025-06-10 01:46:08'),
(225, 49, 'ready_for_review', 9, NULL, '2025-06-10 01:46:48'),
(226, 48, 'uploaded', 13, NULL, '2025-06-10 01:57:59'),
(227, 46, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-10 01:58:24'),
(228, 48, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-10 01:58:30'),
(229, 49, 'uploaded', 2, NULL, '2025-06-10 09:11:16'),
(230, 49, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-10 13:04:12'),
(231, 50, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-10 14:56:05'),
(232, 50, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-10 14:56:25'),
(233, 50, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-10 14:57:01'),
(234, 50, 'revision', 1, NULL, '2025-06-10 14:57:37'),
(235, 50, 'uploaded', 2, 'Link distribusi telah direvisi', '2025-06-10 15:02:51'),
(236, 51, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-10 15:17:14'),
(237, 52, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-10 15:17:30'),
(238, 53, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-10 15:17:48'),
(239, 52, 'ready_for_review', 9, NULL, '2025-06-10 15:19:44'),
(240, 51, 'ready_for_review', 9, NULL, '2025-06-10 15:20:26'),
(241, 53, 'ready_for_review', 9, NULL, '2025-06-10 15:20:45'),
(242, 53, 'uploaded', 2, NULL, '2025-06-10 15:34:14'),
(243, 52, 'uploaded', 2, NULL, '2025-06-10 15:34:23'),
(244, 51, 'uploaded', 2, NULL, '2025-06-10 15:34:39'),
(245, 53, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-10 15:34:52'),
(246, 54, 'waiting_confirmation', 1, 'Task dibuat dan menunggu konfirmasi', '2025-06-10 15:55:35'),
(247, 54, 'ready_for_review', 9, NULL, '2025-06-10 15:55:59'),
(248, 55, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-10 15:57:35'),
(249, 55, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-10 15:58:12'),
(250, 55, 'ready_for_review', 9, NULL, '2025-06-10 15:58:32'),
(251, 55, 'uploaded', 13, NULL, '2025-06-10 15:58:53'),
(252, 56, 'waiting_confirmation', 1, 'Task dibuat dan menunggu konfirmasi', '2025-06-10 16:07:07'),
(253, 56, 'ready_for_review', 9, NULL, '2025-06-10 16:07:23'),
(254, 52, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-10 16:10:46'),
(255, 51, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-10 16:10:53'),
(256, 50, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-10 16:10:59'),
(257, 56, 'completed', 1, 'Task telah disetujui dan diselesaikan', '2025-06-10 16:11:02'),
(258, 55, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-10 16:11:13'),
(259, 54, 'completed', 1, 'Task telah disetujui dan diselesaikan', '2025-06-10 16:11:21'),
(260, 57, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-11 06:36:19'),
(261, 57, 'ready_for_review', 9, NULL, '2025-06-11 06:36:49'),
(262, 57, 'uploaded', 2, NULL, '2025-06-11 06:37:12'),
(263, 57, 'completed', 1, 'Task telah disetujui dan diselesaikan', '2025-06-11 12:42:37'),
(264, 58, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-11 14:42:26'),
(265, 58, 'ready_for_review', 9, NULL, '2025-06-11 14:42:46'),
(266, 58, 'uploaded', 2, NULL, '2025-06-11 14:46:17'),
(267, 58, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-11 14:46:37'),
(268, 59, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-11 15:05:59'),
(269, 60, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-11 15:06:14'),
(270, 59, 'ready_for_review', 9, NULL, '2025-06-11 15:06:32'),
(271, 60, 'ready_for_review', 9, NULL, '2025-06-11 15:06:41'),
(272, 59, 'uploaded', 2, NULL, '2025-06-11 15:07:05'),
(273, 60, 'uploaded', 2, NULL, '2025-06-11 15:07:19'),
(274, 60, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-11 15:07:35'),
(275, 59, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-11 15:07:42'),
(276, 61, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-11 15:18:37'),
(277, 61, 'ready_for_review', 9, NULL, '2025-06-11 15:18:57'),
(278, 61, 'uploaded', 2, NULL, '2025-06-11 15:19:14'),
(279, 61, 'completed', 1, 'Task diverifikasi dengan rating: 4/5', '2025-06-11 15:19:38'),
(280, 62, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-11 15:37:58'),
(281, 62, 'ready_for_review', 9, NULL, '2025-06-11 15:38:23'),
(282, 62, 'uploaded', 2, NULL, '2025-06-11 15:38:47'),
(283, 62, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-11 15:39:00'),
(284, 63, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 06:21:04'),
(285, 63, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director: tolong dieksekusi ya ndu', '2025-06-12 06:21:51'),
(286, 63, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-12 06:23:15'),
(287, 63, 'revision', 1, NULL, '2025-06-12 06:24:43'),
(288, 63, 'uploaded', 2, 'Link distribusi telah direvisi', '2025-06-12 06:25:02'),
(289, 63, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 06:25:09'),
(290, 64, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 06:27:57'),
(291, 64, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 06:28:07'),
(292, 64, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-12 06:28:26'),
(293, 64, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 06:28:52'),
(294, 65, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 06:29:53'),
(295, 65, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 06:30:19'),
(296, 65, 'ready_for_review', 9, NULL, '2025-06-12 06:30:33'),
(297, 65, 'uploaded', 13, NULL, '2025-06-12 06:30:47'),
(298, 66, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 10:47:08'),
(299, 66, 'ready_for_review', 9, NULL, '2025-06-12 10:47:26'),
(300, 66, 'uploaded', 2, NULL, '2025-06-12 10:59:58'),
(301, 65, 'completed', 1, 'Task diverifikasi dengan rating: 4/5', '2025-06-12 11:00:13'),
(302, 66, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 11:00:17'),
(303, 67, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 11:03:09'),
(304, 67, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 11:03:27'),
(305, 67, 'ready_for_review', 9, NULL, '2025-06-12 11:07:52'),
(306, 67, 'uploaded', 13, NULL, '2025-06-12 11:08:05'),
(307, 67, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 11:08:10'),
(308, 68, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 11:10:18'),
(309, 68, 'ready_for_review', 9, NULL, '2025-06-12 11:10:28'),
(310, 68, 'uploaded', 2, NULL, '2025-06-12 11:10:37'),
(311, 69, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 11:17:08'),
(312, 69, 'ready_for_review', 9, NULL, '2025-06-12 11:17:21'),
(313, 69, 'uploaded', 2, NULL, '2025-06-12 11:17:35'),
(314, 68, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 11:17:51'),
(315, 69, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 11:17:54'),
(316, 70, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 12:52:47'),
(317, 70, 'ready_for_review', 9, NULL, '2025-06-12 12:52:58'),
(318, 70, 'uploaded', 2, NULL, '2025-06-12 12:53:21'),
(319, 70, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 12:53:41'),
(320, 71, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 13:10:49'),
(321, 71, 'ready_for_review', 9, NULL, '2025-06-12 13:11:03'),
(322, 71, 'uploaded', 2, NULL, '2025-06-12 13:11:13'),
(323, 71, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 13:11:47'),
(324, 72, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 13:14:52'),
(325, 72, 'ready_for_review', 9, NULL, '2025-06-12 13:15:01'),
(326, 72, 'uploaded', 2, NULL, '2025-06-12 13:15:13'),
(327, 72, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 13:15:23'),
(328, 73, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 13:15:49'),
(329, 73, 'ready_for_review', 9, NULL, '2025-06-12 13:15:58'),
(330, 73, 'uploaded', 2, NULL, '2025-06-12 13:16:07'),
(331, 73, 'completed', 1, NULL, '2025-06-12 13:17:14'),
(332, 74, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 13:17:57'),
(333, 75, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 13:19:35'),
(334, 75, 'ready_for_review', 9, NULL, '2025-06-12 13:19:53'),
(335, 74, 'ready_for_review', 9, NULL, '2025-06-12 13:20:01'),
(336, 75, 'uploaded', 2, NULL, '2025-06-12 13:20:12'),
(337, 74, 'uploaded', 2, NULL, '2025-06-12 13:20:23'),
(338, 74, 'completed', 1, NULL, '2025-06-12 13:20:29'),
(339, 75, 'completed', 1, NULL, '2025-06-12 13:20:32'),
(340, 76, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 13:21:44'),
(341, 76, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 13:22:07'),
(342, 76, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-12 13:23:02'),
(343, 76, 'completed', 1, NULL, '2025-06-12 13:23:23'),
(344, 77, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 13:24:50'),
(345, 77, 'ready_for_review', 9, NULL, '2025-06-12 13:25:01'),
(346, 77, 'uploaded', 2, NULL, '2025-06-12 13:25:13'),
(347, 77, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 13:25:26'),
(348, 78, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 13:37:37'),
(349, 78, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 13:56:55'),
(350, 78, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-12 13:57:39'),
(351, 78, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 14:04:14'),
(352, 79, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 15:43:08'),
(353, 79, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 15:43:17'),
(354, 79, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-12 15:51:48'),
(355, 80, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 15:52:20'),
(356, 79, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 15:52:40'),
(357, 80, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 15:52:57'),
(358, 80, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-12 15:53:10'),
(359, 80, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 15:53:18'),
(360, 81, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 15:54:12'),
(361, 81, 'ready_for_review', 9, NULL, '2025-06-12 15:54:22'),
(362, 81, 'uploaded', 2, NULL, '2025-06-12 15:54:41'),
(363, 81, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 15:54:51'),
(364, 82, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 16:05:37'),
(365, 82, 'ready_for_review', 9, NULL, '2025-06-12 16:05:59'),
(366, 82, 'uploaded', 2, NULL, '2025-06-12 16:06:35'),
(367, 82, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 16:06:43'),
(368, 83, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 16:07:16'),
(369, 83, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 16:07:23'),
(370, 83, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-12 16:07:47'),
(371, 83, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 16:07:55'),
(372, 84, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 16:08:42'),
(373, 84, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 16:08:52'),
(374, 84, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-12 16:09:06'),
(375, 84, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 16:09:15'),
(376, 85, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-12 16:10:18'),
(377, 85, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-12 16:10:25'),
(378, 85, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-12 16:10:45'),
(379, 85, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 16:10:58'),
(380, 86, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-12 16:11:22'),
(381, 86, 'ready_for_review', 9, NULL, '2025-06-12 16:11:35'),
(382, 86, 'uploaded', 2, NULL, '2025-06-12 16:11:52'),
(383, 86, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-12 16:12:08'),
(384, 87, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-15 04:32:45'),
(385, 87, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-15 04:33:11'),
(386, 87, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-15 04:34:58'),
(387, 87, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-15 04:35:13'),
(388, 43, 'uploaded', 2, NULL, '2025-06-15 08:52:25'),
(389, 43, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-15 08:52:48'),
(390, 88, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-15 08:53:34'),
(391, 88, 'ready_for_review', 9, NULL, '2025-06-16 00:31:31'),
(392, 88, 'uploaded', 2, NULL, '2025-06-16 00:31:52'),
(393, 88, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 00:32:04'),
(394, 89, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 00:32:54'),
(395, 89, 'ready_for_review', 9, NULL, '2025-06-16 00:33:04'),
(396, 89, 'uploaded', 2, NULL, '2025-06-16 00:33:22'),
(397, 89, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 00:33:36'),
(398, 90, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 05:11:14'),
(399, 90, 'ready_for_review', 7, NULL, '2025-06-16 05:12:04'),
(400, 90, 'uploaded', 5, NULL, '2025-06-16 05:12:22'),
(401, 90, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 05:12:29'),
(402, 91, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 05:13:06'),
(403, 91, 'ready_for_review', 7, NULL, '2025-06-16 05:13:15'),
(404, 91, 'uploaded', 5, NULL, '2025-06-16 05:13:34'),
(405, 91, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 05:13:46'),
(406, 92, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 05:39:08'),
(407, 92, 'ready_for_review', 7, NULL, '2025-06-16 05:39:17'),
(408, 92, 'uploaded', 5, NULL, '2025-06-16 05:39:39'),
(409, 92, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 05:39:46'),
(410, 93, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 05:40:27'),
(411, 93, 'ready_for_review', 7, NULL, '2025-06-16 05:40:38'),
(412, 93, 'uploaded', 5, NULL, '2025-06-16 05:40:53'),
(413, 93, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 05:41:05'),
(414, 94, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 05:49:56'),
(415, 94, 'ready_for_review', 7, NULL, '2025-06-16 05:50:05'),
(416, 94, 'uploaded', 5, NULL, '2025-06-16 05:50:23'),
(417, 94, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 05:50:32'),
(418, 95, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 05:53:39'),
(419, 95, 'ready_for_review', 7, NULL, '2025-06-16 05:53:47'),
(420, 95, 'uploaded', 5, NULL, '2025-06-16 05:54:01'),
(421, 95, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 05:54:11'),
(422, 96, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 05:54:55'),
(423, 96, 'ready_for_review', 7, NULL, '2025-06-16 05:55:02'),
(424, 96, 'uploaded', 5, NULL, '2025-06-16 05:55:21'),
(425, 96, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 05:55:27'),
(426, 97, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 05:56:39'),
(427, 97, 'ready_for_review', 7, NULL, '2025-06-16 05:56:47'),
(428, 97, 'uploaded', 5, NULL, '2025-06-16 05:57:00'),
(429, 97, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 05:57:12'),
(430, 98, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 06:06:20'),
(431, 98, 'ready_for_review', 7, NULL, '2025-06-16 06:06:27'),
(432, 98, 'uploaded', 5, NULL, '2025-06-16 06:06:46'),
(433, 98, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 06:06:53'),
(434, 99, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 06:08:07'),
(435, 99, 'ready_for_review', 7, NULL, '2025-06-16 06:08:15'),
(436, 99, 'uploaded', 5, NULL, '2025-06-16 06:08:30'),
(437, 99, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 06:08:35'),
(438, 100, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 06:09:05'),
(439, 100, 'ready_for_review', 7, NULL, '2025-06-16 06:09:14'),
(440, 100, 'uploaded', 5, NULL, '2025-06-16 06:09:29'),
(441, 100, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 06:09:37'),
(442, 101, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 06:11:42'),
(443, 101, 'ready_for_review', 7, NULL, '2025-06-16 06:11:54'),
(444, 101, 'uploaded', 5, NULL, '2025-06-16 06:12:09'),
(445, 101, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 06:12:15'),
(446, 102, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 06:14:44'),
(447, 102, 'ready_for_review', 7, NULL, '2025-06-16 06:14:55'),
(448, 102, 'uploaded', 5, NULL, '2025-06-16 06:15:10'),
(449, 102, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 06:15:16'),
(450, 103, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 06:16:22'),
(451, 103, 'ready_for_review', 7, NULL, '2025-06-16 06:16:29'),
(452, 103, 'uploaded', 5, NULL, '2025-06-16 06:16:42'),
(453, 103, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 06:16:51'),
(454, 104, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 06:20:09'),
(455, 104, 'ready_for_review', 7, NULL, '2025-06-16 06:20:16'),
(456, 104, 'uploaded', 5, NULL, '2025-06-16 06:20:31'),
(457, 104, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 06:20:38'),
(458, 105, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:02:37'),
(459, 105, 'ready_for_review', 7, NULL, '2025-06-16 07:02:44'),
(460, 105, 'uploaded', 5, NULL, '2025-06-16 07:03:03'),
(461, 105, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:03:09'),
(462, 106, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:04:28'),
(463, 106, 'ready_for_review', 7, NULL, '2025-06-16 07:04:36'),
(464, 106, 'uploaded', 5, NULL, '2025-06-16 07:04:52'),
(465, 106, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:04:59'),
(466, 107, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:07:42'),
(467, 107, 'ready_for_review', 7, NULL, '2025-06-16 07:07:50'),
(468, 107, 'uploaded', 5, NULL, '2025-06-16 07:08:04'),
(469, 107, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:08:09'),
(470, 108, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:08:27'),
(471, 108, 'ready_for_review', 7, NULL, '2025-06-16 07:08:35'),
(472, 108, 'uploaded', 5, NULL, '2025-06-16 07:08:50'),
(473, 108, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:08:59'),
(474, 109, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-16 07:09:37'),
(475, 109, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-16 07:09:44'),
(476, 109, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-16 07:09:57'),
(477, 109, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:10:06'),
(478, 110, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:18:13'),
(479, 110, 'ready_for_review', 7, NULL, '2025-06-16 07:18:23'),
(480, 110, 'uploaded', 5, NULL, '2025-06-16 07:18:45'),
(481, 110, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:18:53'),
(482, 111, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:26:32'),
(483, 111, 'ready_for_review', 7, NULL, '2025-06-16 07:26:49'),
(484, 111, 'uploaded', 5, NULL, '2025-06-16 07:27:03'),
(485, 111, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:27:09'),
(486, 112, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:27:42'),
(487, 112, 'ready_for_review', 7, NULL, '2025-06-16 07:27:49'),
(488, 112, 'uploaded', 5, NULL, '2025-06-16 07:28:03'),
(489, 112, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:28:09'),
(490, 113, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-16 07:28:31'),
(491, 113, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-16 07:28:45'),
(492, 113, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-16 07:28:58'),
(493, 113, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:29:05'),
(494, 114, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-16 07:32:26'),
(495, 114, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-16 07:32:33'),
(496, 114, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-16 07:32:48'),
(497, 114, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:32:55'),
(498, 115, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-16 07:37:19'),
(499, 115, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-16 07:37:30'),
(500, 115, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-16 07:37:43'),
(501, 115, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:37:50'),
(502, 116, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-16 07:39:05'),
(503, 116, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-16 07:39:12'),
(504, 116, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-16 07:39:28'),
(505, 116, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:39:34'),
(506, 117, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-16 07:39:53'),
(507, 117, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-16 07:40:00'),
(508, 117, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-16 07:40:19'),
(509, 117, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:40:26'),
(510, 118, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-16 07:40:50'),
(511, 118, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-16 07:40:58'),
(512, 118, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-16 07:41:12'),
(513, 118, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:41:23'),
(514, 119, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-16 07:45:54'),
(515, 119, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-16 07:46:03'),
(516, 119, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-16 07:46:15'),
(517, 119, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:46:30'),
(518, 120, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-16 07:48:10'),
(519, 120, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-16 07:48:17'),
(520, 120, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-16 07:48:39'),
(521, 120, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:48:47'),
(522, 121, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:49:00'),
(523, 121, 'ready_for_review', 7, NULL, '2025-06-16 07:49:08'),
(524, 121, 'uploaded', 5, NULL, '2025-06-16 07:49:32'),
(525, 121, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:49:47'),
(526, 122, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:50:18'),
(527, 122, 'ready_for_review', 7, NULL, '2025-06-16 07:50:27'),
(528, 122, 'uploaded', 5, NULL, '2025-06-16 07:50:43'),
(529, 122, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:50:49'),
(530, 123, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:51:41'),
(531, 123, 'ready_for_review', 7, NULL, '2025-06-16 07:51:51'),
(532, 123, 'uploaded', 5, NULL, '2025-06-16 07:52:05'),
(533, 123, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:52:13'),
(534, 124, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:52:36'),
(535, 124, 'ready_for_review', 7, NULL, '2025-06-16 07:52:42'),
(536, 124, 'uploaded', 5, NULL, '2025-06-16 07:52:57'),
(537, 124, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:53:04'),
(538, 125, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:53:22'),
(539, 125, 'ready_for_review', 7, NULL, '2025-06-16 07:53:33'),
(540, 125, 'uploaded', 5, NULL, '2025-06-16 07:53:55'),
(541, 125, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:54:01'),
(542, 126, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:54:32'),
(543, 126, 'ready_for_review', 7, NULL, '2025-06-16 07:54:39'),
(544, 126, 'uploaded', 5, NULL, '2025-06-16 07:55:07'),
(545, 126, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:55:13'),
(546, 127, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:57:42'),
(547, 127, 'ready_for_review', 7, NULL, '2025-06-16 07:57:52'),
(548, 127, 'uploaded', 5, NULL, '2025-06-16 07:58:07'),
(549, 127, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:58:13'),
(550, 128, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 07:58:29'),
(551, 128, 'ready_for_review', 7, NULL, '2025-06-16 07:58:37'),
(552, 128, 'uploaded', 5, NULL, '2025-06-16 07:58:46'),
(553, 128, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 07:58:52'),
(554, 129, 'waiting_confirmation', 3, 'Task dibuat dan menunggu konfirmasi', '2025-06-16 09:13:45'),
(555, 129, 'ready_for_review', 7, NULL, '2025-06-16 09:13:54'),
(556, 129, 'uploaded', 3, NULL, '2025-06-16 09:14:12'),
(557, 129, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-16 09:14:19'),
(558, 130, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-17 05:30:51'),
(559, 130, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-17 05:31:48'),
(560, 130, 'uploaded', 5, 'Link distribusi telah diupload', '2025-06-17 05:32:37'),
(561, 130, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-17 05:32:44'),
(565, 134, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-18 09:21:20'),
(566, 134, 'in_production', 9, NULL, '2025-06-18 10:14:58'),
(567, 134, 'in_production', 9, NULL, '2025-06-18 10:15:00'),
(570, 137, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-18 10:21:10'),
(571, 138, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-18 10:26:53'),
(572, 134, 'ready_for_review', 9, NULL, '2025-06-18 10:44:15'),
(573, 137, 'ready_for_review', 9, NULL, '2025-06-18 11:54:19'),
(574, 138, 'ready_for_review', 9, NULL, '2025-06-18 11:54:28'),
(575, 134, 'uploaded', 2, NULL, '2025-06-18 11:54:43'),
(576, 137, 'uploaded', 2, NULL, '2025-06-18 11:54:55'),
(577, 138, 'uploaded', 2, NULL, '2025-06-18 11:55:08'),
(578, 134, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-18 11:55:16'),
(579, 137, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-18 11:55:19'),
(580, 138, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-18 11:55:22'),
(581, 139, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-18 12:22:24'),
(582, 139, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-18 12:22:51'),
(583, 139, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-18 12:23:25'),
(584, 139, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-18 12:23:59'),
(585, 140, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-18 12:25:02'),
(586, 140, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-18 12:25:24'),
(587, 140, 'rejected', 9, 'lagi sibuk', '2025-06-18 12:25:39'),
(588, 141, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-18 12:26:09'),
(589, 141, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-18 12:26:14'),
(590, 141, 'ready_for_review', 9, NULL, '2025-06-18 12:26:31'),
(591, 141, 'revision', 13, NULL, '2025-06-18 12:26:48'),
(592, 141, 'ready_for_review', 9, NULL, '2025-06-18 12:27:11'),
(593, 141, 'uploaded', 13, NULL, '2025-06-18 12:27:19'),
(594, 141, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-18 12:27:29'),
(595, 142, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 05:28:20'),
(596, 142, 'ready_for_review', 9, NULL, '2025-06-19 05:29:39'),
(597, 142, 'uploaded', 2, NULL, '2025-06-19 05:32:50'),
(598, 142, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 05:33:09'),
(599, 143, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 05:36:13'),
(600, 143, 'ready_for_review', 9, NULL, '2025-06-19 05:39:00'),
(601, 143, 'uploaded', 2, NULL, '2025-06-19 05:39:55'),
(602, 144, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 05:53:47'),
(603, 144, 'ready_for_review', 9, NULL, '2025-06-19 05:58:03'),
(604, 144, 'uploaded', 2, NULL, '2025-06-19 06:06:42'),
(605, 144, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 06:07:19'),
(606, 145, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 06:11:01'),
(607, 145, 'ready_for_review', 9, NULL, '2025-06-19 06:12:45'),
(608, 145, 'uploaded', 2, NULL, '2025-06-19 06:14:14'),
(609, 143, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 06:14:31'),
(610, 146, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 06:15:01'),
(611, 146, 'ready_for_review', 9, NULL, '2025-06-19 06:15:10'),
(612, 146, 'uploaded', 2, NULL, '2025-06-19 06:15:46'),
(613, 146, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 06:16:04'),
(614, 147, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 06:16:30'),
(615, 147, 'ready_for_review', 9, NULL, '2025-06-19 06:16:59'),
(616, 147, 'uploaded', 2, NULL, '2025-06-19 06:17:08'),
(617, 147, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 06:17:22'),
(618, 148, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 06:17:42'),
(619, 148, 'ready_for_review', 9, NULL, '2025-06-19 06:18:01'),
(620, 148, 'uploaded', 2, NULL, '2025-06-19 06:18:12'),
(621, 149, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 06:21:39'),
(622, 149, 'ready_for_review', 9, NULL, '2025-06-19 06:21:53'),
(623, 149, 'uploaded', 2, NULL, '2025-06-19 06:22:12'),
(624, 149, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 06:22:48'),
(625, 148, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 06:24:56'),
(626, 145, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 06:25:03'),
(627, 150, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 06:25:31'),
(628, 150, 'ready_for_review', 9, NULL, '2025-06-19 06:39:25'),
(629, 150, 'uploaded', 2, NULL, '2025-06-19 06:39:40'),
(630, 150, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 06:39:57'),
(631, 151, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 08:53:55'),
(632, 151, 'ready_for_review', 9, NULL, '2025-06-19 08:54:46'),
(633, 152, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 09:02:53'),
(634, 153, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 09:12:54'),
(635, 152, 'ready_for_review', 9, NULL, '2025-06-19 09:13:43'),
(636, 153, 'ready_for_review', 9, NULL, '2025-06-19 09:16:37'),
(637, 153, 'uploaded', 2, NULL, '2025-06-19 09:16:50'),
(644, 155, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 10:55:32'),
(645, 155, 'ready_for_review', 7, NULL, '2025-06-19 10:55:53'),
(646, 155, 'uploaded', 5, NULL, '2025-06-19 10:58:32'),
(647, 155, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 10:58:45'),
(648, 156, 'waiting_confirmation', 5, 'Task dibuat dan menunggu konfirmasi', '2025-06-19 10:59:07'),
(649, 156, 'ready_for_review', 7, NULL, '2025-06-19 11:04:07'),
(650, 156, 'uploaded', 5, NULL, '2025-06-19 11:06:03'),
(651, 156, 'revision', 1, NULL, '2025-06-19 11:25:07'),
(652, 156, 'ready_for_review', 7, NULL, '2025-06-19 13:05:39'),
(653, 156, 'uploaded', 5, NULL, '2025-06-19 13:05:55'),
(654, 156, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 13:06:16'),
(656, 153, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-19 13:06:24'),
(657, 157, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-20 08:04:04'),
(658, 157, 'ready_for_review', 9, NULL, '2025-06-20 08:04:24'),
(659, 157, 'uploaded', 2, NULL, '2025-06-20 08:04:46'),
(660, 157, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-20 08:05:09'),
(661, 158, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-22 00:53:10'),
(662, 158, 'ready_for_review', 9, NULL, '2025-06-22 00:55:54'),
(663, 158, 'uploaded', 2, NULL, '2025-06-22 00:57:09'),
(664, 158, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-22 00:57:37'),
(665, 159, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-22 05:43:22'),
(666, 159, 'ready_for_review', 9, NULL, '2025-06-22 05:43:40'),
(667, 159, 'uploaded', 2, NULL, '2025-06-22 05:44:05'),
(668, 159, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-22 05:44:16'),
(669, 160, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-22 05:56:12'),
(670, 160, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director', '2025-06-22 05:56:30'),
(671, 160, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-22 05:57:10'),
(672, 160, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-22 05:57:20'),
(673, 152, 'uploaded', 2, NULL, '2025-06-22 11:31:05'),
(674, 152, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-22 11:31:25'),
(675, 151, 'uploaded', 2, NULL, '2025-06-22 11:31:49'),
(676, 151, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-22 11:32:00'),
(677, 161, 'waiting_confirmation', 3, 'Task dibuat dan menunggu konfirmasi', '2025-06-23 07:53:30'),
(678, 161, 'ready_for_review', 8, NULL, '2025-06-23 07:54:01'),
(679, 161, 'uploaded', 3, NULL, '2025-06-23 07:55:19'),
(680, 161, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-23 07:55:59'),
(681, 162, 'waiting_confirmation', 1, 'Task dibuat dan menunggu konfirmasi', '2025-06-23 08:59:38'),
(682, 162, 'ready_for_review', 9, NULL, '2025-06-23 09:01:26'),
(683, 163, 'waiting_confirmation', 1, 'Task dibuat dan menunggu konfirmasi', '2025-06-23 09:15:16'),
(684, 163, 'ready_for_review', 9, NULL, '2025-06-23 09:19:38'),
(685, 164, 'waiting_confirmation', 1, 'Task dibuat dan menunggu konfirmasi', '2025-06-23 09:24:48'),
(686, 164, 'ready_for_review', 6, NULL, '2025-06-23 09:25:30'),
(687, 140, 'waiting_confirmation', 1, 'Task dialihkan ke tim produksi lain oleh Creative Director', '2025-06-25 05:00:43'),
(688, 165, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-25 05:34:39'),
(689, 165, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 25/06/2025', '2025-06-25 05:38:35'),
(690, 164, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-25 05:43:33'),
(691, 163, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-25 05:43:38'),
(692, 162, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-25 05:43:41'),
(693, 165, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-25 05:44:45'),
(694, 165, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-25 05:45:05'),
(695, 166, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-25 07:22:42'),
(696, 166, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 25/06/2025', '2025-06-25 07:24:06'),
(697, 166, 'uploaded', 2, 'Link distribusi telah diupload', '2025-06-26 05:25:17'),
(698, 166, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-26 05:25:30'),
(699, 167, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-26 05:51:27'),
(700, 167, 'ready_for_review', 9, NULL, '2025-06-26 06:21:31'),
(701, 167, 'uploaded', 2, NULL, '2025-06-26 06:30:40'),
(702, 167, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-26 06:35:05'),
(703, 168, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-27 03:34:44'),
(704, 168, 'ready_for_review', 6, NULL, '2025-06-27 03:35:37'),
(705, 168, 'uploaded', 2, NULL, '2025-06-27 03:35:56');
INSERT INTO `task_status_logs` (`id`, `task_id`, `status`, `updated_by`, `notes`, `timestamp`) VALUES
(706, 168, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-27 03:36:05'),
(707, 169, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-27 03:37:09'),
(708, 169, 'ready_for_review', 6, NULL, '2025-06-27 03:37:22'),
(709, 169, 'uploaded', 2, NULL, '2025-06-27 03:37:42'),
(710, 169, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-27 03:37:48'),
(711, 170, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-30 13:08:22'),
(712, 170, 'waiting_confirmation', 1, NULL, '2025-06-30 13:09:08'),
(713, 171, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-30 13:13:44'),
(714, 172, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-30 13:14:51'),
(715, 173, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-30 13:18:17'),
(716, 173, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 30/06/2025', '2025-06-30 13:18:39'),
(717, 171, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 30/06/2025', '2025-06-30 13:19:04'),
(718, 174, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-30 13:23:44'),
(719, 175, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-06-30 13:27:28'),
(720, 174, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 30/06/2025', '2025-06-30 13:30:11'),
(722, 175, 'waiting_confirmation', 20, 'Task disetujui oleh Redaktur Pelaksana dengan deadline disesuaikan menjadi 30/06/2025', '2025-06-30 13:41:40'),
(723, 172, 'waiting_confirmation', 20, 'Task disetujui oleh Redaktur Pelaksana dengan deadline disesuaikan menjadi 30/06/2025', '2025-06-30 13:45:04'),
(724, 176, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-30 13:47:02'),
(725, 176, 'ready_for_review', 9, NULL, '2025-06-30 13:47:39'),
(726, 176, 'uploaded', 2, NULL, '2025-06-30 13:48:03'),
(727, 176, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-06-30 13:48:10'),
(728, 177, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-30 13:48:41'),
(729, 178, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-30 13:52:44'),
(730, 179, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-30 13:54:14'),
(731, 180, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-30 13:57:23'),
(732, 181, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-30 14:05:06'),
(733, 182, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-06-30 14:09:46'),
(734, 182, 'ready_for_review', 9, NULL, '2025-06-30 14:11:42'),
(735, 183, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 07:03:44'),
(736, 183, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 01/07/2025', '2025-07-01 07:04:41'),
(737, 184, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 07:11:52'),
(738, 185, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 07:12:32'),
(739, 186, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 07:14:44'),
(740, 187, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 07:15:36'),
(741, 188, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 07:16:21'),
(742, 186, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 01/07/2025', '2025-07-01 07:56:57'),
(743, 184, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 01/07/2025', '2025-07-01 07:57:06'),
(744, 189, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 07:57:35'),
(745, 190, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 08:05:46'),
(746, 191, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 08:32:40'),
(747, 192, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 08:39:36'),
(748, 193, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 08:43:45'),
(749, 194, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 08:52:48'),
(750, 195, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 08:58:12'),
(751, 196, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 09:00:01'),
(752, 197, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 09:17:16'),
(753, 198, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 09:45:56'),
(754, 199, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 09:51:53'),
(755, 200, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 09:54:16'),
(756, 201, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 09:57:53'),
(757, 202, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 10:34:26'),
(758, 203, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 10:40:32'),
(759, 204, 'waiting_redaktur_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 10:47:32'),
(760, 201, 'waiting_confirmation', 20, 'Task disetujui oleh Redaktur Pelaksana dengan deadline disesuaikan menjadi 01/07/2025', '2025-07-01 10:49:23'),
(761, 205, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-01 10:52:03'),
(762, 205, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 01/07/2025', '2025-07-01 10:52:30'),
(763, 205, 'uploaded', 2, 'Link distribusi telah diupload', '2025-07-01 10:54:27'),
(764, 205, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-01 10:54:32'),
(765, 206, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-01 16:04:47'),
(766, 207, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-02 04:54:58'),
(767, 208, 'draft', 13, 'Task dibuat sebagai draft', '2025-07-02 11:42:16'),
(768, 209, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-02 11:53:10'),
(769, 210, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-02 12:21:07'),
(770, 210, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 02/07/2025', '2025-07-02 12:21:18'),
(771, 180, 'ready_for_review', 9, NULL, '2025-07-02 12:37:36'),
(772, 210, 'uploaded', 9, NULL, '2025-07-02 12:44:15'),
(773, 211, 'waiting_head_confirmation', 13, 'Task dikirim untuk persetujuan', '2025-07-02 13:43:28'),
(774, 211, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 02/07/2025', '2025-07-02 13:43:35'),
(775, 211, 'ready_for_review', 9, NULL, '2025-07-02 13:43:59'),
(776, 206, 'ready_for_review', 9, NULL, '2025-07-02 14:26:01'),
(777, 210, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-02 14:38:34'),
(778, 178, 'ready_for_review', 9, NULL, '2025-07-02 14:49:58'),
(779, 211, 'uploaded', 13, NULL, '2025-07-02 15:08:04'),
(780, 211, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-03 04:56:26'),
(781, 209, 'waiting_confirmation', 1, 'Task disetujui oleh Creative Director dengan deadline disesuaikan menjadi 02/07/2025', '2025-07-03 04:56:34'),
(782, 184, 'uploaded', 2, 'Link distribusi telah diupload', '2025-07-03 06:37:48'),
(783, 209, 'uploaded', 2, 'Link distribusi telah diupload', '2025-07-03 06:38:00'),
(784, 171, 'uploaded', 2, 'Link distribusi telah diupload', '2025-07-03 06:38:19'),
(785, 173, 'uploaded', 2, 'Link distribusi telah diupload', '2025-07-03 06:38:33'),
(786, 174, 'uploaded', 2, 'Link distribusi telah diupload', '2025-07-03 06:38:47'),
(787, 183, 'uploaded', 2, 'Link distribusi telah diupload', '2025-07-03 06:43:22'),
(788, 186, 'uploaded', 2, 'Link distribusi telah diupload', '2025-07-03 06:43:35'),
(789, 171, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-03 06:44:19'),
(790, 173, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-03 06:44:27'),
(791, 174, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-03 06:44:35'),
(792, 183, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-03 06:44:42'),
(793, 184, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-03 06:44:48'),
(794, 186, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-03 06:44:54'),
(795, 209, 'completed', 1, 'Task diverifikasi dengan rating: 5/5', '2025-07-03 06:44:59'),
(796, 212, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-03 07:54:46'),
(797, 213, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-03 08:03:23'),
(798, 214, 'waiting_confirmation', 2, 'Task dibuat dan menunggu konfirmasi', '2025-07-03 09:48:29'),
(799, 214, 'ready_for_review', 9, NULL, '2025-07-03 13:03:40'),
(800, 214, 'uploaded', 2, NULL, '2025-07-04 06:37:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `content_type_id` int(11) NOT NULL,
  `content_pillar_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `time_tracking`
--

CREATE TABLE `time_tracking` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `is_auto` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `time_tracking`
--

INSERT INTO `time_tracking` (`id`, `task_id`, `start_time`, `end_time`, `user_id`, `is_auto`, `notes`) VALUES
(2, 9, '2025-06-04 21:19:38', '2025-06-04 21:19:43', 9, 0, 'Auto tracking'),
(3, 9, '2025-06-04 21:19:56', '2025-06-04 21:20:05', 9, 0, 'Auto tracking'),
(4, 9, '2025-06-04 21:20:12', '2025-06-04 21:20:13', 9, 0, 'Auto tracking'),
(5, 9, '2025-06-04 21:20:27', '2025-06-04 21:20:28', 9, 0, 'Auto tracking'),
(7, 4, '2025-06-04 21:25:20', '2025-06-04 21:26:17', 9, 0, ''),
(8, 9, '2025-06-04 21:26:47', '2025-06-04 21:27:12', 9, 0, ''),
(9, 9, '2025-06-04 21:27:19', '2025-06-04 21:29:03', 9, 0, ''),
(10, 10, '2025-06-04 21:30:10', '2025-06-04 21:34:46', 9, 0, ''),
(11, 10, '2025-06-04 21:34:46', '2025-06-04 21:34:46', 9, 0, 'Auto tracking'),
(12, 10, '2025-06-04 21:34:46', '2025-06-04 21:34:47', 9, 0, 'Auto tracking'),
(13, 10, '2025-06-04 21:34:52', '2025-06-04 21:34:52', 9, 0, 'Auto tracking'),
(14, 10, '2025-06-04 21:34:52', '2025-06-04 21:34:53', 9, 0, 'Auto tracking'),
(15, 10, '2025-06-04 21:35:00', '2025-06-04 21:35:00', 9, 0, 'Auto tracking'),
(16, 10, '2025-06-04 21:35:00', '2025-06-04 21:35:01', 9, 0, 'Auto tracking'),
(17, 11, '2025-06-04 21:35:56', '2025-06-04 21:35:56', 9, 0, 'Auto tracking'),
(18, 11, '2025-06-04 21:35:56', '2025-06-04 21:35:58', 9, 0, 'Auto tracking'),
(19, 11, '2025-06-04 21:36:37', '2025-06-04 21:36:37', 9, 0, 'Auto tracking'),
(20, 11, '2025-06-04 21:36:37', '2025-06-04 21:36:38', 9, 0, 'Auto tracking'),
(21, 12, '2025-06-04 21:37:27', '2025-06-04 21:37:27', 9, 0, 'Auto tracking'),
(22, 12, '2025-06-04 21:37:27', '2025-06-04 21:37:29', 9, 0, 'Auto tracking'),
(23, 12, '2025-06-04 21:37:43', '2025-06-04 21:37:43', 9, 0, 'Auto tracking'),
(24, 12, '2025-06-04 21:37:43', '2025-06-04 21:37:47', 9, 0, 'Auto tracking'),
(25, 12, '2025-06-04 21:37:54', '2025-06-04 21:37:54', 9, 0, 'Auto tracking'),
(26, 12, '2025-06-04 21:37:54', '2025-06-04 21:37:56', 9, 0, 'Auto tracking'),
(27, 12, '2025-06-04 21:38:01', '2025-06-04 21:38:01', 9, 0, 'Auto tracking'),
(28, 12, '2025-06-04 21:38:01', '2025-06-04 21:38:09', 9, 0, 'Auto tracking'),
(29, 13, '2025-06-04 21:48:21', '2025-06-04 21:48:21', 9, 0, 'Manual tracking'),
(30, 13, '2025-06-04 21:48:21', '2025-06-04 21:48:26', 9, 0, 'Manual tracking'),
(31, 13, '2025-06-04 21:48:34', '2025-06-04 21:48:34', 9, 0, 'Manual tracking'),
(32, 13, '2025-06-04 21:48:34', '2025-06-04 21:48:43', 9, 0, 'Manual tracking'),
(33, 13, '2025-06-04 21:48:55', '2025-06-04 21:48:55', 9, 0, 'Manual tracking'),
(34, 13, '2025-06-04 21:48:55', '2025-06-04 21:48:57', 9, 0, 'Manual tracking'),
(35, 13, '2025-06-04 21:48:59', '2025-06-04 21:48:59', 9, 0, 'Manual tracking'),
(36, 13, '2025-06-04 21:48:59', '2025-06-04 21:49:05', 9, 0, 'Manual tracking'),
(37, 13, '2025-06-04 21:49:05', '2025-06-04 21:49:19', 9, 0, 'Manual tracking'),
(38, 13, '2025-06-04 21:49:28', '2025-06-04 21:49:28', 9, 0, 'Manual tracking'),
(39, 13, '2025-06-04 21:49:28', '2025-06-04 21:49:37', 9, 0, 'Manual tracking'),
(40, 13, '2025-06-04 21:57:27', '2025-06-04 21:58:17', 9, 0, 'Started manually (Stopped on upload)'),
(42, 14, '2025-06-04 22:31:29', '2025-06-04 22:31:31', 9, 0, 'Started manually'),
(43, 14, '2025-06-04 22:31:42', '2025-06-04 22:32:42', 9, 0, 'Started manually'),
(44, 14, '2025-06-04 22:32:55', '2025-06-04 22:37:46', 9, 0, 'Started manually'),
(45, 15, '2025-06-04 22:39:35', '2025-06-04 22:42:44', 9, 0, 'Started manually'),
(47, 14, '2025-06-04 22:55:10', '2025-06-04 22:55:21', 9, 0, 'Started manually'),
(48, 14, '2025-06-04 22:59:58', '2025-06-04 23:00:02', 9, 1, NULL),
(49, 16, '2025-06-04 23:02:49', '2025-06-04 23:02:55', 9, 0, 'Started manually'),
(50, 16, '2025-06-04 23:10:42', '2025-06-04 23:10:46', 9, 1, NULL),
(51, 17, '2025-06-04 23:26:09', '2025-06-04 23:26:16', 9, 0, 'Started manually'),
(52, 18, '2025-06-04 23:28:38', '2025-06-04 23:28:58', 9, 1, NULL),
(53, 6, '2025-06-05 00:02:13', '2025-06-05 00:02:20', 9, 1, NULL),
(54, 19, '2025-06-05 00:19:04', '2025-06-05 00:19:21', 9, 0, 'Started manually'),
(55, 20, '2025-06-05 00:23:52', '2025-06-05 00:24:06', 9, 0, 'Started manually'),
(56, 21, '2025-06-05 00:26:00', '2025-06-05 00:26:07', 9, 0, 'Started manually'),
(57, 21, '2025-06-05 00:26:07', '2025-06-05 00:26:08', 9, 0, 'Started manually'),
(58, 21, '2025-06-05 00:26:08', '2025-06-05 00:27:44', 9, 0, 'Started manually'),
(59, 21, '2025-06-05 00:28:04', '2025-06-05 00:28:10', 9, 1, NULL),
(60, 23, '2025-06-05 00:42:17', '2025-06-05 00:52:00', 9, 0, 'Started manually'),
(61, 24, '2025-06-05 00:56:30', '2025-06-05 01:04:36', 9, 0, 'Started manually'),
(62, 25, '2025-06-05 02:07:14', '2025-06-05 02:46:03', 9, 0, 'Started manually'),
(63, 26, '2025-06-05 02:52:52', '2025-06-05 02:53:05', 9, 0, 'Started manually'),
(64, 27, '2025-06-05 12:27:11', '2025-06-05 12:27:34', 8, 0, 'Started manually'),
(65, 27, '2025-06-05 12:28:16', '2025-06-05 12:28:23', 8, 1, NULL),
(66, 22, '2025-06-05 12:29:56', '2025-06-05 12:30:10', 8, 0, 'Started manually'),
(67, 28, '2025-06-05 13:50:57', '2025-06-05 15:24:35', 9, 0, 'Started manually'),
(68, 29, '2025-06-08 22:26:58', '2025-06-08 22:27:21', 9, 0, 'Started manually'),
(69, 39, '2025-06-09 13:16:01', '2025-06-09 13:16:16', 9, 0, 'Started manually'),
(70, 39, '2025-06-09 13:17:13', '2025-06-09 13:17:17', 9, 1, NULL),
(71, 40, '2025-06-09 13:31:43', '2025-06-09 13:32:01', 9, 0, 'Started manually'),
(72, 41, '2025-06-09 13:33:56', '2025-06-09 13:34:33', 9, 0, 'Started manually'),
(73, 41, '2025-06-09 13:57:18', '2025-06-09 13:57:25', 9, 1, NULL),
(74, 43, '2025-06-09 21:09:54', '2025-06-09 21:10:01', 9, 0, 'Started manually'),
(75, 44, '2025-06-09 21:10:06', '2025-06-09 21:10:20', 9, 0, 'Started manually'),
(76, 48, '2025-06-10 08:44:24', '2025-06-10 08:45:26', 9, 0, 'Started manually'),
(77, 49, '2025-06-10 08:45:26', '2025-06-10 08:46:48', 9, 0, 'Started manually'),
(78, 51, '2025-06-10 22:18:01', '2025-06-10 22:20:26', 9, 0, 'Started manually'),
(79, 53, '2025-06-10 22:18:12', '2025-06-10 22:20:45', 9, 0, 'Started manually'),
(80, 52, '2025-06-10 22:18:32', '2025-06-10 22:19:44', 9, 0, 'Started manually'),
(81, 54, '2025-06-10 22:55:50', '2025-06-10 22:55:59', 9, 0, 'Started manually'),
(82, 55, '2025-06-10 22:58:25', '2025-06-10 22:58:32', 9, 0, 'Started manually'),
(83, 56, '2025-06-10 23:07:17', '2025-06-10 23:07:23', 9, 0, 'Started manually'),
(84, 57, '2025-06-11 13:36:36', '2025-06-11 13:36:49', 9, 0, 'Started manually'),
(85, 58, '2025-06-11 21:42:37', '2025-06-11 21:42:46', 9, 0, 'Started manually'),
(86, 59, '2025-06-11 22:06:25', '2025-06-11 22:06:32', 9, 0, 'Started manually'),
(87, 60, '2025-06-11 22:06:37', '2025-06-11 22:06:41', 9, 0, 'Started manually'),
(88, 61, '2025-06-11 22:18:45', '2025-06-11 22:18:57', 9, 0, 'Started manually'),
(89, 62, '2025-06-11 22:38:15', '2025-06-11 22:38:23', 9, 0, 'Started manually'),
(90, 65, '2025-06-12 13:30:27', '2025-06-12 13:30:33', 9, 0, 'Started manually'),
(91, 66, '2025-06-12 17:47:14', '2025-06-12 17:47:26', 9, 0, 'Started manually'),
(92, 67, '2025-06-12 18:07:46', '2025-06-12 18:07:52', 9, 0, 'Started manually'),
(93, 68, '2025-06-12 18:10:23', '2025-06-12 18:10:28', 9, 0, 'Started manually'),
(94, 69, '2025-06-12 18:17:15', '2025-06-12 18:17:21', 9, 0, 'Started manually'),
(95, 70, '2025-06-12 19:52:53', '2025-06-12 19:52:58', 9, 0, 'Started manually'),
(96, 71, '2025-06-12 20:10:56', '2025-06-12 20:11:03', 9, 0, 'Started manually'),
(97, 72, '2025-06-12 20:14:57', '2025-06-12 20:15:01', 9, 0, 'Started manually'),
(98, 73, '2025-06-12 20:15:54', '2025-06-12 20:15:58', 9, 0, 'Started manually'),
(99, 74, '2025-06-12 20:19:42', '2025-06-12 20:20:01', 9, 0, 'Started manually'),
(100, 75, '2025-06-12 20:19:46', '2025-06-12 20:19:53', 9, 0, 'Started manually'),
(101, 77, '2025-06-12 20:24:56', '2025-06-12 20:25:01', 9, 0, 'Started manually'),
(102, 81, '2025-06-12 22:54:18', '2025-06-12 22:54:22', 9, 0, 'Started manually'),
(103, 82, '2025-06-12 23:05:53', '2025-06-12 23:05:59', 9, 0, 'Started manually'),
(104, 86, '2025-06-12 23:11:30', '2025-06-12 23:11:35', 9, 0, 'Started manually'),
(105, 88, '2025-06-16 07:31:26', '2025-06-16 07:31:31', 9, 0, 'Started manually'),
(106, 89, '2025-06-16 07:33:01', '2025-06-16 07:33:04', 9, 0, 'Started manually'),
(107, 90, '2025-06-16 12:11:59', '2025-06-16 12:12:04', 7, 0, 'Started manually'),
(108, 91, '2025-06-16 12:13:11', '2025-06-16 12:13:15', 7, 0, 'Started manually'),
(109, 92, '2025-06-16 12:39:12', '2025-06-16 12:39:17', 7, 0, 'Started manually'),
(110, 93, '2025-06-16 12:40:33', '2025-06-16 12:40:38', 7, 0, 'Started manually'),
(111, 94, '2025-06-16 12:50:01', '2025-06-16 12:50:05', 7, 0, 'Started manually'),
(112, 95, '2025-06-16 12:53:43', '2025-06-16 12:53:47', 7, 0, 'Started manually'),
(113, 96, '2025-06-16 12:54:59', '2025-06-16 12:55:02', 7, 0, 'Started manually'),
(114, 97, '2025-06-16 12:56:42', '2025-06-16 12:56:47', 7, 0, 'Started manually'),
(115, 98, '2025-06-16 13:06:23', '2025-06-16 13:06:27', 7, 0, 'Started manually'),
(116, 99, '2025-06-16 13:08:11', '2025-06-16 13:08:15', 7, 0, 'Started manually'),
(117, 100, '2025-06-16 13:09:09', '2025-06-16 13:09:14', 7, 0, 'Started manually'),
(118, 101, '2025-06-16 13:11:49', '2025-06-16 13:11:54', 7, 0, 'Started manually'),
(119, 102, '2025-06-16 13:14:51', '2025-06-16 13:14:55', 7, 0, 'Started manually'),
(120, 103, '2025-06-16 13:16:25', '2025-06-16 13:16:29', 7, 0, 'Started manually'),
(121, 104, '2025-06-16 13:20:13', '2025-06-16 13:20:16', 7, 0, 'Started manually'),
(122, 105, '2025-06-16 14:02:40', '2025-06-16 14:02:44', 7, 0, 'Started manually'),
(123, 106, '2025-06-16 14:04:33', '2025-06-16 14:04:36', 7, 0, 'Started manually'),
(124, 107, '2025-06-16 14:07:46', '2025-06-16 14:07:50', 7, 0, 'Started manually'),
(125, 108, '2025-06-16 14:08:32', '2025-06-16 14:08:35', 7, 0, 'Started manually'),
(126, 110, '2025-06-16 14:18:20', '2025-06-16 14:18:23', 7, 0, 'Started manually'),
(127, 111, '2025-06-16 14:26:45', '2025-06-16 14:26:49', 7, 0, 'Started manually'),
(128, 112, '2025-06-16 14:27:45', '2025-06-16 14:27:49', 7, 0, 'Started manually'),
(129, 121, '2025-06-16 14:49:04', '2025-06-16 14:49:08', 7, 0, 'Started manually'),
(130, 122, '2025-06-16 14:50:23', '2025-06-16 14:50:27', 7, 0, 'Started manually'),
(131, 123, '2025-06-16 14:51:47', '2025-06-16 14:51:51', 7, 0, 'Started manually'),
(132, 124, '2025-06-16 14:52:39', '2025-06-16 14:52:42', 7, 0, 'Started manually'),
(133, 125, '2025-06-16 14:53:29', '2025-06-16 14:53:33', 7, 0, 'Started manually'),
(134, 126, '2025-06-16 14:54:36', '2025-06-16 14:54:39', 7, 0, 'Started manually'),
(135, 127, '2025-06-16 14:57:49', '2025-06-16 14:57:52', 7, 0, 'Started manually'),
(136, 128, '2025-06-16 14:58:33', '2025-06-16 14:58:37', 7, 0, 'Started manually'),
(137, 129, '2025-06-16 16:13:51', '2025-06-16 16:13:54', 7, 0, 'Started manually'),
(178, 134, '2025-06-18 17:14:58', '2025-06-18 17:15:00', 9, 0, 'Started from task acceptance'),
(179, 134, '2025-06-18 17:15:00', '2025-06-18 17:44:15', 9, 0, 'Started from task acceptance'),
(180, 138, '2025-06-18 17:44:21', '2025-06-18 18:54:28', 9, 0, 'Started manually'),
(181, 137, '2025-06-18 17:44:33', '2025-06-18 18:54:19', 9, 0, 'Started manually'),
(182, 141, '2025-06-18 19:26:19', '2025-06-18 19:26:31', 9, 0, 'Started manually'),
(183, 141, '2025-06-18 19:27:07', '2025-06-18 19:27:11', 9, 1, NULL),
(184, 142, '2025-06-19 12:29:13', '2025-06-19 12:29:39', 9, 0, 'Started manually'),
(185, 143, '2025-06-19 12:38:38', '2025-06-19 12:39:00', 9, 0, 'Started manually'),
(186, 144, '2025-06-19 12:57:57', '2025-06-19 12:58:03', 9, 0, 'Started manually'),
(187, 145, '2025-06-19 13:12:40', '2025-06-19 13:12:45', 9, 0, 'Started manually'),
(188, 146, '2025-06-19 13:15:06', '2025-06-19 13:15:10', 9, 0, 'Started manually'),
(189, 147, '2025-06-19 13:16:52', '2025-06-19 13:16:59', 9, 0, 'Started manually'),
(190, 148, '2025-06-19 13:17:55', '2025-06-19 13:18:01', 9, 0, 'Started manually'),
(191, 149, '2025-06-19 13:21:44', '2025-06-19 13:21:53', 9, 0, 'Started manually'),
(192, 150, '2025-06-19 13:39:16', '2025-06-19 13:39:25', 9, 0, 'Started manually'),
(193, 151, '2025-06-19 15:54:39', '2025-06-19 15:54:46', 9, 0, 'Started manually'),
(194, 152, '2025-06-19 16:13:36', '2025-06-19 16:13:43', 9, 0, 'Started manually'),
(195, 153, '2025-06-19 16:16:31', '2025-06-19 16:16:37', 9, 0, 'Started manually'),
(198, 155, '2025-06-19 17:55:37', '2025-06-19 17:55:53', 7, 0, 'Started manually'),
(199, 156, '2025-06-19 17:59:14', '2025-06-19 18:04:07', 7, 0, 'Started manually'),
(200, 156, '2025-06-19 20:05:36', '2025-06-19 20:05:39', 7, 1, NULL),
(201, 157, '2025-06-20 15:04:15', '2025-06-20 15:04:24', 9, 0, 'Started manually'),
(202, 158, '2025-06-22 07:54:09', '2025-06-22 07:55:54', 9, 0, 'Started manually'),
(203, 159, '2025-06-22 12:43:34', '2025-06-22 12:43:40', 9, 0, 'Started manually'),
(204, 161, '2025-06-23 14:53:44', '2025-06-23 14:54:01', 8, 0, 'Started manually'),
(205, 162, '2025-06-23 16:00:20', '2025-06-23 16:01:26', 9, 0, 'Started manually'),
(206, 163, '2025-06-23 16:19:32', '2025-06-23 16:19:38', 9, 0, 'Started manually'),
(207, 164, '2025-06-23 16:25:08', '2025-06-23 16:25:30', 6, 0, 'Started manually'),
(208, 164, '2025-06-23 16:25:32', NULL, 6, 1, NULL),
(209, 167, '2025-06-26 12:51:55', '2025-06-26 13:21:31', 9, 0, 'Started manually'),
(210, 168, '2025-06-27 10:35:11', '2025-06-27 10:35:37', 6, 0, 'Started manually'),
(211, 169, '2025-06-27 10:37:16', '2025-06-27 10:37:22', 6, 0, 'Started manually'),
(212, 176, '2025-06-30 20:47:20', '2025-06-30 20:47:39', 9, 0, 'Started manually'),
(213, 182, '2025-06-30 21:11:14', '2025-06-30 21:11:42', 9, 0, 'Started manually'),
(214, 180, '2025-07-02 19:21:46', '2025-07-02 19:37:36', 9, 0, 'Started manually'),
(215, 210, '2025-07-02 19:43:57', '2025-07-02 19:44:15', 9, 0, 'Started manually'),
(216, 211, '2025-07-02 20:43:42', '2025-07-02 20:43:59', 9, 0, 'Started manually'),
(217, 206, '2025-07-02 21:25:54', '2025-07-02 21:26:01', 9, 0, 'Started manually'),
(218, 178, '2025-07-02 21:49:45', '2025-07-02 21:49:58', 9, 0, 'Started manually'),
(219, 214, '2025-07-03 19:59:20', '2025-07-03 20:03:40', 9, 0, 'Started manually');

-- --------------------------------------------------------

--
-- Struktur dari tabel `typing_indicators`
--

CREATE TABLE `typing_indicators` (
  `user_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `is_typing` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `typing_indicators`
--

INSERT INTO `typing_indicators` (`user_id`, `receiver_id`, `is_typing`, `updated_at`) VALUES
(1, 2, 0, '2025-07-04 13:31:48'),
(1, 3, 0, '2025-06-23 14:59:16'),
(1, 6, 0, '2025-06-23 16:24:06'),
(1, 8, 0, '2025-06-23 16:09:26'),
(1, 9, 0, '2025-06-25 13:44:49'),
(1, 13, 0, '2025-06-25 12:27:38'),
(2, 1, 0, '2025-07-04 13:31:37'),
(2, 9, 0, '2025-06-25 12:44:14'),
(2, 20, 0, '2025-07-01 17:50:23'),
(3, 8, 0, '2025-06-23 14:52:01'),
(4, 9, 0, '2025-06-23 07:38:21'),
(6, 1, 0, '2025-06-23 16:23:56'),
(6, 8, 0, '2025-06-23 16:24:55'),
(8, 1, 0, '2025-06-23 16:09:35'),
(8, 3, 0, '2025-06-23 14:52:15'),
(8, 6, 0, '2025-06-23 16:26:51'),
(9, 1, 0, '2025-06-25 13:44:42'),
(9, 2, 0, '2025-06-23 07:48:21'),
(9, 4, 0, '2025-06-23 07:39:31'),
(9, 6, 0, '2025-06-23 16:26:17'),
(13, 1, 0, '2025-06-25 12:27:41'),
(20, 2, 0, '2025-07-01 17:50:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','marketing_team','content_team','production_team','creative_director','redaksi','redaktur_pelaksana') NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_photo` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `active`, `created_at`, `updated_at`, `profile_photo`, `bio`, `reset_token`, `reset_expires`, `remember_token`, `last_activity`, `whatsapp_number`) VALUES
(1, 'Rebby Noviar', 'rebby@inilah.com', '$2y$10$sb2DMNnv7lS.2Aa6n6AAAu.ylAQMOy/gJcjTfeLr2t/DBnpc9YBJ6', 'creative_director', 1, '2025-06-04 10:48:47', '2025-07-05 02:43:53', 'profile_1_1750326526.png', 'Aduh pusing', NULL, NULL, '5f64f2bb4bf4f37170dc3748984fa22b6ced95150781eae0569108d7e07403da', '2025-07-05 09:43:53', '+6285855636636'),
(2, 'Rindu', 'rindu@inilah.com', '$2y$10$txxw3xqtS405PVXj93y5t.jCaPuKweo7J2lxIdWItCHQ/CvMc7U8K', 'content_team', 1, '2025-06-04 11:10:51', '2025-07-04 12:18:23', 'profile_2_1750832606.png', '', '323391997a64e0157b830719a8fd815fcc19f1c66f1d780208fd113d3a578f63', '2025-07-04 10:02:39', '16d8d9570f8451cca0f1b39be1e8cfea188c63c6e71de02d9c76391ab57756f8', '2025-07-04 19:18:23', '+6281214965263'),
(3, 'Wahyu', 'wahyu@inilah.com', '$2y$10$uSA.aRL/VBz.oZecHF/qKeE6QORaEs7cXEL8SMRQb55nwGvLZ1avy', 'content_team', 1, '2025-06-04 11:11:06', '2025-06-23 09:08:32', NULL, NULL, NULL, NULL, '8c08df250ae77a9a06b81860395b36c1a4b1153eb56a3a3caff54357dca6e183', '2025-06-23 16:08:32', NULL),
(4, 'Adrika', 'adrika@inilah.com', '$2y$10$4234NhqvQS1KsJB3LUNGK.5a4GvIpcCdrYRDNaLvjX0fUEEsTUI6i', 'content_team', 1, '2025-06-04 11:11:25', '2025-06-23 00:56:51', NULL, NULL, NULL, NULL, NULL, '2025-06-23 07:56:51', NULL),
(5, 'Irma', 'irma@inilah.com', '$2y$10$eALAre2wjk1vGF5Dm3wpneWWvbMapTgPKHClZtXKeLfpemaFIu9R6', 'content_team', 1, '2025-06-04 11:11:37', '2025-06-17 05:03:20', 'profile_5_1750136600.png', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Taufan', 'taufan@inilah.com', '$2y$10$v4KebPHDBKnh/4QiqWRH0OgkrbGfvRXX7CTe4W7hCDf3cSMiNf6oO', 'production_team', 1, '2025-06-04 11:11:53', '2025-06-27 04:51:06', 'profile_6_1749480249.jpg', 'Gadis koleris yang suka berimajinasi, terangi harimu dengan senyuman karamelku. Halo, aku Taufan! ', NULL, NULL, '87e8a9fdf6f6c2fcf5358466d89ce5411c14fe4097ec80a9ed4f52f31d720154', '2025-06-27 11:51:06', NULL),
(7, 'Axel', 'axel@inilah.com', '$2y$10$qksykbhd/gdezQ3kMFrNl.LsmwjQ0DwIvI4VT3QGEgVNjqIan4XiC', 'production_team', 1, '2025-06-04 11:12:07', '2025-06-19 10:55:06', 'profile_7_1750330506.png', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Lukman', 'lukman@inilah.com', '$2y$10$y48AjploppCXJNGdJjQ.x.8pE7AXb2nvxZckT7rE27TBLO6fxerF6', 'production_team', 1, '2025-06-04 11:12:23', '2025-06-24 13:55:15', NULL, NULL, NULL, NULL, NULL, '2025-06-24 20:55:15', NULL),
(9, 'Febry', 'febry@inilah.com', '$2y$10$swhfo5H5WynOejlZdnL/4OFJzbTCYIHtHEy327ZylxwU223ystnGu', 'production_team', 1, '2025-06-04 11:12:37', '2025-07-03 13:03:48', 'profile_9_1749066837.jpg', 'yang tiba-tiba suka Gala Premiere.', NULL, NULL, NULL, '2025-07-03 20:03:48', '+6281222333444'),
(10, 'Dede', 'dede@inilah.com', '$2y$10$qDyzVdcYCscBbMHdEW/BT.D29IgKdE1BDaRjffXhM14NPfOp3A2/y', 'production_team', 1, '2025-06-04 11:12:51', '2025-06-04 11:12:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Rizky', 'rizky@inilah.com', '$2y$10$qzhdFM8AVHj49D1JQ2M2KONf5sdjdeFPTS11n/1ypo8MsInlKTc6e', 'production_team', 1, '2025-06-04 11:13:03', '2025-06-04 11:13:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'Talita', 'talita@inilah.com', '$2y$10$tDZpSj1mgvGXK8NLgCQQzOCqNs408HH0QZbuzQFfh4PZRBSV0V7XS', 'marketing_team', 1, '2025-06-08 19:38:47', '2025-07-02 15:08:04', 'profile_13_1750832579.png', 'yang cantik jelita.', NULL, NULL, NULL, '2025-07-02 22:08:04', NULL),
(18, 'Nebby', 'nebby@inilah.com', '$2y$10$oU5YBu33i83bJRsgTYf7P.ewT7cRb0T.3jzqA0HOe914NMugK1Qie', 'redaksi', 1, '2025-06-30 08:42:01', '2025-07-04 07:02:39', 'profile_18_1751290770.png', '', '323391997a64e0157b830719a8fd815fcc19f1c66f1d780208fd113d3a578f63', '2025-07-04 10:02:39', NULL, NULL, '+6281214965263'),
(19, 'ivan@inilah.com', 'ivan@inilah.com', '$2y$10$EMIjlyIwMtyilqZj1REvIu5d8.TrDSGsbUc9g10Lyr3tgkYFrtGGy', 'redaksi', 1, '2025-06-30 08:42:23', '2025-06-30 08:42:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'Ibnu', 'ibnu@inilah.com', '$2y$10$irk0fWTFcdbzoj/155oTXuwwhADVjuASvQ3qrZD6wKxkA6akMUeu6', 'redaktur_pelaksana', 1, '2025-06-30 08:42:45', '2025-07-04 07:02:39', 'profile_20_1751285536.png', '', '323391997a64e0157b830719a8fd815fcc19f1c66f1d780208fd113d3a578f63', '2025-07-04 10:02:39', NULL, NULL, '+6281214965263'),
(21, 'Obes', 'obes@inilah.com', '$2y$10$V2m0PSDyUsSxBzciFtfkFe3VG4ENPejQ4qcpHqDWGCEHGA/p65ZZS', 'redaktur_pelaksana', 1, '2025-07-01 07:17:14', '2025-07-04 07:02:39', NULL, NULL, '323391997a64e0157b830719a8fd815fcc19f1c66f1d780208fd113d3a578f63', '2025-07-04 10:02:39', NULL, NULL, '+6281214965263');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_points`
--

CREATE TABLE `user_points` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `added_by` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_points`
--

INSERT INTO `user_points` (`id`, `user_id`, `task_id`, `points`, `earned_at`, `added_by`, `description`) VALUES
(1, 9, 66, 1.00, '2025-06-12 11:09:26', NULL, NULL),
(2, 9, 66, 10.00, '2025-06-12 11:09:40', NULL, NULL),
(3, 9, 67, 1.00, '2025-06-12 11:12:02', NULL, NULL),
(4, 9, 66, 1.00, '2025-06-12 11:12:05', NULL, NULL),
(5, 9, 73, 2.00, '2025-06-12 13:17:14', NULL, NULL),
(6, 9, 74, 1.50, '2025-06-12 13:20:29', NULL, NULL),
(7, 2, 74, 1.50, '2025-06-12 13:20:29', NULL, NULL),
(8, 9, 75, 1.50, '2025-06-12 13:20:32', NULL, NULL),
(9, 2, 75, 1.50, '2025-06-12 13:20:32', NULL, NULL),
(10, 2, 76, 1.00, '2025-06-12 13:23:23', NULL, NULL),
(11, 13, 76, 1.00, '2025-06-12 13:23:23', NULL, NULL),
(12, 9, 77, 1.50, '2025-06-12 13:25:22', NULL, NULL),
(13, 2, 77, 1.50, '2025-06-12 13:25:22', NULL, NULL),
(14, 13, 78, 1.00, '2025-06-12 13:37:43', NULL, NULL),
(15, 2, 78, 1.00, '2025-06-12 13:56:58', NULL, NULL),
(17, 2, NULL, 4.00, '2025-06-12 15:33:10', 1, 'Brief ilustrasi idul adha'),
(18, 2, NULL, 0.50, '2025-06-12 15:33:57', 1, 'buletin point'),
(19, 9, NULL, 1.50, '2025-06-12 15:34:05', 1, 'bonus'),
(20, 13, 79, 1.00, '2025-06-12 15:43:13', NULL, NULL),
(21, 2, 79, 1.00, '2025-06-12 15:52:29', NULL, NULL),
(22, 13, 80, 1.00, '2025-06-12 15:52:53', NULL, NULL),
(23, 2, 80, 1.00, '2025-06-12 15:53:16', NULL, NULL),
(24, 9, 81, 1.00, '2025-06-12 15:54:49', NULL, NULL),
(25, 2, 81, 1.00, '2025-06-12 15:54:49', NULL, NULL),
(26, 9, 82, 1.00, '2025-06-12 16:06:41', NULL, NULL),
(27, 2, 82, 1.00, '2025-06-12 16:06:41', NULL, NULL),
(28, 13, 83, 1.00, '2025-06-12 16:07:20', NULL, NULL),
(29, 2, 83, 3.00, '2025-06-12 16:07:53', NULL, NULL),
(30, 13, 84, 1.00, '2025-06-12 16:08:48', NULL, NULL),
(31, 2, 84, 2.50, '2025-06-12 16:09:12', NULL, NULL),
(32, 13, 85, 1.00, '2025-06-12 16:10:22', NULL, NULL),
(33, 2, 85, 2.00, '2025-06-12 16:10:56', NULL, NULL),
(34, 9, 86, 1.00, '2025-06-12 16:12:06', NULL, NULL),
(35, 2, 86, 1.00, '2025-06-12 16:12:06', NULL, NULL),
(36, 13, 87, 1.00, '2025-06-15 04:33:00', NULL, NULL),
(37, 2, 87, 2.50, '2025-06-15 04:35:11', NULL, NULL),
(38, 9, 43, 1.50, '2025-06-15 08:16:27', NULL, NULL),
(39, 2, 43, 1.50, '2025-06-15 08:16:27', NULL, NULL),
(40, 9, 88, 1.00, '2025-06-16 00:30:08', NULL, NULL),
(41, 2, 88, 1.00, '2025-06-16 00:30:08', NULL, NULL),
(42, 9, 89, 3.00, '2025-06-16 00:33:34', NULL, NULL),
(43, 2, 89, 3.00, '2025-06-16 00:33:34', NULL, NULL),
(44, 7, 90, 1.50, '2025-06-16 05:12:27', NULL, NULL),
(45, 5, 90, 1.50, '2025-06-16 05:12:27', NULL, NULL),
(46, 7, 91, 3.00, '2025-06-16 05:13:44', NULL, NULL),
(47, 5, 91, 3.00, '2025-06-16 05:13:44', NULL, NULL),
(48, 7, 92, 2.00, '2025-06-16 05:39:44', NULL, NULL),
(49, 5, 92, 2.00, '2025-06-16 05:39:44', NULL, NULL),
(50, 7, 93, 1.00, '2025-06-16 05:41:03', NULL, NULL),
(51, 5, 93, 1.00, '2025-06-16 05:41:03', NULL, NULL),
(52, 7, 94, 1.00, '2025-06-16 05:50:30', NULL, NULL),
(53, 5, 94, 1.00, '2025-06-16 05:50:30', NULL, NULL),
(54, 7, 95, 1.00, '2025-06-16 05:54:08', NULL, NULL),
(55, 5, 95, 1.00, '2025-06-16 05:54:08', NULL, NULL),
(56, 7, 96, 3.00, '2025-06-16 05:55:26', NULL, NULL),
(57, 5, 96, 3.00, '2025-06-16 05:55:26', NULL, NULL),
(58, 7, 97, 1.00, '2025-06-16 05:57:10', NULL, NULL),
(59, 5, 97, 1.00, '2025-06-16 05:57:10', NULL, NULL),
(60, 7, 98, 1.00, '2025-06-16 06:06:50', NULL, NULL),
(61, 5, 98, 1.00, '2025-06-16 06:06:50', NULL, NULL),
(62, 7, 100, 3.00, '2025-06-16 06:09:33', NULL, NULL),
(63, 5, 100, 3.00, '2025-06-16 06:09:33', NULL, NULL),
(64, 7, 101, 1.00, '2025-06-16 06:12:13', NULL, NULL),
(65, 5, 101, 1.00, '2025-06-16 06:12:13', NULL, NULL),
(66, 7, 102, 1.00, '2025-06-16 06:15:14', NULL, NULL),
(67, 5, 102, 1.00, '2025-06-16 06:15:14', NULL, NULL),
(68, 7, 103, 1.00, '2025-06-16 06:16:49', NULL, NULL),
(69, 5, 103, 1.00, '2025-06-16 06:16:49', NULL, NULL),
(70, 7, 104, 1.00, '2025-06-16 06:20:36', NULL, NULL),
(71, 5, 104, 1.00, '2025-06-16 06:20:36', NULL, NULL),
(72, 7, 105, 1.00, '2025-06-16 07:03:07', NULL, NULL),
(73, 5, 105, 1.00, '2025-06-16 07:03:07', NULL, NULL),
(74, 7, 106, 1.00, '2025-06-16 07:04:57', NULL, NULL),
(75, 5, 106, 1.00, '2025-06-16 07:04:57', NULL, NULL),
(76, 7, 107, 1.00, '2025-06-16 07:08:07', NULL, NULL),
(77, 5, 107, 3.00, '2025-06-16 07:08:07', NULL, NULL),
(78, 7, 108, 1.00, '2025-06-16 07:08:57', NULL, NULL),
(79, 5, 108, 2.50, '2025-06-16 07:08:57', NULL, NULL),
(80, 13, 109, 1.00, '2025-06-16 07:09:40', NULL, NULL),
(81, 5, 109, 2.50, '2025-06-16 07:10:03', NULL, NULL),
(82, 7, 110, 1.00, '2025-06-16 07:18:50', NULL, NULL),
(83, 5, 110, 2.50, '2025-06-16 07:18:50', NULL, NULL),
(84, 7, 111, 1.00, '2025-06-16 07:27:07', NULL, NULL),
(85, 5, 111, 1.00, '2025-06-16 07:27:07', NULL, NULL),
(86, 7, 112, 1.00, '2025-06-16 07:28:07', NULL, NULL),
(87, 5, 112, 3.00, '2025-06-16 07:28:07', NULL, NULL),
(88, 13, 113, 1.00, '2025-06-16 07:28:42', NULL, NULL),
(89, 5, 113, 3.50, '2025-06-16 07:29:03', NULL, NULL),
(90, 13, 114, 1.00, '2025-06-16 07:32:30', NULL, NULL),
(91, 5, 114, 3.50, '2025-06-16 07:32:52', NULL, NULL),
(92, 13, 115, 1.00, '2025-06-16 07:37:27', NULL, NULL),
(93, 5, 115, 3.50, '2025-06-16 07:37:48', NULL, NULL),
(94, 13, 116, 1.00, '2025-06-16 07:39:09', NULL, NULL),
(95, 5, 116, 3.50, '2025-06-16 07:39:32', NULL, NULL),
(96, 13, 117, 1.00, '2025-06-16 07:39:57', NULL, NULL),
(97, 5, 117, 3.00, '2025-06-16 07:40:24', NULL, NULL),
(98, 13, 118, 1.00, '2025-06-16 07:40:55', NULL, NULL),
(99, 5, 118, 2.50, '2025-06-16 07:41:20', NULL, NULL),
(100, 13, 119, 1.00, '2025-06-16 07:46:00', NULL, NULL),
(101, 5, 119, 2.50, '2025-06-16 07:46:26', NULL, NULL),
(102, 13, 120, 1.00, '2025-06-16 07:48:14', NULL, NULL),
(103, 5, 120, 1.50, '2025-06-16 07:48:44', NULL, NULL),
(104, 7, 121, 1.00, '2025-06-16 07:49:19', NULL, NULL),
(105, 5, 121, 1.00, '2025-06-16 07:49:19', NULL, NULL),
(106, 7, 122, 1.00, '2025-06-16 07:50:48', NULL, NULL),
(107, 5, 122, 1.00, '2025-06-16 07:50:48', NULL, NULL),
(108, 7, 123, 1.00, '2025-06-16 07:52:11', NULL, NULL),
(109, 5, 123, 2.50, '2025-06-16 07:52:11', NULL, NULL),
(110, 7, 124, 1.00, '2025-06-16 07:53:02', NULL, NULL),
(111, 5, 124, 1.50, '2025-06-16 07:53:02', NULL, NULL),
(112, 7, 125, 1.00, '2025-06-16 07:53:37', NULL, NULL),
(113, 5, 125, 1.00, '2025-06-16 07:53:37', NULL, NULL),
(114, 7, 126, 1.00, '2025-06-16 07:54:43', NULL, NULL),
(115, 5, 126, 1.00, '2025-06-16 07:54:43', NULL, NULL),
(116, 7, 127, 1.00, '2025-06-16 07:58:11', NULL, NULL),
(117, 5, 127, 1.50, '2025-06-16 07:58:11', NULL, NULL),
(118, 7, 128, 1.00, '2025-06-16 07:58:50', NULL, NULL),
(119, 5, 128, 1.00, '2025-06-16 07:58:50', NULL, NULL),
(120, 7, 129, 1.00, '2025-06-16 09:14:17', NULL, NULL),
(121, 3, 129, 3.00, '2025-06-16 09:14:17', NULL, NULL),
(122, 13, 130, 1.00, '2025-06-17 05:30:55', NULL, NULL),
(123, 5, 130, 3.50, '2025-06-17 05:32:42', NULL, NULL),
(124, 9, 134, 1.00, '2025-06-18 11:55:13', NULL, NULL),
(125, 2, 134, 3.00, '2025-06-18 11:55:13', NULL, NULL),
(126, 9, 137, 1.00, '2025-06-18 11:55:17', NULL, NULL),
(127, 2, 137, 3.00, '2025-06-18 11:55:17', NULL, NULL),
(128, 9, 138, 1.00, '2025-06-18 11:55:20', NULL, NULL),
(129, 2, 138, 3.00, '2025-06-18 11:55:20', NULL, NULL),
(130, 13, 139, 1.00, '2025-06-18 12:22:39', NULL, NULL),
(131, 2, 139, 2.50, '2025-06-18 12:23:44', NULL, NULL),
(132, 13, 140, 1.00, '2025-06-18 12:25:13', NULL, NULL),
(133, 9, 140, 3.00, '2025-06-18 12:25:54', NULL, NULL),
(134, 13, 141, 1.00, '2025-06-18 12:26:11', NULL, NULL),
(135, 9, 141, 3.00, '2025-06-18 12:27:24', NULL, NULL),
(136, 9, 142, 1.00, '2025-06-19 05:33:07', NULL, NULL),
(137, 2, 142, 3.00, '2025-06-19 05:33:07', NULL, NULL),
(138, 9, 144, 1.00, '2025-06-19 06:07:17', NULL, NULL),
(139, 2, 144, 3.00, '2025-06-19 06:07:17', NULL, NULL),
(140, 9, 143, 1.00, '2025-06-19 06:14:28', NULL, NULL),
(141, 2, 143, 3.00, '2025-06-19 06:14:28', NULL, NULL),
(142, 9, 146, 1.00, '2025-06-19 06:16:02', NULL, NULL),
(143, 2, 146, 1.00, '2025-06-19 06:16:02', NULL, NULL),
(144, 9, 147, 1.00, '2025-06-19 06:17:18', NULL, NULL),
(145, 2, 147, 1.00, '2025-06-19 06:17:18', NULL, NULL),
(146, 9, 149, 1.00, '2025-06-19 06:22:45', NULL, NULL),
(147, 2, 149, 2.00, '2025-06-19 06:22:45', NULL, NULL),
(148, 9, 148, 1.00, '2025-06-19 06:22:50', NULL, NULL),
(149, 2, 148, 1.50, '2025-06-19 06:22:50', NULL, NULL),
(150, 9, 145, 1.00, '2025-06-19 06:25:00', NULL, NULL),
(151, 2, 145, 1.00, '2025-06-19 06:25:00', NULL, NULL),
(152, 9, 150, 1.00, '2025-06-19 06:39:54', NULL, NULL),
(153, 2, 150, 2.00, '2025-06-19 06:39:54', NULL, NULL),
(154, 7, 155, 1.00, '2025-06-19 10:58:43', NULL, NULL),
(155, 5, 155, 2.50, '2025-06-19 10:58:43', NULL, NULL),
(156, 7, 156, 1.00, '2025-06-19 11:25:02', NULL, NULL),
(157, 5, 156, 3.00, '2025-06-19 11:25:02', NULL, NULL),
(158, 7, 154, 1.00, '2025-06-19 13:06:18', NULL, NULL),
(159, 5, 154, 2.50, '2025-06-19 13:06:18', NULL, NULL),
(160, 9, 153, 1.00, '2025-06-19 13:06:22', NULL, NULL),
(161, 2, 153, 1.50, '2025-06-19 13:06:22', NULL, NULL),
(162, 9, 152, 1.00, '2025-06-19 13:06:25', NULL, NULL),
(163, 2, 152, 1.00, '2025-06-19 13:06:25', NULL, NULL),
(164, 9, NULL, 30.00, '2025-06-20 06:25:28', 1, 'bonus'),
(165, 9, 157, 2.00, '2025-06-20 08:05:06', NULL, NULL),
(166, 2, 157, 4.00, '2025-06-20 08:05:06', NULL, NULL),
(167, 2, NULL, 30.00, '2025-06-20 08:05:44', 1, 'bonus'),
(168, 9, 158, 1.00, '2025-06-22 00:57:32', NULL, NULL),
(169, 2, 158, 3.00, '2025-06-22 00:57:32', NULL, NULL),
(170, 9, 159, 1.00, '2025-06-22 05:44:11', NULL, NULL),
(171, 2, 159, 3.00, '2025-06-22 05:44:11', NULL, NULL),
(172, 13, 160, 1.00, '2025-06-22 05:56:24', NULL, NULL),
(173, 2, 160, 3.50, '2025-06-22 05:57:17', NULL, NULL),
(174, 9, 151, 1.00, '2025-06-22 11:31:15', NULL, NULL),
(175, 2, 151, 1.00, '2025-06-22 11:31:15', NULL, NULL),
(176, 8, 161, 1.00, '2025-06-23 07:55:57', NULL, NULL),
(177, 3, 161, 1.00, '2025-06-23 07:55:57', NULL, NULL),
(178, 8, NULL, 30.00, '2025-06-23 07:56:45', 1, 'Bonus point karena lukman anak baik'),
(179, 9, 162, 1.00, '2025-06-23 08:59:38', NULL, NULL),
(180, 1, 162, 1.00, '2025-06-23 08:59:38', NULL, NULL),
(181, 9, 163, 1.00, '2025-06-23 09:15:16', NULL, NULL),
(182, 1, 163, 1.00, '2025-06-23 09:15:16', NULL, NULL),
(183, 6, 164, 1.00, '2025-06-23 09:24:48', NULL, NULL),
(184, 1, 164, 1.00, '2025-06-23 09:24:48', NULL, NULL),
(185, 7, 140, 3.00, '2025-06-25 05:00:43', NULL, NULL),
(186, 13, 165, 1.00, '2025-06-25 05:34:47', NULL, NULL),
(187, 2, 165, 1.50, '2025-06-25 05:39:10', NULL, NULL),
(188, 13, 166, 1.00, '2025-06-25 07:23:02', NULL, NULL),
(189, 2, 166, 3.50, '2025-06-26 05:25:27', NULL, NULL),
(190, 9, 167, 1.00, '2025-06-26 05:53:47', NULL, NULL),
(191, 2, 167, 1.00, '2025-06-26 05:53:47', NULL, NULL),
(192, 6, 168, 2.00, '2025-06-27 03:36:02', NULL, NULL),
(193, 2, 168, 5.00, '2025-06-27 03:36:02', NULL, NULL),
(194, 6, 169, 2.00, '2025-06-27 03:37:46', NULL, NULL),
(195, 2, 169, 5.00, '2025-06-27 03:37:46', NULL, NULL),
(196, 13, 170, 1.00, '2025-06-30 13:09:11', NULL, NULL),
(197, 13, 171, 1.00, '2025-06-30 13:14:00', NULL, NULL),
(198, 13, 173, 1.00, '2025-06-30 13:18:34', NULL, NULL),
(199, 13, 172, 1.00, '2025-06-30 13:19:13', NULL, NULL),
(200, 13, 174, 1.00, '2025-06-30 13:25:00', NULL, NULL),
(201, 13, 175, 1.00, '2025-06-30 13:30:05', NULL, NULL),
(202, 18, 175, 1.00, '2025-06-30 13:42:17', NULL, NULL),
(203, 9, 176, 1.00, '2025-06-30 13:47:44', NULL, NULL),
(204, 2, 176, 1.00, '2025-06-30 13:47:44', NULL, NULL),
(205, 13, 183, 1.00, '2025-07-01 07:03:59', NULL, NULL),
(206, 13, 184, 1.00, '2025-07-01 07:11:57', NULL, NULL),
(207, 13, 186, 1.00, '2025-07-01 07:56:54', NULL, NULL),
(208, 13, 201, 1.00, '2025-07-01 10:49:02', NULL, NULL),
(209, 13, 185, 1.00, '2025-07-01 10:49:58', NULL, NULL),
(210, 13, 205, 1.00, '2025-07-01 10:52:21', NULL, NULL),
(211, 2, 205, 2.00, '2025-07-01 10:54:30', NULL, NULL),
(212, 13, 210, 1.00, '2025-07-02 12:21:15', NULL, NULL),
(213, 13, 211, 1.00, '2025-07-02 13:43:32', NULL, NULL),
(214, 9, 210, 1.00, '2025-07-02 13:44:55', NULL, NULL),
(215, 9, 211, 2.00, '2025-07-02 14:38:21', NULL, NULL),
(216, 13, 209, 1.00, '2025-07-03 04:56:30', NULL, NULL),
(217, 2, 209, 1.50, '2025-07-03 04:56:39', NULL, NULL),
(218, 2, 171, 3.50, '2025-07-03 06:44:16', NULL, NULL),
(219, 2, 173, 3.50, '2025-07-03 06:44:25', NULL, NULL),
(220, 2, 174, 3.50, '2025-07-03 06:44:33', NULL, NULL),
(221, 2, 183, 3.50, '2025-07-03 06:44:39', NULL, NULL),
(222, 2, 184, 3.50, '2025-07-03 06:44:45', NULL, NULL),
(223, 2, 186, 3.50, '2025-07-03 06:44:51', NULL, NULL),
(224, 2, NULL, 10.00, '2025-07-03 07:56:14', 1, 'bonus');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `last_activity`, `session_status`) VALUES
(66774, 1, 'ga3s67l4eld5sq5t06666r5aik', '2025-07-04 12:28:35', 'active'),
(66775, 1, '', '0000-00-00 00:00:00', 'active'),
(66776, 2, 'fm6qtms8b5c99lb5uo0c52efee', '2025-07-04 12:28:39', 'active'),
(66777, 2, '', '0000-00-00 00:00:00', 'active'),
(66778, 1, '', '0000-00-00 00:00:00', 'active'),
(66779, 2, '', '0000-00-00 00:00:00', 'active'),
(66780, 1, '', '0000-00-00 00:00:00', 'active'),
(66781, 1, '', '0000-00-00 00:00:00', 'active'),
(66782, 2, '', '0000-00-00 00:00:00', 'active'),
(66783, 1, '', '0000-00-00 00:00:00', 'active'),
(66784, 2, '', '0000-00-00 00:00:00', 'active'),
(66785, 1, '', '0000-00-00 00:00:00', 'active'),
(66786, 2, '', '0000-00-00 00:00:00', 'active'),
(66787, 1, '', '0000-00-00 00:00:00', 'active'),
(66788, 2, '', '0000-00-00 00:00:00', 'active'),
(66789, 1, '', '0000-00-00 00:00:00', 'active'),
(66790, 2, '', '0000-00-00 00:00:00', 'active'),
(66791, 1, '', '0000-00-00 00:00:00', 'active'),
(66792, 2, '', '0000-00-00 00:00:00', 'active'),
(66793, 1, '', '0000-00-00 00:00:00', 'active'),
(66794, 2, '', '0000-00-00 00:00:00', 'active'),
(66795, 1, '', '0000-00-00 00:00:00', 'active'),
(66796, 2, '', '0000-00-00 00:00:00', 'active'),
(66797, 1, '', '0000-00-00 00:00:00', 'active'),
(66798, 2, '', '0000-00-00 00:00:00', 'active'),
(66799, 1, '', '0000-00-00 00:00:00', 'active'),
(66800, 2, '', '0000-00-00 00:00:00', 'active'),
(66801, 1, '', '0000-00-00 00:00:00', 'active'),
(66802, 2, '', '0000-00-00 00:00:00', 'active'),
(66803, 1, '', '0000-00-00 00:00:00', 'active'),
(66804, 2, '', '0000-00-00 00:00:00', 'active'),
(66805, 1, '', '0000-00-00 00:00:00', 'active'),
(66806, 2, '', '0000-00-00 00:00:00', 'active'),
(66807, 1, '', '0000-00-00 00:00:00', 'active'),
(66808, 2, '', '0000-00-00 00:00:00', 'active'),
(66809, 1, '', '0000-00-00 00:00:00', 'active'),
(66810, 2, '', '0000-00-00 00:00:00', 'active'),
(66811, 1, '', '0000-00-00 00:00:00', 'active'),
(66812, 2, '', '0000-00-00 00:00:00', 'active'),
(66813, 1, '', '0000-00-00 00:00:00', 'active'),
(66814, 2, '', '0000-00-00 00:00:00', 'active'),
(66815, 1, '', '0000-00-00 00:00:00', 'active'),
(66816, 2, '', '0000-00-00 00:00:00', 'active'),
(66817, 1, '', '0000-00-00 00:00:00', 'active'),
(66818, 2, '', '0000-00-00 00:00:00', 'active'),
(66819, 1, '', '0000-00-00 00:00:00', 'active'),
(66820, 2, '', '0000-00-00 00:00:00', 'active'),
(66821, 1, '', '0000-00-00 00:00:00', 'active'),
(66822, 2, '', '0000-00-00 00:00:00', 'active'),
(66823, 1, '', '0000-00-00 00:00:00', 'active'),
(66824, 2, '', '0000-00-00 00:00:00', 'active'),
(66825, 1, '', '0000-00-00 00:00:00', 'active'),
(66826, 2, '', '0000-00-00 00:00:00', 'active'),
(66827, 1, '', '0000-00-00 00:00:00', 'active'),
(66828, 2, '', '0000-00-00 00:00:00', 'active'),
(66829, 1, '', '0000-00-00 00:00:00', 'active'),
(66830, 2, '', '0000-00-00 00:00:00', 'active'),
(66831, 1, '', '0000-00-00 00:00:00', 'active'),
(66832, 2, '', '0000-00-00 00:00:00', 'active'),
(66833, 1, '', '0000-00-00 00:00:00', 'active'),
(66834, 2, '', '0000-00-00 00:00:00', 'active'),
(66835, 1, '', '0000-00-00 00:00:00', 'active'),
(66836, 2, '', '0000-00-00 00:00:00', 'active'),
(66837, 1, '', '0000-00-00 00:00:00', 'active'),
(66838, 2, '', '0000-00-00 00:00:00', 'active'),
(66839, 1, '', '0000-00-00 00:00:00', 'active'),
(66840, 2, '', '0000-00-00 00:00:00', 'active'),
(66841, 1, '', '0000-00-00 00:00:00', 'active'),
(66842, 2, '', '0000-00-00 00:00:00', 'active'),
(66843, 1, '', '0000-00-00 00:00:00', 'active'),
(66844, 2, '', '0000-00-00 00:00:00', 'active'),
(66845, 1, '', '0000-00-00 00:00:00', 'active'),
(66846, 2, '', '0000-00-00 00:00:00', 'active'),
(66847, 1, '', '0000-00-00 00:00:00', 'active'),
(66848, 2, '', '0000-00-00 00:00:00', 'active'),
(66849, 1, '', '0000-00-00 00:00:00', 'active'),
(66850, 2, '', '0000-00-00 00:00:00', 'active'),
(66851, 1, '', '0000-00-00 00:00:00', 'active'),
(66852, 2, '', '0000-00-00 00:00:00', 'active'),
(66853, 1, '', '0000-00-00 00:00:00', 'active'),
(66854, 2, '', '0000-00-00 00:00:00', 'active'),
(66855, 1, '', '0000-00-00 00:00:00', 'active'),
(66856, 2, '', '0000-00-00 00:00:00', 'active'),
(66857, 1, '', '0000-00-00 00:00:00', 'active'),
(66858, 2, '', '0000-00-00 00:00:00', 'active'),
(66859, 1, '', '0000-00-00 00:00:00', 'active'),
(66860, 2, '', '0000-00-00 00:00:00', 'active'),
(66861, 1, '', '0000-00-00 00:00:00', 'active'),
(66862, 2, '', '0000-00-00 00:00:00', 'active'),
(66863, 1, '', '0000-00-00 00:00:00', 'active'),
(66864, 2, '', '0000-00-00 00:00:00', 'active'),
(66865, 1, '', '0000-00-00 00:00:00', 'active'),
(66866, 2, '', '0000-00-00 00:00:00', 'active'),
(66867, 1, '', '0000-00-00 00:00:00', 'active'),
(66868, 2, '', '0000-00-00 00:00:00', 'active'),
(66869, 1, '', '0000-00-00 00:00:00', 'active'),
(66870, 2, '', '0000-00-00 00:00:00', 'active'),
(66871, 1, '', '0000-00-00 00:00:00', 'active'),
(66872, 2, '', '0000-00-00 00:00:00', 'active'),
(66873, 1, '', '0000-00-00 00:00:00', 'active'),
(66874, 2, '', '0000-00-00 00:00:00', 'active'),
(66875, 1, '', '0000-00-00 00:00:00', 'active'),
(66876, 2, '', '0000-00-00 00:00:00', 'active'),
(66877, 1, '', '0000-00-00 00:00:00', 'active'),
(66878, 2, '', '0000-00-00 00:00:00', 'active'),
(66879, 1, '', '0000-00-00 00:00:00', 'active'),
(66880, 2, '', '0000-00-00 00:00:00', 'active'),
(66881, 1, '', '0000-00-00 00:00:00', 'active'),
(66882, 2, '', '0000-00-00 00:00:00', 'active'),
(66883, 1, '', '0000-00-00 00:00:00', 'active'),
(66884, 2, '', '0000-00-00 00:00:00', 'active'),
(66885, 1, '', '0000-00-00 00:00:00', 'active'),
(66886, 2, '', '0000-00-00 00:00:00', 'active'),
(66887, 1, '', '0000-00-00 00:00:00', 'active'),
(66888, 2, '', '0000-00-00 00:00:00', 'active'),
(66889, 1, '', '0000-00-00 00:00:00', 'active'),
(66890, 2, '', '0000-00-00 00:00:00', 'active'),
(66891, 1, '', '0000-00-00 00:00:00', 'active'),
(66892, 2, '', '0000-00-00 00:00:00', 'active'),
(66893, 1, '', '0000-00-00 00:00:00', 'active'),
(66894, 2, '', '0000-00-00 00:00:00', 'active'),
(66895, 1, '', '0000-00-00 00:00:00', 'active'),
(66896, 2, '', '0000-00-00 00:00:00', 'active'),
(66897, 2, '', '0000-00-00 00:00:00', 'active'),
(66898, 1, 'eo7es0l3hgal1jfa9oj3q3drsg', '2025-07-05 02:44:14', 'active'),
(66899, 1, '', '0000-00-00 00:00:00', 'active'),
(66900, 1, '', '0000-00-00 00:00:00', 'active'),
(66901, 1, '', '0000-00-00 00:00:00', 'active'),
(66902, 1, '', '0000-00-00 00:00:00', 'active'),
(66903, 1, '', '0000-00-00 00:00:00', 'active'),
(66904, 1, '', '0000-00-00 00:00:00', 'active'),
(66905, 1, '', '0000-00-00 00:00:00', 'active'),
(66906, 1, '', '0000-00-00 00:00:00', 'active');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `task_reminders` tinyint(1) DEFAULT 1,
  `theme` varchar(20) DEFAULT 'light',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `email_notifications`, `task_reminders`, `theme`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'light', '2025-06-04 19:30:44', '2025-06-04 19:31:01'),
(2, 2, 1, 1, 'light', '2025-06-09 12:10:24', '2025-06-22 11:32:56'),
(3, 13, 1, 1, 'light', '2025-06-09 14:37:33', '2025-06-09 14:37:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `viral_content`
--

CREATE TABLE `viral_content` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `platform` enum('instagram','tiktok') NOT NULL,
  `views` int(11) NOT NULL,
  `marked_date` date NOT NULL,
  `marked_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `viral_content`
--

INSERT INTO `viral_content` (`id`, `task_id`, `user_id`, `platform`, `views`, `marked_date`, `marked_by`, `created_at`) VALUES
(1, 89, 9, 'instagram', 50000, '2025-06-16', 1, '2025-06-16 00:45:55'),
(2, 89, 9, 'tiktok', 100000, '2025-06-16', 1, '2025-06-16 00:46:11'),
(3, 91, 7, 'instagram', 123610, '2025-06-16', 1, '2025-06-16 05:28:42'),
(4, 91, 7, 'tiktok', 247490, '2025-06-16', 1, '2025-06-16 05:28:50'),
(5, 129, 7, 'instagram', 100000, '2025-06-16', 1, '2025-06-16 09:14:55'),
(6, 129, 7, 'tiktok', 200000, '2025-06-16', 1, '2025-06-16 09:15:00'),
(7, 128, 7, 'instagram', 56000, '2025-06-16', 1, '2025-06-16 09:15:42'),
(8, 128, 7, 'tiktok', 110000, '2025-06-16', 1, '2025-06-16 09:15:50'),
(9, 125, 7, 'instagram', 508000, '2025-06-16', 1, '2025-06-16 09:16:21'),
(10, 125, 7, 'tiktok', 100000, '2025-06-16', 1, '2025-06-16 09:16:29'),
(11, 161, NULL, 'instagram', 56000, '2025-06-23', 1, '2025-06-23 08:00:06'),
(12, 161, NULL, 'tiktok', 120000, '2025-06-23', 1, '2025-06-23 08:00:13'),
(13, 167, NULL, 'instagram', 100000, '2025-06-27', 1, '2025-06-27 02:41:47'),
(14, 167, NULL, 'tiktok', 100000, '2025-06-27', 1, '2025-06-27 02:41:54');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `agenda_items`
--
ALTER TABLE `agenda_items`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `announcement_comments`
--
ALTER TABLE `announcement_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `announcement_reactions`
--
ALTER TABLE `announcement_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`announcement_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `chat_last_checked`
--
ALTER TABLE `chat_last_checked`
  ADD PRIMARY KEY (`user_id`,`sender_id`);

--
-- Indeks untuk tabel `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indeks untuk tabel `content_pillars`
--
ALTER TABLE `content_pillars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `content_types`
--
ALTER TABLE `content_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `distribution_platforms`
--
ALTER TABLE `distribution_platforms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `platform_id` (`platform_id`);

--
-- Indeks untuk tabel `general_info`
--
ALTER TABLE `general_info`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `platforms`
--
ALTER TABLE `platforms`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `point_settings`
--
ALTER TABLE `point_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `team` (`team`,`category`,`content_type`);

--
-- Indeks untuk tabel `program_schedules`
--
ALTER TABLE `program_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `pic_id` (`pic_id`),
  ADD KEY `editor_id` (`editor_id`);

--
-- Indeks untuk tabel `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indeks untuk tabel `revisions`
--
ALTER TABLE `revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `revised_by` (`revised_by`);

--
-- Indeks untuk tabel `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_shift_date` (`user_id`,`shift_date`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `target_schedule`
--
ALTER TABLE `target_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `target_settings`
--
ALTER TABLE `target_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indeks untuk tabel `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `content_type_id` (`content_type_id`),
  ADD KEY `content_pillar_id` (`content_pillar_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indeks untuk tabel `task_assistance`
--
ALTER TABLE `task_assistance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indeks untuk tabel `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `task_links`
--
ALTER TABLE `task_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indeks untuk tabel `task_point_settings`
--
ALTER TABLE `task_point_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `task_rejections`
--
ALTER TABLE `task_rejections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `rejected_by` (`rejected_by`);

--
-- Indeks untuk tabel `task_revisions`
--
ALTER TABLE `task_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `revised_by` (`revised_by`);

--
-- Indeks untuk tabel `task_status_logs`
--
ALTER TABLE `task_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indeks untuk tabel `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `content_type_id` (`content_type_id`),
  ADD KEY `content_pillar_id` (`content_pillar_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `time_tracking`
--
ALTER TABLE `time_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `typing_indicators`
--
ALTER TABLE `typing_indicators`
  ADD PRIMARY KEY (`user_id`,`receiver_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `user_points`
--
ALTER TABLE `user_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indeks untuk tabel `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- Indeks untuk tabel `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `viral_content`
--
ALTER TABLE `viral_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `agenda_items`
--
ALTER TABLE `agenda_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `announcement_comments`
--
ALTER TABLE `announcement_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `announcement_reactions`
--
ALTER TABLE `announcement_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT untuk tabel `content_pillars`
--
ALTER TABLE `content_pillars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `content_types`
--
ALTER TABLE `content_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `distribution_platforms`
--
ALTER TABLE `distribution_platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `general_info`
--
ALTER TABLE `general_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=900;

--
-- AUTO_INCREMENT untuk tabel `platforms`
--
ALTER TABLE `platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `point_settings`
--
ALTER TABLE `point_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `program_schedules`
--
ALTER TABLE `program_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `revisions`
--
ALTER TABLE `revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT untuk tabel `target_schedule`
--
ALTER TABLE `target_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `target_settings`
--
ALTER TABLE `target_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT untuk tabel `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT untuk tabel `task_assistance`
--
ALTER TABLE `task_assistance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `task_links`
--
ALTER TABLE `task_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=478;

--
-- AUTO_INCREMENT untuk tabel `task_point_settings`
--
ALTER TABLE `task_point_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT untuk tabel `task_rejections`
--
ALTER TABLE `task_rejections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `task_revisions`
--
ALTER TABLE `task_revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `task_status_logs`
--
ALTER TABLE `task_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=801;

--
-- AUTO_INCREMENT untuk tabel `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `time_tracking`
--
ALTER TABLE `time_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `user_points`
--
ALTER TABLE `user_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- AUTO_INCREMENT untuk tabel `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66907;

--
-- AUTO_INCREMENT untuk tabel `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `viral_content`
--
ALTER TABLE `viral_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `announcement_comments`
--
ALTER TABLE `announcement_comments`
  ADD CONSTRAINT `announcement_comments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `announcement_reactions`
--
ALTER TABLE `announcement_reactions`
  ADD CONSTRAINT `announcement_reactions_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `content_pillars`
--
ALTER TABLE `content_pillars`
  ADD CONSTRAINT `content_pillars_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `content_types`
--
ALTER TABLE `content_types`
  ADD CONSTRAINT `content_types_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `distribution_platforms`
--
ALTER TABLE `distribution_platforms`
  ADD CONSTRAINT `distribution_platforms_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `distribution_platforms_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `program_schedules`
--
ALTER TABLE `program_schedules`
  ADD CONSTRAINT `program_schedules_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `content_pillars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `program_schedules_ibfk_2` FOREIGN KEY (`pic_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `program_schedules_ibfk_3` FOREIGN KEY (`editor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `revisions`
--
ALTER TABLE `revisions`
  ADD CONSTRAINT `revisions_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `revisions_ibfk_2` FOREIGN KEY (`revised_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `shifts`
--
ALTER TABLE `shifts`
  ADD CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shifts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `target_schedule`
--
ALTER TABLE `target_schedule`
  ADD CONSTRAINT `target_schedule_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`),
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`content_pillar_id`) REFERENCES `content_pillars` (`id`),
  ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `tasks_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tasks_ibfk_6` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `task_assistance`
--
ALTER TABLE `task_assistance`
  ADD CONSTRAINT `task_assistance_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assistance_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `task_assistance_ibfk_3` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `task_links`
--
ALTER TABLE `task_links`
  ADD CONSTRAINT `task_links_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_links_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `task_rejections`
--
ALTER TABLE `task_rejections`
  ADD CONSTRAINT `task_rejections_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_rejections_ibfk_2` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `task_revisions`
--
ALTER TABLE `task_revisions`
  ADD CONSTRAINT `task_revisions_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_revisions_ibfk_2` FOREIGN KEY (`revised_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `task_status_logs`
--
ALTER TABLE `task_status_logs`
  ADD CONSTRAINT `task_status_logs_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_status_logs_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `templates_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `templates_ibfk_2` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`),
  ADD CONSTRAINT `templates_ibfk_3` FOREIGN KEY (`content_pillar_id`) REFERENCES `content_pillars` (`id`),
  ADD CONSTRAINT `templates_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `time_tracking`
--
ALTER TABLE `time_tracking`
  ADD CONSTRAINT `time_tracking_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_tracking_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `typing_indicators`
--
ALTER TABLE `typing_indicators`
  ADD CONSTRAINT `typing_indicators_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `typing_indicators_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `user_points`
--
ALTER TABLE `user_points`
  ADD CONSTRAINT `user_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
