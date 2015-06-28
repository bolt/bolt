# Bolts backend’s frontend workflow using grunt

## Local options

Add JS options files to ``app/src/grunt-local/`` in which you put the options you want to overwrite.
The content of these files look like:

    module.exports = {
        value: "The value"
    };

These files are ignored by git.


### Sourcemaps (grunt-local/sourcemaps.js)

Sample file to enable generation of sourcemaps:

    module.exports = {
        css: true,
        js: true
    };

### Pages (grunt-local/pages.js)

For the linting tasks you have to define a list of pages to download to the ``tmp/pages`` folder.

    module.exports = {
        baseurl: "http://bolt.localhost/bolt/",
        requests: { … }
    };

The key of the ``requests`` part is the filename and the value defines the page to download.

- If no extension is given on the request key ``.html`` is automatically appended.
- If the value is a string it is handled as a GET request with that value a relative url.
- If the value is an empty string the key is used as value.
- If the value is an object it is used as request configuration (see https://github.com/request/request).
- If the key is ``@login`` it is handled as not saved login request.
  The value has to be ``{u: "<username>", p: "<password>"}`` then.
- If the key is ``@logout`` it is handled as not saved logout request. The value has to be ``{}`` then.

#### Example: Key handling

Three requests save the same page to file ``login.html``.

    module.exports = {
        baseurl: "http://bolt.localhost/bolt/",
        requests: {
                "login": "",
                "login": "login",
                "login.html": "login"
            }
        }
    };

#### Example: POST request

Issue a manual login (same as ``@login``, only page is saved as ``dashboard.html``):

    module.exports = {
        baseurl: "http://bolt.localhost/bolt/",
        requests: {
            dashboard: {
                url: "login",
                method: "POST",
                form: {
                    username: "<enter username here>",
                    password: "<enter password here>",
                    action: "login"
                }
            }
        }
    };

#### Example: "Full" interface check

    module.exports = {
        baseurl: "http://bolt.localhost/bolt/",
        requests: {
            "login": "",
            "@login": {"u": "<enter username here>", "p": "<enter password here>"},

            // Dashboard
            "dashboard": "/",

            // Configuration: Users & Permissions
            "config-users": "users",
            "config-users-new": "users/edit",
            "config-users-edit": "users/edit/1",
            "config-roles": "roles",
            "config-permissions": "file/edit/config/permissions.yml",

            // Configuration: Main configuration
            "config-main": "file/edit/config/config.yml",

            // Configuration: Contenttypes
            "config-contenttypes": "file/edit/config/contenttypes.yml",

            // Configuration: Taxonomy
            "config-taxonomy": "file/edit/config/taxonomy.yml",

            // Configuration: Menu
            "config-menu": "file/edit/config/menu.yml",

            // Configuration: Routing
            "config-routing": "file/edit/config/routing.yml",

            // Configuration: Check database
            "config-dbcheck": "dbcheck",
            "config-prefill": "prefill",

            // Configuration: Clear the cache
            "config-clearcache": "clearcache",

            // Configuration: Change Log
            "config-changelog": "changelog",

            // Configuration: System Log
            "config-systemlog": "systemlog",

            // File Management
            "files-files": "files",
            "files-theme": "files/theme",

            // Translations
            "translations-messages": "tr",
            "translations-long-messages": "tr/infos",
            "translations-contenttypes": "tr/contenttypes",

            // Extras
            "extras-view-install": "extend",
            "extras-configure": "files/config/extensions",

            // Main Menu
            "profile": "profile",

            "@logout": {},
        }
    };

### Bootlint

You can override bootlint options, e.g.:

    module.exports = {
        relaxerror: ["W012"],
        showallerrors: false,
        stoponerror: true,
        stoponwarning: false
    };

### Htmllint

You can override bootlint options, e.g.:

    module.exports = {
        ignore: [
            "Element “link” is missing required attribute “property”.",
            /^Duplicate ID/
        ]
    };


##Range Specifiers

Just for the forgetful people and as JSON allows no comments …

###Caret:

    ^0.0.3:   = 0.0.3
    ^0.1.2:   ≥ 0.1.2-0  and  < 0.2.0-0
    ^1.2.3:   ≥ 1.2.3-0  and  < 2.0.0-0

###Tilde:

    ~1.2.3:   ≥ 1.2.3-0  and  < 1.3.0-0
