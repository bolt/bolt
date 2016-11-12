Bolts backend’s frontend workflow using grunt
=============================================

Quick Start
-----------

### Install Yarn

#### CentOS & RHEL 6, Fedora 21

```
    sudo wget https://dl.yarnpkg.com/rpm/yarn.repo -O /etc/yum.repos.d/yarn.repo
    sudo yum install yarn
```


#### CentOS & RHEL 7, Fedora 22+

```
    sudo wget https://dl.yarnpkg.com/rpm/yarn.repo -O /etc/yum.repos.d/yarn.repo
    sudo dnf install yarn
```


#### Debian/Ubuntu Linux

```
    sudo apt-key adv --fetch-keys http://dl.yarnpkg.com/debian/pubkey.gpg
    echo "deb http://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
    sudo apt-get update && sudo apt-get install yarn
```


#### OS X (with Homebrew)

```
    brew update
    brew install yarn
```


#### Windows

Download and install the MSI from [Yarn's site](https://yarnpkg.com/latest.msi)


### Install required components:

```
    cd app/src/
    yarn install --strict-semver
```

### Rebuild CSS & JavaScript:

```
    yarn run grunt updateLib
    yarn run grunt prepareCkeditor 
    yarn run grunt updateBolt
```


Available grunt tasks
---------------------

- **`grunt`**<br> Starts the watch task that monitors the Javascript and Sass source files and
  automatically rebuilds `bolt.js`, `bolt.css` and `liveeditor.css` when changes are detected.

- **`grunt updateBolt`**<br> Manually starts a rebuild of `bolt.js`, `bolt.css` and
  `liveeditor.css`.

- **`grunt updateLib`**<br> Updates everything that depends on external resources, either
  provided by npm or embedded in the `lib` folder. This command mainly builds `lib.js` and
  `lib.css` from external libraries, installs fonts, CKEditor and library locale files. It has to
  be run after any update to one of the external resources.

- **`grunt prepareCkeditor`**<br> Does some cleanup on CKEditor files in `lib/ckeditor` after
  updating it. Update process:

    * Get the latest version with URL extracted from `lib/ckeditor/build-config.js`.
    * Empty the folder `lib/ckeditor` and unpack the newer version there.
    * Run `grunt prepareCkeditor` to get files prepared.
    * Run `grunt updateLib` to get everything in place.

- **`grunt docJs`**<br> Generates documentation of Bolt's own Javascript modules in the folder
  `docs/js`.

- **`grunt docPhp`**<br> Generates documentation of Bolt source files in the folder `docs/php`.

- **`grunt lintHtml`**<br> Downloads the Bolt backend pages defined in `grunt-local/pages.js` and
  checks them for html errors and problems.

- **`grunt lintBoot`**<br> Downloads Bolt backend pages defined in `grunt-local/pages.js` and
  checks them for Bootstrap errors and problems.

Local options
-------------

Add JS options files to the folder `app/src/grunt-local/`, in which you can put the options you
want to overwrite. The content of these files look like:

```javascript
    module.exports = {
        value: "The value"
    };
```
These files will automatically be ignored by git.


### Sourcemaps

If it doesn't yet exist, create the file `app/src/grunt-local/sourcemap.js`. A sample file to
enable generation of sourcemaps looks like this:

```javascript
    module.exports = {
        css: true,
        js: true
    };
```

### Pages

For the linting tasks you have to define a list of pages to download to the
`tmp/pages` folder. If it doesn't yet exist, create the file 
`app/src/grunt-local/pages.js`. A sample file to enable this task looks like this:

```javascript
    module.exports = {
        baseurl: "http://bolt.localhost/bolt/",
        requests: { … }
    };
```

The key of the `requests` part is the filename and the value defines the page to download.

- If no extension is given on the request key `.html` is automatically appended.
- If the value is a string it is handled as a GET request with that value a relative url.
- If the value is an empty string the key is used as value.
- If the value is an object it is used as request configuration (see https://github.com/request/request).
- If the key is `@login` it is handled as not saved login request.
  The value has to be `{u: "<username>", p: "<password>"}` then.
- If the key is `@logout` it is handled as not saved logout request. The value has to be `{}` then.

#### Example: Key handling

Three requests save the same page to file `login.html`.
```javascript
    module.exports = {
        baseurl: "http://bolt.localhost/bolt/",
        requests: {
                "login": "",
                "login": "login",
                "login.html": "login"
            }
        }
    };
```
#### Example: POST request

Issue a manual login (same as `@login`, only page is saved as `dashboard.html`):

```javascript
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
```
#### Example: "Full" interface check

```javascript
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
```
### Bootlint

If it doesn't yet exist, create the file `app/src/grunt-local/bootlint.js`.

You can override Bootlint options, e.g.:

```javascript
    module.exports = {
        relaxerror: ["W012"],
        showallerrors: false,
        stoponerror: true,
        stoponwarning: false
    };
```

### Htmllint

If it doesn't yet exist, create the file `app/src/grunt-local/htmllint.js`. 

You can override Htmllint options, e.g.:

```javascript
    module.exports = {
        ignore: [
            "Element “link” is missing required attribute “property”.",
            /^Duplicate ID/
        ]
    };
```

Range Specifiers
----------------

Just for the forgetful people and as JSON allows no comments …

###Caret:

    ^0.0.3:   = 0.0.3
    ^0.1.2:   ≥ 0.1.2-0  and  < 0.2.0-0
    ^1.2.3:   ≥ 1.2.3-0  and  < 2.0.0-0

###Tilde:

    ~1.2.3:   ≥ 1.2.3-0  and  < 1.3.0-0

Still problems, test it [here](http://semver.npmjs.com/).
