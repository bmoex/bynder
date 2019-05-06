/**
 * Module: TYPO3/CMS/Bynder/CompactView
 *
 * Javascript for show the Bynder compact view in an overlay/modal
 */
define(['jquery',
    'nprogress',
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/Backend/Notification'
], function ($, NProgress, Modal, Notification) {
    'use strict';

    /**
     * The main CompactView object for Bynder
     *
     * @type {{compactViewUrl: string, inlineButton: string, title: string}}
     * @exports TYPO3/CMS/Bynder/CompactView
     */
    var BynderCompactView = {
        inlineButton: '.t3js-bynder-compact-view-btn',
        compactViewUrl: '',
        title: 'Pick a file from Bynder'
    };

    /**
     * Initialize all variables and listeners for CompactView
     *
     * @private
     */
    BynderCompactView.initialize = function () {
        var $button = $(BynderCompactView.inlineButton);
        BynderCompactView.compactViewUrl = $button.data('bynderCompactViewUrl');

        // Add all listeners based on inline button
        $button.on('click', function (event) {
            BynderCompactView.open();
        });

        $(document).on('BynderCompactViewAddMedia', function (event) {
            console.log('received', event.detail);
            var target = event.detail.target;
            var media = event.detail.media;
            if (target && media) {
                BynderCompactView.addMedia(target, media);
            }
        });
    };

    /**
     * Open Compact View through CompactViewController
     *
     * @private
     */
    BynderCompactView.open = function () {
        Modal.advanced({
            type: Modal.types.iframe,
            title: BynderCompactView.title,
            content: BynderCompactView.compactViewUrl,
            size: Modal.sizes.full
        });
    };

    /**
     * Add media to irre element in frontend for possible saving
     *
     * @param {String} target
     * @param {Array} media
     *
     * @private
     */
    BynderCompactView.addMedia = function (target, media) {
        return $.ajax({
            type: 'POST',
            url: TYPO3.settings.ajaxUrls['bynder_compact_view_get_files'],
            dataType: 'json',
            data: {
                target: target,
                files: media
            },
            beforeSend: function () {
                Modal.dismiss();
                NProgress.start();
            },
            success: function (data) {
                if (typeof data.files === 'object' && data.files.length) {
                    inline.importElementMultiple(
                        target,
                        'sys_file',
                        data.files,
                        'file'
                    );
                }

                if (data.message) {
                    Notification.success('', data.message, Notification.duration);
                }
            },
            error: function (xhr, type) {
                var data = xhr.responseJSON || {};
                if (data.error) {
                    Notification.error('', data.error, Notification.duration);
                } else {
                    Notification.error('', 'Unknown ' + type + ' occured.', Notification.duration);
                }
            },
            complete: function () {
                NProgress.done();
            }
        });
    };

    BynderCompactView.initialize();
    return BynderCompactView;
});
