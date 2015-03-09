/*
 * CSSMIN: Compress CSS files
 */
module.exports = {
    installLibCss: {
        options: {
            compatibility: 'ie8',
            relativeTo: 'css/',
            target: 'css/'
        },
        files: {
            '<%= path.dest.css %>/lib.css': [
                '<%= path.src.lib %>/jquery-ui-1.10.3/jquery-ui.custom.min.css',            // 20 kb
                '<%= path.src.bower %>/magnific-popup/dist/magnific-popup.css',  //  9 kb
                '<%= path.src.lib %>/select2/select2.css',                                  // 19 kb
                '<%= path.src.lib %>/jquery-fileupload/jquery-fileupload-ui.css'            //  2 kb
            ]
        }
    }
};
