<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Gubee;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Lock\LockManagerInterface;
use Throwable;

use function base64_encode;
use function bin2hex;
use function hash;
use function hash_equals;
use function http_build_query;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function parse_url;
use function preg_match;
use function random_bytes;
use function rtrim;
use function strcasecmp;
use function strtr;
use function time;

use const JSON_THROW_ON_ERROR;
use const PHP_INT_MAX;
use const PHP_QUERY_RFC3986;
use const PHP_URL_HOST;
use const PHP_URL_SCHEME;

class OAuth
{
    public const CLIENT_ID     = 'gubee/general/oauth_client_id';
    public const CLIENT_SECRET = 'gubee/general/oauth_client_secret';
    public const CONNECTION    = 'gubee/oauth/connection';
    public const AUTHORIZE_URL = 'https://admin.gubee.com.br/#/oauth2/authorize';
    public const TOKEN_URL     = 'https://auth.gubee.com.br/realms/gubee/protocol/openid-connect/token';
    private const LOCK         = 'gubee_oauth_connection';

    private ResourceConnection $resource;
    private WriterInterface $writer;
    private EncryptorInterface $encryptor;
    private ReinitableConfigInterface $config;
    private Curl $httpClient;
    private LockManagerInterface $lock;

    public function __construct(
        ResourceConnection $resource,
        WriterInterface $writer,
        EncryptorInterface $encryptor,
        ReinitableConfigInterface $config,
        Curl $httpClient,
        LockManagerInterface $lock
    ) {
        $this->resource   = $resource;
        $this->writer     = $writer;
        $this->encryptor  = $encryptor;
        $this->config     = $config;
        $this->httpClient = $httpClient;
        $this->lock       = $lock;
    }

    public function hasConnection(): bool
    {
        return $this->readConnection() !== null;
    }

    public function credentialsChanged(): void
    {
        $this->acquireLock();
        try {
            $connection = $this->readConnection();
            [$clientId] = $this->credentials(true);
            if ($connection !== null && $connection['client_id'] !== $clientId) {
                $connection['status'] = 'reauth_required';
                $this->saveConnection($connection);
            }
        } finally {
            $this->lock->unlock(self::LOCK);
        }
    }

    public function getStatus(): string
    {
        $connection = $this->readConnection();
        if ($connection === null) {
            return 'Not connected to OAuth. Existing API-key authentication remains available.';
        }
        [$clientId] = $this->credentials();
        if ($connection['status'] !== 'connected' || $connection['client_id'] !== $clientId) {
            return 'Reconnection required. Legacy authentication is disabled.';
        }
        if (($connection['retry_after'] ?? 0) > time()) {
            return 'Token renewal temporarily unavailable. Please try again shortly.';
        }
        return 'Connected with OAuth.';
    }

    /** @return array{url: string, attempt: array} */
    public function beginAuthorization(string $redirectUri, int $adminId): array
    {
        $scheme = parse_url($redirectUri, PHP_URL_SCHEME);
        $host   = parse_url($redirectUri, PHP_URL_HOST);
        if ($scheme !== 'https' && ! ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1'], true))) {
            throw new OAuthException('The Gubee callback URL must use HTTPS.');
        }
        [$clientId, $secret] = $this->credentials(true);
        if ($clientId === '' || $secret === '') {
            throw new OAuthException('Save the Gubee OAuth client ID and secret before connecting.');
        }
        $verifier = $this->base64Url(random_bytes(32));
        $state    = bin2hex(random_bytes(32));
        return [
            'url'     => self::AUTHORIZE_URL . '?' . http_build_query([
                'client_id'             => $clientId,
                'redirect_uri'          => $redirectUri,
                'response_type'         => 'code',
                'code_challenge'        => $this->base64Url(hash('sha256', $verifier, true)),
                'code_challenge_method' => 'S256',
                'state'                 => $state,
            ], '', '&', PHP_QUERY_RFC3986),
            'attempt' => [
                'state'        => $state,
                'verifier'     => $verifier,
                'redirect_uri' => $redirectUri,
                'admin_id'     => $adminId,
                'expires_at'   => time() + 600,
                'credentials'  => hash('sha256', $clientId . "\0" . $secret),
            ],
        ];
    }

