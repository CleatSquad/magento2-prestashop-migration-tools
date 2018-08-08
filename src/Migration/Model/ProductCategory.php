<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ObjectManager;

/**
 * Class ProductCategory
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class ProductCategory extends AbstractImport
{
    /**
     * @var array
     */
    private $products = [];

    /**
     * Resolver configuration
     */
    public function configureOptions()
    {
        $this->optionsResolver->setRequired(
            [
                'product_id',
                'category_id'
            ]
        );
        $this->configureAllowedValuesInProductOptions();
    }

    /**
     * Define allowed value for store
     */
    protected function configureAllowedValuesInProductOptions()
    {
        $this->optionsResolver->setAllowedValues(
            'product_id',
            function ($value) {
                return $this->getProductById($value);
            }
        );
    }

    /**
     * Get Product by id
     */
    protected function getProductById($id)
    {
        if (!isset($this->products[$id])) {
            $objectManager = ObjectManager::getInstance();
            $productFactory = $objectManager->get(ProductFactory::class);
            if ($product = $productFactory->create()->load($id)) {
                return $this->products[$id] = $product;
            } else {
                return 0;
            }
        }

        return $this->products[$id];
    }

    /**
     * Save product category
     */
    public function saveData()
    {
        $data = $this->getBunches();
        if (count($data)) {
            $categories = [];
            foreach ($data as $row) {
                $product = $this->getProductById($row['product_id']);
                if ($product) {
                    $categories = (array)$product->getCategoryIds();
                    array_push($categories, $row['category_id']);
                    $product->setCategoryIds($categories);
                    $product->save();
                }
               
            }
        }
    }
}
