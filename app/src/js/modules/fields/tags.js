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
            taxonomy = $(fieldset).find('select'),
            tagcloud = $(fieldset).find('div.tagcloud'),
            initSelect = function (tags) {
                taxonomy.select2({
                    width: '100%',
                    tags: tags,
                    allowClear: true,
                    minimumInputLength: 1,
                    tokenSeparators: [',', ' ']
                });
            };

        // Load all tags.
        $.ajax({
            url: bolt.conf('paths.root') + 'async/tags/' + slug,
            dataType: 'json',
            success: function (data) {
                var results = [];

                $.each(data, function (index, item) {
                    results.push( item.slug );
                });

                initSelect(results);
            },
            error: function () {
                initSelect([]);
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
                            tagcloud.append('<a href="#" rel="' + item.count + '">' + item.slug + '</a>');
                        });

                        tagcloud.find('a').on('click', function (e) {
                            var text = $(this).text(),
                                option = taxonomy.find('option[value=' + text + ']');

                            e.preventDefault();

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
                                start: 12,
                                end: 22,
                                unit: 'px'
                            },
                            color: {
                                start: '#888',
                                end: '#194770'
                            }
                        };
                        tagcloud.find('a').tagcloud();
                    }
                }
            });
        }
    };

    // Apply mixin container
    bolt.fields.tags = tags;

})(Bolt || {}, jQuery);
