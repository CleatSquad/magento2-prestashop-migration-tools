<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\FixtureManager as FrameworkFixtureManager;
use Mimlab\PrestashopMigrationTool\Api\FixtureManagerInterface;
use Mimlab\PrestashopMigrationTool\Exception\FileIsEmptyException;

/**
 * Class FixtureManager
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class FixtureManager implements FixtureManagerInterface
{
    /**
     * @var FrameworkFixtureManager
     */
    private $fixtureManager;

    /**
     * @var Csv
     */
    private $csvReader;

    /**
     * FixtureManager constructor.
     *
     * @param FrameworkFixtureManager $fixtureManager
     * @param Csv $csvReader
     */
    public function __construct(
        FrameworkFixtureManager $fixtureManager,
        Csv $csvReader
    ) {
        $this->fixtureManager = $fixtureManager;
        $this->csvReader = $csvReader;
    }

    /**
     * Install fixture from a file
     *
     * @param $fileName
     * @param callable $callback
     *
     * @throws NotFoundException
     * @throws \Exception
     */
    public function iterate($fileName, callable $callback)
    {
        try {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (file_exists($fileName)) {
                $data = $this->csvReader->getData($fileName);
                $header = array_shift($data);
                if (count($data) > 0) {
                    foreach ($data as $index => $row) {
                        if(count($header) != count($row)) {
                            if (count($row) > 0) {
                                $callback([], [], new \Exception(
                                    __('Line non conforme avec le header in line %1', ($index + 1))
                                ));
                            }
                            continue;
                        }
                        $rowData = array_combine($header, $row);
                        // Call the callback function
                        $callback($rowData, $index, false);
                    }
                } else {
                    throw new FileIsEmptyException(
                        __('Failed to read "%1" because file is empty.', $fileName)
                    );
                }
            } else {
                throw new NotFoundException(
                    __('Failed to read "%1" because file does not exist.', $fileName)
                );
            }
        } catch (\Exception $exception) {
            $callback([], [], $exception);
        }
    }
}
