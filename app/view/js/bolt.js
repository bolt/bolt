
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


});

var momentstimeout;

/**
 * Initialize 'moment' timestamps..
 *
 */
function updateMoments() {

    $('.moment').each(function(){
        var stamp = moment($(this).data('timestamp'), "YYYY-MM-DD HH:mm:ss");
        $(this).html( stamp.fromNow() );
    });

    clearTimeout(momentstimeout);
    momentstimeout = setTimeout( function(){ updateMoments(); }, 21 * 1000);

}

/**
 * Auto-update the 'latest activity' widget..
 */
function updateLatestActivity() {

    $.get(path+'latestactivity?nodebug', function(data) {
        $('#latesttemp').html(data);
        updateMoments();
        $('#latestactivity').html( $('#latesttemp').html() );
    });

    setTimeout( function(){ updateLatestActivity(); }, 20 * 1000);

}


/**
 * Bind the file upload, so it works and stuff 
 */
function bindFileUpload(key) {
    
    $('#fileupload-' + key).fileupload({
        dataType: 'json',
        dropZone: $('#dropzone-' + key),
        done: function (e, data) {
            $.each(data.result, function (index, file) {
                var filename = decodeURI(file.url).replace("/files/", "");
                $('#field-' + key).val(filename);
                $('#thumbnail-' + key).html("<img src='/thumbs/120x120c/"+encodeURI(filename)+"' width='120' height='120'>");
                $('#progress-' + key + ' div.bar').css('width', "100%");
                $('#progress-' + key).removeClass('progress-striped active');
                window.setTimeout(function(){ $('#progress-' + key).fadeOut('slow'); }, 3000);
            });
        }
    });    
    
    $('#fileupload-' + key).bind('fileuploadprogress', function (e, data) {        
        var progress = Math.round(100 * data._bitrateTimer.loaded / data.files[0].size);
        $('#progress-' + key).show().addClass('progress-striped active');
        $('#progress-' + key + ' div.bar').css('width', progress+"%");
    });
          
    
}

var makeuritimeout;

function makeUri(contenttypeslug, id, usesfield, slugfield, fulluri) {

    $('#'+usesfield).bind('change keyup input', function() {
       
        var field = $('#'+usesfield).val();

        clearTimeout(makeuritimeout);
        makeuritimeout = setTimeout( function(){ makeUriAjax(field, contenttypeslug, id, usesfield, slugfield, fulluri); }, 200);

        
    });


    
    
}

function makeUriAjax(field, contenttypeslug, id, usesfield, slugfield, fulluri) {

    $.ajax({
        url: path + 'makeuri',
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