var init = {
    /*
     * Notice when (auto)depublish date is in the past
     * TODO: add timer, to check depublish date has passed during editing.
     *
     * @returns {undefined}
     */
    depublishTracking: function () {
        "use strict";

        var noticeID = 'dateDepublishNotice',
            msg = $('#datedepublish').data('notice');

        $('#datedepublish, #statusselect').on('change', function (){

            var status = $('#statusselect').val(),
                depublish = $('#datedepublish').val();

            // remove old notice
            $('.' + noticeID).remove();

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
     *
     * @fires "Bolt.File.Save.Start"
     * @fires "Bolt.File.Save.Done"
     * @fires "Bolt.File.Save.Fail"
     * @fires "Bolt.File.Save.Always"
     */
    bindEditFile: function (data) {
        "use strict";

        var editor;

        if (typeof CodeMirror !== 'undefined') {
            editor = CodeMirror.fromTextArea(document.getElementById('form_contents'), {
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

        $('#saveeditfile').on('click', function () {
            Bolt.events.fire('Bolt.File.Save.Start');

            // Copy back to the textarea.
            if (editor) {
                editor.save();
            }

            var msgNotSaved = 'Not saved';

            // Disable the buttons, to indicate stuff is being done.
            $('#saveeditfile').addClass('disabled');
            $('#saveeditfile i').addClass('fa-spin fa-spinner');
            $('p.lastsaved').text(Bolt.data('editcontent.msg.saving'));

            $.post('?returnto=ajax', $('#editfile').serialize())
                .done(function (data) {
                    if (!data.ok) {
                        alert(data.msg);
                        Bolt.events.fire('Bolt.File.Save.Fail', data);
                    } else {
                        Bolt.events.fire('Bolt.File.Save.Done', data);
                    }
                    $('p.lastsaved').html(data.msg);
                })
                .fail(function () {
                    Bolt.events.fire('Bolt.File.Save.Fail');
                    alert(msgNotSaved);
                })
                .always(function () {
                    Bolt.events.fire('Bolt.File.Save.Always');

                    // Re-enable buttons
                    window.setTimeout(function () {
                        $('#saveeditfile').removeClass('disabled').blur();
                        $('#saveeditfile i').removeClass('fa-spin fa-spinner');
                    }, 300);
                });
        });
    },

    /*
     * Bind editlocale field
     *
     * @param {object} data
     * @returns {undefined}
     */
    bindEditLocale: function (data) {
        "use strict";

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
        "use strict";

        $('#myTab a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
        });

        var getUrlParam = function (paramName) {
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
        "use strict";

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
        "use strict";

        $('#check-all').on('click', function () {
            // because jQuery is being retarded.
            // See: http://stackoverflow.com/questions/5907645/jquery-chrome-and-checkboxes-strange-behavior
            $("#form_contenttypes :checkbox").removeAttr('checked').trigger('click');
        });
        $('#uncheck-all').on('click', function () {
            $("#form_contenttypes :checkbox").removeAttr('checked');
        });
    },

    /**
     * Any link (or clickable <i>-icon) with a class='confirm' gets a confirmation dialog.
     *
     * @returns {undefined}
     */
    confirmationDialogs: function () {
        "use strict";

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
        "use strict";

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
        "use strict";

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
        "use strict";

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
        "use strict";

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
        "use strict";

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
        "use strict";

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
        "use strict";

        $('.info-pop').popover({
            trigger: 'hover',
            delay: {
                show: 500,
                hide: 200
            }
        });
    },


    /*
     * ?
     */
    sortables: function () {
        "use strict";

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
