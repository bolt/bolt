/**
 * @param {Object} $    - Global jQuery object
 * @param {Object} bolt - The Bolt module
 */
(function ($, bolt) {
    'use strict';

    /**
     * BUIC listing widget.
     *
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     *
     * @class buicListing
     * @memberOf jQuery.widget.bolt
     */
    $.widget('bolt.buicListing', /** @lends jQuery.widget.bolt.buicListing.prototype */ {
        /**
         * The constructor of the listing widget.
         *
         * @private
         */
        _create: function () {
            this.csrfToken  = this.element.data('bolt_csrf_token');
            this.contentType = this.element.data('contenttype');
            this.contentTypeName = this.element.data('contenttype-name');

            this.element.find('table.listing tbody').buicListingPart();
        },

        /**
         * Execute commands on triggered button.
         *
         * @param {string} action - Triggered action.
         * @param {array} ids - Array of ids to perform the action on.
         * @param {string} buttonText - Button text to be displayed on ok button.
         */
        modifyRecords: function (action, ids, buttonText) {
            var self = this,
                modifications = {},
                actions = {
                    'delete': {
                        'safe': false,
                        'name': bolt.data('recordlisting.action.delete', {'%CTNAME%': this.contentTypeName}),
                        'cmd': {'delete': null}
                    },
                    'publish': {
                        'safe': true,
                        'name': bolt.data('recordlisting.action.publish', {'%CTNAME%': this.contentTypeName}),
                        'cmd': {'modify': {'status': 'published'}}
                    },
                    'depublish': {
                        'safe': true,
                        'name': bolt.data('recordlisting.action.depublish'),
                        'cmd': {'modify': {'status': 'held'}}
                    },
                    'draft': {
                        'safe': true,
                        'name': bolt.data('recordlisting.action.draft'),
                        'cmd': {'modify': {'status': 'draft'}}
                    }
                },
                msg;

            // Build POST data.
            modifications[self.contentType] = {};
            $(ids).each(function () {
                modifications[self.contentType][this] = actions[action].cmd;
            });

            // Build message:
            if (ids.length === 1) {
                msg = bolt.data('recordlisting.confirm.one');
            } else {
                msg = bolt.data('recordlisting.confirm.multi', {'%NUMBER%': '<b>' + ids.length + '</b>'});
            }
            msg = msg + '<br><br><b>' + bolt.data('recordlisting.confirm.no-undo') + '</b>';
            if (!actions[action].safe) {
                bootbox.dialog({
                    message: msg,
                    title: actions[action].name,
                    buttons: {
                        cancel: {
                            label: bolt.data('recordlisting.action.cancel'),
                            className: 'btn-default'
                        },
                        ok: {
                            label: buttonText,
                            className: 'btn-danger',
                            callback: function () {
                                self._sendModifyRecordsQuery(modifications);
                            }
                        }
                    }
                });
            } else {
                self._sendModifyRecordsQuery(modifications);
            }
        },
        /**
         * Send commands to modify records.
         *
         * @param {object} modifications - The modifications to be sent.
         */
        _sendModifyRecordsQuery: function (modifications) {
            var self = this,
                url = bolt.conf('paths.async') + 'content/action' + window.location.search;
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    'bolt_csrf_token': self.csrfToken,
                    'contenttype': self.contentType,
                    'actions': modifications
                },
                success: function (data) {
                    self.element.html(data);
                    self.element.find('table.listing tbody').buicListingPart();

                    /*
                        Commented out for now - it has to be decided if functionality is wanted
                    // Restore selection state.
                    $(table).find('td input:checkbox[name="checkRow"]').each(function () {
                        var id = $(this).parents('tr').attr('id').substr(5);

                        if (id && ids.indexOf(id) >= 0) {
                            this.checked = true;
                            self._rowSelection(this);
                        }
                    });
                    $(table).find('tbody').each(function () {
                        self._handleSelectionState(this);
                    });
                    */
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR.status + ' (' + errorThrown + '):');
                    console.log(JSON.parse(jqXHR.responseText));
                },
                dataType: 'html'
            });
        }
    });
})(jQuery, Bolt);
