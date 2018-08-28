<?php

namespace Mimlab\PrestashopMigrationTool\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Mimlab\PrestashopMigrationTool\Model\ProductCategory;
use Mimlab\PrestashopMigrationTool\Model\ProductCategoryFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportProductCategory
 *
 * @package Mimlab\PrestashopMigrationTool\Console\Command
 */
class ImportProductCategory extends ImportCommand
{
    /**
     * Type of migration
     */
    const TYPE_IMPORT = 'catalog_products_categories';

    /**
     * @var ProductCategoryFactory
     */
    private $productCategoryFactory;

    /**
     * ImportProductCategory constructor.
     *
     * @param ProductCategoryFactory $productCategoryFactory
     * @param ObjectManagerInterface $objectManager
     * @param Emulation $emulation
     * @param State $state
     * @param null $name
     */
    public function __construct(
        ProductCategoryFactory $productCategoryFactory,
        ObjectManagerInterface $objectManager,
        Emulation $emulation,
        State $state,
        $name = null
    ) {
        $this->productCategoryFactory = $productCategoryFactory;
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
        /** @var ProductCategory $product */
        $product = $this->productCategoryFactory->create();
        if ($dirInputPath = $input->getOption(parent::INPUT_KEY_FLOW_DIR)) {
            $product->setFlowDir($dirInputPath);
        }
        $product->execute(self::TYPE_IMPORT, $output);
    }
}
