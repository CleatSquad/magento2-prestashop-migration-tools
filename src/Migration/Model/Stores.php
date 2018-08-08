<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Store\Model\StoreFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Config\Model\ResourceModel\Config;

/**
 * Class Stores
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class Stores extends AbstractImport
{
    /**
     * Resolver configuration
     */
    public function configureOptions()
    {
        $this->optionsResolver->setRequired(
            [
                'name',
                'code',
                'locale'
            ]
        );
        $this->optionsResolver->setDefined(
            [
                'store_id'
                
            ]
        );
        $this->optionsResolver->setDefaults(
            [
                'is_active' => 1,
            ]
        );
    }

    /**
     * Save store
     */
    public function saveData()
    {
        $data = $this->getBunches();
        if (count($data)) {
            $objectManager = ObjectManager::getInstance();
            $storeFactory = $objectManager->get(StoreFactory::class);
            $resourceConfig = $objectManager->get(Config::class);
            foreach ($data as $row) {
                $store = $storeFactory->create();
                if (isset($row['store_id'])) {
                    $store = $store->load($row['store_id']);
                }
                $store->setId($row['store_id']);
                $store->setName($row['name']);
                $store->setCode($row['code']);
                $store->setWebsiteId(1);
                $store->setGroupId(1);
                $store->setIsActive($row['is_active']);
                $store->save();
                $storeLang = str_replace('-', '_', $row['locale']);
                $resourceConfig->saveConfig(
                    Custom::XML_PATH_GENERAL_LOCALE_CODE,
                    $storeLang,
                    ScopeInterface::SCOPE_STORES,
                    $store->getId()
                );
            }
        }
    }
}
