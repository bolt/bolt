/**
 * Main mixin for the Bolt module.
 *
 * @mixin
 * @namespace Bolt.fields
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.extend mixin container.
     *
     * @private
     * @type {Object}
     */
    var fields = {};

    /**
     * Initializes the fields, optionally based on a context.
     *
     * @function init
     * @memberof Bolt.fields
     * @param context
     */
    fields.init = function(context) {

        if (typeof context === 'undefined') {
            context = $(document.documentElement);
        }
        // Init fieldsets
        $('[data-bolt-field]', context).each(function () {
            console.log($(this));
            var type = $(this).data('bolt-field'),
                conf = $(this).data('bolt-fconf');

            switch (type) {
                case 'categories':
                    bolt.fields.categories.init(this);
                    break;

                case 'geolocation':
                    bolt.fields.geolocation.init(this, conf);
                    break;

                case 'meta':
                    bolt.fields.meta.init(this);
                    break;

                case 'relationship':
                    bolt.fields.relationship.init(this);
                    break;

                case 'repeater':
                    bolt.fields.repeater.init(this);
                    break;

                case 'select':
                    bolt.fields.select.init(this, conf);
                    break;

                case 'slug':
                    bolt.fields.slug.init(this, conf);
                    break;

                case 'tags':
                    bolt.fields.tags.init(this, conf);
                    break;

                case 'templateselect':
                    bolt.fields.templateselect.init(this, conf);
                    break;

                case 'checkbox':
                case 'date':
                case 'datetime':
                case 'file':
                case 'filelist':
                case 'float':
                case 'grouping':
                case 'html':
                case 'image':
                case 'imagelist':
                case 'integer':
                case 'markdown':
                case 'text':
                case 'textarea':
                case 'video':
                    // Not implemented yet.
                    break;

                default:
                    console.log('Unknown field type: ' + type);
            }

        });
    };



    // Apply mixin container.
    bolt.fields = fields;

})(Bolt || {}, jQuery);
