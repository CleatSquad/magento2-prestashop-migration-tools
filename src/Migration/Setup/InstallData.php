<?php

namespace Mimlab\PrestashopMigrationTool\Setup;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\{
    ModuleContextInterface,
    ModuleDataSetupInterface,
    InstallDataInterface
};

/**
 * Class InstallData
 *
 * @package Mimlab\PrestashopMigrationTool\Setup
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * InstallData constructor.
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Install data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            Category::ENTITY, 'presta_id', [
                'type' => 'int',
                'label' => 'Prestashop Id',
                'input' => 'input',
                'visible' => true,
                'default' => '0',
                'required' => false,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL
            ]
        );

        $setup->endSetup();
    }
}
