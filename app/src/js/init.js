var init = {
    /*
     * Notice when (auto)depublish date is in the past
     * TODO: add timer, to check depublish date has passed during editing.
     *
     * @returns {undefined}
     */
    depublishTracking: function () {
        var noticeID = 'dateDepublishNotice',
            msg = $('#datedepublish').data('notice');

        $('#datedepublish, #statusselect').on('change', function (){

            var status = $('#statusselect').val(),
                depublish = $('#datedepublish').val();

            // remove old notice
            $('.'+noticeID).remove();

            if (depublish === '') {
                return;
            }

            if (status === 'published' && moment(depublish + Bolt.conf('timezone.offset')) < moment()) {
                $('<div class="' + noticeID + ' alert alert-warning alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert">×</button>' + msg + '</div>')
                    .hide()
                    .insertAfter('.depublish-group')
                    .slideDown('fast');
            }

        });

        // trigger on load
        $('#datedepublish').trigger('change');

    },

    /*
     * Bind editfile field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindEditFile: function (data) {
        $('#saveeditfile').bind('click', function () {

            // If not on mobile (i.e. Codemirror is present), copy back to the textarea.
            if (typeof CodeMirror !== 'undefined') {
                $('#form_contents').val(editor.getValue());
            }

            // Ping @rarila: How the heck would I get bolt.data('editcontent.msg.saving') here?
            var saving = "Saving …",
                msgNotSaved = "Not saved";

            // Disable the buttons, to indicate stuff is being done.
            $('#saveeditfile').addClass('disabled');
            $('#saveeditfile i').addClass('fa-spin fa-spinner');
            $('p.lastsaved').text(saving);

            $.post('?returnto=ajax', $('#editfile').serialize())
                .done(function (data) {
                    if (!data.ok) {
                        alert(data.msg);
                    }
                    $('p.lastsaved').html(data.msg);
                })
                .fail(function(){
                    alert(msgNotSaved);
                })
                .always(function(){
                    // Re-enable buttons
                    window.setTimeout(function(){
                        $('#saveeditfile').removeClass('disabled').blur();
                        $('#saveeditfile i').removeClass('fa-spin fa-spinner');
                    }, 300);
                });
        });

        if (typeof CodeMirror !== 'undefined') {
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
        }

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
        $('#myTab a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
        });

        var getUrlParam = function(paramName) {
            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i'),
                match = window.location.search.match(reParam);

            return match && match.length > 1 ? match[1] : null;
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
        });
    },

    bindCkFileSelect: function () {
        var getUrlParam = function (paramName) {
            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i'),
                match = window.location.search.match(reParam);

            return match && match.length > 1 ? match[1] : null;
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
            $("#form_contenttypes :checkbox").removeAttr('checked').trigger('click');
        });
        $('#uncheck-all').on('click', function() {
            $("#form_contenttypes :checkbox").removeAttr('checked');
        });
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
                url: Bolt.conf('paths.async') + 'widget/' + key,
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
            $(item).parent().on('show.bs.dropdown', function () {

                // Prevent breakage on old IE.
                if (typeof mouseEvt !== "undefined" && mouseEvt !== null) {
                    var self = $(this).find('[data-toggle="dropdown"]'),
                    menu = self.next('.dropdown-menu'),
                    mousey = mouseEvt.pageY + 20,
                    menuHeight = menu.height(),
                    menuVisY = $(window).height() - mousey + menuHeight, // Distance from the bottom of viewport
                    profilerHeight = 37; // The size of the Symfony Profiler Bar is 37px.

                    // The whole menu must fit when trying to 'dropup', but always prefer to 'dropdown' (= default).
                    if (mousey - menuHeight > 20 && menuVisY < profilerHeight) {
                        menu.css({
                            top: 'auto',
                            bottom: '100%'
                        });
                    }
                }


            });
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
            $('a[href="#tab-meta"]').click();
            $('#statusselect').focus();
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

        $('.login-forgot').bind('click', function () {
            $('.login-group, .password-group').hide();
            $('.reset-group').show();
        });

        $('.login-remembered').bind('click', function () {
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
        $('input[data-upload]').each(function () {
            var data = $(this).data('upload'),
                accept = $(this).attr('accept').replace(/\./g, ''),
                autocomplete_conf;

            switch (data.type) {
                case 'Image':
                case 'File':
                    bindFileUpload(data.key);

                    autocomplete_conf = {
                        source: Bolt.conf('paths.async') + 'file/autocomplete?ext=' + encodeURIComponent(accept),
                        minLength: 2
                    };
                    if (data.type === 'Image') {
                        autocomplete_conf.close = function () {
                            var path = $('#field-' + data.key).val(),
                                url;

                            if (path) {
                                url = Bolt.conf('paths.root') +'thumbs/' + data.width + 'x' + data.height + 'c/' +
                                      encodeURI(path);
                            } else {
                                url = Bolt.conf('paths.app') + 'view/img/default_empty_4x3.png';
                            }
                            $('#thumbnail-' + data.key).html(
                                '<img src="'+ url + '" width="' + data.width + '" height="' + data.height + '">'
                            );
                        };
                    }
                    $('#field-' + data.key).autocomplete(autocomplete_conf);
                    break;

                case 'ImageList':
                    Bolt.imagelist[data.key] = new FilelistHolder({id: data.key, type: data.type});
                    break;

                case 'FileList':
                    Bolt.filelist[data.key] = new FilelistHolder({id: data.key, type: data.type});
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
            update: function () {
                var serial = $(this).sortable('serialize');
                // Sorting request
                $.ajax({
                    url: $('#baseurl').attr('value') + 'content/sortcontent/' +
                        $(this).parent('table').data('contenttype'),
                    type: 'POST',
                    data: serial,
                    success: function () {
                        // Do nothing
                    }
                });
            }
        });
    }
};