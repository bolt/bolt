MetaTags
========

Sets `<meta>` tags for search engine optimization (SEO) purposes.

 * Getting Started
 * Configuration
 * Title
 * Notes

Getting Started
---------------

If you're using the default `config.yml`, then you can add the following fields
to your `contenttypes.yml` if you want to be able to set custom title and
description:

        metatitle:
            prefix: "<hr><h3>Search Engine Optimization</h3>"
            label: Title
            type: text
            class: wide
            variant: inline
        metadescription:
            label: Meta Description
            type: textarea
            height: 50px

Also, you can add a field `metakeywords` for a meta tag with keywords.

        metatitle:
            label: Meta Keywords
            type: text
            class: wide
            variant: inline

Configuration
-------------

Use `config.yaml` to configure `<meta>` elements that are added on every page.
Add items under the `meta` item using the following template:

    meta-tag-name:
        - first-field
        - second-field
        - description
        - excerpt:
            params: [ 160 ]
            filters: [ strip_tags, trim ]

It will check the fields of a contenttype in the order from top to bottom.
Once a value is found, it will stop check following fields. If no values are
found, then no `<meta>` tag is added.

Optionally, use `params` and `filters` for extra customisation. This extension
uses parameters as follows:

    $content->property( $param1, $param2, ... );

Filters are called as follows:

    filter( $value );

When using `params` and/or `filters`, make sure you add an colon `:` after the
field name. Just check the config file `config.yaml` for some examples.

Title
-----

The value for `title` is a special case: no `<meta>` tag is added. Instead, use
`{{ metatitle() }}` in your twig template to override the title. Because
a title can be defined in many ways, the easiest way to use this extension while
maintaining a default fallback is to wrap an `{% if %}` statement around the
current contents of `<title>`. Like so in `_header.twig`:

    <title>
        {%- if metatitle() is not empty %}
            {{- metatitle('|', app.config.get('general/sitename')) }}
        {%- else %}
            {# ... your default title code goes here ... #}
        {%- endif -%}
    </title>

Two (optional) parameters can be set with `metatitle`, a separator and some text
that goes after the separator, usually the name of the site.

Use the value `(empty)` to by-pass the empty string check. It will still output
an empty string. This is useful for homepages, where you just want to show the
site's name and nothing else.

Notes
-----

In the future, it might be handy (or will it make this too complex?) to define
rules and fields per contenttype.