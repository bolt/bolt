/*
 * PAGES: Downloads rendered pages from a Bolt server
 */
module.exports = function (grunt) {
    'use strict';

    grunt.registerTask('pages', 'Downloads rendered pages from a Bolt server', function () {
        var request = require('request'),
            done = this.async(),
            outpath,
            outfile,
            pages,
            options,
            queue = [];

        function getNextPage() {
            var next = queue.shift();

            if (next) {
                if (next.out.substr(0, 1) === '@') {
                    grunt.log.writeln('Execute "' + next.out.substr(1) + '"');
                } else {
                    grunt.log.writeln('Get page "' + next.out + '"');
                    grunt.verbose.writeln(require('util').inspect(next.opt, false, 2, true));
                }

                request(
                    next.opt,
                    function (error, response, body) {
                        if (!error && (response.statusCode < 200 || response.statusCode >= 300)) {
                            error = 'Status code: ' + response.statusCode;
                        }
                        if (error) {
                            grunt.fail.warn(error);
                            return done(false);
                        }
                        // Write response body to file.
                        if (next.out.substr(0, 1) !== '@') {
                            grunt.file.write(next.out, body);
                        }
                        // Go on with next request.
                        getNextPage();
                    }
                );
            } else {
                done();
            }
        }

        // Require config variables.
        grunt.config.requires(
            'path.tmp',
            'pages.baseurl',
            'pages.requests'
        );


        // Create empty output directory inside tmp folder.
        outpath = grunt.config('path.pages');
        if (grunt.file.isDir(outpath)) {
            grunt.file.delete(outpath);
        }
        grunt.file.mkdir(outpath);
        if (!grunt.file.isDir(outpath)) {
            grunt.log.error('Output directory "' + outpath + '" missing!');
            return done(false);
        }

        // Request all required pages.
        pages = grunt.config('pages.requests');
        for (var dest in pages) {
            if (pages.hasOwnProperty(dest)) {
                // Set request options.
                if (typeof pages[dest] === 'object') {
                    // Command shortcut: Login
                    if (dest === '@login' && pages[dest].u && pages[dest].p) {
                        options = {
                            url: "login",
                            method: "POST",
                            form: {
                                username: pages[dest].u,
                                password: pages[dest].p,
                                action: "login"
                            }
                        };
                    // Command shortcut: Logout
                    } else if (dest === '@logout') {
                        options = {
                            url: "logout",
                            method: "POST",
                            form: {}
                        };
                    // Request options
                    } else {
                        options = pages[dest];
                    }
                } else {
                    options = {
                        url: pages[dest] !== '' ? pages[dest] : dest
                    };
                }
                options.baseUrl = options.baseUrl || grunt.config('pages.baseurl');
                options.followAllRedirects = true; // "followRedirect" doesn't seem to work with 302.
                options.jar = true;
                if (typeof options.headers === 'undefined') {
                    options.headers = {};
                }
                if (typeof options.headers['User-Agent'] === 'undefined') {
                    options.headers['User-Agent'] = 'request';
                }

                // Path, where to put the file. Make it always end with ".html"
                if (dest.substr(0, 1) === '@') {
                    outfile = dest;
                } else {
                    outfile = outpath + '/' + dest.replace(/^(.+)\.html$/, '$1') + '.html';
                }

                // Build a request queue.
                queue.push({
                    opt: options,
                    out: outfile
                });
            }
        }

        getNextPage();
    });
};
