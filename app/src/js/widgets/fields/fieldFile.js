/**
 * File field widget.
 *
 * @param {Object} $ - Global jQuery object
 * @param {Object} bolt - The Bolt module.
 */
(function ($, bolt) {
    'use strict';

    /**
     * File field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldFile
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldFile', /** @lends jQuery.widget.bolt.fieldFile.prototype */ {
        /**
         * The constructor of the file field widget.
         *
         * @private
         */
        _create: function () {
            bolt.uploads.bindField(this.element);
        }
    });
})(jQuery, Bolt);
