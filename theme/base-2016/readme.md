Bolt Base-2016 Theme
====================

Base-2016 is a blank theme for Bolt, built on top of
[Zurb Foundation for sites 6](http://foundation.zurb.com/). To learn more about
specific Foundation components, check out the
[Foundation 6 Documentation](http://foundation.zurb.com/sites/docs/).

The documentation laid in this README will cover how to get started with
Foundation for Bolt and how some Foundation components, are integrated with
Bolt.

Features included with Base-2016
--------------------------------

Base-2016 comes with all of the great features that are found in the Zurb
Foundation framework, and a few things more. Simply put, if it works in
Foundation, it will work in Foundation for Bolt. The theme also includes:

 - Sass(scss) or CSS Versions
 - Multiple Foundation Navigation and layout options
 - Optional Bower and Gulp Support
 - And much, much more!

Requirements for Base-2016
--------------------------

You can use whatever you want – seriously. You can use Gulp, the
Foundation CLI-tool, Codekit or nothing at all. It’s completely up to
you how you decide to build your theme – Foundation for Bolt will stay
out of your workflow as much as possible.

This theme does include Bower and Gulp files, and is optimized for a
Gulp-based workflow. To get the most out of Foundation for Bolt, Gulp
is highly recommended. However, if you're not using Gulp yet, you can
also modify the compiled CSS files as is.

File Structure
--------------

These are the most important files, included in this theme.

```
.
├── css/
│   ├── foundation.css       - The compiled Foundation CSS framework
│   └── theme.css            - Theme-specific CSS
├── images/                  - Image files for this theme are put here
├── js/
│   ├── app.js               - Theme-specific Javascript
│   ├── foundation.js        - The compiled Foundation javascript library
│   └── jquery.min.js        - The jQuery javascript library
├── partials/
│   ├── _aside.twig          - Partial for the sidebar. With fixed content, or widgets
│   ├── _footer.twig         - Partial for the footer below every page
│   ├── _fresh_install.twig  - Partial that's shown on fresh installs with some instructions
│   ├── _header.twig         - Partial for the header banner with the site title.
│   ├── _master.twig         - Twig template, that is uses to 'extend' all pages (See 'template inheritance')
│   ├── _recordfooter.twig   - Partial with meta-information below a page or entry
│   ├── _sub_menu.twig       - Partial with macro for rendering the drop-down menu
│   └── _topbar.twig         - Partial containing the top menu bar
├── source/
│   ├── scss/
│   │   ├── _settings.scss   - SCSS source file for Foundation. Is used by `css/foundation.css`
│   │   ├── foundation.scss  - SCSS source file for Foundation. Is compiled to `scss/foundation.scss`
│   │   └── theme.scss       - SCSS source file for the theme. Is compiled to `css/theme.css`
│   ├── .babelrc             - Helper file for gulp / npm
│   ├── bower.json           - Configuration for used Bower packages.
│   ├── gulpfile.js          - Build task script for Gulp.
│   └── package.json         - Configuration for used Node / Gulp packages.
├── CHANGELOG.md             - List of versions, and their respective changes.
├── index.twig               - Template used for 'home'
├── listing.twig             - Template used for 'listings', like `/pages` or `/category/movies`
├── notfound.twig            - Template used for the '404 not found' pages
├── page.twig                - Template used for single record pages, like `/page/lorem-ipsum`
├── readme.md                - This file. :-)
├── record.twig              - Generic template used for single record pages, that don't have a specific template set.
├── search.twig              - Template used for listing search results.
├── styleguide.twig          - Static page, that shows all Foundation elements on one long page. Go to `/styleguide` to see it in the browser.
└── theme.yml                - Theme-specific configuration.
```

Installation
------------

No need to install anything. This theme comes with Bolt. Don't forget to set
`theme: base-2016` in your `config.yml` file, if it doesn't show up already.

Getting Started
---------------

This theme was developed to be as "tinker friendly" as possible. Depending on
your area of expertise and experience with different front-end development
techniques, you can modify the CSS of this theme on different 'levels':

 - If you're familiar with Foundation and gulp, you can finetune which parts of
   Foundation are included, as well as all their settings. See the
   `source/scss/foundation.scss` and `source/gulpfile.js` files.
 - If you do know a bit of SCSS, you can work in `source/scss/theme.scss` and
   `source/scss/_settings.scss` files.
 - Otherwise you can just make your changes in the compiled css at `css/theme.css`.

The templates themselves are the `.twig` files in the root of the theme folder,
as well as the additional helper files in the `partials` folder.

Modifying the HTML of the theme
-------------------------------

All HTML parts of the theme are made in Twig. If you're not familiar with Twig
yet, be sure to read the Bolt documentation on Twig, as well as the official
Twig documentation.

This theme uses a concept called 'template inheritance'. From other themes or
CMS'es, you might be familiar with seeing each page 'include' a header and a
'footer'. Instead, we have one 'master' template, which are extended by each of
the different templates. You can read more about this concept on the
[Twig site - Template Inheritance](http://twig.sensiolabs.org/doc/tags/extends.html)
or here: [Dealing With Themes And Layouts With Twig](http://hugogiraudel.com/2013/11/12/themes-layouts-twig/)

For example, take a look at one of the simpler templates, `record.twig`:

```twig
{% extends 'partials/_master.twig' %}

{% block main %}

        <h1>{{ record.title }}</h1>

        {{ block('sub_fields', 'partials/_sub_fields.twig') }}

        {{ include('partials/_recordfooter.twig', { 'record': record }) }}

{% endblock main %}
```

You'll notice the first line that states that the template 'extends' the
`_master.twig` partial. The rest of the template is the `{% block %}`, which
overrides the 'main' block in the master template. Inside the block is just an
`<h1>` element with the record title, a `sub_fields` block (defined in
`partials/_sub_fields.twig`) that will output the fields that are defined for
this ContentType, and it closes with an include of `_recordfooter.twig` to
display some meta data, like the author, date and permalink.

As you can see, we can still use 'include' for small blocks of HTML, even though
we're using template inheritance. This way we can keep our themes very
structured and organized.

In the diagram below, you'll see the wat most pages are structured. In this case,
`index.twig`. In the HTML, you will see it extends `_master.twig`, which can be found in
the `partials/` folder. Inside this file, the global structure of all pages is laid out:
The basic HTML structure, and a handful of other included partials.

```
 index.twig structure                     _topbar.twig

                                               │
                     ├─────────────────────────┴────────────────────────────────┤

                    ┌────────────────────────────────────────────┬───────────────┐
 _sub_menu.twig ──▶ │  Home link1 link2 link3                    │______ [Search]│ ◀── _search.twig
                    ├────────────────────────────────────────────┴───────────────┤
                    │••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••│
                    │•••••••••••••••••••••••(header image)•••••••••••••••••••••••│ ◀── _header.twig
                    │•••••••••••••••••••••••(name of site)•••••••••••••••••••••••│
                    │••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••│
                    │ ┌──────────────────(main content)─┐ ┌────────────(aside)─┐ │
                    │ │Lorem ipsum dolor sit amet       │ │Lorem ipsum dolor   │ │
                    │ │                                 │ │sit amet. Consec-   │ │
                    │ │Consectetur adipiscing elit. Nunc│ │tetur adipiscing.   │ │
                    │ │omni virtuti vitium contrario    │ │                    │ │
                    │ │nominehgpponitur. Non enim, si   │ │Latest X            │ │
                    │ │malum est dolor, carere eo malo  │ │ - intellegetur     │ │
                    │ │satis est ad bene vivendum. Duo  │ │ - Expectoque       │ │
                    │ │Reges: constructio interrete.    │ │ - videantur        │ │ ◀── _aside.twig
                    │ │                                 │ │                    │ │
                    │ └─────────────────────────────────┘ │Latest Y            │ │
                    │ ┌─────────────────────────────────┐ │ - intellegetur     │ │
                    │ │Lorem ipsum dolor sit amet       │ │ - Expectoque       │ │
                    │ │                                 │ │ - videantur        │ │
                    │ │Consectetur adipiscing elit. Nunc│ │                    │ │
                    │ │omni virtuti vitium contrario    │ │                    │ │
                 ┬  ├─┴─────────────────────────────────┴─┴────────────────────┴─┤
  _footer.twig ──┤  │ (C) 2016                          Home link1 link2 link3   │ ◀── _sub_menu.twig
                 ┴  └────────────────────────────────────────────────────────────┘

                   ├────────────────────────────┬─────────────────────────────────┤
                                                │

                                           _master.twig
```

Options in `theme.yml`
----------------------

This theme comes with its own configuration file, named `theme.yml`. In this
file you can set certain specific options for the theme, such as the default
images for the header, the position of the 'aside' sidebar, and the global
layout.

### Setting `layout:variant`

You can select a global layout, which determines if the way the website looks.
Possible options are:

`centered`: Centers the layout on wide screens, so that the 'main content' is in
the middle of the screen.

```
┌────────────────────────────────────────────────────────────────────────┐
│ o o o      browser window                                              │
├─────┬────────────────────────────────────────────┬───────────────┬─────┤
│     │  Home link1 link2 link3                    │______ [Search]│     │
│     └────────────────────────────────────────────┴───────────────┘     │
│     ••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••     │
│     ••••••••••••••••••••••••(header image)••••••••••••••••••••••••     │
│     ••••••••••••••••••••••••(name of site)••••••••••••••••••••••••     │
│     ••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••     │
│       ┌──────────────────(main content)─┐ ┌────────────(aside)─┐       │
│       │Lorem ipsum dolor sit amet       │ │Lorem ipsum dolor   │       │
│       │                                 │ │sit amet. Consec-   │       │
│       │Consectetur adipiscing elit. Nunc│ │tetur adipiscing.   │       │
│       │omni virtuti vitium contrario    │ │                    │       │
│       │nominehgpponitur. Non enim, si   │ │Latest X            │       │
│       │malum est dolor, carere eo malo  │ │ - intellegetur     │       │
│       └─────────────────────────────────┘ │ - Expectoque       │       │
│       ┌─────────────────────────────────┐ │ - videantur        │       │
│       │Lorem ipsum dolor sit amet       │ │                    │       │
│       │                                 │ │Latest Y            │       │
│       │Consectetur adipiscing elit. Nunc│ │ - intellegetur     │       │
└───────┴─────────────────────────────────┴─┴────────────────────┴───────┘
```

`wide`: uses a 'wide' layout, meaning the header and top bar are streched to the
edges of the browser on large screens:

```
┌────────────────────────────────────────────────────────────────────────┐
│ o o o      browser window                                              │
├────────────────────────────────────────────────────────┬───────────────┤
│  Home link1 link2 link3                                │______ [Search]│
├────────────────────────────────────────────────────────┴───────────────┤
│••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••│
│•••••••••••••••••••••••••••••(header image)•••••••••••••••••••••••••••••│
│•••••••••••••••••••••••••••••(name of site)•••••••••••••••••••••••••••••│
│••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••│
│       ┌──────────────────(main content)─┐ ┌────────────(aside)─┐       │
│       │Lorem ipsum dolor sit amet       │ │Lorem ipsum dolor   │       │
│       │                                 │ │sit amet. Consec-   │       │
│       │Consectetur adipiscing elit. Nunc│ │tetur adipiscing.   │       │
│       │omni virtuti vitium contrario    │ │                    │       │
│       │nominehgpponitur. Non enim, si   │ │Latest X            │       │
│       │malum est dolor, carere eo malo  │ │ - intellegetur     │       │
│       └─────────────────────────────────┘ │ - Expectoque       │       │
│       ┌─────────────────────────────────┐ │ - videantur        │       │
│       │Lorem ipsum dolor sit amet       │ │                    │       │
│       │                                 │ │Latest Y            │       │
│       │Consectetur adipiscing elit. Nunc│ │ - intellegetur     │       │
└───────┴─────────────────────────────────┴─┴────────────────────┴───────┘
```

`boxed`: Adds a background and a border around the centered content.

```
┌────────────────────────────────────────────────────────────────────────┐
│ o o o      browser window                                              │
├────────────────────────────────────────────────────────────────────────┤
│░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░│
│░░░░░┌────────────────────────────────────────────┬───────────────┐░░░░░│
│░░░░░│  Home link1 link2 link3                    │______ [Search]│░░░░░│
│░░░░░├────────────────────────────────────────────┴───────────────┤░░░░░│
│░░░░░│••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••│░░░░░│
│░░░░░│•••••••••••••••••••••••(header image)•••••••••••••••••••••••│░░░░░│
│░░░░░│•••••••••••••••••••••••(name of site)•••••••••••••••••••••••│░░░░░│
│░░░░░│••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••│░░░░░│
│░░░░░│ ┌──────────────────(main content)─┐ ┌────────────(aside)─┐ │░░░░░│
│░░░░░│ │Lorem ipsum dolor sit amet       │ │Lorem ipsum dolor   │ │░░░░░│
│░░░░░│ │                                 │ │sit amet. Consec-   │ │░░░░░│
│░░░░░│ │Consectetur adipiscing elit. Nunc│ │tetur adipiscing.   │ │░░░░░│
│░░░░░│ │omni virtuti vitium contrario    │ │                    │ │░░░░░│
│░░░░░│ │nominehgpponitur. Non enim, si   │ │Latest X            │ │░░░░░│
│░░░░░│ │malum est dolor, carere eo malo  │ │ - intellegetur     │ │░░░░░│
│░░░░░│ └─────────────────────────────────┘ │ - Expectoque       │ │░░░░░│
│░░░░░│ ┌─────────────────────────────────┐ │ - videantur        │ │░░░░░│
│░░░░░│ │Lorem ipsum dolor sit amet       │ │                    │ │░░░░░│
└─────┴─┴─────────────────────────────────┴─┴────────────────────┴─┴─────┘
```

The `theme.yml` file also defines the default images, that are used in the
header of the website. Feel free to change these for other images. A lot of
royalty-free images to be used, can be found at
[visualhunt.com](http://visualhunt.com).

Finally, the last section defines the settings for which templates are used for
which types of pages. The templates you will set in this config file will
override the ones in the global app/config/config.yml, so beware!

```
# maintenance_template: maintenance_default.twig
homepage_template: index.twig
record_template: record.twig
listing_template: listing.twig
search_results_template: search.twig
notfound: notfound.twig
```

For details on which page is used when, see the next section in this document.


Working with the `.twig` files
------------------------------

You are free to do what you want, when it comes to the .twig files. Out-of-the-
box, this theme comes with a handful of templates, that correspond to
the default ContentTypes when you have a fresh install of Bolt.

Most of the templates will be pretty straightforward, especially if you're
familiar with the concept of Template Inheritance. The main templates are:

 - `index.twig`: Used as the frontpage or homepage of the site.
 - `listing.twig`: This template is used for listing overviews of all kind, like
   `/pages` for all records in the 'pages ContentType' or `category/movies` for
   all records that have the 'movies' category assigned to them. Note that
   'search' uses its own template, though.
 - `notfound.twig`: This template is used as the template that's shown when the
   visitor hits a non-existing page on the website.
 - `page.twig`: The detail page for a single record of the 'pages' ContentType.
   Automatically picked up by Bolt, if the name matches.
 - `record.twig`: The "generic" detail page for a single record page. This is
   used as the fallback, if there's no specific template set for a single record
   page.
 - `search.twig`: This page displays the search results and a search box, to
   search again.
 - `styleguide.twig`: A sample page, showing most of the common typograhy
   options, form elements, as well as other components supplied by Foundation 6.
   Use your browser to go to `/styleguide` to view this page.


Working with the `.scss` files
------------------------------

This theme uses Node and NPM to run the tasks to compile and minify the Sass
files. If you don't have Node and NPM yet, install them from [Nodejs.org](https://nodejs.org).

To install the themes dependencies, run the following in the source directory:

```
npm install
```

Now you can simply run `npm start` to compile the javascript and sass files.
This will build the files, and it will continue to monitor changes to the
`.scss` files. If you make a change, the compiled files will be updated
immediately. When you're ready to deploy, and put the site in production, be
sure to build the files and minify them:

```
npm run-script build
```

This will build the files that you can deploy, or put into your versioning
system.

The build process has been tested on NPM 3.10 and Node v7.2. If you do not
have the correct versions you can use [n](https://www.npmjs.com/package/n) to
manage your Node and NPM versions:

```
sudo npm install -g n;
sudo n stable
```

And then go through the above steps again.

If you're interested to learn more about the process, these two tutorials on
Gulp (which is what we use under the hood) might be of interest to you:

 - https://markgoodyear.com/2014/01/getting-started-with-gulp/
 - https://travismaynard.com/writing/getting-started-with-gulp
