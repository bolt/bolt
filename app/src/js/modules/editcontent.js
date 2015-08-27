/**
 * Setp up editcontent stuff
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
     */

    /**
     * Bolt.editcontent mixin container.
     *
     * @private
     * @type {Object}
     */
    var editcontent = {};

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
        initSave();
        initSaveNew();
        initSaveContinue(data);
        initPreview(data.singularSlug);
        initLiveEditor();
        initDelete();
        initTabGroups();
        bolt.liveEditor.init(data);
        window.setTimeout(function () {
            initKeyboardShortcuts();
        }, 1000);
    };

    /**
     * Set validation handlers.
     *
     * @static
     * @function initValidation
     * @memberof Bolt.editcontent
     */
    function initValidation() {
        // Set handler to validate form submit.
        $('#editcontent')
            .attr('novalidate', 'novalidate')
            .on('submit', function (event) {
                var valid = bolt.validation.run(this);

                $(this).data('valid', valid);
                if (!valid){
                    event.preventDefault();

                    return false;
                }
                // Submitting, disable warning.
                window.onbeforeunload = null;
            }
        );

        // Basic custom validation handler.
        $('#editcontent').on('boltvalidate', function () {
            var valid = bolt.validation.run(this);

            $(this).data('valid', valid);

            return valid;
        });
    }

    /**
     * Initialize persistent tabgroups.
     *
     * @static
     * @function initTabGroups
     * @memberof Bolt.editcontent
     */
    function initTabGroups() {
        // Show selected tab.
        var hash = window.location.hash;
        if (hash) {
            $('#filtertabs a[href="#tab-' + hash.replace(/^#/, '') + '"]').tab('show');
        }

        // Set Tab change handler.
        $('#filtertabs a').click(function () {
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
     *
     * @param {string} slug - Contenttype singular slug.
     */
    function initPreview(slug) {
        // To preview the page, we set the target of the form to a new URL, and open it in a new window.
        $('#previewbutton, #sidebarpreviewbutton').bind('click', function (e) {
            var newAction = bolt.conf('paths.root') + 'preview/' + slug;

            e.preventDefault();
            $('#editcontent').attr('action', newAction).attr('target', '_blank').submit();
            $('#editcontent').attr('action', '').attr('target', '_self');
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
        $('#deletebutton, #sidebardeletebutton').bind('click', function (e) {
            e.preventDefault();
            bootbox.confirm(
                bolt.data('editcontent.delete'),
                function (confirmed) {
                    $('.alert').alert(); // Dismiss alert messages
                    if (confirmed === true) {
                        var pathBolt = bolt.conf('paths.bolt'),
                            form = $('#id').closest('form'),
                            ctype = $('#contenttype').val(),
                            id = $('#id').val(),
                            token = form.find('input[name="bolt_csrf_token"]').val(),
                            url = pathBolt + 'content/deletecontent/' + ctype + '/' + id + '?bolt_csrf_token=' + token;

                        // Fire delete request.
                        $.ajax({
                            url: url,
                            type: 'GET',
                            success: function () {
                                window.location.href = pathBolt + 'overview/' + $('#contenttype').val();
                            }
                        });
                    }
                }
            );
        });
    }

    /**
     * Initialize save button handlers.
     *
     * @static
     * @function initSave
     * @memberof Bolt.editcontent
     */
    function initSave() {
        // Save the page.
        $('#sidebarsavebutton').bind('click', function () {
            $('#savebutton').trigger('click');
        });

        $('#savebutton').bind('click', function () {
            watchChanges();
        });
    }

    /**
     * Initialize "save and new " button handlers.
     *
     * @static
     * @function initSaveNew
     * @memberof Bolt.editcontent
     */
    function initSaveNew() {
        $('#sidebarsavenewbutton, #savenewbutton').bind('click', function () {
            watchChanges();

            // Do a regular post, and expect to be redirected back to the "new record" page.
            var newaction = '?returnto=saveandnew';
            $('#editcontent').attr('action', newaction).submit();
        });
    }

    /**
     * Initialize "save and continue" button handlers.
     *
     * Clicking the button either triggers an "ajaxy" post, or a regular post which returns to this page.
     * The latter happens if the record doesn't exist yet, so it doesn't have an id yet.
     *
     * @static
     * @function initSaveContinue
     * @memberof Bolt.editcontent
     *
     * @param {BindData} data - Editcontent configuration data
     */
    function initSaveContinue(data) {
        $('#sidebarsavecontinuebutton, #savecontinuebutton').bind('click', function (e) {
            e.preventDefault();

            // Trigger form validation
            $('#editcontent').trigger('boltvalidate');
            // Check validation
            if (!$('#editcontent').data('valid')) {
                return false;
            }

            var newrecord = data.newRecord,
                savedon = data.savedon,
                msgNotSaved = data.msgNotSaved;

            // Disable the buttons, to indicate stuff is being done.
            $('#sidebarsavecontinuebutton, #savecontinuebutton').addClass('disabled');
            $('#sidebarsavecontinuebutton i, #savecontinuebutton i').addClass('fa-spin fa-spinner');
            $('p.lastsaved').text(bolt.data('editcontent.msg.saving'));

            if (newrecord) {
                watchChanges();

                // New record. Do a regular post, and expect to be redirected back to this page.
                $('#editcontent').attr('action', '?returnto=new').submit();
            } else {
                watchChanges();

                // Existing record. Do an 'ajaxy' post to update the record.
                // Let the controller know we're calling AJAX and expecting to be returned JSON.
                $.post('?returnto=ajax', $('#editcontent').serialize())
                    .done(function (data) {
                        $('p.lastsaved').html(savedon);
                        $('p.lastsaved').find('strong').text(moment(data.datechanged).format('MMM D, HH:mm'));
                        bolt.buic.moment.set($('p.lastsaved').find('time'), data.datechanged);

                        $('a#lastsavedstatus strong').html(
                            '<i class="fa fa-circle status-' + $('#statusselect option:selected').val() + '"></i> ' +
                            $('#statusselect option:selected').text()
                        );

                        // Update anything changed by POST_SAVE handlers
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
                                    // Either an input or a textarea, so get by ID
                                    $('#' + index).val(item);

                                    // If there is a CKEditor attached to our element, update it
                                    if (ckeditor && ckeditor.instances[index]) {
                                        ckeditor.instances[index].setData(
                                            item,
                                            {
                                                callback: function() {
                                                    this.resetDirty();
                                                }
                                            }
                                        );
                                    }
                                }
                            });
                        }
                        // Update dates and times from new values
                        bolt.datetime.update();

                        watchChanges();
                    })
                    .fail(function(){
                        $('p.lastsaved').text(msgNotSaved);
                    })
                    .always(function(){
                        // Re-enable buttons
                        window.setTimeout(function(){
                            $('#sidebarsavecontinuebutton, #savecontinuebutton').removeClass('disabled').blur();
                            $('#sidebarsavecontinuebutton i, #savecontinuebutton i').removeClass('fa-spin fa-spinner');
                        }, 300);
                    });
            }
        });
    }

    /**
     * Initialize keyboard shortcuts:
     * - Click 'save' in Edit content screen.
     * - Click 'save' in "edit file" screen.
     *
     * @static
     * @function initKeyboardShortcuts
     * @memberof Bolt.editcontent
     */
    function initKeyboardShortcuts () {
        // We're on a regular 'edit content' page, if we have a sidebarsavecontinuebutton.
        // If we're on an 'edit file' screen,  we have a #saveeditfile
        if ($('#sidebarsavecontinuebutton').is('*') || $('#saveeditfile').is('*')) {

            // Bind ctrl-s and meta-s for saving..
            $('body, input').bind('keydown.ctrl_s keydown.meta_s', function (event) {
                event.preventDefault();
                watchChanges();
                $('#sidebarsavecontinuebutton, #saveeditfile').trigger('click');
            });

            // Initialize watching for changes on "the form".
            window.setTimeout(
                function () {
                    watchChanges();
                },
                1000
            );

            // Initialize handler for 'closing window'
            window.onbeforeunload = function () {
                if (hasChanged() || bolt.liveEditor.active) {
                    return bolt.data('editcontent.msg.change_quit');
                 }
            };
        }
    }

    /**
     * Remember current state of content for change detection.
     *
     * @static
     * @function watchChanges
     * @memberof Bolt.editcontent
     */
    function watchChanges() {
        $('form#editcontent').find('input, textarea, select').each(function () {
            if (this.type === 'textarea' && $(this).hasClass('ckeditor')) {
                if (ckeditor.instances[this.id].checkDirty()) {
                    ckeditor.instances[this.id].updateElement();
                    ckeditor.instances[this.id].resetDirty();
                }
            }else{
                var val = getComparable(this);
                if (val !== undefined) {
                    $(this).data('watch', val);
                }
            }
        });
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

        $('form#editcontent').find('input, textarea, select').each(function () {
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
        });

        return changes > 0;
    }

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

    // Apply mixin container.
    bolt.editcontent = editcontent;

})(Bolt || {}, jQuery, window, moment, bootbox, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
