<?php

declare(strict_types=1);

namespace Gubee\Integration\Test\Unit\Command\Catalog\Product;

use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Command\Catalog\Product\ValidateCommand;
use Gubee\Integration\Model\Catalog\Product\Identifier\Resolver;
use Gubee\Integration\Service\Model\Catalog\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MageProduct;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateCommandTest extends TestCase
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

        $this->command = new ValidateCommand(
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

        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())->method('validate');

        $this->objectManagerMock->method('create')
            ->with(Product::class, ['product' => $mageProductMock])
            ->willReturn($productMock);

        $hasDispatched = false;
        $this->eventDispatcherMock->method('dispatch')
            ->willReturnCallback(function ($eventName, $parameters) use ($productMock, &$hasDispatched) {
                if ($eventName === 'gubee_catalog_product_validate') {
                    $this->assertSame($productMock, $parameters['product']);
                    $hasDispatched = true;
                }
            });

        $result = $this->command->run($this->inputMock, $this->outputMock);
        $this->assertEquals(0, $result);
        $this->assertTrue($hasDispatched, 'The gubee_catalog_product_validate event was not dispatched');
    }
}
