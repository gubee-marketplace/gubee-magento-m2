<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Gubee;

use Gubee\SDK\Api\ServiceProviderInterface;
use Magento\Framework\ObjectManager\ObjectManager;

class ServiceProvider extends ObjectManager implements ServiceProviderInterface
{
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
