<?php

namespace Codatsoft\CodatClover\Models;

// the clover app's oauth credentials and hosts. the package reads no config:
// the host app builds this once (from its own config) and binds it in the container.
// oauthUrl is the authorize host (www.clover.com / sandbox.dev.clover.com),
// apiUrl the token host (api.clover.com / apisandbox.dev.clover.com) - they differ.
final readonly class CLOAuthConfig
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $oauthUrl,
        public string $apiUrl,
    ) {

    }

}
