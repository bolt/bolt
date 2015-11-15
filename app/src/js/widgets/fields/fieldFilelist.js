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
            bolt.uploads.bindList(
                this.element,
                {
                    removeSingle: bolt.data('field.filelist.message.remove'),
                    removeMulti: bolt.data('field.filelist.message.removeMulti')
                }
            );
        }
    });
})(jQuery, Bolt);
