CREATE TABLE oidc_webauthn_credentials (
  credential_id_hash char(64) NOT NULL PRIMARY KEY,
  credential_key char(64) NOT NULL,
  credential_record text NOT NULL,
  record_version integer NOT NULL DEFAULT 1,
  label varchar(100) NOT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL,
  last_used datetime
);

CREATE INDEX ix_oidc_webauthn_credential_key
  ON oidc_webauthn_credentials(credential_key);
