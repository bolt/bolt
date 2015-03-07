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
                banner: "/**\n" +
                        " * These are Bolt's COMPILED CSS files!\n" +
                        " * Do not edit these files, because all changes will be lost.\n" +
                        " * You can edit ../scss/app.scss & ../scss/app-old-ie.scss, and run 'grunt' to generate this file.\n" +
                        " */\n",
                precision: 5
            },
            files: {
                'css/bolt-old-ie.css': 'sass/app-old-ie.scss',
                'css/bolt.css': 'sass/app.scss'
            }
        }
    };
};
