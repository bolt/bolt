/*
 * Modernizr-Task: Build custom modernizr.
 */
module.exports = function (grunt) {
    'use strict';

    var modernizr = require('modernizr');

    grunt.registerMultiTask('modernizr', 'Build custom modernizr', function () {
        var done = this.async(),
            conf = this.data;

        modernizr.build(conf.options, function (result) {
            grunt.file.write(conf.dest, result);
            grunt.log.ok('File "' + conf.dest + '" written.');
            done();
        });
    });
};
