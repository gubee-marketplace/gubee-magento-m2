<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Gubee;

use Gubee\Integration\Model\Config;
use Gubee\SDK\Api\ServiceProviderInterface;
use Gubee\SDK\Library\HttpClient\Builder;
use Http\Client\Common\HttpMethodsClientInterface;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;
use Throwable;

use function time;

class Client extends \Gubee\SDK\Client
{
    /**
     * Minimum number of seconds between two authentication attempts. Without
     * this, a persistently broken token (bad API key, Gubee auth endpoint
     * down) would trigger a synchronous token-renewal call to Gubee before
     * every single product/order sent in a batch, instead of at most once
     * per this interval.
     */
    private const AUTH_RETRY_INTERVAL = 30;

    private Config $config;
    private ?int $lastAuthAttempt = null;
    private ?Throwable $authError = null;

    public function __construct(
        Config $config,
        ?ServiceProviderInterface $serviceProvider = null,
        ?LoggerInterface $logger = null,
        ?Builder $httpClientBuilder = null,
        int $retryCount = 3
    ) {
        parent::__construct($serviceProvider, $logger, $httpClientBuilder, $retryCount);
        $this->config = $config;
    }

    /**
     * The token can be unavailable or expire at construction time (e.g. this
     * client is a long-lived DI singleton reused across a whole cron run).
     * Re-authenticating right before every actual HTTP call, instead of only
     * once at construction, prevents requests from silently going out
     * without an Authorization header once the token becomes available.
     */
    public function getHttpClient(): HttpMethodsClientInterface
    {
        if (
            $this->config->hasOAuthConnection() || $this->lastAuthAttempt === null
            || (time() - $this->lastAuthAttempt) >= self::AUTH_RETRY_INTERVAL
        ) {
            $this->refreshAuthentication();
        }
        if ($this->authError !== null) {
            throw $this->authError;
        }
        return parent::getHttpClient();
    }

    private function refreshAuthentication(): void
    {
        $this->lastAuthAttempt = time();
        try {
            $this->authenticate($this->config->getApiToken());
            $this->authError = null;
        } catch (Throwable $err) {
            $this->authError = $err;
            throw $err;
        }
    }

    public function buildServiceProvider(): ServiceProviderInterface
    {
        return ObjectManager::getInstance()->get(ServiceProviderInterface::class);
    }
}
