<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data;

use DateTimeInterface;
use Gubee\Integration\Api\Enum\MainCategoryEnum;
use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\Weight\TypeEnum;

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
     * Path to 'default_stock_id' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_DEFAULT_STOCK_ID = "gubee/general/default_stock_id";

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
    /**
     * Path to 'prevent_email_send' system config.
     *
     * @var string
     */
    public const CONFIG_PATH_PREVENT_EMAIL_SEND = 'gubee/general/prevent_email_send';

    public const CONFIG_PATH_DEFAULT_DELIVERY_TIME = 'carriers/gubee/default_delivery_time';

    public const CONFIG_PATH_FULFILMENT_ENABLE     = 'gubee/general/fulfilment_enable';

    public const CONFIG_PATH_FULFILMENT_RULES      = 'gubee/general/fulfilment_rules';

    /**
     * Path to 'invoice_active' system config 
     */
    public const CONFIG_PATH_INVOICE_ACTIVE = 'gubee/invoice/active';
    /**
     * Path to 'invoice_regex_invoice_number' system config 
     */
    public const CONFIG_PATH_INVOICE_REGEX_NUMBER = 'gubee/invoice/regex_invoice_number';
    /**
     * Path to 'invoice_regex_invoice_series' system config 
     */
    public const CONFIG_PATH_INVOICE_REGEX_SERIES = 'gubee/invoice/regex_invoice_series';
    /**
     * Path to 'invoice_regex_invoice_key' system config 
     */
    public const CONFIG_PATH_INVOICE_REGEX_KEY = 'gubee/invoice/regex_invoice_key';
    /**
     * Path to 'invoice_regex_invoice_date' system config 
     */
    public const CONFIG_PATH_INVOICE_REGEX_DATE = 'gubee/invoice/regex_invoice_date';
    /**
     * Path to 'invoice_regex_invoice_link' system config 
     */
    public const CONFIG_PATH_INVOICE_REGEX_LINK = 'gubee/invoice/regex_invoice_link';

     /**
     * Path to 'invoice_regex_invoice_content' system config 
     */
    public const CONFIG_PATH_INVOICE_REGEX_CONTENT = 'gubee/invoice/regex_invoice_content';

     /**
     * Path to 'invoice_regex_invoice_content' system config 
     */
    public const CONFIG_PATH_INVOICE_DATE_FORMAT = 'gubee/invoice/date_format';

    /**
     * Path to 'invoice_regex_invoice_content' system config 
     */
    public const CONFIG_PATH_INVOICE_CLEANUP_XML = 'gubee/invoice/cleanup_xml';

    public const CONFIG_PATH_INVENTORY_PLUGIN_RESERVATIONS = 'gubee/inventory/plugin_inventory_reservations';

    public const CONFIG_PATH_INVENTORY_PLUGIN_INVENTORY = 'gubee/inventory/plugin_inventory';
    
    public const CONFIG_PATH_INVENTORY_CATALOG_PLUGIN_INVENTORY_DELETE = 'gubee/inventory/plugin_inventory_delete';

    public const CONFIG_PATH_INVENTORY_CATALOG_PLUGIN_INVENTORY_UPDATE = 'gubee/inventory/plugin_inventory_update';

    public const CONFIG_PATH_INVENTORY_EVENT_SHIPMENT = 'gubee/inventory/event_shipment';
    
    public const CONFIG_PATH_INVENTORY_EVENT_ORDER = 'gubee/inventory/event_order';

    public const CONFIG_PATH_CUSTOMER_GROUP_AUTO_ASSOC = 'gubee/customer/auto_associate_customers';

    public const CONFIG_PATH_CUSTOMER_GROUP_CPF = 'gubee/customer/customer_group_id_cpf';
    
    public const CONFIG_PATH_CUSTOMER_GROUP_CNPJ = 'gubee/customer/customer_group_id_cnpj';

    /**
     * Multi Stock Area
     */

    public const CONFIG_PATH_MULTISTOCK_ENABLE = 'gubee/multiple_inventory/enable_multi_inventory';

    public const CONFIG_PATH_MULTISTOCK_RELATION = 'gubee/multiple_inventory/stock_relation';
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
     * Get the 'default_stock_id' system config.
     */
    public function getDefaultStockId(): int;

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
     * Get the 'weight_unit' attribute.
     */
    public function getWeightUnit(): TypeEnum;

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
     */
    public function setDefaultDeliveryTime(int $defaultDeliveryTime): self;

    /**
     * Get the 'fulfilment_enable' system config.
     */
    public function getFulfilmentEnable(): bool;

    /**
     * Set the 'fulfilment_enable' system config.
     */
    public function setFulfilmentEnable(bool $fulfilmentEnable): self;

    /**
     * Get the 'fulfilment_rules' system config.
     */
    public function getFulfilmentRules(): array;

    /**
     * Get the 'invoice_active' system config.
     */
    public function getInvoiceActive(): bool;
   
    /**
     * Set the 'invoice_active' system config.
     */
    public function setInvoiceActive(bool $invoiceActive): self;

    /**
     * Get the 'invoice_regex_invoice_number' system config
     */
    public function getInvoiceNumberRegex() : string;

    /**
     * Set the 'invoice_regex_invoice_number' system config
     */
    public function setInvoiceNumberRegex(string $invoiceNumberRegex): self;
    /**
     * Get the 'invoice_regex_invoice_series' system config
     */
    public function getInvoiceSeriesRegex() : string;

    /**
     * Set the 'invoice_regex_invoice_series' system config
     */
    public function setInvoiceSeriesRegex(string $invoiceSeriesRegex): self;
    /**
     * Get the 'invoice_regex_invoice_key' system config
     */
    public function getInvoiceKeyRegex() : string;

    /**
     * Set the 'invoice_regex_invoice_key' system config
     */
    public function setInvoiceKeyRegex(string $invoiceKeyRegex): self;
    /**
     * Get the 'invoice_regex_invoice_date' system config
     */
    public function getInvoiceDateRegex() : string;

    /**
     * Set the 'invoice_regex_invoice_date' system config
     */
    public function setInvoiceDateRegex(string $invoiceDateRegex): self;
    /**
     * Get the 'invoice_regex_invoice_link' system config
     */
    public function getInvoiceLinkRegex() : string;

    /**
     * Set the 'invoice_regex_invoice_link' system config
     */
    public function setInvoiceLinkRegex(string $invoiceLinkRegex): self;

    /**
     * Get the 'invoice_regex_invoice_content' system config
     */
    public function getInvoiceContentRegex() : string;

    /**
     * Set the 'invoice_date_format' system config
     * @param string $invoiceContentRegex
     */
    public function setInvoiceDateFormat(string $invoiceDateFormat): self;

    /**
     * Get the 'invoice_date_format' system config
     */
    public function getInvoiceDateFormat() : string;

    /**
     * Set the 'invoice_regex_invoice_content' system config
     */
    public function setInvoiceContentRegex(string $invoiceContentRegex): self;

    /**
     * Get the 'invoice_cleanup_xml' system config
     */
    public function getInvoiceCleanupXml() : bool;

    /**
     * Set the 'invoice_cleanup_xml' system config
     */
    public function setInvoiceCleanupXml(bool $cleanupXml): self;
    public function getPreventEmailSend() : bool;
    public function setPreventEmailSend(bool $preventEmailSend) : self;

    public function getPluginInventoryReservations() : bool;
    public function setPluginInventoryReservations(bool $pluginInventoryReservations) : self;

    public function getPluginInventory() : bool;
    public function setPluginInventoryCatalogDelete(bool $pluginInventory) : self;
    public function getPluginInventoryCatalogDelete() : bool;
    public function setPluginInventoryCatalogUpdate(bool $pluginInventory) : self;
    public function getPluginInventoryCatalogUpdate() : bool;
    public function setPluginInventory(bool $pluginInventory) : self;
    public function getEventShipment() : bool;
    public function setEventShipment(bool $eventShipment) : self;
    public function getEventOrder() : bool;
    public function setEventOrder(bool $eventOrder) : self;

    public function getAutoAssocCustomerGroup() : bool;
    public function setAutoAssocCustomerGroup(bool $autoAssocCustomerGroup) : self;

    /**
     * Get the 'customer_group_id_cpf' system config
     */
    public function getCustomerGroupCpf() : string;

    /**
     * Set the 'invoice_regex_invoice_date' system config
     */
    public function setCustomerGroupCpf(string $customerGroup): self;

    /**
     * Get the 'customer_group_id_cnpj' system config
     */
    public function getCustomerGroupCnpj() : string;

    /**
     * Set the 'customer_group_id_cnpj' system config
     */
    public function setCustomerGroupCnpj(string $customerGroup): self;
    

    public function setMultistockEnabled(bool $flag) : self;

    public function getMultistockEnabled() : bool;

    
    public function setMultistockRelation(array $flag) : self;

    public function getMultistockRelation() : ?array;
}
