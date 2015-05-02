/*
 * JSDOC: Generate comments based documentation.
 */
module.exports = {
    /*
     * TARGET:  Generate API documentation
     */
    boltJs: {
        src: [
            '<%= path.src.js %>/console.js',
            '<%= path.src.js %>/class-extends.js',
            // Bolt module
            '<%= path.src.js %>/bolt.js',
            '<%= path.src.js %>/modules/*.js',
            '<%= path.src.js %>/modules/fields/*.js'
        ],
        dest: '<%= path.doc.js %>',
        options: {
            private: true,
            configure : 'jsdoc.json'
        }
    }
};
