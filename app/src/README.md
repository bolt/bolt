# Bolts backend’s frontend workflow using grunt

## Local options

Add a file ``app/src/grunt.json`` in which you put the options you want to overwrite.
This file is ignored by git.

### Sourcemaps

Example file to enable generation of sourcemaps:

    {
        "sourceMap": {
            "css": true,
            "js": true
        }
    }

### Pages

For the linting tasks you have to define a list of pages to download to the ``tmp/pages`` folder.

    {
        "pages": {
            "baseurl": "http://bolt.localhost/bolt/",
            "requests": { … }
        }
    }

The key of the ``requests`` part is the filename and the value defines the page to download.

- If the request starts with ``#`` the entry is ignored. A little helper, as there are no comments in JSON.
- If no extension is given on the request key ``.html`` is automatically appended.
- If the value is a string it is handled as a GET request with that value a relative url.
- If the value is an empty string the key is used as value.
- If the value is an object it is used as request configuration (see https://github.com/request/request).
- If the key is ``@login`` it is handled as not saved login request.
  The value has to be ``{"u": "<username>", "p": "<password>"}`` then.
- If the key is ``@logout`` it is handled as not saved logout request. The value has to be ``{}`` then.

#### Example: Key handling

Three requests save the same page to file ``login.html``.

    "pages": {
        "baseurl": "http://bolt.localhost/bolt/",
        "requests": {
                "login": "",
                "login": "login",
                "login.html": "login"
                "#this entry is ignored": "login"
            }
        }
    }

#### Example: POST request

Issue a manual login (same as ``@login``, only page is saved as ``dashboard.html``):

    "pages": {
        "baseurl": "http://bolt.localhost/bolt/",
        "requests": {
            "dashboard": {
                "url": "login",
                "method": "POST",
                "form": {
                    "username": "<enter username here>",
                    "password": "<enter password here>",
                    "action": "login"
                }
            }
        }
    }

#### Example: "Full" interface check
    {
        "pages": {
            "baseurl": "http://bolt.localhost/bolt/",
            "requests": {
                "login": "",
                "@login": {"u": "<enter username here>", "p": "<enter password here>"},

                "#___ Dashboard ___": null,
                "dashboard": "/",

                "#___ Configuration: Users & Permissions ___": null,
                "config-users": "users",
                "config-users-new": "users/edit",
                "config-users-edit": "users/edit/1",
                "config-roles": "roles",
                "config-permissions": "file/edit/config/permissions.yml",

                "#___ Configuration: Main configuration ___": null,
                "config-main": "file/edit/config/config.yml",

                "#___ Configuration: Contenttypes ___": null,
                "config-contenttypes": "file/edit/config/contenttypes.yml",

                "#___ Configuration: Taxonomy ___": null,
                "config-taxonomy": "file/edit/config/taxonomy.yml",

                "#___ Configuration: Menu ___": null,
                "config-menu": "file/edit/config/menu.yml",

                "#___ Configuration: Routing ___": null,
                "config-routing": "file/edit/config/routing.yml",

                "#___ Configuration: Check database ___": null,
                "config-dbcheck": "dbcheck",
                "config-prefill": "prefill",

                "#___ Configuration: Clear the cache ___": null,
                "config-clearcache": "clearcache",

                "#___ Configuration: Change Log ___": null,
                "config-changelog": "changelog",

                "#___ Configuration: System Log ___": null,
                "config-systemlog": "systemlog",

                "#___ File Management ___": null,
                "files-files": "files",
                "files-theme": "files/theme",

                "#___ Translations ___": null,
                "translations-messages": "tr",
                "translations-long-messages": "tr/infos",
                "translations-contenttypes": "tr/contenttypes",

                "#___ Extras ___": null,
                "extras-view-install": "extend",
                "extras-configure": "files/config/extensions",

                "#___ Main Menu ___": null,
                "profile": "profile",

                "@logout": {},
            }
        }
    }

### Bootlint

You can override bootlint options, e.g.:

    {
        "bootlint": {
            "relaxerror": ["W012"],
            "showallerrors": false,
            "stoponerror": false,
            "stoponwarning": false
        }
    }

### Htmllint

You can override bootlint options, e.g.:

    {
        "htmllint": {
            "ignore": "Element “link” is missing required attribute “property”."
        }
    }


##Range Specifiers

Just for the forgetful people and as JSON allows no comments …

###Caret:

    ^0.0.3:   = 0.0.3
    ^0.1.2:   ≥ 0.1.2-0  and  < 0.2.0-0
    ^1.2.3:   ≥ 1.2.3-0  and  < 2.0.0-0

###Tilde:

    ~1.2.3:   ≥ 1.2.3-0  and  < 1.3.0-0
