/**
 * Handling of templateselect input fields.
 *
 * @mixin
 * @namespace Bolt.fields.templateselect
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} _ - Underscore.
 */
(function (bolt, $, _) {

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
        var select = $(fieldset).find('select');
        var warning = $(fieldset).find('p');
        var config = select.data('stats');
        var container = warning.parent();

        // Warn the user of potential template field changes if they change a templateselect field.
        select.change(function() {
            container.addClass('hidden');
            warning.html('').removeClass('text-danger');

            if (select.val() !== config.current) {
                if (config.currentHas) {
                    warning.html(
                        bolt.data('field.templateselect.template.warning', {
                            '%STATUS%': bolt.data('field.templateselect.message.status'),
                            '%MESSAGE%': bolt.data('field.templateselect.message.warning')
                        }
                    ));
                    warning.addClass('text-danger');
                    container.removeClass('hidden');
                } else if (_.contains(config.fieldTemplates, select.val())) {
                    warning.html(bolt.data('field.templateselect.message.change'));
                    container.removeClass('hidden');
                }
            }
        });
    };

    // Apply mixin container
    bolt.fields.templateselect = templateselect;

})(Bolt || {}, jQuery, _);
