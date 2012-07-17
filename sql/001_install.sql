-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 17, 2012 at 08:53 PM
-- Server version: 5.5.25
-- PHP Version: 5.3.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `n0tice_signals`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(64) NOT NULL DEFAULT '',
  `user_realname` varchar(1000) NOT NULL,
  `user_email` varchar(1000) NOT NULL,
  `user_location` varchar(1000) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL,
  `user_lastonline` datetime NOT NULL,
  `user_ip` varchar(15) DEFAULT NULL,
  `user_iplast` varchar(15) DEFAULT NULL,
  `user_dob` datetime DEFAULT NULL,
  `user_status` varchar(50) NOT NULL DEFAULT '0',
  `display_name` text,
  `role` varchar(100) NOT NULL DEFAULT 'member',
  `twitter_oauth` varchar(1000) NOT NULL DEFAULT '',
  `facebook_oauth` varchar(1000) NOT NULL DEFAULT '',
  `invite_used` varchar(100) NOT NULL DEFAULT '',
  `invite_share` varchar(100) NOT NULL DEFAULT '',
  `invite_count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_realname`(333)),
  FULLTEXT KEY `display_name` (`display_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4838 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_messages`
--

CREATE TABLE IF NOT EXISTS `users_messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seq` int(10) unsigned NOT NULL DEFAULT '1',
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_on_ip` varchar(16) NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY (`message_id`,`seq`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=236 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_messages_recips`
--

CREATE TABLE IF NOT EXISTS `users_messages_recips` (
  `message_id` int(10) unsigned NOT NULL,
  `seq` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `status` char(1) NOT NULL DEFAULT 'N',
  KEY `m2r1` (`message_id`,`status`) USING BTREE,
  KEY `m2r2` (`uid`,`status`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users_meta`
--

CREATE TABLE IF NOT EXISTS `users_meta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=33388 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_notifications`
--

CREATE TABLE IF NOT EXISTS `users_notifications` (
  `notification_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `message` varchar(5000) NOT NULL,
  `webUrl` varchar(5000) NOT NULL,
  `status` varchar(10) DEFAULT 'r',
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3732 ;
