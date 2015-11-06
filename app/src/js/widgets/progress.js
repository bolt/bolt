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
    /**
     * progress - Bolt progress bars
     *
     * @class progress
     * @memberOf jQuery.widget.bolt
     * @param {object} [options] - Options
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     */
    $.widget('bolt.progress', /** @lends jQuery.widget.bolt.progress */ {
        /**
         * Progress widget constructor
         */
        _create: function() {
            this.element.addClass('buic-progress');
        }
    });
})(jQuery);
