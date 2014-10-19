/**
 * Backbone object for collapsable sidebar.
 */

var Navpopups = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {
        // Do stuff on menu-pop stuff
        $('#navpage-secondary a[data-toggle="popover"]').each(
            function(e) {
                $(this).attr('data-action', null);
                $(this).data('action', null);
            }
        );
        $('#navpage-secondary').on(
            'mouseover focus',
            'a[data-toggle="popover"]',
            function (e) {
                e.preventDefault();
                console.log('hovering over', $(this));
            }
        );

        console.log('initialized Navpopups');

    }


});
