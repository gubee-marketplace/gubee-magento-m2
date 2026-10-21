<?php

declare(strict_types=1);

namespace Gubee\Integration\Test\Unit\Service\Gubee;

use Gubee\Integration\Model\Config;
use Gubee\Integration\Service\Gubee\Client;
use Gubee\SDK\Api\ServiceProviderInterface;
use Gubee\SDK\Library\HttpClient\Builder;
use Http\Client\Common\HttpMethodsClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

class ClientTest extends TestCase
{
    private Config $config;
    private Builder $builder;
    private HttpMethodsClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->config  = $this->createMock(Config::class);
        $this->builder = $this->createMock(Builder::class);
        $this->builder->method('addPlugin')->willReturnSelf();
        $this->builder->method('removePlugin')->willReturnSelf();
        $uriFactory = $this->createMock(UriFactoryInterface::class);
        $uri        = $this->createMock(UriInterface::class);
        $uri->method('getHost')->willReturn('api.gubee.com.br');
        $uriFactory->method('createUri')->willReturn($uri);
        $this->builder->method('getUriFactory')->willReturn($uriFactory);
        $this->httpClient = $this->createMock(HttpMethodsClientInterface::class);
        $this->builder->method('getClient')->willReturn($this->httpClient);
    }

    public function testLongLivedClientReadsCurrentOauthTokenBeforeEveryRequest(): void
    {
        $this->config->method('hasOAuthConnection')->willReturn(true);
        $this->config->expects($this->exactly(2))->method('getApiToken')
            ->willReturnOnConsecutiveCalls('first-token', 'rotated-token');
        $client = $this->getMockBuilder(Client::class)->setConstructorArgs([
            $this->config,
            $this->createMock(ServiceProviderInterface::class),
            null,
            $this->builder,
        ])->onlyMethods(['authenticate'])->getMock();
        $tokens = [];
        $client->method('authenticate')->willReturnCallback(function ($token) use (&$tokens, $client) {
            $tokens[] = $token;
            return $client;
        });
        self::assertSame($this->httpClient, $client->getHttpClient());
        self::assertSame($this->httpClient, $client->getHttpClient());
        self::assertSame(['first-token', 'rotated-token'], $tokens);
    }

    public function testOauthFailurePreventsHttpAccess(): void
    {
        $this->config->method('hasOAuthConnection')->willReturn(true);
        $this->config->method('getApiToken')->willThrowException(new RuntimeException('Reconnect Gubee'));
        $this->builder->expects($this->never())->method('getClient');
        $client = new Client($this->config, $this->createMock(ServiceProviderInterface::class), null, $this->builder);
        $this->expectExceptionMessage('Reconnect Gubee');
        $client->getHttpClient();
    }

    public function testLegacyAuthenticationStillUsesRetryInterval(): void
    {
        $this->config->method('hasOAuthConnection')->willReturn(false);
        $this->config->expects($this->once())->method('getApiToken')->willReturn('legacy-token');
        $client = new Client($this->config, $this->createMock(ServiceProviderInterface::class), null, $this->builder);
        self::assertSame($this->httpClient, $client->getHttpClient());
        self::assertSame($this->httpClient, $client->getHttpClient());
    }
}
