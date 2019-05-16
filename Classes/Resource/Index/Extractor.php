<?php

namespace BeechIt\Bynder\Resource\Index;

/*
 * This source file is proprietary property of Beech.it
 * Date: 20-2-18
 * All code (c) Beech.it all rights reserved
 */

use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Traits\AssetFactory;
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extractor
 */
class Extractor implements Resource\Index\ExtractorInterface
{
    use AssetFactory;

    /**
     * @return array
     */
    public function getFileTypeRestrictions(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getDriverRestrictions(): array
    {
        return [BynderDriver::KEY];
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 10;
    }

    /**
     * @return int
     */
    public function getExecutionPriority(): int
    {
        return 10;
    }

    /**
     * @param Resource\File $file
     * @return bool
     */
    public function canProcess(Resource\File $file): bool
    {
        return GeneralUtility::isFirstPartOfStr($file->getMimeType(), 'bynder/');
    }

    /**
     * Extract metadata of Bynder assets
     *
     * @param Resource\File $file
     * @param array $previousExtractedData
     * @return array
     */
    public function extractMetaData(Resource\File $file, array $previousExtractedData = []): array
    {
        $asset = $this->getAsset($file->getIdentifier());
        $expectedData = [
            'title',
            'description',
            'copyright',
            'keywords',
        ];
        if ($asset->isImage()) {
            ArrayUtility::mergeRecursiveWithOverrule($expectedData, [
                'height',
                'width'
            ]);
        }
        return $asset->extractProperties($expectedData);
    }
}
