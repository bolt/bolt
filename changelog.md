Bolt 2.3 DEV-head
-----------------

Not yet released.

 - Lots (list needs to be compiled)


Bolt 2.2.12
-----------

Not yet released. Notable changes:

- Added: Allow height and autocomplete in categories taxonomies.
- Added: Allow for 'type: hidden' fields in `contenttypes.yml`
- Added: Allow the theme's `config.yml` to set add_jquery. Fixes #4098
- Added: Optionally allow spaces in tags.
- Updated: Updating UIkit(2.22), CodeMirror and Marked.js
- Changed: Ignore theme directory except for base-* and default
- Changed: Use tag names instead of slugs for autocomplete and tag cloud. #4125
- Changed: Strip periods, commas, colons & semi-colons from prefill titles
- Changed: date-time format uses a more i18n-friendly format (See #4053)
- Changed: Moving 'Install new extension' to the top of the screen.
- Fixed: Don't sort getContent in listing view, when the contenttype has a taxonomy that has a sortorder.
- Fixed: Don't show (non working) drag'n'drop in list overviews.
- Fixed: Fix the info text for imagelist fields (See #4051)
- Fixed: Fix to #3991 – Geolocation snaps pin to matched address.
- Fixed: No links for records that are 'viewless'. Fixes #3999 for [2.2]
- Fixed: [2.2] Allow non-strings as query parameters with pager. issue #4109
- Fixed: "Timed publish" fixed for SQLITE (Now using a `DateTime` object instead of `CURRENT_TIMESTAMP`)
- Fixed: Fix: Don't show notice about `mailoptions` when not logged on.
- Fixed: Alignment of #navpage-secondary menu item icons in FireFox. (See #4178)
- Fixed: Strip tags from `<title>` in editcontent. Fixes: #3590.
- Fixed: Fix secondary nav element's class not being output in certain cases.

Bolt 2.2.10
-----------

Released 2015-09-01. Notable changes:

- Updated: Updated Doctrine and it's components to the latest version. (version 2.5.1, see [here for details](http://www.doctrine-project.org/2015/08/31/doctrine_orm_2_5_1_and_2_4_8_released.html))

Bolt 2.2.9
----------

Released 2015-08-30. Notable changes:

- Added: Add a button for `<hr>` / horizontal ruler in CKeditor for (see #3539)
- Added: Show "profile" button on users page, if not allowed to edit other users than themselves. (See #4008)
- Fixed: Truly allow edit permission to be assigned to the owner role (Thanks @fabschurt, see #4019)
- Fixed: Fix record retrieval for ownership checking (Thanks @fabschurt, see #4024)
- Fixed: Don't allow extension tables that do not use the configured prefix (see #3968)
- Fixed: Don't attempt to log array elements that aren't set. (see #3969)
- Fixed: Changelog 'next' & 'previous' buttons didn't work as expected in Bolt 2.2.x (See #4009)
- Fixed: Move `initMailCheck()` call to a `before()` handler (See #3953)
- Fixed: Allow edit permission to be assigned to the owner role. Fixes "Unable to edit entry with owner permission". (See #3938)
- Fixed: Fix path to Nut for Composer installs (See #3959)
- Changed: Provide UI feedback on extension site timeouts. (see #3972)
- Changed: Move the Showcases template select to the Meta tab (See #4006)
- Changed: Don't `Content::preParse()` return an error, log it and return a generic message (See #3990)
- Changed: Lock Silex to version 1.2.* for PHP 5.3.3 support (See #4021)
- Updated: CKeditor updated to version 4.5.2
- Updated: Symfony updated to 2.6.11
- Updated: Silex updated to 1.2.5
- Updated: Font Awesome to 4.4
- Updated: Database integrity checker. Add foreign key checks to IntegrityChecker (See #3872)
- Tests: Allow `getStatementMock()` to be passed a desired return value (See #3957)


Bolt 2.2.8
----------

Released 2015-07-31. Notable changes:

- Fixed: Ensure grouped taxonomies aren't wiped from listing pages when toggling the publication status. (see #3910)
- Fixed: Timed entries will no longer switch to 'unpublished' after update to 2.2.7 (see #3899)
- Fixed: "Notice: Array to string conversion in /..../src/Storage.php on line 1071" (See #3893)
- Fixed: Avoid a missing array key from displaying a warning (Thanks Fabschurt)
- Updated: `squizlabs/php_codesniffer` requirement to `~2.0` due to upstream changes.
- Fixed: Send storage event in publishTimedRecords (see #3879)
- Fixed: Memory leak / loop in "new content" (see #3883)


Bolt 2.2.5
----------

Released 2015-07-24. Notable changes:

 - Performance: Don't request users if we don't have to, and streamline `isAllowed()` functionality. (#3847)
 - Fixed / security: If a user is not root, do not allow them to change the file extension on rename in UI. (Thanks to Tim Coen of Curesec GmbH for bringing this issue to our attention. See #3815)
 - Fixed: Layout issue in Chrome 44. Pretty sure it's a weird bug in Chrome. (#3856)
 - Changed: Update JS Markdown Options to match Parsedown for consistency. (#3820)
 - Added: A Nut command to rebuild the extension autoloaders. (#3786)
 - Changed: Send "New Bolt site" e-mail upon first user creation only. (Thanks Fabschurt, see #3792)
 - Fixed: Issue in Geolocation field, where it would 'forget' the retrieved address. (#3813)
 - Fixed / Added: Have the Async file/directory routes return useful JSON responses. Display an UI alert on file/directory request failures. (#3815)
 - Fixed: Trigger database update notifications for changed field names (#3816)
 - Fixed: The database platform's method `getCreateTableSQL` allows foreign keys to be added. (Thanks Ntomka, see #3745)
 - Added: Add caching for the translation provider (#3753)
 - Fixed: If vendor/autoload.php is missing, include `LowlevelException.php` manually.

Bolt 2.2.4
----------

Released 2015-06-25. Notable changes:

 - Fixed: Logic preventing building of local extension autoloader (Thanks timcooper, see #3699)
 - Fixed: Clipboard paste issue with fileuploader (Thanks timcooper, see #3702)
 - Added: Now possibile to use the search feature for specific contenttype(s) (Thanks sbani, see #3713)
 - Fixed: Wrong interpretation of max_upload_filesize / post_max_size (Thanks tvlooy, see #3732)
 - Fixed: Password reset "Error: Divide by zero" (see #3730)

Bolt 2.2.3
----------

Released 2015-06-15. Notable changes:

 - Fixed: Yaml config read and write fixed for other indentations than '2 spaces'. (See #3682)

Bolt 2.2.2
----------

Released 2015-06-12. Notable changes:

 - Added: Swedish translation. (Thanks SahAssar, see #3659)
 - Fixed: In menus: Don't assume root URL is '/'
 - Fixed: Generate search pager link
 - Fixed: Sorting in 'overviews':`content.TitleColumnName()` is an array now. (see #3635)
 - Fixed: Set link of item in Menu properly, and fixes bug in populateItemFromRecord. (See #3655)

Bolt 2.2.1
----------

Released 2015-06-05. Notable changes:

 - Update: Silex is now version 1.3.0
 - Added: Implement `title_format:`, to control the behaviour of what's seen as the 'title' in overviews and listings. See #3635
 - Changed: Create the extension's composer.json if only a local extension exists. See #3627
 - Fixed: Use the Silex HttpFragmentServiceProvider as TwigCoreExtension has been removed in Silex 1.3. See #3632
 - Fixed: Two more overrides in `composer.json` for symfony components that got bumped to v2.7.0. See #3634
 - Fixed: Extend SSL/TLS Handling. Fixes bug/warnings in Packagemanager. See #3633
 - Fixed: Generated `<meta>`-tags always stay in the `<head>` section, now. See #3637


Bolt 2.2.0
----------

Released 2015-06-04. Notable changes:

 - Added: Stop Finder from recursing common build folders and place a limit on the maximum depth it will recurse otherwise. (Thanks @Cooperaj, see #3069)
 - Fixed: Removing default taxonomylink route leads to exception (See #3070)
 - Fixed: Don't reset urls when adding base path. (See #3074)
 - Fixed: Whoops error when duplicating a record. (See #3064)
 - Fixed: Fixes broken extension installer (See #3086)
 - Added: Add composer branch alias. (see #3089)
 - Fixed: Redirect for backend trailing slash redirect (`/bolt` -> `/bolt/`) (See #3083)
 - Fixed: Regression that errored on PHP < 5.3.6: `Remove SplFileInfo::getExtension()`. (See #3095)
 - Added: Use the X-Forwarded for IP address when an appropriate one exists and the trustedProxies config contains a valid IP. (Thanks @Cooperaj, see #3031, #3093)
 - Fixed: Extension theme installer working properly (see #3108, thanks @nikgo)
 - Fixed: Replacing `&nbsp;` with single space, instead of nothing. (See #3111)
 - Added: Added an option to delete a record, when editing it. (See #3134)
 - Removed: removed "frontend permission checks". (See #3133)
 - Fixed: Prevent extra spaces in excerpts. (See #3130)
 - Fixed: Show notice on update of Bolt. (See #3129)
 - Fixed: Make dashboard activity log autoupdate again (See #3126)
 - Fixed: Upload UX Improvements (Thanks, @Pinpickle, see #3123)
 - Fixed: Warning for unsaved content comes up when nothing has been changed (see #3077)
 - Added: Make the sanitisation of markdown fields configurable. (see #2992 #3142)
 - Fixed: Fixed z-index of sidebar. (See #3100)
 - Fixed: Disable "revert" button on 'edit file' screen, when file is not wrtiable. (See #3009)
 - Added: Allow for multiple (fallback) locales in `config.yml`. (Thanks @sintemaa, see #3127)
 - Fixed: Be a little more strict in picking 'selected' options: Only use the fallback, if there's no valid id set.
 - Change: Lock composer.json to Symfony 2.6.4 as 2.6.5 fails PHPUnit
 - Added: Re-added standalone jQuery lib, as some extensions might need it.
 - Fixed: Create app/ subdirectories on Composer installs, and other `composer install` fixes.
 - Fixed: Workaround, so we don't break on installations with `"require": []` in `extensions/composer.json` (see #3171)
 - Never add extra jQueries on the backend. (See #3177)
 - JS bugfixes (save button + goto publishing status) (See #3160)
 - Flush the cache if the Bolt version has changed (See #3183)
 - Fixed: Allow `|||`-queries to be more complex (Thanks @Pinpickle, see #3189)
 - Fixed: Storage not using sort from contenttype (Thanks @CarsonF, see #3187)
 - Change: Only log content not found errors if slug isn't numeric, since `next` and `previous` check by `id` (see #3186)
 - Fixed: Make sure we use `ParsedownExtra`, instead of just `Parsedown. (Thanks, @cooperaj, see #3194)
 - Fixed: Changelog content uses correct tablenames. (See 3198)
 - Added: Markdown fields now have a nice new editor, with Preview and fill screen / split screen functionality. (ee #3225)
 - Fixed: Normalising taxonomy before comparison for deleting old ones.(Thanks @silentworks, see #3224)
 - Change: Improve `shyphenate()`: Only add breaks to long words, instead of everywhere. (see #3221)
 - Added: Upload UX improvements part 2 - Progress bars. (Thanks @pinpickle, see #3218)
 - Fixed: Fix 'current' in menu. (see #3209)
 - Change: Use 4 spaces in all `.yml` and base-2014 theme. (see #3205)
 - Fixed: Set the canonical correctly. (see #3214)
 - Fixed: Make `BaseExtension::initialize()` non-abstract again to deal with PHP < 5.3.10 (See #3257)
 - Fixed: `isallowed` checks for extensions to also check for `extensions:config` (Thanks @SahAssar, see #3249)
 - Fixed: Strange step behaviour when no `min` is set for integer fields (Thanks @Pinpickle, see #3284)
 - Fixed: Make sure we have the same amount of columns, always. (See #3228)
 - Added: Allow for filtering on 'taxonomies' on the overview pages. (See #3278)
 - Added: Support for methods in `routing.yml` (see #3292)
 - Fixed: Publishing for items with 'Timed Publish' is working again. (Fixes #3279)
 - Added: Frontend requests should not set cookies. Remove them, to allow Varnish to do a better job of caching the request. (see #3309)
 - Added: Add exif aspect ratio and exif orientation data to imageinfo() (Thanks @Intendit,see #3308)
 - Fixed: Fix rendering in sidebar on mobile. (see #3246)
 - Added: New feature: Retina support for thumnbails (see bolt/bolt-thumbs/#19)
 - Added: Allow filtering in 'record.related' on other fields too. (Thanks @miguelavaqrod, see #3303)
 - Fixed: Fix path for non-ajaxy file-upload. (see #3303)
 - Fixed: Added extra check for "view permission" for quicklinks (Thanks @StevendeVries, see #3299)
 - Fixed: Make geolocation gracefully fail if google is not loaded (See #3356)
 - Added: Small UX improvement: Show spinner while doing ajaxy save. (See #3355)
 - Added: Use `PHPExif\Exif` for getting EXIF data in `TwigExtensions::imageinfo()` (See #3354)
 - Change: `slug` and `geolocation` fields refactored. Refactored out 'GoMap' dependancy. (See #3344)
 - Change: Fixed Scrutinizer config (See #3343)
 - Change: Allow explicit setting of a Contenttype's table name suffix (See #3342)
 - Fixed: Only setting default timezone if config provides it (See #3334)
 - Fixed: Fix for "timed depublish". (See #3330)
 - Fixed: [Tests] Move PHPUnit resource creation into the listener (See #3326)
 - Change: Make backend submenu-items the top level item, if there's only one sub-item. (See #3323, thanks Intendit)
 - Fixed: Fix rendering in sidebar on mobile. Fixes (See #3321)
 - Added: Allow filtering in 'record.related' on other fields too. (See #3320)
 - Fixed: Slugs generation fixed (See #3310)
 - Change: Refactor out `load.php`. (see #3371)
 - Change: Move CodeSniffer to a composer package (see #3365)
 - Fixed: Fixing small inconsistency in `permissions.yml.dist': 'editors' can browse uploaded files from within CKeditor now. (See #3357)
 - Make the removal / stripping of `&nbsp;` characters in CKEditor fields optional. (see #3373)
 - Fixed to handle correctly file requests with built-in server (Thanks, @pedronofuentes, see #3383)
 - Fix to use title and alt text on image field (Thanks @Shyim, see #3387)
 - Fixed: Allow editing of empty files. (Thanks, @SahAssar, see #3391)
 - Added: Include plugins "Styles Combo" and "Stylesheet Parser" in CKEditor (See #3384)
 - Added: Always have a fallback for a timezone when it isn't set in either php.ini or config.yml (See #3397)
 - Added: Ability to set a Email Sender Mail in config.yml (Thanks @Shyim, see #3409)
 - [Tests] Properly tidy the Codeception template field test (see #3451)
 - Check if folder exists first, when using it for uploads (See #3450)
 - [Codeception] Use a conditional version so 5.3 Travis builds won't fail. (See #3448)
 - Enhancement to define templates for the template chooser in backend. (Thanks Shyim, see #3447)
 - Allow 'duplicate' and 'delete' from contextual menu, when a Record has relationships. Fixes #3431
 - Don't trigger DBCheck for changed indexes. Fixes #3426
 - Only show the "delete" button if the page has been saved already. Fixes #3444
 - Fixes #3435 by disabling browser XSS protection for file editing. (See #3439, thanks timcooper)
 - Secondary menu refactoring (JS) + fixes for #2329 and #2347 (see #3433
 - Added: optional filter to select field with contenttype values. (see #3432)
 - Added: support for YAML repeated nodes (see #3430)
 - Fixed: PGSQL encoding settings in post connect event until doctrine/2.5.2 is out (see #3429)
 - Fixed: Change slug label when contenttype is viewless (See #3428, thanks Pinpickle)
 - Make Application::unsetSessionCookie() optional and BC friendly (see #3427)
 - Added: Config file `web.config` for IIS servers. (See #3423, thanks hyperTwitch)
 - Change: PGSQL encoding settings in post connect event until doctrine/2.5.2 is out. (See #3429)
 - Fixed: Change slug label when contenttype is viewless. (See #3428)
 - Added: add optional filter to select field with contenttype values (See #3432)
 - Fixed: Secondary menu refactoring (JS) (Fixes #2329 and #2347)
 - Fixed: prevent error message in `_sub_menu.twig` if `strict_variables` is set. (See #3462)
 - Security: Make sure we set the status correctly for 'async' requests. (See #3463)
 - Fixed: Set status explicitly on ajaxy requests. (See #3466)
 - Fixed: Bunch of small HTML5 validation errors. (See #3485)
 - Fixed: `attrib` for images. (See #3487)
 - Fixed: Fix pagination for searching via `getContent()`` or `{% setcontent %}`. (See #3496)
 - Fixed: Handle empty composer config, e.g. themes (See #3509)
 - Fixed: Use correct service key (See #3507)
 - Return to previous page, with paging and filtering. See #3588
 - Add a try-exception when parsing `allowtwig` fields.
 - Call abort for simpleredirect on "Save and return to overview".
 - Tweaking the delay, making behaviour bit nicer for "fast clickers". Fixes #3513
 - Fixes server-side error when ajaxy-deleting records that were created programmatically.
 - Prevent composer from sneaking in any `v2.7.0` symfony components. Remove `symfony/locale`.
 - Return to previous page, with paging and filtering. See #3588
 - Add a try-exception when parsing `allowtwig` fields.
 - Call abort for simpleredirect on "Save and return to overview".
 - Tweaking the delay, making behaviour bit nicer for "fast clickers". Fixes #3513
 - Fixes server-side error when ajaxy-deleting records that were created programmatically.
 - Prevent composer from sneaking in any `v2.7.0` symfony components. Remove `symfony/locale`.
 - Local extension autoloader See #3607
 - Request cache fix. See #3561
 - To make content 'stick' after saving, use `contentkey` instead of `key`. #3527
 - Make sure `$unserdata` is an array, and not merely `false`. See #3526
 - Handle Flysystem exception correctly when a file is not found. See #3519


Bolt 2.1.9
----------

Released 2015-04-29. Notable changes:

 - Fixed: `attrib` for images. (See #3487)
 - Fixed: Fix pagination for searching via `getContent()`` or `{% setcontent %}`. (See #3496)
 - Fixed: Use 'alt' instead of 'title' in Image fieldtype's attributes. (See #3505)


Bolt 2.1.8
----------

Released 2015-04-29. Notable changes:

 - Fix: prevent error message in `_sub_menu.twig` if `strict_variables` is set. (See #3462)
 - Security: Make sure we set the status correctly for 'async' requests. (See #3463)
 - Fixed: Set status explicitly on ajaxy requests. Fixes #3466

Bolt 2.1.7
----------

Released 2015-04-29. Notable changes:

 - Check if folder exists first, when using it for uploads (See #3450)
 - Allow 'duplicate' and 'delete' from contextual menu, when a Record has relationships. Fixes #3431
 - Don't trigger DBCheck for changed indexes. Fixes #3426
 - Make Application::unsetSessionCookie() optional and BC friendly (see #3427)
 - Make the removal / stripping of `&nbsp;` characters in CKEditor fields optional. (see #3373)
 - Fixed: Allow editing of empty files. (Thanks, @SahAssar, see #3391)
 - Added: Always have a fallback for a timezone when it isn't set in either php.ini or config.yml (See #3394)
 - Only show the "delete" button if the page has been saved already. Fixes #3444

Bolt 2.1.6
----------

Released 2015-04-13. Notable changes:

 - Fixed: Slugs generation with `uses:` fixed (see #3310)
 - Added: Frontend requests should not set cookies. Remove them, to allow Varnish to do a better job of caching the request. (see #3309)
 - Added: Add exif aspect ratio and exif orientation data to imageinfo() (Thanks @Intendit,see #3308)
 - Fixed: Fix rendering in sidebar on mobile. (see #3246)
 - Added: New feature: Retina support for thumnbails (see bolt/bolt-thumbs/#19)
 - Added: Allow filtering in 'record.related' on other fields too. (Thanks @miguelavaqrod, see #3303)
 - Fixed: Fix path for non-ajaxy file-upload. (see #3303)
 - Fixed: Added extra check for "view permission" for quicklinks (Thanks @StevendeVries, see #3299)
 - Change: Frontend requests should not set cookies. Remove them, to allow Varnish to do a better job of caching the request. (See #3309)
 - Fixed: Fix rendering in sidebar on mobile. Fixes (See #3321)
 - Fixed: Fix for "timed depublish". (See #3330)
 - Fixed: Only setting default timezone if config provides it (See #3334)
 - Added: Small UX improvement: Show spinner while doing ajaxy save. (See #3355)
 - Fixed: Fixing small inconsistency in `permissions.yml.dist': 'editors' can browse uploaded files from within CKeditor now. (See #3357)
 - Fix: People who try installing Bolt on PHP 5.1 or 5.2 will now get a nice and friendly notice that Bolt won't work. (see #3371)

Bolt 2.1.5
----------

Released 2015-04-01. Notable changes:

 - Fixed: Strange step behaviour when no `min` is set for integer fields (Thanks @Pinpickle, see #3284)
 - Fixed: Make sure we have the same amount of columns, always. (See #3228)
 - Added: Allow for filtering on 'taxonomies' on the overview pages. (See #3278)
 - Added: Support for methods in `routing.yml` (see #3292)
 - Fixed: Publishing for items with 'Timed Publish' is working again. (Fixes #3279)

Bolt 2.1.4
----------

Released 2015-03-27. Notable changes:

 - Never add extra jQueries on the backend. (See #3177)
 - JS bugfixes (save button + goto publishing status) (See #3160)
 - Flush the cache if the Bolt version has changed (See #3183)
 - Fixed: Allow `|||`-queries to be more complex (Thanks @Pinpickle, see #3189)
 - Fixed: Storage not using sort from contenttype (Thanks @CarsonF, see #3187)
 - Change: Only log content not found errors if slug isn't numeric, since `next` and `previous` check by `id` (see #3186)
 - Fixed: Make sure we use `ParsedownExtra`, instead of just `Parsedown`. (Thanks, @cooperaj, see #3194)
 - Fixed: Changelog content uses correct tablenames. (See 3198)
 - Change: Improve `shyphenate()`: Only add breaks to long words, instead of everywhere. (see #3221)
 - Fixed: Fix 'current' in menu. (see #3209)
 - Fixed: `isallowed` checks for extensions to also check for `extensions:config` (Thanks @SahAssar, see #3249)
 - Fixed: Allow 'name' in contenttype to override translation, for 2.1.x (see #3259)
 - Fixed: Make `BaseExtension::initialize()` non-abstract again to deal with PHP < 5.3.10 (See #3257)

Bolt 2.1.3
----------

Released 2015-03-18. Notable changes:

 - Added: Added an option to delete a record, when editing it. (See #3134)
 - Removed: removed "frontend permission checks". (#see 3133)
 - Fixed: Prevent extra spaces in excerpts. (See #3130)
 - Fixed: Show notice on update of Bolt. (See #3129)
 - Fixed: Make dashboard activity log autoupdate again (see #3126)
 - Added: Make the sanitisation of markdown fields configurable. (see #2992 #3142)
 - Fixed: Fixed z-index of sidebar. (See #3100)
 - Fixed: Disable "revert" button on 'edit file' screen, when file is not wrtiable. (See #3009)
 - Added: Allow for multiple (fallback) locales in `config.yml`. (Thanks @sintemaa, see #3127)
 - Fixed: Warning for unsaved content comes up when nothing has been changed (see #3077)
 - Fixed: Be a little more strict in picking 'selected' options: Only use the fallback, if there's no valid id set.
 - Change: Lock composer.json to Symfony 2.6.4 as 2.6.5 fails PHPUnit
 - Added: Re-added standalone jQuery lib, as some extensions might need it.
 - Fixed: Workaround, so we don't break on installations with `"require": []` in `extensions/composer.json` (see #3171)


Bolt 2.1.1
----------

Released 2015-03-12. Notable changes:

 - Added: Stop Finder from recursing common build folders and place a limit on the maximum depth it will recurse otherwise. (Thanks @Cooperaj, see #3069)
 - Fixed: Removing default taxonomylink route leads to exception (See #3070)
 - Fixed: Don't reset urls when adding base path. (See #3074)
 - Fixed: Whoops error when duplicating a record. (See #3064)
 - Fixed: Fixes broken extension installer (See #3086)
 - Fixed: Redirect for backend trailing slash redirect (`/bolt` -> `/bolt/`) (See #3083)
 - Fixed: Regression that errored on PHP < 5.3.6: `Remove SplFileInfo::getExtension()`. (See #3095)
 - Fixed: Extension theme installer working properly (see #3108, thanks @nikgo)
 - Fixed: Replacing `&nbsp;` with single space, instead of nothing. (See #3111)


Bolt 2.1.0
----------

Released 2015-03-09. Notable changes:

- Added: Allow for `https://` protocol in `canonical` setting in config.yml. (see #3044)
- Added: Taiwanese (zh_TW) localisation. (#3022, thanks @Leon0824)
- Fixed: Update CKEditor field objects if they exist on AJAX content saves. (See #2998)
- Added: A logging record for extension update and uninstall (see #2993)
- Added: Client-side validation (first for floats only). (see #2997)
- Change: Float field now not html5 number field anymore, and both `,` and `.` are allowed as decimal seperator.
- Change: The distribution now includes `composer.json.dist` and  `composer.lock.dist` files, if you need them.
- Added: Allow extensions to be used as controllers (non static) (see #2971)
- Fixed: Long conttenttype names are truncated properly in the sidebar now. (See #2513)
- Fixed: Don't leak Database credentials on connection error during set up. (See #2538)
- Change: Remove unused jquery-catchpaste.
- Change: Many changes (for the better) to logging: Monolog, improved UI, separation of concerns.
- Refactor: Many changes and improvements to the Config object.
- Refactor: Major cleanup in Bolt\Storage, Bolt\Events\StorageEvents and Bolt\Content (#2664)
- **Updated: PHPUnit now covers complete code base** (#2542, thanks @rossriley)
- **Updated: Extensions interface had major overhaul and now uses the Composer API more extensively and provides better error handling for AJAX calls on the Extend page** (#2543 thanks @GawainLynch)
- **Update: Bolt's custom logging provider has been replaced with Monolog** (#2546, thanks @GawainLynch)
- Added: Extension repo as service: extracts the queries of the Extensions repo to a separate service provider.
 (#2550 thanks @rossriley)
- Added: BASH/ZSH command completion for Nut (see #2657)
- Updated: Magnific popup is now at 1.0.0. (#2560, thanks @cdowdy)
- Updated: FlySystem from version 0.5 to 1.1, with php5.3 patch. (#2587)
- Fixed: arrays in type:select fields. (#2609)
- Added: Allow for `keys: slug` in `type: select` fields, to customize the used field that's actually stored in the DB. (#2597)
- Fixed: Small logic fix for 'groupingSort'. (See #2520)
- Fixed: Have `Cache::clearCache()` use $app['resources']->getPath('root') for the 'thumbs' directory (See #2512)
- Fixed: Corner case bug in password reset (See #2616)
- Added: Editing content now shows recent changes that have been logged (if enabled) that link to the change comparison (See #2620)
- Fixed: Minor HTML fix and broken link in base-2015 theme (#2650, thanks @apatkinson)
- Fixed: Nest folders in cache 2 deep. (see #2644)
- Fixed: bug fixed in "Select in all items" in overview. (See #2669)
- Fixed: Fix filebrowser route binding name to be 'filebrowser' (See #2680)
- Fixed: Allow setting of regex pattern, replacement and case of uploaded file names (See #2691)
- Fixed: Regression that would break the ability to set global Twig variables in an extension (See #2717)
- Changed: Enforce SSL Config Change. Now we use only `enforce_ssl`, `cookies_https_only` is deprecated. (See #2726, thanks @dwolf555)
- Fixed: Flipped array replace arguments in `parseFieldsAndGroups()`. (See #2738)
- **Fixed: No more unwanted `&nbsp;`'s in CKeditor.** Fixes #2660
- Fixed: Logged in user can no longer disable themselves
- Fixed: Disabling a logged in user with force a logout
- Fixed: Fixed a bug with some utf8 characters becoming question marks after saving to database. (Thanks @dwr, See #2804)
- Fixed: Fix #2424 and other tab group improvements #2801 (TODO: Specify!)
- Added: Installed extensions now defaults to adding version constraints to allow for easier updating
- Change: The `X-Frame-Options`-header is now only sent for backend pages, and can be disabled in `config.yml` (See #2825)
- Change: Bolt now distinguishes between 'regular news' and 'alerts' on the Dashboard screen. This way, we can better notify people in case of an urgent security issue. (See #2830)
- Fixed: The built-in anti-CSRF token was renamed to `bolt_csrf_token` to prevent clashes when a user has a field named `token`. (See #2831)
- Change: You can now use `{id}` in routes for records instead of `{slug}`, if you wish to have links to records using the id. (See #2832)

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
- Added: You can now use multiple slugs in a single contenttype, should you want to. (#2490)
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
  delete multiple items, and view full-size images. (Thanks @Pinpickle, See #2360)
- Added: proportional autoscaling option to showImage() using '0' values
- Added: Use jshint in grunt workflow
- Added: Whoops now sends errors to AJAX callers in JSON format (#2433)
- Fixed: Bug where contenttype name in menu isn't translated
- Fixed: **No CSS / static files shown** when using PHP's built-in server via ./serve (#2381)
- Fixed: Reference of IntlDateFormatter stub functions (#2415)
- Fixed: Magnific popup image preview on image lists (#2443)
- Fixed: Added 'pasteZone: null', which prevents triggering an upload when pasting from Word in Chrome. (#2427)
- Fixed: Pager works correctly for paging categories and other taxonomies (#2468)
- Fixed: Only apply pagination to `setcontent`, when 'paging' is required / requested (#2417)
- Fixed: Select field type, with values from another content type now always uses the 'id' as the value in the DB. (#2465)
- Fixed: When session is invalidated due to changing your own username, redirect to login screen. (#2457)
- Updated: CKeditor to 4.4.6, and all used plugins to the latest versions
- Updated: jQuery to version 1.11.2
- Updated: jQuery goMap to 1.3.3  (#2377)
- Updated: Unify image- and filelists
- Updated: Make image- and filelists looking more flat
- Change: Set default width/height of showImage() to '0'
- Change: Fields that previously used 0000-00-00 and 1900-01-01 now default to using NULL instead (#2396)
- Change: Uglify bolts js files to bolt.min.js (#2398)
- Change: Priorities of Taxonomy listing template selection (#2420)
- Change: Separate magnific-popup between app and theme (#2429)
- Change: Also use XMLHttpRequest to detect AJAX requests in `getWhichEnd()` (#2423)
- Change: Refactor Twig setup (Thanks @CarsonF, see #2430)
- Change: Url matcher updates (Thanks @CarsonF, see #2431)
- Change: Add a data() Twig function to allow storing of data to be passed en masse to JavaScript (#2458)
- Removed: Removed the `base-2013` theme
- Removed: Ancient browser-specific tags
- Change: System activity and change log permissions have changed and users now require systemlog and/or changelog permissions in permissions.yml (See #2805)

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
- Implemented "viewless" property for contenttypes. Fixes #2149
- IE9: Javascript Dropdowns #2195
- Set twig globals on 404 pages. Fixes #2198
- Show Stack on "Browse Server" for images. Fixes #2235
- Added "Copy to themes" button in Extend
- Force json response to send text/plain header. Fixes uploading images in IE9.
- Transparent button on "focus". Tabbing is visible again, helps with accessibility
- Add "roles" button to users screen
- Allowing the extensions site to bet set in config.yml
- confirm extension delete/removal/uninstall
- Refuse to display Bolt in an iframe, to prevent possible clickjacking. See #2197. Thanks, @narendrabhati
- Editcontent dates are now stored as entered, set the timezone in config.yml
- Paging now works correctly for taxonomy-listings
- Make ckeditor use new global locale setting #2087
- Contenttype submenu labels are now translated
- Store Geolocation fields, if only the coordinates are set. Make sure the view initialises, if on a second tab. Fixes #2172
- Set moments locale before initialise
- proxy twig functions to separate twig extension object
- Disallow snippets on extend sub-requests
- Fix moments and remove it from global space
- Fix notices when translation files are not found
- Follow symlinks when loading local extensions
- Responsive video. Fixes #1916
- Set the correct mime type for woff2 font type
- Tweaking the fonts, goes with #2099
- Fix #2085 allow editing of broken Yaml file
- Actually load the _local file for extensions
- Add register_shutdown_function() to bootstrap for earliest init we can get
- Cleanups in composer.json. Almost got rid of all "dev-master" and "@dev"
- Add an "ungrouped" tab, for when some fields are defined without a group. Fixes #2080
- When sorting with "behaves_like: grouping", order '0' was ignored. Fixes #2112
- Making the form validation notices a bit more in line with our other notices
- Finally workaround the cron interval column removal
- Disable buttons and install section if offline
- Have Extend cope with offline network connections
- Show other files than images in "async browse". Fixes #2136
- Check dates in fromPost instead of in setValues
- Add backend FlashBag messages on various extension loading failures
- Don't reset depublish column when depublishing
- Don't autoDepublish again for later edits
- If you sort by a column, it will also sort the "recent" menu items using the same sort
- Fix time input being displayed for date only fields
- Add "config": {"discard-changes": true} to extensions/composer.json so that changed files will get overridden/overwriten on extension package update instead of silently failing
- Fix "Only variables should be passed by reference"-notice. Fixes #2209
- Parse markdown fields for excerpts. Fixes #2246
- Sigh … If we have more relations, make sure we keep them all. See #2255
- When looking for Twig template file names, also include any character that is not a vertical whitespace character
- image and showimage wrong index #2275
- Logic fix in timestamp for theme/config.yml check


Bolt 2.0 "Beta 3"
-----------------

Released 2014-11-04. Notable changes:

- Updated Moments.js to 2.8.3. Use moment.locale() instead of deprecated moment.lang() #2088
- Fixed: Simplified Html::trimText(), "excerpt" now works better on non-western-european strings
- Fixed: Breadcrumbs in "edit file" screens work correctly now. #2077
- Fixed: Proper sorting in Backend overview. Fixes #2036
- Fixed: "open_basedir restriction in effect" error related to Composer
- Fixed: "File(`/dev/urandom`) is not within the allowed path(s)" error
- Added: min/max/step options for float and integer fieldtypes
- Switching from Googlefonts to our local version of Source Sans Pro. Fixes #2038
- Ongoing fixes and changes to the translation files. (and added Chinese)
- A bunch of fixes to the automatic acceptance tests
- Fixed: Editable record list calls wrong listing template (for related content) #2028
- Added: Javascript form validation #2019
- Added: custom `error: "message"` for use with javascript form validation
- Fixed: Fix notice in `SearchPlugin::handle()` #2025
- Added: Added hints generation for removed columns in dbcheck
- Fixed: Exception when viewing related items #2026
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
