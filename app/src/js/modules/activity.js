/**
 * Auto-update the 'latest activity' widget.
 *
 * @mixin
 * @namespace Bolt.activity
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 */
(function (bolt, $) {
    /**
     * Bolt.activity mixin container.
     *
     * @private
     * @type {Object}
     */
    var activity = {};

    /**
     * Initializes the mixin.
     *
     * @static
     * @function init
     * @memberof Bolt.activity
     */
    activity.init = function () {
        if ($('#latestactivity').is('*')) {
            console.log('activity.init');
            setTimeout(
                function () {
                    //console.log('bolt.activity.update()');
                    bolt.activity.update();
                },
                intervall
            );
        }
    };

    /**
     * Initializes the mixin.
     *
     * @static
     * @function update
     * @memberof Bolt.activity
     */
    activity.update = function () {
        $.get(
            bolt.conf('paths.async') + 'latestactivity',
            function (data) {
                var newActivity = $(data).find('.buic-moment').each(
                        function () {
                            bolt.buic.moment.init(this);
                        }
                    ).end();

                $('#latestactivity').empty().append(newActivity);
            }
        );

        setTimeout(
            function () {
                bolt.activity.update();
            },
            intervall
        );
    };

    /**
     * Update intervall.
     *
     * @private
     * @constant {number} intervall
     * @memberof Bolt.activity
     */
    var intervall = 30 * 1000; // 30 seconds

    // Apply mixin container
    bolt.activity = activity;

})(Bolt || {}, jQuery);
