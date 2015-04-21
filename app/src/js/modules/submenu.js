/**
 * Popups of the collapsable sidebar secondary menu.
 *
 * @mixin
 * @namespace Bolt.submenu
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} window - Window object.
 */
(function (bolt, $, window) {
    /**
     * Bolt.submenu mixin container.
     *
     * @private
     * @type {Object}
     */
    var submenu = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.submenu
     */
    submenu.init = function () {
        initPopOvers();
        if ($('.navbar-toggle').is(':visible')) {
            initMobileAction();
        } else {
            initDesktopAction();
        }
    };

    /**
     * Timeout to open the submenu.
     *
     * @private
     * @constant {integer} Timeout resource number
     * @memberof Bolt.submenu
     */
    var timeout = 0;

    /**
     * Add the submenus to the data-content for bootstrap.popover.
     *
     * @private
     * @function initPopOvers
     * @memberof Bolt.submenu
     */
    function initPopOvers() {
        $('#navpage-secondary a.menu-pop').each(
            function () {
                var menu = '';

                $(this).nextAll('.submenu').children().each(function () {
                    if ($(this).hasClass('subdivider')) {
                        menu += '<hr>';
                    }
                    menu += $(this).html().trim().replace(/[ \n]+/g, ' ').replace(/(>) | (<)/g, '$1$2');
                });

                $(this).attr('data-html', true).attr('data-content', menu);
            }
        );
    }

    /**
     * Initialize opening/closing of mobile submenu.
     *
     * @private
     * @function initMobileAction
     * @memberof Bolt.submenu
     */
    function initMobileAction() {
        $('#navpage-secondary a.menu-pop').on('click', function(e) {
            var submenu = $(this).nextAll('.submenu');

            e.preventDefault();

            if (submenu.hasClass('show')) {
                submenu.removeClass('show');
            } else {
                $('#navpage-secondary .submenu').removeClass('show');
                submenu.addClass('show');
            }
        });
    }

    /**
     * Initialize adding of hover focus and leave blur event handlers for desktop popovers.
     *
     * @private
     * @function initDesktopAction
     * @memberof Bolt.submenu
     */
    function initDesktopAction() {
        $('#navpage-secondary')
            .on('mouseover focus', 'a.menu-pop', function () {
                var item = this;

                window.clearTimeout(timeout);
                timeout = window.setTimeout(function () {
                    $('#navpage-secondary a.menu-pop').not(item).popover('hide');
                    $(item).popover('show');
                }, 300);
            })
            .on('mouseenter focus', '.popover', function () {
                window.clearTimeout(timeout);
            })
            .on('mouseleave blur', 'a.menu-pop, .popover', function () {
                window.clearTimeout(timeout);
                timeout = window.setTimeout(function () {
                    $('#navpage-secondary a.menu-pop').popover('hide');
                }, 300);
            });
    }

    // Apply mixin container
    bolt.submenu = submenu;

})(Bolt || {}, jQuery, window);
