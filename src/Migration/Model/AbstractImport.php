<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\UrlInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mimlab\PrestashopMigrationTool\Exception\DirectoryIsEmptyException;
use Mimlab\PrestashopMigrationTool\Model\FixtureManager;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract class AbstractImport
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 *
 * @method $this setBunches($bunches)
 * @method array getBunches()
 * @method $this unsetBunches()
 * @method bool hasBunches()
 */
abstract class AbstractImport extends DataObject implements ImportInterface
{
    /**#@+
     * elements used in import
     */
    const DIR_INPUT_PATH = 'flow/input';
    const DIR_ERROR_PATH = 'error';
    const DIR_ARCHIVE_PATH = 'archive';
    const DIR_REPORT_PATH = 'report';
    const CSV_VALIDATION_PATTERN = '.csv';
    const LOCK_FILE_PATTERN = '%s.lock';
    /**#@-*/

    /**
     * @var array
     */
    protected $filesErrors;

    /**
     * @var array
     */
    protected $filesErrorsLinks;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var File
     */
    protected $filesystemDriver;

    /**
     * @var String
     */
    protected $importDirectoryPath;

    /**
     * @var String
     */
    protected $errorDirectoryPath;

    /**
     * @var String
     */
    protected $archiveDirectoryPath;

    /**
     * @var String
     */
    protected $reportDirectoryPath;

    /**
     * @var ProcessingErrorAggregatorInterface
     */
    protected $errorAggregator;

    /**
     * @var OptionsResolver
     */
    protected $optionsResolver;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var String
     */
    protected $httpReportDirectoryPath;

    /**
     * @var sting
     */
    protected $fileLock;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * AbstractImport constructor.
     *
     * @param FixtureManager $fixtureManager
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param File $filesystemDriver
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param OptionsResolver $optionsResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        FixtureManager $fixtureManager,
        LoggerInterface $logger,
        Filesystem $filesystem,
        File $filesystemDriver,
        ProcessingErrorAggregatorInterface $errorAggregator,
        OptionsResolver $optionsResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->fixtureManager = $fixtureManager;
        $this->filesystem = $filesystem;
        $this->filesystemDriver = $filesystemDriver;
        $this->logger = $logger;
        $this->errorAggregator = $errorAggregator->clear();
        $this->optionsResolver = $optionsResolver->clear();
        $this->storeManager = $storeManager;
        $this->filesErrors = [];
        $this->filesErrorsLinks = [];
        $this->configureOptions();
        $this->prepareDirectories();

