/**
 * Backbone object for collapsable sidebar.
 */

var Sidebar = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {

        // set up 'fixlength'
        window.setTimeout(function () { bolt.sidebar.fixlength(); }, 500);

    },

    /*
     * Make sure the sidebar is as long as the document height. Also: Typecasting! love it or hate it!
     */
    fixlength: function () {
        var documentheight = $('#navpage-content').height() + 34;
        if (documentheight > $('#navpage-secondary').height()) {
            $('#navpage-secondary').height(documentheight + "px");
            window.setTimeout(function () { bolt.sidebar.fixlength(); }, 300);
        } else {
            window.setTimeout(function () { bolt.sidebar.fixlength(); }, 3000);
        }
    },

    /**
     * Collapse secondary navigation to icon only design
     */
    collapse: function () {
        $('#navpage-wrapper')
            .removeClass('nav-secondary-opened')
            .addClass('nav-secondary-collapsed');
        // We add the '-hoverable' class to make sure the sidebar _first_ collapses,
        // and only _then_ can be opened by hovering on it.
        setTimeout(function () {
            $('#navpage-wrapper').addClass('nav-secondary-collapsed-hoverable');
        }, 300);
        $.cookie('sidebar', 'collapsed', { expires: 21, path: '/' });
    },

    /**
     * Expand secondary navigation to icon full width design
     */
    expand: function () {
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
    }

});
