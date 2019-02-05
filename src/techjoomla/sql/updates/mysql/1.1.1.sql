--
-- Table structure for table `#__tj_houseKeeping`
--

CREATE TABLE IF NOT EXISTS `#__tj_houseKeeping` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `title` varchar(100) NOT NULL COMMENT 'The descriptive title for the housekeeping task',
  `client` varchar(50) NOT NULL COMMENT 'Client extension name',
  `version` varchar(11) NOT NULL COMMENT 'Version for housekeeping task',
  `state` tinyint(3) NOT NULL DEFAULT 0,
  `lastExecutedOn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `params` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
