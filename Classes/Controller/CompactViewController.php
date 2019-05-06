<?php

namespace BeechIt\Bynder\Controller;

/*
 * This source file is proprietary property of Beech.it
 * Date: 20-2-18
 * All code (c) Beech.it all rights reserved
 */

use BeechIt\Bynder\Exception\InvalidExtensionConfigurationException;
use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Traits\BynderStorage;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class CompactViewController
 */
class CompactViewController
{
    use BynderStorage;

    const LANGUAGE_ENGLISH = 'en_US';
    const LANGUAGE_DUTCH = 'nl_NL';
    const LANGUAGE_SPANISH = 'es_ES';

    /**
     * Fluid Standalone View
     *
     * @var StandaloneView
     */
    protected $view;

    /**
     * TemplateRootPath
     *
     * @var string[]
     */
    protected $templateRootPaths = ['EXT:bynder/Resources/Private/Templates/CompactView'];

    /**
     * PartialRootPath
     *
     * @var string[]
     */
    protected $partialRootPaths = ['EXT:bynder/Resources/Private/Partials/CompactView'];

    /**
     * LayoutRootPath
     *
     * @var string[]
     */
    protected $layoutRootPaths = ['EXT:bynder/Resources/Private/Layouts/CompactView'];


    /**
     * CompactViewController constructor.
     */
    public function __construct()
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setPartialRootPaths($this->partialRootPaths);
        $this->view->setTemplateRootPaths($this->templateRootPaths);
        $this->view->setLayoutRootPaths($this->layoutRootPaths);
    }

    /**
     * Action: Display compact view
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws InvalidExtensionConfigurationException
     */
    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->view->setTemplate('Index');
        $parameters = $request->getQueryParams();

        $this->view->assignMultiple([
            'configuration' => [
                'language' => $this->getBackendUserLanguage(),
                'apiBaseUrl' => ConfigurationUtility::getApiBaseUrl(),
            ],
            'parameters' => [
                'element' => $parameters['element'],
                'irreObject' => $parameters['irreObject'],
                'assetTypes' => $parameters['assetTypes']
            ]
        ]);

        $response->getBody()->write($this->view->render());

        return $response;
    }

    /**
     * Action: Retrieve file from storage
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getFilesAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $files = [];
            $storage = $this->getBynderStorage();
            $indexer =$this->getIndexer($storage);

            foreach ($request->getParsedBody()['files'] ?? [] as $fileIdentifier) {
                $file = $storage->getFile($fileIdentifier);
                if ($file instanceof File) {
                    // (Re)Fetch metadata
                    $indexer->extractMetaData($file);
                    $files[] = $file->getUid();
                }
            }

            if ($files === []) {
                return $this->createJsonResponse($response, ['error' => 'No files given/found'], 406);
            }

            return $this->createJsonResponse($response, ['files' => $files], 201);

        } catch (\Exception $e) {
            return $this->createJsonResponse($response, ['error' => 'The interaction with Bynder contained conflicts. Please contact the webmasters.'], 404);
        }
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Gets the Indexer.
     *
     * @param ResourceStorage $storage
     * @return Indexer
     */
    protected function getIndexer(ResourceStorage $storage): Indexer
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }

    /**
     * Safely return possible language keys for CompactView
     * @return string
     */
    protected function getBackendUserLanguage(): string
    {
        $language = trim($this->getBackendUserAuthentication()->uc['lang'] ?: $this->getBackendUserAuthentication()->user['lang']);
        if (empty($language)) {
            return static::LANGUAGE_ENGLISH;
        }

        switch (substr($language, 0, 2)) {
            case 'nl':
                return static::LANGUAGE_DUTCH;
            case 'es':
                return static::LANGUAGE_SPANISH;
            default:
                return static::LANGUAGE_ENGLISH;
        }
    }

    /**
     * @param ResponseInterface $response
     * @param array|null $configuration
     * @param int $statusCode
     * @return ResponseInterface
     */
    protected function createJsonResponse(ResponseInterface $response, $data, int $statusCode): ResponseInterface
    {
        $response = $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        if (!empty($data)) {
            $options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;
            $response->getBody()->write(json_encode($data ?: null, $options));
            $response->getBody()->rewind();
        }

        return $response;
    }
}