    public function completeAuthorization(array $attempt, string $state, string $code, int $adminId): void
    {
        if (
            $state === '' || ! isset(
                $attempt['state'],
                $attempt['expires_at'],
                $attempt['admin_id'],
                $attempt['credentials'],
                $attempt['verifier'],
                $attempt['redirect_uri']
            )
            || ! is_string($attempt['state']) || ! is_string($attempt['credentials'])
            || ! is_string($attempt['verifier']) || ! is_string($attempt['redirect_uri'])
            || ! hash_equals($attempt['state'], $state) || $attempt['expires_at'] <= time()
            || $attempt['admin_id'] !== $adminId
        ) {
            throw new OAuthException('The Gubee authorization attempt is invalid or expired. Please connect again.');
        }
        if ($code === '') {
            throw new OAuthException('Gubee authorization was cancelled or no authorization code was returned.');
        }
        $this->acquireLock();
        try {
            [$clientId, $secret] = $this->credentials(true);
            if (! hash_equals($attempt['credentials'], hash('sha256', $clientId . "\0" . $secret))) {
                throw new OAuthException('Gubee app credentials changed. Please connect again.');
            }
            $token = $this->requestToken([
                'grant_type'    => 'authorization_code',
                'client_id'     => $clientId,
                'client_secret' => $secret,
                'code'          => $code,
                'redirect_uri'  => $attempt['redirect_uri'],
                'code_verifier' => $attempt['verifier'],
            ]);
            $this->saveConnection($this->connectionFromToken($token, $clientId));
        } finally {
            $this->lock->unlock(self::LOCK);
        }
    }

    /** Returns null only before the first successful OAuth connection. */
    public function getAccessToken(): ?string
    {
        $connection = $this->readConnection();
        if ($connection === null) {
            return null;
        }
        [$clientId] = $this->credentials();
        $this->validateConnection($connection, $clientId);
        if ($connection['expires_at'] > time() + 30) {
            return $connection['access_token'];
        }
        $this->acquireLock();
        try {
            // Another worker may have rotated the refresh token while this worker waited.
            $connection          = $this->readConnection();
            [$clientId, $secret] = $this->credentials(true);
            $this->validateConnection($connection, $clientId);
            if ($connection['expires_at'] > time() + 30) {
                return $connection['access_token'];
            }
            try {
                $token      = $this->requestToken([
                    'grant_type'    => 'refresh_token',
                    'client_id'     => $clientId,
                    'client_secret' => $secret,
                    'refresh_token' => $connection['refresh_token'],
                ]);
                $connection = $this->connectionFromToken($token, $clientId, $connection['refresh_token']);
            } catch (OAuthException $error) {
                if ($error->getCode() === 401) {
                    $connection['status'] = 'reauth_required';
                } else {
                    $connection['retry_after'] = time() + 30;
                }
                $this->saveConnection($connection);
                throw $error;
            }
            $this->saveConnection($connection);
            return $connection['access_token'];
        } finally {
            $this->lock->unlock(self::LOCK);
        }
    }

    private function validateConnection(array $connection, string $clientId): void
    {
        if ($connection['status'] !== 'connected' || $connection['client_id'] !== $clientId) {
            throw new OAuthException('Reconnect Gubee in Magento configuration. Legacy authentication is disabled.');
        }
        if (($connection['retry_after'] ?? 0) > time()) {
            throw new OAuthException('Gubee token renewal is temporarily unavailable. Please try again shortly.');
        }
    }

    private function credentials(bool $reload = false): array
    {
        // Cron clients outlive configuration changes, including client-secret rotation.
        if ($reload) {
            $this->config->reinit();
        }
        return [
            (string) $this->config->getValue(self::CLIENT_ID),
            $this->encryptor->decrypt((string) $this->config->getValue(self::CLIENT_SECRET)),
        ];
    }

