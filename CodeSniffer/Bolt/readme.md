#Bolt coding style standard

Bolt tries to adhere a coding style based on PSR-2 and the Symfony2 coding standard.
To help following our standard a ruleset for [PHP_CodeSniffer](http://pear.php.net/package/PHP_CodeSniffer) is provided.

This standard is a work in progress and will be refined over time!

##Usage

###Installation

- Install/update using composers --require-dev command
- Install PHP_CodeSniffer or use the one that is installed with compooser
- Start phpcs with --standard Bolt <path-to-bolt>/CodeSniffer/Bolt

###Run

#### CLI

phpcs --standard Bolt <path-to-bolt>/CodeSniffer/Bolt

#### NetBeans
As of 8.0 there's no way to specify standards-path directly. You would have to copy over Bolt and Symfony2 standards
directory by hand and would have to adjust the path to the Symfony2 ruleset inside Bolt ruleset.

#### NetBeans/PHPCSMD-plugin
Specify the path to standards directory <path-to-bolt>/CodeSniffer/Bolt in Options / PHP / PHPCSMD "--standard".

#### Others

See the manual of your editor if it supports PHP_CodeSniffer and how to use it.

