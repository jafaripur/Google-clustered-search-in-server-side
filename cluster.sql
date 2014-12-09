CREATE TABLE `position` (
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  KEY `lat_lng` (`latitude`,`longitude`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
