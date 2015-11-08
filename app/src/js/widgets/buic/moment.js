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
     * Resource id returned by setInterval().
     *
     * @memberOf jQuery.widget.bolt.moment
     * @static
     * @type string
     */
    var intervalId = 0;

    /**
     * List of update callbacks.
     *
     * @memberOf jQuery.widget.bolt.moment
     * @static
     * @type string
     */
    var updateList = $.Callbacks();

    /**
     * Bolt moment.
     *
     * @class moment
     * @memberOf jQuery.widget.bolt
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     */
    $.widget('bolt.moment', /** @lends jQuery.widget.bolt.moment */ {
        /**
         * The constructor of the moment widget.
         *
         * @private
         */
        _create: function () {
            if (!intervalId) {
                intervalId = setInterval(updateList.fire, 15 * 1000);
            }
            updateList.add(this._update);
        },

        /**
         * Updates the displayed datetime as relative from now.
         *
         * @private
         */
        _update: function () {
        }
    });
})(jQuery);
