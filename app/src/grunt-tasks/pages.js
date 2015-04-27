/*
 * PAGES: Downloads rendered pages from a Bolt server
 */
module.exports = function (grunt) {
    grunt.registerTask('pages', 'Downloads rendered pages from a Bolt server', function () {
        var outpath;

        // Require config variables.
        grunt.config.requires(
            'path.tmp'
        );

        // Create output directory inside tmp folder.
        outpath = grunt.config('path.tmp') + '/pages';
        grunt.file.mkdir(outpath);
    });
};
