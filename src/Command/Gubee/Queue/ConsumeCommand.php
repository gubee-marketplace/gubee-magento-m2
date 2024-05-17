<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Gubee\Queue;

use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Model\Message\Management;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Helper\ProgressBar;

use function count;
use function php_sapi_name;

class ConsumeCommand extends AbstractCommand
{
    protected Management $queueManagement;
    protected State $state;

    /**
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     * @throws LogicException When the command name is empty.
     */
    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        Management $queueManagement,
        State $state
    ) {
        $this->state           = $state;
        $this->queueManagement = $queueManagement;
        parent::__construct($eventDispatcher, $logger, 'queue:consume');
    }

    protected function doExecute(): int
    {
        $this->logger->info("Processing queue");
        $items = $this->queueManagement->getPending()->getItems();
        if (count($items) < 1) {
            $this->getOutput()->writeln("No messages to process");
            return 0;
        }
        // if call was from cli we can process all pending messages
        if (php_sapi_name() === 'cli') {
            $progressbar = ObjectManager::getInstance()->create(
                ProgressBar::class,
                [
                    'output' => $this->getOutput(),
                    'max'    => count($items),
                ]
            );
        }
        $queue = $this->queueManagement;
        foreach ($items as $message) {
        $this->state->emulateAreaCode(Area::AREA_ADMINHTML, function() use ($queue, $message) {
                $queue->process($message);
        });
            if (php_sapi_name() === 'cli') {
                $progressbar->advance();
            }
        }
        if (php_sapi_name() === 'cli') {
            $progressbar->finish();
        }
        $this->logger->info("Queue processed");
        return 0;
        }

}
