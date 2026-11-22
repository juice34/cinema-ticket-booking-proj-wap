-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2026 at 04:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `movie_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookkings`
--

CREATE TABLE `bookkings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `movie` varchar(150) NOT NULL,
  `showtime` varchar(100) NOT NULL,
  `seat` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(15) NOT NULL DEFAULT 'Confirmed',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(20) DEFAULT NULL,
  `payment_ref` varchar(30) DEFAULT NULL,
  `show_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookkings`
--

INSERT INTO `bookkings` (`id`, `name`, `email`, `movie`, `showtime`, `seat`, `quantity`, `total_price`, `status`, `created_at`, `payment_method`, `payment_ref`, `show_date`) VALUES
(3, 'joshua', 'joshua@gmail.com', 'The Conjuring: Last Rites', '10:00 AM', 'A1', 1, 350.00, 'Finished', '2026-11-05 07:50:58', NULL, NULL, '2026-11-05'),
(4, 'joshua', 'joshua@gmail.com', 'The Conjuring: Last Rites', '10:00 AM', 'A1', 1, 350.00, 'Finished', '2026-11-05 07:51:53', NULL, NULL, '2026-11-05'),
(5, 'joshua', 'joshua@gmail.com', 'The Conjuring: Last Rites', '10:00 AM', 'A1', 1, 350.00, 'Finished', '2026-11-05 07:52:17', NULL, NULL, '2026-11-05'),
(6, 'joshua', 'joshua@gmail.com', 'Fury', '4:00 PM', 'A1', 1, 250.00, 'Finished', '2026-11-05 08:11:59', NULL, NULL, '2026-11-05'),
(7, 'joshua', 'joshua@gmail.com', 'Fury', '4:00 PM', 'A1', 1, 250.00, 'Finished', '2026-11-05 08:12:57', NULL, NULL, '2026-11-05'),
(8, 'joshua', 'joshua@gmail.com', 'Fury', '10:00 PM', 'A1', 1, 250.00, 'Finished', '2026-11-05 08:20:31', NULL, NULL, '2026-11-05'),
(9, 'joshua', 'joshua@gmail.com', 'Fury', '10:00 PM', 'A1', 1, 250.00, 'Finished', '2026-11-05 08:20:48', NULL, NULL, '2026-11-05'),
(10, 'joshua', 'joshua@gmail.com', 'The Conjuring: Last Rites', '1:00 PM', 'A1,E8', 2, 700.00, 'Finished', '2026-11-05 08:42:53', 'GCash', 'AACF3825AA', '2026-11-05'),
(11, 'joshua', 'joshua@gmail.com', 'The Conjuring: Last Rites', '1:00 PM', 'B1,A2', 2, 700.00, 'Finished', '2026-11-05 08:43:53', 'Maya', '9EC2FF03E1', '2026-11-05'),
(12, 'joshua', 'joshua@gmail.com', 'The Conjuring: Last Rites', '7:00 PM', 'A1,B1', 2, 700.00, 'Finished', '2026-11-05 09:15:26', 'GCash', '19FDF67638', '2026-11-05'),
(13, 'joshua', 'joshua@gmail.com', 'The Conjuring: Last Rites', '10:00 PM', 'A1', 1, 350.00, 'Finished', '2026-11-05 09:21:30', 'GCash', '294FE0BDBF', '2026-11-05'),
(14, 'joshua', 'joshua@gmail.com', 'The Conjuring: Last Rites', '10:00 PM', 'B1', 1, 350.00, 'Finished', '2026-11-05 09:21:54', 'Maya', 'E4FABDE427', '2026-11-05'),
(15, 'joshua', 'joshua@gmail.com', 'Evil Dead Burn', '10:00 AM', 'A1', 1, 450.00, 'Finished', '2026-11-05 09:27:02', 'GCash', 'E409506685', '2026-11-05'),
(16, 'joshua', 'joshua@gmail.com', 'Evil Dead Burn', '10:00 PM', 'A1,B1,C1,D1,E1', 5, 2250.00, 'Finished', '2026-11-05 09:35:48', 'GCash', '9B044457D0', '2026-11-05'),
(17, 'joshua', 'joshua@gmail.com', 'Evil Dead Burn', '10:00 AM', 'A1,B1,C1,D1,D2,C2,B2', 8, 3600.00, 'Finished', '2026-11-05 09:37:40', 'Maya', '732CE5DF50', '2026-11-05'),
(18, 'ken', 'ken@gmail.com', 'Evil Dead Burn', '10:00 AM', 'B1', 1, 450.00, 'Finished', '2026-11-15 19:15:17', NULL, NULL, '2026-11-15'),
(19, 'keny', 'keny@gmail.com', 'Evil Dead Burn', '10:00 AM', 'A1', 1, 450.00, 'Finished', '2026-11-15 20:36:15', 'GCash', 'DCB438F126', '2026-11-15'),
(20, 'keny', 'keny@gmail.com', 'Evil Dead Burn', '10:00 AM', 'A1', 1, 450.00, 'Finished', '2026-11-15 20:38:02', 'Maya', 'AD579A9B00', '2026-11-15'),
(21, 'keny', 'keny@gmail.com', 'Fury', '10:00 AM', 'B1,A1', 2, 600.00, 'Finished', '2026-11-15 20:46:25', 'GCash', '3EE9032869', '2026-11-15'),
(22, 'keny', 'keny@gmail.com', 'Fury', '10:00 AM', 'A1,B1', 2, 600.00, 'Finished', '2026-11-15 21:26:44', 'Maya', '52A4DE9692', '2026-11-15'),
(23, 'keny', 'keny@gmail.com', 'The Conjuring: Last Rites', '10:00 AM', 'A1', 1, 350.00, 'Finished', '2026-11-15 21:32:02', 'GCash', '0C36E12AD0', '2026-11-15'),
(24, 'keny', 'keny@gmail.com', 'The Dark Knight', '10:00 AM', 'A1', 1, 350.00, 'Finished', '2026-11-15 21:48:52', 'GCash', 'B627CC1B66', '2026-11-15'),
(25, 'keny', 'keny@gmail.com', 'The Dark Knight', '10:00 AM', 'A1', 1, 350.00, 'Finished', '2026-11-17 18:02:24', 'Maya', '981D593F43', '2026-11-17'),
(26, 'keny', 'keny@gmail.com', 'The Dark Knight', '10:00 AM', 'A1,B1,C1', 3, 1050.00, 'Finished', '2026-11-17 18:11:20', 'GCash', '48BD458E7A', '2026-11-17'),
(27, 'keny', 'keny@gmail.com', 'The Lord of the Rings: The Return of the King', '1:00 PM', 'B1,C1', 2, 500.00, 'Finished', '2026-11-17 18:54:22', 'GCash', 'E70E97326D', '2026-11-17'),
(28, 'keny', 'keny@gmail.com', 'The Lord of the Rings: The Return of the King', '4:00 PM', 'A1,B1', 2, 500.00, 'Finished', '2026-11-17 19:00:12', 'GCash', 'B9046C92BA', '2026-11-17'),
(29, 'keny', 'keny@gmail.com', 'The Dark Knight', '1:00 PM', 'A1,B1', 2, 700.00, 'Cancelled', '2026-11-17 19:07:14', NULL, NULL, '2026-11-17'),
(30, 'keny', 'keny@gmail.com', 'The Dark Knight', '10:00 PM', 'A1,A2,A3,A4,A5', 5, 1750.00, 'Finished', '2026-11-17 19:07:38', 'Maya', 'D21C047CB1', '2026-11-17'),
(31, 'keny', 'keny@gmail.com', 'Fury', '10:00 AM', 'A1,B1', 2, 600.00, 'Finished', '2026-11-17 19:11:52', 'GCash', 'A86B7F9F40', '2026-11-17'),
(32, 'Aleza Mae Capricho', 'mae@gmail.com', 'The Lord of the Rings: The Return of the King', '7:00 PM', 'C6', 1, 250.00, 'Finished', '2026-11-17 19:18:51', 'GCash', '3F77B371AD', '2026-11-17'),
(33, 'keny', 'keny@gmail.com', 'The Dark Knight', '1:00 PM', 'A1', 1, 350.00, 'Pending', '2026-11-19 02:25:10', NULL, NULL, '2026-11-19'),
(34, 'keny', 'keny@gmail.com', 'Evil Dead Burn', '1:00 PM', 'A1,B1', 2, 900.00, 'Pending', '2026-11-19 02:48:01', NULL, NULL, '2026-11-19'),
(35, 'keny', 'keny@gmail.com', 'Evil Dead Burn', '10:00 AM', 'A1', 1, 450.00, 'Pending', '2026-11-19 02:48:12', NULL, NULL, '2026-11-19'),
(36, 'keny', 'keny@gmail.com', 'Evil Dead Burn', '10:00 AM', 'B1', 1, 450.00, 'Finished', '2026-11-19 02:48:48', 'Maya', '86B3587A8D', '2026-11-19'),
(37, 'keny', 'keny@gmail.com', 'The Dark Knight', '10:00 AM', 'A1,B1', 2, 700.00, 'Pending', '2026-11-19 19:44:20', NULL, NULL, '2026-11-19'),
(38, 'kenn', 'kenn@gmail.com', 'Spider-Man Brand New Day', '10:00 AM', 'B1,A1,C1', 3, 900.00, 'Finished', '2026-11-19 19:45:40', 'GCash', '894CF5FEFF', '2026-11-19'),
(39, 'lance', 'camatura@gmail.com', 'Fury', '10:00 AM', 'E5', 1, 300.00, 'Finished', '2026-11-20 03:33:34', 'GCash', 'F816803AC5', '2026-11-20'),
(40, 'keny', 'keny@gmail.com', 'The Conjuring: Last Rites', '10:00 AM', 'A1', 1, 350.00, 'Finished', '2026-11-20 04:23:45', 'GCash', '773435E1BD', '2026-11-20'),
(41, 'keny', 'keny@gmail.com', 'The Dark Knight', '4:00 PM', 'A1,B1', 2, 700.00, 'Finished', '2026-11-20 08:56:06', 'GCash', 'FEF42E6581', '2026-11-21'),
(42, 'keny', 'keny@gmail.com', 'The Dark Knight', '1:00 PM', 'E1,C1,D1,B1,A1', 5, 1750.00, 'Pending', '2026-11-20 09:42:53', NULL, NULL, '2026-11-21'),
(43, 'keny', 'keny@gmail.com', 'Spider-Man Brand New Day', '10:00 AM', 'C1,B1,A1', 3, 900.00, 'Finished', '2026-11-20 09:48:58', 'GCash', 'F9F41E083C', '2026-11-21'),
(44, 'keny', 'keny@gmail.com', 'Fury', '10:00 AM', 'B1,A1', 2, 600.00, 'Finished', '2026-11-20 20:58:49', 'GCash', 'E3992056BD', '2026-12-15'),
(45, 'keny', 'keny@gmail.com', 'The Conjuring: Last Rites', '4:00 PM', 'C1,B1', 2, 700.00, 'Finished', '2026-11-22 06:50:12', 'GCash', '74DAE2F102', '2026-11-23'),
(46, 'keny', 'keny@gmail.com', 'The Conjuring: Last Rites', '10:00 AM', 'A1,C1,B1', 3, 1050.00, 'Finished', '2026-11-22 07:12:49', 'GCash', 'F295A7D694', '2026-11-24'),
(47, 'keny', 'keny@gmail.com', 'The Conjuring: Last Rites', '4:00 PM', 'B1,A1,A2,B2,C1,C2', 6, 2100.00, 'Finished', '2026-11-22 07:18:02', 'Maya', '8FC81CC36C', '2026-11-24');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `rating` varchar(10) NOT NULL DEFAULT 'PG',
  `status` varchar(10) NOT NULL DEFAULT 'now',
  `price` decimal(8,2) NOT NULL DEFAULT 250.00,
  `synopsis` text DEFAULT NULL,
  `cast_members` varchar(255) DEFAULT NULL,
  `runtime` varchar(20) DEFAULT NULL,
  `release_date` varchar(40) DEFAULT NULL,
  `genre` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `poster`, `rating`, `status`, `price`, `synopsis`, `cast_members`, `runtime`, `release_date`, `genre`) VALUES
(2, 'Fury', 'p_6ae9e6125b339.jpg', 'PG', 'now', 300.00, 'is a World War II war film written and directed by David Ayer that follows an American Sherman tank crew fighting deep inside Nazi Germany in April 1945. Led by battle-hardened Staff Sergeant Don \"Wardaddy\" Collier (Brad Pitt), the five-man crew faces overwhelming odds and brutal combat as they push behind enemy lines with a raw recruit', 'Brad Pitt. Brad Pitt · Brad Pitt · Shia LaBeouf. Shia LaBeouf · Shia LaBeouf · Logan Lerman. Logan Lerman · Logan Lerman · Michael Peña. Michael Peña.', '2hrs 14min.', 'October 17, 2014', 'action, adventure,history'),
(3, 'The Conjuring: Last Rites', 'p_6aeca59f2ca56.jpg', 'R', 'now', 350.00, 'supernatural horror film—the ninth installment in The Conjuring Universe and the final appearance of Patrick Wilson and Vera Farmiga as paranormal investigators Ed and Lorraine Warren. Set in 1986 (with flashbacks to 1964), the story follows the Warrens as they reluctantly take on one last major case involving the Smurl family in Pennsylvania, who are terrorized by a violent demonic entity tied to a cursed antique mirror', 'Patrick Wilson and Vera Farmiga alongside Mia Tomlinson and Ben Hardy', '2hrs 15mi.', 'September 5, 2025', 'mystery, thriller'),
(4, 'Avengers: Doomsday', 'p_6aecadc812393.jpeg', 'PG', 'soon', 399.00, 'Heroes from three different worlds must unite when they\'re thrust together to confront a catastrophic danger that could destroy everything they know.', 'Pedro Pascal, Chris Hemsworth, Robert Downey Jr.', '2h 50m', '16 December 2026', 'Action/Adventure'),
(6, 'The Dark Knight', 'p_6afa992d8e68f.jpg', 'PG', 'now', 350.00, 'follows Batman, Lieutenant Jim Gordon, and District Attorney Harvey Dent as they form an alliance to dismantle Gotham City\'s organized crime, only for their efforts to be derailed by the anarchistic Joker.', 'Christian Bale, Michael Caine, Heath Ledger, Gary Oldman, Aaron Eckhart, Maggie Gyllenhaal, and Morgan Freeman', '2hrs 32min.', 'July 18, 2008', 'action, crime,thriller'),
(7, 'The Lord of the Rings: The Return of the King', 'p_6afec8d30f34a.jpg', 'PG', 'now', 250.00, 'Frodo and Sam journey into Mordor to destroy the One Ring while Aragorn unites the forces of Men to confront Sauron\'s armies and claim his throne.', 'Elijah Wood · Frodo ; Viggo Mortensen · Aragorn ; Ian McKellen · Gandalf ; Noel Appleby · Everard Proudfoot ; Ali Astin · Elanor Gamgee', '3hrs 1min.', 'December 17, 2003', 'epic high fantasy, adventure, action'),
(8, 'Spider-Man Brand New Day', 'p_6afeca3af134d.jpg', 'PG', 'now', 300.00, 'Directed by Destin Daniel Cretton and released in July 2026, Spider-Man: Brand New Day follows an adult Peter Parker (Tom Holland) four years after the world forgot his identity. Living in isolation and working full-time as Spider-Man, he faces a physical evolution and a powerful telepathic threat in New York City.', 'Tom Holland as Peter Parker, Zendaya as MJ, and Jacob Batalon as Ned Leeds, alongside new additions such as Sadie Sink as Jean Grey, Mark Ruffalo as Bruce Banner, and Jon Bernthal as Frank Castle.', '2hrs 25min', 'July 31, 2026', 'action/adventure'),
(9, 'The Fantastic Four: First Steps', 'p_6afecc15477ca.jpg', 'PG', 'now', 400.00, 'The Fantastic Four: First Steps is a 2025 Marvel Studios film set on Earth-828, a retro-futuristic, 1960s-inspired alternate universe where Reed Richards, Sue Storm, Johnny Storm, and Ben Grimm have been established heroes for four years. Rather than retelling their origin story from scratch, the movie focuses on their family bond as Sue becomes pregnant with Franklin Richards just as Galactus and his herald, the Silver Surfer (Shalla-Bal), threaten to consume Earth', 'Pedro Pascal, Vanessa Kirby, Joseph Quinn, and Ebon Moss-Bachrach', '1hrs 55min.', 'July 25, 2025', 'superhero, action, adventure, and science fiction'),
(10, 'Never After Dark', 'p_6affb8801fa82.jpg', 'R', 'now', 150.00, 'Japanese-language supernatural horror film written and directed by Dave Boyle. It stars Moeka Hoshi as Airi, a medium with the ability to contact and exorcise lingering ghosts from a place. She is hired to remove a dangerous spirit from a remote house, but uncovers dark secrets in the process. The film\'s cast also includes Kurumi Inagaki, Kento Kaku, Tae Kimura and Bokuzō Masana.', 'Moeka Hoshi, Kurumi Inagaki, Kento Kaku, Mutsuo Yoshioka. Inagaki Miku', '1hrs 45min.', 'October 27, 2026', 'thriller,mystery'),
(11, 'Godzilla Minus Zero', 'p_6affca5cf2aa7.webp', 'PG', 'now', 300.00, 'War reduced Japan to zero, and Godzilla plunged it into minus. Two years have passed since then, and the country has faced agonizing struggles to achieve recovery and finally reclaim its daily life.\r\n\r\nJust as humanity managed to cling to a hard-won peace, a new threat strikes, crushing hope in an instant.', 'Minami Hamabe, Ryunosuke Kamiki, Miou Tanaka', '2h 15m', 'November 4,2026', 'Sci-Fi & Action-Adventure'),
(13, 'The Odyssey', 'omdb_tt33764258.jpg', 'R16', 'now', 300.00, 'After the Trojan War, Odysseus faces a dangerous voyage back to Ithaca, meeting creatures like the Cyclops Polyphemus, Sirens, and Calypso along the way.', 'Matt Damon, Tom Holland, Anne Hathaway', '2h 52m', '17 July 2026', 'Action, Adventure, Drama'),
(14, 'Spider-Man: Across the Spider-Verse', 'omdb_tt9362722.jpg', 'PG', 'now', 350.00, 'Miles Morales returns for the next chapter of the Oscar®-winning Spider-Verse saga, an epic adventure that will transport Brooklyn\'s full-time, friendly neighborhood Spider-Man across the Multiverse to join forces with Gwen Stacy and a new team of Spider-People to face off with a villain more powerful than anything they have ever encountered.', 'Shameik Moore, Hailee Steinfeld, Brian Tyree Henry', '2h 20m', '2 June 2023', 'Animation, Action, Adventure'),
(15, 'Joker', 'omdb_tt7286456.jpg', 'R16', 'now', 250.00, 'A socially inept clown for hire - Arthur Fleck aspires to be a stand up comedian among his small job working dressed as a clown holding a sign for advertising. He takes care of his mother, Penny Fleck, and as he learns more about his mental illness, he learns more about his past. Dealing with all the negativity and bullying from society, he heads downwards on a spiral, in turn showing how his alter ego, \"Joker,\" came to be.', 'Joaquin Phoenix, Robert De Niro, Zazie Beetz', '2h 2m', '4 October 2019', 'Drama'),
(16, 'The Notebook', 'omdb_tt0332280.jpg', 'R13', 'now', 250.00, 'With almost religious devotion, Duke, a kind octogenarian inmate of a peaceful nursing home, reads daily a captivating story from the worn-out pages of his leather-bound notebook to a fellow female patient. To keep her company, Duke recounts the fascinating love affair between impecunious but poetic country boy Noah and Allie, an affluent city girl. And little by little, Duke unfolds a Southern, lumber-scented summer romance beneath the tall trees of late 1930s North Carolina. Indeed, it seems as if the silent manuscript possesses the unfathomable power to penetrate the opaque clouds that enclose the silver-haired dame; slowly but surely, the enchanted lady becomes immersed in the strangely alluring fairy tale of the young ardent lovers\' highs and lows. But nobody knows what tomorrow holds. Are all summer loves doomed to fail?', 'Gena Rowlands, James Garner, Rachel McAdams', '2h 3m', '25 June 2004', 'Drama, Romance'),
(17, 'Coco', 'omdb_tt2380307.jpg', 'PG', 'now', 250.00, 'Despite his family\'s baffling generations-old ban on music, Miguel dreams of becoming an accomplished musician like his idol, Ernesto de la Cruz. Desperate to prove his talent, Miguel finds himself in the stunning and colorful Land of the Dead following a mysterious chain of events. Along the way, he meets charming trickster Hector, and together, they set off on an extraordinary journey to unlock the real story behind Miguel\'s family history.', 'Anthony Gonzalez, Gael García Bernal, Benjamin Bratt', '1h 45m', '22 November 2017', 'Animation, Adventure, Drama'),
(18, 'Evil Dead Burn', 'omdb_tt31170389.jpg', 'R16', 'now', 250.00, 'After the loss of her husband, a woman seeks solace with her in-laws in their secluded family home. As one by one they are transformed into Deadites—turning the gathering into a family reunion from hell—she comes to discover that the vows she took in life... live on even in death.', 'Souheila Yacoub, Tandi Wright, Hunter Doohan', '1h 50m', '10 July 2026', 'Horror'),
(19, '2 Valid IDs', 'omdb_tt43807653.jpg', 'PG', 'now', 250.00, 'A poor farmer, unable to claim a remittance because she lacks the required two valid IDs, finds herself in a situation that challenges her character, beliefs, and morality.', 'Pokwang, Joey Marquez, Zaijian Jaranilla', '1h 30m', '14 October 2026', 'Drama'),
(20, 'Project Hail Mary', 'omdb_tt12042730.jpg', 'R13', 'now', 250.00, 'Middle school science teacher Ryland Grace (Ryan Gosling) wakes up on a spaceship light-years from home with no recollection of who he is or how he got there. As his memory returns, he begins to uncover his mission: to solve the riddle of the mysterious substance that is causing the sun to die out. He must call on his scientific knowledge and unorthodox ideas to save everything on Earth from extinction... but an unexpected friendship means he may not have to do it alone.', 'Ryan Gosling, Sandra Hüller, James Ortiz', '2h 36m', '20 March 2026', 'Adventure, Comedy, Sci-Fi'),
(21, 'Always Yours, Never Mine', 'omdb_tt44112032.jpg', 'PG', 'now', 300.00, 'Across thirty years, several almosts, and timing that is never quite right, two people keep crossing paths – always on the verge of being together, but never quite making it. But when life finally offers them one last chance to rewrite their story, will they finally make the one choice they have spent a lifetime avoiding?', 'Joshua Garcia, Julia Barretto, Chanda Romero', '1h 52m', '30 September 2026', 'Drama, Romance'),
(22, 'Love, Ngo', 'omdb_tt42111424.jpg', 'PG', 'now', 150.00, '', 'Jerald Napoles, Candy Pangilinan, Gina Alajar', '', '3 June 2026', 'Comedy'),
(23, 'Dune: Part Three', 'p_6b0123dc9c241.jfif', 'PG', 'soon', 300.00, 'Emperor Paul Atreides faces the fallout from his ascent to power as political plots and a galaxy-wide holy war endanger the future only he can see.', 'Florence Pugh, Robert Pattinson, Jason Momoa, Timothée Chalamet, Zendaya .', '2h 30m', '16 December 2026', 'Sci-Fi & Adventure'),
(24, 'Whalefall', 'omdb_tt28535715.jpg', 'PG', 'now', 250.00, 'Follows a scuba diver who, while looking for his father\'s remains, is swallowed by an 80-foot, 60-ton sperm whale and has just one hour to get out before his oxygen runs out.', 'Josh Brolin, Elisabeth Shue, Emily Rudd', '', '15 October 2026', 'Adventure, Drama, Horror'),
(25, 'Pee Mak', 'omdb_tt44112588.jpg', 'PG', 'now', 250.00, '', 'Bea Binene, Wilbert Ross, Vandolph', '', '14 October 2026', 'Comedy, Fantasy, Horror');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 64, 'Natanggap ang bayad mo para sa The Lord of the Rings: The Return of the King. Hinihintay ang approval ng admin.', NULL, 1, '2026-11-18 02:54:31'),
(2, 64, 'Approved na ang booking mo para sa The Lord of the Rings: The Return of the King! Makikita na ang ticket/barcode mo.', NULL, 1, '2026-11-18 02:55:06'),
(3, 64, 'Natanggap ang bayad mo para sa The Lord of the Rings: The Return of the King. Hinihintay ang approval ng admin.', NULL, 1, '2026-11-18 03:00:31'),
(4, 64, 'Approved na ang booking mo para sa The Lord of the Rings: The Return of the King! Makikita na ang ticket/barcode mo.', NULL, 1, '2026-11-18 03:01:02'),
(5, 64, 'Natanggap ang bayad mo para sa The Dark Knight. Hinihintay ang approval ng admin.', NULL, 1, '2026-11-18 03:07:44'),
(6, 64, 'Approved na ang booking mo para sa The Dark Knight! Makikita na ang ticket/barcode mo.', NULL, 1, '2026-11-18 03:08:12'),
(7, 64, 'Natanggap ang bayad mo para sa Fury. Hinihintay ang approval ng admin.', 'pay.php?id=31', 1, '2026-11-18 03:11:59'),
(8, 64, 'Approved na ang booking mo para sa Fury! Makikita na ang ticket/barcode mo.', 'pay.php?id=31', 1, '2026-11-18 03:12:23'),
(9, 65, 'Natanggap ang bayad mo para sa The Lord of the Rings: The Return of the King. Hinihintay ang approval ng admin.', 'pay.php?id=32', 1, '2026-11-18 03:19:10'),
(10, 65, 'Na-reject ang bayad mo para sa The Lord of the Rings: The Return of the King. Makipag-ugnayan sa cinema o mag-book ulit.', 'pay.php?id=32', 1, '2026-11-18 03:20:03'),
(11, 65, 'Approved na ang booking mo para sa The Lord of the Rings: The Return of the King! Makikita na ang ticket/barcode mo.', 'pay.php?id=32', 0, '2026-11-19 10:48:38'),
(12, 64, 'Natanggap ang bayad mo para sa Evil Dead Burn. Hinihintay ang approval ng admin.', 'pay.php?id=36', 1, '2026-11-19 10:48:59'),
(13, 64, 'Approved na ang booking mo para sa Evil Dead Burn! Makikita na ang ticket/barcode mo.', 'pay.php?id=36', 1, '2026-11-19 10:49:08'),
(14, 66, 'Natanggap ang bayad mo para sa Spider-man brand new day. Hinihintay ang approval ng admin.', 'pay.php?id=38', 1, '2026-11-20 03:45:45'),
(15, 66, 'Approved na ang booking mo para sa Spider-man brand new day! Makikita na ang ticket/barcode mo.', 'pay.php?id=38', 1, '2026-11-20 03:46:08'),
(16, 67, 'Natanggap ang bayad mo para sa Fury. Hinihintay ang approval ng admin.', 'pay.php?id=39', 1, '2026-11-20 11:34:09'),
(17, 67, 'Approved na ang booking mo para sa Fury! Makikita na ang ticket/barcode mo.', 'pay.php?id=39', 1, '2026-11-20 11:34:20'),
(18, 64, 'Natanggap ang bayad mo para sa The Conjuring: Last Rites. Hinihintay ang approval ng admin.', 'pay.php?id=40', 1, '2026-11-20 12:24:03'),
(19, 64, 'Approved na ang booking mo para sa The Conjuring: Last Rites! Makikita na ang ticket/barcode mo.', 'pay.php?id=40', 1, '2026-11-20 12:24:32'),
(20, 64, 'Natanggap ang bayad mo para sa The Dark Knight (Sat, 21 Nov 2026, 4:00 PM). Hinihintay ang approval ng admin.', 'pay.php?id=41', 1, '2026-11-20 16:58:16'),
(21, 64, 'Approved na ang booking mo para sa The Dark Knight! Makikita na ang ticket/barcode mo.', 'pay.php?id=41', 1, '2026-11-20 16:58:26'),
(22, 64, 'Your payment for Spider-Man Brand New Day (Sat, 21 Nov 2026, 10:00 AM) was received. Waiting for admin approval.', 'pay.php?id=43', 1, '2026-11-20 17:49:06'),
(23, 64, 'Your booking for Spider-Man Brand New Day has been approved! Your ticket/barcode is now available.', 'pay.php?id=43', 1, '2026-11-20 17:49:17'),
(24, 64, 'Your payment for Fury (Tue, 15 Dec 2026, 10:00 AM) was received. Waiting for admin approval.', 'pay.php?id=44', 1, '2026-11-21 04:58:56'),
(25, 64, 'Your booking for Fury has been approved! Your ticket/barcode is now available.', 'pay.php?id=44', 1, '2026-11-21 04:59:05'),
(26, 64, 'Your payment for The Conjuring: Last Rites (Mon, 23 Nov 2026, 4:00 PM) was received. Waiting for admin approval.', 'pay.php?id=45', 1, '2026-11-22 14:50:17'),
(27, 64, 'Your booking for The Conjuring: Last Rites has been approved! Your ticket/barcode is now available.', 'pay.php?id=45', 1, '2026-11-22 14:50:28'),
(28, 64, 'Your payment for The Conjuring: Last Rites (Tue, 24 Nov 2026, 10:00 AM) was received. Waiting for admin approval.', 'pay.php?id=46', 1, '2026-11-22 15:12:53'),
(29, 64, 'Your booking for The Conjuring: Last Rites has been approved! Your ticket/barcode is now available.', 'pay.php?id=46', 1, '2026-11-22 15:13:05'),
(30, 64, 'Thank you for watching The Conjuring: Last Rites! We hope you enjoyed the show. See you again at the cinema.', NULL, 1, '2026-11-22 15:13:19'),
(31, 64, 'Your payment for The Conjuring: Last Rites (Tue, 24 Nov 2026, 4:00 PM) was received. Waiting for admin approval.', 'pay.php?id=47', 1, '2026-11-22 15:18:08'),
(32, 64, 'Your booking for The Conjuring: Last Rites has been approved! Your ticket/barcode is now available.', 'pay.php?id=47', 1, '2026-11-22 15:18:18'),
(33, 64, 'Thank you for watching The Conjuring: Last Rites! We hope you enjoyed the show. See you again at the cinema.', NULL, 1, '2026-11-22 15:18:32');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `k` varchar(50) NOT NULL,
  `v` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`k`, `v`) VALUES
('loc_address', 'esperanza mall meycauayan bulacan'),
('loc_lat', '14.735020'),
('loc_lng', '120.961217'),
('loc_name', 'movie booking');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(10) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'lance', 'lance@gmail.com', '$2y$10$sM9Up5TuyNZpEQaBYOKY4.haul0VMfyToT6r96xBX6cfF11Y6.m5i', 'user'),
(2, 'dion', 'dionpogi@gmail.com', '$2y$10$/QFadsF9JbwUZIAyRAhRJOdIJbF3HVLYB4jtF1FIDS2835obnfuUa', 'user'),
(3, 'kent', 'kenty@gmail.com', '$2y$10$WgCRyNqtxgAzBdS8v/Wz5uRAw0iBwB1qj3I.yc/ymNrWUDdm11yHi', 'user'),
(54, 'arrofo', 'arrofo@gmail.com', '$2y$10$deaFVXp9p1kKr6rL.qnSzeM.tPAXcGRK8Rj1eC8baWM7ormbTTk7q', 'user'),
(59, 'joshua', 'joshua@gmail.com', '$2y$10$kthmTVFUgXPb5zILcZZrs.5GD8vSr1wEvWuZRPxsOEjiKRu/0qbMO', 'user'),
(60, 'jos', 'josh@gmail.com', '$2y$10$J7kytf4KJng39lrq2F6liulT546R9pRYvAWkgGrWcYXeCvvgaPdlW', 'user'),
(61, 'ken', 'ken@gmail.com', '$2y$10$MYqE2H4thNf6clfTr52cmu/YlEJXS53JFoyyy9SeKt1Gosk5eQuay', 'user'),
(63, 'admin', 'admin@gmail.com', 'admin123', 'admin'),
(64, 'keny', 'keny@gmail.com', '$2y$10$SkcjHrBWc.d68HEA4qTZPOIsOYxqs7yYNwsS6N52CngXqvRX501lK', 'user'),
(65, 'Aleza Mae Capricho', 'mae@gmail.com', '$2y$10$SVLaWjhZXGQnofEQSz9WlemcvoudwVI/Of.pjzFLhXrd/tfiWbTzW', 'user'),
(66, 'kenn', 'kenn@gmail.com', '$2y$10$9kHMRCEPM77y2GRlG6rUDul7SR2.jM2mXlh07XlXh4PjkiUrkIpt6', 'user'),
(67, 'lance', 'camatura@gmail.com', '$2y$10$4orAjtdKVvo9HpC8uYScKe9zRIHsCE3AwtqdhRoZgPEErzE/YZyBW', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookkings`
--
ALTER TABLE `bookkings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`is_read`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token_hash` (`token_hash`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`k`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookkings`
--
ALTER TABLE `bookkings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
