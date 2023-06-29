-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Počítač: localhost
-- Vytvořeno: Čtv 29. čen 2023, 10:13
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
-- Struktura tabulky `ms21_auction`
--

CREATE TABLE `ms21_auction` (
  `uid` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `highest_bet` int(11) DEFAULT 0,
  `team_id` int(11) DEFAULT -1,
  `t_stamp` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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

-- --------------------------------------------------------

--
-- Struktura tabulky `ms21_ownership`
--

CREATE TABLE `ms21_ownership` (
  `uid` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `card_data` text COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ms21_session`
--

CREATE TABLE `ms21_session` (
  `team_id` int(11) NOT NULL,
  `t_stamp` int(11) DEFAULT 0,
  `command` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `abortable` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ms21_teams`
--

CREATE TABLE `ms21_teams` (
  `id` int(11) NOT NULL,
  `name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `login` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `hotp` varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
  `counter` int(11) DEFAULT 0,
  `score` int(11) NOT NULL DEFAULT 0,
  `cash` int(11) DEFAULT 0,
  `data` text COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ms21_team_history`
--

CREATE TABLE `ms21_team_history` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `cash` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `tstamp` varchar(31) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Klíče pro exportované tabulky
--

--
-- Klíče pro tabulku `ms21_auction`
--
ALTER TABLE `ms21_auction`
  ADD PRIMARY KEY (`uid`);

--
-- Klíče pro tabulku `ms21_cards`
--
ALTER TABLE `ms21_cards`
  ADD PRIMARY KEY (`id`);

--
-- Klíče pro tabulku `ms21_ownership`
--
ALTER TABLE `ms21_ownership`
  ADD PRIMARY KEY (`uid`);

--
-- Klíče pro tabulku `ms21_session`
--
ALTER TABLE `ms21_session`
  ADD PRIMARY KEY (`team_id`);

--
-- Klíče pro tabulku `ms21_teams`
--
ALTER TABLE `ms21_teams`
  ADD PRIMARY KEY (`id`);

--
-- Klíče pro tabulku `ms21_team_history`
--
ALTER TABLE `ms21_team_history`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `ms21_auction`
--
ALTER TABLE `ms21_auction`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `ms21_cards`
--
ALTER TABLE `ms21_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `ms21_ownership`
--
ALTER TABLE `ms21_ownership`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `ms21_teams`
--
ALTER TABLE `ms21_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `ms21_team_history`
--
ALTER TABLE `ms21_team_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
