<?php

namespace Codatsoft\CodatClover\Business;

use Codatsoft\CodatClover\Models\CLOAuthConfig;
use Codatsoft\CodatClover\Models\CLOAuthToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

// clover app oauth (v2, expiring tokens): the authorize redirect, the code
// exchange, the refresh and the recovery. stateless over the wire - storing
// tokens, locking refreshes and deciding when to refresh stay in the host app.
// clover refresh tokens are single-use, so the caller must serialize refreshes
// per merchant.
// recovery: when a refresh rotated the tokens but its response never reached the
// caller, the caller still holds the previous refresh token. clover keeps that one
// as a "recovery token" (up to 2 weeks, high-trust apps only): a refresh with it
// answers 401 + the header X-Clover-Recovery-Available: true, and recover() trades
// it for a new pair - the pair the caller never saw becomes invalid.
// uses laravel's http client rather than TModelNetwork: callers need the http
// status and clover's error body, a timeout, and connection failures reported
// as a failed result - TNetwork exposes none of these.
class STCloverOAuth
{
    private const string AUTHORIZE = '/oauth/v2/authorize';
    private const string TOKEN = '/oauth/v2/token';
    private const string REFRESH = '/oauth/v2/refresh';
    private const string RECOVERY = '/oauth/v2/recovery';
    private const string RECOVERY_HEADER = 'X-Clover-Recovery-Available';
    private const int TIMEOUT_SECONDS = 15;

    public bool $success = true;
    public string $message = '';
    public int $status = 0;
    public ?array $errorBody = null;
    // set by a failed refresh: clover knows the rejected refresh token as a recovery
    // token, so recover() with that same token can still get a new pair
    public bool $recoveryAvailable = false;

    public function __construct(protected CLOAuthConfig $config, protected HttpFactory $http)
    {

    }

    public function authorizeUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id' => $this->config->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return $this->config->oauthUrl . self::AUTHORIZE . '?' . $query;

    }

    public function exchangeCode(string $code): ?CLOAuthToken
    {
        return $this->post(self::TOKEN, [
            'client_id' => $this->config->clientId,
            'client_secret' => $this->config->clientSecret,
            'code' => $code,
        ], 'code exchange');

    }

    public function refresh(string $refreshToken): ?CLOAuthToken
    {
        return $this->post(self::REFRESH, [
            'client_id' => $this->config->clientId,
            'refresh_token' => $refreshToken,
        ], 'token refresh');

    }

    // $recoveryToken is the refresh token a refresh() just rejected with
    // $recoveryAvailable set - the one the caller holds, not an older one. needs
    // the client secret: clover only recovers for high-trust apps.
    public function recover(string $recoveryToken): ?CLOAuthToken
    {
        return $this->post(self::RECOVERY, [
            'client_id' => $this->config->clientId,
            'client_secret' => $this->config->clientSecret,
            'recovery_token' => $recoveryToken,
        ], 'token recovery');

    }

    private function post(string $endPoint, array $body, string $what): ?CLOAuthToken
    {
        $this->success = true;
        $this->message = '';
        $this->status = 0;
        $this->errorBody = null;
        $this->recoveryAvailable = false;

        try
        {
            $response = $this->http->acceptJson()->asJson()
                ->timeout(self::TIMEOUT_SECONDS)
                ->post($this->config->apiUrl . $endPoint, $body);
        } catch (ConnectionException $e)
        {
            $this->success = false;
            $this->message = 'Clover ' . $what . ' failed: ' . $e->getMessage();
            return null;
        }

        $this->status = $response->status();

        if ($response->failed())
        {
            $this->success = false;
            $this->message = 'Clover ' . $what . ' failed (status ' . $this->status . ')';
            $this->errorBody = $response->json();
            $this->recoveryAvailable = $this->status == 401
                && strcasecmp(trim((string) $response->header(self::RECOVERY_HEADER)), 'true') == 0;
            return null;
        }

        $token = CLOAuthToken::fromResponse($response->json() ?? []);

        if (is_null($token))
        {
            $this->success = false;
            $this->message = 'Clover ' . $what . ' returned no access token';
        }

        return $token;

    }

}
