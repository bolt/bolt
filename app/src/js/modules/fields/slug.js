/**
 * Functions for working with the automagic URI/Slug generation with multipleslug support.
 *
 * @mixin
 * @namespace Bolt.fields.slug
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Field configuration.
     *
     * @typedef {Object} FieldConf
     * @memberof Bolt.fields.slug
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
     * @memberof Bolt.fields.slug
     *
     * @property {Object} group - Group container.
     * @property {Object} show - Slug display.
     * @property {Object} data - Data field.
     * @property {Object} lock - Lock button.
     * @property {Object} unlock - Unlock button.
     * @property {Object} edit - Edit button.
     * @property {string} key - The field key
     * @property {Array} uses - Fields used to automatically generate a slug.
     * @property {string} slug - Content slug.
     * @property {string|null} id - Content Id.
     */

    /**
     * Bolt.fields.slug mixin container.
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
     * @memberof Bolt.fields.slug
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    slug.init = function (fieldset, fconf) {
        var field = {
                group: $(fieldset).find('.input-group'),
                show: $(fieldset).find('em'),
                data: $(fieldset).find('input'),
                lock: $(fieldset).find('li.lock a'),
                unlock: $(fieldset).find('li.unlock a'),
                edit: $(fieldset).find('li.edit a'),
                key: fconf.key,
                uses: fconf.uses,
                slug: fconf.slug,
                id: fconf.contentId
            };

        field.lock.on('click', function () {
            lock(field);
        });

        field.unlock.on('click', function () {
            unlock(field, fconf.isEmpty);
        });

        field.edit.on('click', function () {
            edit(field);
        });

        if (fconf.isEmpty) {
            field.unlock.trigger('click');
        }
    };

    /**
     * Timeout.
     *
     * @private
     * @type {Array}
     * @memberof Bolt.fields.slug
     */
    var timeout = [];

    /**
     * Locks the slug field.
     *
     * @private
     * @function lock
     * @memberof Bolt.fields.slug
     *
     * @param {FieldData} field - Field data.
     */
    function lock(field) {
        field.group.removeClass('unlocked').addClass('locked');
        stopAutoGeneration(field);
    }

    /**
     * Unlocks the slug field.
     *
     * @private
     * @function unlock
     * @memberof Bolt.fields.slug
     *
     * @param {FieldData} field - Field data.
     * @param {boolean} wasEmpty - Slug is currently empty
     */
    function unlock(field, wasEmpty) {
        // "unlock" if it's currently empty, _or_ we've confirmed that we want to do so.
        if (wasEmpty || confirm(bolt.data('field.slug.message.unlock'))) {
            field.group.removeClass('locked').addClass('unlocked');
            startAutoGeneration(field);
        }
    }

    /**
     * Edit the slug.
     *
     * @private
     * @function edit
     * @memberof Bolt.fields.slug
     *
     * @param {FieldData} field - Field data.
     */
    function edit(field) {
        var newslug = prompt(bolt.data('field.slug.message.set'), field.data.val());

        if (newslug) {
            field.group.removeClass('unlocked').addClass('locked');
            stopAutoGeneration(field);
            getUriAjax(field, newslug);
        }
    }

    /**
     * Get URI for slug from remote.
     *
     * @private
     * @function getUriAjax
     * @memberof Bolt.fields.slug
     *
     * @param {FieldData} field - Field data.
     * @param {string} text - New slug text.
     */
    function getUriAjax(field, text) {
        $.ajax({
            url: bolt.conf('paths.async') + 'makeuri',
            type: 'GET',
            data: {
                title: text,
                contenttypeslug: field.slug,
                id: field.id,
                slugfield: field.key,
                fulluri: false
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
     * Start generating slugs from uses fields.
     *
     * @private
     * @function startAutoGeneration
     * @memberof Bolt.fields.slug
     *
     * @param {FieldData} field - Field data.
     */
    function startAutoGeneration(field) {
        $.each(field.uses, function (i, bindField) {
            $('#' + bindField).on('propertychange.bolt input.bolt change.bolt', function () {
                var usesValue = [];

                $.each(field.uses, function (i, useField) {
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

                clearTimeout(timeout[field.key]);
                timeout[field.key] = setTimeout(
                    function () {
                        getUriAjax(field, usesValue.join(' '));
                    },
                    200
                );
            }).trigger('change.bolt');
        });
    }

    /**
     * Stop generating slugs from uses fields.
     *
     * @private
     * @function stopAutoGeneration
     * @memberof Bolt.fields.slug
     *
     * @param {FieldData} field - Field data.
     */
    function stopAutoGeneration(field) {
        $.each(field.uses, function (i, name) {
            $('#' + name).off('propertychange.bolt input.bolt change.bolt');
        });
        clearTimeout(timeout[field.key]);
    }

    // Apply mixin container
    bolt.fields.slug = slug;

})(Bolt || {}, jQuery);
