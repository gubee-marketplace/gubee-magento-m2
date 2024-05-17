<?php

declare(strict_types=1);

namespace Gubee\Integration\Setup\Migration;

use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchHistory;
use Psr\Log\LoggerInterface;

class Context
{
    protected ModuleDataSetupInterface $moduleDataSetup;
    protected LoggerInterface $logger;
    protected PatchHistory $patchHistory;
    protected State $state;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger,
        PatchHistory $patchHistory,
        State $state
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger          = $logger;
        $this->patchHistory    = $patchHistory;
        $this->state           = $state;
    }

    public function getModuleDataSetup(): ModuleDataSetupInterface
    {
        return $this->moduleDataSetup;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getPatchHistory(): PatchHistory
    {
        return $this->patchHistory;
    }

    public function getState(): State
    {
        return $this->state;
    }
}
