/**
 * Backbone object for popups in the collapsable sidebar.
 */

var Navpopups = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {

        var menuTimeout = '';

        // Add the submenus to the data-content for bootstrap.popover
        $('#navpage-secondary a.menu-pop').each(
            function() {
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
        if ($('.navbar-toggle').is(':visible')) {
            // we're on mobile view - so do not trigger the popups,
            // console.log('mobile view');
            // only trigger the mobile open action
            $('#navpage-secondary a.menu-pop').on('click', function(e) {
                    e.preventDefault();
                    var submenu = $(this).nextAll('.submenu');

                    if (submenu.hasClass('show')) {
                        submenu.removeClass('show');
                    } else {
                        $('#navpage-secondary .submenu').removeClass('show');
                        submenu.addClass('show');
                    }
                }
            );
        } else {
            // Add hover focus and leave blur event handlers for popovers - on desktop
            $('#navpage-secondary')
                .on('mouseover focus', 'a.menu-pop', function () {
                        var item = this;
                        window.clearTimeout(menuTimeout);
                        menuTimeout = window.setTimeout(function () {
                            $('#navpage-secondary a.menu-pop').not(item).popover('hide');
                            $(item).popover('show');
                        }, 300);
                    }
                )
                .on('mouseenter focus', '.popover', function () {
                        window.clearTimeout(menuTimeout);
                    }
                )
                .on('mouseleave blur', 'a.menu-pop, .popover', function () {
                        window.clearTimeout(menuTimeout);
                        menuTimeout = window.setTimeout(function () {
                            $('#navpage-secondary a.menu-pop').popover('hide');
                        }, 300);
                    }
                );
        }
    }
});
