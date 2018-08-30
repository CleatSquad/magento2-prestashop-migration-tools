<?php

namespace Mimlab\PrestashopMigrationTool\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Mimlab\PrestashopMigrationTool\Model\Order;
use Mimlab\PrestashopMigrationTool\Model\OrderFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportOrderCommand
 *
 * @package Mimlab\PrestashopMigrationTool\Console\Command
 */
class ImportOrderCommand extends ImportCommand
{
    /**
     * Type of migration
     */
    const TYPE_IMPORT = 'order';

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * ImportOrderCommand constructor.
     *
     * @param OrderFactory $orderFactory
     * @param ObjectManagerInterface $objectManager
     * @param null $name
     */
    public function __construct(
        OrderFactory $orderFactory,
        ObjectManagerInterface $objectManager,
        $name = null
    ) {
        $this->orderFactory = $orderFactory;
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
        /** @var Order $order */
        $order = $this->orderFactory->create();
        if ($dirInputPath = $input->getOption(parent::INPUT_KEY_FLOW_DIR)) {
            $order->setFlowDir($dirInputPath);
        }
        $order->execute(self::TYPE_IMPORT, $output);
    }
}
