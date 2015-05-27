/*
 * CSSMIN: Compress CSS files
 */
module.exports = {
    /*
     * TARGET:  Concats and minified library css
     */
    installLibCss: {
        options: {
            compatibility: 'ie8',
            relativeTo: 'css/',
            target: 'css/',
            rebase: true
        },
        files: {
            '<%= path.dest.css %>/lib.css': [
                '<%= path.src.lib %>/jquery-ui-1.11.4.custom/jquery-ui.structure.min.css',  //  5 kb
                '<%= path.src.lib %>/jquery-ui-1.11.4.custom/jquery-ui.theme.min.css',      // 14 kb
                '<%= path.src.bower %>/magnific-popup/dist/magnific-popup.css',             //  9 kb
                '<%= path.src.lib %>/select2/select2.css',                                  // 19 kb
                '<%= path.src.lib %>/jquery-fileupload/jquery-fileupload-ui.css'            //  2 kb
            ]
        }
    }
};
