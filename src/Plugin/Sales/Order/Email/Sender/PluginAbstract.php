<?php

namespace Gubee\Integration\Plugin\Sales\Order\Email\Sender;

use Gubee\Integration\Api\Data\ConfigInterface;

class PluginAbstract 
{
    protected $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;    
    }
    public function shouldPreventExecution($methodCode) : bool
    {
        return $methodCode == 'gubee' && $this->config->getPreventEmailSend();
    }
}