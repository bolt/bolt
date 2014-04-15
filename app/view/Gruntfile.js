module.exports = function(grunt) {

    // 1. All configuration goes here 
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        /*
        concat: {   
            dist: {
                src: [
                    'javascripts/app.js', 
                    'javascripts/foundation.min.js' 
                ],
                dest: 'javascripts/app.min.js',
            }
        }
        */

        /*
        uglify: {
            build: {
                src: 'javascripts/app.js',
                dest: 'javascripts/app.min.js'
            }
        },
        */

        watch: {
            scripts: {
                files: ['js/*.js', 'sass/*.scss'],
                tasks: ['sass'],
                options: {
                    spawn: false,
                },
            } 
        },        

        sass: {
            dist: {
                options: {
                    style: 'compressed'
                },
                files: {
                    'css/app.css': 'sass/app.scss'
                }
            } 
        }        

    });

    // 3. Where we tell Grunt we plan to use this plug-in.
    //grunt.loadNpmTasks('grunt-contrib-concat');
    //grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-sass');
    // grunt.loadNpmTasks('grunt-sass');


    // 4. Where we tell Grunt what to do when we type "grunt" into the terminal.
    grunt.registerTask('default', ['sass']);

};