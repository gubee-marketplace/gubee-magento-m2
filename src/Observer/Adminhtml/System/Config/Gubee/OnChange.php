<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Adminhtml\System\Config\Gubee;

use Gubee\Integration\Command\Catalog\Product\Attribute\SyncCommand;
use Gubee\Integration\Command\Gubee\Token\RenewCommand;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function __;
use function in_array;

class OnChange extends AbstractObserver
{
    protected TypeListInterface $typeList;
    protected Pool $pool;
    protected RenewCommand $renewCommand;
    protected ObjectManagerInterface $objectManager;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement,
        TypeListInterface $typeList,
        ObjectManagerInterface $objectManager,
        RenewCommand $renewCommand,
        Pool $pool
    ) {
        parent::__construct($config, $logger, $queueManagement);
        $this->typeList      = $typeList;
        $this->renewCommand  = $renewCommand;
        $this->objectManager = $objectManager;
        $this->pool          = $pool;
    }

    protected function process(): void
    {
        $this->getLogger()->debug(
            __(
                "The Gubee Integration configuration has been changed."
            )->__toString()
        );

        $changedPaths = $this->getObserver()
            ->getData('changed_paths');
        if (in_array('gubee/general/active', $changedPaths)) {
            $this->getLogger()->debug(
                __(
                    "The Gubee Integration active status has been changed."
                )->__toString()
            );
            $this->clearMenuCache();
        }

        /**
         * If the api key has changed we need to generate a new token
         * and save it in the configuration
         */
        if (in_array('gubee/general/api_key', $changedPaths)) {
            $this->getLogger()->debug(
                __(
                    "The Gubee Integration API Key has been changed."
                )->__toString()
            );
            $this->generateToken();
        }

        $this->getQueueManagement()->append(
            SyncCommand::class,
            []
        );
        $this->getQueueManagement()->append(
            \Gubee\Integration\Command\Catalog\Category\SyncCommand::class,
            []
        );
    }

    protected function generateToken(): void
    {
        $input  = $this->getObjectManager()->create(
            ArrayInput::class,
            [
                'parameters' => [
                    'token' => $this->getConfig()->getApiKey(),
                ],
            ]
        );
        $output = $this->getObjectManager()->create(
            BufferedOutput::class
        );
        $this->renewCommand->run(
            $input,
            $output
        );
        $this->getLogger()->debug(
            __(
                "The Gubee Integration token has been renewed."
            )->__toString()
        );
    }

    protected function clearMenuCache(): void
    {
        $types = [
            'layout',
            'full_page',
            'translate',
        ];
        foreach ($types as $type) {
            $this->getTypeList()
                ->cleanType($type);
        }
        foreach ($this->getPool() as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
        $this->getLogger()->debug(
            __(
                "The Gubee Integration menu cache has been cleared."
            )->__toString()
        );
    }

    protected function isAllowed(): bool
    {
        return true;
    }

    public function getTypeList(): TypeListInterface
    {
        return $this->typeList;
    }

    public function getPool(): Pool
    {
        return $this->pool;
    }

    public function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }
}
