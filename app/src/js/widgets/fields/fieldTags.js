/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($) {
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
         * @property {boolean}  allowSpaces - Allow spaces in tags
         */
        options: {
            allowSpaces: false
        },

        /**
         * The constructor of the tags field widget.
         *
         * @private
         */
        _create: function () {
            var taxonomy = this.element.find('select'),
                tagcloud = this.element.find('.tagcloud'),
                separators = [','],
                tags = {};
            // Regigger firefoxes form cache kagigger
            // See https://stackoverflow.com/questions/1479233/why-doesnt-firefox-show-the-correct-default-select-option
            var selected = taxonomy.find('option[selected]');
            taxonomy.find('option').removeAttr('selected');
            selected.prop('selected', 'selected');

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
                url: this.element.data('tagsUrl'),
                dataType: 'json',
                success: function (data) {
                    var options = taxonomy.val() || [];
                    var arrayLength = data.length;
                    var optionsHTML = '';
                    for (var i = 0; i < arrayLength; i++) {
                        if (options.indexOf(data[i].slug) < 0) {
                            options.push(data[i].slug);
                            optionsHTML += '<option value="' + data[i].slug + '">' + data[i].name + '</option>';
                        }
                    }
                    taxonomy.append($(optionsHTML)).trigger('change');
                }
            });

            // Popular tags.
            if (tagcloud) {
                $.ajax({
                    url: this.element.data('popularTagsUrl'),
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
                                        rel: item.count,
                                        value: item.slug
                                    }))
                                    .append('');
                            });

                            tagcloud.find('button').on('click', function () {
                                var slug = $(this).data('slug'),
                                    text = $(this).text(),
                                    option = taxonomy.find('option[value="' + slug + '"]');

                                if (option.length > 0) {
                                    // Just select if tag exists…
                                    option = option.not(':selected').attr('selected', true).trigger('change');
                                } else {
                                    // … otherwise add.
                                    taxonomy
                                        .append($('<option/>', {
                                            value: slug,
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
