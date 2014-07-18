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
    
    
    updateCheck: function() {
        this.find('.update-container').show();
        var target = this.find(".update-output" );
        active_console = target;
        $.get(baseurl+'check', function(data) {
            target.html(data);
        });            
    },
    
    updateRun: function() {
        this.find('.update-container').show();
        var target = this.find(".update-output" );
        active_console = target;
        $.get(baseurl+'update', function(data) {
            target.html(data);
        });
    },
    
    checkInstalled: function() {
        this.find('.installed-container').each(function(){
            active_console = jQuery(this).find(".console");
            var target = jQuery(this).find('.installed-list');
            jQuery.get(baseurl+"installed", function(data) {
                target.show();
                if(data.length > 0) {
                    active_console.html(data.length + " installed extension(s).");
                    delay(function(){
                        active_console.hide();
                    }, 7000);
                    target.find('.installed-list-items').html('');
                    for(var e in data) {
                        ext = data[e];
                        target.find('.installed-list-items').append("<tr><td class='ext-list'><strong class='title'>"+ext.title+"</strong></td><td> "+ext.description+"</td><td>"+ext.authors+"</td><td> <a action='package-uninstall' class='btn btn-mini btn-danger' href='"+baseurl+"uninstall?package="+ext.name+"'>Uninstall</a></td></tr>");
                    } 
                } else {
                    target.find('.installed-list-items').html("<td colspan='4'><strong>No Bolt Extensions installed.</strong></td>");
                    active_console.hide();
                }
            });
        });
    },
    
    checkPackage: function() {
        var ext = this.find('input[name="check-package"]').val();
        active_console = this.find(".check-package");
        $.ajax({
            url: site+'list.json',
            dataType: 'jsonp',
            data: {'name': ext},
        })
        .success(function(data) {
            var pack = data.packages[0];
            var tpl = '';
            tpl+='<input type="hidden" name="package-name" value="'+pack.name+'">'
            tpl+='<label>Select Version To Install</label><select name="package-version">';
            for(var v in pack.versions) {
                tpl+='<option value="'+pack.versions[v]+'">'+pack.versions[v]+'</option>'
            }
            tpl +='</select><a class="btn install-package"><i class="icon-gears"></i> Install Extension</a>';
            $(".check-package").replaceWith(tpl);
        })
        .fail(function() {
            alert('Bolt Extension could not be found. Please check at {{site}} to ensure correct name.')
        });
        
        e.preventDefault();  
    },
    
    install: function(e) {
        console.log(e);
       var package = $('input[name="package-name"]').val();
        var version = $('select[name="package-version"]').val();
        $('.install-response-container').show();
        active_console = $('.install-response-container .console');
        $.get(
            '/bolt/extend/install', 
            {'package':package,'version':version}
        ) 
        .done(function(data) {
            $('.install-response-container .console').html(data);
            checkInstalled();
        });
        e.preventDefault();
    },
    
    uninstall: function(e) {
        console.log(e);
        var t = this.find('.installed-container .console').html('Preparing to remove package...');
        t.show();
        active_console = t;
        jQuery.get(
            $(this).attr("href")
        )
        .done(function(data) {
            this.find('.installed-container .console').html(data);
            this.checkInstalled();
        });
            
        e.preventDefault();
    },
    
    liveSearch: function() {
        var livesearch = this.find('input[name="check-package"]');   
        
        livesearch.on('keyup', function(e){
            var searchVal = $(this).val();
            if(searchVal.length < 3) return;
            delay(function(){
                $.ajax({
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
                        cont.append("<a class='btn btn-block prefill-package'>"+t.name+"</a>");
                        
                    }
                });
            }, 500 );
        });
    },
    


    events: {
        change: function(e, t){
            var controller = e.data;
            
        },
        
        click: function(e, t){
            var controller = e.data;
            switch($(e.target).data('action')) {
                case "update-check"     : controller.updateCheck(); break;
                case "update-run"       : controller.updateRun(); break;
                case "check-package"    : controller.checkPackage(); break;
                case "package-uninstall": controller.uninstall(e.originalEvent); break;
                case "package-install"  : controller.install(e.originalEvent); break;
            }
        }

    }





});