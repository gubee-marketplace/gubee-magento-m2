<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data;

use DateTimeInterface;
use Gubee\Integration\Api\Enum\MainCategoryEnum;

interface ConfigInterface
{
    /**
     * Path to 'active' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_ACTIVE = "gubee/general/active";

    /**
     * Path to 'api_key' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_API_KEY = "gubee/general/api_key";

    /**
     * Path to 'api_token' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_API_TOKEN = "gubee/general/api_token";

    /**
     * Path to 'api_timeout' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_API_TIMEOUT = "gubee/general/api_timeout";

    /**
     * Path to 'max_backoff_attempts' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_MAX_BACKOFF_ATTEMPTS = "gubee/general/max_backoff_attempts";

    /**
     * Path to 'max_attempts' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_MAX_ATTEMPTS = "gubee/general/max_attempts";

    /**
     * Path to 'log_level' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_LOG_LEVEL = "gubee/general/log_level";

    /**
     * Path to 'product_heading' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_PRODUCT_HEADING = "gubee/attributes/product_heading";

    /**
     * Path to 'brand' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_BRAND = "gubee/attributes/brand";

    /**
     * Path to 'price' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_PRICE = "gubee/attributes/price";

    /**
     * Path to 'nbm' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_NBM = "gubee/attributes/nbm";

    /**
     * Path to 'ean' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_EAN = "gubee/attributes/ean";

    /**
     * Path to 'color' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_COLOR = "gubee/attributes/color";

    /**
     * Path to 'measure_heading' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_MEASURE_HEADING = "gubee/attributes/measure_heading";

    /**
     * Path to 'width' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_WIDTH = "gubee/attributes/width";

    /**
     * Path to 'height' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_HEIGHT = "gubee/attributes/height";

    /**
     * Path to 'depth' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_DEPTH = "gubee/attributes/depth";

    /**
     * Path to 'measure_unit' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_MEASURE_UNIT = "gubee/attributes/measure_unit";

    /**
     * Path to 'cross_docking_time' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_CROSS_DOCKING_TIME = "gubee/attributes/cross_docking_time";

    /**
     * Path to 'warranty_time' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_WARRANTY_TIME = "gubee/attributes/warranty_time";

    /**
     * Path to 'main_category' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_MAIN_CATEGORY = "gubee/attributes/main_category";

    /**
     * Path to 'blacklist' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_BLACKLIST = "gubee/attributes/blacklist";

    /**
     * Path to 'queue_page_size' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_QUEUE_PAGE_SIZE = 'gubee/general/queue_page_size';

    public const CONFIG_PATH_DEFAULT_DELIVERY_TIME = 'carriers/gubee/default_delivery_time';
    public const CONFIG_PATH_FULFILMENT_ENABLE     = 'gubee/general/fulfilment_enable';
    public const CONFIG_PATH_FULFILMENT_RULES      = 'gubee/general/fulfilment_rules';

    /**
     * Set the 'active' system config.
     */
    public function setActive(bool $active): self;

    /**
     * Get the 'active' system config.
     */
    public function getActive(): bool;

    /**
     * Set the 'api_key' system config.
     */
    public function setApiKey(string $apiKey): self;

    /**
     * Get the 'api_key' system config.
     */
    public function getApiKey(): string;

    /**
     * Set the 'api_token' system config.
     *
     * @param mixed $apiToken
     */
    public function setApiToken($apiToken): self;

    /**
     * Get the 'api_token' system config.
     */
    public function getApiToken(): string;

    /**
     * Set the 'api_timeout' system config.
     *
     * @param mixed $apiTimeout
     */
    public function setApiTimeout($apiTimeout): self;

    /**
     * Get the 'api_timeout' system config.
     */
    public function getApiTimeout(): ?DateTimeInterface;

    /**
     * Set the 'max_backoff_attempts' system config.
     */
    public function setMaxBackoffAttempts(int $maxBackoffAttempts): self;

    /**
     * Get the 'max_backoff_attempts' system config.
     */
    public function getMaxBackoffAttempts(): int;

    /**
     * Set the 'max_attempts' system config.
     */
    public function setMaxAttempts(int $maxAttempts): self;

    /**
     * Get the 'max_attempts' system config.
     */
    public function getMaxAttempts(): int;

    /**
     * Set the 'log_level' system config.
     *
     * @param array<string, mixed> $logLevel
     */
    public function setLogLevel(array $logLevel): self;

