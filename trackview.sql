-- phpMyAdmin SQL Dump
-- version 3.5.0-dev
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 04, 2011 at 11:54 PM
-- Server version: 5.2.4
-- PHP Version: 5.3.6-6~dotdeb.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `trackview`
--

-- --------------------------------------------------------

--
-- Table structure for table `link`
--

CREATE TABLE IF NOT EXISTS `link` (
  `from` varchar(100) NOT NULL,
  `to` varchar(100) NOT NULL,
  `heading` int(11) NOT NULL DEFAULT '0',
  `to_lat` double DEFAULT NULL,
  `to_lon` double DEFAULT NULL,
  `to_name` varchar(255) NOT NULL,
  KEY `from` (`from`,`to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `panorama`
--

CREATE TABLE IF NOT EXISTS `panorama` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lat` float NOT NULL,
  `lng` double NOT NULL,
  `hdg` double NOT NULL,
  `name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `trackid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trackid` (`trackid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `track`
--

CREATE TABLE IF NOT EXISTS `track` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


