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
        console.log('tags.init');

        var slug = fconf.slug,
            idTaxonomy = '#taxonomy-' + slug,
            idTagcloud = '#tagcloud-' + slug;

        // Load all tags.
        $.ajax({
            url: bolt.conf('paths.root') + 'async/tags/' + slug,
            dataType: 'json',
            success: function (data) {
                var results = [];
                $.each(data, function (index, item) {
                    results.push( item.slug );
                });
                $(idTaxonomy).select2({tags: results, minimumInputLength: 1, tokenSeparators: [',', ' ']});
            },
            error: function () {
                $(idTaxonomy).select2({tags: [], minimumInputLength: 1, tokenSeparators: [',', ' ']});
            }
        });

        // Popular tags.
        if (fconf.tagcloud) {
            $.ajax({
                url: bolt.conf('paths.root') + 'async/populartags/' + slug,
                dataType: 'json',
                data : {limit: 40},
                success: function(data) {
                    if (data.length > 0) {
                        $.each(data, function(index, item){
                            $(idTagcloud).append('<a href="#" rel="' + item.count + '">' + item.slug + '</a>');
                        });
                        $(idTagcloud + ' a').on('click', function (e) {
                            e.preventDefault();
                            var data = $(idTaxonomy).select2('data');
                            data.push({id:$(this).text(), text:$(this).text()});
                            $(idTaxonomy).select2('data', data);
                        });

                        $.fn.tagcloud.defaults = {
                            size: {start: 12, end: 22, unit: 'px'},
                            color: {start: '#888', end: '#194770'}
                        };
                        $(idTagcloud + ' a').tagcloud();
                    }
                }
            });
        }
    };

    // Apply mixin container
    bolt.fields.tags = tags;

})(Bolt || {}, jQuery);
