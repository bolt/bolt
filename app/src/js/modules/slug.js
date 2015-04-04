/**
 * Functions for working with the automagic URI/Slug generation with multipleslug support.
 *
 * @mixin
 * @namespace Bolt.slug
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    /**
     * Bind data.
     *
     * @typedef {Object} BindData
     * @memberof Bolt.slug
     *
     * @property {string} bind - Always 'slug'.
     * @property {string|null} contentId - Content Id.
     * @property {boolean} isEmpty - Is not set?
     * @property {string} key - The field key
     * @property {string} messageSet - Message asking to input a new slug.
     * @property {string} messageUnlock - Unlock confirmation message.
     * @property {string} slug - Content slug.
     * @property {Array} uses - Fields used to automatically generate a slug.
     */

    /**
     * Bolt.slug mixin container.
     *
     * @private
     * @type {Object}
     */
    var slug = {};

    /**
     * Bind slug field.
     *
     * @static
     * @function init
     * @memberof Bolt.slug
     *
     * @param {object} fieldset
     * @param {BindData} fconfig
     */
    slug.init = function (fieldset, fconf) {
        $(fieldset).find('.sluglocker').bind('click', function () {
            var lock = $(this).find('i');

            if (lock.hasClass('fa-lock')) {
                // "unlock" if it's currently empty, _or_ we've confirmed that we want to do so.
                if (fconf.isEmpty || confirm(fconf.messageUnlock)) {
                    lock.removeClass('fa-lock').addClass('fa-unlock');
                    makeUri(fconf.slug, fconf.contentId, fconf.uses, fconf.key, false);
                }
            } else {
                lock.addClass('fa-lock').removeClass('fa-unlock');
                stopMakeUri(fconf.key, fconf.uses);
            }
        });

        $(fieldset).find('.slugedit').bind('click', function () {
            var newslug = prompt(fconf.messageSet, $('#show-' + fconf.key).text());

            if (newslug) {
                $(fieldset).find('.sluglocker i').addClass('fa-lock').removeClass('fa-unlock');
                stopMakeUri(fconf.key, fconf.uses);
                makeUriAjax(newslug, fconf.slug, fconf.contentId, fconf.key, false);
            }
        });

        if (fconf.isEmpty) {
            $(fieldset).find('.sluglocker').trigger('click');
        }
    };

    /**
     * Timeout.
     *
     * @private
     * @type {Array}
     * @memberof Bolt.slug
     */
    var timeout = [];

    /**
     * Make sure events are bound only once.
     *
     * @private
     * @type {boolean}
     * @memberof Bolt.slug
     */
    var isBound = false;

    /**
     * Get URI for slug from remote
     *
     * @private
     * @function makeUriAjax
     * @memberof Bolt.slug
     *
     * @param {string} text - New slug text.
     * @param {string} contenttypeSlug - Contenttype slug.
     * @param {string} id - Id.
     * @param {string} slugFieldId - Id of the slug field.
     * @param {boolean} fullUri - Get the full URI?
     */
    function makeUriAjax(text, contenttypeSlug, id, slugFieldId, fullUri) {
        $.ajax({
            url: bolt.conf('paths.async') + 'makeuri',
            type: 'GET',
            data: {
                title: text,
                contenttypeslug: contenttypeSlug,
                id: id,
                slugfield: slugFieldId,
                fulluri: fullUri
            },
            success: function (uri) {
                $('#' + slugFieldId).val(uri);
                $('#show-' + slugFieldId).html(uri);
            },
            error: function () {
                console.log('failed to get an URI');
            }
        });
    }

    /**
     * make Uri from input
     *
     * @private
     * @function makeUri
     * @memberof Bolt.slug
     *
     * @param {string} contenttypeSlug - Contenttype slug.
     * @param {string} id - Id.
     * @param {Array} usesFields - Field used to automatically generate a slug.
     * @param {string} slugFieldId - Id of the slug field.
     * @param {boolean} fullUri - Get the full URI?
     */
    function makeUri(contenttypeSlug, id, usesFields, slugFieldId, fullUri) {
        $.each(usesFields, function (i, bindField) {
            $('#' + bindField).on('propertychange.bolt input.bolt change.bolt', function () {
                var usesValue = [];

                $.each(usesFields, function (i, useField) {
                    var field = $('#' + useField);

                    if (field.is('select')) {
                        field.find('option:selected').each(function(i, option) {
                            if (option.text !== '') {
                                usesValue.push(option.text);
                            }
                        });
                    } else if (field.val()) {
                        usesValue.push(field.val());
                    }
                });

                clearTimeout(timeout[slugFieldId]);
                timeout[slugFieldId] = setTimeout(
                    function () {
                        makeUriAjax(usesValue.join(' '), contenttypeSlug, id, slugFieldId, fullUri);
                    },
                    200
                );
            }).trigger('change.bolt');
        });
    }

    /**
     * Stop making URI
     *
     * @private
     * @function stopMakeUri
     * @memberof Bolt.slug
     *
     * @param {string} slugFieldId - Id of the slug field.
     * @param {Array} usesFields - Field used to automatically generate a slug.
     */
    function stopMakeUri(slugFieldId, usesFields) {
        $.each(usesFields, function (i, name) {
            $('#' + name).unbind('propertychange.bolt input.bolt change.bolt');
        });
        clearTimeout(timeout[slugFieldId]);
    }

    // Apply mixin container
    bolt.slug = slug;

})(Bolt || {}, jQuery);
