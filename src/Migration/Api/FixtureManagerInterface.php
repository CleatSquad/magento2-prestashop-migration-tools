<?php

namespace Mimlab\PrestashopMigrationTool\Api;

use Magento\Framework\Exception\NotFoundException;

/**
 * Interface FixtureManagerInterface
 *
 * @package Mimlab\PrestashopMigrationTool\Api
 */
interface FixtureManagerInterface
{
    /**
     * Install fixture from a file
     *
     * @param $fileName
     * @param callable $callback
     *
     * @throws NotFoundException
     * @throws \Exception
     */
    public function iterate($fileName, callable $callback);
}
