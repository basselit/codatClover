<?php

namespace Codatsoft\CodatClover\Models;

// one clover oauth token response (code exchange or refresh). expirations are
// unix epoch seconds, as clover sends them. a refresh response may omit the
// refresh token, in which case the caller keeps the one it has.
final readonly class CLOAuthToken
{
    public function __construct(
        public string $accessToken,
        public ?int $accessExpiresAt = null,
        public ?string $refreshToken = null,
        public ?int $refreshExpiresAt = null,
    ) {

    }

    public static function fromResponse(array $json): ?CLOAuthToken
    {
        if (empty($json['access_token']))
        {
            return null;
        }

        return new CLOAuthToken(
            (string) $json['access_token'],
            isset($json['access_token_expiration']) ? (int) $json['access_token_expiration'] : null,
            isset($json['refresh_token']) ? (string) $json['refresh_token'] : null,
            isset($json['refresh_token_expiration']) ? (int) $json['refresh_token_expiration'] : null,
        );

    }

}
