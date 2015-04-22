/**
 * Popups of the collapsable sidebar secondary menu.
 *
 * @mixin
 * @namespace Bolt.secmenu
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} window - Window object.
 */
(function (bolt, $, window) {
    /**
     * Bolt.secmenu mixin container.
     *
     * @private
     * @type {Object}
     */
    var secmenu = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.secmenu
     */
    secmenu.init = function () {
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
     * @memberof Bolt.secmenu
     */
    var timeout = 0;

    /**
     * Initialize the sidebar.
     *
     * @private
     * @function initSidebar
     * @memberof Bolt.secmenu
     */
    function initSidebar() {
        adjustSidebarHeight();
        initSidebarToggle();
        initSidebarCollapse();
        initSidebarExpand();
    }

    /**
     * Make sure the sidebar is as long as the document height.
     *
     * @private
     * @function adjustSidebarHeight
     * @memberof Bolt.secmenu
     */
    function adjustSidebarHeight() {
        var newHeight = $(document).height() - $('#navpage-secondary').position().top,
            next = 3000;

        if (newHeight !== $('#navpage-secondary').outerHeight()) {
            $('#navpage-secondary').outerHeight(newHeight);
            next = 300;
        }
        window.setTimeout(adjustSidebarHeight, next);
    }

    /**
     * Show/hide secondary navigation.
     *
     * @private
     * @function initSidebarToggle
     * @memberof Bolt.secmenu
     */
    function initSidebarToggle() {
        $('.navbar-toggle').on('click', function () {
            var wrapper = $('#navpage-wrapper');

            if (wrapper.hasClass('nav-secondary-opened')) {
                wrapper.removeClass('nav-secondary-opened nav-secondary-collapsed');
            } else {
                wrapper.removeClass('nav-secondary-collapsed').addClass('nav-secondary-opened');
            }
        });
    }

    /**
     * Bind collapse button, that collapses secondary navigation to icon only design.
     *
     * @private
     * @function initSidebarCollapse
     * @memberof Bolt.secmenu
     */
    function initSidebarCollapse() {
        $('.nav-secondary-collapse a').on('click', function () {
            $('#navpage-wrapper')
                .removeClass('nav-secondary-opened')
                .addClass('nav-secondary-collapsed');
            // We add the '-hoverable' class to make sure the sidebar _first_ collapses,
            // and only _then_ can be opened by hovering on it.
            setTimeout(function () {
                $('#navpage-wrapper').addClass('nav-secondary-collapsed-hoverable');
            }, 300);
            $.cookie('sidebar', 'collapsed', {
                expires: 21,
                path: '/'
            });

            return false;
        });
    }

    /**
     * Bind expand button, that expand secondary navigation to icon full width design.
     *
     * @private
     * @function initSidebarExpand
     * @memberof Bolt.secmenu
     */
    function initSidebarExpand() {
        $('.nav-secondary-expand a').on('click', function () {
            $('#navpage-wrapper').removeClass(
                'nav-secondary-collapsed nav-secondary-opened nav-secondary-collapsed-hoverable'
            );
            $.removeCookie('sidebar', {
                path: '/'
            });

            return false;
        });
    }

    /**
     * Initialize the popover menues in the secondary meny.
     *
     * @private
     * @function initPopOvers
     * @memberof Bolt.secmenu
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
     * @memberof Bolt.secmenu
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
    bolt.secmenu = secmenu;

})(Bolt || {}, jQuery, window);
