<p align="center">
  <h1 align="center">OIDC-cube</h1>
  <p align="center"><i>OpenID Connect and passkey login for Roundcube 1.7.3</i></p>
</p>

---

OIDC-cube provides OpenID Connect login, locally encrypted IMAP credential storage, and WebAuthn passkey login for Roundcube 1.7.3. It is designed for deployments where an external identity provider authenticates a mailbox user while IMAP continues to require a password.

Maintained by [Ra's al Ghul](https://alghul.com).

## Features

- OpenID Connect Authorization Code flow with PKCE, discovery, state, and nonce validation
- Strict issuer, audience, authorized-party, signature-algorithm, and time-claim validation
- First-login IMAP password capture on the Roundcube login page; no mailbox password is sent to the identity provider
- AES-256-GCM encryption for stored IMAP credentials
- Passwordless WebAuthn login using discoverable credentials and required user verification
- RP-initiated logout support
- MySQL/MariaDB, PostgreSQL, and SQLite schema files

## Requirements

- Roundcube 1.7.3
- PHP 8.2 or newer with cURL, JSON, OpenSSL, and mbstring
- An OpenID Connect provider supporting discovery, Authorization Code flow, and PKCE `S256`
- HTTPS for Roundcube, the issuer, registered redirect URIs, and optional logout URI
- MySQL/MariaDB, PostgreSQL, or SQLite

## Installation

Install the plugin in the Roundcube plugin directory and install its Composer dependencies:

```sh
composer require imrasalghul/oidc-cube
```

Enable `roundcube_oidc` in Roundcube's `plugins` configuration. Create the credential and passkey tables using the database-specific schema:

```sh
mysql roundcube < plugins/roundcube_oidc/SQL/mysql.initial.sql
psql roundcube < plugins/roundcube_oidc/SQL/postgres.initial.sql
sqlite3 /path/to/roundcube.db < plugins/roundcube_oidc/SQL/sqlite.initial.sql
```

Existing installations require only the matching `*.webauthn.sql` migration. SQL table names must include the configured Roundcube `db_prefix`, when applicable.

## Configuration

Copy `config.inc.php.dist` to `config.inc.php`. The configuration defines the OIDC issuer, client ID, client secret when applicable, exact redirect URI, accepted signing algorithms, and passkey RP values.

Generate the database encryption key once:

```sh
openssl rand -base64 32
```

Store the result in `oidc_password_encryption_key`. This key must remain outside the database and receive the same protection as other application secrets. Key loss or rotation invalidates saved mailbox credentials.

`oidc_passkey_origin` must be the exact public HTTPS origin of Roundcube. `oidc_passkey_rp_id` must be that origin's hostname. The plugin does not derive either setting from the request `Host` header.

## Provider registration

Register an OIDC client with an exact callback URI, for example:

```text
https://mail.example.com/?_task=login&_action=plugin.oidc
```

The provider must expose `{issuer}/.well-known/openid-configuration`, support Authorization Code with PKCE `S256`, and issue signed ID tokens with `iss`, `sub`, `aud`, `exp`, `iat`, and `nonce` claims. When UserInfo is enabled, its `sub` value must match the ID token subject.

## Security

OIDC-cube validates OIDC state, nonce, issuer, audience, authorized party, token age, signature algorithm, and UserInfo subject consistency. Discovery endpoints are host-restricted, DNS-pinned, size-limited, and do not follow redirects. Passkey operations use exact origin and RP ID binding, single-use challenges, signature verification, replay-safe counter updates, and user verification.

IMAP credentials are encrypted with AES-256-GCM and context-bound authenticated data. This protects against a database-only disclosure; it cannot protect against an attacker with access to both the database and the application encryption key, or code execution in the Roundcube application.

## License

OIDC-cube is released under the [MIT License](LICENSE).

## Attribution

OIDC-cube is an adapted distribution of [pulsejet/roundcube-oidc](https://github.com/pulsejet/roundcube-oidc). WebAuthn support uses [web-auth/webauthn-lib](https://github.com/web-auth/webauthn-lib). Historical WebAuthn integration work was informed by [bartnv/twofactor_webauthn](https://github.com/bartnv/twofactor_webauthn); no code from that project is bundled in this repository.
