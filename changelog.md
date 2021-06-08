Changelog for Bolt 3.x
======================

Bolt 3.7.4
----------

Released: 2021-06-08. Notable changes:

 - Notify users of 4.0 stable release. #7890
 - Improve Yaml export for compatibility with Bolt 4/5 Conimex import. #7910
 - Killing PHP7.4.3 exceptions post-migration from older Bolt/PHP versions. #7906
 - Fix: Fetching content by Taxonomy does not work if ContentType Name is different than ContentType Slug. #7894
 - Allow `rel`-attribute by default. #7885
 - Allow `{{ fields(record = record) }}`, to pass in a record not in global scope. #7912
 - Add Composer 2 compatibility. #7913
 - Fix: querying by ContentType slug (fixes regression introduced in #7894) #7916

Bolt 3.7.3
----------

Released: 2021-06-07. Scrapped due to a regression we found after release.

Bolt 3.7.2
----------

Released: 2020-10-20. Notable changes:

 - Security: Restrict `filter` options in `Request` in Twig context
 - Security: Provide a stronger secret for `UrlSigner`
 - Security: Allow only directories to be renamed with `renameFolder` #7867
 - Fixes slashes in directory names #7871
 - fixed typo 'an' to 'and' in README #7875
 - Check if we have a current user, prevent "Trying to access array offset" extension #7869
 - Fix ContextErrorException in PHP 7.4 #7868
 - Update composer.json: Add `"public-dir": "public"` #7866

Special thanks go out to the following for responsibly disclosing a security issue to us:

 - Charles Fol - https://www.ambionics.io/
 - ERNW Research GmbH - https://ernw.de/

Bolt 3.7.1
----------

Released: 2020-05-07. Notable changes:

 - Security: Check CSRF on Preview page, and prevent renaming files to blacklisted filetypes [#7853](https://github.com/bolt/bolt/pull/#7853)
 - Change: Add hreflang to allowed_attributes [#7855](https://github.com/bolt/bolt/pull/#7855)
 - Chore: Updating dependencies [#7842](https://github.com/bolt/bolt/pull/#7842)
 - Fixed: Fix tag cloud, update NPM deps [#7856](https://github.com/bolt/bolt/pull/#7856)
 - Fixed: Select field with multiple contenttypes and display values results in a `ContextErrorException` [#7849](https://github.com/bolt/bolt/pull/#7849)
 - Fixed: Trying to access array offset on value of type `null` with PHP 7.4 [#7843](https://github.com/bolt/bolt/pull/#7843)
 
Special thanks go out [Sivanesh Ashok](https://stazot.com/) for responsibly disclosing the two fixed security issues to us. 
  
Bolt 3.7.0
----------

Released: 2019-11-12. Notable changes:

 - Change: Dropped support for PHP 5.5, 5.6 and 7.0. [#7826](https://github.com/bolt/bolt/pull/7826)
 
This means the minimum requirement for Bolt 3.7 is now PHP 7.1. Because of this,
the default distributions will now include more recent versions of libraries,
providing better support for more recent versions of PHP 7.1 and higher 
out-of-the-box. This is most noticeable with Doctrine, our database library. 

If you're stuck on an older PHP version, you can keep using Bolt 3.6 for the 
foreseeable future. 
 
Bolt 3.6.11
-----------

Released: 2019-11-10. Notable changes:

 - Fixed: Fix tags in non-english languages and firefox caching selected options [#7822](https://github.com/bolt/bolt/pull/7822)
 - Fixed: Fix typo in `AccessControlListener.php` [#7809](https://github.com/bolt/bolt/pull/7809)
 - Security: Fix CRSF issues in file operations [#7823](https://github.com/bolt/bolt/pull/7823)
 - Updated: Add `download` to `allowed_attributes` in HTML cleaner [#7808](https://github.com/bolt/bolt/pull/7808)
 - Updated: Added comment about required IP in `trustProxies` [#7807](https://github.com/bolt/bolt/pull/7807)
 - Updated: Export improvements [#7812](https://github.com/bolt/bolt/pull/7812)
 - Updates: Update tests for Doctrine 2.10 compatibility [#7824](https://github.com/bolt/bolt/pull/7824)

Special thanks go out f4h4dbt for responsibly disclosing the CSRF issue to us. 

Bolt 3.6.10
-----------

Released: 2019-08-15. Notable changes:

 - Security: Prevent XSS in system log (for authenticated users) [#7802](https://github.com/bolt/bolt/pull/7802)
 - Security: Prevent XSS in image alt/title (for authenticated users) [#7801](https://github.com/bolt/bolt/pull/7801)
 - Security: Prevent XSS in "Create file" in file manager (for authenticated users) [#7800](https://github.com/bolt/bolt/pull/7800)
 - Fixes: Prevent update of `guzzlehttp/psr7` to 1.6, fixes tests [#7798](https://github.com/bolt/bolt/pull/7798)
 - Fixed: Fix taxonomy name collision [#7799](https://github.com/bolt/bolt/pull/7799)
 - Fixed: Rebuilding assets, fixing tags cloud [#7794](https://github.com/bolt/bolt/pull/7794)

Special thanks go out to @marcingajda for his contributions, and to @KotatuBot 
for responsibly disclosing the XSS issues to us. 

Bolt 3.6.9
----------

Released: 2019-06-24. Notable changes:

 - Added: Add abbreviations to CKeditor, using `<abbr>`-tag. [#7783](https://github.com/bolt/bolt/pull/7783)
 - Fixed: Use isIterable over Traversable [#7778](https://github.com/bolt/bolt/pull/7778)
 - Updated: Select2 updated to 4.0.7, since it _finally_ works on IE 11 again. [#7783](https://github.com/bolt/bolt/pull/7783)

Bolt 3.6.8
----------

Released: 2019-05-23. Notable changes:

 - Fixed: Make sure stripFields is set to array by default [#7776](https://github.com/bolt/bolt/pull/7766)

Bolt 3.6.7
----------

Released: 2019-04-11. Notable changes:

 - Added: "Strip keys" option to excerpt of fields. [#7759](https://github.com/bolt/bolt/pull/7759)
 - Added: Add information about how to set up custom CKeditor styles. [#7769](https://github.com/bolt/bolt/pull/7769)
 - Change: Make sure `accept_file_types` is an INDEXED array, and disallow certain filetypes to be whitelisted. [#7768](https://github.com/bolt/bolt/pull/7768)
 - Change: Make the bootstrap more robust. [#7763](https://github.com/bolt/bolt/pull/7763)
 - Fixed: Compare filename extension lowercased [#7772](https://github.com/bolt/bolt/pull/7772)
 - Fixed: Remove unsupported guzzlehttp version. [#7762](https://github.com/bolt/bolt/pull/7762)

This release comes with special thanks to Felipe Gaspar (fgaspar) for
responsibly reporting a potential security issue to us.

Bolt 3.6.6
----------

Released: 2019-03-07. Notable changes:

 - Change: Allow lower and upper case file extensions [#7751](https://github.com/bolt/bolt/pull/7751)
 - Change: Log a warning instead of info when permissions are not configured [#7749](https://github.com/bolt/bolt/pull/7749)
 - Fixed: Add extra workaround for HTML-like and Image fields in preview [#7754](https://github.com/bolt/bolt/pull/7754)
 - Updated: Update `config.yml.dist` [#7748](https://github.com/bolt/bolt/pull/7748)
 - Updated: Update `messages.pl_PL.yml` [#7747](https://github.com/bolt/bolt/pull/7747)

Bolt 3.6.5
----------

This release comes with special thanks to Andrea Santese (medu554) for responsibly
reporting a security issue to us, and to @JarJak for providing a fix.

Released: 2019-02-26. Notable changes:

 - Security: Fix filemanager file extensions. [#7745](https://github.com/bolt/bolt/pull/7745)
 - Added: Allow custom attributes for image fields. [#7743](https://github.com/bolt/bolt/pull/7743)
 - Added: Limit when values of a select is an entity [#7742](https://github.com/bolt/bolt/pull/7742)
 - Fixed: use the correct `<sup>` tag in `allowed_tags`. [#7737](https://github.com/bolt/bolt/pull/7737)

Bolt 3.6.4
----------

Released: 2019-01-24. Notable changes:

 - Fixed asset url generation for Bolt install in subfolder. [#7725](https://github.com/bolt/bolt/pull/7725)
 - Fixed: DBAL Sqlite schema diff bug, still needed on DBAL 2.9. [#7733](https://github.com/bolt/bolt/pull/7733)
 - Fixed: Ignore default value alteration for MySQL `TEXT` and `BLOB` types. [#7477](https://github.com/bolt/bolt/pull/7477)
 - Updated: Updates to `bolt/thumbs` and `bolt/filesystem`.
 - Updated: Various updates to Symfony components, Composer packages and Front-end dependencies.

Bolt 3.6.3
----------

Released: 2018-12-12. Notable changes:

 - Fixed: Add missing status filter. [#7719](https://github.com/bolt/bolt/pull/7719)
 - Fixed: Add whitespace between status label and value. [#7717](https://github.com/bolt/bolt/pull/7717)
 - Fixed: Allow for `$locale` optional parameter in `localedatetime`. [#7723](https://github.com/bolt/bolt/pull/7723)
 - Fixed: Fix filters for Content without taxonomy. [#7722](https://github.com/bolt/bolt/pull/7722)
 - Fixed: Fix listing without owner. [#7716](https://github.com/bolt/bolt/pull/7716)
 - Fixed: Fix setting up legacy Contenttype object. [#7720](https://github.com/bolt/bolt/pull/7720)

Bolt 3.6.2
----------

Released: 2018-11-09. Notable changes:

 - Fixed: Fix slugs for contenttypes that use a field other than 'title'. [#7708](https://github.com/bolt/bolt/pull/7708)
 - Fixed: Handle Empty Search Parameter Error. [#7712](https://github.com/bolt/bolt/pull/7712)
 - Fixed: Fix styling of panel actions. [#7707](https://github.com/bolt/bolt/pull/7707)

Bolt 3.6.1
----------

Released: 2018-11-02. Notable changes:

 - Fixed: Add missing part of select field values in repeaters. [#7701](https://github.com/bolt/bolt/pull/7701)
 - Fixed: Fix for field type blocks with translation extension. [#7705](https://github.com/bolt/bolt/pull/7705)
 - Fixed: Fix for Select values inside repeaters inside templatefields. [#7636](https://github.com/bolt/bolt/pull/7636)
 - Fixed: Stop stack being queried in `_macro.twig` when stack is disabled. [#7698](https://github.com/bolt/bolt/pull/7698)
 - Fixed: Temporary fix for repeater fields in changelog. [#7683](https://github.com/bolt/bolt/pull/7683)
 - Updated: Javascript dependencies, including `blueimp-file-upload`.

Bolt 3.6.0
----------

Released: 2018-10-10. Notable changes:

 - Feature: Add cron "Minute", for timed publish [#7598](https://github.com/bolt/bolt/pull/7598)
 - Feature: Drag-n-drop sorting for Content Blocks [#7621](https://github.com/bolt/bolt/pull/7621)
 - Feature: Drag-n-drop sorting for Repeaters [#7617](https://github.com/bolt/bolt/pull/7617), [#7630](https://github.com/bolt/bolt/pull/7630), [#7648](https://github.com/bolt/bolt/pull/7648)
 - Added: Add a Taxonomy repo and allow query content by taxonomy types [#7522](https://github.com/bolt/bolt/pull/7522)
 - Added: Add class support to new storage and where applicable disable all legacy polyfills [#7571](https://github.com/bolt/bolt/pull/7571)
 - Added: Add confirmation when deleting repeater / block set [#7627](https://github.com/bolt/bolt/pull/7627)
 - Added: Add upload autoconfirm option [#7511](https://github.com/bolt/bolt/pull/7511)
 - Added: Adding status to the listing page filter [#7577](https://github.com/bolt/bolt/pull/7577)
 - Added: Enable the query listener and create pager instances for limited queries [#7526](https://github.com/bolt/bolt/pull/7526)
 - Added: For BC content methods - Add default parameter values [#7555](https://github.com/bolt/bolt/pull/7555)
 - Added: More improvements to New Storage Layer Compatibility [#7291](https://github.com/bolt/bolt/pull/7291), [#7654](https://github.com/bolt/bolt/pull/7654), [#7657](https://github.com/bolt/bolt/pull/7657)
 - Added: Port across relations get from old storage [#7527](https://github.com/bolt/bolt/pull/7527)
 - Added: Use senderMail in outgoing mail [#7675](https://github.com/bolt/bolt/pull/7675)
 - Change: add 'heading' to the list of title-like field names. [#7647](https://github.com/bolt/bolt/pull/7647)
 - Change: Use `nesbot/carbon` [#7633](https://github.com/bolt/bolt/pull/7633)
 - Change: Use stable Passwordlib [#7632](https://github.com/bolt/bolt/pull/7632)
 - Fixed: Add getUri for new storage [#7668](https://github.com/bolt/bolt/pull/7668)
 - Fixed: Allow Methods To work with array or ContentType [#7599](https://github.com/bolt/bolt/pull/7599)
 - Fixed: directives should always be cleared before re-parsing [#7681](https://github.com/bolt/bolt/pull/7681)
 - Fixed: Don't use `getContent` on non-existing legacy items. [#7584](https://github.com/bolt/bolt/pull/7584)
 - Fixed: Fix 'generate Lorem Ipsum' dummy content [#7605](https://github.com/bolt/bolt/pull/7605)
 - Fixed: Fix error when searching for empty string [#7625](https://github.com/bolt/bolt/pull/7625)
 - Fixed: Fix excerpts in new Storage [#7604](https://github.com/bolt/bolt/pull/7604),  [#7624](https://github.com/bolt/bolt/pull/7624)
 - Fixed: Fix issue with html fields [#7619](https://github.com/bolt/bolt/pull/7619)
 - Fixed: Fix New Content BC break (content->values) [#7671](https://github.com/bolt/bolt/pull/7671)
 - Fixed: Fix preview [#7660](https://github.com/bolt/bolt/pull/7660),  [#7663](https://github.com/bolt/bolt/pull/7663)
 - Fixed: Fix search results (Frontend, Omnisearch, Async) [#7670](https://github.com/bolt/bolt/pull/7670)
 - Fixed: Fix that allows default sorting to work when using singular slug [#7525](https://github.com/bolt/bolt/pull/7525)
 - Fixed: Handle ArrayAccess & array the same as 'taxonomy' key might not exist in either [#7586](https://github.com/bolt/bolt/pull/7586)
 - Fixed: Improving relation get set interface [#7506](https://github.com/bolt/bolt/pull/7506)
 - Fixed: Make `getListingOrder()` behave correctly on new Storage Layer. [#7585](https://github.com/bolt/bolt/pull/7585)
 - Fixed: Minor Query Handler tweaks [#7524](https://github.com/bolt/bolt/pull/7524)
 - Fixed: Two minor query parser bugs [#7523](https://github.com/bolt/bolt/pull/7523)
 - Fixed:Fix for default ordering (plus code quality updates) [#7564](https://github.com/bolt/bolt/pull/7564)
 - Updated: Update `cocur/slugify` to latest `^3.1.0` [#7600](https://github.com/bolt/bolt/pull/7600)
 - Updated: Update Yarn dependencies, CKeditor and Codemirror [#7601](https://github.com/bolt/bolt/pull/7601)


Bolt 3.5.7
----------

Released: 2018-08-15. Notable changes

 - Added: Ability to get 'news' through a proxy without authentication. [#7593](https://github.com/bolt/bolt/pull/7593)
 - Changed: Splitting up the "New Issue" template. [#7591](https://github.com/bolt/bolt/pull/7591)
 - Fixed: Cleanup sessions list on "Users" screen. Show 'displayname' consistently. [#7583](https://github.com/bolt/bolt/pull/7583)
 - Fixed: Ensure Firefox updates the CSRF value, even on a "soft refresh". [#7588](https://github.com/bolt/bolt/pull/7588)
 - Fixed: Ensure we have a CSRF login on session start and resume. [#7603](https://github.com/bolt/bolt/pull/7603)
 - Fixed: Finetuning the alt-texts in menus.
 - Updated: Update and standardise some translations. [#7580](https://github.com/bolt/bolt/pull/7580)

Bolt 3.5.6
----------

Released: 2018-08-02. Notable changes

 - Fixed: Fix for undefined property in `FieldCollection::$collection`. [#7566](https://github.com/bolt/bolt/pull/7566)
 - Fixed: Twig exception request santitisation handling. [#7569](https://github.com/bolt/bolt/pull/7569)
 - Fixed: Workaround around DBAL Sqlite schema diff bug. [#7570](https://github.com/bolt/bolt/pull/7570)
 - Security: [packaged version] Updated Symfony components updated to [2.8.44](http://symfony.com/blog/symfony-2-8-44-released).
 - Tests: Set `database:repair` command call to non-interactive. [#7565](https://github.com/bolt/bolt/pull/7565)
 - Updated: Base 2018 updated to [2.0.8](https://github.com/bolt/themes/releases/tag/v2.0.8).

Bolt 3.5.5
----------

Released: 2018-07-14. Notable changes

 - Changed: Require `bolt/package-wrapper: ^4.0 || ^5.0` to handle `doctrine/common` split. [#7562](https://github.com/bolt/bolt/pull/7562)
 - Changed: Increasing input width in filter inputs. [#7544](https://github.com/bolt/bolt/pull/7544)  [#7545](https://github.com/bolt/bolt/pull/7545)
 - Fixed: Incorrect replace in excerpt [#7541](https://github.com/bolt/bolt/pull/7541)
 - Updated: Typo in German language file. [#7557](https://github.com/bolt/bolt/pull/7557)

Bolt 3.5.4
----------

Released: 2018-06-26. Notable changes:

 - Changed: Show a message when attempting to save while CSRF token has expired. [#7529](https://github.com/bolt/bolt/pull/7529)
 - Fixed: Allow custom slug field label. [#7502](https://github.com/bolt/bolt/pull/7502)
 - Fixed: Field with same name as other one inside repeater overwrites config of the first one. Ensure select choices are built and accessed via multi-level field names. [#7496](https://github.com/bolt/bolt/pull/7496) [#7505](https://github.com/bolt/bolt/pull/7505)
 - Fixed: Filter in Backend is lost when clicking first item in pager. [#7536](https://github.com/bolt/bolt/pull/7536)
 - Fixed: Handle fatal error when Block Groups and Sub-Fields have the same name. [#7504](https://github.com/bolt/bolt/pull/7504)
 - Fixed: Named repeaters are not saved even if empty. Add a placeholder form input to always trigger saving of Block Fields [#7503](https://github.com/bolt/bolt/pull/7503)
 - Fixed: Set `preserve_keys` in `array_slice` to keep numeric array key association [#7531](https://github.com/bolt/bolt/pull/7531)
 - Updated: Translating `messages.ru.yml`. [#7533](https://github.com/bolt/bolt/pull/7533)

Bolt 3.5.3
----------

Released: 2018-05-25. Notable changes:

 - Fixed: Fix setting listing order on frontend [#7495](https://github.com/bolt/bolt/pull/7495)
 - Fixed: PHP 7.2 'Must be array or Countable' exception, by removing dead code. [#7494](https://github.com/bolt/bolt/pull/7494)
 - Security: [packaged `.tgz` and `.zip` versions only] Symfony components updated to [Symfony 2.8.41](http://symfony.com/blog/symfony-2-8-41-released).

Bolt 3.5.2
----------

Released: 2018-05-22. Notable changes:

 - Fixed: Don't break if UserId is `null` [#7490](https://github.com/bolt/bolt/pull/7490)
 - Fixed: Missing quotes in MySQL queries. Pass taxonomy field through a platform id quote [#7485](https://github.com/bolt/bolt/pull/7485)
 - Fixed: Recent Records in sidebar menu in backend empty for users other than root. [#7487](https://github.com/bolt/bolt/pull/7487)
 - Fixed: The dreaded "Permission 'everyone' is not granted to any roles." has been fixed. [#7488](https://github.com/bolt/bolt/pull/7488)
 - Updated: Portuguese, Polish, Dutch translation files. [#7479](https://github.com/bolt/bolt/pull/7479), [#7480](https://github.com/bolt/bolt/pull/7480), [#7476](https://github.com/bolt/bolt/pull/7476)

Bolt 3.5.1
----------

Released: 2018-05-10. Notable changes:

 - Fixed: Missing 'complexify' and 'jquery.tagcloud' [#7473](https://github.com/bolt/bolt/pull/7473)
 - Fixed: Renaming field to "Embed". [#7463](https://github.com/bolt/bolt/pull/7463)
 - Updated: Add more translations for `pt_BR`, Brasilian Portuguese. [#7446](https://github.com/bolt/bolt/pull/7446)
 - Updated: Reformat `XLF` & `info.yml` files consistently [#7471](https://github.com/bolt/bolt/pull/7471)
 - Updated: Update language strings from PHP & Twig [#7470](https://github.com/bolt/bolt/pull/7470)
 - Updated: Change nl_NL info popups to use informal 'je' instead of formal 'u' [7476](https://github.com/bolt/bolt/pull/7476)

Bolt 3.5.0
----------

Released: 2018-04-18. Notable changes:

 - Added: Add new 'Embed' field type. [#7278](https://github.com/bolt/bolt/pull/7278)
 - Added: New Base-2018 theme. [#14](https://github.com/bolt/themes/pull/14)
 - Added: Add user accounts to `nut import`/`nut export`. [#7306](https://github.com/bolt/bolt/pull/7306)
 - Added: Add a Homepage singleton to default contenttypes, remove old images. [#7369](https://github.com/bolt/bolt/pull/7369)
 - Added: Add feature to fetch placeholder images from remote URLs. [#7368](https://github.com/bolt/bolt/pull/7368)
 - Added: New Feature: Collapsible Blocks and Repeaters [#7443](https://github.com/bolt/bolt/pull/7443)
 - Added: Possibility to add submenu items to custom menus in backend. [#7252](https://github.com/bolt/bolt/pull/7252)
 - Changed: Rename use of 'oembed' to 'embed'. [#7327](https://github.com/bolt/bolt/pull/7327)
 - Fixed: DBAL 2.7.1 fixes, adjusted comparison for version. [#7436](https://github.com/bolt/bolt/pull/7436)
 - Fixed: Update `ctrl+s`, `meta+s` keybindings for newer version of plugin. [#7364](https://github.com/bolt/bolt/pull/7364)
 - Fixed: Use `path` instead of `url` for the oembed endpoint. [#7300](https://github.com/bolt/bolt/pull/7300)
 - Improvement: Allow a viewless (singleton) contenttype for `homepage: …` setting. [#7372](https://github.com/bolt/bolt/pull/7372)
 - Improvement: Better handling of Marketplace connections. [#7380](https://github.com/bolt/bolt/pull/7380)
 - Improvement: Enabled JSON manifest asset version strategy. [#7305](https://github.com/bolt/bolt/pull/7305)
 - Improvement: Improved filename sanitizer for uploaded files. [#7217](https://github.com/bolt/bolt/pull/7217)
 - Improvement: Make `notfound` setting more fault-tolerant. [#7376](https://github.com/bolt/bolt/pull/7376)
 - Improvement: Set right order of grouped menu entries. [#7219](https://github.com/bolt/bolt/pull/7219)
 - Updated: Update "hardcoded" JS libraries like jQuery UI, CKEditor and more. [#7283](https://github.com/bolt/bolt/pull/7283)
 - Updated: Update `base-2016` and `skeleton` themes for 2018. [#13](https://github.com/bolt/themes/pull/13)
 - Updated: Updating CKeditor to 4.9.1, codemirror to 5.36.0. [#7433](https://github.com/bolt/bolt/pull/7433)

Bolt 3.4.11
-----------

Released: 2018-05-10. Notable changes:

 - Change: Downgrading Select2 to 4.0.0 for IE compatibility. [#7434](https://github.com/bolt/bolt/pull/7434)
 - Change: Update LICENSE to say 2018. [#7388](https://github.com/bolt/bolt/pull/7388)
 - Change: Update polish translations [#7431](https://github.com/bolt/bolt/pull/7431)
 - Fixed: [Security] `preg_quote` search words. [#7464](https://github.com/bolt/bolt/pull/7464)
 - Fixed: [Security] Don't change route from 'preview' to 'contentlink', because it breaks disabling of XSS protection. [#7458](https://github.com/bolt/bolt/pull/7458)
 - Fixed: [Security] Only allow POST on `/preview`, because there's no need to GET it.  [#7457](https://github.com/bolt/bolt/pull/7457)
 - Fixed: Add an 'if' statement for missing Block field [#7449](https://github.com/bolt/bolt/pull/7449)
 - Fixed: Change imagelist upload location from abolute to relative [#7397](https://github.com/bolt/bolt/pull/7397)
 - Fixed: Change slug to name for display in "Delete"-button [#7418](https://github.com/bolt/bolt/pull/7418)
 - Fixed: Don't Run a group sort if user has ordered via the UI [#7404](https://github.com/bolt/bolt/pull/7404)
 - Fixed: Ensure array parameters are set before accessing in `getContentByTaxonomy` [#7394](https://github.com/bolt/bolt/pull/7394)
 - Fixed: Ensure search result array is valid [#7386](https://github.com/bolt/bolt/pull/7386)
 - Fixed: Fix check for numeric slug [#7455](https://github.com/bolt/bolt/pull/7455)
 - Fixed: If stack is disabled, don't show it in recordbrowser popup [#7454](https://github.com/bolt/bolt/pull/7454)
 - Fixed: Make aliased thumbnail generation consistent. [#7428](https://github.com/bolt/bolt/pull/7428)
 - Fixed: Only filter null and empty strings, leave zeros untouched. Fixes Incorrect saving of select field. [#7412](https://github.com/bolt/bolt/pull/7412)
 - Fixed: Show correct order in overview if ContentType has multiple taxonomies. [#7400](https://github.com/bolt/bolt/pull/7400)
 - Fixed: Title for submenu items - to avoid truncation [#7407](https://github.com/bolt/bolt/pull/7407)
 - Tests: Skip test on Travis [#7379](https://github.com/bolt/bolt/pull/7379)

Bolt 3.4.10
-----------

Released: 2018-03-15. Notable changes:

 - Added: Add unicode word support for searches [#7361](https://github.com/bolt/bolt/pull/7361)
 - Change: Allow a viewless (singleton) contenttype for `homepage: …` setting [#7372](https://github.com/bolt/bolt/pull/7372)
 - Fixed: Add check for existing `grouping` taxonomies [#7363](https://github.com/bolt/bolt/pull/7363)
 - Fixed: Fix display in overview when `has_sortorder: false` [#7371](https://github.com/bolt/bolt/pull/7371)

Bolt 3.4.9
----------

Released: 2018-03-01. Notable changes:

 - Added: Nut command to list all users. [#7348](https://github.com/bolt/bolt/pull/7348)
 - Change: Remove v1 version-lock on `paragonie/random_compat`. [#7359](https://github.com/bolt/bolt/pull/7359)
 - Change: Update exception handling in NotFoundListener. [#7346](https://github.com/bolt/bolt/pull/7346)
 - Fixed: Be consistent with session & IP address handling. Fixes IP address still used with `cookies_use_remoteaddr: false`. [#7356](https://github.com/bolt/bolt/pull/7356) [#7355](https://github.com/bolt/bolt/pull/7355)
 - Fixed: Correct redirect after saving "new" record. [#7349](https://github.com/bolt/bolt/pull/7349)
 - Fixed: Fix to support multiple contenttypes. Select Field - Using multiple ContentTypes doesn't store `contenttype/id`. [#7339](https://github.com/bolt/bolt/pull/7339) [#7352](https://github.com/bolt/bolt/pull/7352)
 - Fixed: If there's no route for 'contentlink', don't show the "save on site" button. [#7354](https://github.com/bolt/bolt/pull/7354)

Bolt 3.4.8
----------

Released: 2018-02-15. Notable changes:

 - Fixed: Add return early for Postgres sequence look up failures. [#7321](https://github.com/bolt/bolt/pull/7321)
 - Fixed: Fix for reset of WHERE conditions in times queries. [#7326](https://github.com/bolt/bolt/pull/7326)
 - Fixed: Remove outdated cookie clearing logic. Fixes redirect-loop on expired sessions. [#7333](https://github.com/bolt/bolt/pull/7333)
 - Fixed: Update link to "View on site" after save. [#7340](https://github.com/bolt/bolt/pull/7340)

Bolt 3.4.7
----------

Released: 2018-01-31. Notable changes:

 - Added: Add missing `@internal` PHPDoc tags. [#7308](https://github.com/bolt/bolt/pull/7308)
 - Added: Add missing repeater search to query parser. [#7311](https://github.com/bolt/bolt/pull/7311)
 - Fixed: Allow grouping for other taxonomies than only the first one that has `behaves_like: grouping`. [#7314](https://github.com/bolt/bolt/pull/7314)
 - Fixed: Fix reference to concrete `CsrfTokenManager` instead of its interface [#7288](https://github.com/bolt/bolt/pull/7288)
 - Fixed: Fix reference to concrete `Slugify` instead of its interface [#7290](https://github.com/bolt/bolt/pull/7290)
 - Fixed: Fix translation parameter name. [#7298](https://github.com/bolt/bolt/pull/7298)
 - Fixed: Quote value to be interpreted as string literal. [#7297](https://github.com/bolt/bolt/pull/7297)
 - Fixed: Update `select2sortable` plugin to sort on the full text value of element. [[#7312](https://github.com/bolt/bolt/pull/7312)](https://github.com/bolt/bolt/pull/7312)
 - Fixed: Use `path` instead of `url` for the Video embed endpoint. [#7299](https://github.com/bolt/bolt/pull/7299)
 - Fixed: User level is changed when admin-level user edits own password. [#7309](https://github.com/bolt/bolt/pull/7309)
 - Security: Update handling of user form data. [#7315](https://github.com/bolt/bolt/pull/7315)

Bolt 3.4.6
----------

Released: 2018-01-05. Notable changes:

 - Change: `BoltRequirements` DI and pass in `PathResolver`. [#7285](https://github.com/bolt/bolt/pull/7285)
 - Change: Change Carbon upstream to `CarbonDate/Carbon`, because original seems abandoned. [#7273](https://github.com/bolt/bolt/pull/7273)
 - Fixed: [UI] Fix flash message margins. [#7282](https://github.com/bolt/bolt/pull/7282)
 - Fixed: Edge-case fix for old, missing relations. [#7268](https://github.com/bolt/bolt/pull/7268)
 - Fixed: Get fields from Block, even if Block isn't last. [#7277](https://github.com/bolt/bolt/pull/7277)
 - Fixed: Improve upon the dreaded `unexpected token <` in Extensions screen. [#7267](https://github.com/bolt/bolt/pull/7267)
 - Fixed: Require password for new users from admin [#7274](https://github.com/bolt/bolt/pull/7274) [#7266](https://github.com/bolt/bolt/pull/7266)
 - Fixed: Update sortable plugin to work with latest version of Select2. [#7272](https://github.com/bolt/bolt/pull/7272)
 - Fixed: Use PathResolver to make path relative in Extend controller [#7269](https://github.com/bolt/bolt/pull/7269)

Bolt 3.4.5
----------

Released: 2017-12-18. Notable changes:

 - Fixed: Check for CKE changes on repeater duplication [#7261](https://github.com/bolt/bolt/pull/7261)
 - Fixed: Proper sortorders for groupings. (Additional fix to [#7257](https://github.com/bolt/bolt/pull/7257)) [#7263](https://github.com/bolt/bolt/pull/7263)
 - CI: [Travis] Adjust Composer cache directories [#7259](https://github.com/bolt/bolt/pull/7259)

Bolt 3.4.4
----------

Released: 2017-12-15. Notable changes:

 - Fixed: If taxonomy has sortorder then use that in the Backend Overview. [#7257](https://github.com/bolt/bolt/pull/7257)
 - Fixed: `ksort` the repeater hydrate to ensure grouping order is maintained. [#7253](https://github.com/bolt/bolt/pull/7253)
 - Tests: Require `symfony/browser-kit` to keep CodeCeption running. [#7254](https://github.com/bolt/bolt/pull/7254)
 - Fixed: Missing flashbags of type 'danger' and 'warning'. Pass in `user` in `flashbag()` macro. [#7249](https://github.com/bolt/bolt/pull/7249)
 - Change: Show a friendly warning if fields with consecutive underscores are found. [#7250](https://github.com/bolt/bolt/pull/7250)
 - Fixed: Add extra check to test block exists and matches. [#7242](https://github.com/bolt/bolt/pull/7242)
 - Fixed: Don't prefill a field with `type: templateselect` [#7244](https://github.com/bolt/bolt/pull/7244)
 - Fixed: Replace `slug` with `singular_name` on "Delete"-button. [#7243](https://github.com/bolt/bolt/pull/7243)
 - Change: Disable check for proper field types in `contenttypes.yml`, b/c load order [#7260](https://github.com/bolt/bolt/pull/7260)


Bolt 3.4.3
----------

Released: 2017-12-06. Notable changes:

 - Fixed: "Show" / "Hide" password toggle on log in screen works again. [#7215](https://github.com/bolt/bolt/pull/7215)
 - Fixed: Allow backend filtering of unsearchable contenttypes [#7239](https://github.com/bolt/bolt/pull/7239)
 - Fixed: ContextErrorException in RelationType.php, on sites with "missing" relationships. [#7218](https://github.com/bolt/bolt/pull/7218)
 - Fixed: Display username from users, instead of from sessions. [#7216](https://github.com/bolt/bolt/pull/7216)
 - Fixed: Don't break the backend if a file with templatefields has gone missing [#7223](https://github.com/bolt/bolt/pull/7223)
 - Fixed: Don't rely on magic methods for a protected property value. 🐨 [#7212](https://github.com/bolt/bolt/pull/7212)
 - Fixed: Fixed: On log in page, prevents 'An invalid form control is not focusable' on hidden form field [#7238](https://github.com/bolt/bolt/pull/7238)
 - Fixed: Only use `news_variable` from `config.yml`, if it's actually present in the news feed. [#7230](https://github.com/bolt/bolt/pull/7230)
 - Fixed: Prevent "Notice: Undefined index" when doing database:import [#7240](https://github.com/bolt/bolt/pull/7240)
 - Fixed: Stop tab jumping when hovering bottom border of tabs. [#7211](https://github.com/bolt/bolt/pull/7211)
 - Security: Moment JS rebuild - Covers the latest [ReDoS problem in Moment.js](https://github.com/moment/moment/issues/4163). [#7233](https://github.com/bolt/bolt/pull/7233)

Bolt 3.4.2
----------

Released: 2017-11-25. Notable changes:

 - Added: "create new" button in content edit screen, if no content is present. [#7202](https://github.com/bolt/bolt/pull/7202)
 - Added: [Meta] We've added Github Stale bot, to help us prune the list of open issues. [#7190](https://github.com/bolt/bolt/pull/7190)
 - Added: Clarify how `allowed_tags` uses implicit settings from `wysiwyg:`. [#7205](https://github.com/bolt/bolt/pull/7205)
 - Changed: Basic accessibility in pagination. [#7191](https://github.com/bolt/bolt/pull/7191)
 - Fixed: Check form has submitted before valid. [#7193](https://github.com/bolt/bolt/pull/7193)
 - Fixed: Cope with more DBAL breakage [#7187](https://github.com/bolt/bolt/pull/7187)
 - Fixed: Switch to search mode if a text filter is passed into the overview filter. [#7203](https://github.com/bolt/bolt/pull/7203)
 - Fixed: Use "first_image" to return the first image, for display in overview listings. [#7197](https://github.com/bolt/bolt/pull/7197)
 - Tests: Use `@legacy` annotation to mark tests as "legacy group" [#7206](https://github.com/bolt/bolt/pull/7206)

Bolt 3.4.1
----------

Released: 2017-11-18. Notable changes:

 - Fixed: Installing Bolt 3.4 from `composer create-project` fails. Remove recursive `Bag` use from `BundleConfigurator` [#7179](https://github.com/bolt/bolt/pull/7179) [#7178](https://github.com/bolt/bolt/pull/7178)
 - Fixed: Inserting images from CK's 'browse on server' dialog [#7180](https://github.com/bolt/bolt/pull/7180)
 - Updated: [Security] Symfony components updated to 2.8.31 [Symfony 2.8.31 released](http://symfony.com/blog/symfony-2-8-31-released)

Bolt 3.4.0
----------

See Release Notes on the website for highlights: https://bolt.cm/item/bolt-3-4-0-released

Released: 2017-11-14. Notable changes:

 - Added: Add environment support to config variable lookups [#6208](https://github.com/bolt/bolt/pull/6208)
 - Added: Add labels to translations for repeaters / new block fields [#6247](https://github.com/bolt/bolt/pull/6247)
 - Added: Allow opt in to new setcontent queries [#6622](https://github.com/bolt/bolt/pull/6622)
 - Added: Base entity `get()`/`set()` [#6663](https://github.com/bolt/bolt/pull/6663)
 - Added: File manager improvements [#6898](https://github.com/bolt/bolt/pull/6898) [#6758](https://github.com/bolt/bolt/pull/6758) [#2263](https://github.com/bolt/bolt/pull/2263)
 - Added: Implement the getBlock method on the LazyFieldCollection [#6960](https://github.com/bolt/bolt/pull/6960)
 - Added: Implement use of `bolt/common` [#6892](https://github.com/bolt/bolt/pull/6892)
 - Added: Named Repeater Blocks [#6215](https://github.com/bolt/bolt/pull/6215)
 - Added: New Storage Layer - Add Compatibility features to allow both systems to work concurrently [#6972](https://github.com/bolt/bolt/pull/6972)
 - Added: New Storage Layer - Allow use of short alias for singular_slug [#6969](https://github.com/bolt/bolt/pull/6969)
 - Added: Nut command to optimise the configured database [#6977](https://github.com/bolt/bolt/pull/6977)
 - Added: Nut command to perform Bolt set-up (first time, or post-update) tasks [#6594](https://github.com/bolt/bolt/pull/6594)
 - Added: Nut Simple Deploy [#6901](https://github.com/bolt/bolt/pull/6901)
 - Added: PHP 7.2 support [#6920](https://github.com/bolt/bolt/pull/6920)
 - Change: Adding codemirror features [#6436](https://github.com/bolt/bolt/pull/6436)
 - Change: Allow `group` on named repeater's sets [#7099](https://github.com/bolt/bolt/pull/7099)
 - Change: Allow for consecutive prefills. [#6919](https://github.com/bolt/bolt/pull/6919)
 - Change: Allow opt in to new setcontent queries [#6622](https://github.com/bolt/bolt/pull/6622)
 - Change: Backend listing entities [#6669](https://github.com/bolt/bolt/pull/6669)
 - Change: Composer script logic for default site bundle. [See](https://github.com/bolt/composer-install/pull/28) [#7071](https://github.com/bolt/bolt/pull/7071)
 - Change: Deprecate overly verbose widget queue method names [#7170](https://github.com/bolt/bolt/pull/7170)
 - Change: Enable no, partial, or full Q&A on Nut `user:add` [#6592](https://github.com/bolt/bolt/pull/6592)
 - Change: Handle separate bundles under src/ [#7087](https://github.com/bolt/bolt/pull/7087)
 - Change: Inspection clean ups [#6968](https://github.com/bolt/bolt/pull/6968)
 - Change: Migrate login forms to Symfony Form [#7056](https://github.com/bolt/bolt/pull/7056)
 - Change: Migrate pre-fill service to new storage layer [#6550](https://github.com/bolt/bolt/pull/6550)
 - Change: Modded core's select field to accommodate for multiple contenttypes. [#6270](https://github.com/bolt/bolt/pull/6270) [#6299](https://github.com/bolt/bolt/pull/6299)
 - Change: More Symfony Forms updates [#6766](https://github.com/bolt/bolt/pull/6766)
 - Change: More Twig imports & aliases [#6625](https://github.com/bolt/bolt/pull/6625)
 - Change: Move Nut Import/Export to new Storage Engine and support multiple migrations [#6258](https://github.com/bolt/bolt/pull/6258)
 - Change: Nut styling & integer return values [#6586](https://github.com/bolt/bolt/pull/6586)
 - Change: Phase I of Symfony Forms use for the back-end record editing (Rouge Logic 1) [#6214](https://github.com/bolt/bolt/pull/6214)
 - Change: Pre-fill logic updates [#6659](https://github.com/bolt/bolt/pull/6659)
 - Change: Rebuild translation files [#6219](https://github.com/bolt/bolt/pull/6219)
 - Change: Refactor Import / Export [#7012](https://github.com/bolt/bolt/pull/7012)
 - Change: Refactor of Menus in backend [#6895](https://github.com/bolt/bolt/pull/6895) [#6931](https://github.com/bolt/bolt/pull/6931) [#6933](https://github.com/bolt/bolt/pull/6933)
 - Change: Refresh config checks page [#6775](https://github.com/bolt/bolt/pull/6775)
 - Change: Remove v0 columns … and `NULL` all the thingz [#6670](https://github.com/bolt/bolt/pull/6670)
 - Change: Requirement & recommended setting checks on first user [#6773](https://github.com/bolt/bolt/pull/6773)
 - Change: Session split [#6884](https://github.com/bolt/bolt/pull/6884)
 - Change: Update message & exception handling for `Users::notifyUserSetupEmail` [#6724](https://github.com/bolt/bolt/pull/6624)
 - Change: Update pager template to use blocks [#6381](https://github.com/bolt/bolt/pull/6381)
 - Change: Use class name resolution in Twig tokens tidy & tests [#6478](https://github.com/bolt/bolt/pull/6478)
 - Change: Use class name resolution though code base [#6396](https://github.com/bolt/bolt/pull/6396)
 - Change: Use native JSON handling for Composer CLI [#6948](https://github.com/bolt/bolt/pull/6948)
 - Change: When prefilling, add only one record to singletons. [#7038](https://github.com/bolt/bolt/pull/7038)
 - Changed: Re-introduce `implements SanitiserAwareInterface` for `type: text` fields. [#7169](https://github.com/bolt/bolt/pull/7169)
 - Changed: Tidying up recommendations on `firstuser.twig`. [#7159](https://github.com/bolt/bolt/pull/7159)
 - Changed: Tiny change: Make icons on "Set-up Checks" more consistent with "First user". [#7161](https://github.com/bolt/bolt/pull/7161)
 - Cleanup: CS clean-up [#7143](https://github.com/bolt/bolt/pull/7143)
 - Cleanup: More Twig namespaces [#7134](https://github.com/bolt/bolt/pull/7134)
 - Cleanup: Updating a few dutch labels and a column width for Block type. [#7142](https://github.com/bolt/bolt/pull/7142)
 - Deprecation: Add migration for `Twig_Simple` [#6596](https://github.com/bolt/bolt/pull/6596)
 - Deprecation: Add missing `$form->isSubmitted()` [#6667](https://github.com/bolt/bolt/pull/6667)
 - Deprecation: Use namespaced Twig [#6742](https://github.com/bolt/bolt/pull/6742)
 - Fixed: "Save and return" and "save and new" buttons on edit screens work correctly. [#7153](https://github.com/bolt/bolt/pull/7153)
 - Fixed: `title_format` addition: Only use fields that (still) exist [#7086](https://github.com/bolt/bolt/pull/7086)
 - Fixed: A few minor fileupload tweaks [#7078](https://github.com/bolt/bolt/pull/7078)
 - Fixed: A handful of HTML/CSS tweaks for Blocks <-> Repeaters consistency [#7114](https://github.com/bolt/bolt/pull/7114)
 - Fixed: Add an empty hidden field to ensure the field is saved [#7117](https://github.com/bolt/bolt/pull/7117)
 - Fixed: After an 'action' on a record in overview, the ordering gets lost. Enable new group sorting on the equivalent ajax action. [#7157](https://github.com/bolt/bolt/pull/7157) [#7151](https://github.com/bolt/bolt/pull/7151)
 - Fixed: Allocate the other results to an ungrouped array. Fixes disappearing records when adding "groups" taxonomy. [#7150](https://github.com/bolt/bolt/pull/7150) [#7158](https://github.com/bolt/bolt/pull/7158)
 - Fixed: BC break on username case handling. Add transformer on username field [#7055](https://github.com/bolt/bolt/pull/7055)
 - Fixed: Catch `InvalidFieldNameException` in `revokeSession()` [#6916](https://github.com/bolt/bolt/pull/6916)
 - Fixed: Catch `TableNotFoundException` for new ContentTypes. [#7041](https://github.com/bolt/bolt/pull/7041)
 - Fixed: Change `bolt/requirements` to a stable constraint (^1.0) [#7112](https://github.com/bolt/bolt/pull/7112)
 - Fixed: Change `trimtext` to `excerpt` [#7091](https://github.com/bolt/bolt/pull/7091)
 - Fixed: Contextual menu in listing should show `singular_name` [#7105](https://github.com/bolt/bolt/pull/7105)
 - Fixed: Correct logic for MIME type detection [#7174](https://github.com/bolt/bolt/pull/7174)
 - Fixed: Correct order parameter to use minus sign [#6924](https://github.com/bolt/bolt/pull/6924)
 - Fixed: Don't break the exception page on early exceptions [#7146](https://github.com/bolt/bolt/pull/7146)
 - Fixed: Don't check for changes in dynamically inserted elements. Fixes browser naghing about losing form data when saving page [#6991](https://github.com/bolt/bolt/pull/6991) [#6996](https://github.com/bolt/bolt/pull/6996)
 - Fixed: Don't require double confirmation on deleting folders. [#7081](https://github.com/bolt/bolt/pull/7081)
 - Fixed: Don't show empty "Latest changes panel" if the changelog is not enabled. [#7101](https://github.com/bolt/bolt/pull/7101)
 - Fixed: Edge-case in `title_format` for CT's with no textual fields [#7108](https://github.com/bolt/bolt/pull/7108)
 - Fixed: Ensure hyphens in urls are replaced with underscores before going into the Query Engine [#7140](https://github.com/bolt/bolt/pull/7140)
 - Fixed: Files & directories with certain characters can't be deleted - Use JavaScript context escape for file & directory paths in data-action [#7053](https://github.com/bolt/bolt/pull/7053)
 - Fixed: First item in pager should not be canonical for search (because it loses the search term otherwise) [#7165](https://github.com/bolt/bolt/pull/7165)
 - Fixed: Fix crashes on new ContentTypes [#7028](https://github.com/bolt/bolt/pull/7028)
 - Fixed: Fix grouping support in backend overview. [#7131](https://github.com/bolt/bolt/pull/7131)
 - Fixed: Fix incorrect link to Routing docs. [#7073](https://github.com/bolt/bolt/pull/7073)
 - Fixed: Fix Javascript Error In Block Field Javascript [#6307](https://github.com/bolt/bolt/pull/6307)
 - Fixed: Fix listing issues [#6900](https://github.com/bolt/bolt/pull/6900)
 - Fixed: Fix named repeaters during CLI import and export [#7070](https://github.com/bolt/bolt/pull/7070)
 - Fixed: Fix select values in new named repeater blocks [#7082](https://github.com/bolt/bolt/pull/7082)
 - Fixed: Fix styling for image divs [#6992](https://github.com/bolt/bolt/pull/6992)
 - Fixed: Fix taxonomies on `database:import` CLI script [#7039](https://github.com/bolt/bolt/pull/7039)
 - Fixed: Fix taxonomy grouping queries with commas. [#7130](https://github.com/bolt/bolt/pull/7130)
 - Fixed: Fix thumbnails showing correctly in listing view, if an `imagelist` field is used. [#7044](https://github.com/bolt/bolt/pull/7044)
 - Fixed: Fixes for `title_format` handling [#7067](https://github.com/bolt/bolt/pull/7067)
 - Fixed: Handle deletion of field content after a content record is deleted [#7109](https://github.com/bolt/bolt/pull/7109)
 - Fixed: Handle images that aren't found a bit more gracefully. [#7084](https://github.com/bolt/bolt/pull/7084)
 - Fixed: Handle parent URIs with query parameters [#7115](https://github.com/bolt/bolt/pull/7115)
 - Fixed: Hyphenated ContentTypes & validation checks [#7125](https://github.com/bolt/bolt/pull/7125)
 - Fixed: Improve content types titles in some templates. [#7160](https://github.com/bolt/bolt/pull/7160)
 - Fixed: In route generation, lookup parameters from the route defaults [#7106](https://github.com/bolt/bolt/pull/7106)
 - Fixed: In single fetch mode return `false` instead of empty set [#7110](https://github.com/bolt/bolt/pull/7110)
 - Fixed: Incorrect Regex escaping [#6606](https://github.com/bolt/bolt/pull/6606)
 - Fixed: Invert logic so setcontent legacy is on by default [#6923](https://github.com/bolt/bolt/pull/6923)
 - Fixed: JS Linting Errors [#6547](https://github.com/bolt/bolt/pull/6547)
 - Fixed: Make the `type: blocks` field work with `{{ block() }}`. [#8](https://github.com/bolt/themes/pull/8)
 - Fixed: Menu drop bear. [#7042](https://github.com/bolt/bolt/pull/7042)
 - Fixed: Move build logic out of loop, and account for first ContentType being a singleton in menu. [#7135](https://github.com/bolt/bolt/pull/7135)
 - Fixed: Multiple extends tags are forbidden [#6597](https://github.com/bolt/bolt/pull/6597)
 - Fixed: New storage layer fixed pagination handling [#7014](https://github.com/bolt/bolt/pull/7014)
 - Fixed: On Dashboard listings, use 'key' instead of 'slug' [#7141](https://github.com/bolt/bolt/pull/7141)
 - Fixed: Only pass user's ID into `Users::addRole()` [#7092](https://github.com/bolt/bolt/pull/7092)
 - Fixed: Only set a 'group' when the taxonomy is relevant. [#7155](https://github.com/bolt/bolt/pull/7155)
 - Fixed: Remove `import` for Deprecated in `Arr.php` too [#7097](https://github.com/bolt/bolt/pull/7097)
 - Fixed: Remove import for Bolt\Common\Deprecated [#7094](https://github.com/bolt/bolt/pull/7094)
 - Fixed: Remove permissions in incoming relationships. [#7144](https://github.com/bolt/bolt/pull/7144)
 - Fixed: Reset `ORDER BY` on pager count queries, get "Title Column name" correctly. [#7045](https://github.com/bolt/bolt/pull/7045)
 - Fixed: Restrict resizing of `<textarea>` to vertical only. [#7100](https://github.com/bolt/bolt/pull/7100)
 - Fixed: Save Errors When Deleting Repeaters [#6314](https://github.com/bolt/bolt/pull/6314)
 - Fixed: Singletons in submenus need to skip "overview" too [#7030](https://github.com/bolt/bolt/pull/7030)
 - Fixed: Strict mode fixes for Repeater Blocks [#6593](https://github.com/bolt/bolt/pull/6593)
 - Fixed: Swap `Bag` for `MutableBag` [#6949](https://github.com/bolt/bolt/pull/6949)
 - Fixed: Taxonomy template tweak [#6987](https://github.com/bolt/bolt/pull/6987)
 - Fixed: Update namespace for default bundle :koala: [#7147](https://github.com/bolt/bolt/pull/7147)
 - Fixed: Use 'singular_name' instead of slug in "Edit Content". [#7068](https://github.com/bolt/bolt/pull/7068)
 - Fixed: Use `icon_one` for Singleton ContentTypes, instead of `icon_many` in menu. [#7098](https://github.com/bolt/bolt/pull/7098)
 - Fixed: When prefilling, add only one record to singletons. [#7038](https://github.com/bolt/bolt/pull/7038)
 - Tests: [Tests] Fixes for "not found" assertions [#7049](https://github.com/bolt/bolt/pull/7049)
 - Tests: Coverage for Bolt\Asset [#7172](https://github.com/bolt/bolt/pull/7172)
 - Tests: Deprecation import checks [#6723](https://github.com/bolt/bolt/pull/6723)
 - Tests: Risky Business [#6939](https://github.com/bolt/bolt/pull/6939)
 - Tests: Setcontent [#6872](https://github.com/bolt/bolt/pull/6872)
 - Travis: Drop HHVM and add PHP nightly builds [#6921](https://github.com/bolt/bolt/pull/6921)

Bolt 3.3.6
----------

Released 2017-09-29. Notable changes:

 - Security: Added Request listener to prevent access to `_profiler` routes.
 - Tests: Fix breakage due to upstream [#7025](https://github.com/bolt/bolt/pull/7025)
 - Fixed: Added font sizes to headings in WYSIWYG fields. [#7032](https://github.com/bolt/bolt/pull/7032)
 - Fixed: Added Legacy Content Type to Entity [#7033](https://github.com/bolt/bolt/pull/7033)

Bolt 3.3.5
----------

Released 2017-09-25. Notable changes:

 - Fixed: Loose compare `order` parameter as it can't be empty. Fixes breakage in FieldType Select without explicit ordering. [#7021](https://github.com/bolt/bolt/pull/7021) [#7019](https://github.com/bolt/bolt/pull/7019)
 - Tests: Added coverage for `\Bolt\Storage\Query\Directive\OrderDirective`. [#7022](https://github.com/bolt/bolt/pull/7022)

Bolt 3.3.4 - Codename "Happy birthday, Gawain! 🎉 🎊 🍺"
-------------------------------------------------------

Released 2017-09-22. Notable changes:

 - Added: Add `is_numerical` check to Taxonomy selection. [#7010](https://github.com/bolt/bolt/pull/7010)
 - Added: Add `roave/security-advisories` to `require-dev` [#6962](https://github.com/bolt/bolt/pull/6962)
 - Added: Add CII badge [#6963](https://github.com/bolt/bolt/pull/6963)
 - Added: Add inline variant to textarea field [#6983](https://github.com/bolt/bolt/pull/6983)
 - Added: Added CSS to known extensions for files directory [#6964](https://github.com/bolt/bolt/pull/6964)
 - Added: Support DBAL 2.6 [#6940](https://github.com/bolt/bolt/pull/6940)
 - Change: [GitHub] Re-prioritise the issue template slightly [#6967](https://github.com/bolt/bolt/pull/6967)
 - Change: Performance micro tweaks & tidies [#6958](https://github.com/bolt/bolt/pull/6958)
 - Change: Refactor fields trait to extend the type service with new fields [#6954](https://github.com/bolt/bolt/pull/6954)
 - Change: Update `messages.cs_CZ.yml` [#6971](https://github.com/bolt/bolt/pull/6971)
 - Change: Yarn 1.0 — Burn NPM, burn [#6986](https://github.com/bolt/bolt/pull/6986)
 - Fixed: [GitHub] Page-jumps in the contributing guide [#6990](https://github.com/bolt/bolt/pull/6990)
 - Fixed: [Security] Use a prepared statement for `group_concat_max_len` query [#6984](https://github.com/bolt/bolt/pull/6984)
 - Fixed: Add `FileMatcher` to repeater context [#6980](https://github.com/bolt/bolt/pull/6980)
 - Fixed: Additional schema change updates. (DBAL related) [#6966](https://github.com/bolt/bolt/pull/6966)
 - Fixed: Broken links, add links to `changelog.md`. [#6975](https://github.com/bolt/bolt/pull/6975)
 - Fixed: Don't try to use empty array, and only use objects as objects [#6959](https://github.com/bolt/bolt/pull/6959)
 - Fixed: Fix AJAX detection logic in snippet listener. Prevents Meta tags sneaking in on Ajaxy requests. [#6993](https://github.com/bolt/bolt/pull/6993)
 - Fixed: Fix linking for files in CKEditor [#6976](https://github.com/bolt/bolt/pull/6976)
 - Fixed: Fixes regex warnings on Windows when a directory name starts with a 'p' [#6950](https://github.com/bolt/bolt/pull/6950)
 - Fixed: Group Concat settings in `config.yml` [#6978](https://github.com/bolt/bolt/pull/6978)
 - Fixed: Handle mismatch between type & column names for `json_array` to `json` [#6981](https://github.com/bolt/bolt/pull/6981)
 - Fixed: More fixes for DBAL 2.5 => 2.6 update. [#7000](https://github.com/bolt/bolt/pull/7000)
 - Fixed: Pass boolean string as `boolean` to YamlUpdater [#6988](https://github.com/bolt/bolt/pull/6988)
 - Fixed: Pass in the default `orderby` to a filter on the select fieldtype [#7011](https://github.com/bolt/bolt/pull/7011)
 - Fixed: PermissionParser is not handling UTF-8 properly [#7017](https://github.com/bolt/bolt/pull/7017)
 - Fixed: Taxonomies shown correctly as 'selected'. Fixes: Taxonomies get saved as 'slugs', but not checked as such in the editor. [#6995](https://github.com/bolt/bolt/pull/6995)

Bolt 3.3.3
----------

Released 2017-08-27. Notable changes:

 - Change: Allow definition lists, `figure` and `figcaption` to allowed HTML tags [#6910](https://github.com/bolt/bolt/pull/6910) [#6945](https://github.com/bolt/bolt/pull/6945)
 - Change: Catch exceptions better, don't break layout. [#6935](https://github.com/bolt/bolt/pull/6935)
 - Change: Drop use of "sortable" as it is only used in Twig. Note: this change is for consistency and clarity. You can still use sorting on `type: select` fields. [#6943](https://github.com/bolt/bolt/pull/6943)
 - Change: Fixate Doctrine/DBAL on 2.5.x, for Bolt 3.2.x [#6944](https://github.com/bolt/bolt/pull/6944)
 - Change: Remove `.idea`, `.project`, `.sublime` from our git ignores, as they should be in the global ignore instead. [#6918](https://github.com/bolt/bolt/pull/6918)
 - Change: Set maximum PHP version to be < 7.2 [#6912](https://github.com/bolt/bolt/pull/6912)
 - Fixed: Don't show empty 'Taxonomies' tab. [#6932](https://github.com/bolt/bolt/pull/6932)
 - Fixed: Filtered choice now works for other ContentTypes, and not just 'pages'. [#6941](https://github.com/bolt/bolt/pull/6941)
 - Fixed: Fix caching of schema tables and prevent multiple builds per-request [#6942](https://github.com/bolt/bolt/pull/6942)
 - Fixed: Fix Systemlog Filter by Context `extension`. [#6927](https://github.com/bolt/bolt/pull/6927)
 - Fixed: Use followLinks() on Finder. [#6934](https://github.com/bolt/bolt/pull/6934)

Bolt 3.3.2
----------

Released 2017-08-17. Notable changes:

 - Change: Prevent install of PHP 7.2. Support for PHP 7.2 will land in 3.4
 - Change: Better handling & reporting of extension page failures [#6883](https://github.com/bolt/bolt/pull/6883)
 - Fixed: Incorrect formatting of dates & times according to locale [#6907](https://github.com/bolt/bolt/pull/6907)
 - Fixed: Restore support for sorting in ContentType Selects [#6878](https://github.com/bolt/bolt/pull/6878)
 - Fixed: Show singular name instead of ContentType slug [#6876](https://github.com/bolt/bolt/pull/6876)
 - Fixed: Template tab always showing when enabled on only one ContentType [#6869](https://github.com/bolt/bolt/pull/6869)
 - Fixed: Timed records not being published/unpublished [#6906](https://github.com/bolt/bolt/pull/6906)
 - Tests: Check correct sorting gets applied [#6879](https://github.com/bolt/bolt/pull/6879)
 - Update: Re-generate translation files [#6889](https://github.com/bolt/bolt/pull/6889)
 - Update: Update requirement of `bolt/collection` to v1.1 [#6885](https://github.com/bolt/bolt/pull/6885)

Bolt 3.3.1
----------

Released 2017-07-30. Notable changes:

 - Fixed: Fix for select fields in repeaters [#6865](https://github.com/bolt/bolt/pull/6865)

Bolt 3.3.0
----------

Released 2017-07-27. Notable changes:

 - Added: Add a helpful note on the Changelog page, if it is not enabled. [#6653](https://github.com/bolt/bolt/pull/6653)
 - Added: Add extra PHP module requirements to `composer.json` [#6490](https://github.com/bolt/bolt/pull/6490)
 - Added: Add option to only dump the SQL migration statements with the `database:update` command. [#6496](https://github.com/bolt/bolt/pull/6496)
 - Added: Add scoping for new storage `getContent` queries. [#6188](https://github.com/bolt/bolt/pull/6188)
 - Added: Add support for extension definition in bolt.yml file [#6324](https://github.com/bolt/bolt/pull/6324)
 - Added: Added `app/nut` Commands to query views into system operations. [#6531](https://github.com/bolt/bolt/pull/6531)
 - Added: Adding security service provider. [#6231](https://github.com/bolt/bolt/pull/6231)
 - Added: Adding the Pimple dumper as a require-dev & Nut command [#6402](https://github.com/bolt/bolt/pull/6402)
 - Added: Allow an initial number to be passed in the repeater field config [#6459](https://github.com/bolt/bolt/pull/6459)
 - Added: Allow Canonical Url to be overridden [#6500](https://github.com/bolt/bolt/pull/6500)
 - Added: Allow for project-specific Localised translation files. [#6802](https://github.com/bolt/bolt/pull/6802)
 - Added: Allow lookup of non-ContentType repositories by alias [#6647](https://github.com/bolt/bolt/pull/6647)
 - Added: Allow services in `.bolt` bootstrap config. [#6155](https://github.com/bolt/bolt/pull/6155)
 - Added: API compatibility with legacy content objects: Added `.renderedvalue()` to consistently get values. [#6473](https://github.com/bolt/bolt/pull/6473)
 - Added: Collection Library: `Bolt\Collection\Bag` and `Bolt\Collection\ImmutableBag` [#6572](https://github.com/bolt/bolt/pull/6572)
 - Added: Create / Update .codeclimate.yml [#6351](https://github.com/bolt/bolt/pull/6351) [#6345](https://github.com/bolt/bolt/pull/6345)
 - Added: Debug - Keep order number consistent to class when using --sort-listener [#6578](https://github.com/bolt/bolt/pull/6578)
 - Added: Debug - Option to get short listing of event names [#6581](https://github.com/bolt/bolt/pull/6581)
 - Added: Debug - Show source class for closures [#6576](https://github.com/bolt/bolt/pull/6576)
 - Added: Enable breaking events up into single events [#6573](https://github.com/bolt/bolt/pull/6573)
 - Added: Feature list "local extensions" on Extensions screen [#6419](https://github.com/bolt/bolt/pull/6419)
 - Added: Implement missing `hasParameter` method. [#6764](https://github.com/bolt/bolt/pull/6764)
 - Added: Local Extension Replacement. [#6170](https://github.com/bolt/bolt/pull/6170)
 - Added: More descriptive exception when the bundled-extension class does not exist [#6839](https://github.com/bolt/bolt/pull/6839)
 - Added: New "Configuration Notices" extensions helps catching a lot common oversights or misconfigurations. [webpage](https://github.com/bolt/configuration-notices)
 - Added: New Nut debug functionality, dumping routes, events, twig commands and Twig linter. [#6570](https://github.com/bolt/bolt/pull/6570), [#6568](https://github.com/bolt/bolt/pull/6568), [#6543](https://github.com/bolt/bolt/pull/6543)
 - Added: New Skeleton theme, as a bare-bones starter theme. [#6816](https://github.com/bolt/bolt/pull/6816)
 - Added: Overwritable Output for console commands [#6680](https://github.com/bolt/bolt/pull/6680)
 - Added: Path Resolver. [#6166](https://github.com/bolt/bolt/pull/6166)
 - Added: Profiler deprecation notices [#6254](https://github.com/bolt/bolt/pull/6254)
 - Added: PSR-16 (Simple Cache) cache adapter to Filesystem [#55](https://github.com/bolt/filesystem/pull/55)
 - Added: Replacing RandomLib. [#6021](https://github.com/bolt/bolt/pull/6021)
 - Added: ScriptHandler updates [#6681](https://github.com/bolt/bolt/pull/6681)
 - Added: Set [`bolt/collection` Object Orientated implementations of arrays](https://github.com/bolt/collection) to a stable constraint [#6710](https://github.com/bolt/bolt/pull/6710)
 - Change: Add limit to trace, show less data when not logged in. [#6130](https://github.com/bolt/bolt/pull/6130)
 - Change: Add sub and super to default allowed tags [#6362](https://github.com/bolt/bolt/pull/6362)
 - Change: Allow 'extensions_config' as a prefix for FilePermissions checks [#6762](https://github.com/bolt/bolt/pull/6762)
 - Change: Allow `.bolt.php` to return extension objects [#6767](https://github.com/bolt/bolt/pull/6767)
 - Change: Always reset null templatefields to an empty array instead [#6803](https://github.com/bolt/bolt/pull/6803)
 - Change: Boot extension registered service providers - Fixes "Paths to extensions added pages don't work" [#6157](https://github.com/bolt/bolt/pull/6157)
 - Change: Bumped PostgreSQL minimum version to 9.1. (for MySQL we require version 5.5 or up, and almost any SQLite 3 will do).
 - Change: Bunch of maintenance work on `base-2016`: Updating Foundation to 6.3, etc. [#6185](https://github.com/bolt/bolt/pull/6185)
 - Change: Change app path to always be user directory [#6409](https://github.com/bolt/bolt/pull/6409)
 - Change: Change name from Starter to ConfigurationNotices. [#6207](https://github.com/bolt/bolt/pull/6207)
 - Change: Change passwordlib to use `random_bytes` instead of any of its sources. Fixes the `/dev/urandom` issue on sites with `open_basedir` restrictions. [#6756](https://github.com/bolt/bolt/pull/6756)
 - Change: Change Symfony Security to use lazy url generator. [#6428](https://github.com/bolt/bolt/pull/6428)
 - Change: changing 'Extends' menu to 'Extensions'. [#6156](https://github.com/bolt/bolt/pull/6156)
 - Change: Clarify error message for missing assets in extensions. [#6505](https://github.com/bolt/bolt/pull/6505)
 - Change: Clean up .gitignore [#6737](https://github.com/bolt/bolt/pull/6737)
 - Change: Composer installed default themes [#6816](https://github.com/bolt/bolt/pull/6816)
 - Change: Config update: suggest Sparkpost instead of Sendgrid. [#6787](https://github.com/bolt/bolt/pull/6787)
 - Change: Core deprecation notice logging [#6389](https://github.com/bolt/bolt/pull/6389)
 - Change: Deprecate Render [#6310](https://github.com/bolt/bolt/pull/6310)
 - Change: Deprecation and associated clean ups. [#6159](https://github.com/bolt/bolt/pull/6159)
 - Change: DI Error & Exception Handlers [#6341](https://github.com/bolt/bolt/pull/6341)
 - Change: Drop translation of ContentType-specific translation files. [#6802](https://github.com/bolt/bolt/pull/6802)
 - Change: Extension Nut command registration. [#6176](https://github.com/bolt/bolt/pull/6176)
 - Change: Force mbstring functions call on root namespace [#6385](https://github.com/bolt/bolt/pull/6385)
 - Change: Helpful exception when default thumbnail images are not found. [#6522](https://github.com/bolt/bolt/pull/6522)
 - Change: Improve relation and taxonomy normalisation. [#6535](https://github.com/bolt/bolt/pull/6535)
 - Change: Minor visual changes to repeater blocks: Make them stand out a bit less, better alignment on page. [#6720](https://github.com/bolt/bolt/pull/6720)
 - Change: More Symfony deprecations: Replace BoltResponse. [#6058](https://github.com/bolt/bolt/pull/6058)
 - Change: More Twig deprecations [#6812](https://github.com/bolt/bolt/pull/6812)
 - Change: Only set environment for Nut Twig commands on CLI [#6626](https://github.com/bolt/bolt/pull/6626)
 - Change: Performance improvement for content with large lists of incoming relations [#6799](https://github.com/bolt/bolt/pull/6799)
 - Change: Performance improvement in loading of large tag sets in content editor. [#6817](https://github.com/bolt/bolt/pull/6817)
 - Change: Random randomness [#6350](https://github.com/bolt/bolt/pull/6350)
 - Change: Rebuild translation files. [#6217](https://github.com/bolt/bolt/pull/6217)
 - Change: Remove advanced performance settings from default config. [#6028](https://github.com/bolt/bolt/pull/6028)
 - Change: Remove bolt.render stopwatch event [#6304](https://github.com/bolt/bolt/pull/6304)
 - Change: Remove default value for cookies_domain [#6440](https://github.com/bolt/bolt/pull/6440)
 - Change: Remove hard requirement on PHP `mbstring` extension [#6748](https://github.com/bolt/bolt/pull/6748)
 - Change: Remove requirement on (optional) gmp PHP extension [#6553](https://github.com/bolt/bolt/pull/6553)
 - Change: Removed safe mode and magic quotes checks [#6529](https://github.com/bolt/bolt/pull/6529)
 - Change: Renamed "Local Extensions" to "Bundled Extensions" [#6502](https://github.com/bolt/bolt/pull/6502)
 - Change: Replace deprecated `|twig` filters with `template_from_string`. [#6153](https://github.com/bolt/bolt/pull/6153)
 - Change: Replace ResourceManager with PathResolver in the configuration validators [#6429](https://github.com/bolt/bolt/pull/6429)
 - Change: Rework links, badges & grammar in README [#6390](https://github.com/bolt/bolt/pull/6390)
 - Change: Script handler refactor [#6733](https://github.com/bolt/bolt/pull/6733) [#6729](https://github.com/bolt/bolt/pull/6729)
 - Change: TemplateView - Improving Controller Reusability [#6382](https://github.com/bolt/bolt/pull/6382) [#6443](https://github.com/bolt/bolt/pull/6443)
 - Change: Tweak bootstrap for invalid extension classes. [#6769](https://github.com/bolt/bolt/pull/6769)
 - Change: Tweaked Exceptions - Error/Exception Handler Configuration [#6577](https://github.com/bolt/bolt/pull/6577)
 - Change: Twig runtime loader. [#6127](https://github.com/bolt/bolt/pull/6127)
 - Change: Twig Sandbox: This replaces safe_twig with Twig's Sandbox extension. [#6131](https://github.com/bolt/bolt/pull/6131)
 - Change: Update `config.yml` timezone info [#6464](https://github.com/bolt/bolt/pull/6464)
 - Change: Use `blocks` instead of `macro` for `{{ fields() }}` rendering. [#6784](https://github.com/bolt/bolt/pull/6784) [#6778](https://github.com/bolt/bolt/pull/6778) [#6811](https://github.com/bolt/bolt/pull/6811)
 - CodeClimate: Enable grep engine [#6619](https://github.com/bolt/bolt/pull/6619)
 - Deprecation: `LogChangeRepository::countChangeLog()`. [#6656](https://github.com/bolt/bolt/pull/6656)
 - Deprecation: Add deprecation notices for `$app['controller.classmap']`. [#6526](https://github.com/bolt/bolt/pull/6526)
 - Deprecation: Deprecate and replace use of `{{ link() }}` [#6614](https://github.com/bolt/bolt/pull/6614)
 - Deprecation: Deprecated `\Bolt\Exception\FilesystemException` [#6674](https://github.com/bolt/bolt/pull/6674)
 - Deprecation: Deprecated `Swift_MailTransport` [#6666](https://github.com/bolt/bolt/pull/6666)
 - Deprecation: Deprecated ResourceManager Completely [#6439](https://github.com/bolt/bolt/pull/6439), [#6441](https://github.com/bolt/bolt/pull/6441)
 - Deprecation: Import Twig macro template where used [#6652](https://github.com/bolt/bolt/pull/6652)
 - Deprecation: Remove remaining Twig global use [#6685](https://github.com/bolt/bolt/pull/6685) [#6686](https://github.com/bolt/bolt/pull/6686)
 - Deprecation: replaced use of deprecated request object [#6541](https://github.com/bolt/bolt/pull/6541)
 - Deprecation: Switch YamlUpdater to use Bolt filesystem for YAML handling [#6557](https://github.com/bolt/bolt/pull/6557)
 - Deprecation: Use `Request::isMethodSafe()` according to standard [#6701](https://github.com/bolt/bolt/pull/6701)
 - Deprecation: Use Form::isSubmitted() before Form::isValid() [#6556](https://github.com/bolt/bolt/pull/6556)
 - Deprecation: Various deprecation updates [#6432](https://github.com/bolt/bolt/pull/6432), [#6427](https://github.com/bolt/bolt/pull/6427), [#6424](https://github.com/bolt/bolt/pull/6424), [#6422](https://github.com/bolt/bolt/pull/6422), [#6414](https://github.com/bolt/bolt/pull/6414), [#6410](https://github.com/bolt/bolt/pull/6410)
 - Deprecations [#6715](https://github.com/bolt/bolt/pull/6715)
 - Fixed: "Complex search" crash bug [#6757](https://github.com/bolt/bolt/pull/6757)
 - Fixed: "Info (?)" sign after each field title in the content editor [#6850](https://github.com/bolt/bolt/pull/6850)
 - Fixed: "Search with setcontent" when using strict settings. [#6753](https://github.com/bolt/bolt/pull/6753)
 - Fixed: [3.3] Use correct class for deprecation notice [#6444](https://github.com/bolt/bolt/pull/6444)
 - Fixed: Add an interface check around the set method [#6471](https://github.com/bolt/bolt/pull/6471)
 - Fixed: Add Backwards compatibility binding to extend overview page [#6335](https://github.com/bolt/bolt/pull/6335)
 - Fixed: Additional fix for TemplateField select options [#6845](https://github.com/bolt/bolt/pull/6845)
 - Fixed: Base Directory Fixes for Local Extensions [#6327](https://github.com/bolt/bolt/pull/6327)
 - Fixed: BC fix to ensure sandbox is disabled after exception is thrown. [#6134](https://github.com/bolt/bolt/pull/6134)
 - Fixed: Better handling of invalid images in thumbs [#45](https://github.com/bolt/thumbs/pull/45)
 - Fixed: Broken links in "Extra" menu  - Fix menu urls and integrate url generator. [#6139](https://github.com/bolt/bolt/pull/6139) [#6142](https://github.com/bolt/bolt/pull/6142)
 - Fixed: Button to toggle between `config` and `config_local` in 3.3 was missing. Related has items when its count is greater than zero. [#6516](https://github.com/bolt/bolt/pull/6516) [#6513](https://github.com/bolt/bolt/pull/6513)
 - Fixed: Check `file_uploads` ini directive for allowed uploads. [#5973](https://github.com/bolt/bolt/pull/5973)
 - Fixed: Clear 'exception' and 'translation' too, when clearing cache. [#6546](https://github.com/bolt/bolt/pull/6546)
 - Fixed: Counting double in list overview. Only adding record to the list, if it wasn't already selected before. [#6717](https://github.com/bolt/bolt/pull/6717)
 - Fixed: Decouple exceptions from exception handling [#6361](https://github.com/bolt/bolt/pull/6361)
 - Fixed: Disable the class loader if not specifically enabled during boot. [#6534](https://github.com/bolt/bolt/pull/6534)
 - Fixed: Don't boot disabled extensions [#6599](https://github.com/bolt/bolt/pull/6599)
 - Fixed: Don't handle onclick in HTML, but rather in JS, when we're sure dependencies have loaded. [#6184](https://github.com/bolt/bolt/pull/6184)
 - Fixed: Don't hydrate extension fields that haven't implemented it. [#6274](https://github.com/bolt/bolt/pull/6274)
 - Fixed: Don't set homepage template to `null`, if theme.yml is not present. [#6253](https://github.com/bolt/bolt/pull/6253)
 - Fixed: Don't throw exception for broken images, but return "empty" image info [#52](https://github.com/bolt/filesystem/pull/52) & [#54](https://github.com/bolt/filesystem/pull/54)
 - Fixed: Don't throw exception if stack is empty (or updating from an older version). [#6284](https://github.com/bolt/bolt/pull/6284)
 - Fixed: Ensure default for `image.file` in preview. [#6512](https://github.com/bolt/bolt/pull/6512)
 - Fixed: Ensure fields are using new interface before calling methods [#6458](https://github.com/bolt/bolt/pull/6458) (fixes [#6420](https://github.com/bolt/bolt/pull/6420), [#6273](https://github.com/bolt/bolt/pull/6273))
 - Fixed: First check user: Changing Database settings not resetting session [#6481](https://github.com/bolt/bolt/pull/6481) [#6483](https://github.com/bolt/bolt/pull/6483)
 - Fixed: Fix `LowlevelChecks::disableApacheChecks()` not passing value to ResourceManager. [#6528](https://github.com/bolt/bolt/pull/6528)
 - Fixed: Fix `ymlink` twig filter for multiple matches. [#6290](https://github.com/bolt/bolt/pull/6290)
 - Fixed: Fix AND lookups on relationship/join searches [#6401](https://github.com/bolt/bolt/pull/6401)
 - Fixed: Fix BC for old thumbnail config paths in `notfound_image` and `error_image`. [#6728](https://github.com/bolt/bolt/pull/6728)
 - Fixed: Fix booting extension's service providers [#6340](https://github.com/bolt/bolt/pull/6340)
 - Fixed: Fix bug causing missing incoming relations [#6312](https://github.com/bolt/bolt/pull/6312)
 - Fixed: Fix bugs with PathResolver/ResourceManager [#6303](https://github.com/bolt/bolt/pull/6303)
 - Fixed: Fix content changelog field handling [#6565](https://github.com/bolt/bolt/pull/6565)
 - Fixed: Fix determining base path for dev extensions in src folder [#6365](https://github.com/bolt/bolt/pull/6365)
 - Fixed: Fix edge-case `cache:clear` fails. ("cache/production/data" is not writable) [#6747](https://github.com/bolt/bolt/pull/6747)
 - Fixed: Fix extend page pre-release version display. [#6279](https://github.com/bolt/bolt/pull/6279)
 - Fixed: Fix for maintaining key association in `{{ unique() }}` [#6354](https://github.com/bolt/bolt/pull/6354)
 - Fixed: Fix for queries where fields have the same name as contenttype. [#6779](https://github.com/bolt/bolt/pull/6779)
 - Fixed: Fix for setups using different database prefix [#6300](https://github.com/bolt/bolt/pull/6300)
 - Fixed: Fix for sortable selects. [#6638](https://github.com/bolt/bolt/pull/6638)
 - Fixed: Fix frontend taxonomy lookups where key is different to `slug` [#6495](https://github.com/bolt/bolt/pull/6495)
 - Fixed: Fix incorrect join aliased `content_id` to `id`. [#6286](https://github.com/bolt/bolt/pull/6286)
 - Fixed: Fix News widget on dashboard [#6575](https://github.com/bolt/bolt/pull/6575)
 - Fixed: Fix not being able to delete records from dashboard. [#6289](https://github.com/bolt/bolt/pull/6289)
 - Fixed: Fix PathResolver infinite recursion [#6679](https://github.com/bolt/bolt/pull/6679)
 - Fixed: Fix recent Nut I/O backwards compatibility regression, and clean up `$app` use. [#6580](https://github.com/bolt/bolt/pull/6580)
 - Fixed: Fix sandbox not being disabled when exception is thrown. [#6132](https://github.com/bolt/bolt/pull/6132)
 - Fixed: Fix snippets on redirection. [#6288](https://github.com/bolt/bolt/pull/6288)
 - Fixed: Fix TemplateView handling [#6624](https://github.com/bolt/bolt/pull/6624)
 - Fixed: Fix to allow extension interface backwards compatibility [#6418](https://github.com/bolt/bolt/pull/6418)
 - Fixed: Fix to incorrect Extension Field Initialisation. [#6271](https://github.com/bolt/bolt/pull/6271)
 - Fixed: Fix twig `ismobileclient()` to _actually_ not use super global. [#6124](https://github.com/bolt/bolt/pull/6124)
 - Fixed: Fix/clean up `_sub_fields.twig` for `{{ fields() }}` [#6474](https://github.com/bolt/bolt/pull/6474)
 - Fixed: Fixes for `.bolt.yml` / `.bolt.php` custom path handling [#6805](https://github.com/bolt/bolt/pull/6805)
 - Fixed: Fixing the link to docs.bolt.cm/templating/fields-tag [#6611](https://github.com/bolt/bolt/pull/6611)
 - Fixed: Force saving of repeaters, even when empty. [#6518](https://github.com/bolt/bolt/pull/6518)
 - Fixed: Get fields from repeater, even if repeater isn't last. Obsoletes hackish include of HTML field. [#6719](https://github.com/bolt/bolt/pull/6719)
 - Fixed: Handle empty repeaters in Templatefields [#6328](https://github.com/bolt/bolt/pull/6328)
 - Fixed: Import macros where they are actually used, silencing Deprecation notices. [#6532](https://github.com/bolt/bolt/pull/6532)
 - Fixed: Incoming relation was not displayed when relation is empty. [#6117](https://github.com/bolt/bolt/pull/6117)
 - Fixed: Keeping `keys:` intact for select fields. [#6859](https://github.com/bolt/bolt/pull/6859)
 - Fixed: links in 'node_modules' no longer break "list templates" in backend. [#6460](https://github.com/bolt/bolt/pull/6460)
 - Fixed: Maintain order for multiple-select fields. [#6355](https://github.com/bolt/bolt/pull/6355)
 - Fixed: Make content search case insensitive on every platform. [#6110](https://github.com/bolt/bolt/pull/6110) [#6109](https://github.com/bolt/bolt/pull/6109)
 - Fixed: Make repeater fields live editable. [#6173](https://github.com/bolt/bolt/pull/6173)
 - Fixed: Make sure the Bolt nub has no background or border in the debug toolbar. [#6268](https://github.com/bolt/bolt/pull/6268)
 - Fixed: Make thrown SecurityPolicy in Twig-in-content show Exception, instead of outputting HTML on page. [#6504](https://github.com/bolt/bolt/pull/6504)
 - Fixed: Minor updates to base-2016 [#6210](https://github.com/bolt/bolt/pull/6210)
 - Fixed: Missing "Delete" button appears when it should. [#6468](https://github.com/bolt/bolt/pull/6468)
 - Fixed: Move the `getRepeaters` call into the nohydrate block. [#6283](https://github.com/bolt/bolt/pull/6283)
 - Fixed: Only add the label in the content editor, when the label isn't empty [#6837](https://github.com/bolt/bolt/pull/6837)
 - Fixed: Optimise Queries for Repeater Fields. [#6280](https://github.com/bolt/bolt/pull/6280)
 - Fixed: Performance issue fixing in `ContentRelationTrait` [#6108](https://github.com/bolt/bolt/pull/6108)
 - Fixed: prepending theme path instead of appending, fixing template priority order. [#6480](https://github.com/bolt/bolt/pull/6480)
 - Fixed: Prevent filelist exceptions when file not found [#6760](https://github.com/bolt/bolt/pull/6760)
 - Fixed: Register Silex provider first, and don't authenticate base route [#6449](https://github.com/bolt/bolt/pull/6449)
 - Fixed: Remove array_column; doesn't work for PHP 5.5/5.6. [#6278](https://github.com/bolt/bolt/pull/6278) [#6287](https://github.com/bolt/bolt/pull/6287)
 - Fixed: Remove outdated requirement on ext-posix [#6604](https://github.com/bolt/bolt/pull/6604)
 - Fixed: Reset styles for last field in repeater since its in its own panel [#6338](https://github.com/bolt/bolt/pull/6338)
 - Fixed: Resolve template for template fields. [#6237](https://github.com/bolt/bolt/pull/6237)
 - Fixed: Select fields … The sage continues: Make sure `type: select` fields properly maintain order, persist properly and show all items. [#6841](https://github.com/bolt/bolt/pull/6841)
 - Fixed: Session Handlers Phase II [#6484](https://github.com/bolt/bolt/pull/6484)
 - Fixed: Set database character set & collation as configured. For MySQL. [#6533](https://github.com/bolt/bolt/pull/6533)
 - Fixed: Show a message for when using Preview if using `fields()` _and_ Repeaters. [#6633](https://github.com/bolt/bolt/pull/6633)
 - Fixed: Skip symlinks instead of throwing exceptions on local filesystems [#6744](https://github.com/bolt/bolt/pull/6744)
 - Fixed: Slugify slugs on edit save [#6411](https://github.com/bolt/bolt/pull/6411), [#6408](https://github.com/bolt/bolt/pull/6408)
 - Fixed: Stricter parameter handling in `{{ order }}` [#6838](https://github.com/bolt/bolt/pull/6838)
 - Fixed: Sync PathResolver paths to ResourceManager without bootstrap [#6364](https://github.com/bolt/bolt/pull/6364)
 - Fixed: Themes - Some extra safeguards against exceptions in "strict" mode in `_sub_fields.twigs`. [#3](https://github.com/bolt/themes/pull/3)
 - Fixed: This ensures no DI services/parameters are retrieved until boot time.
 - Fixed: Timed publish tweak. [#5680](https://github.com/bolt/bolt/pull/5680)
 - Fixed: Tiny update for backend's frontend assets, removing some unneeded Select2 files. [#6560](https://github.com/bolt/bolt/pull/6560)
 - Fixed: Update `DatabaseExceptionTrait.php` [#6470](https://github.com/bolt/bolt/pull/6470)
 - Fixed: Update legacy `default_status` use in `contenttypes.yml` [#6738](https://github.com/bolt/bolt/pull/6738)
 - Fixed: Updating and tweaking the build process. [#6233](https://github.com/bolt/bolt/pull/6233)
 - Fixed: Use "is defined" to check if a block exists [#6555](https://github.com/bolt/bolt/pull/6555)
 - Fixed: Use ternary logic on block to prevent printing blocks [#6662](https://github.com/bolt/bolt/pull/6662)
 - Remove `createQueryBuilder()` override where not needed [#6650](https://github.com/bolt/bolt/pull/6650)
 - Style: Pre-release code style pass [#6702](https://github.com/bolt/bolt/pull/6702)
 - Tests: [Scrutinizer] Remove unused metrics [#6446](https://github.com/bolt/bolt/pull/6446)
 - Tests: Back-port of deprecations work [#6356](https://github.com/bolt/bolt/pull/6356)
 - Tests: Bootstrap tests & a couple of minor bug fixes [#6844](https://github.com/bolt/bolt/pull/6844)
 - Tests: Codeception Layout & clean ups [#6722](https://github.com/bolt/bolt/pull/6722)
 - Tests: Codeception Tweaks [#6665](https://github.com/bolt/bolt/pull/6665)
 - Tests: Cover installed Composer extensions in PackageManagerTest [#6690](https://github.com/bolt/bolt/pull/6690)
 - Tests: Deprecation - `getMock` to `getMockBuilder`. [#6201](https://github.com/bolt/bolt/pull/6201)
 - Tests: Extension coverage. [#6521](https://github.com/bolt/bolt/pull/6521)
 - Tests: More coverage [#6678](https://github.com/bolt/bolt/pull/6678) [#6675](https://github.com/bolt/bolt/pull/6675)
 - Tests: More Silex 2 deprecation tweaks [#6567](https://github.com/bolt/bolt/pull/6567) [#6564](https://github.com/bolt/bolt/pull/6564)
 - Tests: Need for speed [#6745](https://github.com/bolt/bolt/pull/6745)
 - Tests: Nut coverage [#6588](https://github.com/bolt/bolt/pull/6588)
 - Tests: PHP 7.1 fix. [#6085](https://github.com/bolt/bolt/pull/6085)
 - Tests: Re-enable skipped and incomplete tests [#6393](https://github.com/bolt/bolt/pull/6393)
 - Tests: Remove `phpmd/phpmd` & `squizlabs/php_codesniffer` from `require-dev` [#6692](https://github.com/bolt/bolt/pull/6692)
 - Tests: Restore last incomplete unit test [#6397](https://github.com/bolt/bolt/pull/6397)
 - Tests: Travis cron code coverage [#6358](https://github.com/bolt/bolt/pull/6358)
 - Tests: Update expected element for submit button. [#6202](https://github.com/bolt/bolt/pull/6202)
 - Tests: Update mock builder to be forward-compatible [#6721](https://github.com/bolt/bolt/pull/6721)
 - Travis: Remove remaining legacy sudo use [#6595](https://github.com/bolt/bolt/pull/6595)
 - Updated: Dutch translation file for 3.3 [#6843](https://github.com/bolt/bolt/pull/6843)
 - Updated: Updates to base-2016 [#6281](https://github.com/bolt/bolt/pull/6281)

Bolt 3.3 betas were released on:

 - Beta 1: Released 2017-01-24
 - Beta 2: Released 2017-02-22
 - Beta 3: Released 2017-03-08
 - Beta 4: Released 2017-03-22
 - Beta 5: Released 2017-04-03
 - Beta 6: Released 2017-04-20
 - Beta 7: Released 2017-05-09
 - Beta 8: Released 2017-05-27
 - Beta 9: Released 2017-05-30
 - Beta 10: Released 2017-06-07
 - Beta 11: Released 2017-06-16
 - Beta 12: Released 2017-06-22
 - Beta 13: Released 2017-07-14
 - RC 1: Released 2017-07-18
 - RC 2: Released 2017-07-21
 - RC 3: Released 2017-07-25
 - RC 4: Released 2017-07-26

Bolt 3.2.20
-----------

Released 2017-09-29. Notable changes:

 - Security: Added Request listener to prevent access to `_profiler` routes.

Bolt 3.2.19
-----------

Note: This will most likely be the last release in the 3.2 branch. In case of the discovery of an urgent security issue, we will make a fix for that available for users on 3.2. In other cases we advise to upgrade to Bolt 3.3.

Released 2017-08-27. Notable changes:

 - Change: Allow definition lists, `figure` and `figcaption` to allowed HTML tags [#6910](https://github.com/bolt/bolt/pull/6910) [#6945](https://github.com/bolt/bolt/pull/6945)
 - Change: Drop use of "sortable" as it is only used in Twig. Note: this change is for consistency and clarity. You can still use sorting on `type: select` fields. [#6943](https://github.com/bolt/bolt/pull/6943)
 - Change: Fixate Doctrine/DBAL on 2.5.x, for Bolt 3.2.x [#6944](https://github.com/bolt/bolt/pull/6944)
 - Change: Set maximum PHP version to be `< 7.2`. Bolt 3.4 will fully support PHP 7.2. [#6912](https://github.com/bolt/bolt/pull/6912)
 - Fixed: Filtered choice now works for other ContentTypes, and not just 'pages'. [#6941](https://github.com/bolt/bolt/pull/6941)
 - Fixed: Fix caching of schema tables and prevent multiple builds per-request [#6942](https://github.com/bolt/bolt/pull/6942)
 - Fixed: Show singular name instead of contenttype slug. [#6876](https://github.com/bolt/bolt/pull/6876)
 - Tests: Add test to check correct sorting gets applied [#6879](https://github.com/bolt/bolt/pull/6879)

Bolt 3.2.18
-----------

Released 2017-07-30. Notable changes:

 - Fixed: Fix for select fields in repeaters [#6865](https://github.com/bolt/bolt/pull/6865)
 - Fixed: Keeping `keys:` intact for select fields. [#6859](https://github.com/bolt/bolt/pull/6859)

Bolt 3.2.17
-----------

Released 2017-07-25. Notable changes:

 - Fixed: Additional fix for TemplateField select options [#6845](https://github.com/bolt/bolt/pull/6845)

Bolt 3.2.16
-----------

Released 2017-07-21. Notable changes:

 - Fixed: Select fields … The saga continues: Make sure `type: select` fields properly maintain order, persist properly and show all items. [#6841](https://github.com/bolt/bolt/pull/6841)

Bolt 3.2.15
-----------

Released 2017-07-15. Notable changes:

 - Added: Add pager to base-2016 search template [#6704](https://github.com/bolt/bolt/pull/6704)
 - Added: Implement missing `hasParameter` method. [#6786](https://github.com/bolt/bolt/pull/6786)
 - Change: Clean up .gitignore [#6737](https://github.com/bolt/bolt/pull/6737)
 - Change: Missed 'app.config' to 'config' changes [#6705](https://github.com/bolt/bolt/pull/6705)
 - Change: More refactoring of fields blocks [#6811](https://github.com/bolt/bolt/pull/6811)
 - Change: Switched out Sendgrid for Sparkpost as recommendation for `mailoptions`
 - Change: Translations refresh [#6711](https://github.com/bolt/bolt/pull/6711) [#6714](https://github.com/bolt/bolt/pull/6714) [#6713](https://github.com/bolt/bolt/pull/6713) [#6801](https://github.com/bolt/bolt/pull/6801)
 - Fixed:  Fix edge-case cache:clear fails [#6747](https://github.com/bolt/bolt/pull/6747)
 - Fixed: "Delete record" does not get a system log entry. Audit delete record [#6732](https://github.com/bolt/bolt/pull/6732)
 - Fixed: "Selected items counter" shows wrong number. Only adding record to the list, if it wasn't already selected before. [#6717](https://github.com/bolt/bolt/pull/6717)
 - Fixed: Always reset null templatefields to an empty array instead [#6803](https://github.com/bolt/bolt/pull/6803)
 - Fixed: base-2016 fixes and facepalms [#6778](https://github.com/bolt/bolt/pull/6778)
 - Fixed: Change passwordlib to use random_bytes instead of any of its sources. ("The /dev/urandom issue") [#6756](https://github.com/bolt/bolt/pull/6756)
 - Fixed: Crash using "Setcontent search".  [#6753](https://github.com/bolt/bolt/pull/6753)
 - Fixed: fix complex search crash bug [#6757](https://github.com/bolt/bolt/pull/6757)
 - Fixed: Fix for queries where fields have the same name as contenttype [#6779](https://github.com/bolt/bolt/pull/6779)
 - Fixed: Fix for specific ContentType translations being ignored [#6783](https://github.com/bolt/bolt/pull/6783)
 - Fixed: Get fields from repeater, even if repeater isn't last. Obsoletes hackish include of HTML field. [#6719](https://github.com/bolt/bolt/pull/6719)
 - Fixed: Performance in tags load [#6817](https://github.com/bolt/bolt/pull/6817) [#6818](https://github.com/bolt/bolt/pull/6818)
 - Fixed: permissions.yml link to docs incorrect [#6688](https://github.com/bolt/bolt/pull/6688)
 - Fixed: Quick fix for content with large lists of incoming relations [#6799](https://github.com/bolt/bolt/pull/6799) [#6806](https://github.com/bolt/bolt/pull/6806)
 - Fixed: Translations speaking the wrong layout language [#6792](https://github.com/bolt/bolt/pull/6792)
 - Fixed: Update `default_status` in `contenttypes.yml`  [#6738](https://github.com/bolt/bolt/pull/6738)
 - Tests: Guzzle mocks [#6743](https://github.com/bolt/bolt/pull/6743)

Bolt 3.2.14
-----------

Released 2017-05-22. Notable changes:

 - Added: Add a CONTRIBUTING.md file to the repo root, pointing to docs [#6658](https://github.com/bolt/bolt/pull/6658)
 - Fixed: `slug` used instead of `singular_name` in ContentType edit buttons. [#6682](https://github.com/bolt/bolt/pull/6682)
 - Fixed: Change log fixes [#6683](https://github.com/bolt/bolt/pull/6683)
 - Fixed: Correctly set the ordering for Select dropdown lists, so it's not reversed anymore. [#6695](https://github.com/bolt/bolt/pull/6695)
 - Fixed: Embed tests & corner case minor hotfix [#6673](https://github.com/bolt/bolt/pull/6673)
 - Fixed: Fix for JS error in IE for Filelist fieldtype. [#6697](https://github.com/bolt/bolt/pull/6697)
 - Fixed: Fix slug generation for tags. [#6676](https://github.com/bolt/bolt/pull/6676)
 - Fixed: Tags and Relations are not deleted from the database when a Record is deleted. [#6651](https://github.com/bolt/bolt/pull/6651) [#6657](https://github.com/bolt/bolt/pull/6657)
 - Fixed: Update `messages.es_ES.yml` [#6654](https://github.com/bolt/bolt/pull/6654)
 - Fixed: Weird default selection of `/` for Select fields populated from other contenttypes. [#6696](https://github.com/bolt/bolt/pull/6696)
 - Tests: More coverage [#6675](https://github.com/bolt/bolt/pull/6675)
 - Tests: Tests added for Internal Embed API [#6641](https://github.com/bolt/bolt/pull/6641)

Bolt 3.2.13
-----------

Released 2017-05-09. Notable changes:

 - Change: Switch to (built-in) oEmbed API from external Embed.ly API. [#6636](https://github.com/bolt/bolt/pull/6636)
 - Fixed: Make sure `wysiwyg` fields have the correct label, strip trailing `:` from alerts. [#6630](https://github.com/bolt/bolt/pull/6630)
 - Fixed: No margin or borders for hidden field placeholders. Hidden fields no longer take up space. [#6632](https://github.com/bolt/bolt/pull/6632)
 - Fixed: Set default values for Video field, prevent exception when adding video with height but no width set. [#6634](https://github.com/bolt/bolt/pull/6634)
 - Fixed: Use `singular_slug` instead of `slug` for "New [..]". [#6631](https://github.com/bolt/bolt/pull/6631)

Bolt 3.2.12
-----------

Released 2017-05-05 (Liberation day in NL 🇳🇱🎉) . Notable changes:

 - Added: Add `disabled` attribute support to buic select options [#6590](https://github.com/bolt/bolt/pull/6590)
 - Fixed: Allow overriding `description: ` in `contenttypes.yml`. [#6609](https://github.com/bolt/bolt/pull/6609)
 - Fixed: Fix incompatible extensions showing [#6616](https://github.com/bolt/bolt/pull/6616)
 - Fixed: Fix issue with sortable selects not retaining ordering [#6610](https://github.com/bolt/bolt/pull/6610)
 - Fixed: Grab Dashboard news feed over https. [#6613](https://github.com/bolt/bolt/pull/6613)
 - Fixed: Implement getValues in new Storage Entity. Fixes "New content object has old `getValues()` method". [#6591](https://github.com/bolt/bolt/pull/6591)
 - Fixed: Use getter to get id. Fixes exception when using datetime in repeater fields. Fixes "'datetime' fields in Repeaters throw exception." [#6608](https://github.com/bolt/bolt/pull/6608)

Bolt 3.2.11
-----------

Released 2017-04-19. Notable changes:

 - Change: Report a boolean value in nut as `true` or `false`. [#6558](https://github.com/bolt/bolt/pull/6558)
 - Fixed: Database prefill breaks on PostgreSQL. [#6548](https://github.com/bolt/bolt/pull/6548)
 - Fixed: Fix bug in repeater buttons. [#6525](https://github.com/bolt/bolt/pull/6525)
 - Fixed: Reset `GROUP BY` and `JOIN` parameters for `count()`. [#6551](https://github.com/bolt/bolt/pull/6551)
 - Updated: Updating Symfony components to 2.8.19 and Twig to 1.33.1.

Bolt 3.2.10
-----------

Released 2017-04-03. Notable changes:

 - Added: Docs in code for Version compare `\Bolt\Version::compare()`. [#6520](https://github.com/bolt/bolt/pull/6520)
 - Added: Helpful exception when default thumbnail images are not found [#6522](https://github.com/bolt/bolt/pull/6522)
 - Fixed: Changing Database Settings Not Resetting Session. First check user [#6483](https://github.com/bolt/bolt/pull/6483) [#6481](https://github.com/bolt/bolt/pull/6481)
 - Fixed: Fix frontend taxonomy lookups where `key` is different to `slug` [#6495](https://github.com/bolt/bolt/pull/6495)
 - Fixed: Force saving of repeaters, even when empty [#6518](https://github.com/bolt/bolt/pull/6518)
 - Fixed: Image `alt` and `title` without `file` breaks admin page. Ensure default for image.file in preview [#6512](https://github.com/bolt/bolt/pull/6512) [#6511](https://github.com/bolt/bolt/pull/6511)
 - Fixed: Uploading of images now works, even if current domain name is not equal to the canonical. Use `path()` for ajaxy uploads instead of `url()`. [#6515](https://github.com/bolt/bolt/pull/6515)
 - Updated: Translation updates for `de_DE`. [#6493](https://github.com/bolt/bolt/pull/6493)

Bolt 3.2.9
----------

Released 2017-03-12. Notable changes:

 - Change: Maintenance on Base 2016. Replaced `gulp-minify-css` for `cssnano` in base-2016 theme. [#6469](https://github.com/bolt/bolt/pull/6469) [#6461](https://github.com/bolt/bolt/pull/6461)
 - Change: Update `config.yml` with timezone info. [#6464](https://github.com/bolt/bolt/pull/6464)
 - Fixed: Missing "Delete" button appears when it should. [#6468](https://github.com/bolt/bolt/pull/6468)
 - Fixed: Raw should only be used on user data when intended. [#6463](https://github.com/bolt/bolt/pull/6463)

Bolt 3.2.8
----------

Released 2017-03-03. Notable changes:

 - Change: Force mbstring functions call on root namespace [#6385](https://github.com/bolt/bolt/pull/6385)
 - Change: Mark `Arr::isEmptyArray()` as `@internal` [#6434](https://github.com/bolt/bolt/pull/6434)
 - Change: Remove default value for `cookies_domain` [#6440](https://github.com/bolt/bolt/pull/6440)
 - Fixed: Add "extension" to list of contexts [#6384](https://github.com/bolt/bolt/pull/6384)
 - Fixed: Don't require outgoing relations to show incoming relations [#6433](https://github.com/bolt/bolt/pull/6433)
 - Fixed: Don't save an empty repeater block [#6421](https://github.com/bolt/bolt/pull/6421)
 - Fixed: Ensure `config-cache.json` gets flushed [#6445](https://github.com/bolt/bolt/pull/6445)
 - Fixed: Failsafe for missing filename in `ImageHandler.php` [#6400](https://github.com/bolt/bolt/pull/6400)
 - Fixed: Fix "Wrong Name in Sub-Navigation for Content" [#6417](https://github.com/bolt/bolt/pull/6417)
 - Fixed: Fix renaming files in sub-subfolders [#6435](https://github.com/bolt/bolt/pull/6435)
 - Fixed: Generated `cookies_domain` config is cached [#6431](https://github.com/bolt/bolt/pull/6431)
 - Fixed: Lock select2 at 4.0.0 [#6415](https://github.com/bolt/bolt/pull/6415)
 - Fixed: Skip the field setup if there is no longer a mapping for it [#6380](https://github.com/bolt/bolt/pull/6380)
 - Tests: [Scrutinizer] Remove unused metrics [#6446](https://github.com/bolt/bolt/pull/6446)

Bolt 3.2.7
----------

Released 2017-02-13. Notable changes:

 - Added: Allow "direct" access to fields from FieldCollection in Twig [#6368](https://github.com/bolt/bolt/pull/6368)
 - Added: Placeholder text in textarea [#6285](https://github.com/bolt/bolt/pull/6285)
 - Added: Support SVGs in thumbnails. [#6374](https://github.com/bolt/bolt/pull/6374)
 - Added: warning when relation name clashes with field name [#6373](https://github.com/bolt/bolt/pull/6373)
 - Change: Clarify message in system log, and don't log the message for user root. Resolves "Configuration error: root is not granted to any roles" [#6339](https://github.com/bolt/bolt/pull/6339)
 - Change: Minor tweaks to fix some regressions in the base-2016 theme. [#6297](https://github.com/bolt/bolt/pull/6297)
 - Change: Move the getRepeaters call into the nohydrate block [#6283](https://github.com/bolt/bolt/pull/6283)
 - Change: Refactor lazy logic out of FieldCollection [#6371](https://github.com/bolt/bolt/pull/6371)
 - Documentation: Move "3rd party install" & "test instructions" to docs [#6313](https://github.com/bolt/bolt/pull/6313)
 - Fixed: `mcrypt` deprecation warning [#6375](https://github.com/bolt/bolt/pull/6375)
 - Fixed: `ymlink` twig filter for multiple matches. [#6290](https://github.com/bolt/bolt/pull/6290)
 - Fixed: bug causing missing incoming relations [#6312](https://github.com/bolt/bolt/pull/6312)
 - Fixed: Don't throw exception if stack is empty (or updating from an older version) [#6284](https://github.com/bolt/bolt/pull/6284)
 - Fixed: Handle empty repeaters in Templatefields [#6328](https://github.com/bolt/bolt/pull/6328)
 - Fixed: Taxonomy links broken on entry preview, because `record.taxonomy` data is different. [#6293](https://github.com/bolt/bolt/pull/6293)
 - Fixed: Updating NPM dependencies, make build work again. [#6330](https://github.com/bolt/bolt/pull/6330)
 - Fixes: broken link in contributing doc. [#6367](https://github.com/bolt/bolt/pull/6367)
 - Fixes: Status not passed to `$values` error [#6360](https://github.com/bolt/bolt/pull/6360)

Bolt 3.2.6
----------

Released 2017-01-22. Notable changes:

 - Change: Switch extensions.bolt.cm to market.bolt.cm. [#6234](https://github.com/bolt/bolt/pull/6234)
 - Fixed: `.dev` is an actual TLD, so use `.test` for testing instead. [#6223](https://github.com/bolt/bolt/pull/6223)
 - Fixed: Adding some fallbacks for the "Exception" template.  [#6249](https://github.com/bolt/bolt/pull/6249)
 - Fixed: Backport PathsProxy to 3.2: This fixes urlPrefix (base path) not being used in paths array. [#6235](https://github.com/bolt/bolt/pull/6235)
 - Fixed: Check if record is defined for base-2016 homepage. [#6213](https://github.com/bolt/bolt/pull/6213)
 - Fixed: Fix `Notice: Undefined index: persistant` when using redis for sessions. [#6241](https://github.com/bolt/bolt/pull/6241)
 - Fixed: Fix derp in NotFoundListener. [#6236](https://github.com/bolt/bolt/pull/6236)
 - Fixed: Fix extend page pre-release version display. [#6279](https://github.com/bolt/bolt/pull/6279)
 - Fixed: Fix HTTPS detection for canonical url. [#6209](https://github.com/bolt/bolt/pull/6209)
 - Fixed: Fix label translate in 'Extend' template. [#6226](https://github.com/bolt/bolt/pull/6226)
 - Fixed: Fix magic attribute parsing to avoid removing parts of field. [#6246](https://github.com/bolt/bolt/pull/6246)
 - Fixed: Flash logging configuration validation failures. [#6250](https://github.com/bolt/bolt/pull/6250)
 - Fixed: Handling of fragment in URL generator. [#6227](https://github.com/bolt/bolt/pull/6227)
 - Fixed: Set a default as request will not exists on CLI. [#6232](https://github.com/bolt/bolt/pull/6232)
 - Fixed: Set a default on user display name. [#6266](https://github.com/bolt/bolt/pull/6266)
 - Fixed: Update LICENSE to say 2017. [#6221](https://github.com/bolt/bolt/pull/6221)
 - Fixed: Wrong canonical url with a sub-folder url. Only prepend base path if url is a path. [#6212](https://github.com/bolt/bolt/pull/6212)
 - Update: All Symfony components updated to 2.8.16.
 - Update: Updates to base-2016 for Bolt 3.2 [#6281](https://github.com/bolt/bolt/pull/6281)

Bolt 3.2.5
----------

Released 2016-12-29. Notable changes:

 - Security: Update Swiftmailer to `^5.4.5` per CVE-2016-10074. [#6204](https://github.com/bolt/bolt/pull/6204)
 - Added: Added "Save" button in Live editor. [#6178](https://github.com/bolt/bolt/pull/6178) [#6182](https://github.com/bolt/bolt/pull/6182)
 - Fixed: Frontend no longer starting a session, when it shouldn't. [#6196](https://github.com/bolt/bolt/pull/6196), [#35](https://github.com/bolt/bolt-thumbs/pull/35)
 - Fixed: Don't persist unset fields on update. [#6199](https://github.com/bolt/bolt/pull/6199)
 - Fixed: Fix regex in yamlupdater. [#6197](https://github.com/bolt/bolt/pull/6197)
 - Fixed: Slug generation with multiple fields respect ordering now. [#6191](https://github.com/bolt/bolt/pull/6191)
 - Tests: Fix acceptance test checks for session cookies in front-end. [#6198](https://github.com/bolt/bolt/pull/6198)
 - Tests: Fixed Codeception tests. [#6186](https://github.com/bolt/bolt/pull/6186)

Bolt 3.2.4
----------

Released 2016-12-17. Notable changes:

 - Fixed: Backport fix for assets double applying base path. Fixes Assets not loading correctly for installations in subfolders. [#6179](https://github.com/bolt/bolt/pull/6179)
 - Fixed: Twig_Source path argument [#6171](https://github.com/bolt/bolt/pull/6171)
 - Fixed: Fix `adjustSidebarHeight` so it's not adjusting when it shouldn't. [#6125](https://github.com/bolt/bolt/pull/6125) [#6150](https://github.com/bolt/bolt/pull/6150)
 - Fixed: HTML Overflow in system log fixed. [#6148](https://github.com/bolt/bolt/pull/6148)
 - Fixed: Re-ordering for repeaters when there are more than 10. [#6136](https://github.com/bolt/bolt/pull/6136)
 - Fixed: Twig `{{ current() }}` function [#6135](https://github.com/bolt/bolt/pull/6135)
 - Fixed: Fix JSON parse inconsistency. [#6122](https://github.com/bolt/bolt/pull/6122)
 - Fixed: Fix for [#6115](https://github.com/bolt/bolt/pull/6115): Added a check on $valueSelect to see if it is a string. [#6121](https://github.com/bolt/bolt/pull/6121)
 - Changed: Nicer listing overview for System Log. [#6113](https://github.com/bolt/bolt/pull/6113)
 - Updated: Russian langiage file messages.ru.yml updated. [#6111](https://github.com/bolt/bolt/pull/6111)
 - Fixed: Don't stretch tags to full width [#6112](https://github.com/bolt/bolt/pull/6112)


Bolt 3.2.3
----------

Released 2016-11-29. Notable changes:

 - Added: Backport `canonical` Twig function [#6092](https://github.com/bolt/bolt/pull/6092), [#6091](https://github.com/bolt/bolt/pull/6092)
 - Added: Missing `HttpFoundationExtension` Twig extension. fixes Exception when generating absolute URLs with `{{ asset() }}`. [#6091](https://github.com/bolt/bolt/pull/6091)
 - Added: Yarn, to lessen the pain related sudden onset bitrot in NPM packages. :fire: :boom: [#6038](https://github.com/bolt/bolt/pull/6038)
 - Fixed: "excerpts" exception with no found highlights [#6041](https://github.com/bolt/bolt/pull/6041)
 - Fixed: Additional set of fixes for complex getContent queries [#6050](https://github.com/bolt/bolt/pull/6050), [#6061](https://github.com/bolt/bolt/pull/6061), [#6054](https://github.com/bolt/bolt/pull/6054)
 - Fixed: Empty multiple & sortable select fields to have blank item, by preventing bogus items in array. [#6096](https://github.com/bolt/bolt/pull/6096)
 - Fixed: Fix accessing slug property from `Entity\Content` class [#6067](https://github.com/bolt/bolt/pull/6067)
 - Fixed: Fix metadata lookup in repeaters [#6089](https://github.com/bolt/bolt/pull/6089)
 - Fixed: Fix problems saving fields with underscores and numbers [#6088](https://github.com/bolt/bolt/pull/6088)
 - Fixed: Fixing issues with underscores [#6051](https://github.com/bolt/bolt/pull/6051)
 - Fixed: HTML showing in Latest system activity widget. Output the `log.message` as raw as the logs contain HTML. [#6080](https://github.com/bolt/bolt/pull/6080)
 - Fixed: HTTP cache options: Ensure `http_cache.options` is always passed an array. [#6059](https://github.com/bolt/bolt/pull/6059)
 - Fixed: Relation collection hydration [#6052](https://github.com/bolt/bolt/pull/6052)
 - Fixed: Silence events when saving relations and taxonomies [#6045](https://github.com/bolt/bolt/pull/6045), [#6047](https://github.com/bolt/bolt/pull/6047)
 - Fixed: Sortable select fields now also work inside repeaters. [#6101](https://github.com/bolt/bolt/pull/6101)
 - Fixed: Use consistent semantics for PHP version [#6100](https://github.com/bolt/bolt/pull/6100)
 - Fixed: When saving field values for repeaters, do not trigger additional events. [#6043](https://github.com/bolt/bolt/pull/6043)
 - Maintenance: Clean up formatting of `composer.json` [#6084](https://github.com/bolt/bolt/pull/6084)
 - Maintenance: removing unused npm package `jshint`. [#6098](https://github.com/bolt/bolt/pull/6098)
 - Removed: Remove `console.js`, as every browser > IE 8 has it. No need for this anymore. [#6037](https://github.com/bolt/bolt/pull/6037)
 - Removed: Remove `setParameterWhitelist()` as we never ended up using it [#6070](https://github.com/bolt/bolt/pull/6070)
 - Removed: Remove unused cache handling check from `Frontend::before()` [#6076](https://github.com/bolt/bolt/pull/6076)
 - Updated: Russian translation, 46 more translations added [#6056](https://github.com/bolt/bolt/pull/6056), [#6103](https://github.com/bolt/bolt/pull/6103)
 - Updated: Updating Yarn and dependencies. [#6095](https://github.com/bolt/bolt/pull/6095), [#6099](https://github.com/bolt/bolt/pull/6099)

Bolt 3.2.2
----------

Released 2016-11-12. Notable changes:

 - Added: Enable HTTP Cache to be passed configuration options. [#6023](https://github.com/bolt/bolt/pull/6023)
 - Added: Show "saving" indication on different types of 'save' actions. [#6026](https://github.com/bolt/bolt/pull/6026)
 - Changed: `$context['contenttype']` to be the same as `$content->getContentType()`. [#5988](https://github.com/bolt/bolt/pull/5988)
 - Changed: Remove record taxonomy based routes from example. [#6008](https://github.com/bolt/bolt/pull/6008)
 - Fixed: Add max-width to images in modals. [#6010](https://github.com/bolt/bolt/pull/6010)
 - Fixed: Allow aliased thumbnails for image 'arrays'. [#6027](https://github.com/bolt/bolt/pull/6027)
 - Fixed: Fix `only_aliases` behaviour to work for backend users [#32](https://github.com/bolt/bolt-thumbs/pull/32)
 - Fixed: Fix check for alias if ContentType uses a custom `tablename`. [#6024](https://github.com/bolt/bolt/pull/6024)
 - Fixed: Fix for Notice and subsequent warning related to repeater fields [#6031](https://github.com/bolt/bolt/pull/6031)
 - Fixed: Prevent "404" on missing ContentType for homepage. [#6012](https://github.com/bolt/bolt/pull/6012)
 - Fixed: Prevent encoding of entities "On save" in text fields. [#6018](https://github.com/bolt/bolt/pull/6018)
 - Fixed: Readded missing alias config and default sizes. [#6029](https://github.com/bolt/bolt/pull/6029)
 - Fixed: Use indentation of 4 spaces for consistency in `theme.yml`. [#6033](https://github.com/bolt/bolt/pull/6033)
 - Updated: Fix for Twig `^1.27`, updated to Twig 1.27. [#6004](https://github.com/bolt/bolt/pull/6004)

Bolt 3.2.1
----------

Released 2016-11-08. Notable changes:

 - Updated: updated `bolt/thumbs` to 3.1.0, for thumbnail alias functionality.

Bolt 3.2.0
----------

Released 2016-11-07. Notable changes:

 - Feature: New Exception screen, for easier and better troubleshooting.
 - Feature: New and revamped Error and Exception handling.
 - Feature: Use of underscores and hyphens (`_` and `-`) in contenttype names and fields is possible again. [#5787](https://github.com/bolt/bolt/pull/5787), [#5853](https://github.com/bolt/bolt/pull/5853), [#5939](https://github.com/bolt/bolt/pull/5939), [#5853](https://github.com/bolt/bolt/pull/5853)
 - Feature: Symfony HTTP Cache for request caching (replaces Doctrine file cache use) [#5615](https://github.com/bolt/bolt/pull/5615)
 - Feature: You can use 'aliases' for thumbnails, to give different thumbnail sizes names, as well as prevent unlimited remote thumbnail generation. [#3703](https://github.com/bolt/bolt/pull/3703) [#5768](https://github.com/bolt/bolt/pull/5768)
 - Added: `debug_error_use_profiler` option to replace `Whoops!` with Symfony Profiler page [#5615](https://github.com/bolt/bolt/pull/5615)
 - Added: Add support for separate entity attribute, versus db column names. [#5608](https://github.com/bolt/bolt/pull/5608)
 - Added: Exception controller to display error pages rendered from Twig templates [#5615](https://github.com/bolt/bolt/pull/5615)
 - Added: JSON exception listener [#5863](https://github.com/bolt/bolt/pull/5863)
 - Added: Lazy loading service provider for EntityManager [#5615](https://github.com/bolt/bolt/pull/5615)
 - Added: Lazy loading service provider for SchemaManager [#5615](https://github.com/bolt/bolt/pull/5615)
 - Added: Twig, Doctrine, Bolt & request caching done per-environment, and per service [#5615](https://github.com/bolt/bolt/pull/5615)
 - Changed: `{{ asset() }}` and `{{ path() }}` instead of `{{ paths }}`
 - Changed: Cropping images in imagefield differently, less 'chopped off' parts. [#5847](https://github.com/bolt/bolt/pull/5847)
 - Changed: Display order on exception page, and show page before dumps have loaded. [#5993](https://github.com/bolt/bolt/pull/5993)
 - Changed: Dropped last bit of PHP `<= 5.2` support. We don't have 'legacy.php' anymore, so no use in keeping `__FILE__` around. [#5916](https://github.com/bolt/bolt/pull/5916)
 - Changed: For slugs use 'Generate from:' instead of 'Link to:', as that was found to be confusing. [#5936](https://github.com/bolt/bolt/pull/5936)
 - Changed: Lock Twig at `< 1.26` to preserve BC break on extension driven Twig functions. (see also [This Twig issue](https://github.com/twigphp/Twig/issues/2165). [#5870](https://github.com/bolt/bolt/pull/5870)
 - Changed: Make sure all Symfony packages stay at `2.8.*`. [#5866](https://github.com/bolt/bolt/pull/5866)
 - Changed: Making "delete" button red in confirmation modal for deleting records. [#5942](https://github.com/bolt/bolt/pull/5942)
 - Changed: Rename `BootInitListener` to `ConfigListener`. [#5877](https://github.com/bolt/bolt/pull/5877)
 - Changed: Stricter settings for developers, to get higher visibility for potential issues. [#5840](https://github.com/bolt/bolt/pull/5840)
 - Changed: Switch profiler target option to Symfony's target [#5946](https://github.com/bolt/bolt/pull/5946)
 - Changed: Timed record tweak [#5995](https://github.com/bolt/bolt/pull/5995)
 - Changed: Use `Legacy\Content` subclasses for template fields. [#5881](https://github.com/bolt/bolt/pull/5881)
 - Fixed: Add missing use statement to HiddenType [#5999](https://github.com/bolt/bolt/pull/5999)
 - Fixed Runtime Notice: Only variables should be passed by reference [#5865](https://github.com/bolt/bolt/pull/5865)
 - Fixed: `installAssets` failure during `composer install` [#5930](https://github.com/bolt/bolt/pull/5930)
 - Fixed: Add a `value_boolean` type to the `field_value` table. [#5959](https://github.com/bolt/bolt/pull/5959)
 - Fixed: Cache Twig relative to the defined theme [#6000](https://github.com/bolt/bolt/pull/6000)
 - Fixed: Database is no longer initialised prior to boot
 - Fixed: Edge case bugs with `routing.yml`. [#5923](https://github.com/bolt/bolt/pull/5923)
 - Fixed: Ensure response strings context variable exists and is iterable [#5978](https://github.com/bolt/bolt/pull/5978)
 - Fixed: file autocomplete for sub directories and unquoted regular expressions. [#5904](https://github.com/bolt/bolt/pull/5904)
 - Fixed: Fix empty title exceptions [#5992](https://github.com/bolt/bolt/pull/5992)
 - Fixed: fix for images with `alt` attribute but no `path` - See [#5900](https://github.com/bolt/bolt/issues/5900). [#5919](https://github.com/bolt/bolt/pull/5919)
 - Fixed: Fix Issues Caused By Slug/Key inconsistencies: Multi select field doesn't save values. [#5965](https://github.com/bolt/bolt/pull/5965) [#5969](https://github.com/bolt/bolt/pull/5969)
 - Fixed: fix order of content repeater fields in frontend [#5986](https://github.com/bolt/bolt/pull/5986)
 - Fixed: Fixed `theme.yml` cache refresh [#5889](https://github.com/bolt/bolt/pull/5889)
 - Fixed: Fixed context for `isallowed` permissions check. Fixes the display of the delete button in the aside on editcontent views for 'editor' user roles. [#5890](https://github.com/bolt/bolt/pull/5890)
 - Fixed: Fixed the info Popovers in Repeaters [#5883](https://github.com/bolt/bolt/pull/5883)
 - Fixed: Fixed Twig editing in live editor / CKEditor [#5899](https://github.com/bolt/bolt/pull/5899)
 - Fixed: Handle user entity `pre-save` events very early to mitigate passwords not being hashed, if another event stopped propagation [#5958](https://github.com/bolt/bolt/pull/5958)
 - Fixed: Hidden fields inside a repeater block work correctly now. [#5938](https://github.com/bolt/bolt/pull/5938)
 - Fixed: If `name:` or `singular_name:` isn't set in 'contenttype.yml', generate something semi-logical from the slug. [#5962](https://github.com/bolt/bolt/pull/5962)
 - Fixed: In Metadatadriver set an additional alias for when the slug doesn't match the CT name [#5987](https://github.com/bolt/bolt/pull/5987)
 - Fixed: Less assumptions for configuration [#5856](https://github.com/bolt/bolt/pull/5856)
 - Fixed: Making license link to the MIT License on docs. [#5949](https://github.com/bolt/bolt/pull/5949)
 - Fixed: Memcache connection creation to only pass weight if it is `> 0` [#5861](https://github.com/bolt/bolt/pull/5861)
 - Fixed: Memcache session handler closing connection [#5859](https://github.com/bolt/bolt/pull/5859)
 - Fixed: No `{% extends %}` in exception handler template, because we're not sure we have `{{ app }}` yet.. [#5867](https://github.com/bolt/bolt/pull/5867)
 - Fixed: Optional `order` in relationship shouldn't throw an exception. [#5955](https://github.com/bolt/bolt/pull/5955)
 - Fixed: Order field values by grouping in postgres [#5968](https://github.com/bolt/bolt/pull/5968) [#5976](https://github.com/bolt/bolt/pull/5976)
 - Fixed: Re-add translation cache. [#5922](https://github.com/bolt/bolt/pull/5922)
 - Fixed: Replace paths string concat with `path()` route and `asset()` generation. [#5906](https://github.com/bolt/bolt/pull/5906)
 - Fixed: Set the slug after filling the object, prevent breakage in "prefilling" content without a `title` field. [#5967](https://github.com/bolt/bolt/pull/5967)
 - Fixed: Show custom fields in "Relationships" tab. [#5925](https://github.com/bolt/bolt/pull/5925)
 - Fixed: Standardise the Doctrine Types used by custom fields [#5956](https://github.com/bolt/bolt/pull/5956)
 - Fixed: Switch checkbox field storage type from `string` to `boolean`. [#5858](https://github.com/bolt/bolt/pull/5858)
 - Fixed: System checks now run at start of request cycle
 - Fixed: Use of `tablename:` in contenttypes disallows editing records. [#5960](https://github.com/bolt/bolt/pull/5960)
 - Fixed: Use the contenttype name not the tablename to register an alias for a table name. [#5971](https://github.com/bolt/bolt/pull/5971)
 - Fixed: Using singular_slug as key in contenttypes.yml breaks saving content [#5981](https://github.com/bolt/bolt/pull/5981)
 - Removed: Internal use of `LowlevelChecks` (See `Validator`)
 - Removed: Internal use of `LowlevelException` & `LowlevelDatabaseException` (See `BootException`)
 - Tests: Don't try to set `strict_variables: true` in config as it now mirrors debug setting by default [#5871](https://github.com/bolt/bolt/pull/5871)
 - Update: Updated Symfony components to 2.8.13.
 - Update: Updating Base-2016 dependencies. [#5934](https://github.com/bolt/bolt/pull/5934)

Bolt 3.2 betas and RCs were released on:

 - Beta 1: Released 2016-09-27.
 - Beta 2: Released 2016-10-03.
 - Beta 3: Released 2016-10-05.
 - Beta 4: Released 2016-10-08.
 - Beta 6: Released 2016-10-13.
 - Beta 5: Released 2016-10-17.
 - Beta 7: Released 2016-10-21.
 - Release Candidate 1: 2016-10-24.
 - Release Candidate 2: 2016-10-31.

Bolt 3.1.6
----------

Released 2016-11-06. Notable changes:

 - Changed: Replace paths string concat with `path()` route and `asset()` generation. [#5906](https://github.com/bolt/bolt/pull/5906)
 - Fixed: fix `listing_records` and sort in `theme.yml` [#5980](https://github.com/bolt/bolt/pull/5980)
 - Fixed: Fix file autocomplete for sub directories and unquoted regex. [#5904](https://github.com/bolt/bolt/pull/5904)
 - Fixed: fix for images with `alt` attribute but no `path` - see [#5900](https://github.com/bolt/bolt/pull/5900) [#5919](https://github.com/bolt/bolt/pull/5919)
 - Fixed: Show custom fields in "Relationships" tab [#5925](https://github.com/bolt/bolt/pull/5925)
 - Fixed: Unset unset `has_sortorder` parameter causing exceptions [#5984](https://github.com/bolt/bolt/pull/5984)
 - Reverted: Set Twig syntax as protected source for CKEditor. [#5902](https://github.com/bolt/bolt/pull/5902)
 - Travis: Add PHP 7.1 [#5911](https://github.com/bolt/bolt/pull/5911)
 - Updated: Updating Base-2016 dependencies. [#5934](https://github.com/bolt/bolt/pull/5934)

Bolt 3.1.6
----------

Released 2016-11-06. Notable changes:

 - Changed: Replace paths string concat with `path()` route and `asset()` generation. [#5906](https://github.com/bolt/bolt/pull/5906)
 - Fixed: fix `listing_records` and sort in `theme.yml` [#5980](https://github.com/bolt/bolt/pull/5980)
 - Fixed: Fix file autocomplete for sub directories and unquoted regex. [#5904](https://github.com/bolt/bolt/pull/5904)
 - Fixed: fix for images with `alt` attribute but no `path` - see [#5900](https://github.com/bolt/bolt/pull/5900) [#5919](https://github.com/bolt/bolt/pull/5919)
 - Fixed: Show custom fields in "Relationships" tab [#5925](https://github.com/bolt/bolt/pull/5925)
 - Fixed: Unset unset `has_sortorder` parameter causing exceptions [#5984](https://github.com/bolt/bolt/pull/5984)
 - Reverted: Set Twig syntax as protected source for CKEditor. [#5902](https://github.com/bolt/bolt/pull/5902)
 - Travis: Add PHP 7.1 [#5911](https://github.com/bolt/bolt/pull/5911)
 - Updated: Updating Base-2016 dependencies. [#5934](https://github.com/bolt/bolt/pull/5934)

Bolt 3.1.5
----------

Released 2016-10-12. Notable changes:

 - Change: Better link for generating the Google Maps key. [#5843](https://github.com/bolt/bolt/pull/5843)
 - Change: Lock Twig at `< 1.26` to preserve BC break on extension driven Twig functions [#5870](https://github.com/bolt/bolt/pull/5870)
 - Change: Make sure all Symfony packages stay at `2.8.*`. [#5866](https://github.com/bolt/bolt/pull/5866)
 - Fixed: add `colspan`, `rowspan`, `target` as allowed attributes, and `caption` as allowed tag [#5873](https://github.com/bolt/bolt/pull/5873) [#5827](https://github.com/bolt/bolt/pull/5827)
 - Fixed: Add a 'manage' dropdown for uninstalled extensions. [#5831](https://github.com/bolt/bolt/pull/5831)
 - Fixed: Memcache connection creation to only pass weight if it is > 0 [#5861](https://github.com/bolt/bolt/pull/5861)
 - Fixed: Memcache session handler closing connection [#5859](https://github.com/bolt/bolt/pull/5859)
 - Fixed: Runtime Notice: Only variables should be passed by reference [#5865](https://github.com/bolt/bolt/pull/5865)
 - Fixed: switch checkbox field storage type from `string` to `boolean` [#5858](https://github.com/bolt/bolt/pull/5858)
 - Fixed: Use `Legacy\Content` subclasses for template fields [#5881](https://github.com/bolt/bolt/pull/5881)
 - Fixed: using `Silex\Application` in `bootstrap.php` [#5878](https://github.com/bolt/bolt/pull/5878)
 - Updated: Spanish translations [#5838](https://github.com/bolt/bolt/pull/5838)


Bolt 3.1.4
----------

Released 2016-09-25. Notable changes:

 - Fixed: Fix time picker regexp: slashes are not needed for regex as string. [#5822](https://github.com/bolt/bolt/pull/5822)

Bolt 3.1.3
----------

Released 2016-09-22. Notable changes:

 - Fixed: Add 'alt' and 'title' to `allowed_attributes` for sanitising. [#5782](https://github.com/bolt/bolt/pull/5782)
 - Fixed: Check for existence of `$this->values[$key]` to prevent warnings. [#5802](https://github.com/bolt/bolt/pull/5802)
 - Fixed: Cleaned up double-encoded HTML entities in some descriptions. [#5804](https://github.com/bolt/bolt/pull/5804)
 - Fixed: Don't display 'ungrouped' tab, if no groups are defined. [#5797](https://github.com/bolt/bolt/pull/5797)
 - Fixed: Don't sanitise 'text' and 'textarea' type fields. [#5794](https://github.com/bolt/bolt/pull/5794)
 - Fixed: Fix dashboard news timeout [#5769](https://github.com/bolt/bolt/pull/5769)
 - Fixed: Get the `passwordreset.twig` content instead of the bolt Response [#5780](https://github.com/bolt/bolt/pull/5780)
 - Fixed: In fields, only apply the default to an actual `null` value. [#5805](https://github.com/bolt/bolt/pull/5805)
 - Fixed: Making npm / grunt work (again) [#5791](https://github.com/bolt/bolt/pull/5791), [#5793](https://github.com/bolt/bolt/pull/5793)
 - New: Better feedback when Records can't be saved. [#5801](https://github.com/bolt/bolt/pull/5801)
 - Updated: Bolt MIT Licence was out of date [#5800](https://github.com/bolt/bolt/pull/5800)

Bolt 3.1.2
----------

Released 2016-09-14. Notable changes:

 - Change: Updating default `debug_error_level` from `6135` to `8181`. [#5751](https://github.com/bolt/bolt/pull/5751)
 - Change: We shouldn't assume 'page/1' is the homepage. [#5750](https://github.com/bolt/bolt/pull/5750)
 - Fixed: Add `'name'` to query to fetch popular tags, to prevent SQL error. [#5758](https://github.com/bolt/bolt/pull/5758)
 - Fixed: Files without recognised extensions don't break the file browser. [#5760](https://github.com/bolt/bolt/pull/5760)
 - Fixed: Markdown parsing in frontend works as expected again. (regression in 3.1.0) [#5755](https://github.com/bolt/bolt/pull/5755)
 - Fixed: The 'allowed tags' in the HTML sanitizer should include `iframe` by default. For Youtube/Vimeo embeds, etc. [#5756](https://github.com/bolt/bolt/pull/5756)
 - Fixed: Viewless contenttypes no longer have a 'view on site' button. [#5757](https://github.com/bolt/bolt/pull/5757)
 - Updated: Add FR translations for 'meta' tab [#5744](https://github.com/bolt/bolt/pull/5744)


Bolt 3.1.1
----------

Released 2016-09-07. Notable changes:

 - Updated: Javascript and CSS. [#5737](https://github.com/bolt/bolt/pull/5737)
 - Updated: Symfony and components updated to 2.8.11.
 - Updated: Minor updates to Base-2016 theme. [#5738](https://github.com/bolt/bolt/pull/5738)
 - Fixed: File types arrow's position is messed up. [#5715](https://github.com/bolt/bolt/pull/5715)
 - Fixed: Timed publishing & MetadataDriver fixes. [#5735](https://github.com/bolt/bolt/pull/5735)
 - Fixed: Allow use of string class names in app bootstrap. [#5726](https://github.com/bolt/bolt/pull/5726)
 - Fixed: Use correct link to available locales. [#5722](https://github.com/bolt/bolt/pull/5722)
 - Fixed: Ensure entity is always named as a parameter in storage events. [#5717](https://github.com/bolt/bolt/pull/5717)
 - Fixed: Correctly initialize aliases to support prefixed database tables. [#5716](https://github.com/bolt/bolt/pull/5716)
 - Translations: `contenttype` -> `ContentType` (uppercasing the `C` and the `T` in `contenttype`). [#5712](https://github.com/bolt/bolt/pull/5712)
 - Fixed: `.bolt.*` being allowed to specify application as string. [#5710](https://github.com/bolt/bolt/pull/5710)
 - Fixed: Loading of controllers without before/after middlewares. [#5711](https://github.com/bolt/bolt/pull/5711)

Bolt 3.1.0
----------

Released 2016-08-23. Notable changes:

 - Fix: Allow taxonomies in contentlinks again. [#5698](https://github.com/bolt/bolt/pull/5698)
 - Fix: Case-insensitive username lookup at login. [#5696](https://github.com/bolt/bolt/pull/5696)
 - Fix: Change `slug` to `singular_slug` in Edit Record screen. [#5688](https://github.com/bolt/bolt/pull/5688)
 - Fix: Exclude 'bower_components', 'node_modules' in `.gitignore`. [#5689](https://github.com/bolt/bolt/pull/5689)
 - Fix: Fix for uploading to `themes/` folder in backend. [#5679](https://github.com/bolt/bolt/pull/5679)
 - Fix: Fix slash in taxonomies [#5675](https://github.com/bolt/bolt/pull/5675)
 - Fix: Handle Upload exceptions better [#5683](https://github.com/bolt/bolt/pull/5683)
 - Fix: Improve Hydration Process in Repeaters [#5670](https://github.com/bolt/bolt/pull/5670), [#5684](https://github.com/bolt/bolt/pull/5684)
 - Fix: More session `save_path` bugfixes. [#5691](https://github.com/bolt/bolt/pull/5691)
 - Fix: Show correct error message on incorrect login attempt. [#5697](https://github.com/bolt/bolt/pull/5697)

Bolt 3.1.0 beta 2
-----------------

Released 2016-08-11. Notable changes:

 - Changed: Modify `checkFirstUser` to check for a valid logged in user, to prevent expensive test. [#5649](https://github.com/bolt/bolt/pull/5649)
 - Fixed: Function names can't be used in import prior to PHP 5.6 [#5642](https://github.com/bolt/bolt/pull/5642)
 - Updated: Updating JS and CSS dependencies. [#5653](https://github.com/bolt/bolt/pull/5653)
 - … Plus all changes listed under 3.0.12

Bolt 3.1.0 beta 1
-----------------

Released 2016-08-04. Notable changes:

- Fixed: Installation of specific extension version [#5635](https://github.com/bolt/bolt/pull/5635)
- Fixed: Disabling news feed in backend [#5544](https://github.com/bolt/bolt/pull/5544)
- Fixed: Display of "last seen" user date/time [#5547](https://github.com/bolt/bolt/pull/5547)
- Added: Automatic Translation Inclusion for Extensions [#5292](https://github.com/bolt/bolt/pull/5292)
- Added: Allow extra plugins for Ckeditor [#5342](https://github.com/bolt/bolt/pull/5342)
- Added: Setting Extensions Composer options [#5571](https://github.com/bolt/bolt/pull/5571)
- Added: Nut command to enable, disable, and list details for a user [#5483](https://github.com/bolt/bolt/pull/5483)
- Added: Add `--enable` option to Nut `user:create` [#5483](https://github.com/bolt/bolt/pull/5483)
- Added: Better sanitization of content on save. [#5611](https://github.com/bolt/bolt/pull/5611)

Bolt 3.0.x
-----------

- Fixed: Hydrate repeater in templatefields [#5670](https://github.com/bolt/bolt/pull/5670)
- Fixed: Adding a tag with a slash crashes the content [#5675](https://github.com/bolt/bolt/pull/5675)
- Fixed: Uploading to `themes/` folder in backend. [#5679](https://github.com/bolt/bolt/pull/5679)
- Fixed: Exclude 'bower_components', 'node_modules' in translation search [#5689](https://github.com/bolt/bolt/pull/5689)
- Fixed: Session save_path bugfixes [#5691](https://github.com/bolt/bolt/pull/5691)
- Fixed: Login seems to be case-sensitive [#5696](https://github.com/bolt/bolt/pull/5696)
- Fixed: Show correct error message at login [#5697](https://github.com/bolt/bolt/pull/5697)

Bolt 3.0.12
-----------

Released 2016-08-10. Notable changes:

 - Added: Add scripts to run grunt without global [#5552](https://github.com/bolt/bolt/pull/5552)
 - Added: Add support for missing `skip_uses` parameter in `{{ fields() }}` [#5609](https://github.com/bolt/bolt/pull/5609)
 - Added: Create custom exception for invalid repo, and throw this when accessed [#5568](https://github.com/bolt/bolt/pull/5568)
 - Added: Session can now be configured via config.yml and custom handler/path in ini is not overridden [#5563](https://github.com/bolt/bolt/pull/5563)
 - Change: Remove the Foreign Key Constraint properties from diffs when `supportsForeignKeyConstraints()` is `false` [#5550](https://github.com/bolt/bolt/pull/5550)
 - Change: Set `searchable: true` for Showcases. No reason why they shouldn't be. [#5617](https://github.com/bolt/bolt/pull/5617)
 - Change: Set a flash and redirect to to dashboard if ContentType doesn't exist fetching repository [#5569](https://github.com/bolt/bolt/pull/5569)
 - Change: Use URL generator where appropriate [#5577](https://github.com/bolt/bolt/pull/5577)
 - Docs: PHPDoc fixes [#5645](https://github.com/bolt/bolt/pull/5645), [#5647](https://github.com/bolt/bolt/pull/5647), [#5660](https://github.com/bolt/bolt/pull/5660)
 - Fixed: Add index on slug in taxonomy table [#5597](https://github.com/bolt/bolt/pull/5597)
 - Fixed: Address init failures in repeaters [#5631](https://github.com/bolt/bolt/pull/5631)
 - Fixed: Always return something in getTitleColumnName [#5598](https://github.com/bolt/bolt/pull/5598)
 - Fixed: Be very select about extension autoloader error/exception emitting [#5565](https://github.com/bolt/bolt/pull/5565)
 - Fixed: Bug in Imagefield with attrib would break repeater fields. [#5665](https://github.com/bolt/bolt/pull/5665)
 - Fixed: Bugfix for HTML fields inside repeaters [#5639](https://github.com/bolt/bolt/pull/5639)
 - Fixed: Change link to taxonomies documentation [#5618](https://github.com/bolt/bolt/pull/5618)
 - Fixed: Filesystem session handler's garbage collection [#5633](https://github.com/bolt/bolt/pull/5633)
 - Fixed: Fix attempts to access values via $this->values in storage [#5593](https://github.com/bolt/bolt/pull/5593)
 - Fixed: Fix backend publish process quirks ([#5085](https://github.com/bolt/bolt/pull/5085)) [#5610](https://github.com/bolt/bolt/pull/5610)
 - Fixed: Fix bidirectional relations and relations affecting each other [#5641](https://github.com/bolt/bolt/pull/5641)
 - Fixed: Fix issue with multi-value value selects [#5632](https://github.com/bolt/bolt/pull/5632)
 - Fixed: Fix two issues with deferred widgets. [#5643](https://github.com/bolt/bolt/pull/5643)
 - Fixed: Fixing a link in the base-2016 theme [#5627](https://github.com/bolt/bolt/pull/5627)
 - Fixed: Get correct path to compare in 'current' filter, when Bolt is in a subfolder/ [#5620](https://github.com/bolt/bolt/pull/5620)
 - Fixed: Incoming relations must be checked with both `contenttype` and `id`. [#5637](https://github.com/bolt/bolt/pull/5637)
 - Fixed: Memcached and Redis session handler and realm getting appended multiple times [#5662](https://github.com/bolt/bolt/pull/5662)
 - Fixed: Redis session handler [#5664](https://github.com/bolt/bolt/pull/5664)
 - Fixed: Remove the last references to `listcontent` [#5634](https://github.com/bolt/bolt/pull/5634)
 - Fixed: Replacing deprecated `localdate` with `localedatetime`. [#5621](https://github.com/bolt/bolt/pull/5621)
 - Fixed: Set relations indexes to be multi column indexes [#5602](https://github.com/bolt/bolt/pull/5602)
 - Fixed: Skip schema check only on `_wdt` (profiler) & `dbupdate` routes [#5570](https://github.com/bolt/bolt/pull/5570)
 - Fixed: Tweak changelog notes about session and port redis handler fix [#5664](https://github.com/bolt/bolt/pull/5664)
 - Folder Handling doesn't need parent value concatenated [#5582](https://github.com/bolt/bolt/pull/5582)
 - Updated: GitHub hints in `.github/` [#5661](https://github.com/bolt/bolt/pull/5661)
 - Updated: Update features section in CONTRIBUTING.md [#5536](https://github.com/bolt/bolt/pull/5536)
 - Updated: Updating the version of Jquery that's used with `add_jquery` in the frontend. [#5663](https://github.com/bolt/bolt/pull/5663)

Bolt 3.0.11
-----------

Released 2016-07-19. Notable changes:

 - Update: [SECURITY] Updated bundled [Guzzle to 5.3.1](https://github.com/guzzle/guzzle/releases/tag/6.2.1) that mitigates [Httpoxy](https://httpoxy.org/) (CVE-2016-5385)
 - Update: [SECURITY] Updated [Composer to 1.2.0](https://github.com/composer/composer/releases/tag/1.2.0), which mitigates [Httpoxy](https://httpoxy.org/) (CVE-2016-5385)
 - Added: Add Google Maps API key option [#5505](https://github.com/bolt/bolt/pull/5505)
 - Fixed: Enforce relative schema on Twig `{{ url() }}` calls [#5497](https://github.com/bolt/bolt/pull/5497)
 - Fixed: Ability to disable the news feed [#5544](https://github.com/bolt/bolt/pull/5544)
 - Fixed: Enable use of repeaters inside Templatefields [#5542](https://github.com/bolt/bolt/pull/5542)

Bolt 3.0.10
-----------

Released 2016-07-14. Notable changes:

 - Added: Allow callables to be registered as repository classes [#5523](https://github.com/bolt/bolt/pull/5523)
 - Change: Improve logic of hydration events on create [#5521](https://github.com/bolt/bolt/pull/5521)
 - Change: Refactor the hydration event to use an ArrayObject [#5518](https://github.com/bolt/bolt/pull/5518)
 - Fixed: Cant open readme from extension on WINDOWS [#5501](https://github.com/bolt/bolt/pull/5501)
 - Fixed: Check isallowed on new page buttons [#5529](https://github.com/bolt/bolt/pull/5529)
 - Fixed: Ensure the id is unique in repeater fields [#5526](https://github.com/bolt/bolt/pull/5526)
 - Fixed: Fix comment on default image sizing [#5528](https://github.com/bolt/bolt/pull/5528)
 - Fixed: Tokenise PHP version string to remove `-extra` on Debian [#5524](https://github.com/bolt/bolt/pull/5524)
 - Update: Updating dependencies for base-2016. [#5520](https://github.com/bolt/bolt/pull/5520)

Bolt 3.0.9
----------

Released 2016-07-06. Notable changes:

 - Added: Add option for google maps api key [#5492](https://github.com/bolt/bolt/pull/5492)
 - Added: Allow pre-hydration data to be modified in event [#5510](https://github.com/bolt/bolt/pull/5510)
 - Change: [Travis] Disable Composer install test [#5514](https://github.com/bolt/bolt/pull/5514)
 - Change: [Travis] Drop installation of language packs & Codeception failures [#5498](https://github.com/bolt/bolt/pull/5498)
 - Fixed: "Invalid Version String" on Ext Update Check [#5516](https://github.com/bolt/bolt/pull/5516)
 - Fixed: Contenttype vs. Table name "_" and "-" [#5363](https://github.com/bolt/bolt/pull/5363)
 - Fixed: Initialize `slugFields` variable [#5503](https://github.com/bolt/bolt/pull/5503)
 - Fixed: Lock lstrojny/phpunit-function-mocker to 0.3.0 for PHP 5.5 support [#5493](https://github.com/bolt/bolt/pull/5493)
 - Fixed: Don't hide exceptions when adding user with Nut [#5481](https://github.com/bolt/bolt/pull/5481)

Bolt 3.0.8
----------

Released 2016-06-22. Notable changes:

 - Fix: Postgres Fix: add missing second parameter to `string_agg` call [#5467](https://github.com/bolt/bolt/pull/5467)
 - Fix: Various fixes for z-index positioning of modals. [#5459](https://github.com/bolt/bolt/pull/5459), [#5461](https://github.com/bolt/bolt/pull/5461), [#5475](https://github.com/bolt/bolt/pull/5475)
 - Fix: Hotfix filesystem plugins. [#5450](https://github.com/bolt/bolt/pull/5450)
 - Fix: Some Composer fixes. [#5472](https://github.com/bolt/bolt/pull/5472)

Bolt 3.0.7
----------

Released 2016-06-17. Notable changes:

 - Fixed: Mea culpa! Use `$zindex-modal` for the `.bootbox` z-index. (Prevents Modal dialogs from being not dismissable.) [#5459](https://github.com/bolt/bolt/pull/5459)
 - Fixed: Set correct mount point / namespace in AdapterPlugin before plugin methods executes. [#5449](https://github.com/bolt/bolt/pull/5449) / [#5450](https://github.com/bolt/bolt/pull/5450)

Bolt 3.0.6
----------

Released 2016-06-15. Notable changes:

 - Added: Add an ability to delete a record from the 'mobile' version of the Bolt backend [#5444](https://github.com/bolt/bolt/pull/5444)
 - Change: Move the assignment of COMPOSER_HOME to BaseAction::getComposer() [#5424](https://github.com/bolt/bolt/pull/5424)
 - Fix: "Select all" button was visible when taxonomy `multiple: false` was set for category behaviour [#5443](https://github.com/bolt/bolt/pull/5443)
 - Fix: Be more user friendly with file manager edit failures [#5447](https://github.com/bolt/bolt/pull/5447)
 - Fix: BUG Select all button visible when taxanomy multiple set to false for category behaviour [#5437](https://github.com/bolt/bolt/pull/5437)
 - Fix: Clicking on 'tags with spaces' wouldn't work. [#5431](https://github.com/bolt/bolt/pull/5431)
 - Fix: Extensions: Don't evaluate an empty constraint [#5457](https://github.com/bolt/bolt/pull/5457)
 - Fix: Fix the path for files, when found in Omnisearch. [#5422](https://github.com/bolt/bolt/pull/5422)
 - Fix: Fixed `blur` in 'select all' and 'select none' in taxonomies. [#5452](https://github.com/bolt/bolt/pull/5452)
 - Fix: Fixed invalid ExtensionInterface namespace in Controller Resolver [#5434](https://github.com/bolt/bolt/pull/5434)
 - Fix: Fixing z-index for modals. [#5455](https://github.com/bolt/bolt/pull/5455)
 - Fix: Repeaters: Fix duplicate button functionality in repeater groups [#5442](https://github.com/bolt/bolt/pull/5442)
 - Fix: Repeaters: Fix hyphenated field names for repeating fields [#5436](https://github.com/bolt/bolt/pull/5436)
 - Fix: Theme: fix wrong link to edit template in base-2016 theme [#5445](https://github.com/bolt/bolt/pull/5445)
 - Update: French and Russion translations updated.

Bolt 3.0.5
----------

Released 2016-06-08. Notable changes:

 - Added: Optionally copy in Bolt's .gitignore file on `composer create-project` [#5420](https://github.com/bolt/bolt/pull/5420)
 - Added: Refinements for content fetching [#5401](https://github.com/bolt/bolt/pull/5401)
 - Added: Setting 'provided_link', allowing for more flexibility in "branding" [#5377](https://github.com/bolt/bolt/pull/5377)
 - Changed: Remove version numbers from doc links to be more future-proof [#5416](https://github.com/bolt/bolt/pull/5416)
 - Fixed: Don't trigger an exception on PostgreSQL if no table sequence is defined [#5412](https://github.com/bolt/bolt/pull/5412)
 - Fixed: File asset priority & location [#5415](https://github.com/bolt/bolt/pull/5415)
 - Fixed: Fix for lookup failures on hyphenated data names [#5399](https://github.com/bolt/bolt/pull/5399)
 - Fixed: Fix for remaining doc links pointing to the incorrect version [#5414](https://github.com/bolt/bolt/pull/5414)
 - Fixed: Handle site root directory moves on Sqlite [#5393](https://github.com/bolt/bolt/pull/5393)
 - Fixed: MySQL error in select fields populated from content types [#5407](https://github.com/bolt/bolt/pull/5407)
 - Fixed: Placing the delay parameter for Omnisearch to reduce the amount of cancelled XHRs. [#5408](https://github.com/bolt/bolt/pull/5408)
 - Fixed: Set configured schemes from routing.yml [#5409](https://github.com/bolt/bolt/pull/5409)
 - Fixed: Use the correct version for link to the docs [#5413](https://github.com/bolt/bolt/pull/5413)
 - Travis: Remove dependency on Postfix [#5421](https://github.com/bolt/bolt/pull/5421)
 - Updated: Update messages.en_GB.yml [#5386](https://github.com/bolt/bolt/pull/5386)
 - Updated: Updating NPM dependencies, rebuild CSS and JS [#5410](https://github.com/bolt/bolt/pull/5410)

Bolt 3.0.4
----------

Released 2016-06-01. Notable changes:

 - Deprecation: Replace deprecated trimtext with excerpt. [#5381](https://github.com/bolt/bolt/pull/5381)
 - Fixed: Adding style for widget(holders), pagination and record footers. Also updated `bower`/`npm` dependencies. [#5387](https://github.com/bolt/bolt/pull/5387)
 - Fixed: Cast to string in thumbnail handling, prevent `substr() expects parameter 1 to be string` [#5329](https://github.com/bolt/bolt/pull/5329)
 - Fixed: Missing PHP icon in webdev Toolbar. [#5376](https://github.com/bolt/bolt/pull/5376)
 - Fixed: Tweak search input. No lowercasing search input, handle html and entities better. [#5374](https://github.com/bolt/bolt/pull/5374)
 - Fixed: Update `_taxonomies.twig`, correctly add fields with `group: taxonomy` in contenttypes. [#5369](https://github.com/bolt/bolt/pull/5369)
 - Fixed: Use path that includes subdirectories, to prevent breakage on Extend page. [#5389](https://github.com/bolt/bolt/pull/5389)
 - Typo: Fixed Unfinished sentence in `config.yml` [#5370](https://github.com/bolt/bolt/pull/5370)

Bolt 3.0.3
----------

Released 2016-05-25. Notable changes:

 - Fixed: Stupid translation error in Dutch: The name of this project is Bolt, not "Bout". [#5367](https://github.com/bolt/bolt/pull/5367)
 - Fixed: `bolt_log_system` collecting redundant entries for remote assets. [#5357](https://github.com/bolt/bolt/pull/5357)
 - Fixed: Handle `json_array` for Sqlite separately [#5362](https://github.com/bolt/bolt/pull/5362)

Bolt 3.0.2
----------

Released 2016-05-23. Notable changes:

- Added: Function to allow comparitor to add ignored change when extending Database Schema. [#5360](https://github.com/bolt/bolt/pull/5360)
- Fixed: Add last insert id support for Postgres. [#5355](https://github.com/bolt/bolt/pull/5355)
- Fixed: Bind correct name, fixes 'select from server' in CKeditor. [#5354](https://github.com/bolt/bolt/pull/5354)
- Fixed: Contenttype slugs with underscores now work correctly. [#5299](https://github.com/bolt/bolt/pull/5299)
- Fixed: Delete button in sidebar, when editing a record. [#5353](https://github.com/bolt/bolt/pull/5353)
- Fixed: Don't log external assets in `bolt_log_system`. [#5357](https://github.com/bolt/bolt/pull/5357)
- Fixed: Link to Taxonomy and Routing pages in documentation. [#5328](https://github.com/bolt/bolt/pull/5328), [#5327](https://github.com/bolt/bolt/pull/5327)
- Fixed: Postgres string aggregation to force cast. [#5345](https://github.com/bolt/bolt/pull/5345)
- Fixed: Set Z-index for "full screen" CKeditor. [#5351](https://github.com/bolt/bolt/pull/5351)
- Fixed: Updating `app/src/README.md` to use code highlighting. [#5330](https://github.com/bolt/bolt/pull/5330)

Bolt 3.0.1
----------

Released 2016-05-15. Notable changes:

- Change: Clarify the location of the source files for `.css` and `.js` in the compiled files. [#5321](https://github.com/bolt/bolt/pull/5321)
- Change: Don't emit autoloader warnings from `class_exists()`. [#5319](https://github.com/bolt/bolt/pull/5319)
- Change: Remove `web.config` for IIS from Git repo (should be in docs). [#5304](https://github.com/bolt/bolt/pull/5304)
- Fixed: [theme] Fix view height for header photo in base-2016. [#5314](https://github.com/bolt/bolt/pull/5314)
- Fixed: [Travis] Update hirak/prestissimo constraint [#5303](https://github.com/bolt/bolt/pull/5303)
- Fixed: Better display of exceptions on Extend Page. [#5305](https://github.com/bolt/bolt/pull/5305)
- Fixed: Fix small CSS misalignment in slug input. [#5312](https://github.com/bolt/bolt/pull/5312)
- Fixed: Fixing links in code/comments to the docs site.  [#5302](https://github.com/bolt/bolt/pull/5302)
- Fixed: Markdown output by `{{ fields() }}` is parsed correctly. [#5310](https://github.com/bolt/bolt/pull/5310)
- Fixed: Set TCP/IP address columns to a max length of 45 characters [#5317](https://github.com/bolt/bolt/pull/5317)
- Fixed: Upload limit (2mb max) calculation, due to rounding errors. [#5318](https://github.com/bolt/bolt/pull/5318)


Bolt 3.0.0
----------

Released 2016-05-10. Notable changes:

 - Fixed: Field prefix and suffix visual tweak. [#5296](https://github.com/bolt/bolt/pull/5296)
 - Ficed: Only regex match from beginning of multi-line for inserting assets. [#5294](https://github.com/bolt/bolt/pull/5294)
 - Update: Bump node-sass to 3.7.0 [#5293](https://github.com/bolt/bolt/pull/5291)

Bolt 3.0.0 RC 3
---------------

Released 2016-05-09. Notable changes:

 - Fixed: `FieldValue` initialisation of `json_array`. [#5291](https://github.com/bolt/bolt/pull/5291)
 - Fixed: Change `YAMLupdater` regex to be non-greedy. [#5290](https://github.com/bolt/bolt/pull/5290)
 - Fixed: Finish Translation keywords. [#5287](https://github.com/bolt/bolt/pull/5287)
 - Fixed: Tweak exception classes thrown for access control [#5285](https://github.com/bolt/bolt/pull/5285)

Bolt 3.0.0 RC 2
---------------

Released 2016-05-03. Notable changes:

 - Fixed: Doing some minor cleanup for base-2016 sass files. [#5280](https://github.com/bolt/bolt/pull/5280)
 - Fixed: Hotfixing Audit extension, for better logging. [#5275](https://github.com/bolt/bolt/pull/5275)
 - Fixed: More selective logic for updating authtoken data. [#5278](https://github.com/bolt/bolt/pull/5278)
 - Updated: Several updates to language files. [#5273](https://github.com/bolt/bolt/pull/5273) [#5276](https://github.com/bolt/bolt/pull/5276)
 - Updated: Symfony components updated to Symfony 2.8.5

Bolt 3.0.0 RC 1
---------------

Released 2016-05-02. Notable changes:

 - Added: ACL Events, for hunting down trouble with sessions. [#5265](https://github.com/bolt/bolt/pull/5265)
 - Added: Created `server:run` nut command [#5228](https://github.com/bolt/bolt/pull/5228)
 - Changed: Disabled the "live editor" by default [#5266](https://github.com/bolt/bolt/pull/5266)
 - Fixed: Case error in entity hydration [#5258](https://github.com/bolt/bolt/pull/5258)
 - Fixed: error for repository `findBy` query [#5231](https://github.com/bolt/bolt/pull/5231)
 - Fixed: for multiple select fields in repeater collection [#5230](https://github.com/bolt/bolt/pull/5230)
 - Fixed: Hack to get web path for local extensions on a git install [#5244](https://github.com/bolt/bolt/pull/5244)
 - Fixed: Handle custom fields with incorrectly namespaced field templates [#5238](https://github.com/bolt/bolt/pull/5238)
 - Fixed: Handle exception when table is missing [#5253](https://github.com/bolt/bolt/pull/5253)
 - Fixed: Set a CSRF token for 'recently edited' on Dashboard [#5255](https://github.com/bolt/bolt/pull/5255)
 - Fixed: Setting z-index for bootbox correctly. [#5259](https://github.com/bolt/bolt/pull/5259)
 - Fixed: Sync configuration defaults with `config.yml` [#5234](https://github.com/bolt/bolt/pull/5234)
 - Fixed: Typo induced inconsistency in `Extends` [#5243](https://github.com/bolt/bolt/pull/5243)
 - Removed: 'serve' shell script [#5277](https://github.com/bolt/bolt/pull/5277)
 - Removed: The `viewSourceRoles` option that don't work [#5236](https://github.com/bolt/bolt/pull/5236)

Bolt 3.0.0 beta 3
-----------------

Released 2016-04-19. Notable changes:

 - Added: Add omnisearch test [#5203](https://github.com/bolt/bolt/pull/5203)
 - Change: Change the 'About' link in the profiler to a slightly more useful 'Dashboard' link. [#5174](https://github.com/bolt/bolt/pull/5174)
 - Change: Move "Possible field types" in config.yml [#5176](https://github.com/bolt/bolt/pull/5176)
 - Change: Put the 'template select' in the "Template" tab, where it makes most sense [#5160](https://github.com/bolt/bolt/pull/5160)
 - Change: Rename chapter taxonomy example to group [#5169](https://github.com/bolt/bolt/pull/5169)
 - Change: Set session.save_path to a hidden directory, to persist after flush [#5154](https://github.com/bolt/bolt/pull/5154)
 - Change: Show better log message for "failed login attempt". [#5177](https://github.com/bolt/bolt/pull/5177)
 - Change: Updating base-2016 dependencies [#5175](https://github.com/bolt/bolt/pull/5175)
 - Change: Various theme improvements [#5189](https://github.com/bolt/bolt/pull/5189)
 - Deprecation: Remove livereload settings from config.yml and _page.twig [#5166](https://github.com/bolt/bolt/pull/5166)
 - Deprecation: Remove number from contenttypes.yml [#5167](https://github.com/bolt/bolt/pull/5167)
 - Fixed: A few issues regarding database constraints. [#5207](https://github.com/bolt/bolt/pull/5207), [#5219](https://github.com/bolt/bolt/pull/5219), [#5224](https://github.com/bolt/bolt/pull/5224)
 - Fixed: Adding missing .control-label class to fields, markdown, image, imagelist and textarea [#5200](https://github.com/bolt/bolt/pull/5200)
 - Fixed: Bad default values for list types [#5221](https://github.com/bolt/bolt/pull/5221)
 - Fixed: Don't re-fetch record on preview [#5151](https://github.com/bolt/bolt/pull/5151)
 - Fixed: Don't throw a fatal exception on missing table [#5223](https://github.com/bolt/bolt/pull/5223)
 - Fixed: Fix default value for select contenttype [#5187](https://github.com/bolt/bolt/pull/5187)
 - Fixed: Fix for recursive join bug in repeaters [#5216](https://github.com/bolt/bolt/pull/5216)
 - Fixed: JS Fix for imagelists in templatefields [#5188](https://github.com/bolt/bolt/pull/5188)
 - Fixed: Look for template files in deeper folders. [#5217](https://github.com/bolt/bolt/pull/5217)
 - Fixed: Only force enabling user entity on first user creation [#5208](https://github.com/bolt/bolt/pull/5208)
 - Fixed: Reset moved ckeditor instances in repeaters [#5171](https://github.com/bolt/bolt/pull/5171)
 - Fixed: Show folders in ck_files template [#5147](https://github.com/bolt/bolt/pull/5147)
 - Fixed: Strip tags from useragent. (minor security issue) [#5178](https://github.com/bolt/bolt/pull/5178)
 - Fixed: Temporary hack to get the permission name associated with the route [#5202](https://github.com/bolt/bolt/pull/5202)
 - Fixed: Use right syntax for Omnisearch templates [#5192](https://github.com/bolt/bolt/pull/5192)
 - Travis: Composer install testing [#5150](https://github.com/bolt/bolt/pull/5150)

Bolt 3.0.0 beta 2
-----------------

Released 2016-04-08. Notable changes:

 - Fixed: Prefix branding path with root URL [#5136](https://github.com/bolt/bolt/pull/5136)
 - Fixed: Improve Select Queries Across Joins [#5128](https://github.com/bolt/bolt/pull/5128)
 - Fixed: Fix for [#5009](https://github.com/bolt/bolt/pull/5009) – Can't delete a record from dashboard listing [#5131](https://github.com/bolt/bolt/pull/5131)
 - Fixed: Fix contenttype listing blocks [#5133](https://github.com/bolt/bolt/pull/5133)
 - Fixed: Fix domain cookie value with http port [#5115](https://github.com/bolt/bolt/pull/5115)
 - Fixed: Extend services… and actually return the service [#5089](https://github.com/bolt/bolt/pull/5089)
 - Fixed: Loop inside the closure [#5090](https://github.com/bolt/bolt/pull/5090)
 - Fixed: Some minor Theme fixes. [#5092](https://github.com/bolt/bolt/pull/5092)
 - Fixed: Setting the `lang` attribute of the `<html>` tag. [#5096](https://github.com/bolt/bolt/pull/5096)
 - Fixed: Fix overwriting of key variable for repeating field metadata [#5099](https://github.com/bolt/bolt/pull/5099)
 - Fixed: Don't return nbsp in ImageHandler [#5100](https://github.com/bolt/bolt/pull/5100)
 - Fixed: Setting the `lang` attribute of the `<html>` tag. [#5096](https://github.com/bolt/bolt/pull/5096)
 - Fixed: Removing `default` and `base-2014` themes. Only `base-2016` should stay. [#5093](https://github.com/bolt/bolt/pull/5093)
 - Fixed: Hotfix storage trait [#5106](https://github.com/bolt/bolt/pull/5106)
 - Fixed: Suggestion for better text in case the 'about-us' block is missing. [#5116](https://github.com/bolt/bolt/pull/5116)
 - Fixed: Fix named templatefields without burning the whole thing down 🚒 [#5127](https://github.com/bolt/bolt/pull#5127)
 - Fixed: Clarify the _sub_fields.twig defaults file. [#5120](https://github.com/bolt/bolt/pull/5120)
 - Fixed: Update Composer version constraint for stable version [#5129](https://github.com/bolt/bolt/pull/5129)
 - Fixed: Migrate password hash on login [#5132](https://github.com/bolt/bolt/pull/5132)
 - Fixed: Properly check if templatefields are still present. [#5139](https://github.com/bolt/bolt/pull/5139)
 - Fixed: Don't `dump()` unless logged on, or debug_show_loggedoff is set. [#5138](https://github.com/bolt/bolt/pull/5138)
 - Fixed: Prioritise HTTP_HOST over SERVER_NAME [#5140](https://github.com/bolt/bolt/pull/5140)
 - Fixed: Fix Upload Button for Templatefields [#5141](https://github.com/bolt/bolt/pull/5141)
 - Fixed: Assets… Remove outdated v2 functionality [#5144](https://github.com/bolt/bolt/pull/5144)
 - Fixed: Theme improvements [#5145](https://github.com/bolt/bolt/pull/5145)
 - Fixed: Base 2016/minor tweaks [#5148](https://github.com/bolt/bolt/pull/5148)
 - Fixed: Updating theme.yml [#5149](https://github.com/bolt/bolt/pull/5149)

Bolt 3.0.0 beta 1
-----------------

Released 2016-03-26. Notable changes:

 - So much new things, first release of major new version. See: https://bolt.cm/newsitem/bolt-3-beta-1

Bolt 2.2.20
-----------

Released 2016-04-19. Notable changes:

 - Fixed: templates output `{{ dump() }}` when not logged in. (See [#5122](https://github.com/bolt/bolt/issues/5122))
 - Change: Better log messages for failed login attempts (See [#5197](https://github.com/bolt/bolt/issues/5197))
 - Fixed: Strip tags from useragent. (minor security issue) (See [#5179](https://github.com/bolt/bolt/issues/5179))
 - Fixed: Fix issue with imagelists not updating due to event checking. (See [#5159](https://github.com/bolt/bolt/issues/5159))
 - Change: Update Composer version constraint for stable version (See [#5130](https://github.com/bolt/bolt/issues/5130))
 - Fix: Only skip htmlsnippets if we are returning a cached response (See [#5121](https://github.com/bolt/bolt/issues/5121))
 - [Travis] Ensure that Composer install is built against Bolt 2.2 (See [#5118](https://github.com/bolt/bolt/issues/5118))
 - Move create factory to the start of setup in src/Composer/PackageManager (See [#5048](https://github.com/bolt/bolt/issues/5048))

Bolt 2.2.19
-----------

Released 2016-03-11. Notable changes:

 - Updated: Updating bundled Javascript modules and libraries
 - Updating dependencies (most notable, Foundation 5.5.3) (See [#4856](https://github.com/bolt/bolt/issues/4856))
 - Fix: Locking some more Symfony packages to Symfony 2.6.x, for PHP 5.3.3 compatibility (See [#4984](https://github.com/bolt/bolt/issues/4984))
 - Fix: Imagehandler updates and bugfixes (See [#4973](https://github.com/bolt/bolt/issues/4973))
 - Fix: Prevent duplicate content for paging requests (See [#4981](https://github.com/bolt/bolt/issues/4981))
 - Updated: Spanish Translation (See [#4958](https://github.com/bolt/bolt/issues/4958))
 - Updated: Set the Composer requirement to ^1.0@beta (See [#4955](https://github.com/bolt/bolt/issues/4955))
 - Fix: Fix Config Setting for certain options in config.yml (See [#4940](https://github.com/bolt/bolt/issues/4940))
 - Change: Set default error reporting to ignore 'warnings' (See [#4926](https://github.com/bolt/bolt/issues/4926))
 - Fix for image/file list blur (See [#4923](https://github.com/bolt/bolt/issues/4923))
 - Added: Multiple file select when "picking from server" (See [#4879](https://github.com/bolt/bolt/issues/4879))
 - Added the search dialog to the standalone file editor interface (See [#4890](https://github.com/bolt/bolt/issues/4890))
 - Fix: Add a default value for checkboxes (See [#4869](https://github.com/bolt/bolt/issues/4869))
 - Fix: PackageManager ping 504 Gateway Time-out (See [#4735](https://github.com/bolt/bolt/issues/4735))
 - Fix: Dashboardnews 504 Gateway Time-out (See [#4734](https://github.com/bolt/bolt/issues/4734))
 - [in PL1] Fixed: Checkbox displays wrong value directly after saving (See [#4997](https://github.com/bolt/bolt/issues/4997))

Bolt 2.2.18
-----------

Released 2016-02-08. Notable changes:

 - Fix: Fix select fields in templatefields (See [#4759](https://github.com/bolt/bolt/issues/4759))
 - Fix: Now possible to set a page size for a taxonomy listing (See [#4822](https://github.com/bolt/bolt/issues/4822))
 - Fix: Viewing a preview of a record clears the unsaved record warning (See [#4640](https://github.com/bolt/bolt/issues/4640))
 - Fix: Don't use value on checkbox, and set with prop (See [#4777](https://github.com/bolt/bolt/issues/4777))
 - Change: Allow to set context for custom Twig functions and filters (See [#4779](https://github.com/bolt/bolt/issues/4779))
 - Change: Update `MenuBuilder.php`, don't do unneeded hydration on menu items. (See [#4791](https://github.com/bolt/bolt/issues/4791))
 - Fix: Fixes "non-interactive configuration" of composer-install by providing extra vars in composer.json (See [#4750](https://github.com/bolt/bolt/issues/4750))
 - Fix: Update form_div_layout.html.twig (See [#4795](https://github.com/bolt/bolt/issues/4795))

Bolt 2.2.17
-----------

Released 2016-01-27. Notable changes:

 - Fix: No href around '…'-placeholders in pagers. (See [#4650](https://github.com/bolt/bolt/issues/4650))
 - Fix: Templatefields in 'Viewless' records work correctly now ([#4653](https://github.com/bolt/bolt/issues/4653))
 - Change: Move `NutSP::addCommand` to `$app['nut.commands.add']` so it's not "static" ([#4662](https://github.com/bolt/bolt/issues/4662))
 - Fix: Don't whitescreen on a response with a Twig exception ([#4668](https://github.com/bolt/bolt/issues/4668))
 - Fix: Don't try writing to vendor on composer installs ([#4677](https://github.com/bolt/bolt/issues/4677))
 - Added: Added capability to set an amount of requested records for specific kind of taxonomy. ([#4691](https://github.com/bolt/bolt/issues/4691))
 - Change: Generate preview route from url generator. (See [#4697](https://github.com/bolt/bolt/issues/4697))
 - Change: Add JS events to editfile/editcontent ajax saving. ([#4720](https://github.com/bolt/bolt/issues/4720))
 - Fix: Block access to `.git` folders in `.htaccess`. ([#4749](https://github.com/bolt/bolt/issues/4749))

Bolt 2.2.16
-----------

Released 2016-01-01. Notable changes:

 - Change: Updating .gitignore. Add PHPstorm cruft. (See [#4621](https://github.com/bolt/bolt/issues/4621))
 - Change: Themes use `theme.yml` now, fallback to old `config.yml` (See [#4414](https://github.com/bolt/bolt/issues/4414))
 - Fixed: Fixed a 'Catchable Error' in the `record.twig` template for the old 'default' theme ([#4645](https://github.com/bolt/bolt/issues/4645))
 - Updated: A few Bower / Grunt modules were updated, and all JS / CSS rebuilt (See [#4647](https://github.com/bolt/bolt/issues/4647))

Bolt 2.2.15
-----------

Released 2015-12-29. Notable changes:

 - Fixed: Exception is no longer thrown when editing an empty config file or template. (See [#4636](https://github.com/bolt/bolt/issues/4636))
 - Added: Add custom sidebar groups for contenttypes. (Backport of #3793)
 - Fixed: Don't re-sort taxonomy listing pages, if the taxonomy has `has_sortorder` (See [#4601](https://github.com/bolt/bolt/issues/4601))
 - Fixed: Add JSON to list of denied file types for Apache (See [#4610](https://github.com/bolt/bolt/issues/4610)) [security]
 - Fixed: Fix/more spinners and missing icons (See [#4573](https://github.com/bolt/bolt/issues/4573))
 - Fixed: Sidebar Height Resize Issue (See [#4573](https://github.com/bolt/bolt/issues/4573))
 - Fixed: Fix preview unpublished content (See [#4544](https://github.com/bolt/bolt/issues/4544))

Bolt 2.2.14
-----------

Released 2015-11-27. Notable changes:

 - Updated: Symfony components updated to 2.6.12
 - Fixed: Determine web profiler location using Extension rather than bundle (See [#4432](https://github.com/bolt/bolt/issues/4432))
 - Fixed: No scrolling panes on "View Users" page. (See [#4438](https://github.com/bolt/bolt/issues/4438))
 - Fixed: Limit user agent strings to 128 characters on persist. (See [#4413](https://github.com/bolt/bolt/issues/4413))
 - Fixed: Fix alt and title tags for popup & showimage, height/width for showImage (See [#4231](https://github.com/bolt/bolt/issues/4231))
 - Fixed: Make 'required' for `type: select` fields work. (See: #4420)
 - Fixed: Replace `☰` for `≡ `for better supported unicode on Android. (See [#4388](https://github.com/bolt/bolt/issues/4388))
 - Regex that insert snippets not working when </head> does not starts the line.  [#4367](https://github.com/bolt/bolt/issues/4367)
 - Changed: Set `composer/composer` to dev stability. (See [#4345](https://github.com/bolt/bolt/issues/4345))
 - Fixed: Fixed priority issue in assets (See [#4343](https://github.com/bolt/bolt/issues/4343))
 - Fixed: Fixing reordering images in an ImageList. (See [#3573](https://github.com/bolt/bolt/issues/3573))
 - Fixed: Retrieve existing relations before setting them from POST. Fixes "No related entries on preview" issue. (See [#4340](https://github.com/bolt/bolt/issues/4340))
 - Fixed: Handle save status transition (See [#4326](https://github.com/bolt/bolt/issues/4326))
 - Added: Allow the `notfound:` status to point to a static template, instead of a contenttype record.
 - Fixed: Don't insert Bolt meta tags on AJAX requests (See [#4297](https://github.com/bolt/bolt/issues/4297))
 - Fixed: Put correct cannonical link into html head on paging content request
 - Fixed: Increase z-index, so popups cover `{{ dump() }}` output.

Bolt 2.2.13
-----------

Released 2015-10-07. Notable changes:

- Fixed: Taxonomies being wiped on status change using grouped taxonomy. (See [#3868](https://github.com/bolt/bolt/issues/3868))
- Fixed: Add edit permission to the `modifiable` property (See [#4198](https://github.com/bolt/bolt/issues/4198))
- Added: Allow height and autocomplete in categories taxonomies.
- Added: Allow for 'type: hidden' fields in `contenttypes.yml`
- Added: Allow the theme's `config.yml` to set add_jquery. Fixes [#4098](https://github.com/bolt/bolt/issues/4098)
- Added: Optionally allow spaces in tags.
- Updated: Updating UIkit(2.22), CodeMirror and Marked.js
- Changed: Ignore theme directory except for base-* and default
- Changed: Use tag names instead of slugs for autocomplete and tag cloud. [#4125](https://github.com/bolt/bolt/issues/4125)
- Changed: Strip periods, commas, colons & semi-colons from prefill titles
- Changed: date-time format uses a more i18n-friendly format (See [#4053](https://github.com/bolt/bolt/issues/4053))
- Changed: Moving 'Install new extension' to the top of the screen.
- Fixed: Don't sort getContent in listing view, when the contenttype has a taxonomy that has a sortorder.
- Fixed: Don't show (non working) drag'n'drop in list overviews.
- Fixed: Fix the info text for imagelist fields (See [#4051](https://github.com/bolt/bolt/issues/4051))
- Fixed: Fix to #3991 – Geolocation snaps pin to matched address.
- Fixed: No links for records that are 'viewless'. Fixes [#3999](https://github.com/bolt/bolt/issues/3999) for [2.2]
- Fixed: [2.2] Allow non-strings as query parameters with pager. issue [#4109](https://github.com/bolt/bolt/issues/4109)
- Fixed: "Timed publish" fixed for SQLITE (Now using a `DateTime` object instead of `CURRENT_TIMESTAMP`)
- Fixed: Fix: Don't show notice about `mailoptions` when not logged on.
- Fixed: Alignment of #navpage-secondary menu item icons in FireFox. (See [#4178](https://github.com/bolt/bolt/issues/4178))
- Fixed: Strip tags from `<title>` in editcontent. Fixes: #3590.
- Fixed: Fix secondary nav element's class not being output in certain cases.

Bolt 2.2.12
-----------

Not yet released. Notable changes:

- Added: Allow height and autocomplete in categories taxonomies.
- Added: Allow for 'type: hidden' fields in `contenttypes.yml`
- Added: Allow the theme's `config.yml` to set add_jquery. Fixes [#4098](https://github.com/bolt/bolt/issues/4098)
- Added: Optionally allow spaces in tags.
- Updated: Updating UIkit(2.22), CodeMirror and Marked.js
- Changed: Ignore theme directory except for base-* and default
- Changed: Use tag names instead of slugs for autocomplete and tag cloud. [#4125](https://github.com/bolt/bolt/issues/4125)
- Changed: Strip periods, commas, colons & semi-colons from prefill titles
- Changed: date-time format uses a more i18n-friendly format (See [#4053](https://github.com/bolt/bolt/issues/4053))
- Changed: Moving 'Install new extension' to the top of the screen.
- Fixed: Don't sort getContent in listing view, when the contenttype has a taxonomy that has a sortorder.
- Fixed: Use hydration for `{{ record.previous() }}` and `{{ record.next() }}`, so routes that use taxonmies in slugs work correctly. (See [#4193](https://github.com/bolt/bolt/issues/4193))
- Fixed: Don't override "templatechosen" if previously set. Makes sure the correct template is shown in the Twig nub in the Toolbar. (See [#4191](https://github.com/bolt/bolt/issues/4191))
- Fixed: Don't show (non working) drag'n'drop in list overviews.
- Fixed: Fix the info text for imagelist fields (See [#4051](https://github.com/bolt/bolt/issues/4051))
- Fixed: Fix to #3991 – Geolocation snaps pin to matched address.
- Fixed: No links for records that are 'viewless'. Fixes [#3999](https://github.com/bolt/bolt/issues/3999) for [2.2]
- Fixed: [2.2] Allow non-strings as query parameters with pager. issue [#4109](https://github.com/bolt/bolt/issues/4109)
- Fixed: "Timed publish" fixed for SQLITE (Now using a `DateTime` object instead of `CURRENT_TIMESTAMP`)
- Fixed: Fix: Don't show notice about `mailoptions` when not logged on.
- Fixed: Alignment of #navpage-secondary menu item icons in FireFox. (See [#4178](https://github.com/bolt/bolt/issues/4178))
- Fixed: Strip tags from `<title>` in editcontent. Fixes: #3590.
- Fixed: Fix secondary nav element's class not being output in certain cases.

Bolt 2.2.10
-----------

Released 2015-09-01. Notable changes:

- Updated: Updated Doctrine and it's components to the latest version. (version 2.5.1, see [here for details](http://www.doctrine-project.org/2015/08/31/doctrine_orm_2_5_1_and_2_4_8_released.html))

Bolt 2.2.9
----------

Released 2015-08-30. Notable changes:

- Added: Add a button for `<hr>` / horizontal ruler in CKeditor for (See [#3539](https://github.com/bolt/bolt/issues/3539))
- Added: Show "profile" button on users page, if not allowed to edit other users than themselves. (See [#4008](https://github.com/bolt/bolt/issues/4008))
- Fixed: Truly allow edit permission to be assigned to the owner role (Thanks @fabschurt, See [#4019](https://github.com/bolt/bolt/issues/4019))
- Fixed: Fix record retrieval for ownership checking (Thanks @fabschurt, See [#4024](https://github.com/bolt/bolt/issues/4024))
- Fixed: Don't allow extension tables that do not use the configured prefix (See [#3968](https://github.com/bolt/bolt/issues/3968))
- Fixed: Don't attempt to log array elements that aren't set. (See [#3969](https://github.com/bolt/bolt/issues/3969))
- Fixed: Changelog 'next' & 'previous' buttons didn't work as expected in Bolt 2.2.x (See [#4009](https://github.com/bolt/bolt/issues/4009))
- Fixed: Move `initMailCheck()` call to a `before()` handler (See [#3953](https://github.com/bolt/bolt/issues/3953))
- Fixed: Allow edit permission to be assigned to the owner role. Fixes "Unable to edit entry with owner permission". (See [#3938](https://github.com/bolt/bolt/issues/3938))
- Fixed: Fix path to Nut for Composer installs (See [#3959](https://github.com/bolt/bolt/issues/3959))
- Changed: Provide UI feedback on extension site timeouts. (See [#3972](https://github.com/bolt/bolt/issues/3972))
- Changed: Move the Showcases template select to the Meta tab (See [#4006](https://github.com/bolt/bolt/issues/4006))
- Changed: Don't `Content::preParse()` return an error, log it and return a generic message (See [#3990](https://github.com/bolt/bolt/issues/3990))
- Changed: Lock Silex to version 1.2.* for PHP 5.3.3 support (See [#4021](https://github.com/bolt/bolt/issues/4021))
- Updated: CKeditor updated to version 4.5.2
- Updated: Symfony updated to 2.6.11
- Updated: Silex updated to 1.2.5
- Updated: Font Awesome to 4.4
- Updated: Database integrity checker. Add foreign key checks to IntegrityChecker (See [#3872](https://github.com/bolt/bolt/issues/3872))
- Tests: Allow `getStatementMock()` to be passed a desired return value (See [#3957](https://github.com/bolt/bolt/issues/3957))


Bolt 2.2.8
----------

Released 2015-07-31. Notable changes:

- Fixed: Ensure grouped taxonomies aren't wiped from listing pages when toggling the publication status. (See [#3910](https://github.com/bolt/bolt/issues/3910))
- Fixed: Timed entries will no longer switch to 'unpublished' after update to 2.2.7 (See [#3899](https://github.com/bolt/bolt/issues/3899))
- Fixed: "Notice: Array to string conversion in /..../src/Storage.php on line 1071" (See [#3893](https://github.com/bolt/bolt/issues/3893))
- Fixed: Avoid a missing array key from displaying a warning (Thanks Fabschurt)
- Updated: `squizlabs/php_codesniffer` requirement to `~2.0` due to upstream changes.
- Fixed: Send storage event in publishTimedRecords (See [#3879](https://github.com/bolt/bolt/issues/3879))
- Fixed: Memory leak / loop in "new content" (See [#3883](https://github.com/bolt/bolt/issues/3883))


Bolt 2.2.5
----------

Released 2015-07-24. Notable changes:

 - Performance: Don't request users if we don't have to, and streamline `isAllowed()` functionality. ([#3847](https://github.com/bolt/bolt/issues/3847))
 - Fixed / security: If a user is not root, do not allow them to change the file extension on rename in UI. (Thanks to Tim Coen of Curesec GmbH for bringing this issue to our attention. See [#3815](https://github.com/bolt/bolt/issues/3815))
 - Fixed: Layout issue in Chrome 44. Pretty sure it's a weird bug in Chrome. ([#3856](https://github.com/bolt/bolt/issues/3856))
 - Changed: Update JS Markdown Options to match Parsedown for consistency. ([#3820](https://github.com/bolt/bolt/issues/3820))
 - Added: A Nut command to rebuild the extension autoloaders. ([#3786](https://github.com/bolt/bolt/issues/3786))
 - Changed: Send "New Bolt site" e-mail upon first user creation only. (Thanks Fabschurt, See [#3792](https://github.com/bolt/bolt/issues/3792))
 - Fixed: Issue in Geolocation field, where it would 'forget' the retrieved address. ([#3813](https://github.com/bolt/bolt/issues/3813))
 - Fixed / Added: Have the Async file/directory routes return useful JSON responses. Display an UI alert on file/directory request failures. ([#3815](https://github.com/bolt/bolt/issues/3815))
 - Fixed: Trigger database update notifications for changed field names ([#3816](https://github.com/bolt/bolt/issues/3816))
 - Fixed: The database platform's method `getCreateTableSQL` allows foreign keys to be added. (Thanks Ntomka, See [#3745](https://github.com/bolt/bolt/issues/3745))
 - Added: Add caching for the translation provider ([#3753](https://github.com/bolt/bolt/issues/3753))
 - Fixed: If vendor/autoload.php is missing, include `LowlevelException.php` manually.

Bolt 2.2.4
----------

Released 2015-06-25. Notable changes:

 - Fixed: Logic preventing building of local extension autoloader (Thanks timcooper, See [#3699](https://github.com/bolt/bolt/issues/3699))
 - Fixed: Clipboard paste issue with fileuploader (Thanks timcooper, See [#3702](https://github.com/bolt/bolt/issues/3702))
 - Added: Now possibile to use the search feature for specific contenttype(s) (Thanks sbani, See [#3713](https://github.com/bolt/bolt/issues/3713))
 - Fixed: Wrong interpretation of max_upload_filesize / post_max_size (Thanks tvlooy, See [#3732](https://github.com/bolt/bolt/issues/3732))
 - Fixed: Password reset "Error: Divide by zero" (See [#3730](https://github.com/bolt/bolt/issues/3730))

Bolt 2.2.3
----------

Released 2015-06-15. Notable changes:

 - Fixed: Yaml config read and write fixed for other indentations than '2 spaces'. (See [#3682](https://github.com/bolt/bolt/issues/3682))

Bolt 2.2.2
----------

Released 2015-06-12. Notable changes:

 - Added: Swedish translation. (Thanks SahAssar, See [#3659](https://github.com/bolt/bolt/issues/3659))
 - Fixed: In menus: Don't assume root URL is '/'
 - Fixed: Generate search pager link
 - Fixed: Sorting in 'overviews':`content.TitleColumnName()` is an array now. (See [#3635](https://github.com/bolt/bolt/issues/3635))
 - Fixed: Set link of item in Menu properly, and fixes bug in populateItemFromRecord. (See [#3655](https://github.com/bolt/bolt/issues/3655))

Bolt 2.2.1
----------

Released 2015-06-05. Notable changes:

 - Update: Silex is now version 1.3.0
 - Added: Implement `title_format:`, to control the behaviour of what's seen as the 'title' in overviews and listings. See [#3635](https://github.com/bolt/bolt/issues/3635)
 - Changed: Create the extension's composer.json if only a local extension exists. See [#3627](https://github.com/bolt/bolt/issues/3627)
 - Fixed: Use the Silex HttpFragmentServiceProvider as TwigCoreExtension has been removed in Silex 1.3. See [#3632](https://github.com/bolt/bolt/issues/3632)
 - Fixed: Two more overrides in `composer.json` for symfony components that got bumped to v2.7.0. See [#3634](https://github.com/bolt/bolt/issues/3634)
 - Fixed: Extend SSL/TLS Handling. Fixes bug/warnings in Packagemanager. See [#3633](https://github.com/bolt/bolt/issues/3633)
 - Fixed: Generated `<meta>`-tags always stay in the `<head>` section, now. See [#3637](https://github.com/bolt/bolt/issues/3637)


Bolt 2.2.0
----------

Released 2015-06-04. Notable changes:

 - Added: Stop Finder from recursing common build folders and place a limit on the maximum depth it will recurse otherwise. (Thanks @Cooperaj, See [#3069](https://github.com/bolt/bolt/issues/3069))
 - Fixed: Removing default taxonomylink route leads to exception (See [#3070](https://github.com/bolt/bolt/issues/3070))
 - Fixed: Don't reset urls when adding base path. (See [#3074](https://github.com/bolt/bolt/issues/3074))
 - Fixed: Whoops error when duplicating a record. (See [#3064](https://github.com/bolt/bolt/issues/3064))
 - Fixed: Fixes broken extension installer (See [#3086](https://github.com/bolt/bolt/issues/3086))
 - Added: Add composer branch alias. (See [#3089](https://github.com/bolt/bolt/issues/3089))
 - Fixed: Redirect for backend trailing slash redirect (`/bolt` -> `/bolt/`) (See [#3083](https://github.com/bolt/bolt/issues/3083))
 - Fixed: Regression that errored on PHP < 5.3.6: `Remove SplFileInfo::getExtension()`. (See [#3095](https://github.com/bolt/bolt/issues/3095))
 - Added: Use the X-Forwarded for IP address when an appropriate one exists and the trustedProxies config contains a valid IP. (Thanks @Cooperaj, See [#3031](https://github.com/bolt/bolt/issues/3031), #3093)
 - Fixed: Extension theme installer working properly (See [#3108](https://github.com/bolt/bolt/issues/3108), thanks @nikgo)
 - Fixed: Replacing `&nbsp;` with single space, instead of nothing. (See [#3111](https://github.com/bolt/bolt/issues/3111))
 - Added: Added an option to delete a record, when editing it. (See [#3134](https://github.com/bolt/bolt/issues/3134))
 - Removed: removed "frontend permission checks". (See [#3133](https://github.com/bolt/bolt/issues/3133))
 - Fixed: Prevent extra spaces in excerpts. (See [#3130](https://github.com/bolt/bolt/issues/3130))
 - Fixed: Show notice on update of Bolt. (See [#3129](https://github.com/bolt/bolt/issues/3129))
 - Fixed: Make dashboard activity log autoupdate again (See [#3126](https://github.com/bolt/bolt/issues/3126))
 - Fixed: Upload UX Improvements (Thanks, @Pinpickle, See [#3123](https://github.com/bolt/bolt/issues/3123))
 - Fixed: Warning for unsaved content comes up when nothing has been changed (See [#3077](https://github.com/bolt/bolt/issues/3077))
 - Added: Make the sanitisation of markdown fields configurable. (See [#2992](https://github.com/bolt/bolt/issues/2992) #3142)
 - Fixed: Fixed z-index of sidebar. (See [#3100](https://github.com/bolt/bolt/issues/3100))
 - Fixed: Disable "revert" button on 'edit file' screen, when file is not wrtiable. (See [#3009](https://github.com/bolt/bolt/issues/3009))
 - Added: Allow for multiple (fallback) locales in `config.yml`. (Thanks @sintemaa, See [#3127](https://github.com/bolt/bolt/issues/3127))
 - Fixed: Be a little more strict in picking 'selected' options: Only use the fallback, if there's no valid id set.
 - Change: Lock composer.json to Symfony 2.6.4 as 2.6.5 fails PHPUnit
 - Added: Re-added standalone jQuery lib, as some extensions might need it.
 - Fixed: Create app/ subdirectories on Composer installs, and other `composer install` fixes.
 - Fixed: Workaround, so we don't break on installations with `"require": []` in `extensions/composer.json` (See [#3171](https://github.com/bolt/bolt/issues/3171))
 - Never add extra jQueries on the backend. (See [#3177](https://github.com/bolt/bolt/issues/3177))
 - JS bugfixes (save button + goto publishing status) (See [#3160](https://github.com/bolt/bolt/issues/3160))
 - Flush the cache if the Bolt version has changed (See [#3183](https://github.com/bolt/bolt/issues/3183))
 - Fixed: Allow `|||`-queries to be more complex (Thanks @Pinpickle, See [#3189](https://github.com/bolt/bolt/issues/3189))
 - Fixed: Storage not using sort from contenttype (Thanks @CarsonF, See [#3187](https://github.com/bolt/bolt/issues/3187))
 - Change: Only log content not found errors if slug isn't numeric, since `next` and `previous` check by `id` (See [#3186](https://github.com/bolt/bolt/issues/3186))
 - Fixed: Make sure we use `ParsedownExtra`, instead of just `Parsedown. (Thanks, @cooperaj, See [#3194](https://github.com/bolt/bolt/issues/3194))
 - Fixed: Changelog content uses correct tablenames. (See 3198)
 - Added: Markdown fields now have a nice new editor, with Preview and fill screen / split screen functionality. (ee #3225)
 - Fixed: Normalising taxonomy before comparison for deleting old ones.(Thanks @silentworks, See [#3224](https://github.com/bolt/bolt/issues/3224))
 - Change: Improve `shyphenate()`: Only add breaks to long words, instead of everywhere. (See [#3221](https://github.com/bolt/bolt/issues/3221))
 - Added: Upload UX improvements part 2 - Progress bars. (Thanks @pinpickle, See [#3218](https://github.com/bolt/bolt/issues/3218))
 - Fixed: Fix 'current' in menu. (See [#3209](https://github.com/bolt/bolt/issues/3209))
 - Change: Use 4 spaces in all `.yml` and base-2014 theme. (See [#3205](https://github.com/bolt/bolt/issues/3205))
 - Fixed: Set the canonical correctly. (See [#3214](https://github.com/bolt/bolt/issues/3214))
 - Fixed: Make `BaseExtension::initialize()` non-abstract again to deal with PHP < 5.3.10 (See [#3257](https://github.com/bolt/bolt/issues/3257))
 - Fixed: `isallowed` checks for extensions to also check for `extensions:config` (Thanks @SahAssar, See [#3249](https://github.com/bolt/bolt/issues/3249))
 - Fixed: Strange step behaviour when no `min` is set for integer fields (Thanks @Pinpickle, See [#3284](https://github.com/bolt/bolt/issues/3284))
 - Fixed: Make sure we have the same amount of columns, always. (See [#3228](https://github.com/bolt/bolt/issues/3228))
 - Added: Allow for filtering on 'taxonomies' on the overview pages. (See [#3278](https://github.com/bolt/bolt/issues/3278))
 - Added: Support for methods in `routing.yml` (See [#3292](https://github.com/bolt/bolt/issues/3292))
 - Fixed: Publishing for items with 'Timed Publish' is working again. (Fixes #3279)
 - Added: Frontend requests should not set cookies. Remove them, to allow Varnish to do a better job of caching the request. (See [#3309](https://github.com/bolt/bolt/issues/3309))
 - Added: Add exif aspect ratio and exif orientation data to imageinfo() (Thanks @Intendit,See [#3308](https://github.com/bolt/bolt/issues/3308))
 - Fixed: Fix rendering in sidebar on mobile. (See [#3246](https://github.com/bolt/bolt/issues/3246))
 - Added: New feature: Retina support for thumbnails (see bolt/bolt-thumbs/#19)
 - Added: Allow filtering in 'record.related' on other fields too. (Thanks @miguelavaqrod, See [#3303](https://github.com/bolt/bolt/issues/3303))
 - Fixed: Fix path for non-ajaxy file-upload. (See [#3303](https://github.com/bolt/bolt/issues/3303))
 - Fixed: Added extra check for "view permission" for quicklinks (Thanks @StevendeVries, See [#3299](https://github.com/bolt/bolt/issues/3299))
 - Fixed: Make geolocation gracefully fail if google is not loaded (See [#3356](https://github.com/bolt/bolt/issues/3356))
 - Added: Small UX improvement: Show spinner while doing ajaxy save. (See [#3355](https://github.com/bolt/bolt/issues/3355))
 - Added: Use `PHPExif\Exif` for getting EXIF data in `TwigExtensions::imageinfo()` (See [#3354](https://github.com/bolt/bolt/issues/3354))
 - Change: `slug` and `geolocation` fields refactored. Refactored out 'GoMap' dependancy. (See [#3344](https://github.com/bolt/bolt/issues/3344))
 - Change: Fixed Scrutinizer config (See [#3343](https://github.com/bolt/bolt/issues/3343))
 - Change: Allow explicit setting of a Contenttype's table name suffix (See [#3342](https://github.com/bolt/bolt/issues/3342))
 - Fixed: Only setting default timezone if config provides it (See [#3334](https://github.com/bolt/bolt/issues/3334))
 - Fixed: Fix for "timed depublish". (See [#3330](https://github.com/bolt/bolt/issues/3330))
 - Fixed: [Tests] Move PHPUnit resource creation into the listener (See [#3326](https://github.com/bolt/bolt/issues/3326))
 - Change: Make backend submenu-items the top level item, if there's only one sub-item. (See [#3323](https://github.com/bolt/bolt/issues/3323), thanks Intendit)
 - Fixed: Fix rendering in sidebar on mobile. Fixes (See [#3321](https://github.com/bolt/bolt/issues/3321))
 - Added: Allow filtering in 'record.related' on other fields too. (See [#3320](https://github.com/bolt/bolt/issues/3320))
 - Fixed: Slugs generation fixed (See [#3310](https://github.com/bolt/bolt/issues/3310))
 - Change: Refactor out `load.php`. (See [#3371](https://github.com/bolt/bolt/issues/3371))
 - Change: Move CodeSniffer to a composer package (See [#3365](https://github.com/bolt/bolt/issues/3365))
 - Fixed: Fixing small inconsistency in `permissions.yml.dist': 'editors' can browse uploaded files from within CKeditor now. (See [#3357](https://github.com/bolt/bolt/issues/3357))
 - Make the removal / stripping of `&nbsp;` characters in CKEditor fields optional. (See [#3373](https://github.com/bolt/bolt/issues/3373))
 - Fixed to handle correctly file requests with built-in server (Thanks, @pedronofuentes, See [#3383](https://github.com/bolt/bolt/issues/3383))
 - Fix to use title and alt text on image field (Thanks @Shyim, See [#3387](https://github.com/bolt/bolt/issues/3387))
 - Fixed: Allow editing of empty files. (Thanks, @SahAssar, See [#3391](https://github.com/bolt/bolt/issues/3391))
 - Added: Include plugins "Styles Combo" and "Stylesheet Parser" in CKEditor (See [#3384](https://github.com/bolt/bolt/issues/3384))
 - Added: Always have a fallback for a timezone when it isn't set in either php.ini or config.yml (See [#3397](https://github.com/bolt/bolt/issues/3397))
 - Added: Ability to set a Email Sender Mail in config.yml (Thanks @Shyim, See [#3409](https://github.com/bolt/bolt/issues/3409))
 - [Tests] Properly tidy the Codeception template field test (See [#3451](https://github.com/bolt/bolt/issues/3451))
 - Check if folder exists first, when using it for uploads (See [#3450](https://github.com/bolt/bolt/issues/3450))
 - [Codeception] Use a conditional version so 5.3 Travis builds won't fail. (See [#3448](https://github.com/bolt/bolt/issues/3448))
 - Enhancement to define templates for the template chooser in backend. (Thanks Shyim, See [#3447](https://github.com/bolt/bolt/issues/3447))
 - Allow 'duplicate' and 'delete' from contextual menu, when a Record has relationships. Fixes [#3431](https://github.com/bolt/bolt/issues/3431)
 - Don't trigger DBCheck for changed indexes. Fixes [#3426](https://github.com/bolt/bolt/issues/3426)
 - Only show the "delete" button if the page has been saved already. Fixes [#3444](https://github.com/bolt/bolt/issues/3444)
 - Fixes [#3435](https://github.com/bolt/bolt/issues/3435) by disabling browser XSS protection for file editing. (See [#3439](https://github.com/bolt/bolt/issues/3439), thanks timcooper)
 - Secondary menu refactoring (JS) + fixes for #2329 and #2347 (See [#3433](https://github.com/bolt/bolt/issues/3433)
 - Added: optional filter to select field with contenttype values. (See [#3432](https://github.com/bolt/bolt/issues/3432))
 - Added: support for YAML repeated nodes (See [#3430](https://github.com/bolt/bolt/issues/3430))
 - Fixed: PGSQL encoding settings in post connect event until doctrine/2.5.2 is out (See [#3429](https://github.com/bolt/bolt/issues/3429))
 - Fixed: Change slug label when contenttype is viewless (See [#3428](https://github.com/bolt/bolt/issues/3428), thanks Pinpickle)
 - Make Application::unsetSessionCookie() optional and BC friendly (See [#3427](https://github.com/bolt/bolt/issues/3427))
 - Added: Config file `web.config` for IIS servers. (See [#3423](https://github.com/bolt/bolt/issues/3423), thanks hyperTwitch)
 - Change: PGSQL encoding settings in post connect event until doctrine/2.5.2 is out. (See [#3429](https://github.com/bolt/bolt/issues/3429))
 - Fixed: Change slug label when contenttype is viewless. (See [#3428](https://github.com/bolt/bolt/issues/3428))
 - Added: add optional filter to select field with contenttype values (See [#3432](https://github.com/bolt/bolt/issues/3432))
 - Fixed: Secondary menu refactoring (JS) (Fixes #2329 and #2347)
 - Fixed: prevent error message in `_sub_menu.twig` if `strict_variables` is set. (See [#3462](https://github.com/bolt/bolt/issues/3462))
 - Security: Make sure we set the status correctly for 'async' requests. (See [#3463](https://github.com/bolt/bolt/issues/3463))
 - Fixed: Set status explicitly on ajaxy requests. (See [#3466](https://github.com/bolt/bolt/issues/3466))
 - Fixed: Bunch of small HTML5 validation errors. (See [#3485](https://github.com/bolt/bolt/issues/3485))
 - Fixed: `attrib` for images. (See [#3487](https://github.com/bolt/bolt/issues/3487))
 - Fixed: Fix pagination for searching via `getContent()`` or `{% setcontent %}`. (See [#3496](https://github.com/bolt/bolt/issues/3496))
 - Fixed: Handle empty composer config, e.g. themes (See [#3509](https://github.com/bolt/bolt/issues/3509))
 - Fixed: Use correct service key (See [#3507](https://github.com/bolt/bolt/issues/3507))
 - Return to previous page, with paging and filtering. See [#3588](https://github.com/bolt/bolt/issues/3588)
 - Add a try-exception when parsing `allowtwig` fields.
 - Call abort for simpleredirect on "Save and return to overview".
 - Tweaking the delay, making behaviour bit nicer for "fast clickers". Fixes [#3513](https://github.com/bolt/bolt/issues/3513)
 - Fixes server-side error when ajaxy-deleting records that were created programmatically.
 - Prevent composer from sneaking in any `v2.7.0` symfony components. Remove `symfony/locale`.
 - Return to previous page, with paging and filtering. See [#3588](https://github.com/bolt/bolt/issues/3588)
 - Add a try-exception when parsing `allowtwig` fields.
 - Call abort for simpleredirect on "Save and return to overview".
 - Tweaking the delay, making behaviour bit nicer for "fast clickers". Fixes [#3513](https://github.com/bolt/bolt/issues/3513)
 - Fixes server-side error when ajaxy-deleting records that were created programmatically.
 - Prevent composer from sneaking in any `v2.7.0` symfony components. Remove `symfony/locale`.
 - Local extension autoloader See [#3607](https://github.com/bolt/bolt/issues/3607)
 - Request cache fix. See [#3561](https://github.com/bolt/bolt/issues/3561)
 - To make content 'stick' after saving, use `contentkey` instead of `key`. [#3527](https://github.com/bolt/bolt/issues/3527)
 - Make sure `$unserdata` is an array, and not merely `false`. See [#3526](https://github.com/bolt/bolt/issues/3526)
 - Handle Flysystem exception correctly when a file is not found. See [#3519](https://github.com/bolt/bolt/issues/3519)


Bolt 2.1.9
----------

Released 2015-04-29. Notable changes:

 - Fixed: `attrib` for images. (See [#3487](https://github.com/bolt/bolt/issues/3487))
 - Fixed: Fix pagination for searching via `getContent()`` or `{% setcontent %}`. (See [#3496](https://github.com/bolt/bolt/issues/3496))
 - Fixed: Use 'alt' instead of 'title' in Image fieldtype's attributes. (See [#3505](https://github.com/bolt/bolt/issues/3505))


Bolt 2.1.8
----------

Released 2015-04-29. Notable changes:

 - Fix: prevent error message in `_sub_menu.twig` if `strict_variables` is set. (See [#3462](https://github.com/bolt/bolt/issues/3462))
 - Security: Make sure we set the status correctly for 'async' requests. (See [#3463](https://github.com/bolt/bolt/issues/3463))
 - Fixed: Set status explicitly on ajaxy requests. Fixes [#3466](https://github.com/bolt/bolt/issues/3466)

Bolt 2.1.7
----------

Released 2015-04-29. Notable changes:

 - Check if folder exists first, when using it for uploads (See [#3450](https://github.com/bolt/bolt/issues/3450))
 - Allow 'duplicate' and 'delete' from contextual menu, when a Record has relationships. Fixes [#3431](https://github.com/bolt/bolt/issues/3431)
 - Don't trigger DBCheck for changed indexes. Fixes [#3426](https://github.com/bolt/bolt/issues/3426)
 - Make Application::unsetSessionCookie() optional and BC friendly (See [#3427](https://github.com/bolt/bolt/issues/3427))
 - Make the removal / stripping of `&nbsp;` characters in CKEditor fields optional. (See [#3373](https://github.com/bolt/bolt/issues/3373))
 - Fixed: Allow editing of empty files. (Thanks, @SahAssar, See [#3391](https://github.com/bolt/bolt/issues/3391))
 - Added: Always have a fallback for a timezone when it isn't set in either php.ini or config.yml (See [#3394](https://github.com/bolt/bolt/issues/3394))
 - Only show the "delete" button if the page has been saved already. Fixes [#3444](https://github.com/bolt/bolt/issues/3444)

Bolt 2.1.6
----------

Released 2015-04-13. Notable changes:

 - Fixed: Slugs generation with `uses:` fixed (See [#3310](https://github.com/bolt/bolt/issues/3310))
 - Added: Frontend requests should not set cookies. Remove them, to allow Varnish to do a better job of caching the request. (See [#3309](https://github.com/bolt/bolt/issues/3309))
 - Added: Add exif aspect ratio and exif orientation data to imageinfo() (Thanks @Intendit,See [#3308](https://github.com/bolt/bolt/issues/3308))
 - Fixed: Fix rendering in sidebar on mobile. (See [#3246](https://github.com/bolt/bolt/issues/3246))
 - Added: New feature: Retina support for thumbnails (see bolt/bolt-thumbs/#19)
 - Added: Allow filtering in 'record.related' on other fields too. (Thanks @miguelavaqrod, See [#3303](https://github.com/bolt/bolt/issues/3303))
 - Fixed: Fix path for non-ajaxy file-upload. (See [#3303](https://github.com/bolt/bolt/issues/3303))
 - Fixed: Added extra check for "view permission" for quicklinks (Thanks @StevendeVries, See [#3299](https://github.com/bolt/bolt/issues/3299))
 - Change: Frontend requests should not set cookies. Remove them, to allow Varnish to do a better job of caching the request. (See [#3309](https://github.com/bolt/bolt/issues/3309))
 - Fixed: Fix rendering in sidebar on mobile. Fixes (See [#3321](https://github.com/bolt/bolt/issues/3321))
 - Fixed: Fix for "timed depublish". (See [#3330](https://github.com/bolt/bolt/issues/3330))
 - Fixed: Only setting default timezone if config provides it (See [#3334](https://github.com/bolt/bolt/issues/3334))
 - Added: Small UX improvement: Show spinner while doing ajaxy save. (See [#3355](https://github.com/bolt/bolt/issues/3355))
 - Fixed: Fixing small inconsistency in `permissions.yml.dist': 'editors' can browse uploaded files from within CKeditor now. (See [#3357](https://github.com/bolt/bolt/issues/3357))
 - Fix: People who try installing Bolt on PHP 5.1 or 5.2 will now get a nice and friendly notice that Bolt won't work. (See [#3371](https://github.com/bolt/bolt/issues/3371))

Bolt 2.1.5
----------

Released 2015-04-01. Notable changes:

 - Fixed: Strange step behaviour when no `min` is set for integer fields (Thanks @Pinpickle, See [#3284](https://github.com/bolt/bolt/issues/3284))
 - Fixed: Make sure we have the same amount of columns, always. (See [#3228](https://github.com/bolt/bolt/issues/3228))
 - Added: Allow for filtering on 'taxonomies' on the overview pages. (See [#3278](https://github.com/bolt/bolt/issues/3278))
 - Added: Support for methods in `routing.yml` (See [#3292](https://github.com/bolt/bolt/issues/3292))
 - Fixed: Publishing for items with 'Timed Publish' is working again. (Fixes #3279)

Bolt 2.1.4
----------

Released 2015-03-27. Notable changes:

 - Never add extra jQueries on the backend. (See [#3177](https://github.com/bolt/bolt/issues/3177))
 - JS bugfixes (save button + goto publishing status) (See [#3160](https://github.com/bolt/bolt/issues/3160))
 - Flush the cache if the Bolt version has changed (See [#3183](https://github.com/bolt/bolt/issues/3183))
 - Fixed: Allow `|||`-queries to be more complex (Thanks @Pinpickle, See [#3189](https://github.com/bolt/bolt/issues/3189))
 - Fixed: Storage not using sort from contenttype (Thanks @CarsonF, See [#3187](https://github.com/bolt/bolt/issues/3187))
 - Change: Only log content not found errors if slug isn't numeric, since `next` and `previous` check by `id` (See [#3186](https://github.com/bolt/bolt/issues/3186))
 - Fixed: Make sure we use `ParsedownExtra`, instead of just `Parsedown`. (Thanks, @cooperaj, See [#3194](https://github.com/bolt/bolt/issues/3194))
 - Fixed: Changelog content uses correct tablenames. (See 3198)
 - Change: Improve `shyphenate()`: Only add breaks to long words, instead of everywhere. (See [#3221](https://github.com/bolt/bolt/issues/3221))
 - Fixed: Fix 'current' in menu. (See [#3209](https://github.com/bolt/bolt/issues/3209))
 - Fixed: `isallowed` checks for extensions to also check for `extensions:config` (Thanks @SahAssar, See [#3249](https://github.com/bolt/bolt/issues/3249))
 - Fixed: Allow 'name' in contenttype to override translation, for 2.1.x (See [#3259](https://github.com/bolt/bolt/issues/3259))
 - Fixed: Make `BaseExtension::initialize()` non-abstract again to deal with PHP < 5.3.10 (See [#3257](https://github.com/bolt/bolt/issues/3257))

Bolt 2.1.3
----------

Released 2015-03-18. Notable changes:

 - Added: Added an option to delete a record, when editing it. (See [#3134](https://github.com/bolt/bolt/issues/3134))
 - Removed: removed "frontend permission checks". (#see 3133)
 - Fixed: Prevent extra spaces in excerpts. (See [#3130](https://github.com/bolt/bolt/issues/3130))
 - Fixed: Show notice on update of Bolt. (See [#3129](https://github.com/bolt/bolt/issues/3129))
 - Fixed: Make dashboard activity log autoupdate again (See [#3126](https://github.com/bolt/bolt/issues/3126))
 - Added: Make the sanitisation of markdown fields configurable. (See [#2992](https://github.com/bolt/bolt/issues/2992) #3142)
 - Fixed: Fixed z-index of sidebar. (See [#3100](https://github.com/bolt/bolt/issues/3100))
 - Fixed: Disable "revert" button on 'edit file' screen, when file is not wrtiable. (See [#3009](https://github.com/bolt/bolt/issues/3009))
 - Added: Allow for multiple (fallback) locales in `config.yml`. (Thanks @sintemaa, See [#3127](https://github.com/bolt/bolt/issues/3127))
 - Fixed: Warning for unsaved content comes up when nothing has been changed (See [#3077](https://github.com/bolt/bolt/issues/3077))
 - Fixed: Be a little more strict in picking 'selected' options: Only use the fallback, if there's no valid id set.
 - Change: Lock composer.json to Symfony 2.6.4 as 2.6.5 fails PHPUnit
 - Added: Re-added standalone jQuery lib, as some extensions might need it.
 - Fixed: Workaround, so we don't break on installations with `"require": []` in `extensions/composer.json` (See [#3171](https://github.com/bolt/bolt/issues/3171))


Bolt 2.1.1
----------

Released 2015-03-12. Notable changes:

 - Added: Stop Finder from recursing common build folders and place a limit on the maximum depth it will recurse otherwise. (Thanks @Cooperaj, See [#3069](https://github.com/bolt/bolt/issues/3069))
 - Fixed: Removing default taxonomylink route leads to exception (See [#3070](https://github.com/bolt/bolt/issues/3070))
 - Fixed: Don't reset urls when adding base path. (See [#3074](https://github.com/bolt/bolt/issues/3074))
 - Fixed: Whoops error when duplicating a record. (See [#3064](https://github.com/bolt/bolt/issues/3064))
 - Fixed: Fixes broken extension installer (See [#3086](https://github.com/bolt/bolt/issues/3086))
 - Fixed: Redirect for backend trailing slash redirect (`/bolt` -> `/bolt/`) (See [#3083](https://github.com/bolt/bolt/issues/3083))
 - Fixed: Regression that errored on PHP < 5.3.6: `Remove SplFileInfo::getExtension()`. (See [#3095](https://github.com/bolt/bolt/issues/3095))
 - Fixed: Extension theme installer working properly (See [#3108](https://github.com/bolt/bolt/issues/3108), thanks @nikgo)
 - Fixed: Replacing `&nbsp;` with single space, instead of nothing. (See [#3111](https://github.com/bolt/bolt/issues/3111))


Bolt 2.1.0
----------

Released 2015-03-09. Notable changes:

- Added: Allow for `https://` protocol in `canonical` setting in config.yml. (See [#3044](https://github.com/bolt/bolt/issues/3044))
- Added: Taiwanese (zh_TW) localisation. (#3022, thanks @Leon0824)
- Fixed: Update CKEditor field objects if they exist on AJAX content saves. (See [#2998](https://github.com/bolt/bolt/issues/2998))
- Added: A logging record for extension update and uninstall (See [#2993](https://github.com/bolt/bolt/issues/2993))
- Added: Client-side validation (first for floats only). (See [#2997](https://github.com/bolt/bolt/issues/2997))
- Change: Float field now not html5 number field anymore, and both `,` and `.` are allowed as decimal seperator.
- Change: The distribution now includes `composer.json.dist` and  `composer.lock.dist` files, if you need them.
- Added: Allow extensions to be used as controllers (non static) (See [#2971](https://github.com/bolt/bolt/issues/2971))
- Fixed: Long conttenttype names are truncated properly in the sidebar now. (See [#2513](https://github.com/bolt/bolt/issues/2513))
- Fixed: Don't leak Database credentials on connection error during set up. (See [#2538](https://github.com/bolt/bolt/issues/2538))
- Change: Remove unused jquery-catchpaste.
- Change: Many changes (for the better) to logging: Monolog, improved UI, separation of concerns.
- Refactor: Many changes and improvements to the Config object.
- Refactor: Major cleanup in Bolt\Storage, Bolt\Events\StorageEvents and Bolt\Content ([#2664](https://github.com/bolt/bolt/issues/2664))
- **Updated: PHPUnit now covers complete code base** (#2542, thanks @rossriley)
- **Updated: Extensions interface had major overhaul and now uses the Composer API more extensively and provides better error handling for AJAX calls on the Extend page** (#2543 thanks @GawainLynch)
- **Update: Bolt's custom logging provider has been replaced with Monolog** (#2546, thanks @GawainLynch)
- Added: Extension repo as service: extracts the queries of the Extensions repo to a separate service provider.
 (#2550 thanks @rossriley)
- Added: BASH/ZSH command completion for Nut (See [#2657](https://github.com/bolt/bolt/issues/2657))
- Updated: Magnific popup is now at 1.0.0. (#2560, thanks @cdowdy)
- Updated: FlySystem from version 0.5 to 1.1, with php5.3 patch. ([#2587](https://github.com/bolt/bolt/issues/2587))
- Fixed: arrays in type:select fields. ([#2609](https://github.com/bolt/bolt/issues/2609))
- Added: Allow for `keys: slug` in `type: select` fields, to customize the used field that's actually stored in the DB. ([#2597](https://github.com/bolt/bolt/issues/2597))
- Fixed: Small logic fix for 'groupingSort'. (See [#2520](https://github.com/bolt/bolt/issues/2520))
- Fixed: Have `Cache::clearCache()` use $app['resources']->getPath('root') for the 'thumbs' directory (See [#2512](https://github.com/bolt/bolt/issues/2512))
- Fixed: Corner case bug in password reset (See [#2616](https://github.com/bolt/bolt/issues/2616))
- Added: Editing content now shows recent changes that have been logged (if enabled) that link to the change comparison (See [#2620](https://github.com/bolt/bolt/issues/2620))
- Fixed: Minor HTML fix and broken link in base-2015 theme (#2650, thanks @apatkinson)
- Fixed: Nest folders in cache 2 deep. (See [#2644](https://github.com/bolt/bolt/issues/2644))
- Fixed: bug fixed in "Select in all items" in overview. (See [#2669](https://github.com/bolt/bolt/issues/2669))
- Fixed: Fix filebrowser route binding name to be 'filebrowser' (See [#2680](https://github.com/bolt/bolt/issues/2680))
- Fixed: Allow setting of regex pattern, replacement and case of uploaded file names (See [#2691](https://github.com/bolt/bolt/issues/2691))
- Fixed: Regression that would break the ability to set global Twig variables in an extension (See [#2717](https://github.com/bolt/bolt/issues/2717))
- Changed: Enforce SSL Config Change. Now we use only `enforce_ssl`, `cookies_https_only` is deprecated. (See [#2726](https://github.com/bolt/bolt/issues/2726), thanks @dwolf555)
- Fixed: Flipped array replace arguments in `parseFieldsAndGroups()`. (See [#2738](https://github.com/bolt/bolt/issues/2738))
- **Fixed: No more unwanted `&nbsp;`'s in CKeditor.** Fixes [#2660](https://github.com/bolt/bolt/issues/2660)
- Fixed: Logged in user can no longer disable themselves
- Fixed: Disabling a logged in user with force a logout
- Fixed: Fixed a bug with some utf8 characters becoming question marks after saving to database. (Thanks @dwr, See [#2804](https://github.com/bolt/bolt/issues/2804))
- Fixed: Fix #2424 and other tab group improvements #2801 (TODO: Specify!)
- Added: Installed extensions now defaults to adding version constraints to allow for easier updating
- Change: The `X-Frame-Options`-header is now only sent for backend pages, and can be disabled in `config.yml` (See [#2825](https://github.com/bolt/bolt/issues/2825))
- Change: Bolt now distinguishes between 'regular news' and 'alerts' on the Dashboard screen. This way, we can better notify people in case of an urgent security issue. (See [#2830](https://github.com/bolt/bolt/issues/2830))
- Fixed: The built-in anti-CSRF token was renamed to `bolt_csrf_token` to prevent clashes when a user has a field named `token`. (See [#2831](https://github.com/bolt/bolt/issues/2831))
- Change: You can now use `{id}` in routes for records instead of `{slug}`, if you wish to have links to records using the id. (See [#2832](https://github.com/bolt/bolt/issues/2832))

Bolt 2.0.5
----------

Released 2015-01-21. Notable changes:

- Fixed: appending `order=...` to arbitrary Bolt URLs will no longer silently try to apply sorting to getContent.
- Fixed: For extensions adding Twig functions in content: `isSafe()` works correctly now (#2492, thanks @jmschelcher)
- Change: Use Twig’s `resolveTemplate` instead of `file_exists` in Frontend Controller. (#2494, thanks @lavoiesl)
- Fixed: Remove horizontal scroll on login screen. (#2495, thanks @cdowdy)
- Fixed: Ongoing cleanup of translation labels. (thanks @Chilion)
- Fixed: "Clear Cache" now also clears all generated thumbs from `thumbs/`
- Fixed: Nav links in admin dashboard, when accessed over HTTPS (#2499, thanks @glasspelican)
- Fixed: Much better code-formatting in CKEditor (#2841, thanks @Pinpickle)
- Added: You can now use multiple slugs in a single contenttype, should you want to. ([#2490](https://github.com/bolt/bolt/issues/2490))
- Fixed: EXIF orientation and general breakage of thumbnails on older versions of GD has been fixed.
- Updated: Several used components were updated: Symfony components to 2.6.5, Silex to 1.2.3, Twig to 1.17, Parsedown to 1.5.0, Doctrine DBAL to 2.5.1

Note: due to a quick fix, right after 2.0.4 was tagged, that version was skipped as a separate release.

Bolt 2.0.3
----------

Released 2015-01-16. Notable changes:

- Added: **integration of Symfony's VarDumper component**. The old 'Dumper::dump' is still
  present, for backwards compatibility
- Added: Option to disable dashboard news
- Added: Browser caching and EXIF orientation support for thumbnails
- Added: Improvements to Imagelist and Filelist fields: Better selection and re-ordering,
  delete multiple items, and view full-size images. (Thanks @Pinpickle, See [#2360](https://github.com/bolt/bolt/issues/2360))
- Added: proportional autoscaling option to showImage() using '0' values
- Added: Use jshint in grunt workflow
- Added: Whoops now sends errors to AJAX callers in JSON format ([#2433](https://github.com/bolt/bolt/issues/2433))
- Fixed: Bug where contenttype name in menu isn't translated
- Fixed: **No CSS / static files shown** when using PHP's built-in server via ./serve ([#2381](https://github.com/bolt/bolt/issues/2381))
- Fixed: Reference of IntlDateFormatter stub functions ([#2415](https://github.com/bolt/bolt/issues/2415))
- Fixed: Magnific popup image preview on image lists ([#2443](https://github.com/bolt/bolt/issues/2443))
- Fixed: Added 'pasteZone: null', which prevents triggering an upload when pasting from Word in Chrome. ([#2427](https://github.com/bolt/bolt/issues/2427))
- Fixed: Pager works correctly for paging categories and other taxonomies ([#2468](https://github.com/bolt/bolt/issues/2468))
- Fixed: Only apply pagination to `setcontent`, when 'paging' is required / requested ([#2417](https://github.com/bolt/bolt/issues/2417))
- Fixed: Select field type, with values from another content type now always uses the 'id' as the value in the DB. ([#2465](https://github.com/bolt/bolt/issues/2465))
- Fixed: When session is invalidated due to changing your own username, redirect to login screen. ([#2457](https://github.com/bolt/bolt/issues/2457))
- Updated: CKeditor to 4.4.6, and all used plugins to the latest versions
- Updated: jQuery to version 1.11.2
- Updated: jQuery goMap to 1.3.3  ([#2377](https://github.com/bolt/bolt/issues/2377))
- Updated: Unify image- and filelists
- Updated: Make image- and filelists looking more flat
- Change: Set default width/height of showImage() to '0'
- Change: Fields that previously used 0000-00-00 and 1900-01-01 now default to using NULL instead ([#2396](https://github.com/bolt/bolt/issues/2396))
- Change: Uglify bolts js files to bolt.min.js ([#2398](https://github.com/bolt/bolt/issues/2398))
- Change: Priorities of Taxonomy listing template selection ([#2420](https://github.com/bolt/bolt/issues/2420))
- Change: Separate magnific-popup between app and theme ([#2429](https://github.com/bolt/bolt/issues/2429))
- Change: Also use XMLHttpRequest to detect AJAX requests in `getWhichEnd()` ([#2423](https://github.com/bolt/bolt/issues/2423))
- Change: Refactor Twig setup (Thanks @CarsonF, See [#2430](https://github.com/bolt/bolt/issues/2430))
- Change: Url matcher updates (Thanks @CarsonF, See [#2431](https://github.com/bolt/bolt/issues/2431))
- Change: Add a data() Twig function to allow storing of data to be passed en masse to JavaScript ([#2458](https://github.com/bolt/bolt/issues/2458))
- Removed: Removed the `base-2013` theme
- Removed: Ancient browser-specific tags
- Change: System activity and change log permissions have changed and users now require systemlog and/or changelog permissions in permissions.yml (See [#2805](https://github.com/bolt/bolt/issues/2805))

Bolt 2.0.2
----------

Released 2014-12-21. Notable changes:

- Update Storage to insert instead of update when content doesn't exist
- If we fall back from UPDATE to INSERT, we should also log it as such
- Added an option to disable stack functionality
- Tweaking protocol detection for HTTPS

Bolt 2.0.0
----------

Released 2014-12-18. Notable changes:

- Fix the pager for taxonomy-listings
- Implemented "viewless" property for contenttypes. Fixes [#2149](https://github.com/bolt/bolt/issues/2149)
- IE9: Javascript Dropdowns [#2195](https://github.com/bolt/bolt/issues/2195)
- Set twig globals on 404 pages. Fixes [#2198](https://github.com/bolt/bolt/issues/2198)
- Show Stack on "Browse Server" for images. Fixes [#2235](https://github.com/bolt/bolt/issues/2235)
- Added "Copy to themes" button in Extend
- Force json response to send text/plain header. Fixes uploading images in IE9.
- Transparent button on "focus". Tabbing is visible again, helps with accessibility
- Add "roles" button to users screen
- Allowing the extensions site to bet set in config.yml
- confirm extension delete/removal/uninstall
- Refuse to display Bolt in an iframe, to prevent possible clickjacking. See [#2197](https://github.com/bolt/bolt/issues/2197). Thanks, @narendrabhati
- Editcontent dates are now stored as entered, set the timezone in config.yml
- Paging now works correctly for taxonomy-listings
- Make ckeditor use new global locale setting [#2087](https://github.com/bolt/bolt/issues/2087)
- Contenttype submenu labels are now translated
- Store Geolocation fields, if only the coordinates are set. Make sure the view initialises, if on a second tab. Fixes [#2172](https://github.com/bolt/bolt/issues/2172)
- Set moments locale before initialise
- proxy twig functions to separate twig extension object
- Disallow snippets on extend sub-requests
- Fix moments and remove it from global space
- Fix notices when translation files are not found
- Follow symlinks when loading local extensions
- Responsive video. Fixes [#1916](https://github.com/bolt/bolt/issues/1916)
- Set the correct mime type for woff2 font type
- Tweaking the fonts, goes with [#2099](https://github.com/bolt/bolt/issues/2099)
- Fix #2085 allow editing of broken Yaml file
- Actually load the _local file for extensions
- Add register_shutdown_function() to bootstrap for earliest init we can get
- Cleanups in composer.json. Almost got rid of all "dev-master" and "@dev"
- Add an "ungrouped" tab, for when some fields are defined without a group. Fixes [#2080](https://github.com/bolt/bolt/issues/2080)
- When sorting with "behaves_like: grouping", order '0' was ignored. Fixes [#2112](https://github.com/bolt/bolt/issues/2112)
- Making the form validation notices a bit more in line with our other notices
- Finally workaround the cron interval column removal
- Disable buttons and install section if offline
- Have Extend cope with offline network connections
- Show other files than images in "async browse". Fixes [#2136](https://github.com/bolt/bolt/issues/2136)
- Check dates in fromPost instead of in setValues
- Add backend FlashBag messages on various extension loading failures
- Don't reset depublish column when depublishing
- Don't autoDepublish again for later edits
- If you sort by a column, it will also sort the "recent" menu items using the same sort
- Fix time input being displayed for date only fields
- Add "config": {"discard-changes": true} to extensions/composer.json so that changed files will get overridden/overwriten on extension package update instead of silently failing
- Fix "Only variables should be passed by reference"-notice. Fixes [#2209](https://github.com/bolt/bolt/issues/2209)
- Parse markdown fields for excerpts. Fixes [#2246](https://github.com/bolt/bolt/issues/2246)
- Sigh … If we have more relations, make sure we keep them all. See [#2255](https://github.com/bolt/bolt/issues/2255)
- When looking for Twig template file names, also include any character that is not a vertical whitespace character
- image and showimage wrong index [#2275](https://github.com/bolt/bolt/issues/2275)
- Logic fix in timestamp for theme/config.yml check


Bolt 2.0 "Beta 3"
-----------------

Released 2014-11-04. Notable changes:

- Updated Moments.js to 2.8.3. Use moment.locale() instead of deprecated moment.lang() [#2088](https://github.com/bolt/bolt/issues/2088)
- Fixed: Simplified Html::trimText(), "excerpt" now works better on non-western-european strings
- Fixed: Breadcrumbs in "edit file" screens work correctly now. [#2077](https://github.com/bolt/bolt/issues/2077)
- Fixed: Proper sorting in Backend overview. Fixes [#2036](https://github.com/bolt/bolt/issues/2036)
- Fixed: "open_basedir restriction in effect" error related to Composer
- Fixed: "File(`/dev/urandom`) is not within the allowed path(s)" error
- Added: min/max/step options for float and integer fieldtypes
- Switching from Googlefonts to our local version of Source Sans Pro. Fixes [#2038](https://github.com/bolt/bolt/issues/2038)
- Ongoing fixes and changes to the translation files. (and added Chinese)
- A bunch of fixes to the automatic acceptance tests
- Fixed: Editable record list calls wrong listing template (for related content) [#2028](https://github.com/bolt/bolt/issues/2028)
- Added: Javascript form validation [#2019](https://github.com/bolt/bolt/issues/2019)
- Added: custom `error: "message"` for use with javascript form validation
- Fixed: Fix notice in `SearchPlugin::handle()` [#2025](https://github.com/bolt/bolt/issues/2025)
- Added: Added hints generation for removed columns in dbcheck
- Fixed: Exception when viewing related items [#2026](https://github.com/bolt/bolt/issues/2026)
- Uploads from the "files" screens upload to the correct folder, instead of always to `files/`
- Updated HTML/CSS for the "Changelog" screen
- Added Pathogen, in order to handle paths on Windows systems better …
- … and immediately factored out [Isolator](https://github.com/IcecaveStudios/isolator), because that shit's just wrong, man

Known issues:

- If you have PHP 5.3 or PHP 5.4 with APC enabled, the installation of extensions might not work. PHP 5.3 and PHP 5.4 _will_ work with APC disabled. PHP 5.5 works as expected, regardless of whether APC is enabled

Bolt 2.0 "Beta 2"
-----------------

Released 2014-11-29

- Ongoing fixes to the "Translation" module (for the backend): extra labels, updated translations, code cleanup
- Ongoing fixes to the "Paths" module (for the backend): Fixed some missing paths and edge-cases
- Installing "Extensions" works much better on Windows servers now
- Refactor: Translating using `__( )` has been moved to it's own class
- Refactor: Refactored `lib.php` into a proper class
- Usage of "icons" in various `.yml` files has been tweaked to make them futureproof
- Installing a theme copies `config.yml.dist` to `config.yml` in the new folder now
- Stack upload button does not work  Blocking release bug
- Error in "date" and "datetime" fields fixed. Datepicker works correctly for a wider range of languages now
- The "templateselect" field in records now does actually select that template to render the pages
- Cleanup a lot of issues in the code, as reported by [Sensiolabs Insight](https://insight.sensiolabs.com/projects/4d1713e3-be44-4c2e-ad92-35f65eee6bd5)
- CSS / HTML fixes in "users" and "edit file" screens
- Fix to filesystem locations for asset installer
- Jumping to "current status" from Edit Record screen works correctly now

Bolt 2.0 "Beta Boltcamp"
------------------------

Released 2014-10-19. Notable changes:

- Everything[.](http://sandvoxxcheap.com/wp-content/uploads/2014/07/ZPWb7iM.gif)
