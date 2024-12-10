<?php
declare(strict_types=1);
namespace Gubee\Integration\Plugin\Sales\Order;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;

class GubeeAttributes 
{
    /**
     * @var OrderExtensionFactory
     */
    private $extensionFactory;
    /**
     * @var \Gubee\Integration\Api\OrderRepositoryInterface
     */
    private $gubeeOrderRepository;

    /**
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(
        OrderExtensionFactory $extensionFactory, 
        \Gubee\Integration\Api\OrderRepositoryInterface $gubeeOrderRepository
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->gubeeOrderRepository = $gubeeOrderRepository;
    }

    /**
     * Loads product entity extension attributes
     *
     * @param ProductInterface $entity
     * @param ProductExtensionInterface|null $extension
     * @return ProductExtensionInterface
     */
    public function afterGetExtensionAttributes(
        OrderInterface $entity,
        OrderExtensionInterface $extension = null
    ) {
        if ($extension === null) {
            $extension = $this->extensionFactory->create();
        }
        if (!$entity->getPayment()) {
            return $extension;
        }
        if ($entity->getPayment()->getMethod() != "gubee") {
            return $extension;
        }
        /**
         * @var \Gubee\Integration\Model\Order
         */
        $gubeeOrder = $this->gubeeOrderRepository->getByOrderId($entity->getId());

        $extension->setData('gubee_sales_channel', $gubeeOrder->getGubeeChannel());
        $extension->setData('gubee_marketplace_code', $gubeeOrder->getGubeeMarketplace());
        $extension->setData('gubee_account', $gubeeOrder->getGubeeAccountId());
        $extension->setData('gubee_is_fullfilement', $gubeeOrder->getFulfillment());
        return $extension;
    }

}
