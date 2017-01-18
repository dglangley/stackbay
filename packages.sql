-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jan 18, 2017 at 11:06 PM
-- Server version: 5.5.49-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `c9`
--

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE IF NOT EXISTS `packages` (
  `weight` decimal(7,3) unsigned DEFAULT NULL,
  `length` decimal(7,3) unsigned DEFAULT NULL,
  `width` decimal(7,3) unsigned DEFAULT NULL,
  `height` decimal(7,3) unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  `order_number` mediumint(9) unsigned NOT NULL,
  `package_no` smallint(5) unsigned DEFAULT NULL,
  `freight_amount` decimal(6,2) DEFAULT NULL,
  `tracking_no` varchar(40) DEFAULT NULL,
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `so_number` (`order_number`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=29 ;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`weight`, `length`, `width`, `height`, `date`, `order_number`, `package_no`, `freight_amount`, `tracking_no`, `id`) VALUES
('6.000', '5.000', '6.000', '15.000', '2017-01-12', 55, 12, '3.00', '9819841919819841984946', 1),
(NULL, NULL, NULL, NULL, NULL, 52, NULL, NULL, NULL, 2),
(NULL, NULL, NULL, NULL, NULL, 52, 0, NULL, NULL, 3),
('123.000', '456.000', '223.000', '65.000', NULL, 56, 0, NULL, NULL, 15),
('23.000', '56.000', '545.000', NULL, NULL, 56, 1, NULL, NULL, 16),
(NULL, NULL, NULL, NULL, NULL, 56, 2, NULL, NULL, 17),
(NULL, NULL, NULL, NULL, NULL, 56, 3, NULL, NULL, 18),
(NULL, NULL, NULL, NULL, NULL, 56, 4, NULL, NULL, 19),
(NULL, NULL, NULL, NULL, NULL, 56, 5, NULL, NULL, 20),
(NULL, NULL, NULL, NULL, NULL, 56, 6, NULL, NULL, 21),
(NULL, NULL, NULL, NULL, NULL, 57, 1, NULL, NULL, 22),
(NULL, NULL, NULL, NULL, NULL, 23, 1, NULL, NULL, 23),
(NULL, NULL, NULL, NULL, NULL, 54, 1, NULL, NULL, 24),
(NULL, NULL, NULL, NULL, NULL, 23, 2, NULL, NULL, 25),
(NULL, NULL, NULL, NULL, NULL, 23, 3, NULL, NULL, 26),
(NULL, NULL, NULL, NULL, NULL, 23, 4, NULL, NULL, 27),
(NULL, NULL, NULL, NULL, NULL, 23, 5, NULL, NULL, 28);

-- --------------------------------------------------------

--
-- Table structure for table `package_contents`
--

CREATE TABLE IF NOT EXISTS `package_contents` (
  `packageid` mediumint(9) unsigned NOT NULL,
  `serialid` mediumint(9) unsigned NOT NULL COMMENT 'Part/SO_Item'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `problematic` FOREIGN KEY (`order_number`) REFERENCES `sales_orders` (`so_number`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
