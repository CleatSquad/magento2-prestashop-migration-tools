<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreRepository;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * Class Customer
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class Customer extends AbstractImport
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
                'customer_id',
                'email',
                'fisrtname',
                'lastname',
                'gender',
                'dob'
            ]
        );
        $this->optionsResolver->setDefined(
            [
                'newsletter',
                'password'
            ]
        );
        $this->optionsResolver->setDefaults(
            [
                'store' => 0,
            ]
        );
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
     * Save product
     */
    public function saveData()
    {
        $data = $this->getLines();
        if (count($data)) {
            $objectManager = ObjectManager::getInstance();
            $customerFactory = $objectManager->get(CustomerFactory::class);
            $subscriberFactory = $objectManager->get(SubscriberFactory::class);
            foreach ($data as $row) {
                $customer = $customerFactory->create();
                if (isset($row['customer_id'])) {
                    $customer = $customer->load($row['customer_id']);
                }
                $row['store'] = $this->getStoreIdFromCode($row['store']);
                $groupId = $this->storeManager->getStore($row['store'])->getStoreGroupId();
                $websiteId = $this->storeManager->getGroup($groupId)->getWebsiteId();
                $customer->setData($row);
                $customer->setId($row['customer_id']);
                $customer->setFirstname($row['fisrtname']);
                $customer->setWebsiteId($websiteId);
                $customer->save();
                if (isset($row['newsletter']) && $row['newsletter'] == 1) {
                    $subscriberFactory->create()->subscribeCustomerById($customer->getId());
                }
            }
        }
    }
}
