/**
 * Make the 'video embed' field work.
 *
 *
 * @mixin
 * @namespace Bolt.fields.video
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    'use strict';

    /*
     * Bolt.fields.video mixin container.
     */
    var video = {};

    /**
     * Initialise video field.
     *
     * @static
     * @function init
     * @memberof Bolt.fields.video
     *
     * @param {Object} fieldset
     * @param fconf
     */
    video.init = function (fieldset, fconf) {
        console.log(fieldset);
        bolt.fields.video.bind(fieldset);
    };

    /**
     * bind
     *
     * @static
     * @function bind
     * @memberof Bolt.fields.video
     *
     * @param {string} key - Id of the video element.
     */
    video.bind = function (fieldset) {

        $(fieldset).find('[data-video="main"]').bind(
            'propertychange input',
            function () {
                clearTimeout(timeout);
                timeout = setTimeout(
                    function () {
                        update(fieldset);
                    },
                    400
                );
            }
        );

        $(fieldset).find('[data-video="width"]').bind(
            'propertychange input',
            function () {
                if ($(fieldset).find('[data-video="ratio"]').val() > 0) {
                    $(fieldset).find('[data-video="height"]').val(
                        Math.round($(fieldset).find('[data-video="width"]').val() / $(fieldset).find('[data-video="ratio"]').val())
                    );
                }
            }
        );

        $(fieldset).find('[data-video="height"]').bind(
            'propertychange input',
            function () {
                if ($(fieldset).find('[data-video="ratio"]').val() > 0) {
                    $(fieldset).find('[data-video="width"]').val(
                        Math.round($(fieldset).find('[data-video="height"]').val() * $(fieldset).find('[data-video="ratio"]').val())
                    );
                }
            }
        );
    };

    /**
     * Timeout Id.
     *
     * @private
     * @type {number}
     */
    var timeout;

    /**
     * Gets video embedding info from http://api.embed.ly and then updates video fields
     *
     * @static
     * @private
     * @function update
     * @memberof Bolt.video
     *
     * @param {string} key - Id of the video element.
     */
    var update = function (fieldset) {
        // Embed endpoint https://api.embed.ly/1/oembed?format=json&callback=:callbackurl=
        var endpoint = 'https://api.embed.ly/1/oembed?format=json&key=51fa004148ad4d05b115940be9dd3c7e&url=',
            val = $(fieldset).find('[data-video="main"]').val(),
            url = endpoint + encodeURI(val);

        // If val is emptied, clear the video fields.
        if (val.length < 2) {
            $(fieldset).find('[data-video="html"]').val('');
            $(fieldset).find('[data-video="width"]').val('');
            $(fieldset).find('[data-video="height"]').val('');
            $(fieldset).find('[data-video="ratio"]').val('');
            $(fieldset).find('[data-video="text"]').html('');
            $('#myModal').find('.modal-body').html('');
            $(fieldset).find('[data-video="authorname"]').val('');
            $(fieldset).find('[data-video="authorurl"]').val('');
            $(fieldset).find('[data-video="title"]').val('');
            $(fieldset).find('[data-video="thumbcontainer"]').html('');
            $(fieldset).find('[data-video="thumbnail"]').val('');
            return;
        }

        $.getJSON(url, function (data) {
            if (data.html) {
                $(fieldset).find('[data-video="html"]').val(data.html);
                $(fieldset).find('[data-video="width"]').val(data.width);
                $(fieldset).find('[data-video="height"]').val(data.height);
                $(fieldset).find('[data-video="ratio"]').val(data.width / data.height);
                $(fieldset).find('[data-video="text"]').html('"<b>' + data.title + '</b>" by ' + data.author_name);
                $('#myModal').find('.modal-body').html(data.html);
                $(fieldset).find('[data-video="authorname"]').val(data.author_name);
                $(fieldset).find('[data-video="authorurl"]').val(data.author_url);
                $(fieldset).find('[data-video="title"]').val(data.title);
            }

            if (data.thumbnail_url) {
                $(fieldset).find('[data-video="thumbcontainer"]').html('<img src="' + data.thumbnail_url + '" width="200" height="150">');
                $(fieldset).find('[data-video="thumnail"]').val(data.thumbnail_url);
            }
        });
    };


    // Apply mixin container
    bolt.fields.video = video;

})(Bolt || {}, jQuery);