    /**
     * Get the 'log_level' system config.
     *
     * @return array<string, mixed>
     */
    public function getLogLevel(): array;

    /**
     * Set the 'brand' attribute.
     *
     * @param mixed $brand
     */
    public function setBrandAttribute($brand): self;

    /**
     * Get the 'brand' attribute.
     */
    public function getBrandAttribute(): string;

    /**
     * Set the 'price' attribute.
     *
     * @param mixed $price
     */
    public function setPriceAttribute($price): self;

    /**
     * Get the 'price' attribute.
     */
    public function getPriceAttribute(): string;

    /**
     * Set the 'nbm' attribute.
     *
     * @param mixed $nbm
     */
    public function setNbmAttribute($nbm): self;

    /**
     * Get the 'nbm' attribute.
     */
    public function getNbmAttribute(): string;

    /**
     * Set the 'ean' attribute.
     *
     * @param mixed $ean
     */
    public function setEanAttribute($ean): self;

    /**
     * Get the 'ean' attribute.
     */
    public function getEanAttribute(): string;

    /**
     * Set the 'color' attribute.
     *
     * @param mixed $color
     */
    public function setColorAttribute($color): self;

    /**
     * Get the 'color' attribute.
     */
    public function getColorAttribute(): string;

    /**
     * Set the 'measure_heading' attribute.
     *
     * @param mixed $measureHeading
     */
    public function setMeasureHeadingAttribute($measureHeading): self;

    /**
     * Get the 'measure_heading' attribute.
     */
    public function getMeasureHeadingAttribute(): string;

    /**
     * Set the 'width' attribute.
     *
     * @param mixed $width
     */
    public function setWidthAttribute($width): self;

    /**
     * Get the 'width' attribute.
     */
    public function getWidthAttribute(): string;

    /**
     * Set the 'height' attribute.
     *
     * @param mixed $height
     */
    public function setHeightAttribute($height): self;

    /**
     * Get the 'height' attribute.
     */
    public function getHeightAttribute(): string;

    /**
     * Set the 'depth' attribute.
     *
     * @param mixed $depth
     */
    public function setDepthAttribute($depth): self;

    /**
     * Get the 'depth' attribute.
     */
    public function getDepthAttribute(): string;

    /**
     * Set the 'measure_unit' attribute.
     *
     * @param mixed $measureUnit
     */
    public function setMeasureUnitAttribute($measureUnit): self;

    /**
     * Get the 'measure_unit' attribute.
     */
    public function getMeasureUnitAttribute(): string;

    /**
     * Set the 'cross_docking_time' attribute.
     *
     * @param mixed $crossDockingTime
     */
    public function setCrossDockingTimeAttribute($crossDockingTime): self;

    /**
     * Get the 'cross_docking_time' attribute.
     */
    public function getCrossDockingTimeAttribute(): string;

    /**
     * Set the 'warranty_time' attribute.
     *
     * @param mixed $warrantyTime
     */
    public function setWarrantyTimeAttribute($warrantyTime): self;

    /**
     * Get the 'warranty_time' attribute.
     */
    public function getWarrantyTimeAttribute(): string;

    /**
     * Set the 'main_category' position.
     */
    public function setMainCategoryPosition(MainCategoryEnum $mainCategory): self;

    /**
     * Get the 'main_category' position.
     */
    public function getMainCategoryPosition(): MainCategoryEnum;

    /**
     * Set the 'blacklist' attribute.
     *
     * @param array<string, mixed> $blacklist
     */
    public function setBlacklistAttribute(array $blacklist): self;

    /**
     * Get the 'blacklist' attribute.
     *
     * @return array<string, mixed>
     */
    public function getBlacklistAttribute(): array;

    /**
     * Get the 'queue_page_size' system config.
     */
    public function getQueuePageSize(): int;

    /**
     * Get the 'default_delivery_time' system config.
     */
    public function getDefaultDeliveryTime(): int;

    /**
     * Set the 'default_delivery_time' system config.
     *
     * @return ConfigInterface
     */
    public function setDefaultDeliveryTime(int $defaultDeliveryTime): self;

    /**
     * Get the 'fulfilment_enable' system config.
     */
    public function getFulfilmentEnable(): bool;

    /**
     * Set the 'fulfilment_enable' system config.
     *
     * @return ConfigInterface
     */
    public function setFulfilmentEnable(bool $fulfilmentEnable): self;

    /**
     * Get the 'fulfilment_rules' system config.
     */
    public function getFulfilmentRules(): array;
}
