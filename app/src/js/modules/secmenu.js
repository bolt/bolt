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
    'use strict';

    /**
     * Bolt.secmenu mixin container.
     *
     * @private
     * @type {Object}
     */
    var secmenu = {};

    /**
     * Timeout to open/close the popover submenu.
     *
     * @private
     * @constant {integer} Timeout resource number.
     * @memberof Bolt.secmenu
     */
    var timeout = 0;

    /**
     * Initialize the menu toogle, that shows/hides secondary navigation.
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
     * Initialize the collapse button, that collapses secondary navigation to icon only design.
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

            $('#bolt-footer').addClass('bolt-footer-hidden');

            return false;
        });
    }

    /**
     * Initialize the expand button, that expands secondary navigation to icon full width design.
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

            $('#bolt-footer').removeClass('bolt-footer-hidden');

            return false;
        });
    }

    /**
     * Make sure the sidebar is as long as the content height.
     *
     * @private
     * @function adjustSidebarHeight
     * @memberof Bolt.secmenu
     */
    function adjustSidebarHeight() {
        var contentHeight = $('#navpage-content').outerHeight(),
            sidebarObj = $('#navpage-secondary'),
            sidebarHeight = sidebarObj.outerHeight(),
            docHeight = $(document).height() - sidebarObj.position().top,
            newHeight = Math.max(contentHeight, docHeight),
            next = 5000;

        // If the sidebar height doesn't match the content's height, then adjust it. Check back
        // sooner, so we can adjust again if necessary (the content might still be changing).
        if (sidebarHeight !== newHeight) {
            sidebarObj.outerHeight(newHeight);
            next = 500;
        }

        window.setTimeout(adjustSidebarHeight, next);
    }

    /**
     * Initialize the popover menues in the secondary menu.
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
                    trigger: 'manual',
                    content: submenu,
                    html: true
                });

                menuitem.on('mouseover focus click dblclick', function (e) {
                    var item = this,
                        delay = 300;

                    // If clicked and popover not shown yet, show it.
                    if (e.type === 'click' && !$(item).next().hasClass('popover')) {
                        e.preventDefault();
                        delay = 0;
                    }

                    window.clearTimeout(timeout);
                    timeout = window.setTimeout(function () {
                        $('#navpage-secondary a.menu-pop').not(item).popover('hide');
                        $(item).popover('show');
                    }, delay);
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
        $('#navpage-secondary a.menu-pop').on('click', function (e) {
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
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.secmenu
     */
    secmenu.init = function () {
        var usePopOvers = !$('.navbar-toggle').is(':visible');

        // Initialize the secondary menu in the sidebar.
        initSidebarToggle();
        initSidebarCollapse();
        initSidebarExpand();
        if ($('#navpage-secondary').length) {
            adjustSidebarHeight();
        }

        // Initialize the submenu
        if (usePopOvers) {
            initPopOvers();
        } else {
            initMobileSubmenu();
        }
    };

    // Apply mixin container.
    bolt.secmenu = secmenu;

})(Bolt || {}, jQuery, window);
