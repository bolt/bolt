Bolt RSS Aggregator
=======================

A RSS Aggregator extension for the [Bolt CMS](http://www.bolt.cm). Shows feed items of external RSS feeds anywhere on your site.

Instructions
=======================

1. Download the extension and place it into your app/extensions folder as app/extensions/RSSAggregator

2. Activate it in your app/config/config.yml by adding RSSAggregator to the `enabled_extensions` option.  
Example: `enabled_extensions: [ RSSAggregator, your_other_extensions... ]`

3. Place the `{{ rss_aggregator() }}` Twig function in your template. It requires at least 1 parameter: the feed URL.  
Example: `{{ rss_aggregator('http://rss.cnn.com/rss/edition.rss') }}`

4. You can pass several options to the Twig function:  
`{{ rss_aggregator('http://rss.cnn.com/rss/edition.rss', { 'limit': limit, 'showDesc': true }) }}`  
	+ limit: The amount of links to be shown, default: 5
	+ showDesc: Show the full description, default: false
	+ showDate: Show the date, default: false  
	+ descCutoff: Number of characters to display in the description, default: 100
	+ cacheMaxAge: The time a cached feed stays valid in minutes, default: 15, set to 0 to disable caching

Customization
=======================

Override the CSS styles defined in RSSAggregator/assets/rssaggregator.css in your own stylesheet.

Support
=======================

Please use the issue tracker: [Github](http://github.com/sekl/bolt-rssaggregator/issues)