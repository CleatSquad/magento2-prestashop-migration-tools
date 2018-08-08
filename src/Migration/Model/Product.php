<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreRepository;
//
/**
 * Class Product
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class Product extends AbstractImport
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
                'sku',
                'name',
                'description',
                'description_short',
                'url_key',
                'status',
                'weight',
                'visibility',
                'price',
            ]
        );
        $this->optionsResolver->setDefined(
            [
                'product_id',
                'tax_class_id',
                'type_id',
                'cost',
                'qty',
                'min_sale_qty',
                'is_in_stock',
                'meta_title',
                'meta_keywords',
                'meta_description',
                'categories',
                'backorders'
            ]
        );
        $this->optionsResolver->setDefaults(
            [
                'store' => 0,
                'attribute_set_id' => $this->getDefaultAttributeSetId()
            ]
        );
        $this->configureAllowedValuesForStoreOptions();
    }

    /**
     * Define allowed value for store
     */
    protected function configureAllowedValuesForStoreOptions()
    {
        $this->optionsResolver->setAllowedValues(
            'store',
            function ($value) {
                return $this->checkCodeOrIdAreCorrect($value);
            }
        );
    }

    /**
     * Check if the code or id are correct
     *
     * @param string|int $code
     *
     * @return bool
     */
    protected function checkCodeOrIdAreCorrect($code)
    {
        if (!$code || $code == "") {
            return true;
        }
        return (bool)$this->getStoreIdFromCode($code);
    }

    /**
     * Retrieve default attribute set id
     *
     * @return int
     */
    public function getDefaultAttributeSetId()
    {
        $objectManager = ObjectManager::getInstance();
        $productFactory = $objectManager->get(ProductFactory::class);
        return $productFactory->create()->getResource()->getEntityType()->getDefaultAttributeSetId();
    }

    /**
     * Get store from code
     *
     * @param string|int $code
     *
     * @return int|null
     */
    protected function getStoreIdFromCode($code)
    {
        if (!$code) {
            $this->stores[$code] = 0;
        }
        if (!isset($this->stores[$code])) {
            $objectManager = ObjectManager::getInstance();
            $storeRepository = $objectManager->get(StoreRepository::class);
            if (is_integer($code)) {
                $store = $storeRepository->getById($code);
            } else {
                $store = $storeRepository->get($code);
            }
            if ($store->getId()) {
                $this->stores[$code] = $store->getId();
            }
        }

        return $this->stores[$code];
    }

    /**
     * Save product
     */
    public function saveData()
    {
        $data = $this->getBunches();
        if (count($data)) {
            $objectManager = ObjectManager::getInstance();
            $productFactory = $objectManager->get(ProductFactory::class);
            foreach ($data as $row) {
                $product = $productFactory->create();
                if (isset($row['product_id'])) {
                    $product = $product->load($row['product_id']);
                }
                if (isset($row['backorders'])) {
                    $row['use_config_backorders'] = 0;
                }
                if (isset($row['qty'])) {
                    $row['use_config_manage_stock'] = 0;
                    $row['manage_stock'] = 1;
                }
                $row['url_key'] = sprintf('%s-%s', $row['product_id'], $row['url_key']);
                $row['store'] = $this->getStoreIdFromCode($row['store']);
                $groupId = $this->storeManager->getStore($row['store'])->getStoreGroupId();
                $websiteId = $this->storeManager->getGroup($groupId)->getWebsiteId();
                $product->setData($row);
                $product->setId($row['product_id']);
                $product->setWebsiteIds([$websiteId]);
                $product->setStockData($row);
                $product->save();
            }
        }
    }
}
