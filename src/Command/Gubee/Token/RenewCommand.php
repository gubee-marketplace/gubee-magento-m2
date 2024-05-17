<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Gubee\Token;

use DateTimeInterface;
use Exception;
use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Model\Config;
use Gubee\SDK\Api\ServiceProviderInterface;
use Gubee\SDK\Client;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;

class RenewCommand extends AbstractCommand
{
    protected Client $client;
    protected Config $config;

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        ServiceProviderInterface $serviceProvider,
        Registry $registry,
        Config $config
    ) {
        $this->client   = new Client(
            $serviceProvider,
            $logger
        );
        $this->registry = $registry;
        $this->config   = $config;
        parent::__construct(
            $eventDispatcher,
            $logger,
            "token:renew"
        );
    }

    /**
     * Configures the command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription("Renew the token");
        $this->setHelp("This command will renew the token");
        $this->addArgument("token", InputArgument::REQUIRED, "The token to be renewed");
    }

    /**
     * Executes the command.
     */
    protected function doExecute(): int
    {
        $this->logger->info("Renewing token");
        try {
            $token = $this->getClient()->token()->revalidate(
                $this->input->getArgument("token")
            );
            $this->config->setApiTimeout(
                $token->getValidity()->format(DateTimeInterface::ISO8601)
            );
            $this->config->setApiToken(
                $token->getToken()
            );
            $this->getLogger()->debug(
                "Token renewed successfully"
            );
        } catch (Exception $e) {
            $this->config->setApiToken("");
            $this->config->setApiTimeout(null);
            $this->getLogger()->error(
                $e->getMessage()
            );
        }

        return self::SUCCESS;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getPriority(): int
    {
        return 150;
    }
}
