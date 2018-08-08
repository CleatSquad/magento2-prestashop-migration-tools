<?php

namespace Mimlab\PrestashopMigrationTool\Model;

/**
 * Interface ImportInterface
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
interface ImportInterface
{
    /**
     * Resolver configuration
     */
    public function configureOptions();

    /**
     * Validate data
     *
     * @param array $data
     * @param int $index
     *
     * @return array
     */
    public function validateData($data, $index);

    /**
     * Launch the save Data Process
     */
    public function saveData();
}
