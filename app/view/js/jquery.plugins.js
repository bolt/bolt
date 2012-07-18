//
//	jQuery Slug Generation Plugin by Perry Trinier (perrytrinier@gmail.com)
//  Licensed under the GPL: http://www.gnu.org/copyleft/gpl.html
//  Modified for Pilex by Bob den Otter

jQuery.fn.slug = function(options) {
	var settings = {
		slug: 'slug', // Class used for slug destination input and span. The span is created on $(document).ready() 
		hide: true	 // Boolean - By default the slug input field is hidden, set to false to show the input field and hide the span. 
	};
	
	if(options) {
		jQuery.extend(settings, options);
	}
	
	$this = jQuery(this);

	jQuery(document).ready( function() {
		if (settings.hide) {
			jQuery('input.' + settings.slug).after("<span class="+settings.slug+"></span>");
			jQuery('input.' + settings.slug).hide();
		}
		makeSlug();
	});
	
	makeSlug = function() {
	
    	var slugcontent = $this.val().toLowerCase();
    	
    	// remove accents, swap ñ for n, etc
    	var from = "àáäâåèéëêìíïîòóöôøùúüûñç·/_,:;",
            to   = "aaaaaeeeeiiiiooooouuuunc------";
        for (var i=0, l=from.length ; i<l ; i++) {
            slugcontent = slugcontent.replace(from[i], to[i]);
        }
		
		slugcontent = slugcontent.replace(/\s/g,'-')
		          .replace(/[^a-zA-Z0-9\-]/g,'')
		          .replace(/[-]+/g,'-')
		          .replace(/^[\s|-]+|[\s|-]+$/g, '');
		
		jQuery('input.' + settings.slug).val(slugcontent);
		jQuery('span.' + settings.slug).text(slugcontent);
	}
		
	jQuery(this).bind('keyup', function(){ makeSlug() });
		
	return $this;
};