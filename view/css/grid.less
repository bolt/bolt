/////////////////
// Semantic.gs // for LESS: http://lesscss.org/
/////////////////

// Defaults which you can freely override
@column-width: 60;
@gutter-width: 20;
@columns: 12;

// Utility variable — you should never need to modify this
@gridsystem-width: (@column-width*@columns) + (@gutter-width*@columns) * 1px;

// Set @total-width to 100% for a fluid layout
@total-width: @gridsystem-width;

// Uncomment these two lines and the star-hack width/margin lines below to enable sub-pixel fix for IE6 & 7. See http://tylertate.com/blog/2012/01/05/subpixel-rounding.html
// @min-width: 960;
// @correction: 0.5 / @min-width * 100 * 1%;

// The micro clearfix http://nicolasgallagher.com/micro-clearfix-hack/
.clearfix() {
	*zoom:1;
	
	&:before,
	&:after {
	    content:"";
	    display:table;
	}
	&:after {
	    clear:both;
	}
}


//////////
// GRID //
//////////

body {
	width: 100%;
	.clearfix;
}

.row(@columns:@columns) {
	display: block;
	width: @total-width*((@gutter-width + @gridsystem-width)/@gridsystem-width);
	margin: 0 @total-width*(((@gutter-width*.5)/@gridsystem-width)*-1);
	// *width: @total-width*((@gutter-width + @gridsystem-width)/@gridsystem-width)-@correction;
	// *margin: 0 @total-width*(((@gutter-width*.5)/@gridsystem-width)*-1)-@correction;
	.clearfix;
}
.column(@x,@columns:@columns) {
	display: inline;
	float: left;
	width: @total-width*((((@gutter-width+@column-width)*@x)-@gutter-width) / @gridsystem-width);
	margin: 0 @total-width*((@gutter-width*.5)/@gridsystem-width);
	// *width: @total-width*((((@gutter-width+@column-width)*@x)-@gutter-width) / @gridsystem-width)-@correction;
	// *margin: 0 @total-width*((@gutter-width*.5)/@gridsystem-width)-@correction;
}
.push(@offset:1) {
	margin-left: @total-width*(((@gutter-width+@column-width)*@offset) / @gridsystem-width) + @total-width*((@gutter-width*.5)/@gridsystem-width);
}
.pull(@offset:1) {
	margin-right: @total-width*(((@gutter-width+@column-width)*@offset) / @gridsystem-width) + @total-width*((@gutter-width*.5)/@gridsystem-width);
}