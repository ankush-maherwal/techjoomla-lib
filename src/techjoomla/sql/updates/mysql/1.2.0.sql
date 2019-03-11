--
-- Table structure for table `#__tj_houseKeeping`
--

CREATE TABLE IF NOT EXISTS `#__tj_houseKeeping` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `title` varchar(100) NOT NULL COMMENT 'The descriptive title for the housekeeping task',
  `client` varchar(50) NOT NULL COMMENT 'Client extension name',
  `version` varchar(11) NOT NULL COMMENT 'Version for housekeeping task',
  `status` tinyint(3) NOT NULL DEFAULT 0,
  `lastExecutedOn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `params` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `#__tj_media_files`
--

CREATE TABLE IF NOT EXISTS `#__tj_media_files` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL,
  `type` varchar(250) NOT NULL,
  `path` varchar(250) COLLATE utf8mb4_bin NOT NULL,
  `state` tinyint(1) NOT NULL,
  `source` varchar(250) NOT NULL,
  `original_filename` varchar(250) COLLATE utf8mb4_bin NOT NULL,
  `size` int(11) NOT NULL,
  `storage` varchar(250) NOT NULL,
  `created_by` int(11) NOT NULL,
  `access` tinyint(1) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `params` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;

--
-- Table structure for table `#__tj_media_files_xref`
--

CREATE TABLE IF NOT EXISTS `#__tj_media_files_xref` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `client` varchar(250) NOT NULL,
  `is_gallery` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;