    private function acquireLock(): void
    {
        if (! $this->lock->lock(self::LOCK, 10)) {
            throw new OAuthException('Another Gubee connection update is in progress. Please try again shortly.');
        }
    }

    private function readConnection(): ?array
    {
        $db    = $this->resource->getConnection();
        $value = $db->fetchOne($db->select()->from($this->resource->getTableName('core_config_data'), ['value'])
            ->where('path = ?', self::CONNECTION)->where('scope = ?', 'default')->where('scope_id = ?', 0));
        if ($value === false) {
            return null;
        }
        try {
            $connection = json_decode($this->encryptor->decrypt($value), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $error) {
            throw new OAuthException('The stored Gubee OAuth connection cannot be read. Please reconnect.');
        }
        if (
            ! is_array($connection) || ! isset(
                $connection['status'],
                $connection['client_id'],
                $connection['access_token'],
                $connection['refresh_token'],
                $connection['expires_at']
            )
            || ! in_array($connection['status'], ['connected', 'reauth_required'], true)
            || ! is_string($connection['client_id']) || ! is_string($connection['access_token'])
            || $connection['access_token'] === '' || ! is_string($connection['refresh_token'])
            || $connection['refresh_token'] === '' || ! is_int($connection['expires_at'])
            || (isset($connection['retry_after']) && ! is_int($connection['retry_after']))
        ) {
            throw new OAuthException('The stored Gubee OAuth connection is invalid. Please reconnect.');
        }
        return $connection;
    }

    private function saveConnection(array $connection): void
    {
        $this->writer->save(self::CONNECTION, $this->encryptor->encrypt(json_encode($connection, JSON_THROW_ON_ERROR)));
    }

    private function requestToken(array $parameters): array
    {
        try {
            $curl = $this->httpClient;
            $curl->setTimeout(20);
            $curl->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json']);
            $curl->post(self::TOKEN_URL, http_build_query($parameters, '', '&', PHP_QUERY_RFC3986));
            $status = $curl->getStatus();
            $token  = json_decode($curl->getBody(), true);
        } catch (Throwable $error) {
            // Do not retain transport exceptions: they can contain credentials or token response bodies.
            throw new OAuthException('Gubee token service is temporarily unavailable. Please try again shortly.');
        }
        if ($status !== 200) {
            $error = is_array($token) ? ($token['error'] ?? '') : '';
            if (
                in_array($status, [400, 401, 403], true)
                && (in_array($error, ['invalid_grant', 'invalid_client', 'unauthorized_client'], true)
                    || in_array($status, [401, 403], true))
            ) {
                throw new OAuthException(
                    'Gubee rejected authorization. Check the app credentials and reconnect.',
                    401
                );
            }
            throw new OAuthException('Gubee token service is temporarily unavailable. Please try again shortly.');
        }
        if (
            ! is_array($token) || ! is_string($token['access_token'] ?? null) || $token['access_token'] === ''
            || preg_match('/\s/', $token['access_token']) || ! is_int($token['expires_in'] ?? null)
            || $token['expires_in'] <= 0 || $token['expires_in'] > PHP_INT_MAX - time()
            || ! is_string($token['token_type'] ?? null) || strcasecmp($token['token_type'], 'Bearer') !== 0
            || (isset($token['refresh_token'])
                && (! is_string($token['refresh_token']) || $token['refresh_token'] === ''))
        ) {
            throw new OAuthException('Gubee returned an invalid token response. Please try connecting again.');
        }
        return $token;
    }

    private function connectionFromToken(array $token, string $clientId, ?string $refreshToken = null): array
    {
        $refreshToken = $token['refresh_token'] ?? $refreshToken;
        if ($refreshToken === null || $refreshToken === '') {
            throw new OAuthException('Gubee did not return a refresh token. Please connect again.');
        }
        return [
            'status'        => 'connected',
            'client_id'     => $clientId,
            'access_token'  => $token['access_token'],
            'refresh_token' => $refreshToken,
            'expires_at'    => time() + $token['expires_in'],
            'retry_after'   => 0,
        ];
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
