<?php

namespace Mimlab\PrestashopMigrationTool\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Mimlab\PrestashopMigrationTool\Model\Categories;
use Mimlab\PrestashopMigrationTool\Model\CategoriesFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCategoriesCommand
 *
 * @package Mimlab\PrestashopMigrationTool\Console\Command
 */
class ImportCategoriesCommand extends ImportCommand
{
    /**
     * Type of migration
     */
    const TYPE_IMPORT = 'catalog_categories';

    /**
     * @var CategoriesFactory
     */
    private $categoriesFactory;

    /**
     * ImportCategoriesCommand constructor.
     *
     * @param CategoriesFactory $categoriesFactory
     * @param ObjectManagerInterface $objectManager
     * @param Emulation $emulation
     * @param State $state
     * @param null $name
     */
    public function __construct(
        CategoriesFactory $categoriesFactory,
        ObjectManagerInterface $objectManager,
        Emulation $emulation,
        State $state,
        $name = null
    ) {
        $this->categoriesFactory = $categoriesFactory;
        parent::__construct($objectManager, $emulation, $state, $name);
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
        /** @var Categories $categories */
        $categories = $this->categoriesFactory->create();
        if ($dirInputPath = $input->getOption(self::INPUT_KEY_FLOW_DIR)) {
            $categories->setFlowDir($dirInputPath);
        }
        $categories->execute(self::TYPE_IMPORT, $output);
    }
}
