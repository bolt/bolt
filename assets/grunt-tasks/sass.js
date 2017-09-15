/*
 * SASS-Task: Compile Sass to CSS.
 */
module.exports = function (grunt) {
    'use strict';

    var path = require('path'),
        sass = require('node-sass');

    grunt.verbose.writeln(sass.info);

    grunt.registerMultiTask('sass', 'Compiles SCSS to CSS', function () {
        var done = this.async(),
            task = this,
            asyncCnt = 0;

        // Loop over files to build.
        this.files.forEach(function (file) {
            var opt = task.options();

            // Build options.
            opt.file = file.src[0];
            opt.outFile = file.dest;
            if (!opt.sourceMap || opt.sourceMap === 'false' || opt.sourceMap === '0') {
                opt.sourceMap = false;
            } else if (opt.sourceMap.match(/[\/\\]$/)) {
                opt.sourceMap = path.posix.normalize(opt.sourceMap + path.basename(file.dest) + '.map');
            }

            // Count asynchronous tasks.
            asyncCnt++;

            // Start the asynchronous sass render tasks.
            sass.render(opt, function (error, result) {
                grunt.verbose.write('Process ' + opt.file + '...');

                if (error) {
                    // An error occurred.
                    grunt.log.error();
                    grunt.log.error(error.message);
                    if (error.file) {
                        var msg = path.relative(process.cwd(), error.file) + '#L' + error.line + ':' + error.column;
                        grunt.log.error(msg);
                    }
                    done(false);
                } else {
                    // Write CSS.
                    grunt.verbose.ok();
                    grunt.file.write(opt.outFile, result.css);

                    // Write source-map.
                    if (opt.sourceMap) {
                        grunt.file.write(opt.sourceMap, result.map);
                    }
                }

                // No more asynchronous running?
                if (--asyncCnt === 0) {
                    done();
                }
            });
        });
    });
};
