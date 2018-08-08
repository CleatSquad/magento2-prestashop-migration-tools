<?php

namespace Mimlab\PrestashopMigrationTool\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Mimlab\PrestashopMigrationTool\Model\ProductMedia;
use Mimlab\PrestashopMigrationTool\Model\ProductMediaFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProductMedia
 *
 * @package Mimlab\PrestashopMigrationTool\Console\Command
 */
class ImportProductMedia extends ImportCommand
{
    /**
     * Type of migration
     */
    const TYPE_IMPORT = 'catalog_products_medias';

    /**
     * @var ProductMediaFactory
     */
    private $productMediaFactory;

    /**
     * ImportProductMedia constructor.
     *
     * @param ProductMediaFactory $productMediaFactory
     * @param ObjectManagerInterface $objectManager
     * @param Emulation $emulation
     * @param State $state
     * @param null $name
     */
    public function __construct(
        ProductMediaFactory $productMediaFactory,
        ObjectManagerInterface $objectManager,
        Emulation $emulation,
        State $state,
        $name = null
    ) {
        $this->productMediaFactory = $productMediaFactory;
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
        /** @var ProductMedia $product */
        $product = $this->productMediaFactory->create();
        $product->execute(self::TYPE_IMPORT, $output);
    }
}
