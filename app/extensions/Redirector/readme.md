Bolt Redirector
===============

A wicked little [Bolt] [1] 1.2 extension that allows you to perform any pre-app `301 Moved Permanently` redirects. Kinda handy when you're moving from your silly flat file website/overly complicated CMS to Bolt. ;)

Installation
------------

If you'd like to use the latest `master` instead of the version included in the [bolt/bolt] (http://github.com/bolt/bolt) repo, you can install the extension by copying the downloaded `Redirector` directory into the `extensions` directory. Then, activate by adding `Redirector` to the array of enabled extensions in the main `app/config/config.yml` file:

```yml
enabled_extensions: [ Redirector ]
```

You can grab the extension as a [zip ball] [3] / [tar ball] [4].

301 away!
---------

Setting up your redirects is simple. In the extension's `config.yml` file, add the following:

```yml
aboutus:
	from: 'about-us.html'
	to: 'page/about-us'
```

Let's translate this: before any site processing takes place, and if a request is made to `/about-us.html`, the browser will be redirected to `/page/about-us` with the `301 Moved Permanently` response code.

Wildcards!
----------

Sometimes, it aint awesome to specify each redirect. So, we've added the ability to use wilcards. This is really useful if you want to match a whole bunch of requests that have a similar pattern. Let's consider this:

```yml
page:
	from: '{slug:segment}.html'
	to: 'page/{slug}'
```

Here, we're not specifying an individual request. Instead, we're allowing multiple requests to be processed through a single rule.

Also, notice the `segment` part? That simply means that `slug` must match the rule of `segment`, which is `([a-z0-9\-\_]+)`. This is required for all wilcards. It's just a rule of thumb.

You can also match various common extensions in one argument, like so (see the next section to find support extensions):

```yml
page:
	from: '{slug:segment}.{ext:ext}'
	to: 'page/{slug}'
```

**Note:** Upon checking each URI, the extension is not interested in what case it is in. Upper and lower case makes no difference. When `autoslug` is enabled (which it is by default), The URI will be converted to it's slugified equivalent (see Options).

#### Available Wilcard Types

The following wilcard types are available:

- `:all` is interpreted as `.*`
- `:alpha` is interpreted as `[a-z]+`
- `:alphanum` is interpreted as `[a-z0-9]+`
- `:any` is interpreted as `[a-z0-9\.\-\_\%\=\s]+`
- `:num` is interpreted as `[0-9]+`
- `:segment` is interpreted as `[a-z0-9\-\_]+`
- `:segments` is interpreted as `[a-z0-9\-\_\/]+`
- `:ext` is interpreted as `aspx?|f?cgi|s?html?|jhtml|jsp|phps?` (any to add/remove here?)

Multiple wilcards
-----------------

Not everyone has only one segment in their URIs. So, you can use as many as you like:

```yml
news:
	from: '/blog/{year:num}/{month:num}/{slug:segment}'
	to: '/news/{slug}'
```

Of course, in this example, you'd need to make sure that no two names/slugs are the same, otherwise there will be a little overlap - not good!

Options
-------

Options are defined like so:

```yml
options:
	option: value
	...
```

At the moment, there is only one option available: `autoslug`

When set to `true` (be default, you can turn it off if you really want to), the extension will convert your URIs to their slugified equivalents. So, if you've captured `About_Us` or `About Us` (`About%20Us`), it will be converted to `about-us` before the redirect takes place. Note that it makes these conversions *for each capture*, and not for the entire URI.

When set to `false`, the extension will not implement the slugger. So, if you have a rule stating that `Pages/{page:any}.{ext:ext}` (where `page` could be `AboutUs`), it would simply be converted to lowercase, ie. `aboutus`.

Coming Soon
-----------

There are a few features that need to be added at some point. Whilst there's no rush right now, these are the ones that are being looked into:

#### 1. Content type 'aware' redirecting

The idea here is to map an old route to one defined by a content type. Consider this markup:

```yml
pages:
	from: 'Default/Pages/{document:any}.{ext:ext}'
	to: '{page:document}'
```

Much like we use colons to capture and map arguments/parameters in `from`, for the purposes of generating a slug for a content type, we'll do the same in `to`. The syntax for such a map is `{<contenttype>:<usedvariable>}`.

In this case, we are stating that Bolt should generate a slug for the `page` content type, using `document` as the variable. So, if we visited `Default/Pages/Pricing.aspx`, we'd be redirected to `page/pricing`.

A to explain this a little more clearly, let's assume that our content type's `singular_slug` is set to `site-pages`. In that case, the above redirect would instead take you to `site-pages/profile`.

Here's another example:

```yml
newsitems:
	from: 'news/{id:num}/{title:any}' # news/73/this-is-a-test
	to: '{newsitem:title}'
```

Here, the idea is for Redirector to first check that the field `slug` in the `newsitem` content type definition uses `[ title, id ]`, after which it will generate the proper URI at the new location, say `news-item/this-is-a-test-73`, for example.

Handy, right?

#### 2. Routing.yml compatibility

Bolt 1.2 introduced the ability to map custom URIs to actual ones, by means of a `routing.yml` config file. This being flexible, you could then do anything you like. For example, you could map `/about-us` to the `page` content type. Internally, Bolt will simply perform an internal request to `/page/about-us` or `page/3`, for example.

Now, our idea is to add some functionality that would mould into this feature. Let's say that you have a routing rule specifying that `news/tech/{slug}` should be mapped to `newsitem/{slug}`. In your `routing.yml` file, you would have defined something like this in `routing.yml`:

```yml
technews:
    path:           /news/tech/{slug}
    defaults:       { _controller: 'Bolt\Controllers\Frontend::record', 'contenttypeslug': 'techitem' }
    contenttype:    techitem
```

In addition, let's say your old site had the following URI structure for this particular route: `News/Technology/Item/<slug>.html` Considering that you've defined the above routing rule, Redirector would be able to map to it. The syntax for this would look something like this:

```yml
news:
	from: 'News/Item/{slug:any}.html'
	to: '{route:technews}'
```

In this example, `News/Technology/Item/Microsoft_Acquires_Nokias_Hardware_and_Services_Business.html` would be redirected to `news/tech/microsoft-acquires-nokias-hardware-and-services-business`, which would then be internally processed by Bolt.

#### More

1. The ability to match an entire route, including the GET array
2. The ability to forward redirected POST requests (perhaps we'll match any method?) - this would incorporate point 1
3. Various options to define strict rules and global pattern replacements

Contributing
------------

If you feel that something is missing, not done right, or can be optimised, please submit a pull request. If you feel that features can be added, please submit an issue.

License
-------

Bolt Redirect is licensed under the Open Source [MIT License] [2].

  [1]: http://bolt.cm/                                  "Bolt"
  [2]: http://opensource.org/licenses/mit-license.php   "MIT License"
  [3]: https://github.com/foundry-code/bolt-redirector/zipball/master
  [4]: https://github.com/foundry-code/bolt-redirector/tarball/master
