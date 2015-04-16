# Bolts backend’s frontend workflow using grunt

## Local options

Add a file ``app/src/grunt.json`` in which you put the options you want to overwrite.
This file is ignored by git.

Example file to enable generation of sourcemaps:

    {
        "sourceMap": {
            "css": true,
            "js": true
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
