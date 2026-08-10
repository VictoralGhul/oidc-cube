CREATE TABLE `oidc_webauthn_credentials` (
 `credential_id_hash` char(64) BINARY NOT NULL,
 `credential_key` char(64) BINARY NOT NULL,
 `credential_record` mediumtext NOT NULL,
 `record_version` int(10) UNSIGNED NOT NULL DEFAULT 1,
 `label` varchar(100) NOT NULL,
 `created` datetime NOT NULL,
 `updated` datetime NOT NULL,
 `last_used` datetime DEFAULT NULL,
 PRIMARY KEY (`credential_id_hash`),
 KEY `ix_oidc_webauthn_credential_key` (`credential_key`)
) ROW_FORMAT=DYNAMIC ENGINE=INNODB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