        parent::__construct([]);
    }

    /**
     * Import all csv from a csvDir
     *
     * @param $name
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    public function execute($name, OutputInterface $output)
    {
        $files = $this->getAllFiles($this->getFileName($name));
        $this->fileLock = sprintf(self::LOCK_FILE_PATTERN, $this->importDirectoryPath.$this->getFileName($name));
        if ($this->filesystemDriver->isExists($this->fileLock)) {
            throw new \Exception(__("There is another import operation in progress"));
        }
        $this->filesystemDriver->touch($this->fileLock);
        foreach ($files as $fileName) {
            $output->writeln("Processing data for {$fileName}...");
            $this->processFile($fileName);
        }
        $this->filesystemDriver->deleteFile($this->fileLock);
        if (count($this->filesErrors) > 0) {
            throw new \Exception(
                __(
                    "%1 file(s) (%2) contains errors, please download the reports below: \r\n%3",
                    count($this->filesErrors),
                    implode(",", $this->filesErrors),
                    implode("\r\n", $this->filesErrorsLinks)
                )
            );
        }
    }

    /**
     * Get the name of CSV file
     *
     * @param string $name
     * @return string
     */
    protected function getFileName($name)
    {
        return $name;
    }

    /**
     * Resolver configuration
     */
    public function configureOptions()
    {
        return;
    }

    /**
     * Validate data
     *
     * @param array $data
     * @param int $index
     *
     * @return array
     */
    public function validateData($data, $index)
    {
        try {
            return $this->optionsResolver->resolve($data);
        } catch (\Exception $e) {
            $this->addErrors($e->getMessage(), $index);
        }
    }

    /**
     * Launch the save Data Process
     */
    abstract public function saveData();

    /**
     * Add a new item to the current import
     *
     * @param $bunch
     */
    protected function addBunch($bunch)
    {
        $bunches = $this->getBunches();
        $bunches[] = $bunch;
        $this->setBunches($bunches);
    }

    /**
     * Initialisation of all directory paths
     */
    private function prepareDirectories()
    {
        // Init Directories
        $this->importDirectoryPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath(self::DIR_INPUT_PATH) . DIRECTORY_SEPARATOR;
        $this->errorDirectoryPath = $this->filesystemDriver->getAbsolutePath(
            $this->importDirectoryPath,
            self::DIR_ERROR_PATH
        );
        $this->archiveDirectoryPath = $this->filesystemDriver->getAbsolutePath(
            $this->importDirectoryPath,
            self::DIR_ARCHIVE_PATH
        );
        $this->reportDirectoryPath = $this->filesystemDriver->getAbsolutePath(
            $this->importDirectoryPath,
            self::DIR_REPORT_PATH
        );
        try {
            $this->httpReportDirectoryPath = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA).
                self::DIR_INPUT_PATH . DIRECTORY_SEPARATOR.
                self::DIR_REPORT_PATH . DIRECTORY_SEPARATOR;
        } catch (NoSuchEntityException $exception) {
            $this->logger->log($exception);
        }

        // Create all directories
        $this->filesystemDriver->createDirectory($this->importDirectoryPath);
        $this->filesystemDriver->createDirectory($this->errorDirectoryPath);
        $this->filesystemDriver->createDirectory($this->archiveDirectoryPath);
        $this->filesystemDriver->createDirectory($this->reportDirectoryPath);
    }

    /**
     * Process filename by validate data & prepare data to import
     *
     * @param string $fileName
     */
    protected function processFile($fileName)
    {
        $this->unsetBunches();
        $this->errorAggregator->clear();
        try {
            $this->fixtureManager->iterate(
                $fileName,
                function ($data, $index) {
                    if ($rowData = $this->validateData($data, $index)) {
                        $this->addBunch($rowData);
                    }
                }
            );
        } catch (\Exception $exception) {
            $this->addErrors($exception->getMessage());
        }

        $this->afterProcess($fileName);
    }

    /**
     * After process filename
     *
     * @param $fileName
     */
    protected function afterProcess($fileName)
    {
        try {
            // Check errors after import process
            if ($this->errorAggregator->getErrorsCount() > 0) {
                // Prepare log error file
                $this->logger->pushHandler($this->initStream($fileName));
                // Loop on all errors and write into log file
                foreach ($this->errorAggregator->getAllErrors() as $error) {
                    if ($error->getRowNumber()) {
                        $errorLine = $error->getRowNumber() + $this->getFirstDataLine();
                        $this->logger->error("{$error->getErrorMessage()} in row {$errorLine}");
                    } else {
                        $this->logger->error($error->getErrorMessage());
                    }
                }
                $this->logger->popHandler($this->initStream($fileName));
                // Rename the file which has error(s)
                $this->filesystemDriver->rename(
                    $fileName,
                    $this->errorDirectoryPath . DIRECTORY_SEPARATOR . basename($fileName)
                );
                array_push($this->filesErrors, basename($fileName));
                array_push(
                    $this->filesErrorsLinks,
                    $this->httpReportDirectoryPath . basename($fileName) . '.log'
                );
            } else {
                // If no error
                $this->saveData();
                $this->filesystemDriver->rename(
                    $fileName,
                    $this->archiveDirectoryPath . DIRECTORY_SEPARATOR . basename($fileName)
                );
            }
        } catch (\Exception $exception) {
            $this->addErrors($exception->getMessage());
            $this->afterProcess($fileName);
        }
    }

    /**
     * Get stream handle for file
     *
     * @param $fileName
     *
     * @return StreamHandler
     */
    protected function initStream($fileName)
    {
        return new StreamHandler($this->reportDirectoryPath . DIRECTORY_SEPARATOR . basename($fileName) . '.log');
    }

    /**
     * Returns the number of the first line wich contains data (the line 1 wich is the header)
     *
     * @return int
     */
    protected function getFirstDataLine()
    {
        return 2;
    }

    /**
     * Get all csv files
     *
     * @param $name
     *
     * @return \string[]
     * @throws DirectoryIsEmptyException
     */
    protected function getAllFiles($name)
    {
        $directory = $this->importDirectoryPath;
        $files = $this->filesystemDriver->search(
            $name . self::CSV_VALIDATION_PATTERN,
            $directory
        );
        if (count($files) > 0) {
            return $files;
        } else {
            throw new DirectoryIsEmptyException(
                __('Repository "%1" is empty.', $directory)
            );
        }
    }

    /**
     * Add errors to error aggregator
     *
     * @param string $code
     * @param int $row
     * @param int $col
     */
    protected function addErrors($code, $row = null, $col = null)
    {
        $this->errorAggregator->addError(
            $code,
            ProcessingError::ERROR_LEVEL_CRITICAL,
            $row,
            $col
        );
    }
}
