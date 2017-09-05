/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($) {
    'use strict';

    /**
     * Image field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldImage
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.fieldFile
     */
    $.widget('bolt.fieldImage', $.bolt.fieldFile, /** @lends jQuery.widget.bolt.fieldImage.prototype */ {

        _create: function () {
            this._super();

            // Until content field returns file objects, fake initially selected data here.
            var input = $('input.path', this.element);
            var selected = input.data('selected');
            selected.previewUrl = this.element.find('img').attr('src');
        },

        _onPreview: function (event, file) {
            this._super(event, file);
            this.element.find('img').attr('src', file.previewUrl);
        },

        _onSelect: function (event, file) {
            this._super(event, file);
            this.element.find('img').attr('src', file.previewUrl);
        },

        _onClose: function () {
            this._super();
            var input = $('input.path', this.element);
            this.element.find('img').attr('src', input.data('selected').previewUrl);
        },

        _onClear: function () {
            this._super();
            var img = this.element.find('img');
            img.attr('src', img.data('defaultUrl'));
        },
    });
})(jQuery, Bolt);
