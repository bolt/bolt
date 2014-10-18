// Create a static 'extends' method on the Object class
// This allows us to extend existing classes
// for classical object-oriented inheritance
Object.extend = function(superClass, definition) {
    var subClass = function() {};
    // Our constructor becomes the 'subclass'
    if (definition.constructor !== Object)
        subClass = definition.constructor;
    subClass.prototype = new superClass();
    for (var prop in definition) {
        if (prop != 'constructor')
            subClass.prototype[prop] = definition[prop];
    }
    return subClass;
};

var delay = (function(){
    var timer = 0;
    return function(callback, ms){
        clearTimeout (timer);
        timer = setTimeout(callback, ms);
    };
})();


var BoltExtender = Object.extend(Object, {

    selector: ".extend-bolt-container",
    messages:  {},
    paths:  {},

    constructor: function(){
        jQuery(this.selector).on("change", this, this.events.change);
        jQuery(this.selector).on("click", this, this.events.click);

        jQuery(document).ajaxStart(function() {
            // show loader on start
            active_interval = setInterval(function(){
                if(active_console) {
                    active_console.append(".");
                }
            },1000);
        }).ajaxSuccess(function() {
            clearInterval(active_interval);
        });

        this.checkInstalled();
        this.liveSearch();
        this.installReset();

    },

    find: function(selector) {
        return jQuery(this.selector).find(selector);
    },

    setMessage: function(key, value) {
        this.messages[key]=value;
    },


    setPath: function(key, value) {
        this.paths[key]=value;
    },

    installReset: function() {
        var controller = this;
        jQuery('#installModal').on('hide.bs.modal', function (e) {
            controller.find(".stable-version-container .installed-version-item").html('<tr><td colspan="3"><strong>'+controller.messages['noStable']+'</strong></td></tr>');
            controller.find(".dev-version-container .installed-version-item").html('<tr><td colspan="3"><strong>'+controller.messages['noTest']+'</strong></td></tr>');
            controller.find('.extension-postinstall').hide();
            controller.find('.install-version-container').hide();
            controller.find("#installModal .loader").show();
        });
    },

    updateCheck: function() {
        var controller = this;

        controller.find('.update-container').show();
        var target = controller.find(".update-list-items");
        active_console = controller.find('.update-output');
        active_console.html(controller.messages['updating']);
        jQuery.get(baseurl+'check', function(data) {
            if(data.updates.length > 0 || data.installs.length >0) {
                for(var e in data.installs) {
                    var ext = data.installs[e];
                    target.append("<tr data-package='"+ext+"'><td class='ext-list'><strong class='title'>"+ext+"</strong></td><td> <a data-action='update-package' class='btn btn-sm btn-warning' data-package='"+ext+"'>Install New Package</a></td></tr>");
                }
                for(var e in data.updates) {
                    var ext = data.updates[e];
                    target.append("<tr data-package='"+ext+"'><td class='ext-list'><strong class='title'>"+ext+"</strong></td><td> <a data-action='update-package' class='btn btn-sm btn-tertiary' data-package='"+ext+"'>Install Package Update</a></td></tr>");
                }
                active_console.hide();
                controller.find('.update-list').show();
            } else {
                active_console.html(controller.messages['updated']);
            }
            controller.updateLog();
        });
    },

    updateRun: function() {
        var controller = this;

        controller.find('.update-container').show();
        var target = controller.find(".update-output" );
        active_console = target;
        active_console.html("Running update....");
        controller.updateLog();
        jQuery.get(baseurl+'update', function(data) {
            target.html(data);
            setTimeout(function(){
                controller.find('.update-container').hide();
            },7000);
            controller.updateLog();
        });
    },

    updatePackage: function(e) {
        var controller = this;
        var t = this.find('.update-output').html(controller.messages['updating']);
        var packageToUpdate = jQuery(e.target).data("package");
        t.show();
        active_console = t;
        jQuery.get(
            baseurl+'update?package='+packageToUpdate
        )
        .success(function(data) {
            controller.find('.update-output').html(data);
            controller.find('.update-list-items tr[data-package="'+packageToUpdate+'"]').remove();
            delay(function(){
                active_console.hide();
            }, 4000);
            if(controller.find('.update-list-items tbody tr').length <1) {
                controller.find('.update-container').hide();
            }
            controller.checkInstalled();
            controller.updateLog();
        });

        e.preventDefault();
    },

    installRun: function(e) {
        var controller = this;

        controller.find('.update-container').show();
        var target = controller.find(".update-output" );
        active_console = target;
        active_console.html(controller.messages['installAll']);
        jQuery.get(baseurl+'installAll', function(data) {
            target.html(data);
            delay(function(){
                controller.find('.update-container').hide();
            },7000);
            controller.updateLog();
        });
        e.stopPropagation();
        e.preventDefault();
    },

    checkInstalled: function() {
        var controller = this;

        controller.find('.installed-container').each(function(){
            active_console = controller.find(".installed-container .console");
            var target = jQuery(this).find('.installed-list');
            jQuery.get(baseurl+"installed", function(data) {
                target.show();
                if(data.length > 0) {
                    active_console.html(data.length + " installed extension(s).");
                    controller.find(".installed-container .console").hide();

                    target.find('.installed-list-items').html('');
                    for(var e in data) {
                        ext = data[e];
                        var html = "<div class='panel panel-default'>";
                        html += "<div class='panel-heading'><i class='fa fa-cube fa-fw'></i> " + ext["name"] + "</div> ";
                        html += "<div class='panel-body'>";

                        html += "<div class='actions pull-right btn-group'> ";
                        if (ext["readmelink"]) {
                            html += "<a data-action='package-readme' data-readme='" + ext["readmelink"] + "' class='btn btn-sm btn-tertiary' href=''><i class='fa fa-quote-right fa-fw'></i> Readme</a> ";
                        }
                        if (ext["config"]) {
                            html += "<a href='" + ext["config"] + "' class='btn btn-sm btn-tertiary' ><i class='fa fa-cog fa-fw'></i> Config</a> ";
                        }
                        html += "<a data-action='uninstall-package' class='btn btn-sm btn-danger' href='" + baseurl + "uninstall?package=" + ext["name"] + "'><i class='fa fa-trash-o fa-fw'></i> Uninstall</a>" + "</div> ";

                        html += "<div class='description text-muted'>" + ext["descrip"] + "</div> ";
                        html += "<span class='version label label-default'>" + ext["version"] + "</span> ";
                        html += "<span class='type label label-default'>" + ext["type"] + "</span> ";
                        if (ext["keywords"]) {
                            html += "<span class='type label label-info'>" + ext["keywords"] + "</span> ";
                        }

                        html += "</div></div>";
                        console.log(ext);
                        target.find('.installed-list-items').append(html);
                    }
                } else {
                    target.find('.installed-list-items').html("<div class='ext-list'><strong class='no-results'>No Bolt Extensions installed.</strong></div>");
                    active_console.hide();
                }

                controller.updateLog();

            });
        });
    },

    checkPackage: function(e) {
        var controller = this;

        // Depending on whether we 'autocompleted' an extension name or not, either
        // pick the value from the input itself, or from the data attribute.
        var ext = this.find('input[name="check-package"]').val();
        var packagename = this.find('input[name="check-package"]').data('packagename');
        if (packagename) {
            ext = packagename;
        }
        active_console = false;
        jQuery.get(baseurl+'installInfo?package='+ext, function(data) {

            var devpacks = data['dev'];
            var stablepacks = data['stable'];

            if(devpacks.length > 0) {
              controller.find('.dev-version-container .installed-version-item').html("");
              controller.find('.dev-version-container .installed-version-item').append(controller.buildVersionTable(devpacks));
            }

            if(stablepacks.length > 0) {
              controller.find('.stable-version-container .installed-version-item').html("");
              controller.find('.stable-version-container .installed-version-item').append(controller.buildVersionTable(stablepacks));
            }


            controller.find(".install-version-container").show();
            controller.find("#installModal .loader").hide();

            controller.updateLog();
        });




        e.preventDefault();
    },

    buildVersionTable: function(packages) {
        var tpl = "";
        for(var v in packages) {
            version = packages[v];
            tpl = tpl+'<tr><td>'+version.name+'</td><td>'+version.version+'</td><td><span class="label label-default';
            if(version.buildStatus=='approved') tpl = tpl+' label-success';
            tpl = tpl+'">'+version.buildStatus+'</span></td>';
            tpl = tpl+'<td><div class="btn-group"><a href="#" data-action="install-package" class="btn btn-primary btn-sm" data-package="'+version.name+'" data-version="'+version.version+'">';
            tpl = tpl+'<i class="icon-gears"></i> Install This Version</a></div></td></tr>';
        }
        return tpl;
    },

    install: function(e) {
        var controller = this;

        var package = jQuery(e.target).data("package");
        var version = jQuery(e.target).data("version");

        controller.find('.install-response-container').show();
        controller.find('.install-version-container').hide();
        active_console = controller.find('.install-response-container .console');
        active_console.html(controller.messages['installing']);
        controller.find("#installModal .loader .message").html(controller.messages['installing']);
        jQuery.get(
            baseurl+'install',
            {'package':package,'version':version}
        )
        .done(function(data) {
            active_console.html(data);
            controller.postInstall(package, version);
            controller.find(".check-package").show()
            controller.find('input[name="check-package"]').val('');
            controller.checkInstalled();
            controller.updateLog();
        });
        e.preventDefault();
    },

    postInstall: function(package, version) {
        var controller = this;
        jQuery.get(
            baseurl+'packageInfo',
            {'package':package,'version':version}
        )
        .done(function(data) {
            if(data['type']=='bolt-extension') {
                controller.extensionPostInstall(data);
            }
            if(data['type']=='bolt-theme') {
                controller.themePostInstall(data);
            }
            controller.updateLog();
        });
    },

    extensionPostInstall: function(extension) {
        var controller = this;
        controller.find('.extension-postinstall .ext-link').attr("href", extension.source);
        controller.find('.extension-postinstall').show();
    },

    themePostInstall: function(extension) {
        var controller = this;
        controller.find('.theme-postinstall').show();
        controller.find('.theme-postinstall .theme-generator').data("theme",extension['name']);
    },

    generateTheme: function(e) {
        var controller = this;
        var trigger = jQuery(e.target);
        var theme = trigger.data("theme");
        var themename  = controller.find('#theme-name').val();
        jQuery.get(
            baseurl+'generateTheme',
            {'theme':theme,'name':themename}
        )
        .done(function(data) {
            controller.find('.theme-generate-response').html("<p>"+data+"</p>").show();
            controller.find('.theme-generation-container').hide();
            controller.updateLog();
        });
        e.preventDefault();
    },

    packageReadme: function(e) {

        jQuery.get( jQuery(e.target).data("readme") )
        .done(function(data) {
            bootbox.dialog({
                message: data
            });
            controller.updateLog();
        });

        e.preventDefault();
    },

    uninstall: function(e) {
        var controller = this;
        var t = this.find('.installed-container .console').html(controller.messages['removing']);
        t.show();
        active_console = t;
        jQuery.get(
            jQuery(e.target).attr("href")
        )
        .done(function(data) {
            controller.find('.installed-container .console').html(data);
            controller.checkInstalled();
            delay(function(){
                active_console.hide();
            }, 2000);
            controller.updateLog();
        });

        e.preventDefault();
    },

    liveSearch: function() {
        var livesearch = this.find('input[name="check-package"]');

        livesearch.on('keyup', function(e){
            var searchVal = jQuery(this).val();
            if(searchVal.length < 3) return;
            delay(function(){
                jQuery.ajax({
                    url: site+"list.json",
                    dataType: 'jsonp',
                    data: {'name': searchVal}
                })
                .success(function(data){
                    if(data['packages'].length <1 ) return;
                    var cont = livesearch.parent().find(".auto-search");
                    cont.html("").show();
                    for(var p in data['packages']) {
                        var t = data['packages'][p];
                        var dataattr = "data-action='prefill-package' data-packagename='"+ t.name + "'";
                        cont.append("<a class='btn btn-block btn-default prefill-package' " +
                            dataattr + "style='text-align: left;'>" + t.title +
                            " <small " + dataattr +">(" + t.authors + " - " + t.name + ")</small></a>");
                    }
                    livesearch.on('blur', function(){
                       cont.fadeOut();
                    });
                });
            }, 500 );
        });
    },

    prefill: function(e) {
        var target = jQuery(e.target);
        this.find('input[name="check-package"]').val(target.closest('a').text());
        this.find('input[name="check-package"]').data('packagename', target.data('packagename'));
        this.find('.auto-search').hide();
    },

    updateLog: function() {
        jQuery.get(baseurl+'getLog', function(data) {
            $('#extension-log').html(data);
            $('#extension-log').animate({ scrollTop: $('#extension-log')[0].scrollHeight }, "fast");
        });
    },

    clearLog: function() {
        $('#extension-log').html('');
        jQuery.get(baseurl+'clearLog', function(data) {});
    },


    events: {
        change: function(e, t){
            var controller = e.data;

        },

        click: function(e, t){
            var controller = e.data;
            var action = jQuery(e.target).data('action');
            switch(action) {
                case "update-check"      : controller.updateCheck(); break;
                case "update-run"        : controller.updateRun(); break;
                case "update-package"    : controller.updatePackage(e.originalEvent); break;
                case "check-package"     : controller.checkPackage(e.originalEvent); break;
                case "uninstall-package" : controller.uninstall(e.originalEvent); break;
                case "install-package"   : controller.install(e.originalEvent); break;
                case "prefill-package"   : controller.prefill(e.originalEvent); break;
                case "install-run"       : controller.installRun(e.originalEvent); break;
                case "generate-theme"    : controller.generateTheme(e.originalEvent); break;
                case "package-readme"    : controller.packageReadme(e.originalEvent); break;
                case "package-config"    : controller.packageConfig(e.originalEvent); break;
                case "clear-log"         : controller.clearLog(e.originalEvent); break;
            }
        }

    }





});
