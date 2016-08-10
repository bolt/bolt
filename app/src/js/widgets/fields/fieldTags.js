/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Tags field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldTags
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldTags', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldTags.prototype */ {
        /**
         * Default options.
         *
         * @property {string}   slug        - The slug
         * @property {boolean}  allowSpaces - Allow spaces in tags
         */
        options: {
            slug:        '',
            allowSpaces: false
        },

        /**
         * The constructor of the ags field widget.
         *
         * @private
         */
        _create: function () {
            var taxonomy = this.element.find('select'),
                tagcloud = this.element.find('.tagcloud'),
                slug = this.options.slug,
                separators = [','],
                tags = {};

            // Initialize the tag selector.
            if (!this.options.allowSpaces) {
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
                    var options = taxonomy.val() || [];

                    $.each(data, function (idx, item) {
                        if (options.indexOf(item.name) < 0) {
                            options.push(item.name);
                            taxonomy
                                .append($('<option/>', {
                                    value: item.name,
                                    text: item.name
                                }))
                                .trigger('change');
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
                    success: function (data) {
                        if (data.length > 0) {
                            $.each(data, function (idx, item){
                                tagcloud
                                    .append($('<button/>', {
                                        type: 'button',
                                        text: item.name,
                                        rel: item.count
                                    }))
                                    .append('');
                            });

                            tagcloud.find('button').on('click', function () {
                                var text = $(this).text(),
                                    option = taxonomy.find('option[value="' + text + '"]');

                                if (option.length > 0) {
                                    // Just select if tag exists…
                                    option = option.not(':selected').attr('selected', true).trigger('change');
                                } else {
                                    // … otherwise add.
                                    taxonomy
                                        .append($('<option/>', {
                                            value: text,
                                            text: text,
                                            selected: true
                                        }))
                                        .trigger('change');
                                }

                            });

                            $.fn.tagcloud.defaults = {
                                size: {
                                    start: 10,
                                    end:   20,
                                    unit:  'px'
                                },
                                color: {
                                    start: '#888',
                                    end:   '#194770'
                                }
                            };
                            tagcloud.find('button').tagcloud();
                            // Show the tagcloud.
                            tagcloud.css('display', 'block');
                        }
                    }
                });
            }
        }
    });
})(jQuery, Bolt);
