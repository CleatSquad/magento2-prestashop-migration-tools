<?php

namespace Mimlab\PrestashopMigrationTool\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Mimlab\PrestashopMigrationTool\Model\ProductChild;
use Mimlab\PrestashopMigrationTool\Model\ProductChildFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportChildProduct
 *
 * @package Mimlab\PrestashopMigrationTool\Console\Command
 */
class ImportChildProduct extends ImportCommand
{
    /**
     * Type of migration
     */
    const TYPE_IMPORT = 'catalog_products_child';

    /**
     * @var ProductChildFactory
     */
    private $productChildFactory;

    /**
     * ImportChildProduct constructor.
     *
     * @param ProductChildFactory $productChildFactory
     * @param ObjectManagerInterface $objectManager
     * @param Emulation $emulation
     * @param State $state
     * @param null $name
     */
    public function __construct(
        ProductChildFactory $productChildFactory,
        ObjectManagerInterface $objectManager,
        Emulation $emulation,
        State $state,
        $name = null
    ) {
        $this->productChildFactory = $productChildFactory;
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
        /** @var ProductChild $product */
        $product = $this->productChildFactory->create();
        $product->execute(self::TYPE_IMPORT, $output);
    }
}
