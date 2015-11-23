/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Slug field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldSlug
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.fieldSlug', /** @lends jQuery.widget.bolt.fieldSlug.prototype */ {
        /**
         * Default options.
         *
         * @property {string|null} contentId - Content Id
         * @property {string}      key       - The field key
         * @property {string}      slug      - Content slug
         * @property {Array}       uses      - Fields used to automatically generate a slug
         */
        options: {
            contentId: null,
            key:       '',
            slug:      '',
            uses:      []
        },

        /**
         * The constructor of the slug field widget.
         *
         * @private
         */
        _create: function () {
            var fieldset = this.element;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             *
             * @property {Object} form   - The form this input is part of
             * @property {Object} group  - Group container
             * @property {Object} data   - Data field
             */
            this._ui = {
                form:   this.element.closest('form'),
                group:  fieldset.find('.input-group'),
                data:   fieldset.find('input')
            };

            /**
             * Slug is generated, if true.
             *
             * @type {boolean}
             * @name _timeout
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             */
            this._generated = this._ui.group.hasClass('generated');

            /**
             * A timeout.
             *
             * @type {number}
             * @name _timeout
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             */
            this._timeout = 0;

            // Bind events.
            this._on({
                'click button': function () {
                    this._toggleGeneration(true);
                },
                'dblclick button': function () {
                    this._toggleGeneration();
                }
            });

            if (this._generated) {
                this._startGeneration();
            }
        },

        /**
         * Cleanup.
         *
         * @private
         */
        _destroy: function () {
            clearTimeout(this._timeout);
        },

        /**
         * Toogle automatic generation of the slug field.
         *
         * @private
         * @param {boolean} [doConfirm=false] - Open a confirmation dialog before unlocking
         */
        _toggleGeneration: function (doConfirm) {
            if (this._generated) {
                this._generated = false;
                this._stopGeneration();
            } else if (doConfirm !== true || confirm(bolt.data('field.slug.message.unlock'))) {
                this._generated = true;
                this._startGeneration();
            }
            this._ui.group.toggleClass('generated', this._generated);
            this._ui.data.prop('readonly', this._generated);
        },

        /**
         * Get URI for slug from remote.
         *
         * @private
         * @param {string} text - New slug text
         */
        _getUri: function (text) {
            var self = this,
                data = {
                    title:           text,
                    contenttypeslug: self.options.slug,
                    id:              self.options.contentId,
                    slugfield:       self.options.key,
                    fulluri:         false
                };

            $.get(bolt.conf('paths.async') + 'makeuri', data)
                .done(function (uri) {
                    self._ui.data.val(uri);
                })
                .fail(function () {
                    console.log('failed to get an URI');
                });
        },

        /**
         * Start generating slugs from uses fields.
         *
         * @private
         */
        _startGeneration: function () {
            var self = this;

            $.each(self.options.uses, function (i, bindField) {
                $('[name="' + bindField + '"]', self._ui.form)
                    .on('propertychange.bolt input.bolt change.bolt', function () {
                        self._buildSlug();
                    })
                    .trigger('change.bolt');
            });
        },

        /**
         * Build the slug using the fields described in the uses parameter.
         *
         * @private
         */
        _buildSlug: function () {
            var self = this,
                usesValue = [];

            $.each(self.options.uses, function (i, useField) {
                var field = $('[name="' + useField + '"]', self._ui.form);

                if (field.is('select')) {
                    field.find('option:selected').each(function(i, option) {
                        if (option.text !== '') {
                            usesValue.push(option.text);
                        }
                    });
                } else if (field.val()) {
                    usesValue.push(field.val());
                }
            });

            clearTimeout(self._timeout);
            self._timeout = setTimeout(
                function () {
                    self._getUri(usesValue.join(' '));
                },
                200
            );
        },

        /**
         * Stop generating slugs from uses fields.
         *
         * @private
         */
        _stopGeneration: function () {
            var self = this;

            clearTimeout(this._timeout);

            $.each(this.options.uses, function (i, name) {
                $('[name="' + name + '"]', self._ui.form).off('propertychange.bolt input.bolt change.bolt');
            });
        }
    });
})(jQuery, Bolt);
