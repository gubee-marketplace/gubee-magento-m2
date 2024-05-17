<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Gubee;

use Gubee\Integration\Model\Config;
use Gubee\SDK\Api\ServiceProviderInterface;
use Gubee\SDK\Library\HttpClient\Builder;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

class Client extends \Gubee\SDK\Client
{
    public function __construct(
        Config $config,
        ?ServiceProviderInterface $serviceProvider = null,
        ?LoggerInterface $logger = null,
        ?Builder $httpClientBuilder = null,
        int $retryCount = 3
    ) {
        parent::__construct($serviceProvider, $logger, $httpClientBuilder, $retryCount);
        $this->authenticate($config->getApiToken());
    }

    public function buildServiceProvider(): ServiceProviderInterface
    {
        return ObjectManager::getInstance()->get(ServiceProviderInterface::class);
    }
}
