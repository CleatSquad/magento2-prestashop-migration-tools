<?php

namespace Mimlab\PrestashopMigrationTool\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Mimlab\PrestashopMigrationTool\Model\Customer;
use Mimlab\PrestashopMigrationTool\Model\CustomerFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCustomerCommand
 *
 * @package Mimlab\PrestashopMigrationTool\Console\Command
 */
class ImportCustomerCommand extends ImportCommand
{
    /**
     * Type of migration
     */
    const TYPE_IMPORT = 'customer';

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * ImportCustomerCommand constructor.
     *
     * @param CustomerFactory $customerFactory
     * @param ObjectManagerInterface $objectManager
     * @param Emulation $emulation
     * @param State $state
     * @param null $name
     */
    public function __construct(
        CustomerFactory $customerFactory,
        ObjectManagerInterface $objectManager,
        Emulation $emulation,
        State $state,
        $name = null
    ) {
        $this->customerFactory = $customerFactory;
        parent::__construct($objectManager, $emulation, $state, $name);
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool 
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Customer $customer */
        $customer = $this->customerFactory->create();
        if ($dirInputPath = $input->getOption(parent::INPUT_KEY_FLOW_DIR)) {
            $customer->setFlowDir($dirInputPath);
        }
        $customer->execute(self::TYPE_IMPORT, $output);
    }
}
