/**
 * Making the 'video embed' filetype work.
 */

var videoembedtimeout;

function bindVideoEmbedAjax(key) {
    // oembed endpoint http://api.embed.ly/1/oembed?format=json&callback=:callbackurl=
    // @todo make less dependant on key.
    var endpoint = 'http://api.embed.ly/1/oembed?format=json&key=51fa004148ad4d05b115940be9dd3c7e&url=',
        val = $('#video-' + key).val(),
        url = endpoint + encodeURI(val);

    // If val is emptied, clear the video fields.
    if (val.length < 2) {
        $('#video-' + key + '-html').val('');
        $('#video-' + key + '-width').val('');
        $('#video-' + key + '-height').val('');
        $('#video-' + key + '-ratio').val('');
        $('#video-' + key + '-text').html('');
        $('#myModal').find('.modal-body').html('');
        $('#video-' + key + '-author_name').val('');
        $('#video-' + key + '-author_url').val('');
        $('#video-' + key + '-title').val('');
        $('#thumbnail-' + key).html('');
        $('#video-' + key + '-thumbnail').val('');
        return;
    }

    $.getJSON(url, function (data) {
        if (data.html) {
            $('#video-' + key + '-html').val(data.html);
            $('#video-' + key + '-width').val(data.width);
            $('#video-' + key + '-height').val(data.height);
            $('#video-' + key + '-ratio').val(data.width / data.height);
            $('#video-' + key + '-text').html('"<b>' + data.title + '</b>" by ' + data.author_name);
            $('#myModal').find('.modal-body').html(data.html);
            $('#video-' + key + '-author_name').val(data.author_name);
            $('#video-' + key + '-author_url').val(data.author_url);
            $('#video-' + key + '-title').val(data.title);
        }

        if (data.thumbnail_url) {
            $('#thumbnail-' + key).html('<img src="' + data.thumbnail_url + '" width="200" height="150">');
            $('#video-' + key + '-thumbnail').val(data.thumbnail_url);
        }
    });
}

function bindVideoEmbed(key) {
    $('#video-' + key).bind('propertychange input', function () {
        clearTimeout(videoembedtimeout);
        videoembedtimeout = setTimeout(function () {
            bindVideoEmbedAjax(key);
        }, 400);
    });

    $('#video-' + key + '-width').bind('propertychange input', function () {
        if ($('#video-' + key + '-ratio').val() > 0) {
            $('#video-' + key + '-height').val(Math.round(
                $('#video-' + key + '-width').val() / $('#video-' + key + '-ratio').val()
            ));
        }
    });

    $('#video-' + key + '-height').bind('propertychange input', function () {
        if ($('#video-' + key + '-ratio').val() > 0) {
            $('#video-' + key + '-width').val(Math.round(
                $('#video-' + key + '-height').val() * $('#video-' + key + '-ratio').val()
            ));
        }
    });
}
