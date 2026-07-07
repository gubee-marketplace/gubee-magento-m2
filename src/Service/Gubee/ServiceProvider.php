<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Gubee;

use Gubee\SDK\Api\ServiceProviderInterface;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\ObjectManager\FactoryInterface;
use Magento\Framework\ObjectManager\ConfigInterface;

class ServiceProvider extends ObjectManager implements ServiceProviderInterface
{
    public function __construct(FactoryInterface $factory, ConfigInterface $config, &$sharedInstances = [])
    {
        parent::__construct($factory, $config, $sharedInstances);
        $factory->setObjectManager($this);
        $this->_sharedInstances[ServiceProviderInterface::class] = $this;
        $this->_sharedInstances[self::class] = $this;
    }
    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has($id)
    {
        return parent::get($id);
    }
}
