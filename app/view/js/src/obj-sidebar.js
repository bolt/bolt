/**
 * Backbone object for collapsable sidebar.
 */

var Sidebar = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {

        var menuTimeout = "";

        // Do this, only if the sidebar is visible. (not when in small-responsive view)
        if ($('#navpage-secondary').is(':visible')) {

            // Note: It might seem easier to do this with a simple .popover, but we
            // shouldn't. People using keyboard access will not appreciate the menu timing
            // out and disappearing after a split-second of losing focus.
            $('#navpage-secondary a.menu-pop').on('mouseover focus', function (e) {
                var thiselem = this;
                window.clearTimeout(menuTimeout);
                menuTimeout = window.setTimeout(function () {
                    $('#navpage-secondary a.menu-pop').not(thiselem).popover('hide');
                    $(thiselem).popover('show');
                }, 400);
            });

            // We need two distinct events, to hide the sidebar's popovers:
            // One for 'mouseleave' on the sidebar itself, and one for keyboard
            // 'focus' on the items before and after.
            $('#navpage-secondary').on('mouseleave', function () {
                menuTimeout = window.setTimeout(function () {
                    $('#navpage-secondary a.menu-pop').popover('hide');
                }, 800);
            });
            $('.nav-secondary-collapse a, .nav-secondary-dashboard a').on('focus', function () {
                window.clearTimeout(menuTimeout);
                $('#navpage-secondary a.menu-pop').popover('hide');
            });

        }

        // set up 'fixlength'
        window.setTimeout(function () { bolt.sidebar.fixlength(); }, 500);

    },

    /*
     * Make sure the sidebar is as long as the document height. Also: Typecasting! love it or hate it!
     */
    fixlength: function () {
        var documentheight = $('#navpage-content').height() + 22;
        if (documentheight > $('#navpage-secondary').height()) {
            $('#navpage-secondary').height(documentheight + "px");
            window.setTimeout(function () { bolt.sidebar.fixlength(); }, 300);
        } else {
            window.setTimeout(function () { bolt.sidebar.fixlength(); }, 3000);
        }
    },

    /**
     * Hide / show subitems in the sidebar for mobile devices.
     *
     * @param {string} name
     */
    showSidebarItems: function (name) {
        bolt.sidebar.closePopOvers();
        // Check if the "hamburger menu" is actually visible. If not, we're not on mobile
        // or tablet, and we should just redirect to the first link, to prevent confusion.
        if (!$('.navbar-toggle').is(':visible')) {
            window.location.href = $('#navpage-secondary .submenu-' + name).find('a').first().attr('href');
        } else {
            if ($('#navpage-secondary .submenu-' + name).hasClass('show')) {
                $('#navpage-secondary .submenu-' + name).removeClass('show');
            } else {
                $('#navpage-secondary .submenu').removeClass('show');
                $('#navpage-secondary .submenu-' + name).addClass('show');
            }
        }
    },

    /**
     * Collapse secondary navigation to icon only design
     */
    collapse: function () {
        bolt.sidebar.closePopOvers();
        $('#navpage-wrapper').removeClass('nav-secondary-opened').addClass('nav-secondary-collapsed');
        // We add the '-hoverable' class to make sure the sidebar _first_ collapses, and only _then_
        // can be opened by hovering on it.
        setTimeout(function () {
            $('#navpage-wrapper').addClass('nav-secondary-collapsed-hoverable');
        }, 300);
        $.cookie('sidebar', 'collapsed', { expires: 21, path: '/' });
    },

    /**
     * Expand secondary navigation to icon full width design
     */
    expand: function () {
        bolt.sidebar.closePopOvers();
        $('#navpage-wrapper').removeClass(
            'nav-secondary-collapsed nav-secondary-opened nav-secondary-collapsed-hoverable'
        );
        $.removeCookie('sidebar', {path: '/'});
    },

    /**
     * Show/hide secondary navigation
     */
    toggle: function () {
        var wrapper = $('#navpage-wrapper');
        if (wrapper.hasClass('nav-secondary-opened')) {
            wrapper.removeClass('nav-secondary-opened nav-secondary-collapsed');
        } else {
            wrapper.removeClass('nav-secondary-collapsed').addClass('nav-secondary-opened');
        }
    },

    closePopOvers: function () {
        $('#navpage-secondary a.menu-pop').popover('hide');
    }
});
