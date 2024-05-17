<?php

declare(strict_types=1);

namespace Gubee\Integration\Setup;

use Gubee\Integration\Setup\Migration\Context;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

abstract class AbstractMigration implements DataPatchInterface, PatchRevertableInterface
{
    protected const AREA_CODE = null;
    protected Context $context;

    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    /**
     * Undo/revert the migration (optional)
     *
     * @return void
     * @phpcs:disable
     */
    protected function rollback()
    {
        // optional, override to implement an undo feature in your migration
    }
    /** @phpcs:disable */

    /**
     * Your migration logic goes here
     * 
     * @return void
     */
    abstract protected function execute() : void;

    /**
     * @inheritDoc
     */
    final public function apply()
    {
        if ($appliedAlias = $this->hasAlreadyAppliedAlias()) {
            $this->context->getLogger()->info(
                (string)
                __(
                'Patch data "%1" skipped because the it was already '
                    . 'applied under the old name "%2"',
                    [static::class, $appliedAlias]
                )
            );

            return $this;
        }

        $this->getConnection()->startSetup();

        if ($areaCode = static::AREA_CODE) {
            try {
                $this->context->getState()
                    ->setAreaCode($areaCode);
            } catch (\Throwable $th) {
                $this->context->getLogger()->error(
                    'Failed to set area code: ' . $th->getMessage()
                );
            }
        }

        $this->execute();

        $this->getConnection()->endSetup();

        return $this;
    }


    /**
     * @inheritDoc
     */
    final public function revert()
    {
        $this->getConnection()->startSetup();
        $this->rollback();
        $this->getConnection()->endSetup();
    }


    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Workaround for Magento core bug
     *
     * Return false if there's no previous applied alias,
     * otherwise return the applied alias name
     *
     * @see https://github.com/magento/magento2/issues/31396
     *
     * @return string|false
     */
    private function hasAlreadyAppliedAlias()
    {
        foreach ($this->getAliases() as $alias) {
            if ($this->context->getPatchHistory()->isApplied($alias)) {
                return $alias;
            }
        }

        return false;
    }

    /**
     * Shorthand for getting the database connection
     */
    protected function getConnection(): AdapterInterface
    {
        return $this->getModuleDataSetup()->getConnection();
    }

    /**
     * Get given table name in database including prefix
     *
     * @param string $rawName
     * @return string
     */
    protected function getTableName(string $rawName): string
    {
        return $this->getModuleDataSetup()->getTable($rawName);
    }

    /**
     * Module Setup Data getter
     *
     * @return ModuleDataSetupInterface
     */
    protected function getModuleDataSetup()
    {
        return $this->context->getModuleDataSetup();
    }
}
