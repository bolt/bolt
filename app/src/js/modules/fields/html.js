/**
 * Handling of HTML (ckeditor) fields.
 *
 * @mixin
 * @namespace Bolt.fields.html
 *
 * @param {Object} bolt - The Bolt module.
 */
(function (bolt) {
    'use strict';

    /**
     * Bolt.fields.file mixin container.
     *
     * @private
     * @type {Object}
     */
    var html = {};

    /**
     * Bind html field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.html
     *
     * @param {Object} fieldset
     * @param fconf
     */
    html.init = function (fieldset, fconf) {
        $(fieldset).find('.ckeditor').each(function(){
            bolt.ckeditor.add(this);
        })
    };

    // Apply mixin container
    bolt.fields.html = html;

})(Bolt || {}, jQuery);
