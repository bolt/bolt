/**
 * Don't break on browsers without console.log()
 */
if (typeof console === "undefined") {
    console = {
        log: function () {
            "use strict";
        },
        assert: function () {
            "use strict";
        }
    };
}
