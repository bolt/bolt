/**
 * See (http://jquery.com/).
 * @name jQuery
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 */

/**
 * See (http://jquery.com/)
 * @name widget
 * @class
 * See the jQuery Library  (http://jquery.com/) for full details. This just
 * documents the function and classes that are added to jQuery by this plug-in.
 * @memberOf jQuery
 */

/**
 * See (http://jquery.com/)
 * @name bolt
 * @class
 * @memberOf jQuery.widget
 * @param {object} $ - Global jQuery object
 * @param {object} bolt - Global Bolt object
 */
(function ($, bolt) {
    'use strict';

    /**
     * Bolt listing.
     *
     * @class listing
     * @memberOf jQuery.widget.bolt
     * @license http://opensource.org/licenses/mit-license.php MIT License
     * @author rarila
     */
    $.widget('bolt.listing', /** @lends jQuery.widget.bolt.listing */ {
        /**
         * The constructor of the listing widget.
         *
         * @private
         */
        _create: function () {
            this.csrfToken  = this.element.data('bolt_csrf_token');
            this.contentType = this.element.data('contenttype');

            this.element.find('table.listing tbody').listingpart();
        },

        /**
         * Execute commands on triggered button.
         *
         * @param {string} action - Triggered action (Allowed: 'delete').
         * @param {array} ids - Array of ids to perform the action on.
         * @param {string} buttonText - Button text to be displayed on ok button.
         */
        modifyRecords: function (action, ids, buttonText) {
            var self = this,
                modifications = {},
                actions = {
                    'delete': {
                        'name': bolt.data('recordlisting.action.delete'),
                        'cmd': {'delete': null}
                    },
                    'publish': {
                        'name': bolt.data('recordlisting.action.publish'),
                        'cmd': {'modify': {'status': 'published'}}
                    },
                    'depublish': {
                        'name': bolt.data('recordlisting.action.depublish'),
                        'cmd': {'modify': {'status': 'held'}}
                    },
                    'draft': {
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

            // Remove when done:
            msg = msg + '<hr><b style="color:red;">Anti CSRF token functionality still disabled ' +
                'in Bolt\Controller\Async\Records::modify</b>';

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
                        className: 'btn-primary',
                        callback: function () {
                            var url = bolt.conf('paths.async') + 'content/action' + window.location.search;

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
                                    self.element.find('table.listing tbody').listingpart();

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
                    }
                }
            });
        }
    });
})(jQuery, Bolt);
