-- phpMyAdmin SQL Dump
-- version 2.6.2-pl1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Aug 11, 2005 at 11:43 PM
-- Server version: 4.1.12
-- PHP Version: 5.0.4
-- 
-- Database: `trisweb_zenphoto`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `albums`
-- 

CREATE TABLE IF NOT EXISTS `albums` (
  `id` int(11) NOT NULL auto_increment,
  `folder` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `desc` text,
  `date` datetime default NULL,
  `place` varchar(255) default NULL,
  `show` int(1) NOT NULL default '1',
  `thumb` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `folder` (`folder`)
) TYPE=InnoDB AUTO_INCREMENT=17 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `comments`
-- 

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL auto_increment,
  `imageid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `website` varchar(255) default NULL,
  `date` datetime default NULL,
  `comment` text NOT NULL,
  `inmoderation` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `imageid` (`imageid`)
) TYPE=InnoDB AUTO_INCREMENT=35 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `images`
-- 

CREATE TABLE IF NOT EXISTS `images` (
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
) TYPE=InnoDB AUTO_INCREMENT=1677 ;

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
