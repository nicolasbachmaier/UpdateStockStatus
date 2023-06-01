<?php
namespace Nicolas\UpdateStockStatus\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class Productsaveafter implements ObserverInterface
{
    protected $_configurable;
    protected $_stockRegistry;

    public function __construct(Configurable $configurable, StockRegistryInterface $stockRegistry)
    {
        $this->_configurable = $configurable;
        $this->_stockRegistry = $stockRegistry;
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if ($product->getTypeId() == 'simple') {
            $parentIds = $this->_configurable->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                foreach ($parentIds as $parentId) {
                    $parent = $this->_configurable->getProductByAttributes($parentId);
                    $childProducts = $this->_configurable->getUsedProducts($parent);
                    $isInStock = false;

                    foreach ($childProducts as $child) {
                        $childStockItem = $this->_stockRegistry->getStockItem($child->getId());
                        if ($childStockItem->getIsInStock()) {
                            $isInStock = true;
                            break;
                        }
                    }

                    $parentStockItem = $this->_stockRegistry->getStockItem($parentId);
                    $parentStockItem->setIsInStock($isInStock);
                    $this->_stockRegistry->updateStockItemBySku($parent->getSku(), $parentStockItem);
                }
            }
        }
    }
}
