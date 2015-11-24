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
             * @property {Object} info  - Linkinfo block
             * @property {Object} uses  - Collection of uses fields
             */
            this._ui = {
                form:   this.element.closest('form'),
                group:  fieldset.find('.input-group'),
                data:   fieldset.find('input'),
                info:  fieldset.find('.linkinfo'),
                uses:   $()
            };

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
            this._mode =
                (this._ui.group.hasClass('locked') ? mode.lock : 0) +
                (this._ui.group.hasClass('linked') ? mode.link : 0) +
                (this._ui.group.hasClass('editable') ? mode.edit : 0);

            if (this._mode === mode.linked) {
                this._startGeneration();
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
                    this._setMode(mode.lock);
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

            this._ui.data.prop('readonly', !modeIsEditable);

            if (modeIsLinked) {
                this._startGeneration();
            } else if (this._mode === mode.link) {
                this._stopGeneration();
            }

            this._mode = setMode;
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

            self._buildSlug();

            self._on(self._ui.uses, {
                'change': function () {
                    self._buildSlug();
                },
                'input': function () {
                    self._buildSlug();
                }
            });
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

            $.each(self._ui.uses, function (i, field) {
                value = $(field).val();

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
         * Stop generating slugs from uses fields.
         *
         * @private
         */
        _stopGeneration: function () {
            var self = this;

            clearTimeout(this._timeout);

            $(self.options.uses).each(function () {
                self._off($(this), 'change');
                self._off($(this), 'input');
            });
        }
    });
})(jQuery, Bolt);
