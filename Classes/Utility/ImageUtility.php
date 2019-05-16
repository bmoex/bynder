<?php

namespace BeechIt\Bynder\Utility;

/**
 * Utility: Image
 * @package BeechIt\Bynder\Utility
 */
class ImageUtility
{

    /**
     * Calculate dimension based on image ratio or cropped variant
     *
     * For instance you have the original width, height and new width.
     * And want to calculate the new height with the same ratio as the original dimensions
     *
     * @param integer $originalWidth
     * @param integer $originalHeight
     * @param integer|string $width
     * @param integer|string $height
     * @return array [width, height]
     */
    public static function calculateDimensions($originalWidth, $originalHeight, $width, $height): array
    {
        $keepRatio = true;
        $crop = false;

        // When width and height are set and non of them have a 'm' suffix we don't keep existing ratio
        if ($width && $height && strpos($width . $height, 'm') === false) {
            $keepRatio = false;
        }

        // When width and height are set and one of then have a 'c' suffix we don't keep existing ratio and allow cropping
        if ($width && $height && strpos($width . $height, 'c') !== false) {
            $keepRatio = false;
            $crop = true;
        }

        $width = (int)$width;
        $height = (int)$height;

        if (!$keepRatio && $width > $originalWidth) {
            $height = static::calculateRelativeDimension($width, $height, $originalWidth);
            $width = $originalWidth;
        } elseif (!$keepRatio && $height > $originalHeight) {
            $width = static::calculateRelativeDimension($height, $width, $originalHeight);
            $height = $originalHeight;
        } elseif ($keepRatio && $width > $originalWidth) {
            $height = (int)floor($originalWidth / $width * $height);
        } elseif ($keepRatio && $height > $originalHeight) {
            $height = (int)floor($originalHeight / $height * $width);
        } elseif ($width === 0 && $height > 0) {
            $height = static::calculateRelativeDimension($originalWidth, $originalHeight, $width);
        } elseif ($width === 0 && $height > 0) {
            $width = static::calculateRelativeDimension($originalHeight, $originalWidth, $height);
        }

        return ($crop === true)
            ? [$width . 'c', $height . 'c']
            : [$width, $height];
    }

    /**
     * Calculate relative dimension based on width/height and target dimension
     *
     * @param int $orgA
     * @param int $orgB
     * @param int $newA
     * @return int
     */
    protected static function calculateRelativeDimension(int $orgA, int $orgB, int $newA): int
    {
        return ($newA === 0) ? $orgB : (int)($orgB / ($orgA / $newA));
    }
}
