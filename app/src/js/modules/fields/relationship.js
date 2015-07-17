/**
 * Handling of relationship input fields.
 *
 * @mixin
 * @namespace Bolt.fields.relationship
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {

    /**
     * Bolt.fields.relationship mixin container.
     *
     * @private
     * @type {Object}
     */
    var relationship = {};

    /**
     * Bind relationship field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.relationship
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    relationship.init = function (fieldset, fconf) {
        var select = $(fieldset).find('select');

        select.select2({
            placeholder: bolt.data('field.relationship.text.placeholder'),
            allowClear: true,
            templateSelection: function (item) {
                var label = $(item.element).parent().attr('label');

                return (label ? label + ': ' : '') + item.text;
            }
        });
    };

    // Apply mixin container
    bolt.fields.relationship = relationship;

})(Bolt || {}, jQuery);
