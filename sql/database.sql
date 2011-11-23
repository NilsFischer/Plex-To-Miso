CREATE TABLE IF NOT EXISTS `checkinLog` (
  `checkinLogID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `misoCheckinID` int(10) unsigned NOT NULL,
  `checkinTimestamp` timestamp NULL DEFAULT NULL,
  `comment` varchar(255) NOT NULL,
  `userID` int(10) unsigned NOT NULL,
  `mediaID` int(10) unsigned NOT NULL,
  `episodeTitle` varchar(128) DEFAULT NULL,
  `season` int(11) DEFAULT NULL,
  `episode` int(11) DEFAULT NULL,
  `postedToFacebook` tinyint(1) NOT NULL DEFAULT '0',
  `postedToTwitter` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`checkinLogID`),
  KEY `mediaID` (`mediaID`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mediaTypes` (
  `mediaTypeID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`mediaTypeID`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `misoMedia` (
  `misoMediaID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mediaID` int(10) unsigned NOT NULL,
  `title` varchar(128) NOT NULL,
  `releaseYear` int(4) DEFAULT NULL,
  `posterImage` varchar(255) DEFAULT NULL,
  `posterCached` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `bannerCached` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `typeID` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`misoMediaID`),
  KEY `mediaID` (`mediaID`),
  KEY `typeID` (`typeID`)
) ENGINE=InnoDB;


ALTER TABLE `checkinLog`
  ADD CONSTRAINT `checkinLog_ibfk_1` FOREIGN KEY (`mediaID`) REFERENCES `misoMedia` (`misoMediaID`) ON UPDATE CASCADE;

ALTER TABLE `misoMedia`
  ADD CONSTRAINT `misoMedia_ibfk_1` FOREIGN KEY (`typeID`) REFERENCES `mediaTypes` (`mediaTypeID`) ON UPDATE CASCADE;
