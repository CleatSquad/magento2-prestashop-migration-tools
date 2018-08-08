<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreRepository;

/**
 * Class Categories
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class Categories extends AbstractImport
{
    /**
     * @var array
     */
    private $categories = array();

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
                'name',
                'description',
                'url_key'
            ]
        );
        $this->optionsResolver->setDefined(
            [
                'category_id',
                'path',
                'level',
                'meta_title',
                'meta_keywords',
                'meta_description',
                
            ]
        );
        $this->optionsResolver->setDefaults(
            [
                'include_in_menu' => 1,
                'parent_id' => 0,
                'position' => 0,
                'is_anchor' => 1,
                'is_active' => 1,
                'display_mode' => 'PRODUCTS',
                'page_layout' => '1column',
                'meta_keywords' => '',
                'meta_description' => '',
                'store' => 0,
                'is_root_category' => 0
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
     * Prepare row
     *
     * @param array $row
     * @return array
     */
    private function prepareData(&$row)
    {
        $row['store'] = $this->getStoreIdFromCode($row['store']);
        if (!isset($row['meta_title'])) {
            $row['meta_title'] = $row['name'];
        }
        $paths = [];
        if (!isset($row['path'])) {
            if (isset($this->categories[$row['parent_id']])) {
                $paths[] = $this->categories[$row['parent_id']];
            }
            $paths[] = $row['category_id'];
            $row['path'] = implode('/', $paths);
            $this->categories[$row['category_id']] = $row['path'];
        }
        if (!isset($row['level'])) {
            $row['level'] = count(explode('/'.$row['path'])) - 1;
        }
        return $row;
    }

    /**
     * Save categorie
     */
    public function saveData()
    {
        $data = $this->getBunches();
        if (count($data)) {
            $objectManager = ObjectManager::getInstance();
            $categoryFactory = $objectManager->get(CategoryFactory::class);
            foreach ($data as $row) {
                $category = $categoryFactory->create();
                if (isset($row['category_id'])) {
                    $category = $category->load($row['category_id']);
                }
                $this->prepareData($row);
                $category->setId($row['category_id']);
                $category->setName($row['name']);
                $category->setIsActive($row['is_active']);
                $category->setUrlKey($row['url_key']);
                $category->setDescription($row['description']);
                $category->setParentId($row['parent_id']);
                $category->setStoreId($row['store']);
                $category->setLevel($row['level']);
                $category->setPosition($row['position']);
                $category->setPath($row['path']);
                $category->setCustomAttributes($row);
                $category->save();
                if ($row['is_root_category'] == 1) {
                    $groupId = $this->storeManager->getStore($row['store'])->getStoreGroupId();
                    $this->storeManager->getGroup($groupId)->setRootCategoryId($category->getId());
                }
            }
        }
    }
}
