CREATE TABLE oidc_credentials (
    credential_key char(64) PRIMARY KEY,
    user_id integer NOT NULL
        REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    oidc_uid varchar(255) NOT NULL,
    encrypted_password text NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone DEFAULT now() NOT NULL
);


CREATE TABLE oidc_webauthn_credentials (
    credential_id_hash char(64) PRIMARY KEY,
    credential_key char(64) NOT NULL,
    credential_record text NOT NULL,
    record_version integer DEFAULT 1 NOT NULL,
    label varchar(100) NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone DEFAULT now() NOT NULL,
    last_used timestamp with time zone
);

CREATE INDEX ix_oidc_webauthn_credential_key
    ON oidc_webauthn_credentials (credential_key);
