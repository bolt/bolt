/**
 * Initialize 'moment' timestamps.
 */

var momentstimeout;

function updateMoments() {
    $('time.moment').each(function () {
        var stamp = moment($(this).attr('datetime'));
        $(this).html(stamp.fromNow());
    });
    clearTimeout(momentstimeout);
    momentstimeout = setTimeout(function () {
        updateMoments();
    }, 16 * 1000);
}

/**
 * Auto-update the 'latest activity' widget.
 */
function updateLatestActivity() {
    $.get(asyncpath + 'latestactivity', function (data) {
        $('#latesttemp').html(data);
        updateMoments();
        $('#latestactivity').html($('#latesttemp').html());
    });

    setTimeout(function () {
        updateLatestActivity();
    }, 30 * 1000);
}
