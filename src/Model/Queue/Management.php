<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Queue;

use Gubee\Integration\Api\Data\MessageInterface;
use Gubee\Integration\Api\Data\MessageInterfaceFactory;
use Gubee\Integration\Api\Enum\Message\StatusEnum;
use Gubee\Integration\Api\MessageRepositoryInterface;
use Gubee\Integration\Api\Queue\ManagementInterface;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use InvalidArgumentException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function __;
use function json_encode;
use function sprintf;

class Management implements ManagementInterface
{
    protected LoggerInterface $logger;
    protected MessageCollectionFactory $messageCollectionFactory; /* @phpstan-ignore-line */
    protected MessageInterfaceFactory $messageFactory; /* @phpstan-ignore-line */
    protected MessageRepositoryInterface $messageRepository;
    protected ProductRepositoryInterface $productRepository;
    protected Attribute $attribute;
    protected ObjectManagerInterface $objectManager;
    public function __construct(
        LoggerInterface $logger,
        MessageCollectionFactory $messageCollectionFactory, /* @phpstan-ignore-line */
        MessageInterfaceFactory $messageFactory, /* @phpstan-ignore-line */
        MessageRepositoryInterface $messageRepository,
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager,
        Attribute $attribute
    ) {
        $this->attribute                = $attribute;
        $this->productRepository        = $productRepository;
        $this->logger                   = $logger;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->messageFactory           = $messageFactory;
        $this->messageRepository        = $messageRepository;
        $this->objectManager            = $objectManager;
    }

    /**
     * Append a job to the queue with the given command and parameters.
     *
     * @param array<int|string, mixed> $params
     */
    public function append(string $command, array $params = [], ?int $productId = null): self
    {
        try {
            if ($command !== SendCommand::class) {
                if ($productId) {
                    $product = $this->productRepository->getById($productId);
                    if (! $product->getId()) {
                        throw new NoSuchEntityException(
                            __(
                                "Product with ID '%s' not found",
                                $productId
                            )
                        );
                    }

                    if ($this->attribute->getRawAttributeValue('gubee_integration_status', $product) === 0) {
                        throw new InvalidArgumentException(
                            sprintf(
                                "Product with ID '%s' is not integrated with Gubee yet",
                                $productId
                            )
                        );
                    }
                }
            }
            /* @phpstan-ignore-next-line */
            $message = $this->messageFactory->create();
            $message->setCommand($command);
            $message->setPayload($params);
            $message->setPriority(
                $this->objectManager->create($command)->getPriority() ?: 0
            );

            if ($productId) {
                $message->setProductId($productId);
            }

            if (! $this->alreadyQueued($message)) {
                $this->messageRepository->save($message);
            }
        } catch (Throwable $th) {
            $this->logger->error(
                "Queue Management: Error while appending message to queue",
                [
                    'exception' => $th,
                    'command'   => $command,
                    'params'    => $params,
                ]
            );
        }

        return $this;
    }

    /**
     * Check if given message is already queued
     */
    public function alreadyQueued(MessageInterface $message): bool
    {
        /* @phpstan-ignore-next-line */
        $queued = $this->messageCollectionFactory->create()
            ->addFieldToFilter(MessageInterface::COMMAND, $message->getCommand());
        if ($message->getPayload()) {
            $queued->addFieldToFilter(MessageInterface::PAYLOAD, json_encode($message->getPayload()));
        }

        return $queued->addFieldToFilter(MessageInterface::STATUS, StatusEnum::PENDING())
            ->getSize() > 0;
    }
}
