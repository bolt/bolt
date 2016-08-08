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
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget(
        'bolt.fieldTemplateselect',
        $.bolt.baseField,
        /** @lends jQuery.widget.bolt.fieldTemplateselect.prototype */ {
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

                            // Iterate over the templatefields, check if they're all empty. Note:
                            // We're conveniently forgetting <select> elements here, because we
                            // can't 'guess' if the first option also means "nothing selected".
                            var templatefieldsEmpty = true;
                            $('[name^="templatefields"]').each(function () {
                                if ($(this).attr('type') !== 'checkbox' && $(this).prop('tagName') !== "SELECT" &&
                                    $(this).val() !== '') {
                                    templatefieldsEmpty = false;
                                }
                                if ($(this).attr('type') === 'checkbox' && $(this).prop('checked')) {
                                    templatefieldsEmpty = false;
                                }
                            });

                            if (templatefieldsEmpty) {
                                // we can simply hide the "template" tab and don't need the notice.
                                $('#tabindicator-tab-template').hide();
                            } else {
                                // Show the notice
                                warning.addClass('text-danger');
                                container.removeClass('hidden');
                            }

                        } else if (self.options.fieldTemplates.indexOf(select.val()) > -1) {
                            warning.html(bolt.data('field.templateselect.message.change'));
                            container.removeClass('hidden');
                            $('#tabindicator-tab-template').show();
                        }
                    }
                });
            }
        }
    );
})(jQuery, Bolt);
