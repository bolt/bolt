// Create a static 'extends' method on the Object class
// This allows us to extend existing classes
// for classical object-oriented inheritance
Object.extend = function(superClass, definition) {
    var subClass = function () {};
    // Our constructor becomes the 'subclass'
    if (definition.constructor !== Object) {
        subClass = definition.constructor;
    }
    subClass.prototype = new superClass();
    for (var prop in definition) {
        if (prop !== 'constructor') {
            subClass.prototype[prop] = definition[prop];
        }
    }
    return subClass;
};

var delay = (function () {
    var timer = 0;
    return function(callback, ms){
        clearTimeout(timer);
        timer = setTimeout(callback, ms);
    };
})();

var active_console;
var active_interval;

var BoltExtender = Object.extend(Object, {

    selector: '.extend-bolt-container',
    messages:  {},
    paths:  {},

    constructor: function (){
        jQuery(this.selector).on('change', this, this.events.change);
        jQuery(this.selector).on('click', this, this.events.click);

        jQuery(document).ajaxStart(function () {
            // show loader on start
            active_interval = setInterval(function () {
                if (active_console) {
                    active_console.append('.');
                }
            },1000);
        }).ajaxSuccess(function () {
            clearInterval(active_interval);
        });

        this.checkInstalled();
        this.liveSearch();
        this.installReset();
    },

    find: function (selector) {
        return jQuery(this.selector).find(selector);
    },

    setMessage: function (key, value) {
        this.messages[key] = value;
    },

    setPath: function (key, value) {
        this.paths[key] = value;
    },

    installReset: function () {
        var controller = this;
        jQuery('#installModal').on('hide.bs.modal', function (e) {
            controller.find('.stable-version-container .installed-version-item')
                .html('<tr><td colspan="3"><strong>' + controller.messages.noStable + '</strong></td></tr>');
            controller.find('.dev-version-container .installed-version-item')
                .html('<tr><td colspan="3"><strong>' + controller.messages.noTest + '</strong></td></tr>');
            controller.find('.install-response-container .console').html(controller.messages.installing);
            controller.find('.theme-postinstall').hide();
            controller.find('.theme-generate-response').hide();
            controller.find('.extension-postinstall').hide();
            controller.find('.install-response-container').hide();
            controller.find('.install-version-container').hide();
            controller.find('#installModal .loader').show();
        });
    },

    updateCheck: function () {
        var controller = this,
            target;

        controller.find('.update-container').show();
        target = controller.find('.update-list-items');
        active_console = controller.find('.update-output');
        active_console.html(controller.messages.updating);
        jQuery.get(baseurl + 'check', function(data) {
            if (data.updates.length > 0 || data.installs.length > 0) {
                var e, ext;
                for (e in data.installs) {
                    ext = data.installs[e];
                    target.append('<tr data-package="' + ext + '"><td class="ext-list"><strong class="title">' +
                        ext + '</strong></td><td> ' +
                        '<a data-request="update-package" class="btn btn-sm btn-warning" data-package="' + ext + '">' +
                        'Install New Package</a></td></tr>');
                }
                for (e in data.updates) {
                    ext = data.updates[e];
                    target.append("<tr data-package='" + ext + "'><td class='ext-list'><strong class='title'>" + ext +
                        "</strong></td><td> <a data-request='update-package' class='btn btn-sm btn-tertiary' " +
                        "data-package='" + ext + "'>Install Package Update</a></td></tr>");
                }
                active_console.hide();
                controller.find('.update-list').show();
            } else {
                active_console.html(controller.messages.updated);
            }
            controller.updateLog();
        });
    },

    updateRun: function () {
        var controller = this;

        controller.find('.update-container').show();
        var target = controller.find('.update-output');
        active_console = target;
        active_console.html(controller.messages.runningUpdate);
        controller.updateLog();
        jQuery.get(baseurl + 'update', function(data) {
            target.html(data);
            setTimeout(function(){
                controller.find('.update-container').hide();
            }, 7000);
            controller.updateLog();
        });
    },

    updatePackage: function (e) {
        var controller = this,
            t = this.find('.update-output').html(controller.messages.updating),
            packageToUpdate = jQuery(e.target).data('package');

        t.show();
        active_console = t;
        jQuery.get(baseurl + 'update?package=' + packageToUpdate).success(function (data) {
            controller.find('.update-output').html(data);
            controller.find('.update-list-items tr[data-package="' + packageToUpdate + '"]').remove();
            delay(function () {
                active_console.hide();
            }, 4000);
            if (controller.find('.update-list-items tbody tr').length < 1) {
                controller.find('.update-container').hide();
            }
            controller.checkInstalled();
            controller.updateLog();
        });

        e.preventDefault();
    },

    installRun: function (e) {
        var controller = this;

        controller.find('.update-container').show();
        var target = controller.find('.update-output');
        active_console = target;
        active_console.html(controller.messages.installAll);
        jQuery.get(baseurl + 'installAll', function (data) {
            target.html(data);
            delay(function () {
                controller.find('.update-container').hide();
            }, 7000);
            controller.updateLog();
        });
        e.stopPropagation();
        e.preventDefault();
    },

    checkInstalled: function () {
        var controller = this;

        controller.find('.installed-container').each(function(){
            active_console = controller.find('.installed-container .console');
            var target = jQuery(this).find('.installed-list');
            jQuery.get(baseurl + 'installed', function (data) {
                target.show();
                var html = '';

                if (data.installed.length > 0) {
                    active_console.html(data.length + ' installed extension(s).');
                    controller.find('.installed-container .console').hide();
                    target.find('.installed-list-items').html('');

                    var html = '';
                    for (var e in data) {
                        var ext = data.installed[e],
                        conf = bolt.data.extend.packages,
                        authors = '',
                        keywords = '',
                        i = 0;
                        
                        // Authors array
                        if (ext.authors.length > 0) {
                            var authorsArray = ext.authors;
                            for (i = 0; i < authorsArray.length; i++) {
                                authors += conf.author.subst({'%AUTHOR%': authorsArray[i].name});
                            }
                        }

                        // Keyword array
                        if (ext.keywords.length > 0) {
                            var keywordsArray = ext.keywords;
                            for (i = 0; i < keywordsArray.length; i++) {
                                keywords += conf.keyword.subst({'%KEYWORD%': keywordsArray[i]});
                            }
                        }

                        html += conf.item.subst({
                            '%TITLE%': ext.title ? ext.title : ext.name,
                            '%NAME%': ext.name,
                            '%VERSION%': ext.version,
                            '%AUTHORS%': authors,
                            '%TYPE%': ext.type,
                            '%README%': ext.readme ? conf.readme_button.subst({'%README%': ext.readme}) : '',
                            '%CONFIG%': ext.config ? conf.config_button.subst({'%CONFIG%': ext.config}) : '',
                            '%THEME%': (ext.type == 'bolt-theme') ? conf.theme_button : '',
                            '%BASEURL%': baseurl,
                            '%DESCRIPTION%': ext.descrip,
                            '%KEYWORDS%': keywords});
                    }
                } else {
                    html = bolt.data.extend.packages.empty;
                    active_console.hide();
                }

                target.find('.installed-list-items').append(html);

                controller.updateLog();
            });
        });
    },

    checkPackage: function (e) {
        var controller = this;

        // Depending on whether we 'autocompleted' an extension name or not, either
        // pick the value from the input itself, or from the data attribute.
        var ext = this.find('input[name="check-package"]').val();
        var packagename = this.find('input[name="check-package"]').data('packagename');
        if (packagename) {
            ext = packagename;
        }
        active_console = false;
        jQuery.get(baseurl + 'installInfo?package=' + ext, function(data) {

            var devpacks = data.dev;
            var stablepacks = data.stable;

            if (devpacks.length > 0) {
                controller.find('.dev-version-container .installed-version-item').html('');
                controller.find('.dev-version-container .installed-version-item')
                    .append(controller.buildVersionTable(devpacks));
            }

            if (stablepacks.length > 0) {
                controller.find('.stable-version-container .installed-version-item').html('');
                controller.find('.stable-version-container .installed-version-item')
                    .append(controller.buildVersionTable(stablepacks));
            }


            controller.find('.install-version-container').show();
            controller.find('#installModal .loader').hide();

            controller.updateLog();
        });

        e.preventDefault();
    },

    buildVersionTable: function (packages) {
        var tpl = '',
            version,
            aclass;

        for (var v in packages) {
            version = packages[v];
            aclass = version.buildStatus === 'approved' ? ' label-success' : '';

            tpl += '<tr>' +
                    '<td>' + version.name + '</td>' +
                    '<td>' + version.version + '</td>' +
                    '<td><span class="label label-default' + aclass + '">' + version.buildStatus + '</span></td>' +
                    '<td><div class="btn-group"><a href="#" data-request="install-package" class="btn btn-primary ' +
                    'btn-sm" data-package="' + version.name + '" data-version="' + version.version + '">' +
                    '<i class="icon-gears"></i> Install This Version</a></div></td>' +
                '</tr>';
        }
        return tpl;
    },

    install: function (e) {
        var controller = this,
            package = jQuery(e.target).data('package'),
            version = jQuery(e.target).data('version');

        controller.find('.install-response-container').show();
        controller.find('.install-version-container').hide();
        active_console = controller.find('.install-response-container .console');
        active_console.html(controller.messages.installing);
        controller.find('#installModal .loader .message').html(controller.messages.installing);
        jQuery.get(
            baseurl + 'install',
            {'package': package, 'version': version}
        )
        .done(function(data) {
            active_console.html(data);
            controller.postInstall(package, version);
            controller.find('.check-package').show();
            controller.find('input[name="check-package"]').val('');
            controller.checkInstalled();
            controller.updateLog();
        })
        .fail(function(data) {
        	active_console.html(controller.formatErrorLog(data));
        	controller.extensionFailedInstall(data);
        });
        e.preventDefault();
    },

    postInstall: function (package, version) {
        var controller = this;
        jQuery.get(
            baseurl + 'packageInfo',
            {'package': package, 'version': version}
        )
        .done(function(data) {
            if (data[0].type === 'bolt-extension') {
                controller.extensionPostInstall(data);
            }
            if (data[0].type === 'bolt-theme') {
                controller.themePostInstall(data);
            }
            controller.updateLog();
        });
    },

    extensionPostInstall: function (extension) {
        var controller = this;
        controller.find('.extension-postinstall .ext-link').attr('href', extension.source);
        controller.find('.extension-postinstall').show();
        controller.find('.extension-postinstall .modal-success').show();
    },

    themePostInstall: function (extension) {
        var controller = this;

        controller.find('.install-response-container').hide();
        controller.find('.theme-postinstall').show();
        controller.find('.theme-generation-container').show();
        var name = extension.name.split(/\/+/).pop();
        controller.find('.theme-postinstall .theme-generator').data('theme', extension.name);
        controller.find('.theme-postinstall #theme-name').val(name);
    },

    generateTheme: function (e) {
        var controller = this;
        var trigger = jQuery(e.target);
        var theme = trigger.data('theme');
        var themename  = controller.find('#theme-name').val();
        jQuery.get(
            baseurl+'generateTheme',
            {'theme': theme, 'name': themename}
        )
        .done(function(data) {
            controller.find('.theme-generate-response').html('<p>' + data + '</p>').show();
            controller.find('.theme-generation-container').hide();
            controller.updateLog();
        });
        e.preventDefault();
    },

    copyTheme: function (e) {
        // Magic goes here.
        var controller = this;
        var trigger = jQuery(e.target);
        var theme = trigger.data('theme');
        var themename  = controller.find('#theme-name').val();
        if (confirm(controller.messages.overwrite)) {
            var t = controller.find('.installed-container .console').html(controller.messages.copying);
            t.show();
            active_console = t;
            jQuery.get(
                baseurl + 'generateTheme',
                {'theme': theme, 'name': themename}
            ).done(function (data) {
                active_console.html(data);
                controller.updateLog();
                delay(function () {
                    t.hide();
                }, 5000);
            });
        }
        e.preventDefault();
    },

    packageReadme: function (e) {
        var controller = this;

        jQuery.get( jQuery(e.target).data('readme') )
        .done(function(data) {
            bootbox.dialog({
                message: data
            });
            controller.updateLog();
        });

        e.preventDefault();
    },

    uninstall: function (e) {
        var controller = this,
        t = this.find('.installed-container .console').html(controller.messages.removing);

        if (confirm(controller.messages.confirmRemove) === false) {
            e.stopPropagation();
            e.preventDefault();
            return false;
        }

        t.show();
        active_console = t;
        jQuery.get(
            jQuery(e.target).attr('href')
        )
        .done(function (data) {
            controller.find('.installed-container .console').html(data);
            controller.checkInstalled();
            delay(function(){
                active_console.hide();
            }, 2000);
            controller.updateLog();
        });

        e.preventDefault();
    },

    liveSearch: function () {
        var livesearch = this.find('input[name="check-package"]');

        livesearch.on('keyup', function (e) {
            var searchVal = jQuery(this).val();
            if (searchVal.length < 3) {
                return;
            }
            delay(function () {
                jQuery.ajax({
                    url: site + 'list.json',
                    dataType: 'jsonp',
                    data: {'name': searchVal}
                })
                .success(function (data) {
                    if (data.packages.length < 1) {
                        return;
                    }
                    var cont = livesearch.parent().find('.auto-search');
                    cont.html('').show();
                    for (var p in data.packages) {
                        var t = data.packages[p];
                        var dataattr = 'data-request="prefill-package" data-packagename="' + t.name + '"';
                        cont.append('<a class="btn btn-block btn-default prefill-package" ' +
                            dataattr + 'style="text-align: left;">' + t.title +
                            ' <small ' + dataattr + '>(' + t.authors + ' - ' + t.name + ')</small></a>');
                    }
                    livesearch.on('blur', function(){
                       cont.fadeOut();
                    });
                });
            }, 500 );
        });
    },

    prefill: function (e) {
        var target = jQuery(e.target);
        this.find('input[name="check-package"]').val(target.closest('a').text());
        this.find('input[name="check-package"]').data('packagename', target.data('packagename'));
        this.find('.auto-search').hide();
    },

    updateLog: function () {
        jQuery.get(baseurl + 'getLog', function (data) {
            $('#extension-log').html(data);
            $('#extension-log').animate({ scrollTop: $('#extension-log')[0].scrollHeight }, 'fast');
        });
    },

    clearLog: function () {
        $('#extension-log').html('');
        jQuery.get(baseurl + 'clearLog', function (data) {});
    },

    formatErrorLog: function(data) {
        var errObj = $.parseJSON(data.responseText),
        html = '';
        if (errObj.error.type === 'Bolt\\Exception\\BoltComposerException') {
        	html = bolt.data.extend.packages.error.subst({
        		'%ERROR_TYPE%': 'Composer Error',
        		'%ERROR_MESSAGE%': errObj.error.message
        	});
        } else {
        	html = bolt.data.extend.packages.error.subst({
        		'%ERROR_TYPE%': 'PHP Error',
        		'%ERROR_MESSAGE%': errObj.error.message,
        		'%ERROR_LOCATION%': errObj.error.file + '::' + errObj.error.line
        	});
        }

        return html;
    },

    events: {
        change: function (e, t) {
            var controller = e.data;

        },

        click: function (e, t) {
            var controller = e.data;
            var request = jQuery(e.target).data('request');
            switch (request) {
                case "update-check"      : controller.updateCheck(); break;
                case "update-run"        : controller.updateRun(); break;
                case "update-package"    : controller.updatePackage(e.originalEvent); break;
                case "check-package"     : controller.checkPackage(e.originalEvent); break;
                case "uninstall-package" : controller.uninstall(e.originalEvent); break;
                case "install-package"   : controller.install(e.originalEvent); break;
                case "prefill-package"   : controller.prefill(e.originalEvent); break;
                case "install-run"       : controller.installRun(e.originalEvent); break;
                case "generate-theme"    : controller.generateTheme(e.originalEvent); break;
                case "package-copy"      : controller.copyTheme(e.originalEvent); break;
                case "package-readme"    : controller.packageReadme(e.originalEvent); break;
                case "package-config"    : controller.packageConfig(e.originalEvent); break;
                case "clear-log"         : controller.clearLog(e.originalEvent); break;
            }
        }

    }
});
