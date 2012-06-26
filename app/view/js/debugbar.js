
// Don't break on browsers without console.log();
try { console.assert(1); } catch(e) { console = { log: function() {}, assert: function() {} } }

// Jquery.cookie plugin, if it's not loaded yet.
jQuery.cookie=function(name,value,options){if(typeof value!='undefined'){options=options||{};if(value===null){value='';options=$.extend({},options);options.expires=-1;}var expires='';if(options.expires&&(typeof options.expires=='number'||options.expires.toUTCString)){var date;if(typeof options.expires=='number'){date=new Date();date.setTime(date.getTime()+(options.expires*24*60*60*1000));}else{date=options.expires;}expires='; expires='+date.toUTCString();}var path=options.path?'; path='+(options.path):'';var domain=options.domain?'; domain='+(options.domain):'';var secure=options.secure?'; secure':'';document.cookie=[name,'=',encodeURIComponent(value),expires,path,domain,secure].join('');}else{var cookieValue=null;if(document.cookie&&document.cookie!=''){var cookies=document.cookie.split(';');for(var i=0;i<cookies.length;i++){var cookie=jQuery.trim(cookies[i]);if(cookie.substring(0,name.length+1)==(name+'=')){cookieValue=decodeURIComponent(cookie.substring(name.length+1));break;}}}return cookieValue;}};


jQuery(function($) {
    
    $('#pilex-nipple').bind('click', function(){
        
        if ($('#pilex-debugbar').is(":visible")) {
            $('#pilex-debugbar, .pilex-debugpanel').fadeOut();
            $('#pilex-footer').fadeIn(); 
            $.cookie('pilex-debugbar-show', '');
        } else {
            $('#pilex-debugbar').fadeIn();
            $('#pilex-footer').fadeOut(); 
            $.cookie('pilex-debugbar-show', 1);
        }
        
    });
    
    $('#pilex-debugbar li a').bind('click', function(e){
        e.preventDefault();
        
        var forthis = "#" + $(this).data('for');
        
        $('.pilex-debugpanel').not(forthis).fadeOut();
        
        if ($(forthis).is(":visible")) {
            $(forthis).fadeOut();
        } else {
            $(forthis).fadeIn();
        }
        
    });
    

    // Initialise the debugbar and open it, if there's a cookie set..
    if ($.cookie('pilex-debugbar-show')==1) {
        setTimeout( function(){ $('#pilex-debugbar').fadeIn(); }, 250 ); 
    } else {
        setTimeout( function(){ $('#pilex-debugbar').fadeOut(); }, 250 ); 
    }
    

});