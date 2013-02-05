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

Current build status
--------------------

[![Build Status](https://secure.travis-ci.org/bobdenotter/bolt.png?branch=master)](http://travis-ci.org/bobdenotter/bolt)

Installation
------------

Create a new folder, and clone from github. Then use composer to get the Silex micro-framework and components:

    git clone git://github.com/bobdenotter/bolt.git bolt
    cd bolt 
    curl -s http://getcomposer.org/installer | php
    php composer.phar install

And you're good to go.

More detailed instructions can be found in the [Setup section in the documentation](http://docs.bolt.cm/setup).

Unit tests
----------
For running unit tests you need [phpunit](http://www.phpunit.de/)

After installing, you can run the unit test suite by running

    phpunit -c app/

This can now also be done by executing the 'tests:run' command from app/nut.


Reporting issues
----------------
When you run into an issue, be sure to provide some details on the issue.
Please include with your report:
- the (example) input;
- the output you expected;
- the output actually produced.

This way we can reproduce your issue, turn it into a test and prevent the issue from occurring in future versions.