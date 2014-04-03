Ed-It In Place Editor extension
===============================

Actually, I made this stuff just for fun to see how easy implement additional functions with an extension and I may say,
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

The function `{{ editable(field, record, options) }}` will place the editable content in the output page. The content is a
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

you may set the `teaser` field of a record to editable with the following twig command:

``{{ editable('teaser', record) }}``

`record` can be any content object available either implicitly or explicitly.
If optional `record` parameter omitted default record from the template context will be selected.

If the actual visitor has logged in and has corresponding permissions to change then can edit the content.
Moving the mouse over the editable area of the page an `Edit` button will float over that should raise the editor toolbar.

The editor toolbar can be customized with the optional `options` parameter of `editable()` like below.

``{{ editable('teser', record, { 'logo': false, 'statistics': false }) }}``

With this option you may enable or disable editor specific options that may differs in underlying editors.
In Raptor this parameter is a key=>value twig array map and turns off/on the corresponding plugins of Raptor.

**Warning! Parameter order has changed since version v0.1 because record object became optional now.**

Options in Raptor
-----------------

Currently following plugins are enabled by default and can be switched off:

* dockToScreen
* dockToElement
* guides
* viewSource
* historyUndo
* historyRedo
* textBold,
* textItalic
* textUnderline
* listUnordered
* listOrdered
* hrCreate
* clearFormatting
* linkCreate
* linkRemove

and these are can be enabled:

* floatLeft
* floatNone
* floatRight
* textBlockQuote
* textStrike
* textSuper
* textSub
* alignLeft
* alignCenter
* alignJustify
* alignRight
* languageMenu
* statistics
* logo
* textSizeDecrease
* textSizeIncrease
* fontFamilyMenu
* embed
* insertFile
* colorMenuBasic
* tagMenu
* classMenu
* snippetMenu
* specialCharacters
* tableCreate
* tableInsertRow
* tableDeleteRow
* tableInsertColumn
* tableDeleteColumn

Options in CKeditor
-------------------

Options in CKeditor just configures toolbar button groups and doesn't adds or removes any plugin.
Toolbar functions are grouped by group name which defines available toolbar functions internally.
Following toolbar functions enabled under a group name by default and these can't be turned off:

* inlinesave: EditableSave
* styles: Format
* basicstyles: Bold, Italic, Underline, Strike
* paragraph: NumberedList, BulletedList, Indent, Outdent, Blockquote
* table: Table

These are the optional toolbar elements:

* anchor: Link, Unlink, Anchor
* links: Link, Unlink
* subsuper: Subscript, Superscript
* mediaembed: MediaEmbed
* align: JustifyLeft, JustifyCenter, JustifyRight, JustifyBlock
* colors: TextColor, BGColor
* tools: SpecialChar, RemoveFormat, Maximize, Source

Enable a group in editor toolbar just list the group name in the `option` parameter this way:

``{{ editable('teser', record, 'anchor, subsuper') }}`` or ``{{ editable('teser', record, [ 'anchor', 'subsuper' ]) }}``

About Raptor
------------

<a href="https://www.raptor-editor.com/" target="_blank">Raptor</a> is LGPL licensed Javascript in-place editor.
About its configuration and API please visit the site.

This boundled build of Raptor.js is a slightly patched version. Check `saveJson` plugin to see how it is
modified to be able to post some extra data to server side.

About CKEditor
--------------

Extension has made support internal CKEditor boundled in Bolt but may use with your custom build with CKEditor download site.
This case just copy your distribution to ``Editable/assets/ckeditor`` and (I hope) no any special settings required.

Notes
=====

Please feel free to make modifications or changes. Theoretically any kind of in-place WYSYWYG editor can be used
with a little alignment.
I hope my code may contains bugs or mistakes that would means I'm still alive. :-)

