<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data;

use DateTimeInterface;
use JsonSerializable;

interface InvoiceInterface extends JsonSerializable
{
    public const INVOICE_ID     = 'invoice_id';
    public const NUMBER         = 'number';
    public const DANFEXML       = 'danfeXml';
    public const LINE           = 'line';
    public const KEY            = 'key';
    public const DANFELINK      = 'danfeLink';
    public const ISSUEDATE      = 'issueDate';
    public const ORDER_ID       = 'order_id';
    public const ORIGIN         = 'origin';
    public const ORIGIN_GUBEE   = 1;
    public const ORIGIN_MAGENTO = 0;

    /**
     * Get invoice_id
     *
     * @return string|null
     */
    public function getInvoiceId();

    /**
     * Set invoice_id
     *
     * @param string $invoiceId
     * @return \Gubee\Integration\Invoice\Api\Data\InvoiceInterface
     */
    public function setInvoiceId($invoiceId);

    /**
     * Get danfeLink
     *
     * @return string|null
     */
    public function getDanfeLink();

    /**
     * Set danfeLink
     *
     * @param string $danfeLink
     * @return \Gubee\Integration\Invoice\Api\Data\InvoiceInterface
     */
    public function setDanfeLink($danfeLink);

    /**
     * Get danfeXml
     *
     * @return string|null
     */
    public function getDanfeXml();

    /**
     * Set danfeXml
     *
     * @param string $danfeXml
     * @return \Gubee\Integration\Invoice\Api\Data\InvoiceInterface
     */
    public function setDanfeXml($danfeXml);

    /**
     * Get issueDate
     *
     * @return string|null
     */
    public function getIssueDate();

    /**
     * Set issueDate
     *
     * @return \Gubee\Integration\Invoice\Api\Data\InvoiceInterface
     */
    public function setIssueDate(DateTimeInterface $issueDate);

    /**
     * Get key
     *
     * @return string|null
     */
    public function getKey();

    /**
     * Set key
     *
     * @param string $key
     * @return \Gubee\Integration\Invoice\Api\Data\InvoiceInterface
     */
    public function setKey($key);

    /**
     * Get line
     *
     * @return string|null
     */
    public function getLine();

    /**
     * Set line
     *
     * @param string $line
     * @return \Gubee\Integration\Invoice\Api\Data\InvoiceInterface
     */
    public function setLine($line);

    /**
     * Get number
     *
     * @return string|null
     */
    public function getNumber();

    /**
     * Set number
     *
     * @param string $number
     * @return \Gubee\Integration\Invoice\Api\Data\InvoiceInterface
     */
    public function setNumber($number);

    /**
     * Get order_id
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param string $orderId
     * @return \Gubee\Integration\Invoice\Api\Data\InvoiceInterface
     */
    public function setOrderId($orderId);
}
