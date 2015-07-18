/**
 * Omnisearch initialisation.
 *
 * @mixin
 * @namespace Bolt.omnisearch
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} window - Window object.
 */
(function (bolt, $, window) {
    /**
     * Bolt.omnisearch mixin container.
     *
     * @private
     * @type {Object}
     */
    var omnisearch = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.omnisearch
     */
    omnisearch.init = function () {

        $('.omnisearch').select2({
            width: '100%',
            delay: 250,
            placeholder: bolt.data('omnisearch.placeholder'),
            minimumInputLength: 3,
            multiple: true, // this is for better styling …
            ajax: {
                url: bolt.conf('paths.async') + 'omnisearch',
                dataType: 'json',
                data: function (params) {
                    console.log('omnisearch: data');
                    return {q: params.term};
                },
                processResults: function (data) {
                    console.log('omnisearch: processResults');
                    var results = [];
                    $.each(data, function (index, item) {
                        results.push({
                            id: item.path,
                            path: item.path,
                            label: item.label,
                            priority: item.priority
                        });
                    });

                    return {results: results};
                }
            },
            templateResult: function (item) {
                return '<div class="omnisearch-result">' +
                            '<div>' + item.label + '</div>' +
                            '<small>' + item.path + '</small>' +
                       '</div>';
            },
            templateSelection: function (item) {
                console.log('omnisearch: templateSelection');
                window.location.href = item.path;

                return item.label;
            },
            escapeMarkup: function (m) {
                console.log('omnisearch: escapeMarkup');
                return m;
            }
        });
    };

    // Apply mixin container.
    bolt.omnisearch = omnisearch;

})(Bolt || {}, jQuery, window);
