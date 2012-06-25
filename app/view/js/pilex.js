
// Don't break on browsers without console.log();
try { console.assert(1); } catch(e) { console = { log: function() {}, assert: function() {} } }


jQuery(function($) {

    // Do stuff.



    // For editing content.. 
    if ($('.redactor').is('*')) {
		$('.redactor').redactor({ autoresize: false, resize: true, cleanUp: true, css: 'style_pilex.css' });
	}

	

});

