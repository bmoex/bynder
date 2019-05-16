<?php

namespace BeechIt\Bynder\Traits;

use BeechIt\Bynder\Resource;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait AssetFactory
 * @package BeechIt\Bynder\Traits
 */
trait AssetFactory
{

    /**
     * @return Resource\AssetFactory
     */
    protected function getAssetFactory(): Resource\AssetFactory
    {
        return GeneralUtility::makeInstance(Resource\AssetFactory::class);
    }

    /**
     * @param string $identifier
     * @return Resource\Asset
     */
    protected function getAsset($identifier): Resource\Asset
    {
        return $this->getAssetFactory()->getOrCreate($identifier);
    }
}
