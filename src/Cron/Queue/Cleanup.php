<?php

declare(strict_types=1);

namespace Gubee\Integration\Cron\Queue;

use Gubee\Integration\Command\Gubee\Queue\CleanupCommand;
use Gubee\Integration\Model\Config;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function __;

class Cleanup
{
    protected LoggerInterface $logger;
    protected ObjectManagerInterface $objectManager;
    protected Config $config;

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        Config $config
    ) {
        $this->objectManager = $objectManager;
        $this->config        = $config;
        $this->logger        = $logger;
    }

    public function execute(): int
    {
        if (! $this->isAllowed()) {
            return 0;
        }

        $this->logger->info(__("Cleaning up old queue messages"));

        $input   = $this->objectManager->create(ArrayInput::class, ['parameters' => []]);
        $output  = $this->objectManager->create(BufferedOutput::class);
        $command = $this->objectManager->create(CleanupCommand::class);
        $command->run($input, $output);

        $this->logger->info(__("Queue cleanup finished"));

        return 0;
    }

    protected function isAllowed(): bool
    {
        return $this->config->getActive();
    }
}
