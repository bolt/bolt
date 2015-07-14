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

        if (fconf.groupBy) {
            funcFormatSelection = function (item) {
                return $(item.element).parent().attr('label') + ': ' + item.text;
            };
        }

        select.select2({
            placeholder: bolt.data('field.relationship.text.placeholder'),
            allowClear: true,
            formatSelection: funcFormatSelection
        });
    };

    // Apply mixin container
    bolt.fields.relationship = relationship;

})(Bolt || {}, jQuery);
