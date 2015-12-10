/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Imagelist field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldImagelist
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldImagelist', /** @lends jQuery.widget.bolt.fieldImagelist.prototype */ {
        /**
         * The constructor of the imagelist field widget.
         *
         * @private
         */
        _create: function () {
            bolt.uploads.bindList(
                this.element,
                {
                    removeSingle: bolt.data('field.imagelist.message.remove'),
                    removeMulti: bolt.data('field.imagelist.message.removeMulti')
                }
            );
            bolt.uploads.bindUpload(this.element, true);
            bolt.uploads.bindSelectFromStack(this.element);
        }
    });
})(jQuery, Bolt);
