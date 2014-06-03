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
                    lineNumbers: true,
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
        }

    });

    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-copy');

    grunt.registerTask('default', ['sass', 'watch']);

};
