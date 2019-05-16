<?php

namespace BeechIt\Bynder\Backend;

/*
 * This source file is proprietary property of Beech.it
 * Date: 19-2-18
 * All code (c) Beech.it all rights reserved
 */

use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Class InlineControlContainer
 *
 * Override core InlineControlContainer to inject Bynder button
 */
class InlineControlContainer extends \TYPO3\CMS\Backend\Form\Container\InlineControlContainer
{

    /**
     * @param array $inlineConfiguration
     * @return string
     */
    protected function renderPossibleRecordsSelectorTypeGroupDB(array $inlineConfiguration)
    {
        $selector = parent::renderPossibleRecordsSelectorTypeGroupDB($inlineConfiguration);

        if ($this->displayBynderButton()) {
            $button = $this->renderBynderButton($inlineConfiguration);

            // Inject button before help-block
            if (strpos($selector, '</div><div class="help-block">') > 0) {
                $selector = str_replace('</div><div class="help-block">', $button . '</div><div class="help-block">', $selector);
                // Try to inject it into the form-control container
            } elseif (preg_match('/<\/div><\/div>$/i', $selector)) {
                $selector = preg_replace('/<\/div><\/div>$/i', $button . '</div></div>', $selector);
            } else {
                $selector .= $button;
            }
        }

        return $selector;
    }

    /**
     * @param array $inlineConfiguration
     * @return string
     */
    protected function renderBynderButton(array $inlineConfiguration): string
    {
        $languageService = $this->getLanguageService();

        if (!$this->bynderStorageAvailable()) {
            $errorTextHtml = [];
            $errorTextHtml[] = '<div class="alert alert-danger" style="display: inline-block">';
            $errorTextHtml[] = $this->iconFactory->getIcon('actions-bynder-compact-view', Icon::SIZE_SMALL)->render();
            $errorTextHtml[] = htmlspecialchars($languageService->sL('LLL:EXT:bynder/Resources/Private/Language/locallang_be.xlf:compact_view.error-no-storage-access'));
            $errorTextHtml[] = '</div>';

            return LF . implode(LF, $errorTextHtml);
        }

        $groupFieldConfiguration = $inlineConfiguration['selectorOrUniqueConfiguration']['config'];

        $foreign_table = $inlineConfiguration['foreign_table'];
        $allowedAssetTypes = ConfigurationUtility::getAssetTypesByAllowedElements($groupFieldConfiguration['appearance']['elementBrowserAllowed']);
        $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);

        $element = 'bynder' . $this->inlineData['config'][$currentStructureDomObjectIdPrefix]['md5'];
        $compactViewUrl = BackendUtility::getModuleUrl('bynder_compact_view', [
            'element' => $element,
            'irreObject' => $currentStructureDomObjectIdPrefix . '-' . $foreign_table,
            'assetTypes' => implode(',', $allowedAssetTypes)
        ]);

        $this->requireJsModules[] = 'TYPO3/CMS/Bynder/CompactView';
        $buttonText = htmlspecialchars($languageService->sL('LLL:EXT:bynder/Resources/Private/Language/locallang_be.xlf:compact_view.button'));
        $titleText = htmlspecialchars($languageService->sL('LLL:EXT:bynder/Resources/Private/Language/locallang_be.xlf:compact_view.header'));

        $buttonHtml = [];
        $buttonHtml[] = '<a href="#" class="btn btn-default t3js-bynder-compact-view-btn ' . $element . '"'
            . ' data-bynder-compact-view-url="' . htmlspecialchars($compactViewUrl) . '" '
            . ' data-title="' . htmlspecialchars($titleText) . '">';
        $buttonHtml[] = $this->iconFactory->getIcon('actions-bynder-compact-view', Icon::SIZE_SMALL)->render();
        $buttonHtml[] = $buttonText;
        $buttonHtml[] = '</a>';
        return LF . implode(LF, $buttonHtml);
    }

    /**
     * Check if the BE user has access to the Bynder storage
     *
     * Admin has access when there is a resource storage with driver type bynder
     * Editors need to have access to a mount of that storage
     *
     * @return bool
     */
    protected function bynderStorageAvailable(): bool
    {
        /** @var ResourceStorage $fileStorage */
        foreach ($this->getBackendUserAuthentication()->getFileStorages() as $fileStorage) {
            if ($fileStorage->getDriverType() === BynderDriver::KEY) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the BE user has access to the Bynder compact view
     *
     * Admin has access when there is a resource storage with driver type bynder
     * Editors need to have access to a mount of that storage
     *
     * @return bool
     */
    protected function displayBynderButton(): bool
    {
        $backendUser = $this->getBackendUserAuthentication();
        $filePermissions = $backendUser->getFilePermissions();

        return $backendUser->isAdmin() || (bool)$filePermissions['addFileViaBynder'];
    }
}
