<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Gubee\Queue;

use DateInterval;
use DateTime;
use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Api\Data\Message\DetailInterface;
use Gubee\Integration\Api\Data\MessageInterface;
use Gubee\Integration\Api\Enum\Message\StatusEnum;
use Gubee\Integration\Command\AbstractCommand;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Psr\Log\LoggerInterface;

use function __;
use function count;
use function sprintf;

class CleanupCommand extends AbstractCommand
{
    private const CHUNK_SIZE = 500;

    protected ResourceConnection $resourceConnection;

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        ConfigInterface $configManager,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($eventDispatcher, $logger, $configManager, 'queue:cleanup');
        $this->resourceConnection = $resourceConnection;
    }

    protected function configure(): void
    {
        $this->setDescription('Delete old, successfully processed queue messages');
        $this->setHelp(
            "Deletes queue messages (and their details) that finished successfully " .
            "more than 'queue_cleanup_days' days ago. Pending, running, error and " .
            "failed messages are never touched."
        );
    }

    protected function doExecute(): int
    {
        $connection   = $this->resourceConnection->getConnection();
        $messageTable = $this->resourceConnection->getTableName(MessageInterface::TABLE);
        $detailTable  = $this->resourceConnection->getTableName(DetailInterface::TABLE);
        $days         = $this->configManager->getQueueCleanupDays();
        $cutoff       = (new DateTime())
            ->sub(new DateInterval(sprintf('P%dD', $days)))
            ->format('Y-m-d H:i:s');
        $statuses     = [
            (int) StatusEnum::DONE()->__toString(),
            (int) StatusEnum::FINISHED()->__toString(),
        ];

        $deleted = 0;
        do {
            $ids = $connection->fetchCol(
                $connection->select()
                    ->from($messageTable, MessageInterface::MESSAGE_ID)
                    ->where(MessageInterface::STATUS . ' IN (?)', $statuses)
                    ->where(MessageInterface::UPDATED_AT . ' < ?', $cutoff)
                    ->limit(self::CHUNK_SIZE)
            );

            if (! $ids) {
                break;
            }

            $connection->delete($detailTable, [DetailInterface::MESSAGE_ID . ' IN (?)' => $ids]);
            $connection->delete($messageTable, [MessageInterface::MESSAGE_ID . ' IN (?)' => $ids]);
            $deleted += count($ids);
        } while (count($ids) === self::CHUNK_SIZE);

        $this->logger->info(
            __(
                'Cleaned up %1 queue message(s) older than %2 days',
                $deleted,
                $days
            )->__toString()
        );

        return self::SUCCESS;
    }
}
