Bolt 4 (Early Development)
==========================

---
### NOTE: This is the v4 development branch:
  * **Should be considered unstable**
  * **Not suitable for production use**
  * **API is not stable and subject to change**

---

A [Sophisticated, lightweight & simple CMS][bolt-cm] released under the open
source [MIT-license][MIT-license].

Bolt is a tool for Content Management, which strives to be as simple and
straightforward as possible.

It is quick to set up, easy to configure, uses elegant templates, and above
all, it's a joy to use!

Bolt is created using modern open source libraries, and is best suited to build
sites in HTML5 with modern markup.

From a technical perspective, Bolt is written in PHP, and built upon the
[Silex framework][silex] together with a number of [Symfony][symfony]
[components][sf-components] and many other mature and well supported
[libraries][required-libraries]. 

Bolt also supports using either SQLite, MySQL (MariaDB) or PostgreSQL as the 
database platform.


Build status, code quality and other badges
-------------------------------------------

[![Build Status][travis-badge]][travis]
[![Scrutinizer Continuous Inspections][codeclimate-badge]][codeclimate]
[![SensioLabsInsight][sensio-badge]][sensio-insight]


Installation
------------

Detailed instructions can be found in the [Installation section in the documentation][bolt-installation].

Support
-------

Have a question? Want to chat? Run into a problem?  

 - We have a **Slack channel** at [boltcms.slack.com][bolt-slack]. To get in, 
   get yourself an invite at [slack.bolt.cm][bolt-slack-invite]
 - There’s always some people willing to chat in our **IRC channel** on 
   Freenode at **#boltcms**. No IRC client? Use [our web-based IRC client][bolt-irc]
 - There's a **Forum** at [discuss.bolt.cm][bolt-forum], where we'll gladly 
   help you sort out problems, or discuss other Bolt-related things
 - We’re pretty active on **Twitter**. Follow us, or say hello at 
   [@BoltCM][bolt-twitter]


Reporting issues
----------------
When you run into an issue, be sure to provide some details on the issue.

Please include with your report:
- the (example) input;
- the output you expected;
- the output actually produced.

This way we can reproduce your issue, turn it into a test and prevent the issue
from occurring in future versions.


Extensions and Themes
---------------------
Since Bolt 2.0, you can install extensions and themes directly from Bolt's
interface. To browse the available extensions and themes, visit
[market.bolt.cm][market-bolt-cm].

-------

[bolt-cm]: https://bolt.cm
[market-bolt-cm]: https://market.bolt.cm
[bolt-installation]: https://docs.bolt.cm/installation
[bolt-irc]: https://bolt.cm/irc
[bolt-slack-invite]: https://boltcms.slack.com
[bolt-slack]: https://boltcms.slack.com
[bolt-forum]: https://discuss.bolt.cm
[bolt-twitter]: https://twitter.com/boltcm
[silex]: http://silex.sensiolabs.org
[symfony]: http://symfony.com
[sf-components]: http://symfony.com/components
[required-libraries]: https://docs.bolt.cm/other/credits#used-libraries-components
[MIT-license]: http://opensource.org/licenses/mit-license.php
[travis]: http://travis-ci.org/bolt/bolt
[travis-badge]: https://travis-ci.org/GawainLynch/bolt.svg?branch=release%2F3.3
[codeclimate]: https://lima.codeclimate.com/github/bolt/bolt
[codeclimate-badge]: https://lima.codeclimate.com/github/bolt/bolt/badges/gpa.svg
[sensio-insight]: https://insight.sensiolabs.com/projects/4d1713e3-be44-4c2e-ad92-35f65eee6bd5
[sensio-badge]: https://insight.sensiolabs.com/projects/4d1713e3-be44-4c2e-ad92-35f65eee6bd5/mini.png
