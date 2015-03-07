module.exports = function(grunt) {
    grunt.util.linefeed = '\n';

    var options = {
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
        ],

        banner: {
            boltJs: [
                '/**',
                ' * These are Bolt’s COMPILED JS files!',
                ' * You can edit *.js files in <../lib/bolt/> and then run “grunt updateBolt” to generate this file.',
                ' */'
            ].join('\n'),
            boltCss: [
                '/**',
                ' * These are Bolt’s COMPILED CSS files!',
                ' * Do not edit these files, because all changes will be lost.',
                ' * You can edit *.scss files in <../scss/> and then run “grunt updateBolt” to generate this file.',
                ' */'
            ].join('\n')
        }
    };

    require('load-grunt-config')(grunt, {data: options});

};
