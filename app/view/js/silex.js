
// Don't break on browsers without console.log();
if (typeof(console) === 'undefined') { console = { log: function() {}, assert: function() {} }; }


jQuery(function($) {

    // Do stuff.




});


$(window).resize(function() {
    $('.fitlisting').each(function() {
        var row_width = $(this).width();
        var cols = $(this).find('td');
        var left = cols[0];
        var lcell_width = $(left).width();
        var lspan_width = $(left).find('span').width();
        var right = cols[1];
        var rcell_width = $(right).width();
        var rspan_width = $(right).find('span').width();

        if (lcell_width < lspan_width) {
            $(left).width(row_width - rcell_width);
        } else if (rcell_width > rspan_width) {
            $(left).width(row_width / 2);
        }
    });
});

