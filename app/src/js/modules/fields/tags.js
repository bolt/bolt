/**
 * Handling of tags input fields.
 *
 * @mixin
 * @namespace Bolt.fields.tags
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {

    /**
     * Bolt.fields.tags mixin container.
     *
     * @private
     * @type {Object}
     */
    var tags = {};

    /**
     * Bind tags field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.tags
     *
     * @param {Object} fieldset
     * @param {FieldConf} fconf
     */
    tags.init = function (fieldset, fconf) {
    };

    // Apply mixin container
    bolt.fields.tags = tags;

})(Bolt || {}, jQuery);
