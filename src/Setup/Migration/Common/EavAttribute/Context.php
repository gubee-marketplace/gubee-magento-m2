<?php

declare(strict_types=1);

namespace Gubee\Integration\Setup\Migration\Common\EavAttribute;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;

class Context
{
    protected Config $config;
    protected EavSetupFactory $eavSetupFactory; /* @phpstan-ignore-line */
    protected ResourceConnection $resourceConnection;

    // phpcs:ignore
    public function __construct(
        Config $config,
        EavSetupFactory $eavSetupFactory, /* @phpstan-ignore-line */
        ResourceConnection $resourceConnection
    ) {
        $this->config             = $config;
        $this->eavSetupFactory    = $eavSetupFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getEavSetupFactory(): EavSetupFactory /* @phpstan-ignore-line */
    {
        return $this->eavSetupFactory;
    }

    public function getResourceConnection(): ResourceConnection
    {
        return $this->resourceConnection;
    }
}
