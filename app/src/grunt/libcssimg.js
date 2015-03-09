/*
 * Copy lib images & rebase urls
 */
module.exports = function (grunt, option) {
    grunt.registerTask('libcssimg', 'Copy lib images & rebase urls', function () {
        var css = grunt.file.read(option.path.dest.css + '/lib.css'),
            out = css,
            urls = /url\((['"]?)(.+?)\1\)/g,
            repl = {
                'jquery-ui':    /^\.\.\/lib\/jquery-ui-.+?\/images\/ui-/,
                'select2':      /^\.\.\/lib\/select2\//,
                'jquery-upl':   /^\.\.\/lib\/jquery-fileupload\/img\//
            },
            done = {},
            url,
            dest,
            to;

        grunt.file.mkdir(option.path.dest.img + '/lib');

        while ((url = urls.exec(css)) !== null) {
            for (to in repl) {
                dest = url[2].replace(repl[to], option.path.dest.img + '/lib/' + to + '-');
                if (dest !== url[2]) {
                    if (!done[dest]) {
                        grunt.file.copy(url[2].replace(/^\.\.\/lib\//, 'lib/'), dest);
                        out = out.replace(
                            new RegExp(url[0].replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), 'g'),
                            'url(' + url[1] + '../' + dest + url[1] + ')'
                        );
                        done[dest] = 1;
                    }
                    break;
                } else {
                    dest = false;
                }
            }
            if (!dest && !url[2].match(/^data:/)) {
                grunt.fail.warn('URL not handled: ' + url[2]);
            }
        }
        grunt.file.write(option.path.dest.css + '/lib.css', out);
    });
};
