<?php

declare(strict_types=1);

namespace Gubee\Integration\Test\Unit\Service\Gubee;

use Gubee\Integration\Service\Gubee\OAuth;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Lock\LockManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_merge;
use function base64_decode;
use function base64_encode;
use function explode;
use function hash;
use function json_decode;
use function json_encode;
use function parse_str;
use function rtrim;
use function strtr;
use function substr;
use function time;

class OAuthTest extends TestCase
{
    private OAuth $oauth;
    private ReinitableConfigInterface $config;
    private Curl $curl;
    private LockManagerInterface $lock;
    /** @var string|false */
    private $stored             = false;
    private array $requests     = [];
    private array $credentials  = [OAuth::CLIENT_ID => 'app-id', OAuth::CLIENT_SECRET => 'secret&+value'];
    private int $status         = 200;
    private bool $lockAvailable = true;
    private string $body;
    /** @var callable|null */
    private $onLock;

    protected function setUp(): void
    {
        $resource = $this->createMock(ResourceConnection::class);
        $db       = $this->createMock(AdapterInterface::class);
        $select   = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $db->method('select')->willReturn($select);
        $db->method('fetchOne')->willReturnCallback(function () {
            return $this->stored;
        });
        $resource->method('getConnection')->willReturn($db);
        $resource->method('getTableName')->willReturn('core_config_data');
        $writer = $this->createMock(WriterInterface::class);
        $writer->method('save')->willReturnCallback(function ($path, $value) {
            self::assertSame(OAuth::CONNECTION, $path);
            self::assertStringStartsWith('encrypted:', $value);
            $this->stored = $value;
        });
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->method('encrypt')->willReturnCallback(static function ($value) {
            return 'encrypted:' . base64_encode($value);
        });
        $encryptor->method('decrypt')->willReturnCallback(static function ($value) {
            return base64_decode(substr($value, 10));
        });
        $this->config = $this->createMock(ReinitableConfigInterface::class);
        $this->config->method('getValue')->willReturnCallback(function ($path) {
            return $this->credentials[$path] ?? '';
        });
        $this->curl = $this->createMock(Curl::class);
        $this->curl->method('post')->willReturnCallback(function ($url, $body) {
            self::assertSame(OAuth::TOKEN_URL, $url);
            parse_str($body, $parameters);
            $this->requests[] = $parameters;
        });
        $this->curl->method('getStatus')->willReturnCallback(function () {
            return $this->status;
        });
        $this->curl->method('getBody')->willReturnCallback(function () {
            return $this->body;
        });
        $this->body = json_encode([
            'access_token'  => 'new-access',
            'refresh_token' => 'new-refresh',
            'expires_in'    => 300,
            'token_type'    => 'Bearer',
        ]);
        $this->lock = $this->createMock(LockManagerInterface::class);
        $this->lock->method('lock')->willReturnCallback(function () {
            if ($this->onLock !== null) {
                ($this->onLock)();
            }
            return $this->lockAvailable;
        });
        $this->oauth = new OAuth($resource, $writer, $encryptor, $this->config, $this->curl, $this->lock);
    }

    public function testLegacyRemainsAvailableUntilConnected(): void
    {
        self::assertFalse($this->oauth->hasConnection());
        self::assertNull($this->oauth->getAccessToken());
        self::assertSame([], $this->requests);
    }

