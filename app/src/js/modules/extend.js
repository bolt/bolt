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
            }).ajaxError(function() {
                clearInterval(activeInterval);
            });

            checkInstalled();
            liveSearch();
            installReset();
        }
    };

    var activeConsole;

    var activeInterval;

    /* jshint -W126 */
    var delay = (function () {
        var timer = 0;

        return function (callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();
    /* jshint +W126 */

    var find = function (selector) {
        return $('.extend-bolt-container').find(selector);
    };

    var installReset = function () {
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
    };

    var updateCheck = function () {
        var target;

        find('.update-container').show();
        target = find('.update-list-items');
        activeConsole = find('.update-output');
        activeConsole.html(bolt.data('extend.text.updating'));

        $.get(bolt.data('extend.baseurl') + 'check', function(data) {
            if (data.updates.length > 0 || data.installs.length > 0) {
                var e,
                    ext;

                for (e in data.installs) {
                    if (data.installs.hasOwnProperty(e)) {
                        ext = data.installs[e];

                        // Add an install button.
                        target.append(
                            Bolt.data(
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
        .fail(function(data) {
            formatErrorLog(data);
        });
    };

    var updateRun = function () {
        find('.update-container').show();
        var target = find('.update-output');
        activeConsole = target;
        activeConsole.html(bolt.data('extend.text.running-update'));

        $.get(bolt.data('extend.baseurl') + 'update', function(data) {
            target.html(data);
            setTimeout(function(){
                find('.update-container').hide();
            }, 7000);

            checkInstalled();
        })
        .fail(function(data) {
            formatErrorLog(data);
        });
    };

    var updatePackage = function (e) {
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
        .fail(function(data) {
            formatErrorLog(data);
        });

        e.preventDefault();
    };

    var installRun = function (e) {
        var target = find('.update-output');

        find('.update-container').show();
        activeConsole = target;
        activeConsole.html(bolt.data('extend.text.install-all'));

        $.get(bolt.data('extend.baseurl') + 'installAll', function (data) {
            target.html(data);
            delay(function () {
                find('.update-container').hide();
            }, 7000);

            checkInstalled();
        })
        .fail(function(data) {
            formatErrorLog(data);
        });

        e.stopPropagation();
        e.preventDefault();
    };

    var checkInstalled = function () {
        find('.installed-container').each(function(){
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

                // Render installed packages.
                if (data.installed.length) {
                	html += renderPackage(data.installed, true);
                	nadda = false;
                }

                // Render pacakges pending install.
                if (data.pending.length) {
                	html += renderPackage(data.pending, true);
                	nadda = false;
                }

                // Render locally installed packages.
                if (data.local.length) {
                	html += renderPackage(data.local, false);
                	nadda = false;
                }

                // Nothing is installed.
                if (nadda) {
                	 html = bolt.data('extend.packages.empty');
                     activeConsole.hide();
                }

                target.find('.installed-list-items').append(html);

            })
            .fail(function(data) {
                formatErrorLog(data);
            });
        });
    };

    var renderPackage = function (data, composer) {
        var html = '';

        for (var e in data) {
            if (data.hasOwnProperty(e)) {
                var ext = data[e],
                conf = bolt.data('extend.packages'),
                authors = [],
                keywords = [],
                i = 0;

                // Authorsv.
                if (ext.authors && ext.authors.length > 0) {
                    for (i = 0; i < ext.authors.length; i++) {
                        authors.push(conf.author.subst({'%AUTHOR%': ext.authors[i].name}));
                    }
                }
                authors = authors.length ? conf.authors.subst({'%AUTHORS%': authors.join(' ')}) : '';

                // Keywords list.
                if (ext.keywords && ext.keywords.length > 0) {
                    for (i = 0; i < ext.keywords.length; i++) {
                        keywords.push(conf.keyword.subst({'%KEYWORD%': ext.keywords[i]}));
                    }
                }
                keywords = keywords.length ? conf.keywords.subst({'%KEYWORDS%': keywords.join(' ')}) : '';

                // Available versions & uninstall buttons.
                var available = '',
                    uninstall = '';

                if (composer) {
                    available = conf.avail_button.subst({
                        '%NAME%': ext.name
                    });
                    uninstall = conf.uninstall_button.subst({
                        '%BASEURL%': bolt.data('extend.baseurl'),
                        '%NAME%': ext.name
                    });
                }

                // Generate the HTML for a package item.
                html += conf.item.subst({
                    '%TITLE%':       ext.title ? ext.title : ext.name,
                    '%NAME%':        ext.name,
                    '%VERSION%':     ext.version,
                    '%AUTHORS%':     authors,
                    '%TYPE%':        ext.type,
                    '%AVAILABLE%':   available,
                    '%README%':      ext.readme ? conf.readme_button.subst({'%README%': ext.readme}) : '',
                    '%CONFIG%':      ext.config ? conf.config_button.subst({'%CONFIG%': ext.config}) : '',
                    '%THEME%':       ext.type === 'bolt-theme' ? conf.theme_button.subst({'%NAME%': ext.name}) : '',
                    '%BASEURL%':     bolt.data('extend.baseurl'),
                    '%UNINSTALL%':   uninstall,
                    '%DESCRIPTION%': ext.descrip ? conf.description.subst({'%DESCRIPTION%': ext.descrip}) : '',
                    '%KEYWORDS%':    keywords});
            }
        }

        return html;
    };

    var checkPackage = function (e) {
        // Depending on whether we 'autocompleted' an extension name or not, either
        // pick the value from the input itself, or from the data attribute.
        var ext = find('input[name="check-package"]').val(),
            packagename = find('input[name="check-package"]').data('packagename');

        if (packagename) {
            ext = packagename;
        }

        installInfo(ext);

        e.preventDefault();
    };

    var installInfo = function (ext) {
        activeConsole = find('.update-output');

        $.get(bolt.data('extend.baseurl') + 'installInfo?package=' + ext, function(data) {

            var devpacks = data.dev;
            var stablepacks = data.stable;
            var latestpacks = [ data.stable[0] ];

            if (devpacks.length > 0) {
                find('.dev-version-container .installed-version-item').html('');
                find('.dev-version-container .installed-version-item')
                    .append(buildVersionTable(devpacks));
            }

            if (stablepacks.length > 0) {
                find('.stable-version-container .installed-version-item').html('');
                find('.stable-version-container .installed-version-item')
                    .append(buildVersionTable(stablepacks));
            }

            if (latestpacks.length > 0) {
                find('.latest-version-container .installed-version-item').html('');
                find('.latest-version-container .installed-version-item')
                    .append(buildVersionTable(latestpacks));
            }


            find('.install-latest-container').show();
            find('#installModal .loader').hide();
        })
        .fail(function(data) {
            formatErrorLog(data);
        });
    };

    var showAllVersions = function () {
        find('.install-latest-container').hide();
        find('.install-version-container').show();
    };

    var buildVersionTable = function (packages) {
        var tpl = '',
            version,
            aclass;

        for (var v in packages) {
            if (packages.hasOwnProperty(v)) {
                version = packages[v];
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

        return tpl;
    };

    var install = function (e) {
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
        .done(function() {
            postInstall(packageName, packageVersion);
            find('.install-response-container').hide();
            find('.check-package').show();
            find('input[name="check-package"]').val('');
            checkInstalled();
        })
        .fail(function(data) {
            formatErrorLog(data);
        });
        e.preventDefault();
    };

    var postInstall = function (packageName, packageVersion) {
        $.get(
            bolt.data('extend.baseurl') + 'packageInfo',
            {'package': packageName, 'version': packageVersion}
        )
        .done(function(data) {
            if (data[0].type === 'bolt-extension') {
                extensionPostInstall(data[0]);
            }
            if (data[0].type === 'bolt-theme') {
                themePostInstall(data[0]);
            }
        })
        .fail(function(data) {
            formatErrorLog(data);
        });
    };

    var extensionPostInstall = function (extension) {
        find('.extension-postinstall').show();
        find('.extension-postinstall .modal-success').show();
        find('.postinstall-footer .ext-link').attr('href', extension.source);
        find('.postinstall-footer').show();
    };

    var themePostInstall = function (extension) {
        var name = extension.name.split(/\/+/).pop();

        find('.install-response-container').hide();
        find('.theme-postinstall').show();
        find('.theme-generation-container').show();
        find('.theme-postinstall .theme-generator').data('theme', extension.name);
        find('.theme-postinstall #theme-name').val(name);
        find('.postinstall-footer').show();
    };

    var generateTheme = function (e) {
        var trigger = $(e.target),
            theme = trigger.data('theme'),
            themename  = find('#theme-name').val();

        $.get(
            bolt.data('extend.baseurl') + 'generateTheme',
            {'theme': theme, 'name': themename}
        )
        .done(function(data) {
            find('.theme-generate-response').html('<p>' + data + '</p>').show();
            find('.theme-generation-container').hide();
        })
        .fail(function(data) {
            formatErrorLog(data);
        });

        e.preventDefault();
    };

    var copyTheme = function (e) {
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
    };

    var packageReadme = function (e) {
        $.get($(e.target).data('readme') )
        .done(function(data) {
            bootbox.dialog({
                message: data ? data : 'Readme is empty.'
            });
        })
        .fail(function(data) {
            formatErrorLog(data);
        });

        e.preventDefault();
    };

    var packageAvailable = function (e) {
        installInfo($(e.target).data('available'));
    };

    var uninstall = function (e) {
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
            delay(function(){
                activeConsole.hide();
            }, 2000);
        })
        .fail(function(data) {
            formatErrorLog(data);
        });

        e.preventDefault();
    };

    var liveSearch = function () {
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
                        livesearch.on('blur', function(){
                           cont.fadeOut();
                        });
                    }
                });
            }, 500);
        });
    };

    var prefill = function (e) {
        var target = $(e.target);

        find('input[name="check-package"]').val(target.closest('a').text());
        find('input[name="check-package"]').data('packagename', target.data('packagename'));
        find('.auto-search').hide();
    };

    var formatErrorLog = function(data) {
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

    };

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
                case 'generate-theme':    generateTheme(e.originalEvent); break;
                case 'package-available': packageAvailable(e.originalEvent); break;
                case 'package-copy':      copyTheme(e.originalEvent); break;
                case 'package-readme':    packageReadme(e.originalEvent); break;
                case 'show-all':    showAllVersions(e.originalEvent); break;
            }
        }
    };

    // Apply mixin container.
    bolt.extend = extend;

})(Bolt || {}, jQuery);
