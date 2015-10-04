/**
 * Handling of tags input fields.
 *
 * @mixin
 * @namespace Bolt.fields.tags
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.fields.tags mixin container.
     *
     * @private
     * @type {Object}
     */
    var tags = {};

    /**
     * Bind tags field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.tags
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    tags.init = function (fieldset, fconf) {
        var slug = fconf.slug,
            separators = [','],
            taxonomy = $(fieldset).find('select'),
            tagcloud = $(fieldset).find('.tagcloud');

        // Initialize the tag selector.
        if (!fconf.allow_spaces) {
          separators.push(' ');
        }
        taxonomy.select2({
            width: '100%',
            tags: tags,
            minimumInputLength: 1,
            tokenSeparators: separators
        });

        // Load all tags.
        $.ajax({
            url: bolt.conf('paths.root') + 'async/tags/' + slug,
            dataType: 'json',
            success: function (data) {
                var options = taxonomy.val();

                $.each(data, function (index, item) {
                    if (options.indexOf(item.name) < 0) {
                        options.push(item.name);
                        taxonomy.append($('<option/>', {
                            value: item.name,
                            text: item.name
                        })).trigger('change');
                    }
                });
            }
        });

        // Popular tags.
        if (tagcloud) {
            $.ajax({
                url: bolt.conf('paths.root') + 'async/populartags/' + slug,
                dataType: 'json',
                data : {
                    limit: 40
                },
                success: function(data) {
                    if (data.length > 0) {
                        $.each(data, function(index, item){
                            tagcloud.append($('<button/>', {
                                type: 'button',
                                text: item.name,
                                rel: item.count
                            })).append('');
                        });

                        tagcloud.find('button').on('click', function () {
                            var text = $(this).text(),
                                option = taxonomy.find('option[value=' + text + ']');

                            if (option.length > 0) {
                                // Just select if tag exists…
                                option = option.not(':selected').attr('selected', true).trigger('change');
                            } else {
                                // … otherwise add.
                                taxonomy.append($('<option/>', {
                                    value: text,
                                    text: text,
                                    selected: true
                                })).trigger('change');
                            }

                        });

                        $.fn.tagcloud.defaults = {
                            size: {
                                start: 10,
                                end: 20,
                                unit: 'px'
                            },
                            color: {
                                start: '#888',
                                end: '#194770'
                            }
                        };
                        tagcloud.find('button').tagcloud();
                        // Show the tagcloud.
                        tagcloud.css('display', 'block');
                    }
                }
            });
        }
    };

    // Apply mixin container
    bolt.fields.tags = tags;

})(Bolt || {}, jQuery);