    public function testPkceAuthorizationAndCodeExchange(): void
    {
        $authorization = $this->authorization();
        $attempt       = $authorization['attempt'];
        parse_str(explode('?', $authorization['url'], 2)[1], $query);
        self::assertStringStartsWith(OAuth::AUTHORIZE_URL . '?', $authorization['url']);
        self::assertSame('app-id', $query['client_id']);
        self::assertSame('code', $query['response_type']);
        self::assertSame('S256', $query['code_challenge_method']);
        self::assertSame(
            rtrim(strtr(base64_encode(hash('sha256', $attempt['verifier'], true)), '+/', '-_'), '='),
            $query['code_challenge']
        );
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $attempt['verifier']);
        self::assertNotSame($attempt['state'], $this->authorization()['attempt']['state']);
        self::assertStringNotContainsString('secret', $authorization['url']);
        $this->lock->expects($this->once())->method('unlock');
        $before = time();
        $this->oauth->completeAuthorization($attempt, $attempt['state'], 'code+&value', 7);
        self::assertSame([
            'grant_type'    => 'authorization_code',
            'client_id'     => 'app-id',
            'client_secret' => 'secret&+value',
            'code'          => 'code+&value',
            'redirect_uri'  => $attempt['redirect_uri'],
            'code_verifier' => $attempt['verifier'],
        ], $this->requests[0]);
        self::assertTrue($this->oauth->hasConnection());
        self::assertSame('new-access', $this->oauth->getAccessToken());
        self::assertGreaterThanOrEqual($before + 300, $this->connection()['expires_at']);
        self::assertStringNotContainsString('new-access', $this->stored);
    }

    /** @dataProvider invalidAttempts */
    public function testRejectsInvalidAuthorizationAttempts(string $change): void
    {
        $attempt = $this->authorization()['attempt'];
        $state   = $attempt['state'];
        $code    = 'code';
        if ($change === 'state') {
            $state = 'forged';
        } elseif ($change === 'expired') {
            $attempt['expires_at'] = time() - 1;
        } elseif ($change === 'admin') {
            $attempt['admin_id'] = 8;
        } elseif ($change === 'credentials') {
            $this->credentials[OAuth::CLIENT_SECRET] = 'rotated';
        } elseif ($change === 'cancelled') {
            $code = '';
        } else {
            $attempt = [];
        }
        try {
            $this->oauth->completeAuthorization($attempt, $state, $code, 7);
            self::fail('Invalid authorization must be rejected.');
        } catch (RuntimeException $error) {
            self::assertSame([], $this->requests);
            self::assertFalse($this->oauth->hasConnection());
        }
    }

    public static function invalidAttempts(): array
    {
        return [['state'], ['expired'], ['admin'], ['credentials'], ['cancelled'], ['missing']];
    }

    public function testMissingCredentialsPreventAuthorization(): void
    {
        $this->credentials[OAuth::CLIENT_SECRET] = '';
        $this->expectExceptionMessage('Save the Gubee OAuth client ID and secret');
        $this->authorization();
    }

    public function testRejectsInsecureProductionCallback(): void
    {
        $this->expectExceptionMessage('must use HTTPS');
        $this->oauth->beginAuthorization('http://store.example/admin/gubee/oauth/callback/', 7);
    }

    public function testValidTokenDoesNotRefreshOrReloadConfiguration(): void
    {
        $this->storeConnection(['expires_at' => time() + 60]);
        $this->config->expects($this->never())->method('reinit');
        $this->lock->expects($this->never())->method('lock');
        self::assertSame('old-access', $this->oauth->getAccessToken());
        self::assertSame([], $this->requests);
    }

    public function testRefreshesBeforeExpiryAndPersistsRotatedToken(): void
    {
        $this->storeConnection(['expires_at' => time() + 20]);
        $this->lock->expects($this->once())->method('unlock');
        self::assertSame('new-access', $this->oauth->getAccessToken());
        self::assertSame([
            'grant_type'    => 'refresh_token',
            'client_id'     => 'app-id',
            'client_secret' => 'secret&+value',
            'refresh_token' => 'old-refresh',
        ], $this->requests[0]);
        self::assertSame('new-refresh', $this->connection()['refresh_token']);
        self::assertSame('new-access', $this->oauth->getAccessToken());
        self::assertCount(1, $this->requests);
    }

    public function testKeepsRefreshTokenWhenRotationIsOmitted(): void
    {
        $this->storeConnection();
        $this->body = '{"access_token":"new-access","expires_in":300,"token_type":"Bearer"}';
        self::assertSame('new-access', $this->oauth->getAccessToken());
        self::assertSame('old-refresh', $this->connection()['refresh_token']);
    }

    public function testRereadsTokensAfterAcquiringLock(): void
    {
        $this->storeConnection();
        $this->onLock = function () {
            $this->storeConnection(['expires_at' => time() + 300, 'access_token' => 'other-worker-access']);
        };
        self::assertSame('other-worker-access', $this->oauth->getAccessToken());
        self::assertSame([], $this->requests);
    }

    public function testRefreshFailureRequiresReconnectAndNeverReturnsToLegacy(): void
    {
        $this->storeConnection();
        $this->status = 400;
        $this->body   = '{"error":"invalid_grant","error_description":"secret-leak"}';
        $this->lock->expects($this->once())->method('unlock');
        try {
            $this->oauth->getAccessToken();
            self::fail('Rejected refresh must fail.');
        } catch (RuntimeException $error) {
            self::assertStringNotContainsString('secret-leak', $error->getMessage());
            self::assertNull($error->getPrevious());
        }
        self::assertTrue($this->oauth->hasConnection());
        self::assertSame('reauth_required', $this->connection()['status']);
        $this->expectExceptionMessage('Reconnect Gubee');
        $this->oauth->getAccessToken();
    }

    public function testTemporaryFailurePreservesTokensAndSharesCooldown(): void
    {
        $this->storeConnection();
        $this->status = 503;
        $this->body   = 'upstream unavailable';
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $this->oauth->getAccessToken();
                self::fail('Token renewal must fail.');
            } catch (RuntimeException $error) {
                self::assertStringContainsString('temporarily unavailable', $error->getMessage());
            }
        }
        self::assertCount(1, $this->requests);
        self::assertSame('old-refresh', $this->connection()['refresh_token']);
        self::assertSame('connected', $this->connection()['status']);
        self::assertGreaterThan(time(), $this->connection()['retry_after']);
    }

    public function testTransportFailureDoesNotExposeOriginalException(): void
    {
        $this->storeConnection();
        $this->curl->method('post')->willThrowException(new RuntimeException('client_secret=leaked'));
        try {
            $this->oauth->getAccessToken();
            self::fail('Transport failure must propagate.');
        } catch (RuntimeException $error) {
            self::assertStringNotContainsString('leaked', $error->getMessage());
            self::assertNull($error->getPrevious());
        }
    }

    /** @dataProvider invalidResponses */
    public function testInvalidInitialResponseDoesNotSwitchFromLegacy(string $body): void
    {
        $attempt    = $this->authorization()['attempt'];
        $this->body = $body;
        try {
            $this->oauth->completeAuthorization($attempt, $attempt['state'], 'code', 7);
            self::fail('Malformed token response must fail.');
        } catch (RuntimeException $error) {
            self::assertFalse($this->oauth->hasConnection());
        }
    }

    public static function invalidResponses(): array
    {
        return [
            ['not json'],
            ['{"access_token":"access","expires_in":300,"token_type":"Bearer"}'],
            ['{"access_token":"","refresh_token":"refresh","expires_in":300,"token_type":"Bearer"}'],
            ['{"access_token":"access","refresh_token":"refresh","expires_in":0,"token_type":"Bearer"}'],
            ['{"access_token":"access","refresh_token":"refresh","expires_in":"300","token_type":"Bearer"}'],
            ['{"access_token":"access","refresh_token":"refresh","expires_in":300,"token_type":"Basic"}'],
        ];
    }

    public function testFailedReconnectPreservesExistingConnection(): void
    {
        $this->storeConnection(['status' => 'reauth_required']);
        $stored       = $this->stored;
        $attempt      = $this->authorization()['attempt'];
        $this->status = 400;
        $this->body   = '{"error":"invalid_grant"}';
        try {
            $this->oauth->completeAuthorization($attempt, $attempt['state'], 'code', 7);
            self::fail('Rejected reconnect must fail.');
        } catch (RuntimeException $error) {
            self::assertSame($stored, $this->stored);
        }
    }

    public function testClientIdChangeRequiresReconnection(): void
    {
        $this->storeConnection();
        $this->credentials[OAuth::CLIENT_ID] = 'changed-app';
        $this->oauth->credentialsChanged();
        self::assertSame('reauth_required', $this->connection()['status']);
        $this->expectExceptionMessage('Reconnect Gubee');
        $this->oauth->getAccessToken();
    }

    public function testMismatchedClientIdDoesNotUseOldTokens(): void
    {
        $this->storeConnection(['expires_at' => time() + 300]);
        $this->credentials[OAuth::CLIENT_ID] = 'another-app';
        $this->expectExceptionMessage('Reconnect Gubee');
        $this->oauth->getAccessToken();
    }

    public function testSavingSameClientIdDoesNotDisconnectOauth(): void
    {
        $this->storeConnection();
        $this->oauth->credentialsChanged();
        self::assertSame('connected', $this->connection()['status']);
    }

    public function testBusyLockPreventsRefresh(): void
    {
        $this->storeConnection();
        $this->lockAvailable = false;
        $this->curl->expects($this->never())->method('post');
        $this->expectExceptionMessage('Another Gubee connection update is in progress');
        $this->oauth->getAccessToken();
    }

    public function testUsesCurrentSecretAfterWaitingForRefreshLock(): void
    {
        $this->storeConnection();
        $this->onLock = function () {
            $this->credentials[OAuth::CLIENT_SECRET] = 'rotated-secret';
        };
        self::assertSame('new-access', $this->oauth->getAccessToken());
        self::assertSame('rotated-secret', $this->requests[0]['client_secret']);
    }

    public function testCorruptedConnectionNeverEnablesLegacyFallback(): void
    {
        $this->stored = 'encrypted:' . base64_encode('broken');
        $this->expectExceptionMessage('cannot be read');
        $this->oauth->getAccessToken();
    }

    private function authorization(): array
    {
        return $this->oauth->beginAuthorization('https://store.example/admin/gubee/oauth/callback/', 7);
    }

    private function storeConnection(array $overrides = []): void
    {
        $this->stored = 'encrypted:' . base64_encode(json_encode(array_merge([
            'status'        => 'connected',
            'client_id'     => 'app-id',
            'access_token'  => 'old-access',
            'refresh_token' => 'old-refresh',
            'expires_at'    => time() - 1,
            'retry_after'   => 0,
        ], $overrides)));
    }

    private function connection(): array
    {
        return json_decode(base64_decode(substr($this->stored, 10)), true);
    }
}
