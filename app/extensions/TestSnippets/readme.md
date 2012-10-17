Snippet Tester
==============

This extension is only interesting for developers. It inserts a lot of snippets
in various locations in the HTML document. Inspect teh source code to see how
to insert different snippets in the different locations available. Snippets will
be inserted both 'directly', as well as via a callback function.

Snippets are inserted at the following locations:

 - `startofhead` - after the `<head>`-tag.
 - `aftermeta` - after the last `<meta [..] >`-tag.
 - `aftercss` - after the last `<link [..] >`-tag.
 - `endofhead` - before the `</head>`-tag.
 - `startofbody` - after the `<body>`-tag.
 - `endofbody` - before the `</body>`-tag.
 - `endofhtml` - before the `</html>`-tag.
 - `afterhtml` - after the `</html>`-tag.

To see how these work, read the source in `app/extensions/TestSnippets/extension.php`.
