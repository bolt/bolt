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
         * @property {boolean}     isEmpty   - Slug is not set?
         * @property {string}      key       - The field key
         * @property {string}      slug      - Content slug
         * @property {Array}       uses      - Fields used to automatically generate a slug
         */
        options: {
            contentId: null,
            isEmpty:   true,
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
                fieldset = self.element;

            /**
             * Refs to UI elements of this widget.
             *
             * @type {Object}
             * @name _ui
             * @memberOf jQuery.widget.bolt.fieldSlug.prototype
             * @private
             *
             * @property {Object} group  - Group container.
             * @property {Object} show   - Slug display.
             * @property {Object} prefix - URL prefix (like `/contenttype/` ).
             * @property {Object} data   - Data field.
             * @property {Object} lock   - Lock button.
             * @property {Object} unlock - Unlock button.
             * @property {Object} edit   - Edit button.
             */
            this._ui = {
                group:  fieldset.find('.input-group'),
                show:   fieldset.find('em'),
                prefix: fieldset.find('span.prefix'),
                data:   fieldset.find('input'),
                lock:   fieldset.find('li.lock a'),
                unlock: fieldset.find('li.unlock a'),
                edit:   fieldset.find('li.edit a')
            };

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

            self._ui.lock.on('click', function () {
                self._lock();
            });

            self._ui.unlock.on('click', function () {
                self._unlock(true);
            });

            self._ui.group.on('dblclick', function () {
                self._unlock();
            });

            self._ui.edit.on('click', function () {
                self._edit();
            });

            if (self.options.isEmpty) {
                self._unlock();
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
         * Locks the slug field.
         *
         * @private
         */
        _lock: function () {
            this._ui.group
                .removeClass('unlocked')
                .addClass('locked');

            this._stopAutoGeneration();
        },

        /**
         * Unlocks the slug field.
         *
         * @private
         * @param {boolean} [doConfirm=false] - Open a confirmation dialog before unlocking
         */
        _unlock: function (doConfirm) {
            if (doConfirm !== true || confirm(bolt.data('field.slug.message.unlock'))) {
                this._ui.group
                    .removeClass('locked')
                    .addClass('unlocked');

                this._startAutoGeneration();
            }
        },

        /**
         * Edit the slug.
         *
         * @private
         */
        _edit: function () {
            var newslug = prompt(bolt.data('field.slug.message.set'), this._ui.data.val());

            if (newslug) {
                this._lock();
                this._getUri(newslug);
            }
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
                    self._ui.show.html(uri);
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
        _startAutoGeneration: function () {
            var self = this,
                form = self.element.closest('form');

            $.each(self.options.uses, function (i, bindField) {
                $('[name="' + bindField + '"]', form).on('propertychange.bolt input.bolt change.bolt', function () {
                    var usesValue = [];

                    $.each(self.options.uses, function (i, useField) {
                        var field = $('[name="' + useField + '"]', form);

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
                }).trigger('change.bolt');
            });
        },

        /**
         * Stop generating slugs from uses fields.
         *
         * @private
         */
        _stopAutoGeneration: function () {
            var form = this.element.closest('form');

            clearTimeout(this._timeout);

            $.each(this.options.uses, function (i, name) {
                $('[name="' + name + '"]', form).off('propertychange.bolt input.bolt change.bolt');
            });
        }
    });
})(jQuery, Bolt);
