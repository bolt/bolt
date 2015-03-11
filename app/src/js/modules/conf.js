/*
 * Bolt module: Conf
 */
var BoltConf = (function (bolt, $) {
    /*
     * Bolt module configuration data
     */
    bolt.conf = {};

    /*
     * Initialize the Bolt module
     */
    bolt.initConf = function () {
        // Get configuration
        bolt.conf = $('script[data-config]').first().data('config');
    };

    return bolt;
})(Bolt || {}, jQuery);
