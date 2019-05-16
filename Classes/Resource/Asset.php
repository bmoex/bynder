<?php
declare(strict_types=1);

namespace BeechIt\Bynder\Resource;

use BeechIt\Bynder\Exception\InvalidAssetException;
use BeechIt\Bynder\Exception\InvalidExtensionConfigurationException;
use BeechIt\Bynder\Exception\InvalidPropertyException;
use BeechIt\Bynder\Exception\InvalidThumbnailException;
use BeechIt\Bynder\Traits\BynderService;
use BeechIt\Bynder\Traits\BynderStorage;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Asset
{
    use BynderService;
    use BynderStorage;

    /**
     * Available Types used by Bynder
     */
    const TYPE_VIDEO = 'video';
    const TYPE_IMAGE = 'image';
    const TYPE_DOCUMENT = 'document';
    const TYPE_AUDIO = 'audio';

    /**
     * Used derivatives from Bynder API
     */
    const DERIVATIVES_WEB_IMAGE = 'webimage';
    const DERIVATIVES_MINI = 'mini';
    const DERIVATIVES_THUMBNAIL = 'thul';

    /**
     * @var string
     */
    protected $identifier;

    /**
     * API Data
     * @var array
     */
    protected $information;

    /**
     * Asset constructor.
     * @param string $identifier
     * @param array $properties
     * @throws InvalidAssetException
     */
    public function __construct($identifier)
    {
        if (!static::validateIdentifier($identifier)) {
            throw new InvalidAssetException(
                'Invalid identifier given: ' . $identifier,
                1558014684521
            );
        }

        $this->identifier = $identifier;
    }

    /**
     * Identifier patern should be: 00000000-0000-0000-0000000000000000
     * @return bool
     */
    public static function validateIdentifier(string $identifier): bool
    {
        return (bool)preg_match('/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{16}$/i', $identifier);
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->getInformation()['type'] === self::TYPE_IMAGE;
    }

    /**
     * @return bool
     */
    public function isVideo(): bool
    {
        return $this->getInformation()['type'] === self::TYPE_VIDEO;
    }

    /**
     * @return bool
     */
    public function isAudio(): bool
    {
        return $this->getInformation()['type'] === self::TYPE_AUDIO;
    }

    /**
     * @return bool
     */
    public function isDocument(): bool
    {
        return $this->getInformation()['type'] === self::TYPE_DOCUMENT;
    }

    /**
     * @param string $derivative
     * @return string
     * @throws InvalidThumbnailException
     */
    public function getThumbnail($derivative = self::DERIVATIVES_WEB_IMAGE): ?string
    {
        $information = $this->getInformation();
        $thumbnail = $information['thumbnails'][$derivative] ??
            $information['thumbnails'][self::DERIVATIVES_WEB_IMAGE] ?? null;

        if ($thumbnail === null) {
            throw new InvalidThumbnailException('No thumbnail derivative found for: ' . $derivative, 1558541966818);
        }

        return $thumbnail;
    }

    /**
     * @return array
     */
    public function getStreams(): array
    {
        $streams = [];
        $sources = $this->getInformation()['videoPreviewURLs'] ?? [];
        foreach ($sources as $url) {
            if (GeneralUtility::isValidUrl($url)) {
                $headers = get_headers($url, 1);
                $streams[$url] = $headers['Content-Type'];
            }
        }
        return $streams;
    }

    /**
     * @param string $width
     * @param string $height
     * @return string
     * @throws InvalidExtensionConfigurationException
     * @throws InvalidThumbnailException
     */
    public function getOnTheFlyPublicUrl($width, $height): string
    {
        if (filter_var($this->getInformation()['isPublic'], FILTER_VALIDATE_BOOLEAN)) {
            return ConfigurationUtility::getOnTheFlyBaseUrl() . $this->getIdentifier() . '?' . http_build_query([
                    'w' => (int)$width,
                    'h' => (int)$height,
                    'crop' => (bool)strpos($width . $height, 'c')
                ]);
        }
        return $this->getThumbnail();
    }

    /**
     * @return array
     */
    public function getInformation(): array
    {
        if ($this->information === null) {
            try {
                // Do API call
                $this->information = $this->getBynderService()->getMediaInfo($this->getIdentifier());
            } catch (\Exception $e) {
                $this->information = [];
            }
        }
        return $this->information;
    }

    /**
     * Extracts information about a file from the filesystem
     *
     * @param array $propertiesToExtract array of properties which should be returned, if empty all default keys will be extracted
     * @return array
     */
    public function extractProperties($propertiesToExtract = []): array
    {
        if (empty($propertiesToExtract)) {
            $propertiesToExtract = [
                'size',
                'atime',
                'mtime',
                'ctime',
                'mimetype',
                'name',
                'extension',
                'identifier',
                'identifier_hash',
                'storage',
                'folder_hash'
            ];
        }
        $fileInformation = [];
        foreach ($propertiesToExtract as $property) {
            $fileInformation[$property] = $this->getSpecificProperty($property);
        }
        return $fileInformation;
    }

    /**
     * Extracts a specific FileInformation from the FileSystem
     *
     * @param string $property
     * @return bool|int|string
     */
    public function getSpecificProperty($property)
    {
        $information = $this->getInformation();
        switch ($property) {
            case 'size':
                return $information['fileSize'];
            case 'atime':
                return strtotime($information['dateModified']);
            case 'mtime':
                return strtotime($information['dateModified']);
            case 'ctime':
                return strtotime($information['dateCreated']);
            case 'name':
                return $information['name'] . '.' . $this->getInternalExtensionByType($information['type']);
            case 'mimetype':
                return 'bynder/' . $information['type'];
            case 'identifier':
                return $information['id'];
            case 'extension':
                return $this->getInternalExtensionByType($information['type']);
            case 'identifier_hash':
                return sha1($information['id']);
            case 'storage':
                return $this->getBynderStorage()->getUid();
            case 'folder_hash':
                return sha1('bynder' . $this->getBynderStorage()->getUid());

            // Metadata
            case 'title':
                return $information['name'];
            case 'description':
                return $information['description'];
            case 'width':
                return $information['width'];
            case 'height':
                return $information['height'];
            case 'copyright':
                return $information['copyright'];
            case 'keywords':
                return implode(', ', $information['tags'] ?? []);
            default:
                throw new InvalidPropertyException(sprintf('The information "%s" is not available.', $property), 1519130380);
        }
    }

    /**
     * Save a file to a temporary path and returns that path.
     *
     * @param string $derivative
     * @param bool $absolute
     * @return string|null The temporary path
     * @throws InvalidThumbnailException
     */
    public function getLocalThumbnail($derivative = self::DERIVATIVES_WEB_IMAGE, bool $absolute = true): ?string
    {
        $url = $this->getThumbnail($derivative);
        if (!empty($url)) {
            $temporaryPath = $this->getTemporaryPathForFile($url);
            if (!is_file($temporaryPath)) {
                try {
                    $data = GeneralUtility::getUrl($url, 0, false);
                } catch (\Exception $e) {
                    throw new InvalidThumbnailException(
                        sprintf('Requested url "%s" couldn\'t be found', $url),
                        1558442606611,
                        $e
                    );
                }
                if (!empty($data)) {
                    $result = GeneralUtility::writeFile($temporaryPath, $data);
                    if ($result === false) {
                        throw new InvalidThumbnailException(
                            sprintf('Copying file "%s" to temporary path "%s" failed.', $this->getIdentifier(), $temporaryPath),
                            1558442609629
                        );
                    }
                }
            }

            // Return absolute path instead of relative when configured
            return $absolute ? $temporaryPath : str_replace(PATH_site, '/', $temporaryPath);
        }
        return $temporaryPath ?? null;
    }

    /**
     * Returns a temporary path for a given file, including the file extension.
     *
     * @param string $url
     * @return string
     */
    protected function getTemporaryPathForFile($url): string
    {
        $temporaryPath = PATH_site . 'typo3temp/assets/' . BynderDriver::KEY . '/';
        if (!is_dir($temporaryPath)) {
            GeneralUtility::mkdir_deep($temporaryPath);
        }
        $info = pathinfo($url);
        return $temporaryPath . $info['filename'] . '.' . $info['extension'];
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getInternalExtensionByType($type): string
    {
        $extension = 'bynder';
        switch ($type) {
            case static::TYPE_IMAGE:
                return $extension . '.jpg';
            case static::TYPE_DOCUMENT:
                return $extension . '.pdf';
            case static::TYPE_VIDEO:
                return $extension . '.mp4';
            case static::TYPE_AUDIO:
                return $extension . '.mp3';
            default:
                return $extension;
        }
    }
}
