CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(255) DEFAULT NULL,
  `service` varchar(255) DEFAULT NULL,
  `version` double DEFAULT NULL,
  `transport` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_service` (`account`,`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_id` int(11) DEFAULT NULL,
  `sender` varchar(255) DEFAULT NULL,
  `time` varchar(32) DEFAULT NULL,
  `message` tinytext,
  `html` tinytext,
  `created` datetime DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `element` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_id` (`log_id`),
  KEY `created` (`created`),
  KEY `sender` (`sender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;