var init = {

    /*
     * Auto-update the 'latest activity' widget.
     *
     * @returns {undefined}
     */
    activityWidget: function () {
        if ($('#latestactivity').is('*')) {
            setTimeout(function () {
                updateLatestActivity();
            }, 20 * 1000);
        }
    },

    /*
     * Bind editcontent
     *
     * @param {type} data
     * @returns {undefined}
     */
    bindEditContent: function (data) {

        // set handler to validate form submit
        $('#editcontent')
          .attr('novalidate', 'novalidate')
          .on('submit', function(event){
              var valid = validateContent(this);
              $(this).data('valid', valid);
              if ( ! valid){
                  event.preventDefault();
                  return false;
              }
              // submitting, disable warning
              window.onbeforeunload = null;
        });

        // basic custom validation handler
        $('#editcontent').on('boltvalidate', function(){
            var valid = validateContent(this);
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
            var newaction = "?returnto=saveandnew";
            $('#editcontent').attr('action', newaction).submit();
        });

        // Clicking the 'save & continue' button either triggers an 'ajaxy' post, or a regular post which returns
        // to this page. The latter happens if the record doesn't exist yet, so it doesn't have an id yet.
        $('#sidebarsavecontinuebutton, #savecontinuebutton').bind('click', function (e) {

            e.preventDefault();

            // trigger form validation
            $('#editcontent').trigger('boltvalidate');
            // check validation
            if ( ! $('#editcontent').data('valid')) {
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
                var newaction = "?returnto=new";
                $('#editcontent').attr('action', newaction).submit();
            } else {
                // Existing record. Do an 'ajaxy' post to update the record.

                // Reset the changes to the form.
                $('form').watchChanges();

                // Let the controller know we're calling AJAX and expecting to be returned JSON
                var ajaxaction = "?returnto=ajax";
                $.post(ajaxaction, $("#editcontent").serialize())
                    .done(function (data) {
                        $('p.lastsaved').html(savedon);
                        $('p.lastsaved').find('strong').text(moment().format('MMM D, HH:mm'));
                        $('p.lastsaved').find('time').attr('datetime', moment().format());
                        $('p.lastsaved').find('time').attr('title', moment().format());
                        bolt.moments.update();

                        $('a#lastsavedstatus strong').html(
                            '<i class="fa fa-circle status-' + $("#statusselect option:selected").val() + '"></i> ' +
                            $("#statusselect option:selected").text()
                        );

                        // Update anything changed by POST_SAVE handlers
                        if ($.type(data) === 'object') {
                            $.each(data, function (index, item) {

                                // Things like images are stored in JSON arrays
                                if ($.type(item) === 'object') {
                                    $.each(item, function (subindex, subitem) {
                                        $(":input[name='" + index + "[" + subindex + "]']").val(subitem);
                                    });
                                } else {
                                    $(":input[name='" + index + "']").val(item);
                                }
                            });
                        }
                        // Update dates and times from new values
                        bolt.datetimes.update();

                        // Reset the changes to the form from any updates we got from POST_SAVE changes
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
            var newaction = data.pathsRoot + "preview/" + data.singularSlug;
            $('#editcontent').attr('action', newaction).attr('target', '_blank').submit();
            $('#editcontent').attr('action', '').attr('target', "_self");
        });

        // Only if we have grouping tabs.
        if (data.hasGroups) {
            // Filter for tabs
            var allf = $('.tabgrouping');
            allf.hide();
            // Click function
            $(".filter").click(function() {
                var customType = $(this).data('filter');
                allf
                    .hide()
                    .filter(function () {
                        return $(this).data('tab') === customType;
                    })
                    .show();
                $('#filtertabs li').removeClass('active');
                $(this).parent().attr('class', 'active');
            });

            $(document).ready(function () {
                $('#filtertabs li a:first').trigger('click');
            });
        }
    },

    /*
     * Bind editfile field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindEditFile: function (data) {
        $('#saveeditfile').bind('click', function (e) {
            // Reset the handler for checking changes to the form.
            window.onbeforeunload = null;
        });

        var editor = CodeMirror.fromTextArea(document.getElementById('form_contents'), {
            lineNumbers: true,
            autofocus: true,
            tabSize: 4,
            indentUnit: 4,
            indentWithTabs: false,
            readOnly: data.readonly
        });

        var newheight = $(window).height() - 312;
        if (newheight < 200) {
            newheight = 200;
        }

        editor.setSize(null, newheight);
    },

    /*
     * Bind editlocale field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindEditLocale: function (data) {
        var editor = CodeMirror.fromTextArea(document.getElementById('form_contents'), {
            lineNumbers: true,
            autofocus: true,
            tabSize: 4,
            indentUnit: 4,
            indentWithTabs: false,
            readOnly: data.readonly
        });

        editor.setSize(null, $(window).height() - 276);
    },

    /*
     * Bind filebrowser
     */
    bindFileBrowser: function () {
        console.log("bindFileBrowser");
        $('#myTab a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
        })

        var getUrlParam = function(paramName) {
            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i'),
                match = window.location.search.match(reParam);

            return (match && match.length > 1) ? match[1] : null;
        };
        var funcNum = getUrlParam('CKEditorFuncNum');

        $('a.filebrowserCallbackLink').bind('click', function (e) {
            e.preventDefault();
            var url = $(this).attr('href');
            window.opener.CKEDITOR.tools.callFunction(funcNum, url);
            window.close();
        });

        $('a.filebrowserCloseLink').bind('click', function () {
            window.close();
        })
    },

    bindCkFileSelect: function (data) {
        var getUrlParam = function (paramName) {
            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i'),
                match = window.location.search.match(reParam);

            return (match && match.length > 1) ? match[1] : null;
        };

        var funcNum = getUrlParam('CKEditorFuncNum');
        $('a.filebrowserCallbackLink').bind('click', function (event) {
            event.preventDefault();
            var url = $(this).attr('href');
            window.opener.CKEDITOR.tools.callFunction(funcNum, url);
            window.close();
        });
    },

    /*
     * Bind prefill
     */
    bindPrefill: function () {
        $('#check-all').on('click', function() {
            // because jQuery is being retarded.
            // See: http://stackoverflow.com/questions/5907645/jquery-chrome-and-checkboxes-strange-behavior
            $("#form_contenttypes :checkbox").removeAttr('checked').trigger('click')
        });
        $('#uncheck-all').on('click', function() {
            $("#form_contenttypes :checkbox").removeAttr('checked');
        });
    },

    /*
     * Bind slug field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindSlug: function (data) {
        $('.sluglocker').bind('click', function () {
            if ($('.sluglocker i').hasClass('fa-lock')) {
                // "unlock" if it's currently empty, _or_ we've confirmed that we want to do so.
                if (data.isEmpty || confirm(data.messageUnlock)) {
                    $('.sluglocker i').removeClass('fa-lock').addClass('fa-unlock');
                    makeUri(data.slug, data.contentId, data.uses, data.key, false);
                }
            } else {
                $('.sluglocker i').addClass('fa-lock').removeClass('fa-unlock');
                stopMakeUri(data.uses);
            }
        });

        $('.slugedit').bind('click', function () {
            newslug = prompt(data.messageSet, $('#show-' + data.key).text());
            if (newslug) {
                $('.sluglocker i').addClass('fa-lock').removeClass('fa-unlock');
                stopMakeUri(data.uses);
                makeUriAjax(newslug, data.slug, data.contentId, data.key, false);
            }
        });

        if (data.isEmpty) {
            $('.sluglocker').trigger('click');
        }
    },

    /*
     * Bind ua
     */
    bindUserAgents: function () {
        $('.useragent').each(function () {
            var parser = new UAParser($(this).data('ua')),
                result = parser.getResult();

            $(this).html(result.browser.name + " " + result.browser.major + " / " + result.os.name + " " + result.os.version);
        });
    },

    /*
     * Bind video field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindVideo: function (data) {
        bindVideoEmbed(data.key);
    },

    /*
     * Initialise CKeditor instances.
     */
    ckeditor: function () {
        CKEDITOR.editorConfig = function (config) {
            var key,
                custom,
                set = bolt.ckeditor;

            var basicStyles = ['Bold', 'Italic'];
            var linkItems = ['Link', 'Unlink'];
            var toolItems = [ 'RemoveFormat', 'Maximize', '-', 'Source'];
            var paragraphItems = ['NumberedList', 'BulletedList', 'Indent', 'Outdent'];

            if (set.underline) {
                basicStyles = basicStyles.concat('Underline');
            }
            if (set.strike) {
                basicStyles = basicStyles.concat('Strike');
            }
            if (set.anchor) {
                linkItems = linkItems.concat('-', 'Anchor');
            }
            if (set.specialchar) {
                toolItems = ['SpecialChar', '-'].concat(toolItems);
            }
            if (set.blockquote) {
                paragraphItems = paragraphItems.concat('-', 'Blockquote');
            }

            config.language = bolt.locale.short;
            config.uiColor = '#DDDDDD';
            config.resize_enabled = true;
            config.entities = false;
            config.extraPlugins = 'codemirror';
            config.toolbar = [
                { name: 'styles', items: ['Format'] },
                { name: 'basicstyles', items: basicStyles }, // ['Bold', 'Italic', 'Underline', 'Strike']
                { name: 'paragraph', items: paragraphItems },
                { name: 'links', items: linkItems }
            ];


            if (set.subsuper) {
                config.toolbar = config.toolbar.concat({
                    name: 'subsuper', items: ['Subscript', 'Superscript']
                });
            }
            if (set.images) {
                config.toolbar = config.toolbar.concat({
                    name: 'image', items: ['Image']
                });
            }
            if (set.embed) {
                config.extraPlugins += ',oembed,widget';
                config.oembed_maxWidth = '853';
                config.oembed_maxHeight = '480';
                config.toolbar = config.toolbar.concat({
                    name: 'embed', items: ['oembed']
                });
            }

            if (set.tables) {
                config.toolbar = config.toolbar.concat({
                    name: 'table', items: ['Table']
                });
            }
            if (set.align) {
                config.toolbar = config.toolbar.concat({
                    name: 'align', items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
                });
            }
            if (set.fontcolor) {
                config.toolbar = config.toolbar.concat({
                    name: 'colors', items: ['TextColor', 'BGColor']
                });
            }

            if (set.codesnippet) {
                config.toolbar = config.toolbar.concat({
                    name: 'code', items: ['-', 'CodeSnippet']
                });
            }

            config.toolbar = config.toolbar.concat({
                name: 'tools', items: toolItems
            });

            config.height = 250;
            config.autoGrow_onStartup = true;
            config.autoGrow_minHeight = 150;
            config.autoGrow_maxHeight = 400;
            config.autoGrow_bottomSpace = 24;
            config.removePlugins = 'elementspath';
            config.resize_dir = 'vertical';

            if (set.filebrowser) {
                if (set.filebrowser.browseUrl) {
                    config.filebrowserBrowseUrl = set.filebrowser.browseUrl;
                }
                if (set.filebrowser.imageBrowseUrl) {
                    config.filebrowserImageBrowseUrl = set.filebrowser.imageBrowseUrl;
                }
                if (set.filebrowser.uploadUrl) {
                    config.filebrowserUploadUrl = set.filebrowser.uploadUrl;
                }
                if (set.filebrowser.imageUploadUrl) {
                    config.filebrowserImageUploadUrl = set.filebrowser.imageUploadUrl;
                }
            } else {
                config.filebrowserBrowseUrl = '';
                config.filebrowserImageBrowseUrl = '';
                config.filebrowserUploadUrl = '';
                config.filebrowserImageUploadUrl = '';
            }

            config.codemirror = {
                theme: 'default',
                lineNumbers: true,
                lineWrapping: true,
                matchBrackets: true,
                autoCloseTags: true,
                autoCloseBrackets: true,
                enableSearchTools: true,
                enableCodeFolding: true,
                enableCodeFormatting: true,
                autoFormatOnStart: true,
                autoFormatOnUncomment: true,
                highlightActiveLine: true,
                highlightMatches: true,
                showFormatButton: false,
                showCommentButton: false,
                showUncommentButton: false
            };

            // Parse override settings from config.yml
            for (key in set.ck) {
                if (set.ck.hasOwnProperty(key)) {
                     config[key] = set.ck[key];
                }
            }

            // Parse override settings from field in contenttypes.yml
            custom = $('textarea[name=' + this.name + ']').data('field-options');
            for (key in custom) {
                if (custom.hasOwnProperty(key)) {
                    config[key] = custom[key];
                }
            }
        };
    },

    /**
     * Any link (or clickable <i>-icon) with a class='confirm' gets a confirmation dialog.
     *
     * @returns {undefined}
     */
    confirmationDialogs: function () {
        $('.confirm').on('click', function () {
            return confirm($(this).data('confirm'));
        });
    },

    /*
     * Dashboard listing checkboxes
     *
     * @returns {undefined}
     */
    dashboardCheckboxes: function () {
        // Check all checkboxes
        $(".dashboardlisting tr th:first-child input:checkbox").click(function () {
            var checkedStatus = this.checked;
            $(".dashboardlisting tr td:first-child input:checkbox").each(function () {
                this.checked = checkedStatus;
                if (checkedStatus === this.checked) {
                    $(this).closest('table tbody tr').removeClass('row-checked');
                }
                if (this.checked) {
                    $(this).closest('table tbody tr').addClass('row-checked');
                }
            });
        });
        // Check if any records in the overview have been checked, and if so: show action buttons
        $('.dashboardlisting input:checkbox').click(function () {
            var aItems = getSelectedItems();
            if (aItems.length >= 1) {
                // if checked
                $('a.checkchosen').removeClass('disabled');
                $('a.showifchosen').show();
            } else {
                // if none checked
                $('a.checkchosen').addClass('disabled');
                $('a.showifchosen').hide();
            }
        });
        // Delete chosen Items
        $("a.deletechosen").click(function (e) {
            e.preventDefault();
            var aItems = getSelectedItems(),
                notice;

            if (aItems.length < 1) {
                bootbox.alert("Nothing chosen to delete");
            } else {
                notice = "Are you sure you wish to <strong>delete " +
                    (aItems.length=== 1 ? "this record" : "these records") + "</strong>? There is no undo.";
                bootbox.confirm(notice, function (confirmed) {
                    $(".alert").alert();
                    if (confirmed === true) {
                        $.each(aItems, function (index, id) {
                            // Delete request
                            $.ajax({
                                url: $('#baseurl').attr('value') + 'content/deletecontent/' +
                                    $('#item_' + id).closest('table').data('contenttype') + '/' + id + '?token=' +
                                    $('#item_' + id).closest('table').data('token'),
                                type: 'get',
                                success: function (feedback) {
                                    $('#item_' + id).hide();
                                    $('a.deletechosen').hide();
                                }
                            });
                        });
                    }
                });
            }
        });
    },

    /**
     * Helper to make things like '<button data-action="eventView.load()">' work
     *
     * @returns {undefined}
     */
    dataActions: function () {
        // Unbind the click events, with the 'action' namespace.
        $('button, input[type=button], a').off('click.action');

        // Bind the click events, with the 'action' namespace.
        $('[data-action]').on('click.action', function (e) {
            var action = $(this).attr('data-action');
            if (typeof action !== "undefined" && action !== "") {
                e.preventDefault();
                eval(action);
                e.stopPropagation();
            }
        })
        // Prevent propagation to parent's click handler from anchor in popover.
        .on('click.popover', '.popover', function (e) {
            e.stopPropagation();
        });
    },

    /*
     * Render any deferred widgets, if any.
     *
     * @returns {undefined}
     */
    deferredWidgets: function () {
        $('div.widget').each(function () {
            if (typeof $(this).data('defer') === 'undefined') {
                return;
            }

            var key = $(this).data('key');

            $.ajax({
                url: bolt.paths.async + 'widget/' + key,
                type: 'GET',
                success: function (result) {
                    $('#widget-' + key).html(result);
                },
                error: function () {
                    console.log('failed to get widget');
                }
            });
        });
    },

    /*
     * Smarter dropdowns/dropups based on viewport height.
     * Based on: https://github.com/twbs/bootstrap/issues/3637#issuecomment-9850709
     *
     * @returns {undefined}
     */
    dropDowns: function () {
        $('[data-toggle="dropdown"]').each(function (index, item) {
            var mouseEvt;
            if (typeof event === 'undefined') {
                $(item).parent().click(function (e) {
                    mouseEvt = e;
                });
            } else {
                mouseEvt = event;
            }
            $(item).parent().on('show.bs.dropdown', function (e) {

                // Prevent breakage on old IE.
                if (typeof mouseEvt === "undefined" || mouseEvt === null) {
                    return false;
                }

                var self = $(this).find('[data-toggle="dropdown"]'),
                    menu = self.next('.dropdown-menu'),
                    mousey = mouseEvt.pageY + 20,
                    menuHeight = menu.height(),
                    menuVisY = $(window).height() - (mousey + menuHeight), // Distance from the bottom of viewport
                    profilerHeight = 37; // The size of the Symfony Profiler Bar is 37px.

                // The whole menu must fit when trying to 'dropup', but always prefer to 'dropdown' (= default).
                if ((mousey - menuHeight) > 20 && menuVisY < profilerHeight) {
                    menu.css({
                        top: 'auto',
                        bottom: '100%'
                    });
                }
            });
        });
    },

    /*
     * Bind geolocation
     */
    geolocation: function () {
        $('input[data-geolocation]').each(function (item) {
            var data = $(this).data('geolocation');

            bindGeolocation(data.key, data.lat, data.lon);
        });
    },

    /*
     * Show 'dropzone' for jQuery file uploader.
     *
     * @returns {undefined}
     */
    dropZone: function () {
        // @todo make it prettier, and distinguish between '.in' and '.hover'.
        $(document).bind('dragover', function (e) {
            var dropZone = $('.dropzone'),
                timeout = window.dropZoneTimeout;
            if (!timeout) {
                dropZone.addClass('in');
            } else {
                clearTimeout(timeout);
            }
            if (e.target === dropZone[0]) {
                dropZone.addClass('hover');
            } else {
                dropZone.removeClass('hover');
            }
            window.dropZoneTimeout = setTimeout(function () {
                window.dropZoneTimeout = null;
                dropZone.removeClass('in hover');
            }, 100);
        });
    },

    /**
     * Initialize keyboard shortcuts:
     * - Click 'save' in Edit content screen.
     * - Click 'save' in "edit file" screen.
     *
     * @returns {undefined}
     */
    keyboardShortcuts: function () {
        function confirmExit() {
            if ($('form').hasChanged()) {
                return "You have unfinished changes on this page. " +
                    "If you continue without saving, you will lose these changes.";
            }
        }

        // We're on a regular 'edit content' page, if we have a sidebarsavecontinuebutton.
        // If we're on an 'edit file' screen,  we have a #saveeditfile
        if ($('#sidebarsavecontinuebutton').is('*') || $('#saveeditfile').is('*')) {

            // Bind ctrl-s and meta-s for saving..
            $('body, input').bind('keydown.ctrl_s keydown.meta_s', function (event) {
                event.preventDefault();
                $('form').watchChanges();
                $('#sidebarsavecontinuebutton, #saveeditfile').trigger('click');
            });

            // Initialize watching for changes on "the form".
            window.setTimeout(function () {
                $('form').watchChanges();
            }, 1000);

            // Initialize handler for 'closing window'
            window.onbeforeunload = confirmExit;
        }
    },

    /*
     * Initialize the Magnific popup shizzle. Fancybox is still here as a trigger, for backwards compatibility.
     */
    magnificPopup: function () {
        //
        $('.magnific, .fancybox').magnificPopup({
            type: 'image',
            gallery: {
                enabled: true
            },
            disableOn: 400,
            closeBtnInside: true,
            enableEscapeKey: true,
            mainClass: 'mfp-with-zoom',
            zoom: {
                enabled: true,
                duration: 300,
                easing: 'ease-in-out',
                opener: function (openerElement) {
                    return openerElement.parent().parent().find('img');
                }
            }
        });
    },

    /*
     * Initialize current status display setting focus on status select
     *
     * @returns {undefined}
     */
    focusStatusSelect: function () {
        $('#lastsavedstatus').click(function (e) {
            e.preventDefault();
            $('a[data-filter="meta"]').click();
            $('#statusselect').focus();
        });
     },

    /*
     * Omnisearch
     *
     * @returns {undefined}
     */
    omnisearch: function () {
        $('.omnisearch').select2({
            placeholder: '',
            minimumInputLength: 3,
            multiple: true, // this is for better styling …
            ajax: {
                url: bolt.paths.async + 'omnisearch',
                dataType: 'json',
                data: function (term, page) {
                    return {
                        q: term
                    };
                },
                results: function (data, page) {
                    var results = [];
                    $.each(data, function (index, item) {
                        results.push({
                            id: item.path,
                            path: item.path,
                            label: item.label,
                            priority: item.priority
                        });
                    });

                    return {results: results};
                }
            },
            formatResult: function (item) {
                var markup = '<table class="omnisearch-result"><tr>' +
                    '<td class="omnisearch-result-info">' +
                    '<div class="omnisearch-result-label">' + item.label + '</div>' +
                    '<div class="omnisearch-result-description">' + item.path + '</div>' +
                    '</td></tr></table>';

                return markup;
            },
            formatSelection: function (item) {
                window.location.href = item.path;

                return item.label;
            },
            dropdownCssClass: "bigdrop",
            escapeMarkup: function (m) {
                return m;
            }
        });
    },

    /*
     * Toggle options for showing / hiding the password input on the logon screen.
     *
     * @returns {undefined}
     */
    passwordInput: function () {
        $(".togglepass").on('click', function () {
            if ($(this).hasClass('show-password')) {
                $('input[name="password"]').attr('type', 'text');
                $('.togglepass.show-password').hide();
                $('.togglepass.hide-password').show();
            } else {
                $('input[name="password"]').attr('type', 'password');
                $('.togglepass.show-password').show();
                $('.togglepass.hide-password').hide();
            }
        });

        $('.login-forgot').bind('click', function (e) {
            $('.login-group, .password-group').hide();
            $('.reset-group').show();
        });

        $('.login-remembered').bind('click', function (e) {
            $('.login-group, .password-group').show();
            $('.reset-group').hide();
        });
    },

    /*
     * Initialize popovers.
     */
    popOvers: function () {
        $('.info-pop').popover({
            trigger: 'hover',
            delay: {
                show: 500,
                hide: 200
            }
        });
    },

    uploads: function () {
        $('input[data-upload]').each(function (item) {
            var data = $(this).data('upload'),
                accept = $(this).attr('accept').replace(/\./g, ''),
                autocomplete_conf;

            switch (data.type) {
                case 'Image':
                case 'File':
                    bindFileUpload(data.key);

                    autocomplete_conf = {
                        source: bolt.paths.async + 'filesautocomplete?ext=' + encodeURIComponent(accept),
                        minLength: 2
                    };
                    if (data.type === 'image') {
                        autocomplete_conf.close = function () {
                            var path = $('#field-' + data.key).val(),
                                url;

                            if (path) {
                                url = bolt.paths.root +'thumbs/' + data.width + 'x' + data.height + 'c/' + encodeURI(path);
                            } else {
                                url = bolt.paths.app + 'view/img/default_empty_4x3.png';
                            }
                            $('#thumbnail-' + data.key).html(
                                '<img src="'+ url + '" width="' + data.width + '" height="' + data.height + '">'
                            );
                        };
                    }
                    $('#field-' + data.key).autocomplete(autocomplete_conf);
                    break;

                case 'ImageList':
                    bolt.imagelist[data.key] = new ImagelistHolder({id: data.key});
                    break;

                case 'FileList':
                    bolt.filelist[data.key] = new FilelistHolder({id: data.key});
                    break;
            }
        });
    },

    /*
     * ?
     */
    sortables: function () {
        $('tbody.sortable').sortable({
            items: 'tr',
            opacity: '0.5',
            axis: 'y',
            handle: '.sorthandle',
            update: function (e, ui) {
                var serial = $(this).sortable('serialize');
                // Sorting request
                $.ajax({
                    url: $('#baseurl').attr('value') + 'content/sortcontent/' +
                        $(this).parent('table').data('contenttype'),
                    type: 'POST',
                    data: serial,
                    success: function (feedback) {
                        // Do nothing
                    }
                });
            }
        });
    },

};
