/**
 * Don't break on browsers without console.log()
 */
if (typeof console === "undefined") {
    // eslint-disable-next-line no-native-reassign
    console = {
        log: function () {
            "use strict";
        },
        assert: function () {
            "use strict";
        }
    };
}
