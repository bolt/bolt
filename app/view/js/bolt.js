
// Don't break on browsers without console.log();
try { console.assert(1); } catch(e) { console = { log: function() {}, assert: function() {} } }

jQuery(function($) {

    // Any link with a class='confirm' gets a confirmation dialog.. 
    $('a.confirm').click(function(){
        return confirm( $(this).data('confirm') );
    });

    // For editing content.. 
    if ($('.redactor').is('*')) {
		$('.redactor').redactor({ autoresize: false, css: 'style_bolt.css' });
	}

	// Initialize the Shadowbox shizzle.
	Shadowbox.init({ 
	   animate: true, 
	   overlayColor: "#DDD", 
	   overlayOpacity: 0.7, 
	   viewportPadding: 40
    });
	
    // Show 'dropzone' for jQuery file uploader. 
    // TODO: make it prettier, and distinguish between '.in' and '.hover'.
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

    // Add Date and Timepickers..
    $(".datepicker").datepicker({ dateFormat: "DD, d MM yy" });

    $.mask.definitions['2']='[0-2]';
    $.mask.definitions['5']='[0-5]';

    $(".timepicker").mask("29:59");

    // initialize 'moment' timestamps..
    if ($('.moment').is('*')) {
        updateMoments();
    }

    // Auto-update the 'latest activity' widget..
    if ($('#latestactivity').is('*')) {
        setTimeout( function(){ updateLatestActivity(); }, 20 * 1000);
    }

	// Hackish fix for an issue on Ipad, where dropdown menus wouldn't be clickable. Hopefully fixed in Bootstrap 2.1.2
    // See https://github.com/twitter/bootstrap/issues/2975
	$('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });

    // Strictly speaking it's not allowed to use <a> inside a <button>, so Firefox ignores the
    // links in our dropdowns. Workaround:
    $('button.uselink').bind('click', function() {
        var link = $(this).find('a').attr('href');
        if (link != "") { window.location = link; }
    });

});


/**
 *
 * Initialize 'moment' timestamps..
 *
 */
function updateMoments() {

    $('time.moment').each(function(){
        var stamp = moment($(this).attr('datetime'));
        $(this).html( stamp.fromNow() );
    });
    clearTimeout(momentstimeout);
    momentstimeout = setTimeout( function(){ updateMoments(); }, 16 * 1000);

}

var momentstimeout;

/**
 *
 * Auto-update the 'latest activity' widget..
 *
 */
function updateLatestActivity() {

    $.get(asyncpath+'latestactivity', function(data) {
        $('#latesttemp').html(data);
        updateMoments();
        $('#latestactivity').html( $('#latesttemp').html() );
    });

    setTimeout( function(){ updateLatestActivity(); }, 30 * 1000);

}


/**
 *
 * Bind the file upload when editing content, so it works and stuff
 *
 */
function bindFileUpload(key) {

    // Since jQuery File Upload's 'paramName' option seems to be ignored,
    // it requires the name of the upload input to be "images[]". Which clashes
    // with the non-fancy fallback, so we hackishly set it here. :-/
    $('#fileupload-' + key).attr('name', 'files[]')
        .fileupload({
            dataType: 'json',
            dropZone: $('#dropzone-' + key),
            done: function (e, data) {
                $.each(data.result, function (index, file) {
                    var filename = decodeURI(file.url).replace("/files/", "");
                    $('#field-' + key).val(filename);
                    $('#thumbnail-' + key).html("<img src='" + path + "../thumbs/120x120c/"+encodeURI(filename)+"' width='120' height='120'>");
                    $('#progress-' + key + ' div.bar').css('width', "100%");
                    $('#progress-' + key).removeClass('progress-striped active');
                    window.setTimeout(function(){ $('#progress-' + key).fadeOut('slow'); }, 3000);
                });
            }
        })
        .bind('fileuploadprogress', function (e, data) {
            var progress = Math.round(100 * data._bitrateTimer.loaded / data.files[0].size);
            $('#progress-' + key).show().addClass('progress-striped active');
            $('#progress-' + key + ' div.bar').css('width', progress+"%");
        });
          
    
}


/**
 *
 * Functions for working with the automagic URI/Slug generation.
 *
 */
function makeUri(contenttypeslug, id, usesfield, slugfield, fulluri) {

    $('#'+usesfield).bind('propertychange input', function() {
        var field = $('#'+usesfield).val();
        clearTimeout(makeuritimeout);
        makeuritimeout = setTimeout( function(){ makeUriAjax(field, contenttypeslug, id, usesfield, slugfield, fulluri); }, 200);
    });

}

var makeuritimeout;

function makeUriAjax(field, contenttypeslug, id, usesfield, slugfield, fulluri) {
    $.ajax({
        url: asyncpath + 'makeuri',
        type: 'GET',
        data: { title: field, contenttypeslug: contenttypeslug, id: id, fulluri: fulluri },
        success: function(uri) {
            $('#'+slugfield).val(uri);
            $('#show-'+slugfield).html(uri);
        },
        error: function() {
            console.log('failed to get an URI');
        }
    });
}


/**
 *
 * Making the 'video embed filetype work.
 *
 */
function bindVideoEmbed(key) {

    $('#video-'+key).bind('propertychange input', function() {
        clearTimeout(videoembedtimeout);
        videoembedtimeout = setTimeout( function(){ bindVideoEmbedAjax(key); }, 400);
    });

    $('#video-'+key+'-width').bind('propertychange input', function() {
        if ($('#video-'+key+'-ratio').val() > 0 ) {
            $('#video-'+key+'-height').val( Math.round($('#video-'+key+'-width').val() / $('#video-'+key+'-ratio').val()) );
        }
    });

    $('#video-'+key+'-height').bind('propertychange input', function() {
        if ($('#video-'+key+'-ratio').val() > 0 ) {
            $('#video-'+key+'-width').val( Math.round($('#video-'+key+'-height').val() * $('#video-'+key+'-ratio').val()) );
        }
    });


}

var videoembedtimeout;

function bindVideoEmbedAjax(key) {

    // oembed endpoint http://api.embed.ly/1/oembed?format=json&callback=:callbackurl=
    // TODO: make less dependant on key..
    var endpoint = "http://api.embed.ly/1/oembed?format=json&key=51fa004148ad4d05b115940be9dd3c7e&url=";
    var url = endpoint + encodeURI($('#video-'+key).val());

    console.log('url', url);

    $.getJSON(url, function(data) {
        console.log(data);
        if (data.html) {
            $('#video-'+key+'-html').val(data.html);
            $('#video-'+key+'-width').val(data.width);
            $('#video-'+key+'-height').val(data.height);
            $('#video-'+key+'-ratio').val(data.width / data.height);
            $('#video-'+key+'-text').html('"' + data.title + '" by ' + data.author_name);
            $('#myModal').find('.modal-body').html(data.html);
            $('#video-'+key+'-author_name').val(data.author_name);
            $('#video-'+key+'-author_url').val(data.author_url);
            $('#video-'+key+'-title').val(data.title);
        }

        if (data.thumbnail_url) {
            $('#thumbnail-'+key).html("<img src='" + data.thumbnail_url + "' width='160' height='120'>");
            $('#video-'+key+'-thumbnail').val(data.thumbnail_url);
        }

    });

}

