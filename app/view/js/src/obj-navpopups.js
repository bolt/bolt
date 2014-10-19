/**
 * Backbone object for collapsable sidebar.
 */

var Navpopups = Backbone.Model.extend({

    defaults: {
    },

    initialize: function () {

        var menuTimeout = '';

        // Add the submenus to the data-content for bootstrap.popover
        $('#navpage-secondary a.menu-pop').each(
            function(i) {
                var name = $(this).attr('data-name'),
                    menu = '';

                $('ul .submenu-' + name + ' li').each(function () {
                    if ($(this).hasClass('subdivider')) {
                        menu += '<hr>';
                    }
                    menu += $(this).html().trim().replace(/[ \n]+/g, ' ').replace(/(>) | (<)/g, '$1$2');
                });

                $(this).attr('data-html', true).attr('data-content', menu);
            }
        );
        // Add hover focus and leave blur event handlers for popovers - on desktop
        $('#navpage-secondary')
            .on('mouseover focus', 'a.menu-pop', function () {
                    var item = this;
                    window.clearTimeout(menuTimeout);
                    menuTimeout = window.setTimeout(function () {
                        $('#navpage-secondary a.menu-pop').not(item).popover('hide');
                        $(item).popover('show');
                    }, 400);
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
                    }, 800);
                }
            );
    }
});
