module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

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
                    style: 'compressed',
                    loadPath: [
                        'node_modules/bootstrap-sass/vendor/assets/stylesheets/',
                        'node_modules/font-awesome/scss/'
                    ]
                },
                files: {
                    'css/app.css': 'sass/app.scss'
                }
            } 
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

    grunt.registerTask('default', ['sass']);

};