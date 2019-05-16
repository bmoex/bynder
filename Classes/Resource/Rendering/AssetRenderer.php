<?php

namespace BeechIt\Bynder\Resource\Rendering;

use BeechIt\Bynder\Exception\InvalidAssetException;
use BeechIt\Bynder\Exception\InvalidThumbnailException;
use BeechIt\Bynder\Service\TagBuilderService;
use BeechIt\Bynder\Traits\AssetFactory;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use BeechIt\Bynder\Utility\ImageUtility;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class AssetRenderer implements FileRendererInterface
{
    use AssetFactory;

    /**
     * Returns the priority of the renderer
     * This way it is possible to define/overrule a renderer
     * for a specific file type/context.
     *
     * For example create a video renderer for a certain storage/driver type.
     *
     * Should be between 1 and 100, 100 is more important than 1
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 15;
    }

    /**
     * Check if given File(Reference) can be rendered
     *
     * @param FileInterface $file File or FileReference to render
     * @return bool
     */
    public function canRender(FileInterface $file): bool
    {
        try {
            if (GeneralUtility::isFirstPartOfStr($file->getMimeType(), 'bynder/')) {
                $asset = $this->getAsset($file->getIdentifier());
                return $asset->isImage() || $asset->isDocument() || $asset->isAudio() || $asset->isVideo();
            }
        } catch (InvalidAssetException $e) {
        }
        return false;
    }

    /**
     * Render for given File(Reference) HTML output
     *
     * @param FileInterface $file
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     */
    public function render(FileInterface $file, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false): string
    {
        if (!($file instanceof File) && is_callable([$this->file, 'getOriginalFile'])) {
            $originalFile = $file->getOriginalFile();
        } else {
            $originalFile = $file;
        }

        list($width, $height) = ImageUtility::calculateDimensions(
            $originalFile->getProperty('width'),
            $originalFile->getProperty('height'),
            $width,
            $height
        );

        $asset = $this->getAsset($file->getIdentifier());
        switch (true) {
            case $asset->isImage():
            case $asset->isDocument():
                return $this->renderImageTag($file, $width, $height, $options, $usedPathsRelativeToCurrentScript);

            case $asset->isVideo():
                return $this->renderVideoTag($file, $width, $height, $options, $usedPathsRelativeToCurrentScript);

            case $asset->isAudio():
                return $this->renderAudioTag($file, $width, $height, $options, $usedPathsRelativeToCurrentScript);

            default:
                throw new InvalidAssetException('No rendering implemented for this asset.', 1558540658478);
        }
    }

    /**
     * @param FileInterface $file
     * @param int|string $width
     * @param int|string $height
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript
     * @return string
     */
    protected function renderVideoTag(FileInterface $file, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false): string
    {
        $sources = [];
        foreach ($this->getAsset($file->getIdentifier())->getStreams() as $url => $type) {
            $sources[] = '<source src="' . $url . '" type="' . $type . '">';
        }

        if (!empty($sources)) {
            $this->addVideoJSLibraryToPageRenderer();

            // Now render tag based on given content sources
            $tag = $this->getTagBuilder('video', $options);

            $tag->addAttributes([
                'class' => 'video-js',
                'controls' => 'controls',
                'preload' => 'auto',
                'width' => '100%',
                'poster' => $this->getWebPath(
                    $this->getThumbnailUrl($file, $width, $height, $options),
                    $usedPathsRelativeToCurrentScript
                )
            ]);
            $tag->setContent(
                implode(PHP_EOL, $sources)
                . '<p class="vjs-no-js">'
                . LocalizationUtility::translate('javascript_required', ConfigurationUtility::EXTENSION, ['video'])
                . '</p>'
            );
            if ((int)$height > 0) {
                $tag->addAttribute('height', !empty($height) ? $height : null);
            }

            return $tag->render();
        }
        return '<!-- Video #' . $file->getIdentifier() . ' not available for embedding -->';
    }

    /**
     * Render HTML5 <audio> tag with VideoJS capabilities
     *
     * @param FileInterface $file
     * @param int|string $width
     * @param int|string $height
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript
     * @return string
     */
    protected function renderAudioTag(FileInterface $file, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false): string
    {
        $sources = [];
        foreach ($this->getAsset($file->getIdentifier())->getStreams() as $url => $type) {
            $sources[] = '<source src="' . $url . '" type="' . $type . '">';
        }

        if (!empty($sources)) {
            $this->addVideoJSLibraryToPageRenderer();

            // Now render tag based on given content sources
            $tag = $this->getTagBuilder('audio', $options);

            $tag->addAttribute('class', 'video-js');
            $tag->addAttributes([
                'class' => 'video-js',
                'controls' => 'controls',
                'preload' => 'auto'
            ]);
            $tag->setContent(
                implode(PHP_EOL, $sources)
                . '<p class="vjs-no-js">'
                . LocalizationUtility::translate('javascript_required', ConfigurationUtility::EXTENSION, ['audio'])
                . '</p>'
            );
            if ((int)$height > 0) {
                $tag->addAttribute('height', !empty($height) ? $height : null);
            }

            return $tag->render();
        }
        return '<!-- Video #' . $file->getIdentifier() . ' not available for embedding -->';
    }

    /**
     * @param FileInterface $file
     * @param int|string $width
     * @param int|string $height
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript
     * @return string
     */
    protected function renderImageTag(FileInterface $file, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false): string
    {
        $tag = $this->getTagBuilder('img', $options);

        $tag->addAttribute('src', $this->getWebPath(
            $this->getThumbnailUrl($file, $width, $height, $options),
            $usedPathsRelativeToCurrentScript
        ));

        if ((int)$width > 0) {
            $tag->addAttribute('width', !empty($width) ? $width : null);
        }
        if ((int)$height > 0) {
            $tag->addAttribute('height', !empty($height) ? $height : null);
        }

        // The alt-attribute is mandatory to have valid html-code, therefore add it even if it is empty
        if ($tag->hasAttribute('alt') === false) {
            $tag->addAttribute('alt', $file->getProperty('alternative'));
        }
        if ($tag->hasAttribute('title') === false) {
            $tag->addAttribute('title', $file->getProperty('title'));
        }
        return $tag->render();
    }


    /**
     * @param FileInterface $file
     * @param int|string $width
     * @param int|string $height
     * @param array $options
     * @return string
     */
    protected function getThumbnailUrl($file, $width, $height, $options): string
    {
        try {
            // Define all required image processing variables
            $cropVariant = $options['cropVariant'] ?: 'default';
            $cropString = $file instanceof FileReference ? $file->getProperty('crop') : '';
            $cropVariantCollection = CropVariantCollection::create((string)$cropString);
            $cropArea = $cropVariantCollection->getCropArea($cropVariant);
            $processingInstructions = [
                'width' => $width,
                'height' => $height,
                'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($file),
            ];

            // Process and generate/retrieve image from Bynder API
            $imageService = $this->getImageService();
            $processedImage = $imageService->applyProcessingInstructions($file, $processingInstructions);
            $url = $imageService->getImageUri($processedImage);

            // Return if path exists on local storage
            if (is_file(PATH_site . ltrim($url, '/'))) {
                return $url;
            }

            // Return if its an external unmodified file
            if (preg_match('/^(?:http)s?:/', $url)) {
                return $url;
            }
        } catch (InvalidThumbnailException $e) {
            // Never throw exception if not available for some reason.
        }

        return ConfigurationUtility::getUnavailableImage();
    }


    /**
     * Fetches the URL to the the avatar image
     *
     * @param string $url
     * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all.
     * @return string
     */
    protected function getWebPath(string $url, bool $relativeToCurrentScript = false): string
    {
        if ($relativeToCurrentScript && !GeneralUtility::isValidUrl($url)) {
            $url = PathUtility::getAbsoluteWebPath(PATH_site . $url);
        }
        return $url;
    }

    /**
     * Include Video.JS javascript libraries and configuration
     *
     * @return void
     */
    protected function addVideoJSLibraryToPageRenderer(): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:bynder/Resources/Public/Styles/video-js.min.css');
        $pageRenderer->addJsInlineCode('video-js', 'window.VIDEOJS_NO_DYNAMIC_STYLE = true;');
        $pageRenderer->addJsFooterFile('EXT:bynder/Resources/Public/JavaScript/video-js.min.js');
    }


    /**
     * Return an instance of ImageService
     *
     * @return ImageService
     */
    protected function getImageService(): ImageService
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        return $objectManager->get(ImageService::class);
    }

    /**
     * Return an instance of TagBuilderService
     *
     * @param string $type
     * @param array $options
     * @return TagBuilder
     */
    protected function getTagBuilder(string $type, array $options): TagBuilder
    {
        $tagBuilderService = GeneralUtility::makeInstance(TagBuilderService::class);
        $tag = $tagBuilderService->getTagBuilder($type);
        $tagBuilderService->initializeAbstractTagBasedAttributes($tag, $options);
        return $tag;
    }
}
