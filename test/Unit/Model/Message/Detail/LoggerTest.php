<?php

declare(strict_types=1);

namespace Gubee\Integration\Test\Unit\Model\Message\Detail;

use Gubee\Integration\Model\Message\Detail;
use Gubee\Integration\Model\Message\Detail\Logger;
use Gubee\Integration\Model\Message\DetailFactory;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

use function json_encode;

class LoggerTest extends TestCase
{
    public function testRedactsHeadersAndCallbackParametersBeforeSavingLogs(): void
    {
        $factory = $this->getMockBuilder(DetailFactory::class)->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();
        $detail  = $this->createMock(Detail::class);
        $factory->method('create')->willReturn($detail);
        $detail->method('setLevel')->willReturnSelf();
        $detail->expects($this->once())->method('setMessage')->with('Request: Bearer [REDACTED]')->willReturnSelf();
        $detail->expects($this->once())->method('setContext')->willReturnCallback(function ($context) use ($detail) {
            $serialized = json_encode($context);
            foreach (['access-secret', 'cookie-secret', 'code-secret', 'state-secret', 'refresh-secret'] as $secret) {
                self::assertStringNotContainsString($secret, $serialized);
            }
            self::assertSame('application/json', $context['request']['headers']['Accept']);
            self::assertSame(401, $context['response']['status']);
            return $detail;
        });
        $detail->expects($this->once())->method('save');
        $logger = new Logger($factory, $this->createMock(Registry::class), new NullLogger());
        $logger->error('Request: Bearer access-secret', [
            'uri'      => '/admin/gubee/oauth/callback/?code=code-secret&state=state-secret',
            'referer'  => 'https://store.example/callback?code=code-secret',
            'request'  => [
                'headers' => [
                    'aUtHoRiZaTiOn' => ['Bearer access-secret'],
                    'Cookie'        => ['session=cookie-secret'],
                    'Accept'        => 'application/json',
                ],
            ],
            'response' => ['status' => 401, 'body' => '{"refresh_token":"refresh-secret"}'],
        ]);
    }
}
