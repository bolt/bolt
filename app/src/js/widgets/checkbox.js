/**
 * See (http://jquery.com/).
 * @name jQuery
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 */

/**
 * See (http://jquery.com/)
 * @name widget
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 * @memberOf jQuery
 */

/**
 * See (http://jquery.com/)
 * @name bolt
 * @class
 * @memberOf jQuery.widget
 * @param {object} $ - Global jQuery object
 */
(function ($) {
    'use strict';

    /**
     * Bolt checkbox.
     *
     * @class checkbox
     * @memberOf jQuery.widget.bolt
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     */
    $.widget('bolt.checkbox', /** @lends jQuery.widget.bolt.checkbox */ {
        /**
         * The constructor of the checkbox widget.
         *
         * @private
         */
        _create: function () {
            this.element.addClass('buic-checkbox');
        }
    });
})(jQuery);
