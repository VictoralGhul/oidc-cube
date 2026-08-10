CREATE TABLE `oidc_credentials` (
 `credential_key` char(64) BINARY NOT NULL,
 `user_id` int(10) UNSIGNED NOT NULL,
 `oidc_uid` varchar(255) BINARY NOT NULL,
 `encrypted_password` text NOT NULL,
 `created` datetime NOT NULL,
 `updated` datetime NOT NULL,
 PRIMARY KEY (`credential_key`),
 CONSTRAINT `user_id_fk_oidc_credentials` FOREIGN KEY (`user_id`)
   REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ROW_FORMAT=DYNAMIC ENGINE=INNODB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
