/**
 * Don't break on browsers without console.log()
 */
if (typeof console === "undefined") {
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
