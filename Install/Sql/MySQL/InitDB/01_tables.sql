CREATE TABLE IF NOT EXISTS `exf_proxy_route` (
  `oid` binary(16) NOT NULL,
  `created_on` datetime NOT NULL,
  `modified_on` datetime NOT NULL,
  `created_by_user_oid` binary(16) DEFAULT NULL,
  `modified_by_user_oid` binary(16) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `alias` varchar(100) NOT NULL,
  `app_oid` binary(16) DEFAULT NULL,
  `description` text,
  `route_url` varchar(400) NOT NULL,
  `route_regex_flag` tinyint NOT NULL DEFAULT '0',
  `destination_url` varchar(400) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `destination_connection` binary(16) DEFAULT NULL,
  `handler_class` varchar(400) DEFAULT NULL,
  `handler_uxon` text,
  PRIMARY KEY (`oid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
