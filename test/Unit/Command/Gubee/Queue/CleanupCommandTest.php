<?php

declare(strict_types=1);

namespace Gubee\Integration\Test\Unit\Command\Gubee\Queue;

use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Api\Data\Message\DetailInterface;
use Gubee\Integration\Api\Data\MessageInterface;
use Gubee\Integration\Command\Gubee\Queue\CleanupCommand;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommandTest extends TestCase
{
    private $eventDispatcherMock;
    private $loggerMock;
    private $configManagerMock;
    private $resourceConnectionMock;
    private $connectionMock;
    private $selectMock;
    private $command;

    protected function setUp(): void
    {
        $this->eventDispatcherMock    = $this->createMock(ManagerInterface::class);
        $this->loggerMock             = $this->createMock(LoggerInterface::class);
        $this->configManagerMock      = $this->createMock(ConfigInterface::class);
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock         = $this->createMock(AdapterInterface::class);
        $this->selectMock             = $this->createMock(Select::class);

        $this->configManagerMock->method('getQueueCleanupDays')->willReturn(30);

        $this->resourceConnectionMock->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceConnectionMock->method('getTableName')
            ->willReturnMap([
                [MessageInterface::TABLE, MessageInterface::TABLE],
                [DetailInterface::TABLE, DetailInterface::TABLE],
            ]);

        $this->connectionMock->method('select')->willReturn($this->selectMock);
        $this->selectMock->method('from')->willReturnSelf();
        $this->selectMock->method('where')->willReturnSelf();
        $this->selectMock->method('limit')->willReturnSelf();

        $this->command = new CleanupCommand(
            $this->eventDispatcherMock,
            $this->loggerMock,
            $this->configManagerMock,
            $this->resourceConnectionMock
        );
    }

    public function testDoesNothingWhenNoOldMessagesFound(): void
    {
        $this->connectionMock->method('fetchCol')->willReturn([]);
        $this->connectionMock->expects($this->never())->method('delete');

        $result = $this->command->run(
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );

        $this->assertEquals(0, $result);
    }

    public function testDeletesDetailsThenMessagesForEachChunk(): void
    {
        $firstChunk  = range(1, 500);
        $secondChunk = [501, 502];

        $this->connectionMock->method('fetchCol')
            ->willReturnOnConsecutiveCalls($firstChunk, $secondChunk, []);

        $deleteCalls = [];
        $this->connectionMock->expects($this->exactly(4))
            ->method('delete')
            ->willReturnCallback(function ($table, $where) use (&$deleteCalls) {
                $deleteCalls[] = [$table, $where];
                return 1;
            });

        $result = $this->command->run(
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );

        $this->assertEquals(0, $result);
        $this->assertSame(DetailInterface::TABLE, $deleteCalls[0][0]);
        $this->assertSame([DetailInterface::MESSAGE_ID . ' IN (?)' => $firstChunk], $deleteCalls[0][1]);
        $this->assertSame(MessageInterface::TABLE, $deleteCalls[1][0]);
        $this->assertSame([MessageInterface::MESSAGE_ID . ' IN (?)' => $firstChunk], $deleteCalls[1][1]);
        $this->assertSame(DetailInterface::TABLE, $deleteCalls[2][0]);
        $this->assertSame([DetailInterface::MESSAGE_ID . ' IN (?)' => $secondChunk], $deleteCalls[2][1]);
        $this->assertSame(MessageInterface::TABLE, $deleteCalls[3][0]);
        $this->assertSame([MessageInterface::MESSAGE_ID . ' IN (?)' => $secondChunk], $deleteCalls[3][1]);
    }
}
