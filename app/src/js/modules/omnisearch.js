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
    'use strict';

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

        $('.omnisearch select').select2({
            width: '100%',
            placeholder: bolt.data('omnisearch.placeholder'),
            minimumInputLength: 3,
            multiple: true, // this is for better styling …
            ajax: {
                delay: 600,
                url: bolt.conf('paths.async') + 'omnisearch',
                dataType: 'json',
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    var results = [];

                    $.each(data, function (index, item) {
                        results.push({
                            id: item.path,
                            path: item.path,
                            text: item.label
                        });
                    });

                    return {results: results};
                }
            },
            templateResult: function (item) {
                var path = item.path ? '<small>' + item.path + '</small>' : '',
                    spinner = item.loading ? '<i class="fa fa-spinner fa-spin"></i> ' : '';

                return '<div>' + spinner + item.text + '</div>' + path;
            },
            templateSelection: function (item) {
                window.location.href = item.path;

                return null;
            },
            escapeMarkup: function (m) {
                return m;
            }
        });
    };

    // Apply mixin container.
    bolt.omnisearch = omnisearch;

})(Bolt || {}, jQuery, window);
