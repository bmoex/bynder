<?php

namespace BeechIt\Bynder\Traits;

use BeechIt\Bynder\Exception\InvalidArgumentException;
use BeechIt\Bynder\Resource\BynderDriver;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;

/**
 * Trait BynderStorage
 * @package BeechIt\Bynder\Traits
 */
trait BynderStorage
{

    /**
     * @var ResourceStorage
     */
    protected $bynderStorage;

    /**
     * @return ResourceStorageInterface
     */
    protected function getBynderStorage(): ResourceStorage
    {
        if ($this->bynderStorage === null) {
            $backendUserAuthentication = $GLOBALS['BE_USER'];
            foreach ($backendUserAuthentication->getFileStorages() as $fileStorage) {
                if ($fileStorage->getDriverType() === BynderDriver::KEY) {
                    return $this->bynderStorage = $fileStorage;
                }
            }
            throw new InvalidArgumentException('Missing Bynder file storage', 1559128872210);
        }
        return $this->bynderStorage;
    }
}
