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
            'css/lib.css': [
                'lib/jquery-ui-1.10.3/jquery-ui.custom.min.css',            // 20 kb
                'bower_components/magnific-popup/dist/magnific-popup.css',  //  9 kb
                'lib/select2/select2.css',                                  // 19 kb
                'lib/jquery-fileupload/jquery-fileupload-ui.css'            //  2 kb
            ]
        }
    }
};
