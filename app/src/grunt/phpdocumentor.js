/*
 * PHPDOCUMENTOR: Runs the PHPDocumentor documentation generator tool.
 */
module.exports = {
    /*
     * TARGET:  Generate Bolt API documentation
     */
    bolt: {
        options: {
            directory: '../../src',
            target: '<%= path.doc.php %>'
        }
    }
};
