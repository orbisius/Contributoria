-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 17, 2012 at 02:41 PM
-- Server version: 5.5.25
-- PHP Version: 5.3.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `contributoria`
--

-- --------------------------------------------------------

--
-- Table structure for table `backoffice_users`
--

DROP TABLE IF EXISTS `backoffice_users`;
CREATE TABLE IF NOT EXISTS `backoffice_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(20) NOT NULL,
  `lastname` varchar(40) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(40) NOT NULL,
  `password_valid` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `email` varchar(340) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_password_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`(255))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `backoffice_users`
--

INSERT INTO `backoffice_users` (`id`, `firstname`, `lastname`, `username`, `password`, `password_valid`, `email`, `phone_number`, `last_login`, `last_password_update`) VALUES
(1, 'Admin', '', 'john.doe', '9e319119dd00b6f1916133b20b5c63f3ae7e84d2', 0, '12755@mailinator.com', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `backoffice_users_groups`
--

DROP TABLE IF EXISTS `backoffice_users_groups`;
CREATE TABLE IF NOT EXISTS `backoffice_users_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_group` (`group_id`,`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_group_id` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `backoffice_users_groups`
--

INSERT INTO `backoffice_users_groups` (`id`, `group_id`, `user_id`) VALUES
(1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

DROP TABLE IF EXISTS `flags`;
CREATE TABLE IF NOT EXISTS `flags` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `active_on_dev` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `active_on_prod` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name` (`name`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `flags`
--

INSERT INTO `flags` (`id`, `name`, `description`, `active_on_dev`, `active_on_prod`) VALUES
(1, 'backoffice-flags', 'Allows user to manage the flags', 1, 0),
(2, 'backoffice-groups', 'Allows user to manage the user groups', 1, 0),
(3, 'backoffice-index', 'Default entry point in the application', 1, 0),
(4, 'backoffice-privileges', 'Allows the users to perform CRUD operations on privileges', 1, 0),
(5, 'backoffice-profile', 'Allows user to manage their profile data', 1, 0),
(6, 'backoffice-system', 'Allow the admins to manage critical info, users, groups, permissions, etc.', 1, 0),
(7, 'backoffice-users', 'Allows the users to perform CRUD operations on other users', 1, 0),
(8, 'frontend-index', 'Default entry point in the application', 1, 0),
(9, 'backoffice-testing', 'Some testing permissions', 1, 0),
(10, 'frontend-testing', 'Some testing permissions', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `flippers`
--

DROP TABLE IF EXISTS `flippers`;
CREATE TABLE IF NOT EXISTS `flippers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL,
  `flag_id` int(11) unsigned NOT NULL,
  `privilege_id` int(11) unsigned NOT NULL,
  `allow` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_flag_id` (`flag_id`),
  KEY `idx_privilege_id` (`privilege_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `flippers`
--

INSERT INTO `flippers` (`id`, `group_id`, `flag_id`, `privilege_id`, `allow`) VALUES
(1, 3, 8, 26, 1),
(2, 3, 8, 27, 1),
(3, 2, 8, 26, 1),
(4, 2, 8, 27, 1);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(25) DEFAULT NULL,
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `parent_id`) VALUES
(1, 'administrators', 0),
(2, 'guests', 0),
(3, 'members', 0);

-- --------------------------------------------------------

--
-- Table structure for table `privileges`
--

DROP TABLE IF EXISTS `privileges`;
CREATE TABLE IF NOT EXISTS `privileges` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `flag_id` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name_flag_id` (`name`,`flag_id`),
  KEY `idx_resource_id` (`flag_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

--
-- Dumping data for table `privileges`
--

INSERT INTO `privileges` (`id`, `name`, `flag_id`, `description`) VALUES
(1, 'index', '1', 'Allows the user to view all the flags registered in the application'),
(2, 'toggleprod', '1', 'Change the active status of a flag on production'),
(3, 'toggledev', '1', 'Change the active status of a flag on development'),
(4, 'index', '2', 'Allows the user to view all the user groups registered\nin the application'),
(5, 'add', '2', 'Allows the user to add another user group in the\napplication'),
(6, 'edit', '2', 'Edits an existing user group'),
(7, 'delete', '2', 'Allows the user to delete an existing user group. All the users attached to\nthis group *WILL NOT* be deleted, they will just lose all'),
(8, 'flippers', '2', 'Allows the user to manage individual permissions for each\nuser group'),
(9, 'index', '3', 'Controller''s entry point'),
(10, 'index', '4', 'Allows the user to view all the permissions registered\nin the application'),
(11, 'add', '4', 'Allows the user to add another privilege in the application'),
(12, 'edit', '4', 'Edits an existing privilege'),
(13, 'delete', '4', 'Allows the user to delete an existing privilege. All the flippers related to\nthis privilege will be removed'),
(14, 'index', '5', 'Allows users to see their dashboards'),
(15, 'edit', '5', 'Allows the users to update their profiles'),
(16, 'change-password', '5', 'Allows users to change their passwords'),
(17, 'login', '5', 'Allows users to log into the application'),
(18, 'logout', '5', 'Allows users to log out of the application'),
(19, 'index', '6', 'Controller''s entry point'),
(20, 'example', '6', 'Theme example page'),
(21, 'index', '7', 'Allows users to see all other users that are registered in\nthe application'),
(22, 'add', '7', 'Allows users to add new users in the application\n(should be reserved for administrators)'),
(23, 'edit', '7', 'Allows users to edit another users'' data\n(should be reserved for administrators)'),
(24, 'view', '7', 'Allows users to see other users'' profiles'),
(25, 'delete', '7', 'Allows users to logically delete other users\n(should be reserved for administrators)'),
(26, 'index', '8', 'Controller''s entry point'),
(27, 'static', '8', 'Static Pages'),
(28, 'zfdebug', '9', 'Debug toolbar'),
(29, 'zfdebug', '10', 'Debug toolbar');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
