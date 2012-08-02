
// Don't break on browsers without console.log();
try { console.assert(1); } catch(e) { console = { log: function() {}, assert: function() {} } }

jQuery(function($) {

    // Any link with a class='confirm' gets a confirmation dialog.. 
    $('a.confirm').click(function(){
        return confirm( $(this).data('confirm') );
    }); 

    // For editing content.. 
    if ($('.redactor').is('*')) {
		$('.redactor').redactor({ autoresize: false, resize: true, cleanUp: true, css: 'style_pilex.css' });
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

});

/**
 * Bind the file upload, so it works and stuff 
 */
function bindFileUpload(key) {
    
    $('#fileupload-' + key).fileupload({
        dataType: 'json',
        dropZone: $('#dropzone-' + key),
        done: function (e, data) {
            $.each(data.result, function (index, file) {
                $('#field-' + key).val(file.name);
                $('#thumbnail-' + key).html("<img src='/thumbs/120x120c/"+encodeURI(file.name)+"' width='120' height='120'>");
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
