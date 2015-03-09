/*
 * SASS: Compile Sass to CSS
 */
module.exports = function(grunt, options) {
    return {
        boltCss: {
            options: {
                outputStyle: 'compressed',
                includePaths: [
                    'node_modules/bootstrap-sass/assets/stylesheets/',
                    'node_modules/font-awesome/scss/'
                ],
                lineNumbers: false,
                unixNewlines: true,
                banner: options.banner.boltCss,
                precision: 5
            },
            files: {
                'css/bolt-old-ie.css': 'sass/app-old-ie.scss',
                'css/bolt.css': 'sass/app.scss'
            }
        }
    };
};
