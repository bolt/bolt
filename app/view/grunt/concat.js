/*
 * CONCAT: Concatenate files
 */
module.exports = {
    installLibJs: {
        options: {
            separator: '\n\n',
            sourceMap: true,
            sourceMapName: 'js/maps/lib.min.js.map'
        },
        nonull: true,
        src: [
            'lib/tmp/jquery.min.js',                            //  95 kb
            'lib/tmp/jquery.cookie.min.js',                     //   2 kb
            'lib/tmp/jquery.formatDateTime.min.js',             //   3 kb
            'lib/tmp/jquery.tagcloud.min.js',                   //   2 kb
            'lib/tmp/underscore.min.js',                        //  16 kb
            'lib/tmp/backbone.min.js',                          //  19 kb
            'lib/tmp/bootbox.min.js',                           //   9 kb
            'lib/tmp/jquery.magnific-popup.min.js',             //  21 kb
            'lib/jquery-ui-1.10.3/jquery-ui.custom.min.js',     //  96 kb
            'lib/tmp/bootstrap-file-input.min.js',              //   1 kb
            'lib/tmp/jquery-hotkeys.min.js',                    //   2 kb
            'lib/tmp/jquery-watchchanges.min.js',               //   1 kb
            'lib/tmp/jquery-iframe-transport.min.js',           //   2 kb
            'lib/tmp/jquery-fileupload.min.js',                 //  15 kb
            'lib/tmp/bootstrap.min.js',                         //   2 kb
            'lib/select2/select2.min.js',                       //  66 kb
            'lib/tmp/moment.min.js',                            //  35 kb
            'lib/tmp/modernizr-custom.min.js'                   //   5 kb
        ],
        dest: 'js/lib.min.js'
    }
};
