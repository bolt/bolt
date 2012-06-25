
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
	

});

