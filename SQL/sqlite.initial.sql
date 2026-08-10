CREATE TABLE oidc_credentials (
  credential_key char(64) NOT NULL PRIMARY KEY,
  user_id integer NOT NULL
    REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  oidc_uid varchar(255) NOT NULL,
  encrypted_password text NOT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL
);

CREATE INDEX ix_oidc_credentials_user_id ON oidc_credentials(user_id);

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
