/**
 * Debugbar.js
 */


try { console.assert(1); } catch(e) { console = { log: function() {}, assert: function() {} } }

// Jquery.cookie plugin, if it's not loaded yet.
jQuery.cookie=function(name,value,options){if(typeof value!='undefined'){options=options||{};if(value===null){value='';options=$.extend({},options);options.expires=-1;}var expires='';if(options.expires&&(typeof options.expires=='number'||options.expires.toUTCString)){var date;if(typeof options.expires=='number'){date=new Date();date.setTime(date.getTime()+(options.expires*24*60*60*1000));}else{date=options.expires;}expires='; expires='+date.toUTCString();}var path=options.path?'; path='+(options.path):'';var domain=options.domain?'; domain='+(options.domain):'';var secure=options.secure?'; secure':'';document.cookie=[name,'=',encodeURIComponent(value),expires,path,domain,secure].join('');}else{var cookieValue=null;if(document.cookie&&document.cookie!=''){var cookies=document.cookie.split(';');for(var i=0;i<cookies.length;i++){var cookie=jQuery.trim(cookies[i]);if(cookie.substring(0,name.length+1)==(name+'=')){cookieValue=decodeURIComponent(cookie.substring(name.length+1));break;}}}return cookieValue;}};

jQuery(function($) {
    $('#bolt-nipple').bind('click', function(){
        if ($('#bolt-debugbar').is(":visible")) {
            $('#bolt-debugbar').stop(true, true).css('right', '0').animate({
                right: '-=800',
                opacity: 0.1
            }, 600);
            $('#bolt-debugbar, .bolt-debugpanel').fadeOut();
            $.cookie('bolt-debugbar-show', '');
        } else {
            $('#bolt-debugbar').stop(true, true).css('right', '-800px').show().animate({
                right: '+=800',
                opacity: 1.0
            }, 600);
            $.cookie('bolt-debugbar-show', 1);
        }
    });

    $('#bolt-debugbar li').not('#pd-edit, #pd-bolt').find('a').bind('click', function(e){
        e.preventDefault();

        var forthis = "#" + $(this).data('for');

        $('.bolt-debugpanel').not(forthis).fadeOut();
        if ($(forthis).is(":visible")) {
            $(forthis).fadeOut();
        } else {
            $(forthis).fadeIn();
        }

        // special case: editors..
        if ($(this).data('for') == "bolt-editpanel") {
            setEditable();
        }

    });

    // Initialise the debugbar and open it, if there's a cookie set..
    if ($.cookie('bolt-debugbar-show')==1) {
        $('#bolt-debugbar').show().css('right', '0').css('opacity', 1.0).css('display', 'block');
    } else {
        $('#bolt-debugbar').hide();
    }

    // Bind the 'save' button in the floating edit bar.
    $('a#btn-save').bind('click', function(e) {
        e.preventDefault();

        console.log('save');

        $('.currentedit').attr('contentEditable', false);

        data = {
            'id': $('.currentedit').data('id'),
            'contenttype': $('.currentedit').data('contenttype'),
            'field': $('.currentedit').data('field'),
            'value': $('.currentedit').html()
        }

        console.log(data);

        $.ajax({
            url: '/bolt/updatefield',
            type: 'GET',
            data: data,
            success: function(data) {
                $('.result').html(data);
                console.log('Data saved.');
                $('#editbar').hide();
            },
            error: function() {
                alert('Post failed.');
            }
        });
    });

    // Bind the 'cancel' button in the floating edit bar.
    $('a#btn-cancel').bind('click', function(e) {
        e.preventDefault();

        $('#editbar').hide();
        $('.currentedit').html( $('.currentedit').data('old') ).attr('contentEditable', false);

    });

    // Bind the 'edit in backend' button in the floating edit bar.
    $('a#btn-editbackend').bind('click', function(e) {
        e.preventDefault();

        var link = "/bolt/edit/" + $('.currentedit').data('contenttype') + "/" + $('.currentedit').data('id');

        document.location = link;

    });

    // Bind the 'bold' button in the floating edit bar.
    $('a#btn-bold').bind('click', function(e) {
        e.preventDefault();

        document.execCommand('bold', false, null);

        $('.currentedit').focus();
    });

    // Bind the 'italic' button in the floating edit bar.
    $('a#btn-italic').bind('click', function(e) {
        e.preventDefault();

        document.execCommand('italic', false, null);

        $('.currentedit').focus();

    });

    // Bind the 'italic' button in the floating edit bar.
    $('a#btn-link').bind('click', function(e) {
        e.preventDefault();

        var link = prompt("Link to:", "http://");
        document.execCommand("CreateLink", false, link);

        $('.currentedit').focus();
    });

    // document.execCommand("CreateLink", false, "http://stackoverflow.com/");
});


function setEditable(elem) {
    $('.bolt-editable').addClass('active');

    $('.bolt-editable').bind('click', function(e) {
        e.preventDefault();

        $('.bolt-editable').unbind('click').removeClass('active');
        $('.currentedit').removeClass('currentedit').attr('contentEditable', false);

        $(this).addClass('currentedit').attr('contentEditable', '').focus();

        // hide Debugbar panels.
        $('.bolt-debugpanel').fadeOut();

        // Set the fly-over menu to the correct position..
        var offset = $(this).offset();
        $('#editbar').show().css('left', offset.left - 3).css('top', offset.top - 34);

        // Set the old values into data-old
        $(this).data('old', $(this).html() );

        console.log("Data: " , $(this).data('id') );
    });
}


function saveSelection() {
    if (window.getSelection) {
        sel = window.getSelection();
        if (sel.getRangeAt && sel.rangeCount) {
            var ranges = [];
            for (var i = 0, len = sel.rangeCount; i < len; ++i) {
                ranges.push(sel.getRangeAt(i));
            }
            return ranges;
        }
    } else if (document.selection && document.selection.createRange) {
        return document.selection.createRange();
    }
    return null;
}

function restoreSelection(savedSel) {
    if (savedSel) {
        if (window.getSelection) {
            sel = window.getSelection();
            sel.removeAllRanges();
            for (var i = 0, len = savedSel.length; i < len; ++i) {
                sel.addRange(savedSel[i]);
            }
        } else if (document.selection && savedSel.select) {
            savedSel.select();
        }
    }
}
