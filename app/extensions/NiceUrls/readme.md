NiceUrls
========

This NiceUrls extension is a pure code extension which allows for custom routes
without the pre-defined 'page', 'entry' or 'kitchensink' part or other 
friendly URLs structure.

The extension provides two separate funcionalities:

1. Match the URL against a set of rules to find the right internal route when
   a "friendly url" is requested.
2. A Twig filter to transform internal links into friendly URLs, to be used
   in the templates.

Configuration
-------------

Routes are added in the `config.yml` extension file. Early matches win, so make
sure you place the more general routes at the end.

When a route matches, a subrequest is made to the actual page and the contents 
are displayed while retaining the user-defined url in the address bar of the
browser.

### Example config

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
    
pages:
  from:
    slug: p
  to:
    contenttypeslug: pages
    slug: ""
      
</pre>
 
In this case we have three custom urls, defined by the from slug. Note that the 
name of the block (`about_foo`, `geo` and `pages`) are just references 
(e.g. descriptions) for you and group a block.
  
This config adds the following routing:
 
- `http://example.org/about/foo/bar` will show the contents of `http://example.org/page/about`
- `http://example.org/about` will show the contents of `http://example.org/kitchensink/about`
- `http://example.org/p` will show the contents of `http://example.org/pages`


Wildcards in routes
-------------------

Named wildcards are allowed in routes, but make sure you don't use wildcards all
the way as the routing can get confused. 

- E.g. if you would use `%%item1%%/%%item2%%` as the 'from slug' in the example
  below, the routing would then match everything as the route just flip the
  URI parts. As both are wildcards, this will mean that anything will be matched
  at `http://yoursite.com/*anything*/*anything*`.

- E.g.2 The example below translates `http://yoursite.com/news/item/entry` to `http://yoursite.com/entry/item`.

<pre>
new_route:
  from:
    slug: news/%%item1%%/%%item2%%
  to:
    contenttypeslug: %%item2%%
    slug: %%item1%%
</pre>


Twig filter
-----------

You can use the `niceurl` Twig filter in your templates to make Bolt honor the 
friendly routes defined.

Example: 

<pre>
pages:
  from:
    slug: p
  to:
    contenttypeslug: pages
    slug: ""

page:
  from:
    slug: p/%%slug%%
  to:
    contenttypeslug: page
    slug: %%slug%%
</pre>

1. Let's say we have the following code in `record.twig`:

    `<a href="{{ previous.link }}">Previous</a>`

    It will generate:
    
    `<a href="/page/my-page-slug">Previous</a>`
    
    We can just add the `niceurl` filter to the link:
    
    `<a href="{{ previous.link | niceurl }}">Previous</a>`
    
    And now it will generate:
    
    `<a href="/p/my-page-slug">Previous</a>`
    
2. In `_aside.twig` we have:

    `<a href="{{ paths.root ~ ct.slug }}">See all</a>`
    
    It will generate:
    
    `<a href="/pages">See all</a>`

    We can just write:
    
    `<a href="{{ (paths.root ~ ct.slug ) | niceurl }}">See all</a>`
    
    And now it will generate:
    
    `<a href="/p">See all</a>`

### Limitations

The filter works only with simple `contenttype/slug` or ` contenttype/%%slug%%` 
definitions as shown in the examples. Do not try it with somthing like 
`news/%%item1%%/%%item2%%` because it doesn't make sense.
    

    
    