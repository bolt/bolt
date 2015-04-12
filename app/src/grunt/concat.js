/*
 * CONCAT: Concatenate files
 */
module.exports = {
    /*
     * TARGET:  Concats minified library scripts
     */
    installLibJs: {
        options: {
            separator: '\n\n',
            sourceMap: '<%= sourceMap %>',
            sourceMapName: '<%= path.dest.js %>/maps/lib.min.js.map'
        },
        nonull: true,
        src: [
            '<%= path.tmp %>/jquery.min.js',                                //  95 kb
            '<%= path.tmp %>/jquery.cookie.min.js',                         //   2 kb
            '<%= path.tmp %>/jquery.formatDateTime.min.js',                 //   3 kb
            '<%= path.tmp %>/jquery.tagcloud.min.js',                       //   2 kb
            '<%= path.tmp %>/underscore.min.js',                            //  16 kb
            '<%= path.tmp %>/backbone.min.js',                              //  19 kb
            '<%= path.tmp %>/bootbox.min.js',                               //   9 kb
            '<%= path.tmp %>/jquery.magnific-popup.min.js',                 //  21 kb
            '<%= path.src.lib %>/jquery-ui-1.10.3/jquery-ui.custom.min.js', //  96 kb
            '<%= path.tmp %>/bootstrap-file-input.min.js',                  //   1 kb
            '<%= path.tmp %>/jquery-hotkeys.min.js',                        //   2 kb
            '<%= path.tmp %>/jquery-iframe-transport.min.js',               //   2 kb
            '<%= path.tmp %>/jquery-fileupload.min.js',                     //  15 kb
            '<%= path.tmp %>/bootstrap.min.js',                             //   2 kb
            '<%= path.src.lib %>/select2/select2.min.js',                   //  66 kb
            '<%= path.tmp %>/moment.min.js',                                //  35 kb
            '<%= path.tmp %>/modernizr-custom.min.js'                       //   5 kb
        ],
        dest: '<%= path.dest.js %>/lib.min.js'
    }
};
