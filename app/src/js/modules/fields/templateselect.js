/**
 * Handling of templateselect input fields.
 *
 * @mixin
 * @namespace Bolt.fields.templateselect
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.fields.templateselect mixin container.
     *
     * @private
     * @type {Object}
     */
    var templateselect = {};

    /**
     * Bind templateselect field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.templateselect
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    templateselect.init = function (fieldset, fconf) {
        var select = $(fieldset).find('select'),
            warning = $(fieldset).find('p'),
            container = warning.parent();

        select.select2({
            width: '100%',
            allowClear: true,
            placeholder: {
                id: '',
                text: bolt.data('field.templateselect.text.default')
            },
            minimumResultsForSearch: Infinity
        });

        // Warn the user of potential template field changes if they change a templateselect field.
        select.change(function() {
            container.addClass('hidden');
            warning.html('').removeClass('text-danger');

            if (select.val() !== fconf.current) {
                if (fconf.currentHas) {
                    warning.html(
                        bolt.data('field.templateselect.template.warning', {
                            '%STATUS%': bolt.data('field.templateselect.message.status'),
                            '%MESSAGE%': bolt.data('field.templateselect.message.warning')
                        }
                    ));
                    warning.addClass('text-danger');
                    container.removeClass('hidden');
                } else if (fconf.fieldTemplates.indexOf(select.val()) > -1) {
                    warning.html(bolt.data('field.templateselect.message.change'));
                    container.removeClass('hidden');
                }
            }
        });
    };

    // Apply mixin container
    bolt.fields.templateselect = templateselect;

})(Bolt || {}, jQuery);
