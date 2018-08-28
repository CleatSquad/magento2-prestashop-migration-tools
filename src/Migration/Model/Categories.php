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
     * @var int
     */
    private $urlKeyId = 1;

    /**
     * @var array
     */
    private $categories = array();

    /**
     * @var array
     */
    private $parentsCategories = array();

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
        if (!$code || $code == "" || $code == "NULL") {
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
        if (!$code || $code == "" || $code == "NULL") {
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
     */
    protected function prepareData(&$row)
    {
        if (!isset($row['meta_title']) || $row['meta_title'] = '') {
            $row['meta_title'] = $row['name'];
        }
        if (!isset($row['level'])) {
            $row['level'] = count(explode('/'.$row['path'])) - 1;
        }
    }

    /**
     * Save categorie
     */
    public function saveData()
    {
        $prestaCats = [];
        $data = $this->getLines();
        if (count($data)) {
            $objectManager = ObjectManager::getInstance();
            $categoryFactory = $objectManager->get(CategoryFactory::class);
            $categoryRepository = $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
            foreach ($data as $row) {
                $this->urlKeyId = 1;
                $row['store'] = $this->getStoreIdFromCode($row['store']);
                $row['presta_id'] = $row['category_id'];
                $row['url_key'] = $row['presta_id'] . '-' . $row['url_key'];
                unset($row['category_id']);
                $this->emulation->startEnvironmentEmulation($row['store']);
                $category = $categoryFactory->create();
                if (isset($row['presta_id']) && isset($this->categories[$row['presta_id']])) {
                    try {
                        $category = $categoryRepository->get($this->categories[$row['presta_id']], $row['store']);
                    } catch (\Exception $e) {
                        
                    }
                }
                if (!$category->getId()) {
                    $this->prepareData($row);
                    $category->setIsActive($row['is_active']);
                    $category->setStoreId($row['store']);
                    $category->setLevel($row['level']);
                    $category->setCustomAttributes($row);
                    if (isset($row['parent']) && $prestaCats[$row['parent']]) {
                        $parent = $prestaCats[$row['parent']];
                    }  else {
                        $parent = 2;
                    }
                    $parentCategory = $categoryRepository->get($parent, 0);
                    $category->setPath($parentCategory->getPath());
                    $category->setParentId($parent);
                }
                $category->setMetaTitle($row['meta_title']);
                $category->setMetaKeywords($row['meta_keywords']);
                $category->setMetaDescription($row['meta_description']);
                $category->setName($row['name']);
                $category->setDescription($row['description']);
                $category->setPrestaId($row['presta_id']);
                $this->saveCategoryWithUrlKey($category, $row['url_key'], $row['url_key']);
                if ($category && $category->getId()) {
                    $this->categories[$row['presta_id']] = $category->getId();
                    $this->parentsCategories[$row['presta_id']] = $row['parent_id'];
                    if ($row['is_root_category'] == 1) {
                        $groupId = $this->storeManager->getStore($row['store'])->getStoreGroupId();
                        $this->storeManager->getGroup($groupId)->setRootCategoryId($category->getId());
                    }
                }
                $this->emulation->stopEnvironmentEmulation();
            }
            foreach($this->categories as $prestaCatId => $cat) {
                $magentoCategory = $categoryRepository->get($cat, 0);
                if (isset($this->parentsCategories[$prestaCatId]) && isset($this->categories[$this->parentsCategories[$prestaCatId]])) {
                    $parentInMagento = $this->categories[$this->parentsCategories[$prestaCatId]];
                    $magentoCategory->setParentId($parentInMagento);
                }
                $parentCategory = $categoryRepository->get($magentoCategory->getParentId(), 0);
                $magentoCategory->setPath($parentCategory->getPath() . '/' . $magentoCategory->getId());
                $magentoCategory->save();
            }
        }
    }
    
    /**
     * Save the category with defined urlkey
     *
     * @param Category $category
     * @param string $urlKey
     * @param string $oldUrlKey
     */
    private function saveCategoryWithUrlKey(&$category, $urlKey, $oldUrlKey)
    {
        $objectManager = ObjectManager::getInstance();
        $categoryRepository = $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class); 
        try {
            $category->setRequestPath($urlKey);
            $category->setUrlKey($urlKey);
            $category = $categoryRepository->save($category);
        } catch (\Exception $e) {
            if ($e->getMessage() == 'Could not save category: URL key for specified store already exists.') {
                $urlKey = sprintf('%s-%d', $oldUrlKey, $this->urlKeyId);
                $this->urlKeyId++;
                $prestaId = $category->getData('presta_id');
                if (!isset($this->categories[$prestaId])) {
                    $category->unsetData('entity_id');
                }
                $this->saveCategoryWithUrlKey($category, $urlKey, $oldUrlKey);
            }
        }
    }
}
