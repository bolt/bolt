Bolt 2.0 DEV-head
-----------------

- Added: **integration of Symfony's VarDumper component**. The old 'Dumper::dump' is still
  present, for backwards compatibility
- Added: option to disable dashboard news
- Added: Improvements to Imagelist and Filelist fields: Better selection and re-ordering,
  delete multiple items, and view full-size images. (Thanks @Pinpickle, See #2360)
- Fixed: bug where contenttype name in menu isn't translated
- Updated: CKeditor to 4.4.6, and all used plugins to the latest versions.
- Removed: ancient browser-specific tags
- Updated: jQuery to version 1.11.2.

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
