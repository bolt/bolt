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
     * Field configuration.
     *
     * @typedef {Object} FieldConf
     * @memberof Bolt.slug
     *
     * @property {string} bind - Always 'slug'.
     * @property {string|null} contentId - Content Id.
     * @property {boolean} isEmpty - Is not set?
     * @property {string} key - The field key
     * @property {string} slug - Content slug.
     * @property {Array} uses - Fields used to automatically generate a slug.
     */

    /**
     * Field data.
     *
     * @typedef {Object} FieldData
     * @memberof Bolt.slug
     *
     * @property {Object} group - Group container.
     * @property {Object} show - Slug display.
     * @property {Object} data - Data field.
     * @property {Object} lock - Lock button.
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
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    slug.init = function (fieldset, fconf) {
        var field = {
                group: $(fieldset).find('.input-group'),
                show: $(fieldset).find('em'),
                data: $(fieldset).find('input'),
                lock: $(fieldset).find('button.lock')
            };

        field.lock.bind('click', function () {
            if (field.group.hasClass('locked')) {
                // "unlock" if it's currently empty, _or_ we've confirmed that we want to do so.
                if (fconf.isEmpty || confirm(Bolt.data('field.slug.message.unlock'))) {
                    field.group.removeClass('locked').addClass('unlocked');
                    makeUri(fconf.slug, fconf.contentId, fconf.uses, fconf.key, field, false);
                }
            } else {
                field.group.removeClass('unlocked').addClass('locked');
                stopMakeUri(fconf.key, fconf.uses);
            }
            this.blur();
        });

        $(fieldset).find('button.edit').bind('click', function () {
            var newslug = prompt(Bolt.data('field.slug.message.set'), field.data.val());

            if (newslug) {
                field.group.removeClass('unlocked').addClass('locked');
                stopMakeUri(fconf.key, fconf.uses);
                makeUriAjax(newslug, fconf.slug, fconf.contentId, fconf.key, field, false);
            }
            this.blur();
        });

        if (fconf.isEmpty) {
            field.lock.trigger('click');
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
     * @param {FieldData} field - Field data.
     * @param {boolean} fullUri - Get the full URI?
     */
    function makeUriAjax(text, contenttypeSlug, id, slugFieldId, field, fullUri) {
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
                field.data.val(uri);
                field.show.html(uri);
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
     * @param {FieldData} field - Field data.
     * @param {boolean} fullUri - Get the full URI?
     */
    function makeUri(contenttypeSlug, id, usesFields, slugFieldId, field, fullUri) {
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
                        makeUriAjax(usesValue.join(' '), contenttypeSlug, id, slugFieldId, field, fullUri);
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
