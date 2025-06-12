-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 12-06-2025 a las 12:47:54
-- Versión del servidor: 5.7.23-23
-- Versión de PHP: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `eyewatch_newsroom`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configs`
--

CREATE TABLE `configs` (
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configs`
--

INSERT INTO `configs` (`name`, `value`) VALUES
('last_scrape_date', '2025-06-12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `covers`
--

CREATE TABLE `covers` (
  `id` int(11) NOT NULL,
  `country` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `preview_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_link` text COLLATE utf8mb4_unicode_ci,
  `scraped_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page_url` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `covers`
--

INSERT INTO `covers` (`id`, `country`, `title`, `image_url`, `thumbnail_url`, `original_url`, `preview_url`, `source`, `original_link`, `scraped_at`, `page_url`, `created_at`, `updated_at`) VALUES
(1, 'uk', 'Portada de The Guardian (Reino Unido)', '', 'images/covers/thumbnails/Portada_de_The_Guardian__Reino_Unido__684b1e9e7332d_thumb.webp', 'images/covers/Portada_de_The_Guardian__Reino_Unido__684b1e9e7332d_original.webp', '', 'https://es.kiosko.net/uk/np/guardian.html', 'https://img.kiosko.net/2025/06/12/uk/guardian.750.jpg', '2025-06-12 12:38:23', NULL, '2025-06-12 18:38:23', '2025-06-12 18:38:23'),
(2, 'uk', 'Portada de Financial Times (Reino Unido)', '', 'images/covers/thumbnails/Portada_de_Financial_Times__Reino_Unido__684b1ea059b52_thumb.webp', 'images/covers/Portada_de_Financial_Times__Reino_Unido__684b1ea059b52_original.webp', '', 'https://es.kiosko.net/uk/np/ft_uk.html', 'https://img.kiosko.net/2025/06/12/uk/ft_uk.750.jpg', '2025-06-12 12:38:25', NULL, '2025-06-12 18:38:25', '2025-06-12 18:38:25'),
(3, 'argentina', 'Clarín', '', 'images/covers/thumbnails/Clar__n_684b1ea20e0ed_thumb.webp', 'images/covers/Clar__n_684b1ea20e0ed_original.webp', '', 'https://es.kiosko.net/ar/np/ar_clarin.html', 'https://img.kiosko.net/2025/06/12/ar/ar_clarin.750.jpg', '2025-06-12 12:38:27', NULL, '2025-06-12 18:38:27', '2025-06-12 18:38:27'),
(4, 'argentina', 'La Nación', '', 'images/covers/thumbnails/La_Naci__n_684b1ea36bc17_thumb.webp', 'images/covers/La_Naci__n_684b1ea36bc17_original.webp', '', 'https://es.kiosko.net/ar/np/nacion.html', 'https://img.kiosko.net/2025/06/12/ar/nacion.750.jpg', '2025-06-12 12:38:28', NULL, '2025-06-12 18:38:28', '2025-06-12 18:38:28'),
(5, 'argentina', 'El Día de la Plata', '', 'images/covers/thumbnails/El_D__a_de_la_Plata_684b1ea49d883_thumb.webp', 'images/covers/El_D__a_de_la_Plata_684b1ea49d883_original.webp', '', 'https://es.kiosko.net/ar/np/ar_eldia.html', 'https://img.kiosko.net/2025/06/12/ar/ar_eldia.750.jpg', '2025-06-12 12:38:29', NULL, '2025-06-12 18:38:29', '2025-06-12 18:38:29'),
(6, 'argentina', 'La Capital - Rosario', '', 'images/covers/thumbnails/La_Capital_-_Rosario_684b1ea5d44d0_thumb.webp', 'images/covers/La_Capital_-_Rosario_684b1ea5d44d0_original.webp', '', 'https://es.kiosko.net/ar/np/ar_capital_rosario.html', 'https://img.kiosko.net/2025/06/12/ar/ar_capital_rosario.750.jpg', '2025-06-12 12:38:30', NULL, '2025-06-12 18:38:30', '2025-06-12 18:38:30'),
(7, 'argentina', 'La Voz del Interior', '', 'images/covers/thumbnails/La_Voz_del_Interior_684b1ea708c47_thumb.webp', 'images/covers/La_Voz_del_Interior_684b1ea708c47_original.webp', '', 'https://es.kiosko.net/ar/np/ar_voz_interior.html', 'https://img.kiosko.net/2025/06/12/ar/ar_voz_interior.750.jpg', '2025-06-12 12:38:31', NULL, '2025-06-12 18:38:31', '2025-06-12 18:38:31'),
(8, 'argentina', 'Olé', '', 'images/covers/thumbnails/Ol___684b1ea824e1c_thumb.webp', 'images/covers/Ol___684b1ea824e1c_original.webp', '', 'https://es.kiosko.net/ar/np/ole.html', 'https://img.kiosko.net/2025/06/12/ar/ole.750.jpg', '2025-06-12 12:38:32', NULL, '2025-06-12 18:38:32', '2025-06-12 18:38:32'),
(9, 'argentina', 'El Cronista Comercial', '', 'images/covers/thumbnails/El_Cronista_Comercial_684b1ea9438cc_thumb.webp', 'images/covers/El_Cronista_Comercial_684b1ea9438cc_original.webp', '', 'https://es.kiosko.net/ar/np/ar_cronista.html', 'https://img.kiosko.net/2025/06/12/ar/ar_cronista.750.jpg', '2025-06-12 12:38:34', NULL, '2025-06-12 18:38:34', '2025-06-12 18:38:34'),
(10, 'argentina', 'Rio Negro', '', 'images/covers/thumbnails/Rio_Negro_684b1eaa9d12f_thumb.webp', 'images/covers/Rio_Negro_684b1eaa9d12f_original.webp', '', 'https://es.kiosko.net/ar/np/ar_rio_negro.html', 'https://img.kiosko.net/2025/06/12/ar/ar_rio_negro.750.jpg', '2025-06-12 12:38:35', NULL, '2025-06-12 18:38:35', '2025-06-12 18:38:35'),
(11, 'argentina', 'El Ancasti', '', 'images/covers/thumbnails/El_Ancasti_684b1eac0af4e_thumb.webp', 'images/covers/El_Ancasti_684b1eac0af4e_original.webp', '', 'https://es.kiosko.net/ar/np/ar_ancasti.html', 'https://img.kiosko.net/2025/06/12/ar/ar_ancasti.750.jpg', '2025-06-12 12:38:36', NULL, '2025-06-12 18:38:36', '2025-06-12 18:38:36'),
(12, 'argentina', 'Diario Hoy', '', 'images/covers/thumbnails/Diario_Hoy_684b1ead3a31e_thumb.webp', 'images/covers/Diario_Hoy_684b1ead3a31e_original.webp', '', 'https://es.kiosko.net/ar/np/ar_hoy.html', 'https://img.kiosko.net/2025/06/12/ar/ar_hoy.750.jpg', '2025-06-12 12:38:38', NULL, '2025-06-12 18:38:38', '2025-06-12 18:38:38'),
(13, 'argentina', 'Diario La Capital - Mar del Plata', '', 'images/covers/thumbnails/Diario_La_Capital_-_Mar_del_Plata_684b1eae609e3_thumb.webp', 'images/covers/Diario_La_Capital_-_Mar_del_Plata_684b1eae609e3_original.webp', '', 'https://es.kiosko.net/ar/np/la_capital.html', 'https://img.kiosko.net/2025/06/12/ar/la_capital.750.jpg', '2025-06-12 12:38:39', NULL, '2025-06-12 18:38:39', '2025-06-12 18:38:39'),
(14, 'argentina', 'El Chubut', '', 'images/covers/thumbnails/El_Chubut_684b1eaf85fb2_thumb.webp', 'images/covers/El_Chubut_684b1eaf85fb2_original.webp', '', 'https://es.kiosko.net/ar/np/ar_chubut.html', 'https://img.kiosko.net/2025/06/12/ar/ar_chubut.750.jpg', '2025-06-12 12:38:40', NULL, '2025-06-12 18:38:40', '2025-06-12 18:38:40'),
(15, 'paraguay', 'La Nación', '', 'images/covers/thumbnails/La_Naci__n_684b1eb10aefb_thumb.webp', 'images/covers/La_Naci__n_684b1eb10aefb_original.webp', '', 'https://es.kiosko.net/py/np/nacion.html', 'https://img.kiosko.net/2025/06/12/py/nacion.750.jpg', '2025-06-12 12:38:42', NULL, '2025-06-12 18:38:42', '2025-06-12 18:38:42'),
(16, 'paraguay', 'Portada', '', 'images/covers/thumbnails/Portada_684b1eb334211_thumb.webp', 'images/covers/Portada_684b1eb334211_original.webp', '', 'https://www.adndigital.com.py/', 'https://adnpy.nyc3.digitaloceanspaces.com/wp-content/uploads/2025/06/tapa-9.jpeg', '2025-06-12 12:38:44', NULL, '2025-06-12 18:38:44', '2025-06-12 18:38:44'),
(17, 'paraguay', 'Portada', '', 'images/covers/thumbnails/Portada_684b1eb95b7a8_thumb.webp', 'images/covers/Portada_684b1eb95b7a8_original.webp', '', 'https://www.popular.com.py/', 'https://popular2.nyc3.cdn.digitaloceanspaces.com/wp-content/uploads/2025/06/11185210/WhatsApp-Image-2025-06-11-at-8.24.49-PM.jpeg', '2025-06-12 12:38:50', NULL, '2025-06-12 18:38:50', '2025-06-12 18:38:50'),
(18, 'paraguay', 'Portada', '', 'images/covers/thumbnails/Portada_684b1ebbf0f3d_thumb.webp', 'images/covers/Portada_684b1ebbf0f3d_original.webp', '', 'https://www.abc.com.py/edicion-impresa/', 'https://hojeable.s3-sa-east-1.amazonaws.com/ediciones/previews/TAPA-ABC-20250612-HIRES.jpg', '2025-06-12 12:38:54', NULL, '2025-06-12 18:38:54', '2025-06-12 18:38:54'),
(19, 'paraguay', 'Portada', '', 'images/covers/thumbnails/Portada_684b1ebf5d651_thumb.webp', 'images/covers/Portada_684b1ebf5d651_original.webp', '', 'https://www.extra.com.py/', 'https://grupovierci.brightspotcdn.com/dims4/default/ddb0fb6/2147483647/strip/true/crop/1102x1437+0+0/resize/1102x1437!/quality/90/?url=https%3A%2F%2Fk2-prod-grupo-vierci.s3.us-east-1.amazonaws.com%2Fbrightspot%2Fcb%2F9a%2F9f275f3a48a2b3c40f22acef5284%2Fwhatsapp-image-2025-06-11-at-21-41-30.jpeg', '2025-06-12 12:38:56', NULL, '2025-06-12 18:38:56', '2025-06-12 18:38:56'),
(20, 'brasil', 'O Globo', '', 'images/covers/thumbnails/O_Globo_684b1ec8ee76b_thumb.webp', 'images/covers/O_Globo_684b1ec8ee76b_original.webp', '', 'https://es.kiosko.net/br/np/br_oglobo.html', 'https://img.kiosko.net/2025/06/12/br/br_oglobo.750.jpg', '2025-06-12 12:39:06', NULL, '2025-06-12 18:39:06', '2025-06-12 18:39:06'),
(21, 'brasil', 'Folha de São Paulo', '', 'images/covers/thumbnails/Folha_de_S__o_Paulo_684b1ecaef273_thumb.webp', 'images/covers/Folha_de_S__o_Paulo_684b1ecaef273_original.webp', '', 'https://es.kiosko.net/br/np/br_folha_spaulo.html', 'https://img.kiosko.net/2025/06/12/br/br_folha_spaulo.750.jpg', '2025-06-12 12:39:08', NULL, '2025-06-12 18:39:08', '2025-06-12 18:39:08'),
(22, 'brasil', 'Extra', '', 'images/covers/thumbnails/Extra_684b1ecc66c88_thumb.webp', 'images/covers/Extra_684b1ecc66c88_original.webp', '', 'https://es.kiosko.net/br/np/br_extra.html', 'https://img.kiosko.net/2025/06/12/br/br_extra.750.jpg', '2025-06-12 12:39:09', NULL, '2025-06-12 18:39:09', '2025-06-12 18:39:09'),
(23, 'brasil', 'O Dia', '', 'images/covers/thumbnails/O_Dia_684b1ecdd4218_thumb.webp', 'images/covers/O_Dia_684b1ecdd4218_original.webp', '', 'https://es.kiosko.net/br/np/o_dia.html', 'https://img.kiosko.net/2025/06/12/br/o_dia.750.jpg', '2025-06-12 12:39:10', NULL, '2025-06-12 18:39:10', '2025-06-12 18:39:10'),
(24, 'brasil', 'O Estado de São Paulo', '', 'images/covers/thumbnails/O_Estado_de_S__o_Paulo_684b1ecf1864a_thumb.webp', 'images/covers/O_Estado_de_S__o_Paulo_684b1ecf1864a_original.webp', '', 'https://es.kiosko.net/br/np/br_estado_spaulo.html', 'https://img.kiosko.net/2025/06/12/br/br_estado_spaulo.750.jpg', '2025-06-12 12:39:12', NULL, '2025-06-12 18:39:12', '2025-06-12 18:39:12'),
(25, 'brasil', 'Correio Braziliense', '', 'images/covers/thumbnails/Correio_Braziliense_684b1ed05d51b_thumb.webp', 'images/covers/Correio_Braziliense_684b1ed05d51b_original.webp', '', 'https://es.kiosko.net/br/np/correio_braziliense.html', 'https://img.kiosko.net/2025/06/12/br/correio_braziliense.750.jpg', '2025-06-12 12:39:13', NULL, '2025-06-12 18:39:13', '2025-06-12 18:39:13'),
(26, 'brasil', 'Diário A Tarde', '', 'images/covers/thumbnails/Di__rio_A_Tarde_684b1ed198e7f_thumb.webp', 'images/covers/Di__rio_A_Tarde_684b1ed198e7f_original.webp', '', 'https://es.kiosko.net/br/np/br_atarde.html', 'https://img.kiosko.net/2025/06/12/br/br_atarde.750.jpg', '2025-06-12 12:39:14', NULL, '2025-06-12 18:39:14', '2025-06-12 18:39:14'),
(27, 'brasil', 'Correio*', '', 'images/covers/thumbnails/Correio__684b1ed31eedc_thumb.webp', 'images/covers/Correio__684b1ed31eedc_original.webp', '', 'https://es.kiosko.net/br/np/correio_bahia.html', 'https://img.kiosko.net/2025/06/12/br/correio_bahia.750.jpg', '2025-06-12 12:39:15', NULL, '2025-06-12 18:39:15', '2025-06-12 18:39:15'),
(28, 'brasil', 'Correio Do Povo', '', 'images/covers/thumbnails/Correio_Do_Povo_684b1ed42b45a_thumb.webp', 'images/covers/Correio_Do_Povo_684b1ed42b45a_original.webp', '', 'https://es.kiosko.net/br/np/correio_povo.html', 'https://img.kiosko.net/2025/06/12/br/correio_povo.750.jpg', '2025-06-12 12:39:17', NULL, '2025-06-12 18:39:17', '2025-06-12 18:39:17'),
(29, 'usa', 'New York Times', '', 'images/covers/thumbnails/New_York_Times_684b1ed597954_thumb.webp', 'images/covers/New_York_Times_684b1ed597954_original.webp', '', 'https://es.kiosko.net/us/np/newyork_times.html', 'https://img.kiosko.net/2025/06/12/us/newyork_times.750.jpg', '2025-06-12 12:39:18', NULL, '2025-06-12 18:39:18', '2025-06-12 18:39:18'),
(30, 'usa', 'USA Today', '', 'images/covers/thumbnails/USA_Today_684b1ed733692_thumb.webp', 'images/covers/USA_Today_684b1ed733692_original.webp', '', 'https://es.kiosko.net/us/np/usa_today.html', 'https://img.kiosko.net/2025/06/12/us/usa_today.750.jpg', '2025-06-12 12:39:20', NULL, '2025-06-12 18:39:20', '2025-06-12 18:39:20'),
(31, 'usa', 'The Washington Post', '', 'images/covers/thumbnails/The_Washington_Post_684b1ed8ad542_thumb.webp', 'images/covers/The_Washington_Post_684b1ed8ad542_original.webp', '', 'https://es.kiosko.net/us/np/washington_post.html', 'https://img.kiosko.net/2025/06/12/us/washington_post.750.jpg', '2025-06-12 12:39:21', NULL, '2025-06-12 18:39:21', '2025-06-12 18:39:21'),
(32, 'usa', 'Boston Globe', '', 'images/covers/thumbnails/Boston_Globe_684b1ed9ee8b5_thumb.webp', 'images/covers/Boston_Globe_684b1ed9ee8b5_original.webp', '', 'https://es.kiosko.net/us/np/boston_globe.html', 'https://img.kiosko.net/2025/06/12/us/boston_globe.750.jpg', '2025-06-12 12:39:22', NULL, '2025-06-12 18:39:22', '2025-06-12 18:39:22'),
(33, 'usa', 'Dallas Morning News', '', 'images/covers/thumbnails/Dallas_Morning_News_684b1edb456de_thumb.webp', 'images/covers/Dallas_Morning_News_684b1edb456de_original.webp', '', 'https://es.kiosko.net/us/np/dallas_morning_news.html', 'https://img.kiosko.net/2025/06/12/us/dallas_morning_news.750.jpg', '2025-06-12 12:39:24', NULL, '2025-06-12 18:39:24', '2025-06-12 18:39:24'),
(34, 'usa', 'Wall Street Journal', '', 'images/covers/thumbnails/Wall_Street_Journal_684b1edc839a3_thumb.webp', 'images/covers/Wall_Street_Journal_684b1edc839a3_original.webp', '', 'https://es.kiosko.net/us/np/wsj.html', 'https://img.kiosko.net/2025/06/12/us/wsj.750.jpg', '2025-06-12 12:39:25', NULL, '2025-06-12 18:39:25', '2025-06-12 18:39:25'),
(35, 'usa', 'Newsweek', '', 'images/covers/thumbnails/Newsweek_684b1eddea997_thumb.webp', 'images/covers/Newsweek_684b1eddea997_original.webp', '', 'https://es.kiosko.net/us/np/newsweek.html', 'https://img.kiosko.net/2025/06/12/us/newsweek.750.jpg', '2025-06-12 12:39:26', NULL, '2025-06-12 18:39:26', '2025-06-12 18:39:26'),
(36, 'usa', 'Time Magazine', '', 'images/covers/thumbnails/Time_Magazine_684b1eded14c9_thumb.webp', 'images/covers/Time_Magazine_684b1eded14c9_original.webp', '', 'https://es.kiosko.net/us/np/time.html', 'https://img.kiosko.net/2025/06/06/us/time.750.jpg', '2025-06-12 12:39:27', NULL, '2025-06-12 18:39:27', '2025-06-12 18:39:27'),
(37, 'usa', 'Los Angeles Times', '', 'images/covers/thumbnails/Los_Angeles_Times_684b1edfe8fc3_thumb.webp', 'images/covers/Los_Angeles_Times_684b1edfe8fc3_original.webp', '', 'https://es.kiosko.net/us/np/latimes.html', 'https://img.kiosko.net/2025/06/12/us/latimes.750.jpg', '2025-06-12 12:39:29', NULL, '2025-06-12 18:39:29', '2025-06-12 18:39:29'),
(38, 'usa', 'Chicago Tribune', '', 'images/covers/thumbnails/Chicago_Tribune_684b1ee194e43_thumb.webp', 'images/covers/Chicago_Tribune_684b1ee194e43_original.webp', '', 'https://es.kiosko.net/us/np/chicago_tribune.html', 'https://img.kiosko.net/2025/06/12/us/chicago_tribune.750.jpg', '2025-06-12 12:39:30', NULL, '2025-06-12 18:39:30', '2025-06-12 18:39:30'),
(39, 'usa', 'Houston Chronicle', '', 'images/covers/thumbnails/Houston_Chronicle_684b1ee2d34d0_thumb.webp', 'images/covers/Houston_Chronicle_684b1ee2d34d0_original.webp', '', 'https://es.kiosko.net/us/np/houston_chronicle.html', 'https://img.kiosko.net/2025/06/12/us/houston_chronicle.750.jpg', '2025-06-12 12:39:32', NULL, '2025-06-12 18:39:32', '2025-06-12 18:39:32'),
(40, 'usa', 'New York Post', '', 'images/covers/thumbnails/New_York_Post_684b1ee450869_thumb.webp', 'images/covers/New_York_Post_684b1ee450869_original.webp', '', 'https://es.kiosko.net/us/np/newyork_post.html', 'https://img.kiosko.net/2025/06/12/us/newyork_post.750.jpg', '2025-06-12 12:39:33', NULL, '2025-06-12 18:39:33', '2025-06-12 18:39:33'),
(41, 'usa', 'San Francisco Chronicle', '', 'images/covers/thumbnails/San_Francisco_Chronicle_684b1ee56fab7_thumb.webp', 'images/covers/San_Francisco_Chronicle_684b1ee56fab7_original.webp', '', 'https://es.kiosko.net/us/np/sf_chronicle.html', 'https://img.kiosko.net/2025/06/12/us/sf_chronicle.750.jpg', '2025-06-12 12:39:34', NULL, '2025-06-12 18:39:34', '2025-06-12 18:39:34'),
(42, 'uruguay', 'El País', '', 'images/covers/thumbnails/El_Pa__s_684b1ee737f43_thumb.webp', 'images/covers/El_Pa__s_684b1ee737f43_original.webp', '', 'https://es.kiosko.net/uy/np/uy_elpais.html', 'https://img.kiosko.net/2025/06/12/uy/uy_elpais.750.jpg', '2025-06-12 12:39:36', NULL, '2025-06-12 18:39:36', '2025-06-12 18:39:36'),
(43, 'uruguay', 'La Diaria', '', 'images/covers/thumbnails/La_Diaria_684b1ee89d2fb_thumb.webp', 'images/covers/La_Diaria_684b1ee89d2fb_original.webp', '', 'https://es.kiosko.net/uy/np/uy_ladiaria.html', 'https://img.kiosko.net/2025/06/12/uy/uy_ladiaria.750.jpg', '2025-06-12 12:39:37', NULL, '2025-06-12 18:39:37', '2025-06-12 18:39:37'),
(44, 'uruguay', 'El Telégrafo', '', 'images/covers/thumbnails/El_Tel__grafo_684b1ee98c701_thumb.webp', 'images/covers/El_Tel__grafo_684b1ee98c701_original.webp', '', 'https://es.kiosko.net/uy/np/uy_telegrafo.html', 'https://img.kiosko.net/2025/06/12/uy/uy_telegrafo.750.jpg', '2025-06-12 12:39:38', NULL, '2025-06-12 18:39:38', '2025-06-12 18:39:38'),
(45, 'chile', 'El Mercurio', '', 'images/covers/thumbnails/El_Mercurio_684b1eec25ae4_thumb.webp', 'images/covers/El_Mercurio_684b1eec25ae4_original.webp', '', 'https://es.kiosko.net/cl/np/cl_mercurio.html', 'https://img.kiosko.net/2025/06/12/cl/cl_mercurio.750.jpg', '2025-06-12 12:39:41', NULL, '2025-06-12 18:39:41', '2025-06-12 18:39:41'),
(46, 'chile', 'La Tercera', '', 'images/covers/thumbnails/La_Tercera_684b1eedaec96_thumb.webp', 'images/covers/La_Tercera_684b1eedaec96_original.webp', '', 'https://es.kiosko.net/cl/np/cl_tercera.html', 'https://img.kiosko.net/2025/06/12/cl/cl_tercera.750.jpg', '2025-06-12 12:39:42', NULL, '2025-06-12 18:39:42', '2025-06-12 18:39:42'),
(47, 'chile', 'Diario Chañarcillo', '', 'images/covers/thumbnails/Diario_Cha__arcillo_684b1eeedd73f_thumb.webp', 'images/covers/Diario_Cha__arcillo_684b1eeedd73f_original.webp', '', 'https://es.kiosko.net/cl/np/cl_chanarcillo.html', 'https://img.kiosko.net/2025/06/12/cl/cl_chanarcillo.750.jpg', '2025-06-12 12:39:43', NULL, '2025-06-12 18:39:43', '2025-06-12 18:39:43'),
(48, 'chile', 'Diario La Región de Coquimbo', '', 'images/covers/thumbnails/Diario_La_Regi__n_de_Coquimbo_684b1ef0198c8_thumb.webp', 'images/covers/Diario_La_Regi__n_de_Coquimbo_684b1ef0198c8_original.webp', '', 'https://es.kiosko.net/cl/np/region_coquimbo.html', 'https://img.kiosko.net/2025/06/12/cl/region_coquimbo.750.jpg', '2025-06-12 12:39:45', NULL, '2025-06-12 18:39:45', '2025-06-12 18:39:45'),
(49, 'chile', 'Diario de Concepción', '', 'images/covers/thumbnails/Diario_de_Concepci__n_684b1ef16e289_thumb.webp', 'images/covers/Diario_de_Concepci__n_684b1ef16e289_original.webp', '', 'https://es.kiosko.net/cl/np/diario_concepcion.html', 'https://img.kiosko.net/2025/06/12/cl/diario_concepcion.750.jpg', '2025-06-12 12:39:46', NULL, '2025-06-12 18:39:46', '2025-06-12 18:39:46'),
(50, 'chile', 'El Pingüino', '', 'images/covers/thumbnails/El_Ping__ino_684b1ef28ef4c_thumb.webp', 'images/covers/El_Ping__ino_684b1ef28ef4c_original.webp', '', 'https://es.kiosko.net/cl/np/pinguino.html', 'https://img.kiosko.net/2025/06/12/cl/pinguino.750.jpg', '2025-06-12 12:39:47', NULL, '2025-06-12 18:39:47', '2025-06-12 18:39:47'),
(51, 'colombia', 'El Espectador', '', 'images/covers/thumbnails/El_Espectador_684b1ef407ab5_thumb.webp', 'images/covers/El_Espectador_684b1ef407ab5_original.webp', '', 'https://es.kiosko.net/co/np/co_espectador.html', 'https://img.kiosko.net/2025/06/12/co/co_espectador.750.jpg', '2025-06-12 12:39:49', NULL, '2025-06-12 18:39:49', '2025-06-12 18:39:49'),
(52, 'colombia', 'El Heraldo', '', 'images/covers/thumbnails/El_Heraldo_684b1ef5517f8_thumb.webp', 'images/covers/El_Heraldo_684b1ef5517f8_original.webp', '', 'https://es.kiosko.net/co/np/co_heraldo.html', 'https://img.kiosko.net/2025/06/12/co/co_heraldo.750.jpg', '2025-06-12 12:39:50', NULL, '2025-06-12 18:39:50', '2025-06-12 18:39:50'),
(53, 'colombia', 'El Universal', '', 'images/covers/thumbnails/El_Universal_684b1ef6758e5_thumb.webp', 'images/covers/El_Universal_684b1ef6758e5_original.webp', '', 'https://es.kiosko.net/co/np/co_universal.html', 'https://img.kiosko.net/2025/06/12/co/co_universal.750.jpg', '2025-06-12 12:39:51', NULL, '2025-06-12 18:39:51', '2025-06-12 18:39:51'),
(54, 'colombia', 'La Patria', '', 'images/covers/thumbnails/La_Patria_684b1ef795ba3_thumb.webp', 'images/covers/La_Patria_684b1ef795ba3_original.webp', '', 'https://es.kiosko.net/co/np/co_patria.html', 'https://img.kiosko.net/2025/06/12/co/co_patria.750.jpg', '2025-06-12 12:39:52', NULL, '2025-06-12 18:39:52', '2025-06-12 18:39:52'),
(55, 'ecuador', 'Expreso', '', 'images/covers/thumbnails/Expreso_684b1ef90fcee_thumb.webp', 'images/covers/Expreso_684b1ef90fcee_original.webp', '', 'https://es.kiosko.net/ec/np/ec_expreso.html', 'https://img.kiosko.net/2025/06/12/ec/ec_expreso.750.jpg', '2025-06-12 12:39:54', NULL, '2025-06-12 18:39:54', '2025-06-12 18:39:54'),
(56, 'peru', 'El Comercio', '', 'images/covers/thumbnails/El_Comercio_684b1efaca740_thumb.webp', 'images/covers/El_Comercio_684b1efaca740_original.webp', '', 'https://es.kiosko.net/pe/np/pe_comercio.html', 'https://img.kiosko.net/2025/06/12/pe/pe_comercio.750.jpg', '2025-06-12 12:39:55', NULL, '2025-06-12 18:39:55', '2025-06-12 18:39:55'),
(57, 'peru', 'La Republica', '', 'images/covers/thumbnails/La_Republica_684b1efc249e6_thumb.webp', 'images/covers/La_Republica_684b1efc249e6_original.webp', '', 'https://es.kiosko.net/pe/np/pe_republica.html', 'https://img.kiosko.net/2025/06/12/pe/pe_republica.750.jpg', '2025-06-12 12:39:57', NULL, '2025-06-12 18:39:57', '2025-06-12 18:39:57'),
(58, 'peru', 'Líbero', '', 'images/covers/thumbnails/L__bero_684b1efd6778e_thumb.webp', 'images/covers/L__bero_684b1efd6778e_original.webp', '', 'https://es.kiosko.net/pe/np/pe_libero.html', 'https://img.kiosko.net/2025/06/12/pe/pe_libero.750.jpg', '2025-06-12 12:39:58', NULL, '2025-06-12 18:39:58', '2025-06-12 18:39:58'),
(59, 'venezuela', 'El Nacional', '', 'images/covers/thumbnails/El_Nacional_684b1efebd293_thumb.webp', 'images/covers/El_Nacional_684b1efebd293_original.webp', '', 'https://es.kiosko.net/ve/np/ve_nacional.html', 'https://img.kiosko.net/2025/06/12/ve/ve_nacional.750.jpg', '2025-06-12 12:39:59', NULL, '2025-06-12 18:39:59', '2025-06-12 18:39:59'),
(60, 'venezuela', '2001 - Dosmiluno', '', 'images/covers/thumbnails/2001_-_Dosmiluno_684b1f0020e24_thumb.webp', 'images/covers/2001_-_Dosmiluno_684b1f0020e24_original.webp', '', 'https://es.kiosko.net/ve/np/ve_2001.html', 'https://img.kiosko.net/2025/06/12/ve/ve_2001.750.jpg', '2025-06-12 12:40:00', NULL, '2025-06-12 18:40:00', '2025-06-12 18:40:00'),
(61, 'venezuela', 'Periodiquito de Aragua', '', 'images/covers/thumbnails/Periodiquito_de_Aragua_684b1f013f964_thumb.webp', 'images/covers/Periodiquito_de_Aragua_684b1f013f964_original.webp', '', 'https://es.kiosko.net/ve/np/periodiquito.html', 'https://img.kiosko.net/2025/06/12/ve/periodiquito.750.jpg', '2025-06-12 12:40:02', NULL, '2025-06-12 18:40:02', '2025-06-12 18:40:02'),
(62, 'venezuela', 'Universal', '', 'images/covers/thumbnails/Universal_684b1f02b5166_thumb.webp', 'images/covers/Universal_684b1f02b5166_original.webp', '', 'https://es.kiosko.net/ve/np/ve_universal.html', 'https://img.kiosko.net/2025/06/11/ve/ve_universal.750.jpg', '2025-06-12 12:40:03', NULL, '2025-06-12 18:40:03', '2025-06-12 18:40:03'),
(63, 'venezuela', 'Meridiano', '', 'images/covers/thumbnails/Meridiano_684b1f03e8793_thumb.webp', 'images/covers/Meridiano_684b1f03e8793_original.webp', '', 'https://es.kiosko.net/ve/np/ve_meridiano.html', 'https://img.kiosko.net/2025/06/12/ve/ve_meridiano.750.jpg', '2025-06-12 12:40:04', NULL, '2025-06-12 18:40:04', '2025-06-12 18:40:04'),
(64, 'bolivia', 'Tapa El Deber 12.06.2026', '', 'images/covers/thumbnails/Tapa_El_Deber_12_06_2026_684b1f062cd70_thumb.webp', 'images/covers/Tapa_El_Deber_12_06_2026_684b1f062cd70_original.webp', '', 'https://eldeber.com.bo/', 'https://eldeber.com.bo/sites/default/efsfiles/styles/large/public/2025-06/01_tapa_jueves_0.jpg?itok=gmDNoR-5', '2025-06-12 12:40:06', NULL, '2025-06-12 18:40:06', '2025-06-12 18:40:06'),
(65, 'bolivia', 'Portada', '', 'images/covers/thumbnails/Portada_684b1f0775749_thumb.webp', 'images/covers/Portada_684b1f0775749_original.webp', '', 'https://www.lostiempos.com/', 'https://www.lostiempos.com/sites/default/files/portadas/2025/6/01_5.jpg', '2025-06-12 12:40:08', NULL, '2025-06-12 18:40:08', '2025-06-12 18:40:08'),
(66, 'mexico', 'El Universal', '', 'images/covers/thumbnails/El_Universal_684b1f0915f70_thumb.webp', 'images/covers/El_Universal_684b1f0915f70_original.webp', '', 'https://es.kiosko.net/mx/np/mx_universal.html', 'https://img.kiosko.net/2025/06/12/mx/mx_universal.750.jpg', '2025-06-12 12:40:10', NULL, '2025-06-12 18:40:10', '2025-06-12 18:40:10'),
(67, 'mexico', 'Excelsior', '', 'images/covers/thumbnails/Excelsior_684b1f0a978e8_thumb.webp', 'images/covers/Excelsior_684b1f0a978e8_original.webp', '', 'https://es.kiosko.net/mx/np/mx_excelsior.html', 'https://img.kiosko.net/2025/06/12/mx/mx_excelsior.750.jpg', '2025-06-12 12:40:11', NULL, '2025-06-12 18:40:11', '2025-06-12 18:40:11'),
(68, 'mexico', 'La Jornada', '', 'images/covers/thumbnails/La_Jornada_684b1f0c25df2_thumb.webp', 'images/covers/La_Jornada_684b1f0c25df2_original.webp', '', 'https://es.kiosko.net/mx/np/mx_jornada.html', 'https://img.kiosko.net/2025/06/12/mx/mx_jornada.750.jpg', '2025-06-12 12:40:13', NULL, '2025-06-12 18:40:13', '2025-06-12 18:40:13'),
(69, 'mexico', 'Milenio', '', 'images/covers/thumbnails/Milenio_684b1f0d71e08_thumb.webp', 'images/covers/Milenio_684b1f0d71e08_original.webp', '', 'https://es.kiosko.net/mx/np/mx_milenio.html', 'https://img.kiosko.net/2025/06/12/mx/mx_milenio.750.jpg', '2025-06-12 12:40:14', NULL, '2025-06-12 18:40:14', '2025-06-12 18:40:14'),
(70, 'mexico', 'Reforma', '', 'images/covers/thumbnails/Reforma_684b1f0ec0751_thumb.webp', 'images/covers/Reforma_684b1f0ec0751_original.webp', '', 'https://es.kiosko.net/mx/np/mx_reforma.html', 'https://img.kiosko.net/2025/06/12/mx/mx_reforma.750.jpg', '2025-06-12 12:40:16', NULL, '2025-06-12 18:40:16', '2025-06-12 18:40:16'),
(71, 'mexico', 'Esto', '', 'images/covers/thumbnails/Esto_684b1f1066962_thumb.webp', 'images/covers/Esto_684b1f1066962_original.webp', '', 'https://es.kiosko.net/mx/np/mx_esto.html', 'https://img.kiosko.net/2025/06/12/mx/mx_esto.750.jpg', '2025-06-12 12:40:17', NULL, '2025-06-12 18:40:17', '2025-06-12 18:40:17'),
(72, 'mexico', 'El Financiero', '', 'images/covers/thumbnails/El_Financiero_684b1f11b34d3_thumb.webp', 'images/covers/El_Financiero_684b1f11b34d3_original.webp', '', 'https://es.kiosko.net/mx/np/mx_financiero.html', 'https://img.kiosko.net/2025/06/12/mx/mx_financiero.750.jpg', '2025-06-12 12:40:18', NULL, '2025-06-12 18:40:18', '2025-06-12 18:40:18'),
(73, 'mexico', 'El Economista', '', 'images/covers/thumbnails/El_Economista_684b1f130861c_thumb.webp', 'images/covers/El_Economista_684b1f130861c_original.webp', '', 'https://es.kiosko.net/mx/np/mx_eleconomista.html', 'https://img.kiosko.net/2025/06/12/mx/mx_eleconomista.750.jpg', '2025-06-12 12:40:19', NULL, '2025-06-12 18:40:19', '2025-06-12 18:40:19'),
(74, 'mexico', 'Diario de Yucatán', '', 'images/covers/thumbnails/Diario_de_Yucat__n_684b1f1442250_thumb.webp', 'images/covers/Diario_de_Yucat__n_684b1f1442250_original.webp', '', 'https://es.kiosko.net/mx/np/mx_diario_yucatan.html', 'https://img.kiosko.net/2025/06/12/mx/mx_diario_yucatan.750.jpg', '2025-06-12 12:40:21', NULL, '2025-06-12 18:40:21', '2025-06-12 18:40:21'),
(75, 'mexico', 'El Sol de México', '', 'images/covers/thumbnails/El_Sol_de_M__xico_684b1f15d0c9d_thumb.webp', 'images/covers/El_Sol_de_M__xico_684b1f15d0c9d_original.webp', '', 'https://es.kiosko.net/mx/np/mx_sol_mexico.html', 'https://img.kiosko.net/2025/06/12/mx/mx_sol_mexico.750.jpg', '2025-06-12 12:40:22', NULL, '2025-06-12 18:40:22', '2025-06-12 18:40:22'),
(76, 'mexico', 'La Prensa', '', 'images/covers/thumbnails/La_Prensa_684b1f171adc3_thumb.webp', 'images/covers/La_Prensa_684b1f171adc3_original.webp', '', 'https://es.kiosko.net/mx/np/mx_laprensa.html', 'https://img.kiosko.net/2025/06/12/mx/mx_laprensa.750.jpg', '2025-06-12 12:40:24', NULL, '2025-06-12 18:40:24', '2025-06-12 18:40:24'),
(77, 'mexico', 'Al Día A.M.', '', 'images/covers/thumbnails/Al_D__a_A_M__684b1f186901c_thumb.webp', 'images/covers/Al_D__a_A_M__684b1f186901c_original.webp', '', 'https://es.kiosko.net/mx/np/mx_am.html', 'https://img.kiosko.net/2025/06/12/mx/mx_am.750.jpg', '2025-06-12 12:40:25', NULL, '2025-06-12 18:40:25', '2025-06-12 18:40:25'),
(78, 'mexico', 'El Informador', '', 'images/covers/thumbnails/El_Informador_684b1f1a16aaa_thumb.webp', 'images/covers/El_Informador_684b1f1a16aaa_original.webp', '', 'https://es.kiosko.net/mx/np/mx_informador.html', 'https://img.kiosko.net/2025/06/12/mx/mx_informador.750.jpg', '2025-06-12 12:40:27', NULL, '2025-06-12 18:40:27', '2025-06-12 18:40:27'),
(79, 'panama', 'La Estrella de Panamá', '', 'images/covers/thumbnails/La_Estrella_de_Panam___684b1f1c34a0c_thumb.webp', 'images/covers/La_Estrella_de_Panam___684b1f1c34a0c_original.webp', '', 'https://es.kiosko.net/pa/np/estrella_panama.html', 'https://img.kiosko.net/2025/06/12/pa/estrella_panama.750.jpg', '2025-06-12 12:40:29', NULL, '2025-06-12 18:40:29', '2025-06-12 18:40:29'),
(80, 'dominicanRepublic', 'Diario Libre', '', 'images/covers/thumbnails/Diario_Libre_684b1f1e5031b_thumb.webp', 'images/covers/Diario_Libre_684b1f1e5031b_original.webp', '', 'https://es.kiosko.net/do/np/do_diario_libre.html', 'https://img.kiosko.net/2025/06/12/do/do_diario_libre.750.jpg', '2025-06-12 12:40:31', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `error_log`
--

CREATE TABLE `error_log` (
  `id` int(11) NOT NULL,
  `level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nivel del error: ERROR, WARNING, INFO, DEBUG',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mensaje del error',
  `context` text COLLATE utf8mb4_unicode_ci COMMENT 'Contexto adicional en formato JSON',
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Archivo donde ocurrió el error',
  `line` int(11) DEFAULT NULL COMMENT 'Línea donde ocurrió el error',
  `trace` text COLLATE utf8mb4_unicode_ci COMMENT 'Stack trace del error',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL donde ocurrió el error (para errores de scraping)',
  `country` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'País relacionado con el error (para errores de scraping)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medios`
--

CREATE TABLE `medios` (
  `id` int(10) UNSIGNED NOT NULL,
  `grupo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pais` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_normalizado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dereach` int(11) DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_id` bigint(20) UNSIGNED DEFAULT NULL,
  `twitter_screen_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visualizar` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medios`
--

INSERT INTO `medios` (`id`, `grupo`, `pais`, `title`, `name_normalizado`, `dereach`, `source`, `twitter_id`, `twitter_screen_name`, `visualizar`, `created_at`) VALUES
(1, 'Sudamerica1', 'Uruguay', 'La Diaria', 'Uy_LaDiaria', 428700, 'https://es.kiosko.net/uy/np/uy_ladiaria.html', 103296066, NULL, 1, '2025-05-27 17:53:56'),
(2, 'Paraguay', 'Paraguay', 'IP Paraguay', 'Py_IP', 102332, NULL, 105696089, NULL, 1, '2025-05-27 17:53:56'),
(3, 'Paraguay', 'Paraguay', 'Crónica Digital', 'Py_CronicaDigital', 9497, NULL, 1254909531550896128, NULL, 1, '2025-05-27 17:53:56'),
(4, 'Paraguay', 'Paraguay', 'El Nacional', 'Py_ElNacional', 73956, NULL, 1291011046090711040, NULL, 1, '2025-05-27 17:53:56'),
(5, 'Sudamerica1', 'Chile', 'El Mercurio', 'Cl_ElMercurio', 245257, NULL, 1323223190, NULL, 0, '2025-05-27 17:53:56'),
(6, 'Mundo', 'Spain', 'EL MUNDO', 'Es_ElMundo', 4929740, NULL, 14436030, NULL, 1, '2025-05-27 17:53:56'),
(7, 'Sudamerica1', 'Brasil', 'Folha de São Paulo', 'Br_FolhadeSPaulo', 8878721, 'https://es.kiosko.net/br/np/br_folha_spaulo.html', 14594813, NULL, 1, '2025-05-27 17:53:56'),
(8, 'Mundo', 'Estados Unidos', 'USA Today', 'Us_UsaToday', 5100369, 'https://es.kiosko.net/us/np/usa_today.html', 15754281, NULL, 1, '2025-05-27 17:53:56'),
(9, 'Paraguay', 'Paraguay', 'El Independiente', 'Py_ElIndependiente', 3613, NULL, 1602366048157798425, NULL, 1, '2025-05-27 17:53:56'),
(10, 'Paraguay', 'Paraguay', 'La Nación', 'Py_DiarioLaNacion', 465972, 'https://es.kiosko.net/py/np/nacion.html', 166275230, NULL, 1, '2025-05-27 17:53:56'),
(11, 'Sudamerica1', 'Bolivia', 'EL DIARIO DE BOLIVIA', 'Bo_ElDiariodeBolivia', 78326, NULL, 169196719, NULL, 1, '2025-05-27 17:53:56'),
(12, 'Paraguay', 'Paraguay', 'Forbes Paraguay', 'Py_ForbesParaguay', 4050, NULL, 1758556531040157697, NULL, 0, '2025-05-27 17:53:56'),
(13, 'Sudamerica1', 'Argentina', 'Diario La Capital', 'Ar_La Capital', 348314, NULL, 179990537, NULL, 0, '2025-05-27 17:53:56'),
(14, 'Mundo', 'Gran Bretaña', 'Financial Times', 'Us_FinancialTimes', 6114485, 'https://es.kiosko.net/uk/np/ft_uk.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(15, 'Mundo', 'Spain', 'ABC España', 'Es_ABC', 2323448, NULL, 19923515, NULL, 1, '2025-05-27 17:53:56'),
(16, 'Paraguay', 'Paraguay', 'Revista PLUS', 'Py_RevistaPLUS', 8458, NULL, 234883590, NULL, 1, '2025-05-27 17:53:56'),
(17, 'Paraguay', 'Paraguay', 'Portada de 5 Días (Paraguay)', 'Py_5Dias', 235136, 'https://es.kiosko.net/py/np/py_5dias.html', 260427586, NULL, 0, '2025-05-27 17:53:56'),
(18, 'Paraguay', 'Paraguay', 'ABC Digital', 'Py_ABCDigital', 1442236, 'https://www.abc.com.py/edicion-impresa/', 28191953, NULL, 1, '2025-05-27 17:53:56'),
(19, 'Paraguay', 'Paraguay', 'Extra', 'Py_DiarioExtraPy', 119948, 'https://www.extra.com.py/', 2873821024, NULL, 1, '2025-05-27 17:53:56'),
(20, 'Paraguay', 'Paraguay', 'Última Hora', 'Py_UltimaHora', 1358864, NULL, 30054530, NULL, 1, '2025-05-27 17:53:56'),
(21, 'Sudamerica1', 'Uruguay', 'El País', 'Uy_ElPais', 880400, 'https://es.kiosko.net/uy/np/uy_elpais.html', 31459537, NULL, 1, '2025-05-27 17:53:56'),
(22, 'Sudamerica1', 'Chile', 'La Tercera', 'Cl_LaTercera', 2268555, 'https://es.kiosko.net/cl/np/cl_tercera.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(23, 'Paraguay', 'Paraguay', 'Diario Popular', 'Py_DiarioPopular', 71503, 'https://www.popular.com.py/', 4049383006, NULL, 1, '2025-05-27 17:53:56'),
(24, 'Sudamerica1', 'Bolivia', 'La Razón Digital', 'Bo_LaRazon', 667420, NULL, 44489439, NULL, 1, '2025-05-27 17:53:56'),
(25, 'Sudamerica1', 'Uruguay', 'El Observador Uruguay', 'Uy_ElObservador', 887024, NULL, 54259597, NULL, 1, '2025-05-27 17:53:56'),
(26, 'Sudamerica1', 'Bolivia', 'EL DEBER', 'Bo_ElDeber', 1122100, 'https://eldeber.com.bo/', 65444625, NULL, 1, '2025-05-27 17:53:56'),
(27, 'Paraguay', 'Paraguay', 'DELPY', 'Py_DELPY', 110750, NULL, 718956501923192833, NULL, 1, '2025-05-27 17:53:56'),
(28, 'Mundo', 'Spain', 'EL PAÍS', 'Es_ElPais', 8847783, NULL, 7996082, NULL, 1, '2025-05-27 17:53:56'),
(29, 'Sudamerica1', 'Argentina', 'Clarín', 'Ar_Clarin', 3592489, 'https://es.kiosko.net/ar/np/ar_clarin.html', 8105922, NULL, 1, '2025-05-27 17:53:56'),
(30, 'Paraguay', 'Paraguay', 'Adndigital', 'Py_ADN_Digital', 33743, 'https://www.adndigital.com.py/', 815959410, NULL, 1, '2025-05-27 17:53:56'),
(31, 'Sudamerica1', 'Brazil', 'Estadão', 'Br_Estadao', 7508980, NULL, 9317502, NULL, 1, '2025-05-27 17:53:56'),
(32, 'Sudamerica1', 'bolivia', 'Los Tiempos', 'Bo_LosTiempos', 554104, 'https://www.lostiempos.com/', 94438031, NULL, 1, '2025-05-27 17:53:56'),
(33, 'Sudamerica1', 'Argentina', 'La Nación', 'Ar_LaNacion', 4100000, 'https://es.kiosko.net/ar/np/nacion.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(34, 'Sudamerica1', 'Argentina', 'El Día de la Plata', 'Ar_ElDia', 330000, 'https://es.kiosko.net/ar/np/ar_eldia.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(35, 'Sudamerica1', 'Argentina', 'La Voz del Interior', 'Ar_LaVozdelInterior', 524000, 'https://es.kiosko.net/ar/np/ar_voz_interior.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(36, 'Sudamerica1', 'Argentina', 'Rio Negro', 'Ar_RioNegro', 112000, 'https://es.kiosko.net/ar/np/ar_rio_negro.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(37, 'Sudamerica1', 'Argentina', 'Olé', 'Ar_Ole', 200000, 'https://es.kiosko.net/ar/np/ole.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(38, 'Sudamerica1', 'Argentina', 'El Cronista Comercial', 'Ar_ElCronista', 232000, 'https://es.kiosko.net/ar/np/ar_cronista.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(39, 'Sudamerica1', 'Argentina', 'Diario El Litoral', 'Ar_ElLitoral', 175000, 'https://es.kiosko.net/ar/np/ar_litoral_sfe.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(40, 'Sudamerica1', 'Argentina', 'El Ancasti', 'Ar_Ancasti', 13000, 'https://es.kiosko.net/ar/np/ar_ancasti.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(41, 'Sudamerica1', 'Argentina', 'Diario Democracia', 'Ar_Democracia', 5900, 'https://es.kiosko.net/ar/np/democracia.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(42, 'Sudamerica1', 'Argentina', 'Diario La Capital - Mar del Plata', 'Ar_LaCapitalMarDelPlata', 97500, 'https://es.kiosko.net/ar/np/la_capital.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(43, 'Sudamerica1', 'Argentina', 'El Chubut', 'Ar_ElChubut', 40600, 'https://es.kiosko.net/ar/np/ar_chubut.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(44, 'Sudamerica1', 'Brasil', 'O Globo', 'Br_Oglobo', 5617888, 'https://es.kiosko.net/br/np/br_oglobo.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(45, 'Sudamerica1', 'Brasil', 'Extra', 'Br_Extra', 1100000, 'https://es.kiosko.net/br/np/br_extra.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(46, 'Sudamerica1', 'Brasil', 'O Dia', 'Br_ODia', 918000, 'https://es.kiosko.net/br/np/o_dia.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(47, 'Sudamerica1', 'Brasil', 'O Estado de São Paulo', 'Br_EstadodeSaoPaulo', 7400000, 'https://es.kiosko.net/br/np/br_estado_spaulo.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(48, 'Sudamerica1', 'Brasil', 'Jornal do Comércio', 'Br_Jornaldo', 81200, 'https://es.kiosko.net/br/np/jornal_comercio.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(49, 'Sudamerica1', 'Brasil', 'Diário A Tarde', 'Br_DiarioATarde', 525000, 'https://es.kiosko.net/br/np/br_atarde.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(50, 'Sudamerica1', 'Brasil', 'Correio*', 'Br_CorreioBahia', 580000, 'https://es.kiosko.net/br/np/correio_bahia.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(51, 'Sudamerica1', 'Brasil', 'Correio Do Povo', 'Br_CorreioDoPovo', 88000, 'https://es.kiosko.net/br/np/correio_povo.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(52, 'Sudamerica1', 'Brasil', 'Correio Braziliense', 'Br_CorreioBraziliense', 87000, 'https://es.kiosko.net/br/np/correio_braziliense.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(53, 'Mundo', 'Estados Unidos', 'New York Times', 'Us_NewYorkTimes', 55000000, 'https://es.kiosko.net/us/np/newyork_times.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(54, 'Mundo', 'Estados Unidos', 'The Washington Post', 'Us_TheWashington', 19700000, 'https://es.kiosko.net/us/np/washington_post.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(55, 'Mundo', 'Estados Unidos', 'Boston Globe', 'Us_BostonGlobe', 779000, 'https://es.kiosko.net/us/np/boston_globe.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(56, 'Mundo', 'Estados Unidos', 'Dallas Morning News', 'Us_DallasMorning', 826000, 'https://es.kiosko.net/us/np/dallas_morning_news.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(57, 'Mundo', 'Estados Unidos', 'Wall Street Journal', 'Us_WallStreetJournal', 20000000, 'https://es.kiosko.net/us/np/wsj.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(58, 'Mundo', 'Estados Unidos', 'Newsweek', 'Us_Newsweek', 36000000, 'https://es.kiosko.net/us/np/newsweek.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(59, 'Mundo', 'Estados Unidos', 'Los Angeles Times', 'Us_LosAngelesTimes', 3800000, 'https://es.kiosko.net/us/np/latimes.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(60, 'Mundo', 'Estados Unidos', 'Chicago Tribune', 'Us_ChicagoTribune', 1200000, 'https://es.kiosko.net/us/np/chicago_tribune.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(61, 'Mundo', 'Estados Unidos', 'Houston Chronicle', 'Us_HoustonChronicle', 720000, 'https://es.kiosko.net/us/np/houston_chronicle.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(62, 'Mundo', 'Estados Unidos', 'New York Post', 'Us_NewYorkPost', 3400000, 'https://es.kiosko.net/us/np/newyork_post.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(63, 'Mundo', 'Estados Unidos', 'San Francisco Chronicle', 'Us_SanFranciscoChronicle', 311000, 'https://es.kiosko.net/us/np/sf_chronicle.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(64, 'Sudamerica1', 'Uruguay', 'El Telégrafo', 'Uy_Telegrafo', 2800, 'https://es.kiosko.net/uy/np/uy_telegrafo.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(65, 'Sudamerica1', 'Chile', 'El Mercurio', 'Cl_Mercurio', 2500000, 'https://es.kiosko.net/cl/np/cl_mercurio.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(66, 'Sudamerica1', 'Chile', 'Diario Chañarcillo', 'Cl_DiarioChanarcillo', 1800, 'https://es.kiosko.net/cl/np/cl_chanarcillo.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(67, 'Sudamerica1', 'Chile', 'Diario La Región de Coquimbo', 'Cl_DiarioLatarde', 20000, 'https://es.kiosko.net/cl/np/region_coquimbo.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(68, 'Sudamerica1', 'Chile', 'Diario de Concepción', 'Cl_DiariodeConcepcion', 80500, 'https://es.kiosko.net/cl/np/diario_concepcion.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(69, 'Sudamerica1', 'Chile', 'El Pingüino', 'Cl_ElPinguino', 2500, 'https://es.kiosko.net/cl/np/pinguino.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(70, 'Sudamerica2', 'Colombia', 'El Espectador', 'Co_ElEspectador', 6900000, 'https://es.kiosko.net/co/np/co_espectador.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(71, 'Sudamerica2', 'Colombia', 'El Heraldo', 'Co_Heraldo', 1200000, 'https://es.kiosko.net/co/np/co_heraldo.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(72, 'Sudamerica2', 'Colombia', 'El Universal', 'Co_ElUniversal', 387000, 'https://es.kiosko.net/co/np/co_universal.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(73, 'Sudamerica2', 'Colombia', 'La Patria', 'Co_LaPatria', 268000, 'https://es.kiosko.net/co/np/co_patria.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(74, 'Sudamerica2', 'Ecuador', 'Expreso', 'Ec_Expreso', 1200000, 'https://es.kiosko.net/ec/np/ec_expreso.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(75, 'Sudamerica2', 'Peru', 'El Comercio', 'Pe_ElComercio', 1300000, 'https://es.kiosko.net/pe/np/pe_comercio.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(76, 'Sudamerica2', 'Peru', 'La Republica', 'Pe_LaRepublica', 3200000, 'https://es.kiosko.net/pe/np/pe_republica.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(77, 'Sudamerica2', 'Peru', 'Diario Chaski', 'Pe_DiarioChaski', 17500, 'https://es.kiosko.net/pe/np/pe_diario_chaski.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(78, 'Sudamerica2', 'Peru', 'Líbero', 'Pe_Libero', 18000, 'https://es.kiosko.net/pe/np/pe_libero.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(79, 'Sudamerica2', 'Venezuela', 'El Nacional', 'Ve_Nacional', 6100000, 'https://es.kiosko.net/ve/np/ve_nacional.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(80, 'Sudamerica2', 'Venezuela', 'Periodiquito de Aragua', 'Ve_Periodiquitodearagua', 386000, 'https://es.kiosko.net/ve/np/periodiquito.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(81, 'Sudamerica2', 'Venezuela', 'Universal', 'Ve_Universal', 5200000, 'https://es.kiosko.net/ve/np/ve_universal.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(82, 'Sudamerica2', 'Venezuela', 'Lider en deportes', 'Ve_Liderendeportes', 132000, 'https://es.kiosko.net/ve/np/ve_lider.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(83, 'Centroamerica', 'mexico', 'El Universal', 'Mx_Universal', 8100000, 'https://es.kiosko.net/mx/np/mx_universal.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(84, 'Centroamerica', 'mexico', 'Excelsior', 'Mx_Excelsior', 2400000, 'https://es.kiosko.net/mx/np/mx_excelsior.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(85, 'Centroamerica', 'mexico', 'La Jornada', 'Mx_Jornada', 2800000, 'https://es.kiosko.net/mx/np/mx_jornada.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(86, 'Centroamerica', 'mexico', 'Milenio', 'Mx_Milenio', 6100000, 'https://es.kiosko.net/mx/np/mx_milenio.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(87, 'Centroamerica', 'mexico', 'Reforma', 'Mx_Reforma', 3900000, 'https://es.kiosko.net/mx/np/mx_reforma.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(88, 'Centroamerica', 'mexico', 'Esto', 'Mx_Esto', 200000, 'https://es.kiosko.net/mx/np/mx_esto.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(89, 'Centroamerica', 'mexico', 'El Financiero', 'Mx_ElFinanciero', 1900000, 'https://es.kiosko.net/mx/np/mx_financiero.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(90, 'Centroamerica', 'mexico', 'El Economista', 'Mx_Eleconomista', 1000000, 'https://es.kiosko.net/mx/np/mx_eleconomista.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(91, 'Centroamerica', 'mexico', 'Diario de Yucatán', 'Mx_DiariodeYucatán', 322000, 'https://es.kiosko.net/mx/np/mx_diario_yucatan.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(92, 'Centroamerica', 'mexico', 'El Sol de México', 'Mx_SoldeMexico', 101700, 'https://es.kiosko.net/mx/np/mx_sol_mexico.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(93, 'Centroamerica', 'mexico', 'La Prensa', 'Mx_LaPrensa', 27100, 'https://es.kiosko.net/mx/np/mx_laprensa.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(94, 'Centroamerica', 'mexico', 'Al Día A.M.', 'Mx_AlDia', 21900, 'https://es.kiosko.net/mx/np/mx_am.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(95, 'Centroamerica', 'mexico', 'El Informador', 'Mx_ElInformador', 690000, 'https://es.kiosko.net/mx/np/mx_informador.html', NULL, NULL, 0, '2025-05-27 17:53:56'),
(96, 'Centroamerica', 'panama', 'La Estrella de Panamá', 'Pa_LaEstrella', 1200000, 'https://es.kiosko.net/pa/np/estrella_panama.html', NULL, NULL, 1, '2025-05-27 17:53:56'),
(100, 'Mundo', 'Gran Bretaña', 'The Guardian', 'Uk_TheGuardian', NULL, 'https://es.kiosko.net/uk/np/guardian.html', NULL, NULL, 1, '2025-06-11 20:46:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pk_meltwater_resumen`
--

CREATE TABLE `pk_meltwater_resumen` (
  `id` int(11) NOT NULL,
  `grupo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pais` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titulo` text COLLATE utf8mb4_unicode_ci,
  `dereach` int(11) DEFAULT NULL,
  `source` text COLLATE utf8mb4_unicode_ci,
  `twitter_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visualizar` tinyint(1) DEFAULT '0',
  `published_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pk_melwater`
--

CREATE TABLE `pk_melwater` (
  `id` int(11) NOT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_date` datetime DEFAULT NULL,
  `indexed_date` datetime DEFAULT NULL,
  `source_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_network` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_image` text COLLATE utf8mb4_unicode_ci,
  `content_text` text COLLATE utf8mb4_unicode_ci,
  `url_destino` text COLLATE utf8mb4_unicode_ci,
  `input_names` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pk_melwater`
--

INSERT INTO `pk_melwater` (`id`, `external_id`, `published_date`, `indexed_date`, `source_id`, `social_network`, `country_code`, `country_name`, `author_name`, `content_image`, `content_text`, `url_destino`, `input_names`, `created_at`) VALUES
(1, '234883590', '2025-06-10 17:52:18', '2025-06-10 17:52:44', NULL, NULL, NULL, NULL, NULL, 'images/melwater/234883590_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:00'),
(2, '14436030', '2025-06-11 03:00:00', '2025-06-11 03:00:23', NULL, NULL, NULL, NULL, NULL, 'images/melwater/14436030_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS MUNDO', '2025-06-12 18:38:01'),
(3, '18949452', '2025-06-10 17:30:14', '2025-06-10 17:30:35', NULL, NULL, NULL, NULL, NULL, 'images/melwater/18949452_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS MUNDO', '2025-06-12 18:38:02'),
(4, '179990537', '2025-06-11 07:49:19', '2025-06-11 07:49:39', NULL, NULL, NULL, NULL, NULL, 'images/melwater/179990537_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:02'),
(5, '166275230', '2025-06-11 04:48:37', '2025-06-11 04:48:57', NULL, NULL, NULL, NULL, NULL, 'images/melwater/166275230_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:03'),
(6, '19923515', '2025-06-10 18:05:42', '2025-06-10 18:06:37', NULL, NULL, NULL, NULL, NULL, 'images/melwater/19923515_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS MUNDO', '2025-06-12 18:38:04'),
(8, '65444625', '2025-06-10 16:19:06', '2025-06-10 16:19:28', NULL, NULL, NULL, NULL, NULL, 'images/melwater/65444625_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:05'),
(9, '260427586', '2025-06-10 21:00:01', '2025-06-10 21:00:24', NULL, NULL, NULL, NULL, NULL, 'images/melwater/260427586_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:06'),
(10, '1602366048157798425', '2025-06-10 22:00:02', '2025-06-10 22:00:26', NULL, NULL, NULL, NULL, NULL, 'images/melwater/1602366048157798425_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:06'),
(12, '103296066', '2025-06-11 07:01:08', '2025-06-11 07:01:42', NULL, NULL, NULL, NULL, NULL, 'images/melwater/103296066_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:07'),
(13, '169196719', '2025-06-11 09:00:36', '2025-06-11 09:01:25', NULL, NULL, NULL, NULL, NULL, 'images/melwater/169196719_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:08'),
(14, '54259597', '2025-06-11 09:23:05', '2025-06-11 09:23:27', NULL, NULL, NULL, NULL, NULL, 'images/melwater/54259597_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:09'),
(15, '3222731', '2025-06-11 08:01:02', '2025-06-11 08:01:25', NULL, NULL, NULL, NULL, NULL, 'images/melwater/3222731_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:09'),
(16, '1291011046090711040', '2025-06-11 05:00:00', '2025-06-11 05:00:26', NULL, NULL, NULL, NULL, NULL, 'images/melwater/1291011046090711040_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:10'),
(17, '1254909531550896128', '2025-06-11 03:29:54', '2025-06-11 03:30:17', NULL, NULL, NULL, NULL, NULL, 'images/melwater/1254909531550896128_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:11'),
(18, '105696089', '2025-06-11 04:13:50', '2025-06-11 04:14:13', NULL, NULL, NULL, NULL, NULL, 'images/melwater/105696089_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:11'),
(19, '7996082', '2025-06-11 00:22:13', '2025-06-11 00:23:25', NULL, NULL, NULL, NULL, NULL, 'images/melwater/7996082_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS MUNDO', '2025-06-12 18:38:11'),
(20, '9317502', '2025-06-11 06:15:02', '2025-06-11 06:19:07', NULL, NULL, NULL, NULL, NULL, 'images/melwater/9317502_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:12'),
(21, '30054530', '2025-06-11 05:00:00', '2025-06-11 05:00:22', NULL, NULL, NULL, NULL, NULL, 'images/melwater/30054530_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:13'),
(23, '31459537', '2025-06-11 07:30:30', '2025-06-11 07:31:02', NULL, NULL, NULL, NULL, NULL, 'images/melwater/31459537_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:14'),
(24, '718956501923192833', '2025-06-10 23:54:17', '2025-06-10 23:54:52', NULL, NULL, NULL, NULL, NULL, 'images/melwater/718956501923192833_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:15'),
(25, '14594813', '2025-06-11 01:01:40', '2025-06-11 01:02:02', NULL, NULL, NULL, NULL, NULL, 'images/melwater/14594813_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:15'),
(27, '1323223190', '2025-06-11 08:00:03', '2025-06-11 08:00:26', NULL, NULL, NULL, NULL, NULL, 'images/melwater/1323223190_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:16'),
(28, '8105922', '2025-06-11 00:40:11', '2025-06-11 00:40:30', NULL, NULL, NULL, NULL, NULL, 'images/melwater/8105922_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:17'),
(29, '2873821024', '2025-06-11 06:06:04', '2025-06-11 06:06:23', NULL, NULL, NULL, NULL, NULL, 'images/melwater/2873821024_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:18'),
(30, '28191953', '2025-06-11 05:45:07', '2025-06-11 05:45:28', NULL, NULL, NULL, NULL, NULL, 'images/melwater/28191953_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:18'),
(31, '44489439', '2025-06-11 08:13:38', '2025-06-11 08:13:58', NULL, NULL, NULL, NULL, NULL, 'images/melwater/44489439_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS LIMITROFESPY', '2025-06-12 18:38:19'),
(32, '815959410', '2025-06-11 00:43:09', '2025-06-11 00:43:35', NULL, NULL, NULL, NULL, NULL, 'images/melwater/815959410_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:20'),
(33, '4049383006', '2025-06-11 03:30:34', '2025-06-11 03:30:57', NULL, NULL, NULL, NULL, NULL, 'images/melwater/4049383006_original.webp', '', '#', 'NEWSROOM_PY_PORTADAS PY', '2025-06-12 18:38:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `portadas`
--

CREATE TABLE `portadas` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pais` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_date` datetime NOT NULL,
  `dereach` int(11) DEFAULT NULL,
  `source_type` enum('meltwater','cover','resumen') COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visualizar` tinyint(1) DEFAULT '1',
  `original_url` text COLLATE utf8mb4_unicode_ci,
  `thumbnail_url` text COLLATE utf8mb4_unicode_ci,
  `indexed_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `portadas`
--

INSERT INTO `portadas` (`id`, `title`, `grupo`, `pais`, `published_date`, `dereach`, `source_type`, `external_id`, `visualizar`, `original_url`, `thumbnail_url`, `indexed_date`, `created_at`, `updated_at`) VALUES
(1, 'The Guardian', 'Mundo', 'Gran Bretaña', '2025-06-12 12:38:23', NULL, 'cover', 'https://es.kiosko.net/uk/np/guardian.html', 1, 'images/covers/Portada_de_The_Guardian__Reino_Unido__684b1e9e7332d_original.webp', 'images/covers/thumbnails/Portada_de_The_Guardian__Reino_Unido__684b1e9e7332d_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(2, 'Financial Times', 'Mundo', 'Gran Bretaña', '2025-06-12 12:38:25', 6114485, 'cover', 'https://es.kiosko.net/uk/np/ft_uk.html', 1, 'images/covers/Portada_de_Financial_Times__Reino_Unido__684b1ea059b52_original.webp', 'images/covers/thumbnails/Portada_de_Financial_Times__Reino_Unido__684b1ea059b52_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(3, 'Clarín', 'Sudamerica1', 'Argentina', '2025-06-12 12:38:27', 3592489, 'cover', 'https://es.kiosko.net/ar/np/ar_clarin.html', 1, 'images/covers/Clar__n_684b1ea20e0ed_original.webp', 'images/covers/thumbnails/Clar__n_684b1ea20e0ed_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(4, 'La Nación', 'Sudamerica1', 'Argentina', '2025-06-12 12:38:28', 4100000, 'cover', 'https://es.kiosko.net/ar/np/nacion.html', 1, 'images/covers/La_Naci__n_684b1ea36bc17_original.webp', 'images/covers/thumbnails/La_Naci__n_684b1ea36bc17_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(5, 'La Voz del Interior', 'Sudamerica1', 'Argentina', '2025-06-12 12:38:31', 524000, 'cover', 'https://es.kiosko.net/ar/np/ar_voz_interior.html', 1, 'images/covers/La_Voz_del_Interior_684b1ea708c47_original.webp', 'images/covers/thumbnails/La_Voz_del_Interior_684b1ea708c47_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(6, 'El Cronista Comercial', 'Sudamerica1', 'Argentina', '2025-06-12 12:38:34', 232000, 'cover', 'https://es.kiosko.net/ar/np/ar_cronista.html', 1, 'images/covers/El_Cronista_Comercial_684b1ea9438cc_original.webp', 'images/covers/thumbnails/El_Cronista_Comercial_684b1ea9438cc_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(7, 'El Chubut', 'Sudamerica1', 'Argentina', '2025-06-12 12:38:40', 40600, 'cover', 'https://es.kiosko.net/ar/np/ar_chubut.html', 1, 'images/covers/El_Chubut_684b1eaf85fb2_original.webp', 'images/covers/thumbnails/El_Chubut_684b1eaf85fb2_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(8, 'La Nación', 'Paraguay', 'Paraguay', '2025-06-12 12:38:42', 465972, 'cover', 'https://es.kiosko.net/py/np/nacion.html', 1, 'images/covers/La_Naci__n_684b1eb10aefb_original.webp', 'images/covers/thumbnails/La_Naci__n_684b1eb10aefb_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(9, 'Adndigital', 'Paraguay', 'Paraguay', '2025-06-12 12:38:44', 33743, 'cover', 'https://www.adndigital.com.py/', 1, 'images/covers/Portada_684b1eb334211_original.webp', 'images/covers/thumbnails/Portada_684b1eb334211_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(10, 'Diario Popular', 'Paraguay', 'Paraguay', '2025-06-12 12:38:50', 71503, 'cover', 'https://www.popular.com.py/', 1, 'images/covers/Portada_684b1eb95b7a8_original.webp', 'images/covers/thumbnails/Portada_684b1eb95b7a8_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(11, 'ABC Digital', 'Paraguay', 'Paraguay', '2025-06-12 12:38:54', 1442236, 'cover', 'https://www.abc.com.py/edicion-impresa/', 1, 'images/covers/Portada_684b1ebbf0f3d_original.webp', 'images/covers/thumbnails/Portada_684b1ebbf0f3d_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(12, 'Extra', 'Paraguay', 'Paraguay', '2025-06-12 12:38:56', 119948, 'cover', 'https://www.extra.com.py/', 1, 'images/covers/Portada_684b1ebf5d651_original.webp', 'images/covers/thumbnails/Portada_684b1ebf5d651_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(13, 'O Globo', 'Sudamerica1', 'Brasil', '2025-06-12 12:39:06', 5617888, 'cover', 'https://es.kiosko.net/br/np/br_oglobo.html', 1, 'images/covers/O_Globo_684b1ec8ee76b_original.webp', 'images/covers/thumbnails/O_Globo_684b1ec8ee76b_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(14, 'Folha de São Paulo', 'Sudamerica1', 'Brasil', '2025-06-12 12:39:08', 8878721, 'cover', 'https://es.kiosko.net/br/np/br_folha_spaulo.html', 1, 'images/covers/Folha_de_S__o_Paulo_684b1ecaef273_original.webp', 'images/covers/thumbnails/Folha_de_S__o_Paulo_684b1ecaef273_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(15, 'Extra', 'Sudamerica1', 'Brasil', '2025-06-12 12:39:09', 1100000, 'cover', 'https://es.kiosko.net/br/np/br_extra.html', 1, 'images/covers/Extra_684b1ecc66c88_original.webp', 'images/covers/thumbnails/Extra_684b1ecc66c88_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(16, 'O Estado de São Paulo', 'Sudamerica1', 'Brasil', '2025-06-12 12:39:12', 7400000, 'cover', 'https://es.kiosko.net/br/np/br_estado_spaulo.html', 1, 'images/covers/O_Estado_de_S__o_Paulo_684b1ecf1864a_original.webp', 'images/covers/thumbnails/O_Estado_de_S__o_Paulo_684b1ecf1864a_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(17, 'New York Times', 'Mundo', 'Estados Unidos', '2025-06-12 12:39:18', 55000000, 'cover', 'https://es.kiosko.net/us/np/newyork_times.html', 1, 'images/covers/New_York_Times_684b1ed597954_original.webp', 'images/covers/thumbnails/New_York_Times_684b1ed597954_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(18, 'USA Today', 'Mundo', 'Estados Unidos', '2025-06-12 12:39:20', 5100369, 'cover', 'https://es.kiosko.net/us/np/usa_today.html', 1, 'images/covers/USA_Today_684b1ed733692_original.webp', 'images/covers/thumbnails/USA_Today_684b1ed733692_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(19, 'The Washington Post', 'Mundo', 'Estados Unidos', '2025-06-12 12:39:21', 19700000, 'cover', 'https://es.kiosko.net/us/np/washington_post.html', 1, 'images/covers/The_Washington_Post_684b1ed8ad542_original.webp', 'images/covers/thumbnails/The_Washington_Post_684b1ed8ad542_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(20, 'Boston Globe', 'Mundo', 'Estados Unidos', '2025-06-12 12:39:22', 779000, 'cover', 'https://es.kiosko.net/us/np/boston_globe.html', 1, 'images/covers/Boston_Globe_684b1ed9ee8b5_original.webp', 'images/covers/thumbnails/Boston_Globe_684b1ed9ee8b5_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(21, 'Wall Street Journal', 'Mundo', 'Estados Unidos', '2025-06-12 12:39:25', 20000000, 'cover', 'https://es.kiosko.net/us/np/wsj.html', 1, 'images/covers/Wall_Street_Journal_684b1edc839a3_original.webp', 'images/covers/thumbnails/Wall_Street_Journal_684b1edc839a3_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(22, 'Newsweek', 'Mundo', 'Estados Unidos', '2025-06-12 12:39:26', 36000000, 'cover', 'https://es.kiosko.net/us/np/newsweek.html', 1, 'images/covers/Newsweek_684b1eddea997_original.webp', 'images/covers/thumbnails/Newsweek_684b1eddea997_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(23, 'Chicago Tribune', 'Mundo', 'Estados Unidos', '2025-06-12 12:39:30', 1200000, 'cover', 'https://es.kiosko.net/us/np/chicago_tribune.html', 1, 'images/covers/Chicago_Tribune_684b1ee194e43_original.webp', 'images/covers/thumbnails/Chicago_Tribune_684b1ee194e43_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(24, 'El País', 'Sudamerica1', 'Uruguay', '2025-06-12 12:39:36', 880400, 'cover', 'https://es.kiosko.net/uy/np/uy_elpais.html', 1, 'images/covers/El_Pa__s_684b1ee737f43_original.webp', 'images/covers/thumbnails/El_Pa__s_684b1ee737f43_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(25, 'La Diaria', 'Sudamerica1', 'Uruguay', '2025-06-12 12:39:37', 428700, 'cover', 'https://es.kiosko.net/uy/np/uy_ladiaria.html', 1, 'images/covers/La_Diaria_684b1ee89d2fb_original.webp', 'images/covers/thumbnails/La_Diaria_684b1ee89d2fb_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(26, 'El Mercurio', 'Sudamerica1', 'Chile', '2025-06-12 12:39:41', 2500000, 'cover', 'https://es.kiosko.net/cl/np/cl_mercurio.html', 1, 'images/covers/El_Mercurio_684b1eec25ae4_original.webp', 'images/covers/thumbnails/El_Mercurio_684b1eec25ae4_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(27, 'La Tercera', 'Sudamerica1', 'Chile', '2025-06-12 12:39:42', 2268555, 'cover', 'https://es.kiosko.net/cl/np/cl_tercera.html', 1, 'images/covers/La_Tercera_684b1eedaec96_original.webp', 'images/covers/thumbnails/La_Tercera_684b1eedaec96_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(28, 'El Espectador', 'Sudamerica2', 'Colombia', '2025-06-12 12:39:49', 6900000, 'cover', 'https://es.kiosko.net/co/np/co_espectador.html', 1, 'images/covers/El_Espectador_684b1ef407ab5_original.webp', 'images/covers/thumbnails/El_Espectador_684b1ef407ab5_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(29, 'El Heraldo', 'Sudamerica2', 'Colombia', '2025-06-12 12:39:50', 1200000, 'cover', 'https://es.kiosko.net/co/np/co_heraldo.html', 1, 'images/covers/El_Heraldo_684b1ef5517f8_original.webp', 'images/covers/thumbnails/El_Heraldo_684b1ef5517f8_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(30, 'Expreso', 'Sudamerica2', 'Ecuador', '2025-06-12 12:39:54', 1200000, 'cover', 'https://es.kiosko.net/ec/np/ec_expreso.html', 1, 'images/covers/Expreso_684b1ef90fcee_original.webp', 'images/covers/thumbnails/Expreso_684b1ef90fcee_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(31, 'El Comercio', 'Sudamerica2', 'Peru', '2025-06-12 12:39:55', 1300000, 'cover', 'https://es.kiosko.net/pe/np/pe_comercio.html', 1, 'images/covers/El_Comercio_684b1efaca740_original.webp', 'images/covers/thumbnails/El_Comercio_684b1efaca740_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(32, 'La Republica', 'Sudamerica2', 'Peru', '2025-06-12 12:39:57', 3200000, 'cover', 'https://es.kiosko.net/pe/np/pe_republica.html', 1, 'images/covers/La_Republica_684b1efc249e6_original.webp', 'images/covers/thumbnails/La_Republica_684b1efc249e6_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(33, 'Líbero', 'Sudamerica2', 'Peru', '2025-06-12 12:39:58', 18000, 'cover', 'https://es.kiosko.net/pe/np/pe_libero.html', 1, 'images/covers/L__bero_684b1efd6778e_original.webp', 'images/covers/thumbnails/L__bero_684b1efd6778e_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(34, 'El Nacional', 'Sudamerica2', 'Venezuela', '2025-06-12 12:39:59', 6100000, 'cover', 'https://es.kiosko.net/ve/np/ve_nacional.html', 1, 'images/covers/El_Nacional_684b1efebd293_original.webp', 'images/covers/thumbnails/El_Nacional_684b1efebd293_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(35, 'Universal', 'Sudamerica2', 'Venezuela', '2025-06-12 12:40:03', 5200000, 'cover', 'https://es.kiosko.net/ve/np/ve_universal.html', 1, 'images/covers/Universal_684b1f02b5166_original.webp', 'images/covers/thumbnails/Universal_684b1f02b5166_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(36, 'EL DEBER', 'Sudamerica1', 'Bolivia', '2025-06-12 12:40:06', 1122100, 'cover', 'https://eldeber.com.bo/', 1, 'images/covers/Tapa_El_Deber_12_06_2026_684b1f062cd70_original.webp', 'images/covers/thumbnails/Tapa_El_Deber_12_06_2026_684b1f062cd70_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(37, 'Los Tiempos', 'Sudamerica1', 'bolivia', '2025-06-12 12:40:08', 554104, 'cover', 'https://www.lostiempos.com/', 1, 'images/covers/Portada_684b1f0775749_original.webp', 'images/covers/thumbnails/Portada_684b1f0775749_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(38, 'El Universal', 'Centroamerica', 'mexico', '2025-06-12 12:40:10', 8100000, 'cover', 'https://es.kiosko.net/mx/np/mx_universal.html', 1, 'images/covers/El_Universal_684b1f0915f70_original.webp', 'images/covers/thumbnails/El_Universal_684b1f0915f70_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(39, 'Excelsior', 'Centroamerica', 'mexico', '2025-06-12 12:40:11', 2400000, 'cover', 'https://es.kiosko.net/mx/np/mx_excelsior.html', 1, 'images/covers/Excelsior_684b1f0a978e8_original.webp', 'images/covers/thumbnails/Excelsior_684b1f0a978e8_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(40, 'La Jornada', 'Centroamerica', 'mexico', '2025-06-12 12:40:13', 2800000, 'cover', 'https://es.kiosko.net/mx/np/mx_jornada.html', 1, 'images/covers/La_Jornada_684b1f0c25df2_original.webp', 'images/covers/thumbnails/La_Jornada_684b1f0c25df2_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(41, 'Milenio', 'Centroamerica', 'mexico', '2025-06-12 12:40:14', 6100000, 'cover', 'https://es.kiosko.net/mx/np/mx_milenio.html', 1, 'images/covers/Milenio_684b1f0d71e08_original.webp', 'images/covers/thumbnails/Milenio_684b1f0d71e08_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(42, 'Reforma', 'Centroamerica', 'mexico', '2025-06-12 12:40:16', 3900000, 'cover', 'https://es.kiosko.net/mx/np/mx_reforma.html', 1, 'images/covers/Reforma_684b1f0ec0751_original.webp', 'images/covers/thumbnails/Reforma_684b1f0ec0751_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(43, 'El Financiero', 'Centroamerica', 'mexico', '2025-06-12 12:40:18', 1900000, 'cover', 'https://es.kiosko.net/mx/np/mx_financiero.html', 1, 'images/covers/El_Financiero_684b1f11b34d3_original.webp', 'images/covers/thumbnails/El_Financiero_684b1f11b34d3_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(44, 'El Economista', 'Centroamerica', 'mexico', '2025-06-12 12:40:19', 1000000, 'cover', 'https://es.kiosko.net/mx/np/mx_eleconomista.html', 1, 'images/covers/El_Economista_684b1f130861c_original.webp', 'images/covers/thumbnails/El_Economista_684b1f130861c_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31'),
(45, 'La Estrella de Panamá', 'Centroamerica', 'panama', '2025-06-12 12:40:29', 1200000, 'cover', 'https://es.kiosko.net/pa/np/estrella_panama.html', 1, 'images/covers/La_Estrella_de_Panam___684b1f1c34a0c_original.webp', 'images/covers/thumbnails/La_Estrella_de_Panam___684b1f1c34a0c_thumb.webp', NULL, '2025-06-12 18:40:31', '2025-06-12 18:40:31');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `configs`
--
ALTER TABLE `configs`
  ADD PRIMARY KEY (`name`);

--
-- Indices de la tabla `covers`
--
ALTER TABLE `covers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Cover unico` (`country`,`title`,`source`);

--
-- Indices de la tabla `error_log`
--
ALTER TABLE `error_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_country` (`country`);

--
-- Indices de la tabla `medios`
--
ALTER TABLE `medios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_twitter_id` (`twitter_id`);

--
-- Indices de la tabla `pk_meltwater_resumen`
--
ALTER TABLE `pk_meltwater_resumen`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pk_melwater`
--
ALTER TABLE `pk_melwater`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `EXTERNALID` (`external_id`);

--
-- Indices de la tabla `portadas`
--
ALTER TABLE `portadas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `portada unica` (`title`,`grupo`,`pais`,`external_id`),
  ADD KEY `idx_grupo` (`grupo`),
  ADD KEY `idx_pais` (`pais`),
  ADD KEY `idx_published_date` (`published_date`),
  ADD KEY `idx_source_type` (`source_type`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `covers`
--
ALTER TABLE `covers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT de la tabla `error_log`
--
ALTER TABLE `error_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `medios`
--
ALTER TABLE `medios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT de la tabla `pk_meltwater_resumen`
--
ALTER TABLE `pk_meltwater_resumen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `pk_melwater`
--
ALTER TABLE `pk_melwater`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `portadas`
--
ALTER TABLE `portadas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
