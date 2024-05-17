<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Catalog\Product\Attribute;

use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Service\Model\Catalog\Product\Attribute;
use Gubee\SDK\Client;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;

class SendCommand extends AbstractCommand
{
    protected Client $client;
    protected ProductAttributeRepositoryInterface $productAttributeRepository;
    protected ObjectManagerInterface $objectManager;

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        Client $client,
        ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->productAttributeRepository = $productAttributeRepository;
        $this->client                     = $client;
        $this->objectManager              = $objectManager;
        parent::__construct(
            $eventDispatcher,
            $logger,
            "catalog:product:attribute:send"
        );
    }

    protected function configure(): void
    {
        $this->setDescription("Send the attribute to Gubee");
        $this->setHelp("This command will send the attribute to Gubee");
        $this->addArgument("attribute", InputArgument::REQUIRED, "The attribute code to be sent");
    }

    protected function doExecute(): int
    {
        $this->logger->info("Sending attribute");

        $attribute = $this->input->getArgument("attribute");
        $attribute = $this->productAttributeRepository->get($attribute);
        $attribute = $this->objectManager->create(
            Attribute::class,
            [
                'attribute' => $attribute,
            ]
        )->getGubeeAttribute();
        return 0;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getPriority(): int
    {
        return 1000;
    }
}
