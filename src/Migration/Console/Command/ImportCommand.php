<?php

namespace Mimlab\PrestashopMigrationTool\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Mimlab\PrestashopMigrationTool\Exception\DirectoryIsEmptyException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ImportCommand
 *
 * @package Mimlab\PrestashopMigrationTool\Console\Command
 */
class ImportCommand extends Command
{
    /**
     * Command
     */
    const COMMAND = 'mimlab:flow:import';

    /**
     * input arguments
     */
    const INPUT_KEY_TYPE = 'type';
 
    /**
     * input options
     */
    const INPUT_KEY_FLOW_DIR = 'dir';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Emulation
     */
    protected $emulation;

    /**
     * @var State
     */
    protected $state;

    /**
     * ImportCommand constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Emulation $emulation
     * @param State $state
     * @param type $name
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Emulation $emulation,
        State $state,
        $name = null
    ) {
        $this->objectManager = $objectManager;
        $this->emulation = $emulation;
        $this->state = $state;
        parent::__construct($name);
    }

    /**
     * Configuration of command
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription(_('Migrate the prestashop datas into Magento'))
            ->setDefinition(
                [
                    new InputArgument(
                        self::INPUT_KEY_TYPE,
                        InputArgument::REQUIRED,
                        __(
                            'Type of migration. Available options are "%1","%2","%3","%4" and "%5"',
                            ImportStoresCommand::TYPE_IMPORT,
                            ImportCategoriesCommand::TYPE_IMPORT,
                            ImportProductCommand::TYPE_IMPORT,
                            ImportCustomerCommand::TYPE_IMPORT,
                            ImportOrderCommand::TYPE_IMPORT
                        )
                    ),
                    new InputOption(
                        self::INPUT_KEY_FLOW_DIR,
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Flow directory'
                    )
                ]
            );

        parent::configure();
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
        $importCommand = null;
        $typeArgument = $input->getArgument(self::INPUT_KEY_TYPE);

        /** @see Memory temporary patch **/
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        try {
            $output->writeln("<info>Start Migration Process `{$typeArgument}`</info>");
            switch ($typeArgument) {
                case ImportStoresCommand::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportStoresCommand::class);
                    break;
                case ImportCategoriesCommand::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportCategoriesCommand::class);
                    break;
                case ImportProductCommand::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportProductCommand::class);
                    break;
                case ImportProductCategory::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportProductCategory::class);
                    break;
                case ImportProductMedia::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportProductMedia::class);
                    break;
                case ImportChildProduct::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportChildProduct::class);
                    break;
                case ImportCustomerCommand::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportCustomerCommand::class);
                    break;
                case ImportCustomerAddressCommand::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportCustomerAddressCommand::class);
                    break;
                case ImportOrderCommand::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportOrderCommand::class);
                    break;
                case ImportOrderItemsCommand::TYPE_IMPORT:
                    $importCommand = $this->objectManager->create(ImportOrderItemsCommand::class);
                    break;
                default:
                    throw new InvalidArgumentException("`{$typeArgument}` type does not exists.");
            }
            $area = Area::AREA_ADMINHTML;
            $this->state->emulateAreaCode(
                $area,
                function($importCommand, $input, $output, $area) {
                    $this->emulation->startEnvironmentEmulation(0, $area);
                    $this->beforeExecute($input, $output);
                    $importCommand->execute($input, $output);
                    $this->afterExecute($input, $output);
                    $this->emulation->stopEnvironmentEmulation();
                },
                [$importCommand, $input, $output, $area, $typeArgument]
            );
        } catch (DirectoryIsEmptyException $exception) {
            $output->writeln($exception->getMessage());
        } catch (\Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        } finally {
            $output->writeln("<info>End Migration Process `{$typeArgument}`</info>");
        }
    }

    /**
     * Before Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function beforeExecute(InputInterface $input, OutputInterface $output) 
    {
        $typeArgument = $input->getArgument(self::INPUT_KEY_TYPE);
        $dirInputPath = $input->getOption(self::INPUT_KEY_FLOW_DIR);
        switch ($typeArgument) {
            case ImportCategoriesCommand::TYPE_IMPORT:
                $arrayInput = $this->objectManager->create(
                    \Symfony\Component\Console\Input\ArrayInput::class,
                    [
                        'parameters' => [
                            'command' => self::COMMAND,
                            self::INPUT_KEY_TYPE => ImportStoresCommand::TYPE_IMPORT,
                            '--' . self::INPUT_KEY_FLOW_DIR => $dirInputPath
                        ]
                    ]
                );
                $this->getApplication()->doRun($arrayInput, $output);
                break;
            case ImportProductCommand::TYPE_IMPORT:
                $arrayInput = $this->objectManager->create(
                    \Symfony\Component\Console\Input\ArrayInput::class,
                    [
                        'parameters' => [
                            'command' => self::COMMAND,
                            self::INPUT_KEY_TYPE => ImportCategoriesCommand::TYPE_IMPORT,
                            '--' . self::INPUT_KEY_FLOW_DIR => $dirInputPath
                        ]
                    ]
                );
                $this->getApplication()->doRun($arrayInput, $output);
                break;
        }
    }

    /**
     * After Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function afterExecute(InputInterface $input, OutputInterface $output) 
    {
        $typeArgument = $input->getArgument(self::INPUT_KEY_TYPE);
        $dirInputPath = $input->getOption(self::INPUT_KEY_FLOW_DIR);
        switch ($typeArgument) {
            case ImportProductCommand::TYPE_IMPORT:
                $arrayInput = $this->objectManager->create(
                    \Symfony\Component\Console\Input\ArrayInput::class,
                    [
                        'parameters' => [
                            'command' => self::COMMAND,
                            self::INPUT_KEY_TYPE => ImportProductCategory::TYPE_IMPORT,
                            '--' . self::INPUT_KEY_FLOW_DIR => $dirInputPath
                        ]
                    ]
                );
                $this->getApplication()->doRun($arrayInput, $output);
                $arrayInput = $this->objectManager->create(
                    \Symfony\Component\Console\Input\ArrayInput::class,
                    [
                        'parameters' => [
                            'command' => self::COMMAND,
                            self::INPUT_KEY_TYPE => ImportProductMedia::TYPE_IMPORT,
                            '--' . self::INPUT_KEY_FLOW_DIR => $dirInputPath
                        ]
                    ]
                );
                $this->getApplication()->doRun($arrayInput, $output);
                break;
            case ImportCustomerCommand::TYPE_IMPORT:
                $arrayInput = $this->objectManager->create(
                    \Symfony\Component\Console\Input\ArrayInput::class,
                    [
                        'parameters' => [
                            'command' => self::COMMAND,
                            self::INPUT_KEY_TYPE => ImportCustomerAddressCommand::TYPE_IMPORT,
                            '--' . self::INPUT_KEY_FLOW_DIR => $dirInputPath
                        ]
                    ]
                );
                $this->getApplication()->doRun($arrayInput, $output);
                break;
            case ImportOrderCommand::TYPE_IMPORT:
                $arrayInput = $this->objectManager->create(
                    \Symfony\Component\Console\Input\ArrayInput::class,
                    [
                        'parameters' => [
                            'command' => self::COMMAND,
                            self::INPUT_KEY_TYPE => ImportOrderItemsCommand::TYPE_IMPORT,
                            '--' . self::INPUT_KEY_FLOW_DIR => $dirInputPath
                        ]
                    ]
                );
                $this->getApplication()->doRun($arrayInput, $output);
                break;
        }
    }
}

