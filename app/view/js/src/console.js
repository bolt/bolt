var bolt = {};

// Don't break on browsers without console.log();
try {
    console.assert(1);
} catch(e) {
    console = {
        log: function () {},
        assert: function () {}
    };
}
