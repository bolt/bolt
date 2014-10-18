Bolt
====

Sophisticated, lightweight & simple CMS. Homepage: [Bolt.cm](http://bolt.cm)

Bolt is a tool for Content Management, which strives to be as simple and straightforward 
as possible. It is quick to set up, easy to configure, uses elegant templates, and above 
all: It's a joy to use. Bolt is created using modern open source libraries, and is best 
suited to build sites in HTML5 with modern markup. 

From a technical perspective: Bolt is written in PHP, and uses either SQLite, MySQL or 
PostgreSQL as a database. It's built upon the [Silex framework](http://silex.sensiolabs.org) 
together with a number of [Symfony](http://symfony.com/) [components](http://symfony.com/components) 
and [other libraries](http://docs.bolt.cm/credits). Bolt is released under the open source 
[MIT-license](http://opensource.org/licenses/mit-license.php).


Build status, code quality and other badges
-------------------------------------------


[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/bolt/bolt/trend.png)](https://bitdeli.com/free "Bitdeli Badge")
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/bolt/bolt?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Build Status](https://secure.travis-ci.org/bolt/bolt.png?branch=master)](http://travis-ci.org/bolt/bolt)
[![Scrutinizer Continuous Inspections](https://scrutinizer-ci.com/g/bolt/bolt/badges/general.png?s=74400dd068f81fe3ba434e5952b961bb83bbea62)](https://scrutinizer-ci.com/g/bolt/bolt/)

For continously inspecting our code, we use Scrutinizer CI. You can find all runs
on our code base [here](https://scrutinizer-ci.com/g/bolt/bolt/inspections).

Installation
------------

Detailed instructions can be found in the [Installation section in the documentation](http://docs.bolt.cm/installation).

Reporting issues
----------------
When you run into an issue, be sure to provide some details on the issue.
Please include with your report:
- the (example) input;
- the output you expected;
- the output actually produced.

This way we can reproduce your issue, turn it into a test and prevent the issue from occurring in future versions.

Unit tests
----------
For running unit tests you need [phpunit](http://www.phpunit.de/).

After installing, you can run the unit test suite by running:

    $ phpunit -c app/

This can now also be done by using app/nut:

    $ php app/nut tests:run

Extensions
----------
The available extensions that ship with Bolt are going to be separated once we have a separate extension
repository. We're planning for a neat way to install and manage extensions. However, this is not ready
yet. Therefor, we temporarily list available third party extensions on this page.

Currently, these are the third party extensions we're aware of:

- [TweetWidget](https://github.com/bolt/tweetwidget) by @bobdenotter
- [Gist](https://github.com/bolt/extension-gist) by @bobdenotter
- [TagCloud](https://github.com/axsy/bolt-extension-tagcloud) by @axsy
- [Newsletter subscription](https://github.com/magabriel/bolt-extension-newsletter-subscription) by @magabriel

The extensions can be placed in the `app/extensions` folder.
Learn more about writing extensions from our [docs](https://github.com/bolt/bolt-docs/blob/master/source/extensions.md).



-------
