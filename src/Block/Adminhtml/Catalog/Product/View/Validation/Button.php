<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Catalog\Product\View\Validation;

use Gubee\Integration\Command\Catalog\Product\ValidateCommand;
use Gubee\Integration\Service\Model\Catalog\Product;
use Gubee\SDK\Library\HttpClient\Exception\ErrorException;
use Magento\Backend\Block\Widget\Button\Item;
use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\App\Emulation;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

use function json_decode;
use function json_encode;
use function sprintf;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Button extends Container
{
    /** @var \Magento\Catalog\Model\Product */
    protected $_product;
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;
    /**
     * App Emulator
     *
     * @var Emulation
     */
    protected $_emulation;
    protected ObjectManagerInterface $objectManager;
    /**
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Magento\Catalog\Model\Product $product,
        Emulation $emulation,
        ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->objectManager = $objectManager;
        $this->_coreRegistry = $registry;
        $this->_product      = $product;
        $this->_request      = $context->getRequest();
        $this->_emulation    = $emulation;
        parent::__construct($context, $data);
    }

    /**
     * Block constructor adds buttons
     */
    protected function _construct()
    {
        $this->addButton(
            'gubee_validation',
            $this->getButtonData()
        );
        parent::_construct();
    }

    /**
     * Only output if product has gubee flagged
     */
    protected function _toHtml()
    {
        return parent::_toHtml();
    }

    public function canRender(Item $item)
    {
        $product = $this->_coreRegistry->registry('current_product');
        return $product && $product->getGubee() && parent::canRender($item);
    }

    /**
     * Return button attributes array
     */
    public function getButtonData()
    {
        $product = $this->_coreRegistry->registry('current_product');
        if (! $product->getGubee()) {
            return [];
        }
        $problems = $this->validate() ?: [];

        return [
            'label'      => json_encode([
                'errors'  => $problems,
                'product' => $this->getGubeeProduct(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'sort_order' => 800,
            'class'      => 'relative action-secondary',
        ];
    }

    public function getGubeeProduct()
    {
        $product = $this->_coreRegistry->registry('current_product');

        $validateCommand = $this->objectManager->create(
            Product::class,
            [
                'product' => $product,
            ]
        );
        return $validateCommand->getGubeeProduct();
    }

    /**
     * Return product frontend url depends on active store
     *
     * @return mixed
     */
    protected function _getProductUrl()
    {
        return null;
    }

    /**
     * @return mixed
     */
    public function validate()
    {
        return [];
        $product = $this->_coreRegistry->registry('current_product');
        if (! $product) {
            return $problems;
        }
        try {
            $input           = $this->objectManager->create(
                ArrayInput::class,
                [
                    'parameters' => [
                        'sku' => $product->getSku(),
                    ],
                ]
            );
            $output          = $this->objectManager->create(
                BufferedOutput::class
            );
            $validateCommand = $this->objectManager->create(
                ValidateCommand::class
            );
            $validateCommand->run($input, $output);
        } catch (ErrorException $e) {
            $response = $e->getResponse();
            $content  = json_decode((string) $response->getBody(), true);
            if (isset($content['fieldErrors'])) {
                foreach ($content['fieldErrors'] as $error) {
                    $problems[] = sprintf(
                        "%s.%s: %s",
                        $error['objectName'],
                        $error['field'],
                        $error['message']
                    );
                }
            } else {
                $problems[] = $e->getMessage();
            }
        } catch (Throwable $e) {
            throw $e;
        }

        return $problems ?: [];
    }
}
