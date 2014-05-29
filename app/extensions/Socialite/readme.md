Socialite
====================

An extension to add asynchronous (lazy/late loading) social media buttons and 
widgets to your templates using <code>{{ socialite('SocialNetwork') }}</code> 
or <code>{{ socialite({'SocialNetwork': 'variable'}) }}</code>.

The extension is based on the [Socialite JavaScript](https://github.com/tmort/Socialite) 
library by [David Bushell](http://dbushell.com) and [Tom Morton](http://twmorton.com)

##Examples
----------

####Facebook
Like this page: <code>{{ socialite('FacebookLike') }}</code>

Like your site's Facebook page: <code>{{ socialite({'FacebookFollow': 'https://www.facebook.com/MyPage'}) }}</code>

####Google+
+1 this page/article: <code>{{ socialite('GooglePlusOne') }}</code>

Share this page/article on Google+: <code>{{ socialite('GooglePlusShare') }}</code>

Follow your page on Google+: <code>{{ socialite({'GooglePlusFollow': 'https://plus.google.com/u/0/+MyPage/'}) }}</code>

####Twitter
Share page/article on Twitter: <code>{{ socialite('TwitterShare') }}</code>

Mention your Twitter handle in a new tweet: <code>{{ socialite('TwitterMention') }}</code>

Follow your Twitter account: <code>{{ socialite('TwitterFollow') }}</code>
- Note that TwitterFollow has compulsorary settings in config.yml to make it work.

Specify a hashtag for a new tweet: <code>{{ socialite({'TwitterHashtag': 'HashCookies'}) }}</code>

An embedded Twitter timeline: <code>{{ socialite('TwitterEmbed') }}</code>
- Note that TwitterEmbed has compulsory settings in `config.yml` to make it work.

####Buffer
Buffer this page/article: <code>{{ socialite({'BufferAppButton': record.image}) }}</code>

####LinkedIn
Share on LinkedIn: <code>{{ socialite('LinkedinShare') }}</code>

####Pinterest
PinIt on Pinterest: <code>{{ socialite('PinterestPinit') }}</code>

####GitHub
Star a user/organisation's repo on GitHub:

<code>{{ socialite('GitHubStar') }}</code> (uses setting in config.yml)

or

<code>{{ socialite({'GitHubStar': ['github_user','github_repo']}) }}</code>

Fork a user/organisation's repo on GitHub:

<code>{{ socialite('GitHubFork') }}</code> (uses setting in config.yml)

or

<code>{{ socialite({'GitHubFork': ['github_user','github_repo']}) }}</code>

Follow a user on GitHub:

<code>{{ socialite('GitHubFollow') }}</code> (uses setting in config.yml)

or

<code>{{ socialite({'GitHubFollow': 'github_user'}) }}</code>


##Required Configuration Options
--------------------------------

####Twitter
In the extension's configuration you need to set your Twitter username in the 
'twitter_handle' parameter.

For the Twitter feed setup you will need to follow these steps:
  1. Sign in to twitter.com and visit the [widgets section of your settings page](https://twitter.com/settings/widgets)
  2. Click the 'Create new' button 
  3. Copy the data-widget-id number from the box below 'Preview' to your `config.yml` file
  4. Add your Twitter handle and other settings to your configuration 
  
