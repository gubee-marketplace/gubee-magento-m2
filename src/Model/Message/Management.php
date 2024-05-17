<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Message;

use Gubee\Integration\Api\Data\MessageInterface;
use Gubee\Integration\Api\Enum\Message\StatusEnum;
use Gubee\Integration\Api\Message\ManagementInterface;
use Gubee\Integration\Api\MessageRepositoryInterface;
use Gubee\Integration\Command\Catalog\Product\SendCommand;
use Gubee\Integration\Command\Sales\Order\Processor\Exception\BlacklistedException;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\Config;
use InvalidArgumentException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

use function __;
use function sprintf;

class Management implements ManagementInterface
{
    protected DateTime $date;
    protected FileDriver $fileDriver;
    protected LoggerInterface $logger;
    protected MessageRepositoryInterface $messageRepository;
    protected ObjectManagerInterface $objectManager;
    protected ScopeConfigInterface $scopeConfig;
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    protected Registry $registry;
    protected Attribute $attribute;
    protected ProductRepositoryInterface $productRepository;
    protected Config $config;

    public function __construct(
        DateTime $date,
        LoggerInterface $logger,
        Config $config,
        ProductRepositoryInterface $productRepository,
        MessageRepositoryInterface $messageRepository,
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Registry $registry,
        Attribute $attribute
    ) {
        $this->config                = $config;
        $this->productRepository     = $productRepository;
        $this->attribute             = $attribute;
        $this->date                  = $date;
        $this->logger                = $logger;
        $this->messageRepository     = $messageRepository;
        $this->objectManager         = $objectManager;
        $this->scopeConfig           = $scopeConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->registry              = $registry;
    }

    public function process(MessageInterface $message): void
    {
        if ($this->registry->registry('gubee_current_message')) {
            $this->registry->unregister('gubee_current_message');
        }

        $this->registry->register('gubee_current_message', $message);
        $this->logger->debug(
            sprintf(
                "Processing message '%s' with status '%s'",
                $message->getId(),
                $message->getStatus()
            )
        );
        // if ($message->getAttempts() == $this->config->getMaxAttempts()) {
        //     $this->updateMessageStatus(
        //         $message,
        //         StatusEnum::ERROR()
        //     );
        //     $this->logger->error(
        //         sprintf(
        //             "Message '%s' reached the maximum number of attempts",
        //             $message->getId()
        //         )
        //     );
        //     return;
        // }

        $status = StatusEnum::ERROR();
        try {
            if ($message->getCommand() !== SendCommand::class) {
                if ($message->getProductId()) {
                    $product = $this->productRepository->getById($message->getProductId());
                    if (! $product->getId()) {
                        throw new NoSuchEntityException(
                            __(
                                "Product with ID '%s' not found",
                                $message->getProductId()
                            )
                        );
                    }

                    if ($this->attribute->getRawAttributeValue('gubee_integration_status', $product) === 0) {
                        throw new InvalidArgumentException(
                            sprintf(
                                "Product with ID '%s' is not integrated with Gubee yet",
                                $message->getProductId()
                            )
                        );
                    }
                }
            }

            $message->setAttempts(
                $message->getAttempts() + 1
            );
            $this->updateMessageStatus(
                $message,
                StatusEnum::RUNNING()
            );
            $this->logger->debug(
                sprintf(
                    "Executing message '%s'",
                    $message->getId()
                )
            );
            $this->execute($message);
            $this->updateMessageStatus($message, StatusEnum::DONE());
        } catch (BlacklistedException $e) {
            $status = StatusEnum::DONE();
            $this->logger->error(
                $e->getMessage()
            );
            $message->setMessage(
                (string) $e->getMessage()
            );
            $this->updateMessageStatus($message, StatusEnum::DONE());
        } catch (ErrorException $e) {
            $status = StatusEnum::ERROR();

            $result = __(
                "EXCEPTION: '%1', check the %s for more details.",
                'var/log/exception.log',
                (string) $e->getResponse()->getBody() ?: $e->getMessage()
            )->__toString();

            $this->logger->error(
                $result,
                [
                    'exception' => $e->getMessage(),
                    'trace'     => $e->getTraceAsString()
                ]
            );
            $message->setMessage(
                (string) $result
            );
            $this->updateMessageStatus($message, StatusEnum::ERROR());
        } catch (InvalidArgumentException $e) {
            $this->logger->warning(
                $e->getMessage()
            );
            $this->updateMessageStatus(
                $message,
                StatusEnum::PENDING()
            );
            $this->updateMessageStatus($message, StatusEnum::ERROR());
        } catch (Throwable $e) {
            $result = __(
                "EXCEPTION: '%1', check the %2 for more details.",
                'var/log/exception.log',
                $e->getMessage()
            );
            $status = StatusEnum::ERROR();

            $this->logger->error(
                $result,
                [
                    'exception' => $e->getMessage(),
                    'trace'     => $e->getTraceAsString()
                ]
            );
            $message->setMessage(
                (string) $e->getMessage()
            );
            $this->updateMessageStatus($message, StatusEnum::ERROR());
        }
        $this->logger->debug(
            sprintf(
                "Message '%s' processed with status '%s'",
                $message->getId(),
                $message->getStatus()
            )
        );
    }

    protected function execute(MessageInterface $message): void
    {
        try {
            $command = $this->objectManager->create(
                $message->getCommand()
            );
            $input   = $this->objectManager->create(
                ArrayInput::class,
                [
                    'parameters' => $message->getPayload(),
                ]
            );
            $output  = $this->objectManager->create(
                BufferedOutput::class
            );
            $command->run($input, $output);
            $message->setMessage(
                (string) $output->fetch()
            );
        } catch (Throwable $e) {
            $message->setMessage(
                (string) $e->getMessage()
            );
            throw $e;
        }
    }

    private function updateMessageStatus(MessageInterface $message, StatusEnum $status): void
    {
        $message->setStatus($status);
        $this->logger->debug(
            sprintf(
                "Updating message '%s' status to '%s'",
                $message->getId(),
                $message->getStatus()
            )
        );
        $this->messageRepository->save($message);
    }

    public function getPending()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                MessageInterface::STATUS,
                (int) StatusEnum::PENDING()->__toString()
            )->create();

        return $this->messageRepository->getList(
            $searchCriteria
        );
    }

    public function getToBeRetried(): array
    {
        $retryAmount = $this->scopeConfig->getValue('queue/general/auto_retry_amount');
        if (! $retryAmount) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(MessageInterface::STATUS, (int) StatusEnum::ERROR()->__toString())
            ->addFilter(MessageInterface::ATTEMPTS, $retryAmount, 'lt')
            ->create();

        return $this->messageRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Process a bunch of messages at once
     *
     * @param iterable<MessageInterface> $messages
     */
    public function massProcess(iterable $messages): void
    {
        foreach ($messages as $message) {
            $this->process($message);
        }
    }
}
