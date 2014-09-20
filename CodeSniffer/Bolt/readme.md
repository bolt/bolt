#Bolt coding style standard

Bolt tries to adhere a coding style based on PSR-2 and the Symfony2 coding standard.
To help following our standard a ruleset for [PHP_CodeSniffer](http://pear.php.net/package/PHP_CodeSniffer) is provided.

This standard is a work in progress and will be refined over time!

##Usage

###Installation

- Install/update using composers `--require-dev` command
- Install PHP_CodeSniffer or use the one that is installed with compooser
- Start phpcs with `--standard Bolt <path-to-bolt>/CodeSniffer/Bolt`

###Run

#### • CLI

`phpcs --standard Bolt <path-to-bolt>/CodeSniffer/Bolt`

#### • NetBeans
As of 8.0 there's no way to specify standards-path directly. You would have to copy over Bolt and Symfony2 standards
directory by hand and would have to adjust the path to the Symfony2 ruleset inside Bolt ruleset.

##### PHPCSMD-plugin
Specify the path to standards directory `<path-to-bolt>/CodeSniffer/Bolt` in Options | PHP | PHPCSMD | "--standard".

#### • Eclipse
- Install Eclipse PTI plugin. (http://www.phpsrc.org/projects/pti/wiki/Installation)
- You need to check PTI Core and PHP Tool CodeSniffer at least.
- Overwrite PTI CodeSniffer plugin sources with that comes with Bolt...
- …PTI CodeSniffer is here about: <path_to_eclipse>/plugins/org.phpsrc.eclipse.pti.library.pear_1.2.2.R20120127000000/php/library/PEAR/PHP/
- …Our codesniffer is here: <bolt_project_path>/vendor/squizlabs/php_codesniffer/

##### Setting up PTI CodeSniffer in Eclipse
- Go to `Window - Preferences - PHP Tools - PHP CodeSniffer`
- Setup your PHP Executable and PEAR library
- Beside the `CodeSniffer Standards` box click `New` button and add Bolt as new standard with the path `<bolt_project_path>/CodeSniffer/Bolt`
- May check Bolt as default standard now in the list

##### Check validation settings
- Go to `Window - Preferences - Validation` and check in `CodeSniffer validation` as you need Manual or Build mode or
- enable it for your `Project specific settings` at `Project - Properties - Validation`

Finally you may need `Problems` view to open to see results wehn you clicked `Validate` on a php file.

#### • PhpStorm
See [here] (http://www.jetbrains.com/phpstorm/webhelp/using-php-code-sniffer-tool.html) and add
`<path-to-bolt>/CodeSniffer/Bolt` as described in point "To appoint a custom coding style to use".

#### • Others

See the manual of your editor if it supports PHP_CodeSniffer and how to use it.

