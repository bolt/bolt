/**
 * Auto-update the 'latest activity' widget.
 */
function updateLatestActivity() {
    $.get(Bolt.conf('paths.async') + 'latestactivity', function (data) {
        $('#latesttemp').html(data);
        bolt.moments.update();
        $('#latestactivity').html($('#latesttemp').html());
    });

    setTimeout(function () {
        updateLatestActivity();
    }, 30 * 1000);
}
