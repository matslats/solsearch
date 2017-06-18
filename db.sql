
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/* MyISAM tables support Spatial indexes */
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `solsearch`
--


CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL,
  `url` varchar(32) NOT NULL,
  `name` tinytext NOT NULL,
  `apikey` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `ads` (
  `id` int(7) NOT NULL,
  `type` varchar(12) NOT NULL 
  `title` tinytext NOT NULL,
  `body` text NOT NULL,
  `keywords` tinytext NOT NULL,
  `directexchange` tinyint(1) NOT NULL,
  `indirectexchange` tinyint(1) NOT NULL,
  `money` tinyint(1) NOT NULL,
  `scope` tinyint(1) NOT NULL DEFAULT '0',
  `uuid` varchar(42) NOT NULL,
  `lat` float(7,5) NOT NULL,
  `lon` float(8,5) NOT NULL,
  `expires` int(14) NOT NULL COMMENT 'unixtime',
  `path` varchar(32) NOT NULL,
  `client_id` int(11) NOT NULL,
  `image_path` VARCHAR(128) NULL DEFAULT NULL 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

create DEFINER = CURRENT_USER function harvesine (lat1 double, lon1 double, lat2 double, lon2 double) returns double
 return  3956 * 2 * ASIN(SQRT(POWER(SIN((lat1 - abs(lat2)) * pi()/180 / 2), 2) 
         + COS(abs(lat1) * pi()/180 ) * COS(abs(lat2) * pi()/180) * POWER(SIN((lon1 - lon2) * pi()/180 / 2), 2) )

--
-- Indexes for dumped tables
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ads`
  MODIFY `id` int(7) NOT NULL AUTO_INCREMENT;
  

CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `message` text NOT NULL,
  `client_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
