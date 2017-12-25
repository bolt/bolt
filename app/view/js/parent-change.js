/* global hierarchies */
(function ($) {
    'use strict';

    var $parent = $('#parent');
    var id      = $('#id').val();

    var getOption = function (key) {
        var option;

        for (var i = 0; i < hierarchies.length; i += 1) {
            if (hierarchies[i].key === key) {
                option       = hierarchies[i];
                option.index = i + 1;

                break;
            }
        }

        return option;
    };

    $parent.on('change', function () {
        var val       = $(this).val();
        var $prefix   = $('.bolt-field-slug .input-group-addon');
        var hierarchy = getOption(val);

        if (typeof hierarchies !== 'undefined' && typeof hierarchy !== 'undefined') {
            $prefix.text(hierarchy.path);
        } else if (typeof hierarchies !== 'undefined' && hierarchies.length && hierarchies[0].prefix !== null) {
            $prefix.text('/' + hierarchies[0].prefix || '/');
        } else if (val === '') {
            $prefix.text('/');
        }
    });

    // Remove this site from the list
    $parent.find('option[value="' + id + '"]').attr('disabled', true);

    if (typeof hierarchies !== 'undefined') {
        // Order the options based on the hierarchies output
        var $options = [];
        var i;
        var $option  = null;

        for (i = 0; i < hierarchies.length; i += 1) {
            // Reset the option
            $option = null;

            hierarchies[i].index = i + 1;

            // Find the option
            $option = $parent.find('option[value="' + hierarchies[i].key + '"]');

            // Check if option is the current item
            if (id === hierarchies[i].key) {
                $(this).attr('disabled', true);
            }

            // Add dashes to text
            var count      = (hierarchies[i].path.replace(hierarchies[i].prefix, '').match(/\//g) || []).length - 2;
            var dashedText = '';

            for (var j = 0; j < count; j++) {
                dashedText += '- '
            }

            dashedText += $option.text();

            $option.text(dashedText);

            // Add to array of all the options
            $options.push($option);
        }

        // Add the ordered options from the field back in to the field
        $parent.append($options);
    }

    $parent.trigger('change');
})(jQuery);
