<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Customer\Model\AddressFactory;
use Magento\Framework\App\ObjectManager;

/**
 * Class Customer
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class CustomerAddress extends AbstractImport
{

    /**
     * Resolver configuration
     */
    public function configureOptions()
    {
        $this->optionsResolver->setRequired(
            [
                'address_id',
                'customer_id',
                'fisrtname',
                'lastname',
                'street1',
                'postcode',
                'country_id',
                'city',
                'telephone',
            ]
        );
        $this->optionsResolver->setDefined(
            [
                'street2',
                'company',
                'state'
            ]
        );
        $this->optionsResolver->setDefaults(
            [
                'is_default_billing' => 0,
                'is_default_shipping' => 0
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
            $addressFactory = $objectManager->get(AddressFactory::class);
            foreach ($data as $row) {
                $address = $addressFactory->create();
                $address->setData($row);
                $address->setFirstname($row['fisrtname']);
                $address->setCustomerId($row['customer_id']);
                $address->setStreet(sprintf("%s %s", $row['street1'], $row['street2']));
                $address->save();
            }
        }
    }
}
