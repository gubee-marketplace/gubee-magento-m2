<?php

declare(strict_types=1);

namespace Gubee\Integration\Model;

use DateTime;
use DateTimeInterface;
use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Api\Enum\MainCategoryEnum;
use Gubee\Integration\Command\Gubee\Token\RenewCommand;
use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\Weight\TypeEnum;
use LogicException;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function __;
use function array_map;
use function explode;

class Config extends AbstractHelper implements ConfigInterface
{
    protected $context;
    protected $configWriter;
    protected $reinitableConfig;
    protected $objectManager;
    protected $logger;

    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        ReinitableConfigInterface $reinitableConfig,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->reinitableConfig = $reinitableConfig;
        $this->objectManager    = $objectManager;
        $this->configWriter     = $configWriter;
        $this->logger           = $logger;
    }

    /**
     * Set the 'active' system config.
     */
    public function setActive(bool $active)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_ACTIVE, $active);
    }

    /**
     * Get the 'active' system config.
     */
    public function getActive(): bool
    {
        return (bool) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_ACTIVE);
    }

    /**
     * Set the 'api_key' system config.
     */
    public function setApiKey(string $apiKey)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_API_KEY, $apiKey);
    }

    /**
     * Get the 'api_key' system config.
     */
    public function getApiKey(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_API_KEY);
    }

    /**
     * Get the 'default_stock_id' system config
     */
    public function getDefaultStockId()  : int
    {
        
        return (int) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_DEFAULT_STOCK_ID);
    }

    /**
     * Set the 'api_token' system config.
     *
     * @param mixed $apiToken
     */
    public function setApiToken($apiToken)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_API_TOKEN, $apiToken);
    }

    /**
     * Get the 'api_token' system config.
     *
     * @throws LogicException If the API Key is not set.
     */
    public function getApiToken(): string
    {
        if (! $this->getApiKey()) {
            throw new LogicException(
                __("The API Key is not set")->__toString()
            );
        }
        if (! $this->isTokenValid()) {
            $this->getLogger()->debug(
                __("The API Token is not valid. Renewing it.")
                    ->__toString()
            );
            $command = $this->objectManager->create(
                RenewCommand::class
            );
            $input   = $this->objectManager->create(
                ArrayInput::class,
                [
                    'parameters' => [
                        'token' => $this->getApiKey(),
                    ],
                ]
            );
            $output  = $this->objectManager->create(
                BufferedOutput::class
            );
            $command->run($input, $output);
            $this->getLogger()->debug(
                $output->fetch()
            );
        }
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_API_TOKEN);
    }

    protected function isTokenValid(): bool
    {
        $tokenTimeout = $this->getApiTimeout();
        if (! $tokenTimeout) {
            return false;
        }

        $now = new DateTime();
        return $now < $tokenTimeout;
    }

    /**
     * Set the 'api_timeout' system config.
     *
     * @param mixed $apiTimeout
     */
    public function setApiTimeout($apiTimeout)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_API_TIMEOUT, $apiTimeout);
    }

    /**
     * Get the 'api_timeout' system config.
     */
    public function getApiTimeout(): ?DateTimeInterface
    {
        if (! $value = $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_API_TIMEOUT)) {
            return null;
        }
        return DateTime::createFromFormat(
            DateTimeInterface::ISO8601,
            $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_API_TIMEOUT)
        ) ?: null;
    }

    /**
     * Set the 'max_backoff_attempts' system config.
     */
    public function setMaxBackoffAttempts(int $maxBackoffAttempts)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_MAX_BACKOFF_ATTEMPTS, $maxBackoffAttempts);
    }

    /**
     * Get the 'max_backoff_attempts' system config.
     */
    public function getMaxBackoffAttempts(): int
    {
        return (int) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_MAX_BACKOFF_ATTEMPTS);
    }

    /**
     * Set the 'max_attempts' system config.
     */
    public function setMaxAttempts(int $maxAttempts)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_MAX_ATTEMPTS, $maxAttempts);
    }

    /**
     * Get the 'max_attempts' system config.
     */
    public function getMaxAttempts(): int
    {
        return (int) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_MAX_ATTEMPTS);
    }

    /**
     * Set the 'log_level' system config.
     *
     * @param array<string, mixed> $logLevel
     */
    public function setLogLevel(array $logLevel)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_LOG_LEVEL, $logLevel);
    }

    /**
     * Get the 'log_level' system config.
     *
     * @return array<string, mixed>
     */
    public function getLogLevel(): array
    {
        return (array) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_LOG_LEVEL);
    }

    /**
     * Set the 'brand' attribute.
     *
     * @param mixed $brand
     */
    public function setBrandAttribute($brand)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_BRAND, $brand);
    }

    /**
     * Get the 'brand' attribute.
     */
    public function getBrandAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_BRAND);
    }

    /**
     * Set the 'price' attribute.
     *
     * @param mixed $price
     */
    public function setPriceAttribute($price)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_PRICE, $price);
    }

    /**
     * Get the 'price' attribute.
     */
    public function getPriceAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_PRICE);
    }

    /**
     * Set the 'nbm' attribute.
     *
     * @param mixed $nbm
     */
    public function setNbmAttribute($nbm)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_NBM, $nbm);
    }

    /**
     * Get the 'nbm' attribute.
     */
    public function getNbmAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_NBM);
    }

    /**
     * Set the 'ean' attribute.
     *
     * @param mixed $ean
     */
    public function setEanAttribute($ean)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_EAN, $ean);
    }

    /**
     * Get the 'ean' attribute.
     */
    public function getEanAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_EAN);
    }

    /**
     * Set the 'color' attribute.
     *
     * @param mixed $color
     */
    public function setColorAttribute($color)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_COLOR, $color);
    }

    /**
     * Get the 'color' attribute.
     */
    public function getColorAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_COLOR);
    }

    /**
     * Set the 'measure_heading' attribute.
     *
     * @param mixed $measureHeading
     */
    public function setMeasureHeadingAttribute($measureHeading)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_MEASURE_HEADING, $measureHeading);
    }

    /**
     * Get the 'measure_heading' attribute.
     */
    public function getMeasureHeadingAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_MEASURE_HEADING);
    }

    /**
     * Set the 'width' attribute.
     *
     * @param mixed $width
     */
    public function setWidthAttribute($width)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_WIDTH, $width);
    }

    /**
     * Get the 'width' attribute.
     */
    public function getWidthAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_WIDTH);
    }

    /**
     * Set the 'height' attribute.
     *
     * @param mixed $height
     */
    public function setHeightAttribute($height)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_HEIGHT, $height);
    }

    /**
     * Get the 'height' attribute.
     */
    public function getHeightAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_HEIGHT);
    }

    /**
     * Set the 'depth' attribute.
     *
     * @param mixed $depth
     */
    public function setDepthAttribute($depth)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_DEPTH, $depth);
    }

    /**
     * Get the 'depth' attribute.
     */
    public function getDepthAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_DEPTH);
    }

    /**
     * Set the 'measure_unit' attribute.
     *
     * @param mixed $measureUnit
     */
    public function setMeasureUnitAttribute($measureUnit)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_MEASURE_UNIT, $measureUnit);
    }

    /**
     * Get the 'measure_unit' attribute.
     */
    public function getMeasureUnitAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_MEASURE_UNIT);
    }

    /**
     * Set the 'cross_docking_time' attribute.
     *
     * @param mixed $crossDockingTime
     */
    public function setCrossDockingTimeAttribute($crossDockingTime)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_CROSS_DOCKING_TIME, $crossDockingTime);
    }

    /**
     * Get the 'cross_docking_time' attribute.
     */
    public function getCrossDockingTimeAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_CROSS_DOCKING_TIME);
    }

    /**
     * Set the 'warranty_time' attribute.
     *
     * @param mixed $warrantyTime
     */
    public function setWarrantyTimeAttribute($warrantyTime)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_WARRANTY_TIME, $warrantyTime);
    }

    /**
     * Get the 'warranty_time' attribute.
     */
    public function getWarrantyTimeAttribute(): string
    {
        return (string) $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_WARRANTY_TIME);
    }

    /**
     * Set the 'main_category' position.
     */
    public function setMainCategoryPosition(MainCategoryEnum $mainCategory)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_MAIN_CATEGORY, $mainCategory->__toString());
    }

    /**
     * Get the 'main_category' position.
     */
    public function getMainCategoryPosition(): MainCategoryEnum
    {
        return MainCategoryEnum::fromValue(
            $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_MAIN_CATEGORY)
        );
    }

    /**
     * Set the 'blacklist' attribute.
     *
     * @param array<string> $blacklist
     */
    public function setBlacklistAttribute(array $blacklist)
    {
        return $this->saveConfig(ConfigInterface::CONFIG_PATH_BLACKLIST, $blacklist);
    }

    public function getQueuePageSize(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::CONFIG_PATH_QUEUE_PAGE_SIZE
        ) ?: 100;
    }

    /**
     * Get the 'blacklist' attribute.
     *
     * @return array<string>
     */
    public function getBlacklistAttribute(): array
    {
        $value = $this->scopeConfig->getValue(ConfigInterface::CONFIG_PATH_BLACKLIST) ?: '';
        $value = explode(',', $value);
        return array_map('trim', $value);
    }

    public function getWeightUnit(): TypeEnum
    {
        switch ($this->scopeConfig->getValue('general/locale/weight_unit')) {
            case 'lbs':
                return TypeEnum::POUND();
            case 'kg':
                return TypeEnum::KILOGRAM();
        }
        return TypeEnum::KILOGRAM();
    }

    /**
     * Save the given value to the given path.
     *
     * @param mixed $value
     * @return Config
     */
    protected function saveConfig(string $path, $value)
    {
        $this->configWriter->save($path, $value);
        $this->reinitableConfig->reinit();
        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get the 'default_delivery_time' system config.
     */
    public function getDefaultDeliveryTime(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::CONFIG_PATH_DEFAULT_DELIVERY_TIME
        ) ?: 45;
    }

    /**
     * Set the 'default_delivery_time' system config.
     *
     * @return ConfigInterface
     */
    public function setDefaultDeliveryTime(int $defaultDeliveryTime)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_DEFAULT_DELIVERY_TIME,
            $defaultDeliveryTime
        );
    }

    /**
     * Get the 'fulfilment_enable' system config.
     */
    public function getFulfilmentEnable(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_PATH_FULFILMENT_ENABLE
        );
    }

    /**
     * Set the 'fulfilment_enable' system config.
     *
     * @return ConfigInterface
     */
    public function setFulfilmentEnable(bool $fulfilmentEnable)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_FULFILMENT_ENABLE,
            $fulfilmentEnable
        );
    }

    /**
     * Get the 'fulfilment_grid_rules' system config.
     */
    public function getFulfilmentRules(): array
    {
        if (! $this->getFulfilmentEnable()) {
            return [];
        }

        $content = $this->scopeConfig->getValue(
            self::CONFIG_PATH_FULFILMENT_RULES
        ) ?: '';
        return explode(',', $content) ?: [];
    }
    /**
     * Get the 'invoice_active' system config.
     * @return bool
     */
    public function getInvoiceActive(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::CONFIG_PATH_INVOICE_ACTIVE);
    }
   
    /**
     * Set the 'invoice_active' system config.
     * @param bool $invoiceActive
     * @return ConfigInterface
     */
    public function setInvoiceActive(bool $invoiceActive)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_INVOICE_ACTIVE,
            $invoiceActive
        );
    }

    /**
     * Get the 'invoice_regex_invoice_number' system config
     * @return string
     */
    public function getInvoiceNumberRegex() : string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_INVOICE_REGEX_NUMBER);
    }

    /**
     * Set the 'invoice_regex_invoice_number' system config
     * @param string $invoiceNumberRegex
     * @return ConfigInterface
     */
    public function setInvoiceNumberRegex(string $invoiceNumberRegex)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_INVOICE_REGEX_NUMBER,
            $invoiceNumberRegex
        );
    }
    /**
     * Get the 'invoice_regex_invoice_series' system config
     * @return string
     */
    public function getInvoiceSeriesRegex() : string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_INVOICE_REGEX_SERIES);
    }

    /**
     * Set the 'invoice_regex_invoice_series' system config
     * @param string $invoiceSeriesRegex
     * @return ConfigInterface
     */
    public function setInvoiceSeriesRegex(string $invoiceSeriesRegex)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_INVOICE_REGEX_SERIES,
            $invoiceSeriesRegex
        );
    }
    /**
     * Get the 'invoice_regex_invoice_key' system config
     * @return string
     */
    public function getInvoiceKeyRegex() : string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_INVOICE_REGEX_KEY);
    }

    /**
     * Set the 'invoice_regex_invoice_key' system config
     * @param string $invoiceKeyRegex
     * @return ConfigInterface
     */
    public function setInvoiceKeyRegex(string $invoiceKeyRegex)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_INVOICE_REGEX_KEY,
            $invoiceKeyRegex
        );
    }
    /**
     * Get the 'invoice_regex_invoice_date' system config
     * @return string
     */
    public function getInvoiceDateRegex() : string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_INVOICE_REGEX_DATE);
    }

    /**
     * Set the 'invoice_regex_invoice_date' system config
     * @param string $invoiceDateRegex
     * @return ConfigInterface
     */
    public function setInvoiceDateRegex(string $invoiceDateRegex)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_INVOICE_REGEX_DATE,
            $invoiceDateRegex
        );
    }
    /**
     * Get the 'invoice_regex_invoice_link' system config
     * @return string
     */
    public function getInvoiceLinkRegex() : string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_INVOICE_REGEX_LINK);
    }

    /**
     * Set the 'invoice_regex_invoice_link' system config
     * @param string $invoiceLinkRegex
     * @return ConfigInterface
     */
    public function setInvoiceLinkRegex(string $invoiceLinkRegex)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_INVOICE_REGEX_LINK,
            $invoiceLinkRegex
        );
    }

    /**
     * Get the 'invoice_regex_invoice_content' system config
     * @return string
     */
    public function getInvoiceContentRegex() : string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_INVOICE_REGEX_CONTENT);
    }

    /**
     * Set the 'invoice_regex_invoice_content' system config
     * @param string $invoiceContentRegex
     * @return ConfigInterface
     */
    public function setInvoiceContentRegex(string $invoiceContentRegex)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_INVOICE_REGEX_CONTENT,
            $invoiceContentRegex
        );
    }
    /**
     * Get the 'invoice_cleanup_xml' system config
     * @return string
     */
    public function getInvoiceCleanupXml() : bool
    {
        return (bool) $this->scopeConfig->getValue(self::CONFIG_PATH_INVOICE_CLEANUP_XML);
    }

    /**
     * Set the 'invoice_cleanup_xml' system config
     * @param bool $cleanupXml
     * @return ConfigInterface
     */
    public function setInvoiceCleanupXml(bool $cleanupXml)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_INVOICE_CLEANUP_XML,
            $cleanupXml
        );
    }

    public function getPreventEmailSend() : bool
    {
        return (bool) $this->scopeConfig->getValue(self::CONFIG_PATH_PREVENT_EMAIL_SEND);
    }

    public function setPreventEmailSend(bool $preventEmailSend)
    {
        return $this->saveConfig(
            self::CONFIG_PATH_PREVENT_EMAIL_SEND,
            $preventEmailSend
        );
    }
}
