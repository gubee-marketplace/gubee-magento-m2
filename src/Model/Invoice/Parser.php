<?php
declare(strict_types=1);

namespace Gubee\Integration\Model\Invoice;

use Exception;

class Parser 
{
    const INVOICE_KEY = 'key';

    const INVOICE_LINK = 'danfeLink';

    const INVOICE_SERIES = 'line';

    const INVOICE_NUMBER = 'number';

    const INVOICE_DATE = 'issueDate';

    const INVOICE_CONTENT = 'danfeXml';

    const DEFAULT_DATETIME_FORMAT = "Y-m-d\TH:i:s.000\Z";
    
    /**
     * @var \Gubee\Integration\Api\Data\ConfigInterface
     */
    protected $config;

    /**
     * @var \Psr\Log\LoggerInterface 
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $date;


    public function __construct(
        \Gubee\Integration\Api\Data\ConfigInterface $config,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
    )
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->date = $date;
    }

    public function regexToArray() : array 
    {
        return [
            self::INVOICE_KEY => $this->config->getInvoiceKeyRegex(),
            self::INVOICE_LINK => $this->config->getInvoiceLinkRegex(),
            self::INVOICE_SERIES => $this->config->getInvoiceSeriesRegex(),
            self::INVOICE_NUMBER => $this->config->getInvoiceNumberRegex(),
            self::INVOICE_DATE => $this->config->getInvoiceDateRegex(),
            self::INVOICE_CONTENT => $this->config->getInvoiceContentRegex()
        ];
    }


    public function findMatch($content) : array
    {
        $invoiceData = [];
        $lines = explode("\n", $content);
        foreach ($this->regexToArray() as $field => $regex)
        {
            try { 
                $matches = [];
                $lineToCleanup = '';
                foreach ($lines as $line) {
                    if (preg_match($regex."i", $line, $matches) === 1) {
                        $lineToCleanup = $line;
                        break 1;
                    }
                    
                }
                if (count($matches) > 0) {
                    $invoiceData[$field] = trim(str_replace($matches[0], "", $lineToCleanup));
                }
            }
            catch (\Exception $err)
            {
                $this->logger->critical($err->getMessage(), ['exception' => $err]);
            }
        }
        
        
        if (!isset($invoiceData[self::INVOICE_DATE]) || empty($invoiceData[self::INVOICE_DATE])) {
            $invoiceData[self::INVOICE_DATE] = $this->date->date()->format(self::DEFAULT_DATETIME_FORMAT);
        }
        else {
            try {
                $invoiceData[self::INVOICE_DATE] = $this->date->date(\DateTime::createFromFormat($this->config->getInvoiceDateFormat(), $invoiceData[self::INVOICE_DATE]))->format(self::DEFAULT_DATETIME_FORMAT);
            }
            catch (Exception $err)
            {
                $this->logger->critical("Failed to parse date from invoice history.", ['exception' => $err]);
                $invoiceData[self::INVOICE_DATE] = $this->date->date()->format(self::DEFAULT_DATETIME_FORMAT);
            }
        }
        if (isset($invoiceData[self::INVOICE_LINK]) && (!isset($invoiceData[self::INVOICE_CONTENT]) || empty($invoiceData[self::INVOICE_CONTENT]) ))
            $invoiceData[self::INVOICE_CONTENT] = $this->fetchXML($invoiceData[self::INVOICE_LINK]);
        
        return $invoiceData;

    }

    /**
     * Checks if any regex matches in a certain string
     * @param string|\Magento\Framework\Phrase $content
     */
    public function isItInvoice($content)
    {
        if ($content instanceof \Magento\Framework\Phrase) {
            $content = $content->render();
        }
        foreach ($this->regexToArray() as $field => $regex){
            if (preg_match($regex."i", (string) $content, $matches) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Uses file_get_contents to fetch the xml data
     * Using file_get_contents for best compatibility
     * @param string $xmlUrl
     * @return string
     */
    private function fetchXML($xmlUrl)
    {
        try {
            return file_get_contents($xmlUrl, false, stream_context_create(["http"=>["timeout"=>30]]));
        }
        catch (\Exception $err)
        {
            $this->logger->critical('Could not download xml content', ['exception' => $err]);
            return '';
        }
    }

    /**
     * Removes the xml file from a string
     * @param string $content 
     * @return string 
     */
    public function cleanupXml($content)
    {
        $lines = explode("\n", $content);
        $regex = $this->config->getInvoiceContentRegex();
        foreach ($lines as $key => $line)
        {
            if (preg_match($regex."i", $line, $matches) === 1) {
                break;
            }

        }

        if ($key)
        {
            unset($lines[$key]);
        }

        return implode("\n", $lines);
    }

}
