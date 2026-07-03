<?php

declare(strict_types=1);

namespace Gubee\Integration\Test\Unit\Command\Catalog\Product;

use Exception;
use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Command\Catalog\Product\SendCommand;
use Gubee\Integration\Model\Catalog\Product\Identifier\Resolver;
use Gubee\Integration\Service\Model\Catalog\ProductSimplified;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MageProduct;
use Magento\Catalog\Model\ResourceModel\Product\Action as ProductAction;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendCommandTest extends TestCase
{
    private $eventDispatcherMock;
    private $loggerMock;
    private $configManagerMock;
    private $productRepositoryMock;
    private $objectManagerMock;
    private $resolverMock;
    private $inputMock;
    private $outputMock;
    private $command;

    protected function setUp(): void
    {
        $this->eventDispatcherMock = $this->createMock(ManagerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configManagerMock = $this->createMock(ConfigInterface::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->resolverMock = $this->createMock(Resolver::class);
        $this->inputMock = $this->createMock(InputInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        // Standard validation check to return true
        $this->configManagerMock->method('getSyncEntities')->willReturn(['product']);

        $this->command = new SendCommand(
            $this->eventDispatcherMock,
            $this->loggerMock,
            $this->configManagerMock,
            $this->productRepositoryMock,
            $this->objectManagerMock,
            $this->resolverMock
        );
    }

    public function testExecuteProductDoesNotExist(): void
    {
        $sku = 'non-existent-sku';
        $this->inputMock->method('getArgument')->with('sku')->willReturn($sku);

        $mageProductMock = $this->createMock(MageProduct::class);
        $mageProductMock->method('getId')->willReturn(null);

        $this->resolverMock->method('resolve')->with($sku)->willReturn($mageProductMock);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains("does not exist"));

        $result = $this->command->run($this->inputMock, $this->outputMock);
        $this->assertEquals(1, $result);
    }

    public function testExecuteSuccess(): void
    {
        $sku = 'test-sku';
        $this->inputMock->method('getArgument')->with('sku')->willReturn($sku);

        $mageProductMock = $this->createMock(MageProduct::class);
        $mageProductMock->method('getId')->willReturn(123);

        $this->resolverMock->method('resolve')->with($sku)->willReturn($mageProductMock);

        $productSimplifiedMock = $this->createMock(ProductSimplified::class);
        $productSimplifiedMock->expects($this->once())->method('save');

        $this->objectManagerMock->method('create')
            ->with(ProductSimplified::class, ['product' => $mageProductMock])
            ->willReturn($productSimplifiedMock);

        // Mock static ObjectManager access inside updateAttribute
        $productActionMock = $this->createMock(ProductAction::class);
        $productActionMock->expects($this->once())
            ->method('updateAttributes')
            ->with([123], ['gubee_integration_status' => 1], 0);

        $staticObjectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $staticObjectManagerMock->method('get')
            ->with('Magento\Catalog\Model\ResourceModel\Product\Action')
            ->willReturn($productActionMock);

        ObjectManager::setInstance($staticObjectManagerMock);

        $result = $this->command->run($this->inputMock, $this->outputMock);
        $this->assertEquals(0, $result);
    }

    public function testExecuteBuildError(): void
    {
        $sku = 'test-sku';
        $this->inputMock->method('getArgument')->with('sku')->willReturn($sku);

        $mageProductMock = $this->createMock(MageProduct::class);
        $mageProductMock->method('getId')->willReturn(123);

        $this->resolverMock->method('resolve')->with($sku)->willReturn($mageProductMock);

        $this->objectManagerMock->method('create')
            ->with(ProductSimplified::class, ['product' => $mageProductMock])
            ->willThrowException(new Exception("Build failed"));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("An error occurred while building the gubee product");

        $this->command->run($this->inputMock, $this->outputMock);
    }
}
