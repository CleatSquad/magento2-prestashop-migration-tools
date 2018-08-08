<?php

namespace Mimlab\PrestashopMigrationTool\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;

/**
 * Class ProductMedia
 *
 * @package Mimlab\PrestashopMigrationTool\Model
 */
class ProductMedia extends AbstractImport
{
    /**
     * Temporary media folder
     */
    const DIR_TMP_PATH = 'tmp';

    /**
     * @var array
     */
    private $products = [];

    /**
     * Resolver configuration
     */
    public function configureOptions()
    {
        $this->optionsResolver->setRequired(
            [
                'product_id',
                'image'
            ]
        );
        $this->optionsResolver->setDefined(
            [
                'cover',
            ]
        );
        $this->configureAllowedValuesInProductOptions();
    }

    /**
     * Define allowed value for store
     */
    protected function configureAllowedValuesInProductOptions()
    {
        $this->optionsResolver->setAllowedValues(
            'product_id',
            function ($value) {
                return $this->getProductById($value);
            }
        );
    }

    /**
     * Get Product by id
     */
    protected function getProductById($id)
    {
        if (!isset($this->products[$id])) {
            $objectManager = ObjectManager::getInstance();
            $productFactory = $objectManager->get(ProductFactory::class);
            if ($product = $productFactory->create()->load($id)) {
                return $this->products[$id] = $product;
            } else {
                return 0;
            }
        }

        return $this->products[$id];
    }

    /**
     * Save product media
     */
    public function saveData()
    {
        $this->createMediaDirTmpDir();
        $tmpDir = $this->getMediaDirTmpDir();
        $data = $this->getBunches();
        if (count($data)) {
            foreach ($data as $row) {
                $product = $this->getProductById($row['product_id']);
                if ($product) {
                    $result = $tmpDir.basename($row['image']);
                    $this->filesystemDriver->copy($row['image'], $result);
                    if ($row['cover'] == 1) {
                        $mediaAttribute = ['image' ,'thumbnail', 'small_image'];
                        $exclude = true;
                    } else {
                        $mediaAttribute = '';
                        $exclude = false;
                    }
                    $product->addImageToMediaGallery($result, $mediaAttribute, true, $exclude);
                    $product->save();
                }
            }
        }
        $this->removeMediaDirTmpDir();
    }

    /**
     * Get Media directory name for the temporary file storage
     * pub/media/tmp
     *
     * @return string
     */
    protected function getMediaDirTmpDir()
    {
        return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath(self::DIR_TMP_PATH) . DIRECTORY_SEPARATOR;
    }

    /**
     * Create Media directory
     */
    protected function createMediaDirTmpDir()
    {
        $tmpDir = $this->getMediaDirTmpDir();
        $this->filesystemDriver->createDirectory($tmpDir);
    }

    /**
     * Remove Media directory
     */
    protected function removeMediaDirTmpDir()
    {
        $tmpDir = $this->getMediaDirTmpDir();
        $this->filesystemDriver->deleteDirectory($tmpDir);
    }
}
