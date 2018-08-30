<?php

namespace Mimlab\PrestashopMigrationTool\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Mimlab\PrestashopMigrationTool\Model\Stores;
use Mimlab\PrestashopMigrationTool\Model\StoresFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportStoresCommand
 *
 * @package Mimlab\PrestashopMigrationTool\Console\Command
 */
class ImportStoresCommand extends ImportCommand
{
    /**
     * Type of migration
     */
    const TYPE_IMPORT = 'stores_view';

    /**
     * @var StoresFactory
     */
    private $storesFactory;

    /**
     * ImportCategoriesCommand constructor.
     *
     * @param StoresFactory $storesFactory
     * @param ObjectManagerInterface $objectManager
     * @param null $name
     */
    public function __construct(
        StoresFactory $storesFactory,
        ObjectManagerInterface $objectManager,
        $name = null
    ) {
        $this->storesFactory = $storesFactory;
        parent::__construct($objectManager, $name);
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Stores $stores */
        $stores = $this->storesFactory->create();
        if ($dirInputPath = $input->getOption(parent::INPUT_KEY_FLOW_DIR)) {
            $stores->setFlowDir($dirInputPath);
        }
        $stores->execute(self::TYPE_IMPORT, $output);
    }
}
