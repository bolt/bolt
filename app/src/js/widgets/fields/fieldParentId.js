/* eslint no-console: ["error", { allow: ["error"] }] */
/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($) {
    'use strict';

    /**
     * Parent ID field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author mrenigma
     *
     * @class parentId
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldParentId', $.bolt.baseField, /** @lends jQuery.widget.bolt.parentId.prototype */ {
        /**
         * Default options.
         *
         * @property {string|null} contentId - Content Id
         * @property {string}      key       - The field key
         * @property {string}      slug      - Content slug
         * @property {Array}       uses      - Fields used to automatically generate a slug
         */
        options : {
            contentId : null,
            key       : ''
        },

        /**
         * The constructor of the slug field widget.
         *
         * @private
         */
        _create : function () {
            var self = this, fieldset = this.element;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.parentId.prototype
             * @private
             *
             * @property {Object} form  - The form this input is part of
             * @property {Object} group - Group container
             * @property {Object} data  - Data field
             * @property {Object} uses  - Collection of uses fields
             */
            this._ui = {
                form  : this.element.closest('form'),
                group : fieldset.find('.input-group'),
                data  : fieldset.find('input'),
                uses  : $()
            };

            /**
             * A timeout.
             *
             * @type {number}
             * @name _timeout
             * @memberOf jQuery.widget.bolt.parentId.prototype
             * @private
             */
            this._timeout = 0;

            // Bind events.
            this._on({
                'click li.lock'  : function (event) {
                    event.preventDefault();
                    this._setMode(mode.lock);
                },
                'click li.link'  : function (event) {
                    event.preventDefault();
                    this._setMode(mode.link);
                },
                'click li.edit'  : function (event) {
                    event.preventDefault();
                    this._setMode(mode.edit);
                    this.element.find('input').focus();
                },
                'focusout input' : function () {
                    if (this._mode === mode.edit) {
                        this._setMode(mode.lock);
                    }
                }
            });
        },

        /**
         * Cleanup.
         *
         * @private
         */
        _destroy : function () {
            clearTimeout(this._timeout);
        },

        /**
         * Build the slug using the fields described in the uses parameter.
         *
         * @private
         */
        _buildSlug : function () {
            var self = this, term = '', value;

            console.log(self);

            $.each(self.options.uses, function (i, field) {
                value = $('#' + field).val();

                if (value) {
                    term += (typeof value === 'object' ? value.join(' ') : value) + ' ';
                }
            });

            clearTimeout(self._timeout);
            self._timeout = setTimeout(function () {
                self._getUri(term.trim());
            }, 200);
        },

        /**
         * Get URI for slug from remote.
         *
         * @private
         * @param {string} text - New slug text
         */
        _getUri : function (text) {
            var self = this, data = {
                title           : text,
                contenttypeslug : self.options.slug,
                id              : self.options.contentId,
                slugfield       : self.options.key,
                fulluri         : false
            };

            self._ui.group.addClass('loading');

            $.get(self._ui.data.data('createSlugUrl'), data)
                .done(function (uri) {
                    if (self._mode === mode.link) {
                        self._ui.data.val(uri);
                    }
                })
                .fail(function () {
                    console.error('Failed to get URI for ' + self.options.slug + '/' + self.options.contentId);
                })
                .always(function () {
                    self._ui.group.removeClass('loading');
                });
        }
    });
})(jQuery, Bolt);
