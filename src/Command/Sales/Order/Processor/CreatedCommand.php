<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order\Processor;

use Exception;
use Gubee\Integration\Api\OrderRepositoryInterface as GubeeOrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\AbstractProcessorCommand;
use Gubee\Integration\Model\InvoiceFactory;
use Gubee\Integration\Model\Order;
use Gubee\Integration\Service\Model\Catalog\Product\Variation;
use Gubee\SDK\Resource\Sales\OrderResource;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\Region;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Service\OrderService;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Throwable;

class CreatedCommand extends AbstractProcessorCommand
{
    protected ProductRepositoryInterface $productRepository;
    protected QuoteManagement $quoteManagement;
    protected CartManagementInterface $cartManagement;
    protected Context $context;
    protected StoreManagerInterface $storeManager;
    protected Product $product;
    protected FormKey $formkey;
    protected QuoteFactory $quoteFactory;
    protected CustomerFactory $customerFactory;
    protected CustomerRepositoryInterface $customerRepository;
    protected OrderService $orderService;
    protected InvoiceFactory $invoiceFactory;
    protected ConvertOrder $convertOrder;

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        CollectionFactory $orderCollectionFactory,
        OrderResource $orderResource,
        QuoteManagement $quoteManagement,
        ProductRepositoryInterface $productRepository,
        Context $context,
        StoreManagerInterface $storeManager,
        Product $product,
        FormKey $formkey,
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        QuoteFactory $quoteFactory,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderService $orderService,
        InvoiceFactory $invoiceFactory,
        GubeeOrderRepositoryInterface $gubeeOrderRepository,
        HistoryFactory $historyFactory,
        OrderManagementInterface $orderManagement,
        ConvertOrder $convertOrder
    )
    {
        parent::__construct(
            $eventDispatcher,
            $logger,
            $orderResource,
            $orderCollectionFactory,
            $orderRepository,
            $gubeeOrderRepository,
            $historyFactory,
            $orderManagement,
            "created"
        );
        $this->convertOrder = $convertOrder;
        $this->invoiceFactory = $invoiceFactory;
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->product = $product;
        $this->formkey = $formkey;
        $this->orderRepository = $orderRepository;
        $this->quoteFactory = $quoteFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->productRepository = $productRepository;
        $this->quoteManagement = $quoteManagement;
        $this->cartManagement = $cartManagement;
    }

    protected function doExecute(): int
    {
        $orderId = $this->input->getArgument('order_id');
        $order = $this->getOrder($orderId);
        if ($order != null) {
            $this->logger->info(
                __("Order with increment ID '%1' found", $orderId)
            );
            return 0;
        }

        $this->logger->error(
                __(
                    "Order with increment ID '%1' not found, creating it",
                    $orderId
                )->__toString()
        );

        try {
            $this->logger->info(
                __("Creating order with increment ID '%1'", $orderId)->__toString()
            );
            $this->create($orderId);
            $this->logger->info(
                __("Order with increment ID '%1' created", $orderId)
            );
            return 0;
        } catch (Throwable $e) {
            $this->logger->error(
                __(
                    "Error creating order with increment ID '%1', Error: %2",
                    $orderId,
                    $e->getMessage()
                )
            );
            throw $e;
        }
    }

    public function create(string $incrementId): bool
    {
        $gubeeOrder = $this->orderResource->loadByOrderId($incrementId);
        $customer = $this->prepareCustomer($gubeeOrder);
        $quote = $this->prepareQuote($gubeeOrder, $customer);
        $order = $this->persistOrder($quote, $customer, $gubeeOrder);
        if (isset($gubeeOrder['invoices']) && count($gubeeOrder['invoices']) > 0) {
            $this->logger->debug(
                __("Creating invoices for order '%1'", $order->getIncrementId())
            );
            $this->createInvoices($order, $gubeeOrder);
            $this->logger->debug(
                __("Invoices for order '%1' created", $order->getIncrementId())
            );
        }
        if (isset($gubeeOrder['shipments']) && count($gubeeOrder['shipments']) > 0) {
            $this->logger->debug(
                __("Creating shipments for order '%1'", $order->getIncrementId())
            );
            $this->createShipments($order, $gubeeOrder);
            $this->logger->debug(
                __("Shipments for order '%1' created", $order->getIncrementId())
            );
        }
        $this->logger->info(
            __("Order '%1' created", $order->getIncrementId())
        );

        return true;
    }

    protected function createShipments(
        $order,
        array $gubeeOrder
    )
    {
        $arrayInput = ObjectManager::getInstance()->create(
            ArrayInput::class,
            [
                'parameters' => [
                    'order_id' => $order->getIncrementId(),
                ],
            ]
        );
        $shipmentCommand = ObjectManager::getInstance()->create(
            ShippedCommand::class
        );

        return $shipmentCommand->run($arrayInput, $this->getOutput());
    }

    protected function createInvoices(
        $order,
        array $gubeeOrder
    )
    {
        $arrayInput = ObjectManager::getInstance()->create(
            ArrayInput::class,
            [
                'parameters' => [
                    'order_id' => $order->getIncrementId(),
                ],
            ]
        );
        $invoiceCommand = ObjectManager::getInstance()->create(
            InvoicedCommand::class
        );

        return $invoiceCommand->run($arrayInput, $this->getOutput());
    }

    protected function persistOrder(
        CartInterface $quote,
        CustomerInterface $customer,
        array $gubeeOrder
    )
    {
        $this->logger->debug(
            __("Persisting order for customer '%1'", $customer->getEmail())
        );
        $this->logger->debug(
            __("Shipping address: %1", json_encode($gubeeOrder['shippingAddress']))
        );
        $shippingAddress = $this->createAddress(
            new DataObject($gubeeOrder['shippingAddress']),
            $gubeeOrder['customer'],
            $customer->getId()
        );
        $this->logger->debug(
            __("Billing address: %1", json_encode($gubeeOrder['billingAddress']))
        );
        $billingAddress = $this->createAddress(
            new DataObject($gubeeOrder['billingAddress']),
            $gubeeOrder['customer'],
            $customer->getId()
        );
        $quote->setData('totalNet', $gubeeOrder['totalNet']);

        /**
         * Set flag to not collect and recalculate totals
         */
        $quote->setCustomer($customer);
        $this->logger->debug(
            __("Setting customer '%1' to quote", $customer->getEmail())
        );
        $quote->setCustomerIsGuest(false);
        $quote->setCustomerEmail($customer->getEmail());
        $quote->setCustomerFirstname($customer->getFirstname());
        $quote->setCustomerLastname($customer->getLastname());
        $quote->setCustomerGroupId($customer->getGroupId());
        $quote->setStoreId($this->storeManager->getDefaultStoreView()->getId());
        $quote->setIsActive(true);
        $quote->getBillingAddress()->addData($billingAddress);
        $quote->getShippingAddress()->addData($shippingAddress);
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod(
                'gubee_gubee'
            );
        $this->logger->debug(
            __("Setting shipping method '%1' to quote", 'gubee_gubee')
        );
        $quote->setPaymentMethod('gubee');
        $this->logger->debug(
            __("Setting payment method '%1' to quote", 'gubee')
        );
        $quote->setInventoryProcessed(true);
        $quote->save();

        $paymentData = [
            'method' => 'gubee',
        ];
        $this->logger->debug(
            __("Setting payment data '%1' to quote", json_encode($paymentData))
        );
        foreach ($gubeeOrder['payments'] as $payment) {
            $paymentData['additional_data']['payment'][] = $payment;
        }

        $quote->getPayment()->importData(
            $paymentData
        );

        $quote->getPayment()->setAdditionalInformation(
            'payment',
            json_encode($paymentData)
        );

        $this->logger->debug(
            __("Importing payment data '%1' to quote", json_encode($paymentData))
        );
        $shippingAmount = $gubeeOrder['totalFreight'];
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals()->save();
        $order = $this->cartManagement->submit($quote);
        // $order = $this->orderRepository->get($orderId);
        $order->setShippingAmount($shippingAmount);
        $order->setBaseShippingAmount($shippingAmount);
        $order->setEmailSent(0);
        $order->setBaseGrandTotal(
            $gubeeOrder['totalNet']
        );
        $order->setGrandTotal(
            $gubeeOrder['totalNet']
        );
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $item->setStoreId($order->getStoreId())->save();
        }
        try {
            $order->save();
            $gubeeOrderItem = ObjectManager::getInstance()->create(
                Order::class
            );
            $gubeeOrderItem
                ->setOrderId($order->getId())
                ->setGubeeOrderId($gubeeOrder['id'])
                ->setGubeeMarketplace(
                    $gubeeOrder['plataform']
                );

            $this->gubeeOrderRepository->save($gubeeOrderItem);
            $this->logger->debug(
                __("Order for customer '%1' persisted", $customer->getEmail())
            );
            $this->addOrderHistory(
                __("Order created by Gubee Integration")->__toString(),
                (int) $order->getId()
            );
            $this->logger->debug(
                __("Order history for order '%1' created", $order->getIncrementId())
            );
            return $order;
        } catch (Throwable $e) {
            $this->logger->error(
                __("Error persisting order for customer '%1'", $customer->getEmail())
            );
            throw $e;
        }
    }

    public function prepareCustomer(array $gubeeOrder): CustomerInterface
    {
        $this->logger->debug(
            __("Preparing customer for order '%1'", $gubeeOrder['id'])
        );
        try {
            $customer = $this->customerRepository->get(
                $gubeeOrder['customer']['email'],
                $this->storeManager->getWebsite()->getId()
            );
            $this->logger->debug(
                __("Customer '%1' found", $gubeeOrder['customer']['email'])
            );
        } catch (Exception $e) {
            $customer = $this->customerFactory->create();
            $this->logger->debug(
                __("Customer '%1' not found, creating it", $gubeeOrder['customer']['email'])
            );
        }
        if (!$customer->getId()) {
            $this->logger->debug(
                __("Customer '%1' not found, creating it", $gubeeOrder['customer']['email'])
            );
            $name = explode(' ', $gubeeOrder['customer']['name']);
            $firstname = $name[0];
            if (count($name) > 1) {
                // remove first name
                unset($name[0]);
                // implode the rest of the names
                $name = array_map(
                    function ($name) {
                        return is_string($name) ? trim($name) : '';
                    },
                    $name
                );
                $lastname = implode(' ', $name);
            } else {
                $lastname = $firstname;
            }
            $customer->setEmail($gubeeOrder['customer']['email']);
            $customer->setTaxvat(
                $gubeeOrder['customer']['documents'][0]['number']
            );
            $customer->setFirstname($firstname);
            $customer->setLastname($lastname);
            $customer->setTaxvat($gubeeOrder['customer']['documents'][0]['number']);
            $customer->setPassword(
                hash('sha256', $gubeeOrder['customer']['email'] . microtime())
            );
            $customer->save();
            $customer = $this->customerRepository->get(
                $gubeeOrder['customer']['email'],
                $this->storeManager->getWebsite()->getId()
            );
        }

        return $customer;
    }

    protected function prepareQuote(
        array $gubeeOrder,
        CustomerInterface $customer
    )
    {
        $quote = $this->quoteFactory->create();
        $quote->assignCustomer($customer)
            ->setCurrency();
        $this->addItemsToQuote($gubeeOrder, $quote);
        return $quote;
    }

    protected function addItemsToQuote(array $gubeeOrder, CartInterface $quote)
    {
        $this->logger->debug(
            __("Adding items to quote for order '%1'", $gubeeOrder['id'])
        );

        foreach ($gubeeOrder['items'] as $item) {
            // echo '<pre><hr/>';
            // print_r($item);
            $this->logger->debug(
                __("Adding item '%1' to quote", $item['skuId'])
            );
            try {
                if (!isset($item['salePrice'])) {
                    throw new Exception(
                        __("Item sale price not informed")
                    );
                }
                $fullPrice = $item['salePrice'];
                if (isset($item['subItems'])) {
                    foreach ($item['subItems'] as $toBeAdded) {
                        if (!isset($toBeAdded['percentageOfTotal'])) {
                            $toBeAdded['percentageOfTotal'] = 100;
                        }
                        $productToBeAdd = $this->getProductByGubeeSku(
                            $toBeAdded['skuId']
                        );
                        // the price of this item is the percentage of the total, divided by the quantity of the subitem
                        $price = ($fullPrice / $toBeAdded['qty']) * $toBeAdded['percentageOfTotal'] / 100;

                        $this->logger->debug(
                            __("Adding product '%1' to quote", $toBeAdded['skuId'])
                        );
                        try {
                            $quoteItem = $quote->addProduct(
                                $productToBeAdd,
                                $toBeAdded['qty'] * $item['qty']
                            );
                            $quoteItem->setCustomPrice(
                                $price
                            );
                            $quoteItem->setOriginalCustomPrice(
                                $price
                            );
                            $quoteItem->setAdditionalData(
                                json_encode($item)
                            );
                            $quoteItem->setStoreId($quote->getStoreId());
                        } catch (Throwable $e) {
                            $message = __(
                                "Error adding product with SKU '%1' to quote, error: " . (string) $e->getMessage(),
                                $toBeAdded['skuId'],
                            );
                            $this->logger->error($message);
                            throw new LocalizedException(
                                $message,
                                $e
                            );
                        }
                    }
                } else {
                    try {
                        $productToBeAdd = $this->getProductByGubeeSku(
                            $item['skuId']
                        );
                        $price = $fullPrice;
                        // echo"<hr>", "Price: ",  $price, "<hr>";
                        $quoteItem = $quote->addProduct(
                            $productToBeAdd,
                            $item['qty']
                        );
                        $quoteItem->setCustomPrice(
                            $price
                        );
                        $quoteItem->setOriginalCustomPrice($price);
                        $quoteItem->setAdditionalData(
                            json_encode($item)
                        );
                        $quoteItem->setStoreId($quote->getStoreId());
                    } catch (Throwable $e) {
                        $message = __(
                            "Error adding product with SKU '%1' to quote, error: " . (string) $e->getMessage(),
                            $item['skuId'],
                        );
                        $this->logger->error($message);
                        throw new LocalizedException(
                            $message,
                            $e
                        );
                    }
                }
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage());
                throw $e;
            }
        }

        // exit;

        $this->logger->debug(
            __("Items added to quote for order '%1'", $gubeeOrder['id'])
        );
    }

    /**
     * validar o campo “subitem“, quando este existe deverá aplicar as formulas:
     * Para obter o valor do item unitário fazer: (order.item.salePrice / (item.subItem.qty * item.qty) ) * (item.subItem.percentageOfTotal / 100)
     * Quando não houver subitem o valor de cada item será:
     * item.salePrice / item.qty
     *
     * @param mixed $item
     * @return float
     */
    public function getItemPrice($item, $qty = 1)
    {
        $price = 0;
        if (isset($item['subItems'])) {
            foreach ($item['subItems'] as $subItem) {
                if (!isset($subItem['percentageOfTotal'])) {
                    $subItem['percentageOfTotal'] = 100;
                }
                $price += (($item['salePrice'] / $subItem['qty']) * ($item['qty'] * $subItem['qty'])) * ($subItem['percentageOfTotal'] / 100);
            }
        } elseif (!isset($item['salePrice'])) {
            throw new Exception(
                __("Item sale price not informed")->__toString()
            );
        } else {
            $price = $item['salePrice'] / $item['qty'];
        }
        return $price / $qty;
    }

    protected function getProductByGubeeSku(
        string $sku
    ): ProductInterface
    {
        $this->logger->debug(
            __("Getting product with SKU '%1'", $sku)
        );
        if (strpos($sku, Variation::SEPARATOR) !== false) {
            $sku = explode(Variation::SEPARATOR, $sku);
            $sku = end($sku);
        }
        try {
            $this->logger->debug(
                __("Loading product with SKU '%1'", $sku)
            );
            $product = $this->productRepository->get($sku);
            if (!$product->getId()) {
                throw new NoSuchEntityException(
                    __("Product with SKU '%1' not found on Magento", $sku)
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
        $this->logger->debug(
            __("Product with SKU '%1' found", $sku)
        );
        return $product;
    }

    public function createAddress($address, $customer, $mageCustomerId)
    {
        $this->logger->debug(
            __("Creating address for customer '%1'", $customer['email'])
        );

        $regionCollection = ObjectManager::getInstance()->create(
            Region::class
        )->getCollection();

        $regionCollection->addFieldToFilter(
            'default_name',
            $address->getRegion()
        );

        if ($regionCollection->getSize() === 0) {
            $region = ObjectManager::getInstance()->create(
                Region::class
            )->loadByCode(
                    $address->getRegion(),
                    'BR'
                );
        } else {
            $region = $regionCollection->getFirstItem();
        }
        $customer = new DataObject($customer);

        $country = ObjectManager::getInstance()->create(Country::class)
            ->loadByCode(
                'BR'
            );
        $phone = $customer->getData('phones/0');
        $phone = sprintf(
            "(%s) %s",
            $phone['ddd'] ?? "11",
            $phone['number'] ?? "999999999"
        );
        $name = explode(' ', $customer->getName());
        $firstName = $name[0];
        if (count($name) > 1) {
            // remove first name
            unset($name[0]);
            // implode the rest of the names
            $name = array_map(
                function ($name) {
                    return is_string($name) ? trim($name) : '';
                },
                $name
            );
            $secondName = implode(' ', $name);
        } else {
            $secondName = $firstName;
        }

        if (
            class_exists(
                    \Pagarme\Pagarme\Observer\CustomerAddressSaveBefore::class
            )
        ) {
            $street = [
                is_string($address->getStreet()) && !empty(trim($address->getStreet())) ? trim($address->getStreet()) : __("Street not informed"),
                is_string($address->getNumber()) && !empty(trim($address->getNumber())) ? trim($address->getNumber()) : __("Number not informed"),
                is_string($address->getNeighborhood()) && !empty(trim($address->getNeighborhood())) ? trim($address->getNeighborhood()): __("Neighborhood not informed"),
                is_string($address->getComplement()) && !empty(trim($address->getComplement())) ? trim($address->getComplement()) : __("Complement not informed"),
            ];
        } else {
            $street = [
                is_string($address->getStreet()) && !empty(trim($address->getStreet())) ? trim($address->getStreet()) : __("Street not informed"),
                is_string($address->getNumber()) && !empty(trim($address->getNumber())) ? trim($address->getNumber()) : __("Number not informed"),
            ];
        }

        $address = [
            'firstname' => $firstName,
            'lastname' => $secondName,
            'email' => $customer->getEmail(),
            'street' => $street,
            'city' => $address->getCity(),
            'country_id' => $country->getId(),
            'region' => $region->getDefaultName(),
            'region_id' => $region->getId(),
            'postcode' => $address->getData('postCode'),
            'telephone' => $phone,
            'save_in_address_book' => 1,
            'customer_id' => $mageCustomerId,
        ];

        $this->logger->debug(
            __("Address for customer '%1' created", $customer['email'])
        );
        return $address;
    }

    public function getPriority(): int
    {
        return 999;
    }
}
