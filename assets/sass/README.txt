This folder contains the *.scss files for the development of the back-end theme.
The *.scss files will be compiled into normal stylesheets in the ../css folder

READ THIS FILE BEFORE DOING ANY STYLE CHANGES, AS IT CONTAINS INFORMATION ABOUT
THE SASS APPROACH

Table of contents:
----------------------
1. Structure: information about the structure and sass setup, where to
   find (and put) what.
2. Media Query handling: information about the MQ  implementation and approach.
   What mixins to use, etc.


1. STRUCTURE
================================================================================
There is one main .scss file in the 'sass' directory:
- app.scss

Folders
---------------------
Within the modules folder you'll find all sass partials that should be included
in the main app .scss files.

In _base.scss we set all variables, mixins, extends and other sass things that
do not generate any styles


2. MEDIA QUERY HANDLING
================================================================================
In _base.scss we see the Media Query handling. Here's the summary:

Put your code for a specific MQ in this mixin:
@include respond-to('query_name') {}

For instance:

/* MQ for medium-screens and up ( min-width >=768 ) */
@include respond-to(medium-screens) {
    /*
     * some styles, e.g.
        color:$green; //gotta have greens!
     */
}

$lmedium-screens is coupled to a certain 'em' based width for the viewport

As we code mobile first the base styles are not in a Media Query, so we set
these: (assuming our base em size of 1em is 16px)

- $mid-mobile
- $medium-screens
- $large-screens
- $wide-screens

See _base.scss for values. Use them wisely...

