/**
 * Set up editcontent stuff
 *
 * @mixin
 * @namespace Bolt.editcontent
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} window - Global window object.
 * @param {Object} moment - Global moment object.
 * @param {Object} bootbox - Global bootbox object.
 * @param {Object|undefined} ckeditor - CKEDITOR global or undefined.
 */
(function (bolt, $, window, moment, bootbox, ckeditor) {
    'use strict';

    /**
     * Bind data.
     *
     * @typedef {Object} BindData
     * @memberof Bolt.editcontent
     *
     * @property {string} bind - Always 'editcontent'.
     * @property {boolean} hasGroups - Has groups.
     * @property {string} msgNotSaved - Message when entry could not be saved.
     * @property {boolean} newRecord - Is new Record?
     * @property {string} savedOn - "Saved on" template.
     * @property {string} singularSlug - Contenttype slug.
     * @property {string} previewUrl - The preview url.
     */

    /**
     * Bolt.editcontent mixin container.
     *
     * @private
     * @type {Object}
     */
    var editcontent = {};

    /**
     * Gets the current value of an input element processed to be comparable
     *
     * @static
     * @function getComparable
     * @memberof Bolt.editcontent
     *
     * @param {Object} item - Input element
     *
     * @returns {string|undefined}
     */
    function getComparable(item) {
        var val;

        if (item.name) {
            val = $(item).val();
            if (item.type === 'select-multiple') {
                val = JSON.stringify(val);
            }
        }

        return val;
    }

    /**
     * Detect if changes were made to the content.
     *
     * @static
     * @function hasChanged
     * @memberof Bolt.editcontent
     *
     * @returns {boolean}
     */
    function hasChanged() {
        var changes = 0;

        $('form[name="content_edit"]').find('input, textarea, select').each(function () {
            if($(this).attr('name') !== 'content_edit[save]') {
                if (this.type === 'textarea' && $(this).hasClass('ckeditor')) {
                    if (ckeditor.instances[this.id].checkDirty()) {
                        changes++;
                    }
                } else {
                    var val = getComparable(this);
                    if (val !== undefined && $(this).data('watch') !== val) {
                        changes++;
                    }
                }
            }
        });

        return changes > 0;
    }

    /**
     * Remember current state of content for change detection.
     *
     * @static
     * @function watchChanges
     * @memberof Bolt.editcontent
     */
    function watchChanges() {
        $('form[name="content_edit"]').find('input, textarea, select').each(function () {
            if (this.type === 'textarea' && $(this).hasClass('ckeditor')) {
                if (ckeditor.instances[this.id].checkDirty()) {
                    ckeditor.instances[this.id].updateElement();
                    ckeditor.instances[this.id].resetDirty();
                }
            } else {
                var val = getComparable(this);
                if (val !== undefined) {
                    $(this).data('watch', val);
                }
            }
        });

        // Initialize handler for 'closing window'
        window.onbeforeunload = function () {
            if (hasChanged() || bolt.liveEditor.active) {
                return bolt.data('editcontent.msg.change_quit');
            }
        };
    }

    /**
     * Forget current state of content, because we're going to save and leave this page.
     *
     * @static
     * @function unWatchChanges
     * @memberof Bolt.editcontent
     */
    function unWatchChanges() {
        window.onbeforeunload = null;
    }

    /**
     * Disable the "save" buttons, to indicate stuff is being done in the background.
     *
     * @static
     * @function indicateSavingAction
     * @memberof Bolt.editcontent
     */
    function indicateSavingAction() {
        $('#sidebar_save, #content_edit_save, #live_editor_save').addClass('disabled');
        $('#sidebar_save i, #content_edit_save i').addClass('fa-spin fa-spinner');
        $('p.lastsaved').text(bolt.data('editcontent.msg.saving'));
    }

    /**
     * Set validation handlers.
     *
     * @static
     * @function initValidation
     * @memberof Bolt.editcontent
     */
    function initValidation() {
        var editForm = $('form[name="content_edit"]');

        // Set handler to validate form submit.
        editForm
            .attr('novalidate', 'novalidate')
            .on('submit', function (event) {
                var valid = bolt.validation.run(this);

                $(this).data('valid', valid);
                if (!valid){
                    event.preventDefault();

                    return false;
                }
            }
        );

        // Basic custom validation handler.
        editForm.on('boltvalidate', function () {
            var valid = bolt.validation.run(this);

            $(this).data('valid', valid);

            return valid;
        });
    }

    /**
     * Initialize persistent tab groups.
     *
     * @static
     * @function initTabGroups
     * @memberof Bolt.editcontent
     */
    function initTabGroups() {
        // Show selected tab.
        var hash = window.location.hash,
            filerTabs = $('#filtertabs');

        if (hash) {
            filerTabs.find('a[href="#tab-' + hash.replace(/^#/, '') + '"]').tab('show');
        }

        // Set Tab change handler.
        filerTabs.find('a').click(function () {
            var top;

            $(this).tab('show');
            top = $('body').scrollTop();
            window.location.hash = this.hash.replace(/^#tab-/, '');
            $('html,body').scrollTop(top);
        });
    }

    /**
     * Initialize page preview button.
     *
     * @static
     * @function initPreview
     * @memberof Bolt.editcontent
     */
    function initPreview() {
        var editForm = $('form[name="content_edit"]');

        // Enable the preview buttons, as they are useless without JavaScript
        editForm.find('#sidebar_preview').attr('disabled', false);
        editForm.find('#content_edit_preview').attr('disabled', false);

        $('#sidebar_preview').bind('click', function () {
            $('#content_edit_preview').trigger('click');
        });

        // To preview the page, we set the target of the form to a new URL, and open it in a new window.
        $('#content_edit_preview').bind('click', function (e) {
            var newAction = $(e.target).data('url');

            e.preventDefault();
            editForm.attr('action', newAction).attr('target', '_blank').submit();
            editForm.attr('action', '').attr('target', '_self');
        });
    }

    /**
     * Initialize the live editor button
     *
     * @static
     * @function initLiveEditor
     * @memberof Bolt.editcontent
     */
    function initLiveEditor() {
    }

    /**
     * Initialize delete button from the editcontent page.
     *
     * @static
     * @function initDelete
     * @memberof Bolt.editcontent
     */
    function initDelete() {
        $('#sidebar_delete').bind('click', function () {
            $('#content_edit_delete').trigger('click');
        });

        $('#content_edit_delete').bind('click', function (e) {
            e.preventDefault();

            var button = this;
            bootbox.confirm(
                bolt.data('editcontent.delete'),
                function (confirmed) {
                    $('.alert').alert(); // Dismiss alert messages
                    if (confirmed === true) {
                        var editForm = $('form[name="content_edit"]');

                        // We don't care about changes, the delete is confirmed.
                        window.onbeforeunload = null;

                        bolt.actions.submit(editForm, button);
                    }
                }
            );
        });
    }

    /**
     * Initialize "save and return to overview" button handlers.
     *
     * @static
     * @function initSaveReturnToOverview
     * @memberof Bolt.editcontent
     */
    function initSaveReturnToOverview() {
        $('#sidebar_save_return').bind('click', function () {
            $('#content_edit_save_return').trigger('click');
        });

        $('#content_edit_save_return').bind('click', function () {
            indicateSavingAction();
            unWatchChanges();
            // Note, no 'actions.submit' here, because we're using a plain "submit" in the form.
        });
    }

    /**
     * Initialize "save and create new" button handlers.
     *
     * @static
     * @function initSaveCreateNew
     * @memberof Bolt.editcontent
     */
    function initSaveCreateNew() {
        $('#sidebar_save_create').bind('click', function () {
            $('#content_edit_save_create').trigger('click');
        });

        $('#content_edit_save_create').bind('click', function () {
            indicateSavingAction();
            unWatchChanges();
            bolt.actions.submit($('form[name="content_edit"]'), this);
        });
    }

    /**
     * Initialize "save" button handlers.
     *
     * Clicking the button either triggers an "ajaxy" post, or a regular post which returns to this page.
     * The latter happens if the record doesn't exist yet, so it doesn't have an id yet.
     *
     * @static
     * @function initSave
     * @memberof Bolt.editcontent
     *
     * @fires "Bolt.Content.Save.Start"
     * @fires "Bolt.Content.Save.Done"
     * @fires "Bolt.Content.Save.Fail"
     * @fires "Bolt.Content.Save.Always"
     *
     * @param {BindData} data - Editcontent configuration data
     */
    function initSave(data) {
        $('#sidebar_save').bind('click', function () {
            $('#content_edit_save').trigger('click');
        });

        $('#content_edit_save').bind('click', function (e) {
            e.preventDefault();

            var editForm = $('form[name="content_edit"]');

            // Trigger form validation
            editForm.trigger('boltvalidate');
            // Check validation
            if (!editForm.data('valid')) {
                return false;
            }

            var newrecord = data.newRecord,
                savedon = data.savedon,
                msgNotSaved = data.msgNotSaved;

            indicateSavingAction();

            if (newrecord) {
                watchChanges();

                if (bolt.liveEditor.active) {
                    bolt.liveEditor.stop();
                }

                if (data.duplicate) {
                    window.onbeforeunload = null;
                }

                // New record. Do a regular post, and expect to be redirected back to this page.
                bolt.actions.submit(editForm, this);
            } else {
                watchChanges();

                // Trigger save started event
                bolt.events.fire('Bolt.Content.Save.Start');

                // Existing record. Do an 'ajaxy' post to update the record.
                // Let the controller know we're calling AJAX and expecting to be returned JSON.
                var button = $(e.target);
                var postData = editForm.serialize()
                    + '&' + encodeURI(button.attr('name'))
                    + '=' + encodeURI(button.attr('value'))
                ;
                $.post('', postData)
                    .done(function (data) {
                        bolt.events.fire('Bolt.Content.Save.Done', {form: data});

                        // Submit was successful, disable warning.
                        window.onbeforeunload = null;

                        $('p.lastsaved')
                            .removeClass('alert alert-danger')
                            .html(savedon)
                            .find('strong')
                            .text(moment(data.datechanged).format('MMM D, HH:mm'))
                            .end()
                            .find('.buic-moment')
                            .buicMoment()
                            .buicMoment('set', data.datechanged);

                        var elSelected = $('#statusselect').find('option:selected');
                        $('a#lastsavedstatus strong').html(
                            '<i class="fa fa-circle status-' + elSelected.val() + '"></i> ' + elSelected.text()
                        );
                        // Display the 'save succeeded' icon in the buttons
                        $('#sidebar_save i, #content_edit_save i')
                            .removeClass('fa-flag fa-spin fa-spinner fa-exclamation-triangle')
                            .addClass('fa-check');

                        // Update anything changed by POST_SAVE handlers, as well as the 'view saved version on site' link
                        if ($.type(data) === 'object') {
                            $.each(data, function (index, item) {

                                // Things like images are stored in JSON arrays
                                if ($.type(item) === 'object') {
                                    $.each(item, function (subindex, subitem) {
                                        $(':input[name="' + index + '[' + subindex + ']"]').val(subitem);
                                    });
                                } else if ($.type(item) === 'array') {
                                    // In 2.3 we return filelists, and imagelist
                                    // as an array of "objects"… because JSON…
                                    // and they now fail here because… JavaScript…
                                    // so we're catching arrays and ignoring
                                    // them, someone else can fix this!
                                } else {
                                    var field = $(editForm.name).find('[name=' + index + ']');

                                    if (field.attr('type') === 'checkbox') {
                                        // A checkbox, so set with prop
                                        field.prop('checked', item === '1');
                                    } else {
                                        // Either an input or a textarea, so set with val
                                        field.val(item);
                                    }

                                    // If there is a CKEditor attached to our element, update it
                                    if (ckeditor && ckeditor.instances[index]) {
                                        ckeditor.instances[index].setData(
                                            item,
                                            {
                                                callback: function () {
                                                    this.resetDirty();
                                                }
                                            }
                                        );
                                    }
                                }
                            });

                            // Update the "View saved version on site" link.
                            $("a[data-href-placeholder]").each(function () {
                                var link = $(this).data('href-placeholder').replace('__replaceme', data['slug']);
                                $(this).attr('href', link);
                            });
                        }
                        // Update dates and times from new values
                        bolt.datetime.update();

                        watchChanges();
                    })
                    .fail(function (data) {
                        bolt.events.fire('Bolt.Content.Save.Fail');

                        var response = $.parseJSON(data.responseText);
                        var message = '<b>' + msgNotSaved + '</b><br><small>' + response.error.message + '</small>';

                        $('p.lastsaved')
                            .html(message)
                            .addClass('alert alert-danger');

                        // Display the 'save failed' icon in the buttons
                        $('#sidebar_save i, #content_edit_save i')
                            .removeClass('fa-flag fa-spin fa-spinner')
                            .addClass('fa-exclamation-triangle');
                    })
                    .always(function () {
                        bolt.events.fire('Bolt.Content.Save.Always');

                        // Re-enable buttons
                        window.setTimeout(function () {
                            $('#sidebar_save, #content_edit_save, #live_editor_save')
                                .removeClass('disabled').blur();
                        }, 1000);
                        window.setTimeout(function () {
                            $('#sidebar_save i, #content_edit_save i').addClass('fa-flag');
                        }, 5000);
                    }
                );
            }
        });
    }

    /**
     * Initialize keyboard shortcuts:
     * - Click 'save' in edit screen.
     *
     * @static
     * @function initKeyboardShortcuts
     * @memberof Bolt.editcontent
     */
    function initKeyboardShortcuts() {
        if ($('#content_edit_save').is('*')) {
            // Bind ctrl-s and meta-s for saving..
            $('body, input').bind('keydown', 'ctrl+s meta+s', function (event) {
                event.preventDefault();
                watchChanges();
                $('#content_edit_save').trigger('click');
            });

            // Initialize watching for changes on "the form".
            window.setTimeout(
                function () {
                    watchChanges();
                },
                1000
            );
        }
    }

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.editcontent
     *
     * @param {BindData} data - Editcontent configuration data
     */
    editcontent.init = function (data) {
        initValidation();
        initSave(data);
        initSaveReturnToOverview();
        initSaveCreateNew();
        initPreview();
        initLiveEditor();
        initDelete(data);
        initTabGroups();
        bolt.liveEditor.init(data);
        window.setTimeout(function () {
            initKeyboardShortcuts();
        }, 1000);
    };

    // Apply mixin container.
    bolt.editcontent = editcontent;

})(Bolt || {}, jQuery, window, moment, bootbox, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
