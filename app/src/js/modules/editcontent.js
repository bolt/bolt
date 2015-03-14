/**
 * Setp up editcontent stuff
 *
 * @mixin
 * @namespace Bolt.editcontent
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object} moment - Global moment object.
 * @param {Object|undefined} ckeditor - CKEDITOR global or undefined.
 */
(function (bolt, $, moment, ckeditor) {
    /**
     * Bind data.
     *
     * @typedef {Object} BindData
     * @memberof Bolt.editcontent
     *
     * @property {string} bind - Always 'editcontent'.
     * @property {boolean} hasGroups - Has groups.
     * @property {string} messageNotSaved - Message when entry could not be saved.
     * @property {string} messageSet - Message while saving.
     * @property {boolean} newRecord - Is new Record?
     * @property {string} pathsRoot - Root path.
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
     * @param {BindData} data
     */
    editcontent.init = function (data) {
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

        // Basic custom validation handler
        $('#editcontent').on('boltvalidate', function () {
            var valid = bolt.validation.run(this);
            $(this).data('valid', valid);
            return valid;
        });

        // Save the page.
        $('#sidebarsavebutton').bind('click', function () {
            $('#savebutton').trigger('click');
        });

        $('#savebutton').bind('click', function () {
            // Reset the changes to the form.
            $('form').watchChanges();
        });

        // Handle "save and new".
        $('#sidebarsavenewbutton, #savenewbutton').bind('click', function () {
            // Reset the changes to the form.
            $('form').watchChanges();

            // Do a regular post, and expect to be redirected back to the "new record" page.
            var newaction = '?returnto=saveandnew';
            $('#editcontent').attr('action', newaction).submit();
        });

        // Clicking the 'save & continue' button either triggers an 'ajaxy' post, or a regular post which returns
        // to this page. The latter happens if the record doesn't exist yet, so it doesn't have an id yet.
        $('#sidebarsavecontinuebutton, #savecontinuebutton').bind('click', function (e) {
            e.preventDefault();

            // trigger form validation
            $('#editcontent').trigger('boltvalidate');
            // check validation
            if (!$('#editcontent').data('valid')) {
                return false;
            }

            var newrecord = data.newRecord,
                savedon = data.savedon,
                msgNotSaved = data.msgNotSaved;

            // Disable the buttons, to indicate stuff is being done.
            $('#sidebarsavecontinuebutton, #savecontinuebutton').addClass('disabled');
            $('p.lastsaved').text(data.msgSaving);

            if (newrecord) {
                // Reset the changes to the form.
                $('form').watchChanges();

                // New record. Do a regular post, and expect to be redirected back to this page.
                var newaction = '?returnto=new';
                $('#editcontent').attr('action', newaction).submit();
            } else {
                // Existing record. Do an 'ajaxy' post to update the record.

                // Reset the changes to the form.
                $('form').watchChanges();

                // Let the controller know we're calling AJAX and expecting to be returned JSON
                var ajaxaction = '?returnto=ajax';
                $.post(ajaxaction, $("#editcontent").serialize())
                    .done(function (data) {
                        $('p.lastsaved').html(savedon);
                        $('p.lastsaved').find('strong').text(moment(data.datechanged).format('MMM D, HH:mm'));
                        $('p.lastsaved').find('time').attr('datetime', moment(data.datechanged).format());
                        $('p.lastsaved').find('time').attr('title', moment(data.datechanged).format());
                        bolt.moments.update();

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
                                } else {
                                    // Either an input or a textarea, so get by ID
                                    $('#' + index).val(item);

                                    // If there is a CKEditor attached to our element, update it
                                    if (ckeditor && ckeditor.instances[index]) {
                                        ckeditor.instances[index].setData(item);
                                    }
                                }
                            });
                        }
                        // Update dates and times from new values
                        bolt.datetimes.update();

                        // Reset the changes to the form frCKom any updates we got from POST_SAVE changes
                        $('form').watchChanges();

                    })
                    .fail(function(){
                        $('p.lastsaved').text(msgNotSaved);
                    })
                    .always(function(){
                        // Re-enable buttons
                        $('#sidebarsavecontinuebutton, #savecontinuebutton').removeClass('disabled');
                    });
            }
        });

        // To preview the page, we set the target of the form to a new URL, and open it in a new window.
        $('#previewbutton, #sidebarpreviewbutton').bind('click', function (e) {
            e.preventDefault();
            var newaction = data.pathsRoot + 'preview/' + data.singularSlug;
            $('#editcontent').attr('action', newaction).attr('target', '_blank').submit();
            $('#editcontent').attr('action', '').attr('target', '_self');
        });

        // Delete item from the editcontent page.
        $('#deletebutton, #sidebardeletebutton').bind('click', function (e) {
            e.preventDefault();
            bootbox.confirm(bolt.data('recordlisting.delete_one'), function (confirmed) {
                $('.alert').alert();
                if (confirmed === true) {
                    var url = bolt.conf('paths.bolt') + 'content/deletecontent/' + $('#contenttype').val() + '/' +
                            $('#id').val() + '?bolt_csrf_token=' + $('#bolt_csrf_token').val();
                    // Delete request
                    $.ajax({
                        url: url,
                        type: 'get',
                        success: function (feedback) {
                            window.location.href = bolt.conf('paths.bolt') + 'overview/' + $('#contenttype').val();
                        }
                    });
                }
            });
        });

        // Persistent tabgroups
        var hash = window.location.hash;
        if (hash) {
            $('#filtertabs a[href="#tab-' + hash.replace(/^#/, '') + '"]').tab('show');
        }

        $('#filtertabs a').click(function () {
            var top;

            $(this).tab('show');
            top = $('body').scrollTop();
            window.location.hash = this.hash.replace(/^#tab-/, '');
            $('html,body').scrollTop(top);
        });
    };

    // Apply mixin container
    bolt.editcontent = editcontent;

})(Bolt || {}, jQuery, moment, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
