
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `solsearch`
--

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

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
  `path` varchar(32) NOT NULL
  `client_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(7) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
