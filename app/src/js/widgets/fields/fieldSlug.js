/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * Mode identifiers.
     *
     * @memberOf jQuery.widget.bolt.fieldSlug
     * @static
     * @type {Object.<string, number>}
     */
    var mode = {
        lock: 1,
        link: 2,
        edit: 3
    };

    /**
     * Slug field widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class fieldSlug
     * @memberOf jQuery.widget.bolt
     * @extends jQuery.widget.bolt.baseField
     */
    $.widget('bolt.fieldSlug', $.bolt.baseField, /** @lends jQuery.widget.bolt.fieldSlug.prototype */ {
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
            var self = this,
                fieldset = this.element;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             *
             * @property {Object} form  - The form this input is part of
             * @property {Object} group - Group container
             * @property {Object} data  - Data field
             * @property {Object} uses  - Collection of uses fields
             */
            this._ui = {
                form:   this.element.closest('form'),
                group:  fieldset.find('.input-group'),
                data:   fieldset.find('input'),
                uses:   $()
            };

            // Get all references to linked ellements.
            $('[name]', self._ui.form).each(function () {
                if (self.options.uses.indexOf(this.name.replace(/\[\]$/, '')) >= 0) {
                    self._ui.uses = self._ui.uses.add($(this));
                }
            });

            /**
             * A timeout.
             *
             * @type {number}
             * @name _timeout
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             */
            this._timeout = 0;

            /**
             * Slug is generated, if true.
             *
             * @type {number}
             * @name _mode
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             */
            this._mode = mode.lock;

            // Initialize modes.
            if (this._ui.group.hasClass('linked')) {
                this._setMode(mode.link);
            } else if (this._ui.group.hasClass('editable')) {
                this._mode = mode.edit;
            }

            // Bind events.
            this._on({
                'click li.lock': function (event) {
                    event.preventDefault();
                    this._setMode(mode.lock);
                },
                'click li.link': function (event) {
                    event.preventDefault();
                    this._setMode(mode.link);
                },
                'click li.edit': function (event) {
                    event.preventDefault();
                    this._setMode(mode.edit);
                    this.element.find('input').focus();
                },
                'focusout input': function () {
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
        _destroy: function () {
            clearTimeout(this._timeout);
        },

        /**
         * Update widgets visual and functional state.
         *
         * @private
         * @param {number} setMode - Mode to set
         */
        _setMode: function (setMode) {
            var modeIsLocked = setMode === mode.lock,
                modeIsLinked = setMode === mode.link,
                modeIsEditable = setMode === mode.edit;

            // Set dropdown button states.
            $('li.lock', this.element).toggleClass('disabled', modeIsLocked);
            $('li.link', this.element).toggleClass('disabled', modeIsLinked);
            $('li.edit', this.element).toggleClass('disabled', modeIsEditable);

            // Set container class.
            this._ui.group
                .toggleClass('locked', modeIsLocked)
                .toggleClass('linked', modeIsLinked)
                .toggleClass('edititable', modeIsEditable);

            // Show/hide edit warning.
            $('.warning', this.element).toggleClass('hidden', !modeIsEditable);

            // Toggle the input readonly.
            this._ui.data.prop('readonly', !modeIsEditable);

            // Start/stop generating slugs from uses fields.
            if (modeIsLinked) {
                this._buildSlug();

                this._on(this._ui.uses, {
                    'change': function () {
                        this._buildSlug();
                    },
                    'input': function () {
                        this._buildSlug();
                    }
                });
            } else if (this._timeout > 0) {
                clearTimeout(this._timeout);
                this._timeout = 0;

                this._off(this._ui.uses, 'change');
                this._off(this._ui.uses, 'input');
            }

            // Finally set new mode.
            this._mode = setMode;
        },

        /**
         * Build the slug using the fields described in the uses parameter.
         *
         * @private
         */
        _buildSlug: function () {
            var self = this,
                term = '',
                value;

            $.each(self.options.uses, function (i, field) {
                value = $('#' + field).val();

                if (value) {
                    term += (typeof value === 'object' ? value.join(' ') : value) + ' ';
                }
            });

            clearTimeout(self._timeout);
            self._timeout = setTimeout(
                function () {
                    self._getUri(term.trim());
                },
                200
            );
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

            self._ui.group.addClass('loading');

            $.get(bolt.conf('paths.async') + 'makeuri', data)
                .done(function (uri) {
                    if (self._mode === mode.link) {
                        self._ui.data.val(uri);
                    }
                })
                .fail(function () {
                    console.log('failed to get an URI');
                })
                .always(function () {
                    self._ui.group.removeClass('loading');
                });
        }
    });
})(jQuery, Bolt);
