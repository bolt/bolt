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
            var aItems = getSelectedItems();

            if (aItems.length < 1) {
                bootbox.alert("Nothing chosen to delete");
            } else {
                var notice = "Are you sure you wish to <strong>delete " + (aItems.length=== 1 ? "this record" : "these records") + "</strong>? There is no undo.";
                bootbox.confirm(notice, function (confirmed) {
                    $(".alert").alert();
                    if (confirmed === true) {
                        $.each(aItems, function (index, id) {
                            // delete request
                            $.ajax({
                                url: $('#baseurl').attr('value') + 'content/deletecontent/' + $('#item_' + id).closest('table').data('contenttype') + '/' + id + '?token=' + $('#item_' + id).closest('table').data('token'),
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
            var action = $(this).data('action');
            if (typeof action !== "undefined" && action !== "") {
                eval(action);
                e.stopPropagation();
                e.preventDefault();
            }
        })
        // Prevent propagation to parent's click handler from anchor in popover.
        .on('click.popover', '.popover', function (e) {
            e.stopPropagation();
        });
    },

    /*
     * Add Date and Timepickers.
     *
     * @returns {undefined}
     */
    dateTimePickers: function () {
        $(".datepicker").datepicker({
            dateFormat: "DD, d MM yy"
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
                url: asyncpath + 'widget/' + key,
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
                    menuVisY = $(window).height() - (mousey + menuHeight), // Distance of element from the bottom of viewport
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
                return "You have unfinished changes on this page. If you continue without saving, you will lose these changes.";
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
     * Initialize 'moment' timestamps.
     *
     * @returns {undefined}
     */
    momentTimestamps: function () {
        if ($('.moment').is('*')) {
            updateMoments();
        }
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
                url: asyncpath + "omnisearch",
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
                serial = $(this).sortable('serialize');
                // sorting request
                $.ajax({
                    url: $('#baseurl').attr('value') + 'content/sortcontent/' + $(this).parent('table').data('contenttype'),
                    type: 'POST',
                    data: serial,
                    success: function (feedback) {
                        // do nothing
                    }
                });
            }
        });
    }

};