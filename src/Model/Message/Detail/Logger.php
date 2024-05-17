<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Message\Detail;

use Exception;
use Gubee\Integration\Model\Message\DetailFactory;
use Magento\Framework\Registry;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function array_merge;
use function implode;
use function php_sapi_name;

class Logger implements LoggerInterface
{
    protected DetailFactory $detailFactory;
    protected Registry $registry;
    protected LoggerInterface $logger;

    public function __construct(
        DetailFactory $detailFactory,
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->detailFactory = $detailFactory;
        $this->registry      = $registry;
        $this->logger        = $logger;
    }

    /**
     * System is unusable.
     *
     * @param string  $message
     * @param array<mixed, mixed> $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        return $this->log('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string  $message
     * @param array<mixed, mixed> $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        return $this->log('alert', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string  $message
     * @param array<mixed, mixed> $context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        return $this->log('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string  $message
     * @param array<mixed, mixed> $context
     * @return void
     */
    public function error($message, array $context = [])
    {
        return $this->log('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string  $message
     * @param array<mixed, mixed> $context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        return $this->log('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string  $message
     * @param array<mixed, mixed> $context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        return $this->log('notice', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string  $message
     * @param array<mixed, mixed> $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        return $this->log('info', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string  $message
     * @param array<mixed, mixed> $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        return $this->log('debug', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed   $level
     * @param string  $message
     * @param array<mixed, mixed> $context
     * @return void
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        switch ($level) {
            case 'emergency':
                $level = 0;
                break;
            case 'alert':
                $level = 1;
                break;
            case 'critical':
                $level = 2;
                break;
            case 'error':
                $level = 3;
                break;
            case 'warning':
                $level = 4;
                break;
            case 'notice':
                $level = 5;
                break;
            case 'info':
                $level = 6;
                break;
            case 'debug':
            default:
                $level = 7;
        }

        $detail  = $this->detailFactory->create();
        $context = array_merge(
            [
                'uri'        => $_SERVER['REQUEST_URI'] ?? (isset($_SERVER['argv']) ? implode(' ', $_SERVER['argv']) : ''),
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? (isset($_SERVER['argv']) ? 'cli' : ''),
                'user_agent' => php_sapi_name() == 'cli' ? 'cli' : $_SERVER['HTTP_USER_AGENT'],
                'referer'    => $_SERVER['HTTP_REFERER'] ?? 'cli',
                'method'     => $_SERVER['REQUEST_METHOD'] ?? (isset($_SERVER['argv']) ? 'cli' : ''),
            ],
            $context
        );
        $detail->setLevel($level)
            ->setMessage(
                (string) $message
            )->setContext($context);
        if ($queueMessage = $this->registry->registry('gubee_current_message')) {
            $detail->setMessageId($queueMessage->getMessageId());
        }
        try {
            $detail->save();
        } catch (Exception $e) {
            if (php_sapi_name() == 'cli') {
                echo $e->getMessage();
            }
        }
    }
}
