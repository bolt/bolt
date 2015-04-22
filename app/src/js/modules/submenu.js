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
        var usePopOvers = !$('.navbar-toggle').is(':visible');

        if (usePopOvers) {
            initPopOvers();
        } else {
            initMobileSubmenu();
        }
        initSidebar();
    };

    /**
     * Timeout to open/close the popover submenu.
     *
     * @private
     * @constant {integer} Timeout resource number.
     * @memberof Bolt.submenu
     */
    var timeout = 0;

    /**
     * Timeout check the sidebar height.
     *
     * @private
     * @constant {integer} Timeout resource number.
     * @memberof Bolt.submenu
     */
    var lengthTimer = 0;

    /**
     * Initialize the sidebar.
     *
     * @private
     * @function initSidebar
     * @memberof Bolt.submenu
     */
    function initSidebar() {
        lengthTimer = window.setTimeout(this.fixlength.bind(this), 500);
    }

    /**
     * Make sure the sidebar is as long as the document height. Also: Typecasting! love it or hate it!
     *
     * @private
     * @function fixlength
     * @memberof Bolt.submenu
     */
    function fixlength() {
        var documentheight = $('#navpage-content').height() + 34;

        if (documentheight > $('#navpage-secondary').height()) {
            $('#navpage-secondary').height(documentheight + 'px');
            lengthTimer = window.setTimeout(this.fixlength.bind(this), 300);
        } else {
            lengthTimer = window.setTimeout(this.fixlength.bind(this), 3000);
        }
    }

    /**
     * Collapse secondary navigation to icon only design.
     *
     * @private
     * @function collapse
     * @memberof Bolt.submenu
     */
    function collapse() {
        $('#navpage-wrapper')
            .removeClass('nav-secondary-opened')
            .addClass('nav-secondary-collapsed');
        // We add the '-hoverable' class to make sure the sidebar _first_ collapses,
        // and only _then_ can be opened by hovering on it.
        setTimeout(function () {
            $('#navpage-wrapper').addClass('nav-secondary-collapsed-hoverable');
        }, 300);
        $.cookie('sidebar', 'collapsed', { expires: 21, path: '/' });
    }

    /**
     * Expand secondary navigation to icon full width design.
     *
     * @private
     * @function expand
     * @memberof Bolt.submenu
     */
    function expand() {
        $('#navpage-wrapper').removeClass(
            'nav-secondary-collapsed nav-secondary-opened nav-secondary-collapsed-hoverable'
        );
        $.removeCookie('sidebar', {path: '/'});
    }

    /**
     * Show/hide secondary navigation.
     *
     * @private
     * @function toggle
     * @memberof Bolt.submenu
     */
    function toggle() {
        var wrapper = $('#navpage-wrapper');

        if (wrapper.hasClass('nav-secondary-opened')) {
            wrapper.removeClass('nav-secondary-opened nav-secondary-collapsed');
        } else {
            wrapper.removeClass('nav-secondary-collapsed').addClass('nav-secondary-opened');
        }
    }

    /**
     * Initialize the popover menues in the secondary meny.
     *
     * @private
     * @function initPopOvers
     * @memberof Bolt.submenu
     */
    function initPopOvers() {

        $('#navpage-secondary')
            .find('a.menu-pop').each(function () {
                var menuitem = $(this),
                    submenu = '';

                // Extract menu data and attach it to the popover.
                menuitem.nextAll('.submenu').children().each(function () {
                    if ($(this).hasClass('subdivider')) {
                        submenu += '<hr>';
                    }
                    submenu += $(this).html().trim().replace(/[ \n]+/g, ' ').replace(/(>) | (<)/g, '$1$2');
                });

                menuitem.popover({
                    content: submenu,
                    html: true
                });

                menuitem.on('mouseover focus', function () {
                    var item = this;

                    window.clearTimeout(timeout);
                    timeout = window.setTimeout(function () {
                        $('#navpage-secondary a.menu-pop').not(item).popover('hide');
                        $(item).popover('show');
                    }, 300);
                });
            })
            .end()
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

    /**
     * Initialize opening/closing of mobile submenu.
     *
     * @private
     * @function initMobileSubmenu
     * @memberof Bolt.submenu
     */
    function initMobileSubmenu() {
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

    // Apply mixin container
    bolt.submenu = submenu;

})(Bolt || {}, jQuery, window);
