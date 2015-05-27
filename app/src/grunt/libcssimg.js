/*
 * Copy lib images & rebase urls
 */
module.exports = function (grunt, option) {
    grunt.registerTask('libcssimg', 'Copy lib images & rebase urls', function () {
        var pathLibCss = option.path.dest.css + '/lib.css',
            pathDestImg = option.path.dest.img + '/lib',
            pathRel = '../img/lib',
            urls = /url\((['"]?)(.+?)\1\)/g,
            repl = {
                'jquery-ui':    /^..\/..\/src\/lib\/jquery-ui-.+?\/images\/ui-/,
                'select2':      /^..\/..\/src\/lib\/select2\//,
                'jquery-upl':   /^..\/..\/src\/lib\/jquery-fileupload\/img\//
            },
            done = {},
            css,
            out,
            url,
            dest,
            to,
            newurl;

        grunt.verbose.writeln('Read css: ' + pathLibCss);
        css = grunt.file.read(pathLibCss);
        out = css;

        grunt.verbose.writeln('Create dir: ' + pathDestImg);
        grunt.file.mkdir(pathDestImg);

        while ((url = urls.exec(css)) !== null) {
            for (to in repl) {
                dest = url[2].replace(repl[to], pathDestImg + '/' + to + '-');
                if (dest !== url[2]) {
                    if (!done[dest]) {
                        grunt.verbose.writeln(
                            '- Copy: ' + url[2] + '\n' +
                            '    To: ' + dest
                        );
                        grunt.file.copy(url[2].replace(/^..\/..\/src\/lib\//, 'lib/'), dest);

                        newurl = 'url(' + url[1] + pathRel + dest.slice(pathDestImg.length) + url[1] + ')';
                        grunt.verbose.writeln(
                            '  Repl: ' + url[0] + '\n' +
                            '  With: ' + newurl
                        );
                        out = out.replace(new RegExp(url[0].replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), 'g'), newurl);

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

        grunt.verbose.writeln('Write: ' + pathLibCss);
        grunt.file.write(pathLibCss, out);
    });
};
