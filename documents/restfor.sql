-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Sam 31 Août 2013 à 13:23
-- Version du serveur: 5.1.66-0+squeeze1
-- Version de PHP: 5.3.3-7+squeeze16

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `restfor`
--

-- --------------------------------------------------------

--
-- Structure de la table `bugs`
--

CREATE TABLE IF NOT EXISTS `bugs` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(150) NOT NULL,
  `url` varchar(250) DEFAULT NULL,
  `browser` varchar(250) DEFAULT NULL,
  `screen` varchar(250) DEFAULT NULL,
  `whatdone` text NOT NULL,
  `whathad` text NOT NULL,
  `whatshould` text NOT NULL,
  `console` text NOT NULL,
  `security` tinyint(4) NOT NULL,
  `usermail` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'join:organizations&users',
  `type` tinyint(3) unsigned NOT NULL COMMENT 'link:contactTypes',
  `value` varchar(50) NOT NULL COMMENT 'label',
  PRIMARY KEY (`id`),
  UNIQUE KEY `value` (`value`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contacts_organizations`
--

CREATE TABLE IF NOT EXISTS `contacts_organizations` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `contacts_id` mediumint(8) unsigned NOT NULL COMMENT 'link:contacts',
  `organizations_id` mediumint(8) unsigned NOT NULL COMMENT 'link:organizations',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Bridge` (`contacts_id`,`organizations_id`),
  KEY `contacts_id` (`contacts_id`),
  KEY `organizations_id` (`organizations_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Structure de la table `contacts_users`
--

CREATE TABLE IF NOT EXISTS `contacts_users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `contacts_id` mediumint(8) unsigned NOT NULL COMMENT 'link:contacts',
  `users_id` mediumint(8) unsigned NOT NULL COMMENT 'link:users',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Bridge` (`contacts_id`,`users_id`),
  KEY `contacts_id` (`contacts_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

--
-- Structure de la table `contactTypes`
--

CREATE TABLE IF NOT EXISTS `contactTypes` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT 'join:contacts',
  `name` varchar(30) NOT NULL,
  `protocol` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`protocol`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Contenu de la table `contactTypes`
--

INSERT INTO `contactTypes` (`id`, `name`, `protocol`) VALUES
(3, 'fax', NULL),
(6, 'gsm', 'tel:'),
(4, 'irc', 'irc:'),
(2, 'mail', 'mailto:'),
(1, 'tel', 'tel:'),
(5, 'web', 'http://');

--
-- Structure de la table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT 'join:rights.id&users.id|ref:users.group',
  `name` varchar(50) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `label` varchar(150) NOT NULL,
  `description` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Contenu de la table `groups`
--

INSERT INTO `groups` (`id`, `name`, `label`, `description`) VALUES
(0, 'visitors', 'Anonymous users (not connected)', NULL),
(1, 'users', 'Users (connected)', NULL),
(2, 'webmasters', 'Application developper', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `groups_rights`
--

CREATE TABLE IF NOT EXISTS `groups_rights` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `groups_id` tinyint(3) unsigned NOT NULL COMMENT 'link:groups',
  `rights_id` mediumint(8) unsigned NOT NULL COMMENT 'link:rights',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Bridge` (`groups_id`,`rights_id`),
  KEY `groups_id` (`groups_id`),
  KEY `rights_id` (`rights_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Contenu de la table `groups_rights`
--

INSERT INTO `groups_rights` (`id`, `groups_id`, `rights_id`) VALUES
(2, 0, 2),
(1, 2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `groups_users`
--

CREATE TABLE IF NOT EXISTS `groups_users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `groups_id` tinyint(3) unsigned NOT NULL COMMENT 'link:groups',
  `users_id` mediumint(8) unsigned NOT NULL COMMENT 'link:users',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Bridge` (`groups_id`,`users_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Structure de la table `organizations`
--

CREATE TABLE IF NOT EXISTS `organizations` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'join:places.id&contacts.id&organizationTypes.id|ref:users.organization',
  `label` varchar(50) NOT NULL,
  `legalLabel` varchar(200) DEFAULT NULL,
  `vatNumber` varchar(13) DEFAULT NULL,
  `companyCode` varchar(20) NOT NULL,
  `referrer` mediumint(8) unsigned DEFAULT NULL COMMENT 'link:users',
  `place` mediumint(8) unsigned NOT NULL COMMENT 'link:places',
  PRIMARY KEY (`id`),
  KEY `referrer` (`referrer`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=41 ;

--
-- Structure de la table `organizations_places`
--

CREATE TABLE IF NOT EXISTS `organizations_places` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `organizations_id` mediumint(8) unsigned NOT NULL COMMENT 'link:organizations',
  `places_id` mediumint(8) unsigned NOT NULL COMMENT 'link:places',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Bridge` (`organizations_id`,`places_id`),
  KEY `organizations_id` (`organizations_id`),
  KEY `places_id` (`places_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Structure de la table `organizationTypes`
--

CREATE TABLE IF NOT EXISTS `organizationTypes` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT 'join:organizations',
  `label` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Structure de la table `organizationTypes_organizations`
--

CREATE TABLE IF NOT EXISTS `organizationTypes_organizations` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `organizations_id` mediumint(8) unsigned NOT NULL COMMENT 'link:organizations',
  `organizationTypes_id` tinyint(3) unsigned NOT NULL COMMENT 'link:organizationTypes',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Bridge` (`organizations_id`,`organizationTypes_id`),
  KEY `organizations_id` (`organizations_id`),
  KEY `organizationTypes_id` (`organizationTypes_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `places`
--

CREATE TABLE IF NOT EXISTS `places` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'join:organizations&users',
  `address` varchar(50) DEFAULT NULL COMMENT 'label',
  `address2` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL COMMENT 'label',
  `postalCode` varchar(10) DEFAULT NULL COMMENT 'label',
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Structure de la table `places_users`
--

CREATE TABLE IF NOT EXISTS `places_users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `places_id` mediumint(8) unsigned NOT NULL COMMENT 'link:places',
  `users_id` mediumint(8) unsigned NOT NULL COMMENT 'link:users',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Bridge` (`places_id`,`users_id`),
  KEY `places_id` (`places_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Structure de la table `rights`
--

CREATE TABLE IF NOT EXISTS `rights` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'join:groups&users',
  `label` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `path` varchar(250) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `enablings` set('OPTIONS','HEAD','GET','POST','PUT','DELETE') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `rights`
--

INSERT INTO `rights` (`id`, `label`, `description`, `path`, `enablings`) VALUES
(1, 'Universal rights', 'All rights, including doing mistakes.', '/(.*)', 'OPTIONS,HEAD,GET,POST,PUT,DELETE'),
(2, 'Public files rights', 'Give access to public files.', '/(fs|mpfs)/(public|db)/(.*)', 'OPTIONS,HEAD,GET'),
(3, 'User right', 'A user can see his profile.', '/users/(me|{user.login}|out)(.*)\\?type=restricted', 'OPTIONS,HEAD,GET,PUT');

-- --------------------------------------------------------

--
-- Structure de la table `rights_users`
--

CREATE TABLE IF NOT EXISTS `rights_users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `rights_id` mediumint(8) unsigned NOT NULL COMMENT 'link:rights',
  `users_id` mediumint(8) unsigned NOT NULL COMMENT 'link:users',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Bridge` (`rights_id`,`users_id`),
  KEY `rights_id` (`rights_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'join:groups.id&rights.id&places.id&contacts.id',
  `login` varchar(20) NOT NULL,
  `firstname` varchar(50) NOT NULL COMMENT 'label',
  `lastname` varchar(50) NOT NULL COMMENT 'label',
  `role` varchar(150) NOT NULL,
  `password` varchar(40) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `group` tinyint(3) unsigned NOT NULL COMMENT 'link:groups.id',
  `lastconnection` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `organization` mediumint(8) unsigned NOT NULL COMMENT 'link:organizations',
  `active` enum('0','1') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `group` (`group`),
  KEY `organization` (`organization`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Structure de la table `visitors`
--

CREATE TABLE IF NOT EXISTS `visitors` (
  `user` mediumint(8) unsigned DEFAULT NULL COMMENT 'link:users',
  `sessid` varchar(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ip` varchar(16) CHARACTER SET ascii NOT NULL,
  `code` varchar(6) CHARACTER SET ascii DEFAULT NULL,
  `lastrequest` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `user` (`user`),
  KEY `sessid` (`sessid`),
  KEY `ip` (`ip`),
  KEY `lastrequest` (`lastrequest`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`type`) REFERENCES `contactTypes` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `contacts_organizations`
--
ALTER TABLE `contacts_organizations`
  ADD CONSTRAINT `contacts_organizations_ibfk_1` FOREIGN KEY (`contacts_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `contacts_organizations_ibfk_2` FOREIGN KEY (`organizations_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `contacts_users`
--
ALTER TABLE `contacts_users`
  ADD CONSTRAINT `contacts_users_ibfk_1` FOREIGN KEY (`contacts_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `contacts_users_ibfk_2` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `groups_rights`
--
ALTER TABLE `groups_rights`
  ADD CONSTRAINT `groups_rights_ibfk_1` FOREIGN KEY (`groups_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `groups_rights_ibfk_2` FOREIGN KEY (`rights_id`) REFERENCES `rights` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `groups_users`
--
ALTER TABLE `groups_users`
  ADD CONSTRAINT `groups_users_ibfk_1` FOREIGN KEY (`groups_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `groups_users_ibfk_2` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `organizations`
--
ALTER TABLE `organizations`
  ADD CONSTRAINT `organizations_ibfk_1` FOREIGN KEY (`referrer`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `organizations_places`
--
ALTER TABLE `organizations_places`
  ADD CONSTRAINT `organizations_places_ibfk_1` FOREIGN KEY (`organizations_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `organizations_places_ibfk_2` FOREIGN KEY (`places_id`) REFERENCES `places` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `organizationTypes_organizations`
--
ALTER TABLE `organizationTypes_organizations`
  ADD CONSTRAINT `organizationTypes_organizations_ibfk_1` FOREIGN KEY (`organizations_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `organizationTypes_organizations_ibfk_2` FOREIGN KEY (`organizationTypes_id`) REFERENCES `organizationTypes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `places_users`
--
ALTER TABLE `places_users`
  ADD CONSTRAINT `places_users_ibfk_1` FOREIGN KEY (`places_id`) REFERENCES `places` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `places_users_ibfk_2` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rights_users`
--
ALTER TABLE `rights_users`
  ADD CONSTRAINT `rights_users_ibfk_1` FOREIGN KEY (`rights_id`) REFERENCES `rights` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rights_users_ibfk_2` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`group`) REFERENCES `groups` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`organization`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `visitors`
--
ALTER TABLE `visitors`
  ADD CONSTRAINT `visitors_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
