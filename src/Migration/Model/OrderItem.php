<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\App\ObjectManager;
use \Magento\Quote\Model\QuoteManagement;
use Magento\Catalog\Model\ProductFactory;

/**
 * Class OrderItem
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class OrderItem extends AbstractImport
{
    /**
     * @var array
     */
    private $stores = array();

    /**
     * Resolver configuration
     */
    public function configureOptions()
    {
        $this->optionsResolver->setRequired(
            [
                "order_id",
                "quote_id",
                "product_id",
                "qty",
                "product_price",
            ]
        );
    }

    /**
     * Save product
     */
    public function saveData()
    {
        $data = $this->getLines();
        if (count($data)) {
            $objectManager = ObjectManager::getInstance();
            $quoteFactory = $objectManager->get(QuoteFactory::class);
            $productFactory = $objectManager->get(ProductFactory::class);
            $quoteManagement = $objectManager->get(QuoteManagement::class);
            foreach ($data as $row) {
                $quote = $quoteFactory->create();
                if (isset($row['quote_id'])) {
                    $quote = $quote->load($row['quote_id']);
                }
                $product = $productFactory->create()->load($row['product_id']);
                $product->setPrice($row['product_price']);
                $product->setFinalPrice($row['product_price']);
                $quote->addProduct(
                    $product,
                    intval($row['qty'])
                );
                // Collect Totals & Save Quote
                $quote->collectTotals()->save();
                $order = $quoteManagement->submit($quote);
            }
        }
    }
}
