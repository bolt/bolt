Bolt
====

Sophisticated, lightweight & simple CMS. Homepage: [Bolt.cm](https://bolt.cm)

Bolt is a tool for Content Management, which strives to be as simple and
straightforward as possible. It is quick to set up, easy to configure, uses
elegant templates, and above all: It's a joy to use. Bolt is created using
modern open source libraries, and is best suited to build sites in HTML5 with
modern markup.

From a technical perspective: Bolt is written in PHP, and uses either SQLite,
MySQL or PostgreSQL as a database. It's built upon the [Silex framework](http://silex.sensiolabs.org)
together with a number of [Symfony](http://symfony.com/) [components](http://symfony.com/components)
and [other libraries](http://docs.bolt.cm/credits). Bolt is released under the
open source [MIT-license](http://opensource.org/licenses/mit-license.php).


Build status, code quality and other badges
-------------------------------------------

[![Build Status](https://secure.travis-ci.org/bolt/bolt.png?branch=master)](http://travis-ci.org/bolt/bolt)
[![Scrutinizer Continuous Inspections](https://scrutinizer-ci.com/g/bolt/bolt/badges/general.png?s=74400dd068f81fe3ba434e5952b961bb83bbea62)](https://scrutinizer-ci.com/g/bolt/bolt/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4d1713e3-be44-4c2e-ad92-35f65eee6bd5/mini.png)](https://insight.sensiolabs.com/projects/4d1713e3-be44-4c2e-ad92-35f65eee6bd5)

For continuously inspecting our code, we use Scrutinizer CI. You can find all
runs on our code base [here](https://scrutinizer-ci.com/g/bolt/bolt/inspections).

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/bolt/bolt/trend.png)](https://bitdeli.com/free "Bitdeli Badge")
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/bolt/bolt?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


Installation
------------

Detailed instructions can be found in the [Installation section in the documentation](http://docs.bolt.cm/installation).

Try bolt in [Ubuntu](https://manageacloud.com/cookbook/tijit2bpp3129rdctb81f1cflk/deploy#test_deployment), [CentOS](https://manageacloud.com/cookbook/nt1pf9254cg8mm1t4k0nv96jv5/deploy#test_deployment), [Debian](https://manageacloud.com/cookbook/oj5dbkcehg9h7497fjq2lagk66/deploy#test_deployment) or [Amazon Linux](https://manageacloud.com/cookbook/a382qtma5gq1928ofrsrncr70c/deploy#test_deployment).

Deployable configuration examples for [Ubuntu](https://manageacloud.com/cookbook/bolt_cms_ubuntu_utopic_unicorn_1410), [CentOS](https://manageacloud.com/cookbook/bolt_cms_centos_7), [Debian](https://manageacloud.com/cookbook/bolt_cms) and  [Amazon Linux](https://manageacloud.com/cookbook/bolt_cms_amazon_2014032)

Reporting issues
----------------
When you run into an issue, be sure to provide some details on the issue.
Please include with your report:
- the (example) input;
- the output you expected;
- the output actually produced.

This way we can reproduce your issue, turn it into a test and prevent the issue
from occurring in future versions.

Unit tests
----------
For running unit tests you need [phpunit](http://www.phpunit.de/).

After installing, you can run the unit test suite by running:

    $ phpunit

This can now also be done by using app/nut:

    $ php app/nut tests:run

Extensions and Themes
---------------------
Since Bolt 2.0, you can install extensions and themes directly from Bolt's
interface. To browse the available extensions and themes, visit
[extensions.bolt.cm](https://extensions.bolt.cm).

-------
