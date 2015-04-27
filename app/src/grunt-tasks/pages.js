/*
 * PAGES: Downloads rendered pages from a Bolt server
 */
module.exports = function (grunt) {
    grunt.registerTask('pages', 'Downloads rendered pages from a Bolt server', function () {
        var outpath,
            outfile,
            baseurl,
            pages,
            options,
            queue = [];

        // Require config variables.
        grunt.config.requires(
            'path.tmp',
            'pages.baseurl',
            'pages.requests'
        );

        // Create output directory inside tmp folder.
        outpath = grunt.config('path.tmp') + '/pages';
        grunt.file.mkdir(outpath);

        // Request all required pages.
        pages = grunt.config('pages.requests');
        for (var dest in pages) {
            // Set request options.
            if (typeof pages[dest] === 'object') {
                options = pages[dest];
            } else {
                options = {
                    url: pages[dest] !== '' ? pages[dest] : dest
                };
            }
            options.baseUrl = options.baseUrl || grunt.config('pages.baseurl');

            // Path, where to put the file. Make it always end with ".html"
            outfile = outpath + '/' + dest.replace(/^(.+)\.html$/, '$1') + '.html';

            // Some verbose output.
            grunt.verbose.writeln('Get page "' + outfile + '":');
            grunt.verbose.writeln(require('util').inspect(options, false, 2, true));

            // Build a request queue.
            queue.push({
                opt: options,
                out: outfile
            });
        }
    });
};
