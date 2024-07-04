<?php
declare(strict_types=1);
namespace Gubee\Integration\Plugin;

use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Api\Queue\ManagementInterface;
use Psr\Log\LoggerInterface;


abstract class AbstractInventoryPlugin
{
    /**
     * @var ConfigInterface
     */
    protected $config;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ManagementInterface
     */
    protected $queueManagement;

    public function __construct(
        ConfigInterface $config,
        LoggerInterface $logger,
        ManagementInterface $queueManagement
    )
    {
        $this->queueManagement = $queueManagement;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Use this method to check if execution is needed or configured
     * @return bool
     */
    public abstract function shouldExecute() : bool;
}