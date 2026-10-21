<?php

declare(strict_types=1);

namespace Gubee\Integration\Test\Unit\Model;

use DateTime;
use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Service\Gubee\OAuth;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

class ConfigOAuthTest extends TestCase
{
    private OAuth $oauth;
    private Config $config;

    protected function setUp(): void
    {
        $this->oauth  = $this->createMock(OAuth::class);
        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()
            ->onlyMethods(['getApiKey', 'getApiTimeout'])->getMock();
        $property     = new ReflectionProperty(Config::class, 'oauth');
        $property->setAccessible(true);
        $property->setValue($this->config, $this->oauth);
    }

    public function testOauthDoesNotRequireLegacyApiKey(): void
    {
        $this->oauth->method('getAccessToken')->willReturn('oauth-access');
        $this->config->expects($this->never())->method('getApiKey');
        self::assertSame('oauth-access', $this->config->getApiToken());
    }

    public function testDisconnectedOauthDoesNotFallBackToLegacy(): void
    {
        $this->oauth->method('getAccessToken')->willThrowException(new RuntimeException('Reconnect Gubee'));
        $this->config->expects($this->never())->method('getApiKey');
        $this->expectExceptionMessage('Reconnect Gubee');
        $this->config->getApiToken();
    }

    public function testLegacyTokenStillWorksBeforeOauthConnection(): void
    {
        $this->oauth->method('getAccessToken')->willReturn(null);
        $this->config->method('getApiKey')->willReturn('legacy-key');
        $this->config->method('getApiTimeout')->willReturn(new DateTime('+1 hour'));
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->with(ConfigInterface::CONFIG_PATH_API_TOKEN)->willReturn('legacy-token');
        $property = new ReflectionProperty(Config::class, 'scopeConfig');
        $property->setAccessible(true);
        $property->setValue($this->config, $scopeConfig);
        self::assertSame('legacy-token', $this->config->getApiToken());
    }
}
