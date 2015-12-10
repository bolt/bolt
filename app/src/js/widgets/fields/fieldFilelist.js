/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Filelist field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldFilelist
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldFilelist', /** @lends jQuery.widget.bolt.fieldFilelist.prototype */ {
        /**
         * The constructor of the filelist field widget.
         *
         * @private
         */
        _create: function () {
            // Mark this widget as type of "FileList", if not already set.
            this.options.isImage = this.options.isImage || false;

            // Bind events.
            bolt.uploads.bindList(
                this.element,
                {
                    removeSingle: bolt.data(
                        this.options.isImage ?
                            'field.imagelist.message.remove' : 'field.filelist.message.remove'
                    ),
                    removeMulti: bolt.data(
                        this.options.isImage ?
                            'field.imagelist.message.removeMulti' : 'field.filelist.message.removeMulti'
                    )
                }
            );
            bolt.uploads.bindUpload(this.element, true);
            bolt.uploads.bindSelectFromStack(this.element);
        }
    });
})(jQuery, Bolt);
