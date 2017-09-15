/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Relationship field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldRelationship
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldRelationship', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldRelationship.prototype */ {
        /**
         * The constructor of the relationship field widget.
         *
         * @private
         */
        _create: function () {
            var select = this.element.find('select');

            select.select2({
                width: '100%',
                placeholder: {
                    id: '',
                    text: bolt.data('field.relationship.text.placeholder')
                },
                allowClear: true,
                templateSelection: function (item) {
                    var label = $(item.element).parent().attr('label');

                    return (label ? label + ': ' : '') + item.text;
                }
            });
        }
    });
})(jQuery, Bolt);
