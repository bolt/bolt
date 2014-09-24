/**
 * Initialize 'moment' timestamps..
 */
function updateMoments() {

    $('time.moment').each(function(){
        var stamp = moment($(this).attr('datetime'));
        $(this).html(stamp.fromNow());
    });
    clearTimeout(momentstimeout);
    momentstimeout = setTimeout(function() { updateMoments(); }, 16 * 1000);

}

var momentstimeout;
