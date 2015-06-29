/* global module */

/*
 * CONCAT: Concatenate files
 */
module.exports = function (grunt, option) {
    var extractUrls = function(css) {
        var reUrls = /url\((['"]?)(.+?)\1\)/g,
            urls = [],
            url;

        while ((url = reUrls.exec(css)) !== null) {
            if (!url[2].match(/^data:/) && urls.indexOf(url[2]) < 0) {
                urls.push({
                    match: url[0],
                    path: url[2]
                });
            }
        }

        return urls;
    };

    var processLibCss = function(css, filepath) {
        var path = require('path'),
            reDir = /(jquery[-.]\w+|select2)/,
            urls = [],
            img = {},
            relativePath;

        // Extract urls from css.
        urls = extractUrls(css);

        // Process images.
        if (urls.length) {
            // Calculate the relative path from the css to the image folder.
            relativePath = path.relative(
                path.resolve() + '/' + option.path.dest.css,
                path.resolve() + '/' + option.path.dest.img
            ).replace('\\', '/');

            // Generate image destination folder.
            img.dir = (img.dir = reDir.exec(filepath)) ? img.dir[1].replace(/^jquery\./, 'jquery-') + '/' : '';

            for (var i in urls) {
                // Set up paths.
                img.src = path.dirname(filepath) + '/' + urls[i].path;
                img.dst = option.path.dest.img + '/lib/' + img.dir + path.basename(urls[i].path);
                img.url = relativePath + '/lib/' + img.dir + path.basename(urls[i].path);

                // Copy the image file.
                grunt.verbose.writeln('Copy: ' + img.src + '\n   => ' + img.dst);
                grunt.file.copy(img.src, img.dst);

                // Replace url() paths in css.
                css = css.replace(
                    new RegExp(urls[i].match.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), 'g'),
                    'url(' + img.url + ')'
                );
            }
        }

        return '/* Source: ' + filepath + '*/\n\n' + css;
    };

    return {
        /*
         * TARGET:  Concats minified library scripts
         */
        installLibJs: {
            options: {
                separator: '\n\n',
                sourceMap: '<%= sourcemap.js %>',
                sourceMapName: '<%= path.sourcemaps %>/lib.js.map'
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
                '<%= path.src.lib %>/jquery-ui-1.11.4.custom/jquery-ui.min.js', //  96 kb
                '<%= path.tmp %>/bootstrap-file-input.min.js',                  //   1 kb
                '<%= path.tmp %>/jquery-hotkeys.min.js',                        //   2 kb
                '<%= path.tmp %>/jquery.iframe-transport.min.js',               //   2 kb
                '<%= path.tmp %>/jquery.fileupload.min.js',                     //  15 kb
                '<%= path.tmp %>/bootstrap.min.js',                             //   2 kb
                '<%= path.src.lib %>/select2/select2.min.js',                   //  66 kb
                '<%= path.tmp %>/moment.min.js',                                //  35 kb
                '<%= path.tmp %>/modernizr-custom.min.js'                       //   5 kb
            ],
            dest: '<%= path.dest.js %>/lib.js'
        },

        /*
         * TARGET:  Concats library css
         */
        installLibCss: {
            options: {
                process: processLibCss,
                sourceMap: '<%= sourcemap.css %>',
                sourceMapName: '<%= path.sourcemaps %>/lib.css.map'
            },
            src: [
                '<%= path.src.lib %>/jquery-ui-1.11.4.custom/jquery-ui.structure.css',
                '<%= path.src.lib %>/jquery-ui-1.11.4.custom/jquery-ui.theme.css',
                '<%= path.src.lib %>/select2/select2.css',
                '<%= path.src.bower %>/blueimp-file-upload/css/jquery.fileupload.css',
                '<%= path.src.bower %>/blueimp-file-upload/css/jquery.fileupload-ui.css',
                '<%= path.src.bower %>/magnific-popup/dist/magnific-popup.css'
            ],
            dest: '<%= path.dest.css %>/lib.css'
        }
    };
};
