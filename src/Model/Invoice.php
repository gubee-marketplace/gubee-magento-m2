<?php

declare (strict_types = 1);

namespace Gubee\Integration\Model;

use DateTime;
use DateTimeInterface;
use Gubee\Integration\Api\Data\InvoiceInterface;
use Magento\Framework\Model\AbstractModel;

class Invoice extends AbstractModel implements InvoiceInterface {
    /**
     * @inheritDoc
     */
    public function _construct() {
        $this->_init(\Gubee\Integration\Model\ResourceModel\Invoice::class);
    }

    public function beforeSave() {
        if (!$this->getLine()) {
            throw new \InvalidArgumentException(
                __('The NFE line is required')->__toString()
            );
        }

        if (!$this->getNumber()) {
            throw new \InvalidArgumentException(
                __('The NFE number is required')->__toString()
            );
        }

        if (strlen($this->getKey()) !== 44) {
            throw new \InvalidArgumentException(
                __('The NFE key must be 44 characters long')->__toString()
            );
        }
        if ($this->getIssueDate() && $this->getIssueDate() instanceof DateTimeInterface) {
            parent::setData(
                self::ISSUEDATE,
                $this->getIssueDate()->format(
                    'Y-m-d\TH:i:s.v'
                )
            );
        }

        if (!$this->getIssueDate()) {
            throw new \InvalidArgumentException(
                __('The NFE issue date is required')->__toString()
            );
        }

        return parent::beforeSave();
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceId() {
        return $this->getData(self::INVOICE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setInvoiceId($invoiceId) {
        return $this->setData(self::INVOICE_ID, $invoiceId);
    }

    /**
     * @inheritDoc
     */
    public function getDanfeLink() {
        return $this->getData(self::DANFELINK);
    }

    /**
     * @inheritDoc
     */
    public function setDanfeLink($danfeLink) {
        return $this->setData(self::DANFELINK, $danfeLink);
    }

    /**
     * @inheritDoc
     */
    public function getDanfeXml() {
        return $this->getData(self::DANFEXML);
    }

    /**
     * @inheritDoc
     */
    public function setDanfeXml($danfeXml) {
        return $this->setData(self::DANFEXML, $danfeXml);
    }

    /**
     * @inheritDoc
     */
    public function getIssueDate(): DateTimeInterface {
        if (!$this->getData(self::ISSUEDATE)) {
            return new DateTime();
        }

        if (!$this->getData(self::ISSUEDATE) instanceof DateTimeInterface) {
            return new DateTime($this->getData(self::ISSUEDATE));
        }
        return $this->getData(self::ISSUEDATE);
    }

    /**
     * @inheritDoc
     */
    public function setIssueDate(DateTimeInterface $issueDate) {
        return $this->setData(self::ISSUEDATE, $issueDate);
    }

    /**
     * @inheritDoc
     */
    public function getKey() {
        return $this->getData(self::KEY);
    }

    /**
     * @inheritDoc
     */
    public function setKey($key) {
        return $this->setData(self::KEY, $key);
    }

    /**
     * @inheritDoc
     */
    public function getLine() {
        return $this->getData(self::LINE);
    }

    /**
     * @inheritDoc
     */
    public function setLine($line) {
        return $this->setData(self::LINE, $line);
    }

    /**
     * @inheritDoc
     */
    public function getNumber() {
        return $this->getData(self::NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setNumber($number) {
        return $this->setData(self::NUMBER, $number);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId() {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId) {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function getOrigin() {
        return $this->getData(self::ORIGIN);
    }

    public function setOrigin($origin) {
        return $this->setData(self::ORIGIN, $origin);
    }

    public function jsonSerialize() {
        return [
            'danfeLink' => $this->getDanfeLink(),
            'danfeXml' => $this->getDanfeXml(),
            'issueDate' => $this->getIssueDate()->format('Y-m-d\TH:i:s.v'),
            'key' => $this->getKey(),
            'line' => $this->getLine(),
            'number' => $this->getNumber(),
        ];
    }
}
