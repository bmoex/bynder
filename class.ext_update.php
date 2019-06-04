<?php

namespace BeechIt\Bynder;

use BeechIt\Bynder\Traits\BynderService;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class for validating information
 * @package BeechIt\Bynder
 */
class ext_update
{
    public const STATE_NOTICE = -2;
    public const STATE_INFO = -1;
    public const STATE_OK = 0;
    public const STATE_WARNING = 1;
    public const STATE_ERROR = 2;

    public static $classes = [
        self::STATE_NOTICE => 'notice',
        self::STATE_INFO => 'info',
        self::STATE_OK => 'success',
        self::STATE_WARNING => 'warning',
        self::STATE_ERROR => 'danger'
    ];

    public static $icons = [
        self::STATE_NOTICE => 'lightbulb-o',
        self::STATE_INFO => 'info',
        self::STATE_OK => 'check',
        self::STATE_WARNING => 'exclamation',
        self::STATE_ERROR => 'times'
    ];

    use BynderService;

    /**
     * @return string
     */
    public function main(): string
    {
        $content = '';

        try {
            $user = $this->getBynderService()->getBynderApi()->getCurrentUser()
                ->wait();
            if ($user['active'] === true) {
                $content .= $this->message(self::STATE_OK, 'User used for API is active.');
            } else {
                $content .= $this->message(self::STATE_WARNING, 'Current user is not an admin.');
            }
        } catch (\Exception $e) {
            $content .= $this->message(self::STATE_ERROR, $e->getMessage(), $e->getCode());
        }

        return $content;
    }

    /**
     * @param int $state
     * @param string $content
     * @param string|null $title
     * @param array $arguments
     * @param string|null $iconName
     * @param bool $disableIcon
     * @return string
     * @see \TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper::renderStatic()
     */
    public function message(int $state, string $content, string $title = null, array $arguments = [], $iconName = null, $disableIcon = false): string
    {
        if (!empty($arguments)) {
            $title = preg_replace_callback(
                '/\G((?:[^{\\]|\\.|{[^{])*){{((?:[^}\\]|\\.|}[^}])+)}}/',
                static function ($match) use ($arguments) {
                    return $match[1] . $arguments[$match[2]];
                },
                $title
            );
            $content = preg_replace_callback(
                '/\G((?:[^{\\]|\\.|{[^{])*){{((?:[^}\\]|\\.|}[^}])+)}}/',
                static function ($match) use ($arguments) {
                    return $match[1] . $arguments[$match[2]];
                },
                $content
            );
        }

        $isInRange = MathUtility::isIntegerInRange($state, -2, 2);
        if (!$isInRange) {
            $state = -2;
        }

        $stateClass = self::$classes[$state];
        $icon = self::$icons[$state];
        if ($iconName !== null) {
            $icon = $iconName;
        }
        $iconTemplate = '';
        if (!$disableIcon) {
            $iconTemplate = '' .
                '<div class="media-left">' .
                '<span class="fa-stack fa-lg callout-icon">' .
                '<i class="fa fa-circle fa-stack-2x"></i>' .
                '<i class="fa fa-' . htmlspecialchars($icon) . ' fa-stack-1x"></i>' .
                '</span>' .
                '</div>';
        }
        $titleTemplate = '';
        if ($title !== null) {
            $titleTemplate = '<h4 class="callout-title">' . htmlspecialchars($title) . '</h4>';
        }
        return '<div class="callout callout-' . htmlspecialchars($stateClass) . '">' .
            '<div class="media">' .
            $iconTemplate .
            '<div class="media-body">' .
            $titleTemplate .
            '<div class="callout-body">' . $content . '</div>' .
            '</div>' .
            '</div>' .
            '</div>'
            . PHP_EOL;
    }

    /**
     * @return boolean
     */
    public function access(): bool
    {
        return true;
    }
}
