<?php

use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    private roundcube_oidc $plugin;

    protected function setUp(): void
    {
        rcmail::configure([
            'oidc_password_encryption_key' => base64_encode(str_repeat("\xA5", 32)),
            'oidc_password_prompt_ttl' => 300,
            'oidc_passkey_origin' => 'https://mail.example.com',
            'oidc_passkey_rp_id' => 'mail.example.com',
        ]);
        $this->plugin = new roundcube_oidc();
    }

    public function testCredentialEncryptionRoundTripAndRandomNonce(): void
    {
        $first = $this->invoke('encryptSecret', ['correct horse battery staple', 'imap-password:key']);
        $second = $this->invoke('encryptSecret', ['correct horse battery staple', 'imap-password:key']);

        self::assertNotSame($first, $second);
        self::assertSame(
            'correct horse battery staple',
            $this->invoke('decryptSecret', [$first, 'imap-password:key'])
        );
    }

    public function testCredentialCiphertextAndContextAreAuthenticated(): void
    {
        $encrypted = $this->invoke('encryptSecret', ['secret', 'imap-password:key']);
        $payload = base64_decode($encrypted, true);
        $payload[strlen($payload) - 1] = chr(ord($payload[strlen($payload) - 1]) ^ 1);

        self::assertNull($this->invoke('decryptSecret', [base64_encode($payload), 'imap-password:key']));
        self::assertNull($this->invoke('decryptSecret', [$encrypted, 'imap-password:other-key']));
    }

    public function testRejectsInvalidEncryptionKey(): void
    {
        rcmail::configure(['oidc_password_encryption_key' => 'not-a-key']);

        $this->expectException(RuntimeException::class);
        $this->invoke('encryptSecret', ['secret', 'context']);
    }

    public function testPendingLoginExpires(): void
    {
        $base = [
            'uid' => 'user@example.com',
            'issuer' => 'https://id.example.com',
            'sub' => '123',
            'credential_key' => str_repeat('a', 64),
            'id_token' => 'encrypted',
        ];

        self::assertTrue($this->invoke('pendingLoginIsFresh', [$base + ['created_at' => time()]]));
        self::assertFalse($this->invoke('pendingLoginIsFresh', [$base + ['created_at' => time() - 301]]));
    }

    public function testOnlyAbsoluteHttpsUrlsAreAccepted(): void
    {
        $this->invoke('assertHttpsUrl', ['https://mail.example.com/callback', 'test']);

        $this->expectException(Jumbojett\OpenIDConnectClientException::class);
        $this->invoke('assertHttpsUrl', ['http://mail.example.com/callback', 'test']);
    }

    public function testPasskeyUserHandleIsStableOpaqueAndContextBound(): void
    {
        $first = $this->invoke('passkeyUserHandle', [str_repeat('a', 64)]);
        $second = $this->invoke('passkeyUserHandle', [str_repeat('a', 64)]);
        $other = $this->invoke('passkeyUserHandle', [str_repeat('b', 64)]);

        self::assertSame(32, strlen($first));
        self::assertSame($first, $second);
        self::assertNotSame($first, $other);
        self::assertStringNotContainsString(str_repeat('a', 16), $first);
    }

    public function testPasskeyCeremonyIsSingleWindowAndExpires(): void
    {
        self::assertTrue($this->invoke('passkeyCeremonyIsFresh', [[
            'created_at' => time(),
            'options' => '{"challenge":"example"}',
        ]]));
        self::assertFalse($this->invoke('passkeyCeremonyIsFresh', [[
            'created_at' => time() - 301,
            'options' => '{"challenge":"example"}',
        ]]));
    }

    public function testPasskeyRpIdMustExactlyMatchOriginHost(): void
    {
        self::assertSame('mail.example.com', $this->invoke('passkeyRpId', []));

        rcmail::configure([
            'oidc_password_encryption_key' => base64_encode(str_repeat("\xA5", 32)),
            'oidc_passkey_origin' => 'https://mail.example.com',
            'oidc_passkey_rp_id' => 'example.com',
        ]);
        $this->expectException(RuntimeException::class);
        $this->invoke('passkeyRpId', []);
    }

    public function testPasskeyServicesUseRequiredUvAndDiscoverableLogin(): void
    {
        $serializer = $this->invoke('webauthnSerializer', []);
        $options = Webauthn\PublicKeyCredentialRequestOptions::create(
            str_repeat("\x01", 32),
            rpId: 'mail.example.com',
            allowCredentials: [],
            userVerification: Webauthn\PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: 300000
        );
        $json = json_decode($serializer->serialize($options, 'json'), true, 16, JSON_THROW_ON_ERROR);

        self::assertSame('mail.example.com', $json['rpId']);
        self::assertSame('required', $json['userVerification']);
        self::assertSame([], $json['allowCredentials']);
        self::assertInstanceOf(
            Webauthn\AuthenticatorAssertionResponseValidator::class,
            $this->invoke('assertionValidator', [])
        );
    }

    /** @param list<mixed> $arguments */
    private function invoke(string $method, array $arguments)
    {
        $reflection = new ReflectionMethod($this->plugin, $method);

        return $reflection->invokeArgs($this->plugin, $arguments);
    }
}
