var bolt = {};

// Don't break on browsers without console.log();
try {
    console.assert(1);
} catch(e) {
    /* jshint -W020 */
    console = {
        log: function () {},
        assert: function () {}
    };
    /* jshint +W020 */
}
