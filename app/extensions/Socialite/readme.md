Socialite
====================

An extention to add asynchronous (lazy/late loading) social media buttons and 
widgets to your tempalates using <code>{{ socialite('SocialNetwork') }}</code> 
or <code>{{ socialite({'SocialNetwork': 'variable'}) }}</code>.

The extension is based on the [Socialite JavaScript](https://github.com/tmort/Socialite) 
library by [David Bushell](http://dbushell.com) and [Tom Morton](http://twmorton.com)

##Examples
----------

####Facebook
Like this page: <code>{{ socialite('FacebookLike') }}</code>

Like your sites Facebook page: <code>{{ socialite({'FacebookFollow': 'https://www.facebook.com/MyPage'}) }}</code>

####Google+
+1 this page/article: <code>{{ socialite('GooglePlusOne') }}</code>

Share this page/article on Google+: <code>{{ socialite('GooglePlusShare') }}</code>

Follow your page on Goolge+: <code>{{ socialite({'GooglePlusFollow': 'https://plus.google.com/u/0/+MyPage/'}) }}</code>

####Twitter
Share page/article on twitter: <code>{{ socialite('TwitterShare') }}</code>

Mention your Twitter handle in a new tweet: <code>{{ socialite('TwitterMention') }}</code>

Follow your Twitter account: <code>{{ socialite('TwitterFollow') }}</code>
- Note that TwitterFollow has compulsorary settings in config.yml to make it work.

Specify a hashtag for a new tweet: <code>{{ socialite({'TwitterHashtag': 'HashCookies'}) }}</code>

An embedded Twitter timeline: <code>{{ socialite('TwitterEmbed') }}</code>
- Note that TwitterEmbed has compulsorary settings in config.yml to make it work.

####Buffer
Buffer this page/article: <code>{{ socialite({'BufferAppButton': record.image}) }}</code>

####LinkedIn
Share on LinkedIn: <code>{{ socialite('LinkedinShare') }}</code>

####Pinterest
PinIt on Pinterest: <code>{{ socialite('PinterestPinit') }}</code>


##Required Configuration Options
--------------------------------

####Twitter
In the extension's configuration you need to set your Twitter user name in the 
'twitter_handle' parameter.

For the Twitter feed set up you will need to follow these steps:
  1. Sign in to twitter.com and visit the [widgets section of your settings page](https://twitter.com/settings/widgets)
  2. Click the 'Create new' button 
  3. Copy the data-widget-id number from the box below 'Preview' to your config.yml file
  4. Add your Twitter handle and other settings to your configuration 
