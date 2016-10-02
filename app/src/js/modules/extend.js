/**
 * Bolt extensions management.
 *
 * @mixin
 * @namespace Bolt.extend
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /**
     * Bolt.extend mixin container.
     *
     * @private
     * @type {Object}
     */
    var extend = {};

    var activeConsole;

    var activeInterval;

    function find(selector) {
        return $('.extend-bolt-container').find(selector);
    }

    function formatErrorLog(data) {
        var errObj = '',
            html = '',
            msg = '';

        try {
            errObj = $.parseJSON(data.responseText);
        } catch(err) {
            $('.modal').modal('hide');
            bootbox.alert('<p>An unknown error occurred. This was the error message:</p>\n\n' +
                '<pre>' + err.message + '</pre>');
        }

        if (errObj.error.type === 'Bolt\\Exception\\PackageManagerException') {
            // Clean up Composer messages.
            msg = errObj.error.message.replace(/(<http)/g, '<a href="http').replace(/(\w+>)/g, '">this link<\/a>');

            html = bolt.data(
                'extend.packages.error',
                {
                    '%ERROR_TYPE%': 'Composer Error',
                    '%ERROR_MESSAGE%': msg,
                    '%ERROR_LOCATION%': ''
                }
            );
        } else if (errObj.error.type === 'Bolt\\Exception\\ExtensionsInfoServiceException') {
            // Get the exception details
            msg = errObj.error.message;

            html = bolt.data(
                'extend.packages.error',
                {
                    '%ERROR_TYPE%': 'Extension Site Error',
                    '%ERROR_MESSAGE%': msg,
                    '%ERROR_LOCATION%': ''
                }
            );
        } else {
            // Sanitize PHP error file paths.
            var file = errObj.error.file.replace(new RegExp(bolt.data('extend.rootpath'), 'g'), '');

            html = bolt.data(
                'extend.packages.error',
                {
                    '%ERROR_TYPE%': 'PHP Error',
                    '%ERROR_MESSAGE%': errObj.error.message,
                    '%ERROR_LOCATION%': 'File: ' + file + '::' + errObj.error.line
                }
            );
        }

        $('.modal').modal('hide');
        bootbox.alert(html);
    }

    function renderPackage(data) {
        var html = '';

        for (var e in data) {
            if (data.hasOwnProperty(e)) {
                var ext = data[e],
                    conf = bolt.data('extend.packages'),
                    authors = [],
                    keywords = [],
                    i = 0;

                // Authors.
                if (ext.authors && ext.authors.length > 0) {
                    for (i = 0; i < ext.authors.length; i++) {
                        authors.push(conf.author.subst({'%AUTHOR%': ext.authors[i].name}));
                    }
                }
                authors = authors.length ? conf.authors.subst({'%AUTHORS%': authors.join(', ')}) : '';

                // Keywords list.
                if (ext.keywords && ext.keywords.length > 0) {
                    for (i = 0; i < ext.keywords.length; i++) {
                        keywords.push(conf.keyword.subst({'%KEYWORD%': ext.keywords[i]}));
                    }
                }
                keywords = keywords.length ? conf.keywords.subst({'%KEYWORDS%': keywords.join(' ')}) : '';

                if (ext.name === 'wikimedia/composer-merge-plugin') {
                    ext.title = 'Local Extension Helper';
                }

                // Manage dropdown
                var manage = '';
                var parameters = {
                    '%NAME%': ext.name,
                    '%VERSION%': ext.version,
                    '%BASEURL%': bolt.data('extend.baseurl'),
                    '%MARKETPLACE_URL%': 'https://extensions.bolt.cm/view/' + ext.name,
                    '%REPOSITORY_URL%': ext.repositoryLink
                };
                if (ext.status === 'installed' && ext.type !== 'composer-plugin') {
                    manage = conf.manage_dropdown_installed.subst(parameters);
                } else {
                    manage = conf.manage_dropdown_uninstalled.subst(parameters);
                }
                var invalid = ' — [INVALID] ';
                var disabled = ' — [DISABLED] ';
                var constraint = '<i class="fa fa-cog fa-fw"></i>';

                var buttonGroup = conf.left_buttons.subst({
                    '%README%': ext.readmeLink !== null ? conf.readme_button.subst({'%README%': ext.readmeLink}) : '',
                    '%CONFIG%': ext.configLink !== null ? conf.config_button.subst({'%CONFIG%': ext.configLink}) : '',
                    '%THEME%':  ext.type === 'bolt-theme' ? conf.theme_button.subst({'%NAME%': ext.name}) : ''
                });

                // Generate the HTML for a package item.
                html += conf.item.subst({
                    '%TITLE%':       ext.title ? ext.title : ext.name,
                    '%NAME%':        ext.name,
                    '%VERSION%':     ext.version,
                    '%AUTHORS%':     authors,
                    '%TYPE%':        ext.type,
                    '%MANAGE%':      manage,
                    '%DETAILS%':     buttonGroup,
                    '%BASEURL%':     bolt.data('extend.baseurl'),
                    '%DESCRIPTION%': ext.description ? conf.description.subst({'%DESCRIPTION%': ext.description}) : '',
                    '%KEYWORDS%':    keywords,
                    '%STATUS%':      ext.valid === false ? invalid : '',
                    '%ENABLED%':     ext.enabled === false ? disabled : '',
                    '%CONSTRAINT%':  ext.constraint !== null ? constraint + ' Requires Bolt ' + ext.constraint : ''
                });
            }
        }

        return html;
    }

    function checkInstalled() {
        find('.installed-container').each(function () {
            var target = $(this).find('.installed-list');

            activeConsole = find('.installed-container .console');

            $.get(bolt.data('extend.baseurl') + 'installed', function (data) {
                var html = '';

                if (typeof data !== 'object') {
                    activeConsole.html(bolt.data('extend.text.bad-json'));

                    return;
                }

                target.show();
                target.find('.installed-list-items').html('');
                find('.installed-container .console').hide();

                var nadda = true;

                // Render packages
                if (typeof data === 'object') {
                    html += renderPackage(data);
                    nadda = false;
                }

                // Nothing is installed.
                if (nadda) {
                    html = bolt.data('extend.packages.empty');
                    activeConsole.hide();
                }

                target.find('.installed-list-items').append(html);

            })
                .fail(function (data) {
                    formatErrorLog(data);
                });
        });
    }

    var delay = (function () {
        var timer = 0;

        return function (callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();

    function installReset() {
        $('#installModal').on('hide.bs.modal', function () {
            find('.latest-version-container .installed-version-item')
                .html('<tr><td colspan="3"><strong>' + bolt.data('extend.text.no-stable') + '</strong></td></tr>');
            find('.stable-version-container .installed-version-item')
                .html('<tr><td colspan="3"><strong>' + bolt.data('extend.text.no-stable') + '</strong></td></tr>');
            find('.dev-version-container .installed-version-item')
                .html('<tr><td colspan="3"><strong>' + bolt.data('extend.text.no-test') + '</strong></td></tr>');
            find('.install-response-container .console').html(bolt.data('extend.text.installing'));
            find('.theme-postinstall').hide();
            find('.theme-generate-response').hide();
            find('.extension-postinstall').hide();
            find('.install-response-container').hide();
            find('.install-latest-container').hide();
            find('.install-version-container').hide();
            find('.postinstall-footer').hide();
            find('#installModal .loader').show();
        });
    }

    function updateCheck() {
        var target,
            notice;

        // Set up container and wipe existing/previous
        find('.update-container').show();

        notice = find('.update-output-console').html();
        find('.update-output-notice').html(notice);
        find('.update-output-notice')
            .find('.update-output-element')
            .removeClass('update-output-element')
            .addClass('update-output')
            .show();

        find('.update-list').hide();
        target = find('.update-list-items');
        target.html('');

        activeConsole = find('.update-output');
        activeConsole.html(bolt.data('extend.text.updating'));

        $.get(bolt.data('extend.baseurl') + 'check', function (data) {
            if (data.updates.length > 0 || data.installs.length > 0) {
                var e,
                    ext;

                for (e in data.installs) {
                    if (data.installs.hasOwnProperty(e)) {
                        ext = data.installs[e];

                        // Add an install button.
                        target.append(
                            bolt.data(
                                'extend.packages.install_new',
                                {
                                    '%PACKAGE%': ext.name,
                                    '%VERSION%': ext.version,
                                    '%PRETTYVERSION%': ext.prettyversion
                                }
                            )
                        );
                    }
                }
                for (e in data.updates) {
                    if (data.updates.hasOwnProperty(e)) {
                        ext = data.updates[e];

                        // Add an update button.
                        target.append(
                            bolt.data(
                                'extend.packages.install_update',
                                {
                                    '%PACKAGE%': ext.name,
                                    '%VERSION%': ext.version,
                                    '%PRETTYVERSION%': ext.prettyversion
                                }
                            )
                        );
                    }
                }
                activeConsole.hide();
                find('.update-list').show();
            } else {
                activeConsole.html(bolt.data('extend.text.updated'));
            }
        })
            .fail(function (data) {
                formatErrorLog(data);
            });
    }

    /**
     * Load the initial feedback dialogue.
     *
     * @param titleMsg
     * @param consoleMsg
     * @param noticeMsg
     */
    function feedbackDialogueLoad(titleMsg, consoleMsg, noticeMsg) {
        var container = find('.update-container');

        if (titleMsg) {
            container.find('.update-output-title').html(titleMsg);
        }
        if (consoleMsg) {
            container.find('.update-output-console').find('.console').html(consoleMsg);
            container.find('.update-output-console').show();
        }
        if (noticeMsg) {
            container.find('.update-output-notice').html(noticeMsg).show();
        }
        container.show();
    }

    /**
     * Update the message console.
     *
     * @param consoleMsg
     * @param noticeMsg
     */
    function feedbackDialogueSetMessage(consoleMsg, noticeMsg) {
        var container = find('.update-container');

        if (consoleMsg) {
            container.find('.update-output-console').find('.console').html(consoleMsg).show();
        }
        if (noticeMsg) {
            container.find('.update-output-notice').html(noticeMsg).show();
        }
    }

    function updateRun() {
        feedbackDialogueLoad(
            bolt.data('extend.text.running-update-all'),
            bolt.data('extend.text.running-update'),
            false
        );

        $.get(bolt.data('extend.baseurl') + 'update', function (data) {
            setTimeout(function () {
                find('.update-container').hide();
            }, 7000);
            feedbackDialogueSetMessage(data);
            checkInstalled();
        })
            .fail(function (data) {
                formatErrorLog(data);
            });
    }

    function updatePackage(e) {
        var t = find('.update-output').html(bolt.data('extend.text.updating')),
            packageToUpdate = $(e.target).data('package');

        t.show();
        activeConsole = t;
        $.get(bolt.data('extend.baseurl') + 'update?package=' + packageToUpdate).success(function (data) {
            find('.update-output').html(data);
            find('.update-list-items tr[data-package="' + packageToUpdate + '"]').remove();
            delay(function () {
                activeConsole.hide();
            }, 4000);
            if (find('.update-list-items tbody tr').length < 1) {
                find('.update-container').hide();
            }
            checkInstalled();
        })
            .fail(function (data) {
                formatErrorLog(data);
            });

        e.preventDefault();
    }

    function installRun(e) {
        feedbackDialogueLoad(
            bolt.data('extend.text.install-running'),
            bolt.data('extend.text.install-all'),
            false
        );

        $.get(bolt.data('extend.baseurl') + 'installAll', function (data) {
            delay(function () {
                find('.update-container').hide();
            }, 7000);

            feedbackDialogueSetMessage(data);
            checkInstalled();
        })
            .fail(function (data) {
                formatErrorLog(data);
            });

        e.stopPropagation();
        e.preventDefault();
    }

    /**
     * Callback for requesting "dump autoloader".
     *
     * @param e
     */
    function autoloadDump(e) {

        feedbackDialogueLoad(
            bolt.data('extend.text.autoloader-update'),
            bolt.data('extend.text.autoloader-start') + ' …',
            false
        );

        $.get(bolt.data('extend.baseurl') + 'dumpAutoload', function (data) {
            delay(function () {
                find('.update-container').hide();
            }, 7000);

            feedbackDialogueSetMessage(data);
        })
            .fail(function (data) {
                formatErrorLog(data);
            });

        e.stopPropagation();
        e.preventDefault();
    }

    function buildVersionTable(packages) {
        var tpl = '',
            version,
            aclass;

        for (var v in packages) {
            if (packages.hasOwnProperty(v)) {
                version = packages[v];
                if (typeof version !== 'undefined') {
                    aclass = version.buildStatus === 'approved' ? ' label-success' : '';

                    // Add a row and replace macro values.
                    tpl += bolt.data(
                        'extend.packages.versions',
                        {
                            '%NAME%': version.name,
                            '%VERSION%': version.version,
                            '%CLASS%%': aclass,
                            '%BUILDSTATUS%': version.buildStatus
                        }
                    );
                }
            }
        }

        return tpl;
    }

    function installInfo(ext) {
        activeConsole = find('.update-output');

        $.get(bolt.data('extend.baseurl') + 'installInfo?package=' + ext, function (data) {

            var devpacks = data.dev;
            var stablepacks = data.stable;

            if (devpacks.length > 0) {
                find('.dev-version-container .installed-version-item').html('');
                find('.dev-version-container .installed-version-item')
                    .append(buildVersionTable(devpacks));
            }

            if (stablepacks.length > 0) {
                find('.stable-version-container .installed-version-item').html('');
                find('.stable-version-container .installed-version-item')
                    .append(buildVersionTable(stablepacks));

                find('.latest-version-container .installed-version-item').html('');
                find('.latest-version-container .installed-version-item')
                    .append(buildVersionTable([ data.stable[0] ]));

                find('.install-latest-container').show();
            } else {
                find('.install-version-container').show();
            }

            find('#installModal .loader').hide();
        })
            .fail(function (data) {
                formatErrorLog(data);
            });
    }

    function checkPackage(e) {
        // Depending on whether we 'autocompleted' an extension name or not, either
        // pick the value from the input itself, or from the data attribute.
        var ext = find('input[name="check-package"]').val(),
            packagename = find('input[name="check-package"]').data('packagename');

        if (packagename) {
            ext = packagename;
        }

        installInfo(ext);

        e.preventDefault();
    }

    function showAllVersions() {
        find('.install-latest-container').hide();
        find('.install-version-container').show();
    }

    function extensionPostInstall(extension) {
        find('.extension-postinstall').show();
        find('.extension-postinstall .modal-success').show();
        find('.postinstall-footer .ext-link').attr('href', extension.source);
        find('.postinstall-footer').show();
    }

    function themePostInstall(extension) {
        var name = extension.name.split(/\/+/).pop();

        find('.install-response-container').hide();
        find('.theme-postinstall').show();
        find('.theme-generation-container').show();
        find('.theme-postinstall .theme-generator').data('theme', extension.name);
        find('.theme-postinstall #theme-name').val(name);
        find('.postinstall-footer').show();
    }

    function postInstall(packageName, packageVersion) {
        $.get(
            bolt.data('extend.baseurl') + 'packageInfo',
            {'package': packageName, 'version': packageVersion}
        )
            .done(function (data) {
                if (data.type === 'bolt-extension') {
                    extensionPostInstall(data);
                }
                if (data.type === 'bolt-theme') {
                    themePostInstall(data);
                }
            })
            .fail(function (data) {
                formatErrorLog(data);
            });
    }

    function install(e) {
        var packageName = $(e.target).data('package'),
            packageVersion = $(e.target).data('version');

        find('.install-response-container').show();
        find('.install-latest-container').hide();
        find('.install-version-container').hide();
        activeConsole = find('.install-response-container .console');
        activeConsole.html(bolt.data('extend.text.installing'));
        find('#installModal .loader .message').html(bolt.data('extend.text.installing'));
        $.get(
            bolt.data('extend.baseurl') + 'install',
            {'package': packageName, 'version': packageVersion}
        )
            .done(function () {
                postInstall(packageName, packageVersion);
                find('.install-response-container').hide();
                find('.check-package').show();
                find('input[name="check-package"]').val('');
                checkInstalled();
            })
            .fail(function (data) {
                formatErrorLog(data);
            });
        e.preventDefault();
    }

    function generateTheme(e) {
        var trigger = $(e.target),
            theme = trigger.data('theme'),
            themename  = find('#theme-name').val();

        $.get(
            bolt.data('extend.baseurl') + 'generateTheme',
            {'theme': theme, 'name': themename}
        )
            .done(function (data) {
                find('.theme-generate-response').html('<p>' + data + '</p>').show();
                find('.theme-generation-container').hide();
            })
            .fail(function (data) {
                formatErrorLog(data);
            });

        e.preventDefault();
    }

    function copyTheme(e) {
        // Magic goes here.
        var trigger = $(e.target),
            theme = trigger.data('theme'),
            themename  = find('#theme-name').val();

        if (confirm(bolt.data('extend.text.overwrite'))) {
            var t = find('.installed-container .console').html(bolt.data('extend.text.copying'));

            t.show();
            activeConsole = t;

            $.get(bolt.data('extend.baseurl') + 'generateTheme', {'theme': theme, 'name': themename})
                .done(function (data) {
                    activeConsole.html(data);
                    delay(function () {
                        t.hide();
                    }, 5000);
                })
                .fail(function (data) {
                    formatErrorLog(data);
                });
        }
        e.preventDefault();
    }

    function packageReadme(e) {
        $.get($(e.target).data('readme') )
            .done(function (data) {
                bootbox.dialog({
                    message: data ? data : 'Readme is empty.'
                });
            })
            .fail(function (data) {
                formatErrorLog(data);
            });

        e.preventDefault();
    }

    function packageDepends(e) {
        var needle = $(e.target).data('needle');
        var constraint = $(e.target).data('constraint');

        find('.dependency-response-container').show();

        find('.install-latest-container').hide();
        find('.install-version-container').hide();

        activeConsole = find('.dependency-response-container .console');
        activeConsole.html('');

        $.get(bolt.data('extend.baseurl') + 'depends',
            {'needle': needle, 'constraint': constraint}
        )
            .done(function (data) {
                find('.loader').hide();
                find('.dependency-response-container').hide();
                find('.check-package').show();
                find('input[name="check-package"]').val('');

                var depList = find('#installModal .extension-dependencies-list');
                depList.html('');
                data.forEach(function (entry) {
                    depList.append('<li>' + entry.link + '</li>');
                });
                depList.show();

                find('.extension-dependencies').show();
                find('.postinstall-footer').show();
            })
            .fail(function (data) {
                formatErrorLog(data);
            });

        e.preventDefault();
    }

    function packageAvailable(e) {
        installInfo($(e.target).data('available'));
    }

    function uninstall(e) {
        var t = find('.installed-container .console').html(bolt.data('extend.text.removing'));

        if (confirm(bolt.data('extend.text.confirm-remove')) === false) {
            e.stopPropagation();
            e.preventDefault();
            return false;
        }

        t.show();
        activeConsole = t;

        $.get(
            $(e.target).attr('href')
        )
            .done(function (data) {
                find('.installed-container .console').html(data);
                checkInstalled();
                delay(function () {
                    activeConsole.hide();
                }, 2000);
            })
            .fail(function (data) {
                formatErrorLog(data);
            });

        e.preventDefault();
    }

    function liveSearch() {
        var livesearch = find('input[name="check-package"]');

        livesearch.on('keyup', function () {
            var searchVal = $(this).val();

            if (searchVal.length < 3) {
                return;
            }
            delay(function () {
                $.ajax({
                    url: bolt.data('extend.siteurl') + 'list.json',
                    dataType: 'jsonp',
                    data: {'name': searchVal}
                })
                    .success(function (data) {
                        if (data.packages.length) {
                            var cont = livesearch.parent().find('.auto-search');
                            cont.html('').show();

                            for (var p in data.packages) {
                                if (data.packages.hasOwnProperty(p)) {
                                    var t = data.packages[p];
                                    var dataattr = 'data-request="prefill-package" data-packagename="' + t.name + '"';
                                    cont.append('<a class="btn btn-block btn-default prefill-package" ' +
                                        dataattr + 'style="text-align: left;">' + t.title +
                                        ' <small ' + dataattr + '>(' + t.authors + ' - ' + t.name + ')</small></a>');
                                }
                            }
                            livesearch.on('blur', function () {
                                cont.fadeOut();
                            });
                        }
                    });
            }, 500);
        });
    }

    function prefill(e) {
        var target = $(e.target);

        find('input[name="check-package"]').val(target.closest('a').text());
        find('input[name="check-package"]').data('packagename', target.data('packagename'));
        find('.auto-search').hide();
    }

    var events = {
        click: function (e) {
            var request = $(e.target).data('request');

            switch (request) {
                case 'update-check':      updateCheck(); break;
                case 'update-run':        updateRun(); break;
                case 'update-package':    updatePackage(e.originalEvent); break;
                case 'check-package':     checkPackage(e.originalEvent); break;
                case 'uninstall-package': uninstall(e.originalEvent); break;
                case 'install-package':   install(e.originalEvent); break;
                case 'prefill-package':   prefill(e.originalEvent); break;
                case 'install-run':       installRun(e.originalEvent); break;
                case 'autoload-dump':     autoloadDump(e.originalEvent); break;
                case 'generate-theme':    generateTheme(e.originalEvent); break;
                case 'package-available': packageAvailable(e.originalEvent); break;
                case 'package-copy':      copyTheme(e.originalEvent); break;
                case 'package-readme':    packageReadme(e.originalEvent); break;
                case 'package-depends':   packageDepends(e.originalEvent); break;
                case 'show-all':          showAllVersions(e.originalEvent); break;
            }
        }
    };

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.extend
     */
    extend.init = function () {
        var extend = $('.extend-bolt-container');

        if (extend.length) {
            extend.on('click', events.click);

            $(document).ajaxStart(function () {
                // Show loader on start.
                activeInterval = setInterval(function () {
                    if (activeConsole) {
                        activeConsole.append('.');
                    }
                }, 1000);
            }).ajaxSuccess(function () {
                clearInterval(activeInterval);
            }).ajaxError(function () {
                clearInterval(activeInterval);
            });

            checkInstalled();
            liveSearch();
            installReset();
        }
    };

    // Apply mixin container.
    bolt.extend = extend;

})(Bolt || {}, jQuery);
