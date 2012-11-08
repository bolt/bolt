NiceUrls
========

This NiceUrls extension is a pure code extension which allows for custom routes
without the pre-defined 'page', 'entry' or 'kitchensink' part.

It works by using the configuration to add more routes, which are defined
before all other application routes. When a route matches, a subrequest is made
to the actual page and the contents are displayed while retaining the
user-defined url in the address bar of the browser.

Example
-------

example config:

<pre>
about_foo:
  from:
    slug: about/foo/bar
  to:
    contenttypeslug: page
    slug: about
geo:
  from:
    slug: about
  to:
    contenttypeslug: kitchensink
    slug: about
</pre>

In this case we have two custom urls, defined by the from slug. Note that the
name of the block (about_foo and geo) are just references (e.g. descriptions)
for you and group a block.

This config adds the following routing:

http://example.org/about/foo/bar will show the contents of http://example.org/page/about
http://example.org/about will show the contents of http://example.org/kitchensink/about