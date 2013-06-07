Bolt
====

Sophisticated, lightweight & simple CMS, homepage: [Bolt.cm](http://bolt.cm)

Bolt is a tool for Content Management, which strives to be as simple and straightforward 
as possible. It is quick to set up, easy to configure, uses elegant templates, and above 
all: It's a joy to use. Bolt is created using modern open source libraries, and is best 
suited to build sites in HTML5 with modern markup. 

From a technical perspective: Bolt is written in PHP, and uses either SQLite, MySQL or 
PostgreSQL as a database. It's built upon the [Silex framework](http://silex.sensiolabs.org) 
together with a number of [Symfony](http://symfony.com/) [components](http://symfony.com/components) 
and [other libraries](http://docs.bolt.cm/credits). Bolt is released under the open source 
[MIT-license](http://opensource.org/licenses/mit-license.php).

Current build status and code quality
-------------------------------------

[![Build Status](https://secure.travis-ci.org/bolt/bolt.png?branch=master)](http://travis-ci.org/bolt/bolt)

For continously inspecting our code, we use Scrutinizer CI. You can find all runs
on our code base [here](https://scrutinizer-ci.com/g/bolt/bolt/inspections).

Installation
------------

Because Bolt is now on [Packagist](https://packagist.org/packages/bolt/bolt),
installing is even more easy by using [Composer](http://getcomposer.org).

Installing composer can be done from the command line like so:

    $ curl -s http://getcomposer.org/installer | php

After that, you can install Bolt in one line:

    $ php composer.phar create-project bolt/bolt demo/ 1.0.0

In the above command, `demo/` is the relative path where you want Bolt to be
installed. `1.0.0` is the version number you'd like to install. For available
version numbers, check the tags in the [Github repo](https://github.com/bolt/bolt)
or on the [Packagist page](https://packagist.org/packages/bolt/bolt).

You're now good to go.

More detailed instructions can be found in the [Setup section in the documentation](http://docs.bolt.cm/setup).

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
For running unit tests you need [phpunit](http://www.phpunit.de/)

After installing, you can run the unit test suite by running

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
Learn more about writing extensions from our [docs](https://github.com/bolt/bolt-docs/blob/master/source/extensions.md)
