/**
 * Don't break on browsers without console.log()
 */
try {
    console.assert(1);
} catch(e) {
    /* jshint -W020 */
    console = {
        log: function () {
            "use strict";
        },
        assert: function () {
            "use strict";
        }
    };
    /* jshint +W020 */
}
