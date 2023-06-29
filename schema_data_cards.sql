-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Počítač: localhost
-- Vytvořeno: Čtv 29. čen 2023, 10:27
-- Verze serveru: 10.3.29-MariaDB
-- Verze PHP: 7.4.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `wordpress`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `ms21_cards`
--

CREATE TABLE `ms21_cards` (
  `id` int(11) NOT NULL,
  `count_avail` int(11) DEFAULT 0,
  `value` int(11) DEFAULT 0,
  `is_special` tinyint(4) DEFAULT NULL,
  `image_src` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Vypisuji data pro tabulku `ms21_cards`
--

INSERT INTO `ms21_cards` (`id`, `count_avail`, `value`, `is_special`, `image_src`, `data`) VALUES
(2, 9, 1, 0, 'd1.png', '{\"image\":\"house.png\", \"coords\":[[1,0],[0,1],[1,1],[2,1],[1,2]]}'),
(3, 998, 1, 0, 'd2.png', '{\"image\":\"house.png\", \"coords\":[[0,0],[0,1],[0,2],[1,1],[2,1]]}'),
(4, 999, 1, 1, 'd3.png', '{\"image\":\"house.png\", \"coords\":[[0,0],[1,0]]}'),
(5, 997, 1, 0, 'd4.png', '{\"image\":\"house.png\", \"coords\":[[2,0],[2,1],[1,1],[1,2],[0,2]]}'),
(6, 998, 1, 0, 'd5.png', '{\"image\":\"house.png\", \"coords\":[[0,0],[0,1],[0,2],[1,0]]}'),
(7, 999, 1, 0, 'h1.png', '{\"image\":\"hero.png\", \"coords\":[[1,1]], \"scoords\":[[1,0],[0,1],[1,2],[2,1]]}'),
(8, 998, 1, 0, 'h2.png', '{\"image\":\"hero.png\", \"coords\":[[1,0]], \"scoords\":[[0,2],[1,2],[2,2]]}'),
(9, 999, 1, 0, 'h3.png', '{\"image\":\"hero.png\", \"coords\":[[1,1]], \"scoords\":[[0,0],[2,0],[0,2],[2,2]]}'),
(10, 999, 1, 0, 'h4.png', '{\"image\":\"hero.png\", \"coords\":[[0,0]], \"scoords\":[[0,1],[0,2],[0,3]]}'),
(11, 999, 1, 0, 'l1.png', '{\"image\":\"forest.png\", \"coords\":[[0,0],[1,0],[0,2],[1,2]]}'),
(12, 998, 1, 0, 'l2.png', '{\"image\":\"forest.png\", \"coords\":[[0,0],[0,1],[0,2],[1,0],[1,2]]}'),
(13, 999, 1, 0, 'l3.png', '{\"image\":\"forest.png\", \"coords\":[[1,0],[0,1],[0,2]]}'),
(14, 998, 1, 1, 'l4.png', '{\"image\":\"forest.png\", \"coords\":[[0,0],[0,1],[1,0]]}'),
(15, 999, 1, 0, 'l5.png', '{\"image\":\"forest.png\", \"coords\":[[1,0],[0,1],[1,1],[2,1],[1,2]]}'),
(16, 999, 1, 0, 'p1.png', '{\"image\":\"field.png\", \"coords\":[[0,1],[1,0],[0,2],[1,1]]}'),
(17, 996, 1, 0, 'p2.png', '{\"image\":\"field.png\", \"coords\":[[0,0],[0,1],[0,2],[1,1],[2,1]]}'),
(18, 999, 1, 0, 'p3.png', '{\"image\":\"field.png\", \"coords\":[[1,0],[0,1],[0,2]]}'),
(19, 998, 1, 0, 'p4.png', '{\"image\":\"field.png\", \"coords\":[[0,0],[1,0],[0,1],[1,1]]}'),
(20, 998, 1, 1, 'p5.png', '{\"image\":\"field.png\", \"coords\":[[0,0],[0,2]]}'),
(21, 999, 1, 0, 'v1.png', '{\"image\":\"water.png\", \"coords\":[[0,0],[0,1],[0,2],[1,1]]}'),
(22, 998, 1, 0, 'v2.png', '{\"image\":\"water.png\", \"coords\":[[0,0],[0,1],[0,2],[1,0],[1,2]]}'),
(23, 999, 1, 0, 'v3.png', '{\"image\":\"water.png\", \"coords\":[[0,0],[0,1],[0,2],[1,0]]}'),
(24, 999, 1, 0, 'v4.png', '{\"image\":\"water.png\", \"coords\":[[0,0],[0,1],[1,0],[1,1]]}'),
(25, 998, 1, 0, 'v5.png', '{\"image\":\"water.png\", \"coords\":[[0,1],[1,0]]}'),
(27, 999, 0, 0, 'prisera.png', '{\"image\":\"monster.png\", \"coords\":[[1,0],[0,1],[1,2]]}'),
(28, 999, 0, 0, 'prisera.png', '{\"image\":\"monster.png\", \"coords\":[[1,0],[0,1],[1,2],[1,1]]}'),
(29, 999, 0, 0, 'prisera.png', '{\"image\":\"monster.png\", \"coords\":[[0,0],[1,1],[2,2],[0,2],[2,0]]}'),
(30, 999, 0, 0, 'prisera.png', '{\"image\":\"monster.png\", \"coords\":[[0,0],[0,1],[1,0]]}');

--
-- Klíče pro exportované tabulky
--

--
-- Klíče pro tabulku `ms21_cards`
--
ALTER TABLE `ms21_cards`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `ms21_cards`
--
ALTER TABLE `ms21_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
