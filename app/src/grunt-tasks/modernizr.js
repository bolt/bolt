/*
 * Modernizr-Task: Build custom modernizr.
 */
module.exports = function (grunt) {
    'use strict';

    var modernizr = require('modernizr');

	grunt.registerMultiTask('modernizr', 'Build custom modernizr', function () {
        var done = this.async(),
            options = this.data;

        done();
	});
};
