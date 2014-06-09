module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        config: grunt.file.readYAML('../config/config.yml'),

        watch: {
            scripts: {
                files: ['js/*.js', 'sass/*.scss'],
                tasks: ['sass'],
                options: {
                    spawn: false,
                    livereload: true
                },
            } 
        },        

        sass: {
            dist: {
                options: {
                    style: 'nested',
                    loadPath: [
                        'node_modules/bootstrap-sass/vendor/assets/stylesheets/',
                        'node_modules/font-awesome/scss/'
                    ],
                    lineNumbers: true
                },
                files: {
                    'css/app.css': 'sass/app.scss'
                }
            }, 

        },


        copy: {
            main: {
                files: [
                    // includes files within path
                    {expand: true, flatten: true, src: ['node_modules/font-awesome/fonts/*'], dest: 'fonts/', filter: 'isFile'}
                ]
            }
        },


        concat: {
            bootstrap: {
                src: [
                    'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/alert.js',
                    'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/button.js',
                    'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/dropdown.js',
                    'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/tab.js',
                    'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/transition.js',
                    'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/modal.js',
                    'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/tooltip.js',
                    'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/popover.js',
                ],
                dest: 'js/bootstrap-concat.js',
            },
            bolt: {
                // TODO: configure this. 
                //src: ['src/main.js', 'src/extras.js'],
                //dest: 'dist/with_extras.js',
            },
        },

        uglify: {
            bootstrap: {
                files: {
                    'js/bootstrap.js': [
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/alert.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/button.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/dropdown.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/tab.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/transition.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/modal.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/tooltip.js',
                        'node_modules/bootstrap-sass/vendor/assets/javascripts/bootstrap/popover.js'
                    ]
                }
            }
        } 

    });

    require('load-grunt-tasks')(grunt);

    grunt.registerTask('default', ['sass', 'watch']);

};
