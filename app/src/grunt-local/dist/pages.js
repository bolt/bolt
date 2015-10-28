/**
 *  This is just a sample file!
 *  Copy it to /app/src/grunt-local/ to have an effect!
 */
module.exports = {
    baseurl: "http://your.local.installation/bolt/",
    requests: {
        "login": "",
        "@login": {"u": "<enter username here>", "p": "<enter password here>"},

        // Dashboard
        "dashboard": "/",

        // Content
        "edit-pages-5": "editcontent/pages/5",
        "edit-entries-5": "editcontent/entries/5",
        "edit-showcases-5": "editcontent/showcases/2",

        // Main Menu
        "profile": "profile",

        "@logout": {}
    }
};
