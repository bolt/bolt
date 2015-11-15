/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Templateselect field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldTemplateselect
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldTemplateselect', /** @lends jQuery.widget.bolt.fieldTemplateselect.prototype */ {
        /**
         * Default options.
         *
         * @property {boolean}  currentHas     - Currently a template is selected
         * @property {string}   current        - The currently selected template
         * @property {string[]} fieldTemplates - List of available templates
         */
        options: {
            currentHas:       false,
            current:          '',
            fieldTemplates:   []
        },

        /**
         * The constructor of the templateselect field widget.
         *
         * @private
         */
        _create: function () {
            var self = this,
                select = self.element.find('select'),
                warning = self.element.find('p'),
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
            select.on('change', function () {
                container.addClass('hidden');
                warning
                    .html('')
                    .removeClass('text-danger');

                if (select.val() !== self.options.current) {
                    if (self.options.currentHas) {
                        warning.html(
                            bolt.data('field.templateselect.template.warning', {
                                '%STATUS%': bolt.data('field.templateselect.message.status'),
                                '%MESSAGE%': bolt.data('field.templateselect.message.warning')
                            }
                        ));
                        warning.addClass('text-danger');
                        container.removeClass('hidden');
                    } else if (self.options.fieldTemplates.indexOf(select.val()) > -1) {
                        warning.html(bolt.data('field.templateselect.message.change'));
                        container.removeClass('hidden');
                    }
                }
            });
        }
    });
})(jQuery, Bolt);
