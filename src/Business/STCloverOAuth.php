<?php

namespace Codatsoft\CodatClover\Business;

use Codatsoft\CodatClover\Models\CLOAuthConfig;
use Codatsoft\CodatClover\Models\CLOAuthToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

// clover app oauth (v2, expiring tokens): the authorize redirect, the code
// exchange and the refresh. stateless over the wire - storing tokens, locking
// refreshes and deciding when to refresh stay in the host app. clover refresh
// tokens are single-use, so the caller must serialize refreshes per merchant.
// uses laravel's http client rather than TModelNetwork: callers need the http
// status and clover's error body, a timeout, and connection failures reported
// as a failed result - TNetwork exposes none of these.
class STCloverOAuth
{
    private const string AUTHORIZE = '/oauth/v2/authorize';
    private const string TOKEN = '/oauth/v2/token';
    private const string REFRESH = '/oauth/v2/refresh';
    private const int TIMEOUT_SECONDS = 15;

    public bool $success = true;
    public string $message = '';
    public int $status = 0;
    public ?array $errorBody = null;

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

    private function post(string $endPoint, array $body, string $what): ?CLOAuthToken
    {
        $this->success = true;
        $this->message = '';
        $this->status = 0;
        $this->errorBody = null;

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
