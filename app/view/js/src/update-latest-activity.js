/**
 * Auto-update the 'latest activity' widget.
 */
function updateLatestActivity() {
    $.get(asyncpath+'latestactivity', function(data) {
        $('#latesttemp').html(data);
        updateMoments();
        $('#latestactivity').html($('#latesttemp').html());
    });

    setTimeout(function() { updateLatestActivity(); }, 30 * 1000);
}
