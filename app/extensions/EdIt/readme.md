Ed-It In Place Editor extension
===============================

Obviously, I made this stuff just for fun to see how easy implement additional functions with an extension and I may say,
Bolt is cool. :-)
This extension tries to extend administation functionality of Bolt on Frontend with enabling in-place editing some content.

Administrator or chief editor of the site may think there are some type of content on the site which may be editable in place
where it is. This function could be useful if editor wants to see instantly how the edited content would looks like in its page
context, or some of them can be touched quickly without entering dashboard.

Installation
============

  - Download and extract the extension to a directory called Visitors in your
    Bolt extension directory.
  - Copy `config.yml.dist` to `config.yml` in the same directory.
  - Align configuration setting as comments shows in
  - Use `editable()` twig function in template to define which content can be edited

Usage
=====

The function `{{ editable(record, field, options) }}` will place the editable content in the output page. The content is a
`value` of a `field` in a specified `record`. If you have a contenttype like below


```
pages:
    name: Pages
    singular_name: Page
    fields:
        title:
            type: text
            class: large
        slug:
            type: slug
            uses: title
        image:
            type: image
        teaser:
            type: html
            height: 150px
```

you may set the `teaser` field of current record to editable with the following twig command:

``{{ editable(record, 'teaser') }}``

If the actual visitor has logged in and has corresponding perrmissions to change then can edit the content.
Moving the mouse over the editable area of the page an `Edit` button will float over that should raise the editor toolbar.

The editor toolbar can be customized with the optional `options` parameter of `editable()`. This parameter is a key=>value
twig array map with a following possible format:

``{{ editable(record, 'teser', { 'logo': false, 'statistics': false }) }}``

With this option you may enable or disable the specific plugin of Raptor editor.

Raptor
======

<a href="https://www.raptor-editor.com/" target="_blank">Raptor</a> is LGPL licensed Javascript in-place editor.
About it configuration and API please visit the site.
Raptor comes with many plugins has been integrated in. Each function in the editor toolbar is a plugin that can
be enabled or disabled in the frontend. (See its' plugins at Raptor's documentation)

In the other hand you may check `assets/startup.js` about the available option flags.

Otherwise this boundled build of Raptor.js is a slightly patched version. Check `saveJson` plugin to see how it is
modified to be able to post some extra data to server side.

Notes
=====

Please feel free to make modifications or changes. Theoretically any kind of in-place WYSYWYG editor can be used
with a little alignment.
I hope my code may contains bugs or mistakes that would means I'm still alive. :-)

