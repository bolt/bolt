UIKit
-----

We currently use UIKit only for the markdown editor. We might expand this to
other things, perhaps.

Once it's working properly, we might want to integrate it into our Grunt/Bower
build setup: https://github.com/uikit/uikit

The compressed codemirror was built at http://codemirror.net/doc/compress.html
with the following options:

    Version: 4.13

    CodeMirror Library:
    - codemirror.js
    Modes:
    - gfm.js
    - markdown.js
    Add-ons:
    - active-line.js
    - colorize.js
    - markdown-fold.js
    - trailingspace.js
