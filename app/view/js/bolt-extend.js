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

    constructor: function(){
        jQuery(this.selector).on("change", this, this.events.change);
        jQuery(this.selector).on("click", this, this.events.click);
        
        jQuery(document).ajaxStart(function() {
            // show loader on start
            active_interval = setInterval(function(){
                active_console.append(".");
            },1000);
        }).ajaxSuccess(function() {
            clearInterval(active_interval);
        });
        
        this.checkInstalled();
        this.liveSearch();
        
    },
    
    find: function(selector) {
        return jQuery(this.selector).find(selector);
    },
    
    setMessage: function(key, value) {
        this.messages[key]=value;
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
        });            
    },
    
    updateRun: function() {
        var controller = this;
        
        controller.find('.update-container').show();
        var target = controller.find(".update-output" );
        active_console = target;
        active_console.html("Running update....");
        jQuery.get(baseurl+'update', function(data) {
            target.html(data);
            setTimeout(function(){
                controller.find('.update-container').hide();
            },7000);
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
                    delay(function(){
                        controller.find(".installed-container .console").hide();
                    },7000);
                    target.find('.installed-list-items').html('');
                    for(var e in data) {
                        ext = data[e];
                        target.find('.installed-list-items').append("<tr><td class='ext-list'><strong class='title'>"+ext["name"]+"</strong></td><td>"+ext["version"]+"</td><td> "+ext["type"]+"</td><td> "+ext["descrip"]+"</td><td> <a data-action='uninstall-package' class='btn btn-sm btn-danger' href='"+baseurl+"uninstall?package="+ext["name"]+"'>Uninstall</a></td></tr>");
                    } 
                } else {
                    target.find('.installed-list-items').html("<tr><td colspan='4'><strong>No Bolt Extensions installed.</strong></td></tr>");
                    active_console.hide();
                }
            });
        });
    },
    
    checkPackage: function(e) {
        var controller = this;
        var ext = this.find('input[name="check-package"]').val();
        active_console = this.find(".check-package");
        jQuery.get(baseurl+'installInfo?package='+ext, function(data) {
            
            var devpacks = data['dev'];
            var stablepacks = data['stable'];
            
            if(devpacks.length > 0) {
              controller.find('.dev-version-container .installed-version-item').html("");  
            }
            
            if(stablepacks.length > 0) {
              controller.find('.stable-version-container .installed-version-item').html("");  
            }

            for(var v in devpacks) {
                version = devpacks[v];
                var tpl = '<tr><td>'+version.name+'</td><td>'+version.version+'</td>';
                tpl = tpl+'<td><div class="btn-group"><a href="#" data-action="install-package" class="btn btn-primary btn-sm" data-package="'+version.name+'" data-version="'+version.version+'">'
                tpl = tpl+'<i class="icon-gears"></i> Install This Version</a></div></td></tr>';
                controller.find('.dev-version-container .installed-version-item').append(tpl);
            }
            for(var v in stablepacks) {
                version = stablepacks[v];
                var tpl = '<tr><td>'+version.name+'</td><td>'+version.version+'</td>';
                tpl = tpl+'<td><div class="btn-group"><a href="#" data-action="install-package" class="btn btn-primary btn-sm" data-package="'+version.name+'" data-version="'+version.version+'">';
                tpl = tpl+'<i class="icon-gears"></i> Install This Version</a></div></td></tr>';
                controller.find('.stable-version-container .installed-version-item').append(tpl);
            }
            
            controller.find(".install-version-container").show();
            controller.find("#installModal .loader").hide();
        });
        
            
        
        
        e.preventDefault();  
    },
    
    install: function(e) {
        var controller = this;
        
        var package = jQuery(e.target).data("package");
        var version = jQuery(e.target).data("version");
        
        controller.find('.install-response-container').show();
        controller.find('.install-version-container').hide();
        active_console = controller.find('.install-response-container .console');
        controller.find("#installModal .loader .message").html(controller.messages['installing']);
        jQuery.get(
            baseurl+'install', 
            {'package':package,'version':version}
        ) 
        .done(function(data) {
            active_console.html(data);
            controller.postInstall(package, version);
            setTimeout(function(){
                controller.find('.install-response-container').hide();
            }, 5000);
            controller.find(".check-package").show()
            controller.find('input[name="check-package"]').val('');
            controller.checkInstalled();
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
        });
    },
    
    extensionPostInstall: function(extension) {
        var controller = this;
        jQuery('#installModal').on('hide.bs.modal', function (e) {
            controller.find('.extension-postinstall').hide();
            controller.find("#installModal .loader").show();
        })
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
        var theme = jQuery(e.target).data("theme");
        var themename  = controller.find('#theme-name');
        alert(themename);
        jQuery.get(
            baseurl+'generateTheme', 
            {'theme':theme,'name':themename}
        )
        .done(function(data) {
            console.log(data);
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
                        cont.append("<a data-action='prefill-package' class='btn btn-block btn-tertiary prefill-package'>"+t.name+"</a>");
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
        console.log(target.text());
        this.find('input[name="check-package"]').val( target.text());
        target.parent().hide();
    },
    


    events: {
        change: function(e, t){
            var controller = e.data;
            
        },
        
        click: function(e, t){
            var controller = e.data;
            switch(jQuery(e.target).data('action')) {
                case "update-check"     : controller.updateCheck(); break;
                case "update-run"       : controller.updateRun(); break;
                case "update-package"   : controller.updatePackage(e.originalEvent); break;
                case "check-package"    : controller.checkPackage(e.originalEvent); break;
                case "uninstall-package": controller.uninstall(e.originalEvent); break;
                case "install-package"  : controller.install(e.originalEvent); break;
                case "prefill-package"  : controller.prefill(e.originalEvent); break;
                case "install-run"      : controller.installRun(e.originalEvent); break;
                case "generate-theme"   : controller.generateTheme(e.originalEvent); break;
            }
        }

    }





});