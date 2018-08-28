<?php

namespace Mimlab\PrestashopMigrationTool\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Mimlab\PrestashopMigrationTool\Model\OrderItem;
use Mimlab\PrestashopMigrationTool\Model\OrderItemFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportOrderItemsCommand
 *
 * @package Mimlab\PrestashopMigrationTool\Console\Command
 */
class ImportOrderItemsCommand extends ImportCommand
{
    /**
     * Type of migration
     */
    const TYPE_IMPORT = 'order_items';

    /**
     * @var OrderItemFactory
     */
    private $orderItemFactory;

    /**
     * ImportOrderCommand constructor.
     *
     * @param OrderItemFactory $orderItemFactory
     * @param ObjectManagerInterface $objectManager
     * @param Emulation $emulation
     * @param State $state
     * @param null $name
     */
    public function __construct(
        OrderItemFactory $orderItemFactory,
        ObjectManagerInterface $objectManager,
        Emulation $emulation,
        State $state,
        $name = null
    ) {
        $this->orderItemFactory = $orderItemFactory;
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
        /** @var OrderItem $order */
        $order = $this->orderItemFactory->create();
        if ($dirInputPath = $input->getOption(parent::INPUT_KEY_FLOW_DIR)) {
            $order->setFlowDir($dirInputPath);
        }
        $order->execute(self::TYPE_IMPORT, $output);
    }
}
