Bolt 2.1.5
----------

Not yet released. Notable changes: 

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
