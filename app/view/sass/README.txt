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
There are two main .scss files in de 'sass' directory:
- app.scss
- app-old-ie.scss

Both are the same except for two variables: $old-ie & $fixed-mq, which are present in 
app-old-ie.scss. With these two variables we add some sass magic to the scss
processing, an use them in mixins to generate a special separate stylehsheet for 
Old Internet Explorer browser versions. Hence "old-ie".

Variable $old-ie
---------------------
$old-ie set to TRUE makes the mixin old-ie() include all styles in the generated
stylesheet. This is also incorporated into the media query handling mixin.

Variable $fixed-mq
---------------------
With $fixed-mq you set the width to what extent to include all styles. E.g. set 
it 60em, and all styles in Media Queries up to and including 60ems will be 
written to the app-old-ie.scss stylesheet. IT's a handy fix to (generally) 
target the older IE browser versions IE8 and lower; the oldies.

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

