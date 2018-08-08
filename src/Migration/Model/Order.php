<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreRepository;

/**
 * Class Order
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class Order extends AbstractImport
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
                "customer_id",
                "quote_id",
                "currency",
                "shipping_address",
                "billing_address",
            ]
        );
        $this->optionsResolver->setDefined(
            [
                'imported_payment',
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
        $data = $this->getBunches();
        if (count($data)) {
            $objectManager = ObjectManager::getInstance();
            $quoteFactory = $objectManager->get(QuoteFactory::class);
            $customerRepository = $objectManager->get(CustomerRepository::class);
            $storeRepository = $objectManager->get(StoreRepository::class);
            foreach ($data as $row) {
                $quote = $quoteFactory->create();
                if (isset($row['quote_id'])) {
                    $quote = $quote->load($row['quote_id']);
                }
                $row['store'] = $this->getStoreIdFromCode($row['store']);
                $store = $storeRepository->getById($row['store']);
                $quote->setStore($store); 
                $customer = $customerRepository->getById($row['customer_id']);
                $quote->setCurrency();
                
                //Set Address to quote
                $quote->removeAllAddresses();
                $quote->assignCustomerWithAddressChange($customer);

                // Collect Rates and Set Shipping & Payment Method
                $shippingAddress = $quote->getShippingAddress();
                $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod('freeshipping_freeshipping');
                $quote->assignCustomer($customer);
                $quote->setPaymentMethod('checkmo');
                $quote->setInventoryProcessed(false);
                $quote->save();
                
                // Collect Totals & Save Quote
                $quote->collectTotals()->save();
   
                $quote->save();
            }
        }
    }
}
