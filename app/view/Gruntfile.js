module.exports = function(grunt) {
    grunt.util.linefeed = '\n';

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        config: grunt.file.readYAML('../config/config.yml'),

        filesBoltJs: [
            'lib/bolt/console.js',
            'lib/bolt/fnc-helpers.js',
            'lib/bolt/activity.js',
            'lib/bolt/bind-fileupload.js',
            'lib/bolt/make-uri-slug.js',
            'lib/bolt/video-embed.js',
            'lib/bolt/geolocation.js',
            'lib/bolt/upload-files.js',
            'lib/bolt/obj-sidebar.js',
            'lib/bolt/obj-navpopups.js',
            'lib/bolt/obj-moments.js',
            'lib/bolt/obj-files.js',
            'lib/bolt/obj-stack.js',
            'lib/bolt/obj-folders.js',
            'lib/bolt/obj-datetime.js',
            'lib/bolt/obj-validation.js',
            'lib/bolt/extend.js',
            'lib/bolt/init.js',
            'lib/bolt/start.js'
        ]

    });

    require('load-grunt-config')(grunt);

};
