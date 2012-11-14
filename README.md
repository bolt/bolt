Bolt
====

Sophisticated, lightweight & simple CMS, homepage: [Bolt.cm](http://bolt.cm)

Bolt is a tool for Content Management, which strives to be as simple and straightforward 
as possible. It is quick to set up, easy to configure, uses elegant templates, and above 
all: It's a joy to use. Bolt is created using modern open source libraries, and is best 
suited to build sites in HTML5 with modern markup. 

From a technical perspective: Bolt is written in PHP, and uses either SQLite, MySQL or 
PostgreSQL as a database. It's built upon the <a href="http://silex.sensiolabs.org/">Silex 
framework</a> together with a number of <a href="http://symfony.com/" target="">Symfony</a> 
<a href="http://symfony.com/components" target="">components</a> and
<a href="http://docs.bolt.cm/credits" target="">other libraries</a>. Bolt is released under 
the open source <a href="http://opensource.org/licenses/mit-license.php" target="">MIT-license</a>.

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