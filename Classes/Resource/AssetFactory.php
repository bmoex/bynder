<?php

namespace BeechIt\Bynder\Resource;

use BeechIt\Bynder\Exception\InvalidAssetException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory: Assets
 * @package BeechIt\Bynder\Resource
 */
class AssetFactory implements SingletonInterface
{
    /**
     * @var array [<identifier> => Asset]
     */
    protected static $instances = [];

    /**
     * @param Asset $asset
     * @return bool
     */
    public static function attach(Asset $asset): bool
    {
        if (static::has($asset->getIdentifier())) {
            return false;
        }

        static::$instances[$asset->getIdentifier()] = $asset;
        return true;
    }

    /**
     * @param $identifier
     * @return mixed
     */
    public static function has($identifier): bool
    {
        return isset(static::$instances[$identifier]);
    }

    /**
     * @param string $identifier
     * @return Asset
     * @throws InvalidAssetException
     */
    public static function create(string $identifier): Asset
    {
        return GeneralUtility::makeInstance(Asset::class, $identifier);
    }

    /**
     * @param string $identifier
     * @return Asset
     * @throws InvalidAssetException
     */
    public function get(string $identifier): Asset
    {
        if (!static::$instances[$identifier]) {
            throw new InvalidAssetException('No asset found', 1558432065393);
        }
        return static::$instances[$identifier];
    }

    /**
     * @param string $identifier
     * @return Asset
     * @throws InvalidAssetException
     */
    public function getOrCreate(string $identifier): Asset
    {
        if (!static::has($identifier)) {
            $asset = static::create($identifier);
            static::attach($asset);
        }
        return static::$instances[$identifier];
    }
}
