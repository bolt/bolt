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
and [other libraries](http://docs.bolt.cm/3.0/other/credits#used-libraries-components). Bolt is released under the
open source [MIT-license](http://opensource.org/licenses/mit-license.php).


Build status, code quality and other badges
-------------------------------------------

[![Build Status](https://secure.travis-ci.org/bolt/bolt.png?branch=master)](http://travis-ci.org/bolt/bolt)
[![Scrutinizer Continuous Inspections](https://scrutinizer-ci.com/g/bolt/bolt/badges/general.png?s=74400dd068f81fe3ba434e5952b961bb83bbea62)](https://scrutinizer-ci.com/g/bolt/bolt/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4d1713e3-be44-4c2e-ad92-35f65eee6bd5/mini.png)](https://insight.sensiolabs.com/projects/4d1713e3-be44-4c2e-ad92-35f65eee6bd5)

For continuously inspecting our code, we use Scrutinizer CI. You can find all
runs on our code base [here](https://scrutinizer-ci.com/g/bolt/bolt/inspections).

Installation
------------

Detailed instructions can be found in the [Installation section in the documentation](http://docs.bolt.cm/installation).

Support
-------

Have a question? Want to chat? Run into a problem?  

 - There’s always some people willing to chat in our <strong>IRC channel</strong> on Freenode at <strong>#boltcms</strong>. No IRC client? Use <a href="/irc">our web-based client</a>.
 - We also have a <strong>Slack channel</strong> at <a href="https://boltcms.slack.com">boltcms.slack.com</a>. To get in, get yourself an invite at <a href="https://slack.bolt.cm">slack.bolt.cm</a>.
 - There's a <strong>Forum</strong> at <a href="https://discuss.bolt.cm">discuss.bolt.cm</a>, where we'll gladly help you sort out problems, or discuss other Bolt-related things.
 - We’re pretty active on <strong>Twitter</strong>. Follow us, or say hello at <a href="https://twitter.com/boltcm">@BoltCM</a>.


3rd Party Install Options
-------------------------

**Note:** These install options are provided by members of the greater Bolt community and are not directly supported by the core
developers. 

#### Yeoman Generator

[Tight](https://github.com/pinpickle/tight) 

Tighten that Bolt! This generator will set up Bolt for you with a local extension 
and a feature-packed theme ready for you to make the best website the world has yet seen.

**Note:** This is not designed to be a "ready-to-deploy" theme. It is designed to 
be barebones boilerplate so you can get to making what you need to make.

Provided by: [Christian Silver](https://github.com/Pinpickle)

#### ManageaCloud Deployable Configuration Examples

Distribution  | Status
------------- | -------------
[CentOS 7](https://manageacloud.com/configuration/bolt_cms_centos_7) | [![CentOS 7](https://manageacloud.com/configuration/bolt_cms_centos_7/build/5/image)](https://manageacloud.com/configuration/bolt_cms_centos_7/builds)
[Debian Jessie](https://manageacloud.com/configuration/bolt_debian_jessie) | [![Debian Jessie 8.0](https://manageacloud.com/configuration/bolt_cms/build/1/image)](https://manageacloud.com/configuration/bolt_cms/builds)
[Debian Wheezy](https://manageacloud.com/configuration/bolt_cms) | [![Debian Wheezy 7.0](https://manageacloud.com/configuration/bolt_cms/build/1/image)](https://manageacloud.com/configuration/bolt_cms/builds)
[Ubuntu Vivid 15.04](https://manageacloud.com/configuration/bolt_ubuntu_vivid) | [![Ubuntu Ubuntu Vivid Vervet 15.04](https://manageacloud.com/configuration/bolt_ubuntu_vivid/build/8/image)](https://manageacloud.com/configuration/bolt_ubuntu_vivid/builds)
[Ubuntu Utopic 14.10](https://manageacloud.com/configuration/bolt_cms_ubuntu_utopic_unicorn_1410) | [![Ubuntu Utopic Unicorn 14.10](https://manageacloud.com/configuration/bolt_cms_ubuntu_utopic_unicorn_1410/build/6/image)](https://manageacloud.com/configuration/bolt_cms_ubuntu_utopic_unicorn_1410/builds)
[Ubuntu Trusty 14.04](https://manageacloud.com/configuration/bolt_cms_ubuntu_trusty_tahr_1404) | [![Ubuntu Trusty Tahr 14.04](https://manageacloud.com/configuration/bolt_cms_ubuntu_trusty_tahr_1404/build/2/image)](https://manageacloud.com/configuration/bolt_cms_ubuntu_trusty_tahr_1404/builds)

Provided by: [Rubén Rubio Rey](https://github.com/tk421)

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
