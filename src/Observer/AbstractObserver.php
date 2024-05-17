<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer;

use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractObserver implements ObserverInterface
{
    protected Observer $observer;
    protected LoggerInterface $logger;
    protected Config $config;
    protected Management $queueManagement;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement
    ) {
        $this->config          = $config;
        $this->logger          = $logger;
        $this->queueManagement = $queueManagement;
    }

    /**
     * Execute the observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->setObserver($observer);
        if ($this->isAllowed() == true) {
            $this->process();
        }
    }

    /**
     * Process the observer
     */
    abstract protected function process(): void;

    /**
     * Validate if the observer is allowed to run
     */
    protected function isAllowed(): bool
    {
        if (! $this->getConfig()->getActive()) {
            return false;
        }

        return true;
    }

    public function getObserver(): Observer
    {
        return $this->observer;
    }

    public function setObserver(Observer $observer): self
    {
        $this->observer = $observer;
        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getQueueManagement(): Management
    {
        return $this->queueManagement;
    }
}
