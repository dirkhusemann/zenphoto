-- phpMyAdmin SQL Dump
-- version 2.6.0-pl2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Jun 04, 2005 at 09:32 PM
-- Server version: 4.1.7
-- PHP Version: 5.0.2
-- 
-- Database: `trisweb_zenphoto`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `albums`
-- 

DROP TABLE IF EXISTS `albums`;
CREATE TABLE `albums` (
  `id` int(11) NOT NULL auto_increment,
  `folder` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `desc` text,
  `date` date default NULL,
  `place` varchar(255) default NULL,
  `show` int(1) NOT NULL default '1',
  `thumb` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `folder_2` (`folder`),
  KEY `folder` (`folder`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table structure for table `comments`
-- 

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL auto_increment,
  `imageid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `comment` text NOT NULL,
  `inmoderation` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `imageid` (`imageid`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table structure for table `images`
-- 

DROP TABLE IF EXISTS `images`;
CREATE TABLE `images` (
  `id` int(11) NOT NULL auto_increment,
  `albumid` int(11) NOT NULL default '0',
  `filename` varchar(255) NOT NULL default '',
  `title` varchar(255) default NULL,
  `desc` text,
  `commentson` int(1) NOT NULL default '1',
  `show` int(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `filename` (`filename`,`albumid`),
  KEY `albumid` (`albumid`)
) ENGINE=InnoDB;

-- 
-- Constraints for dumped tables
-- 

-- 
-- Constraints for table `comments`
-- 
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`imageid`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Constraints for table `images`
-- 
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`albumid`) REFERENCES `albums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
