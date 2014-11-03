Bolt 2.0 "Beta 3" 
-----------------

Work in progress. Notable changes: 

- Fixed: Proper sorting in Backend overview. Fixes #2036
- Fixed: "open_basedir restriction in effect" error related to Composer.
- Fixed: "File(`/dev/urandom`) is not within the allowed path(s)" error. 
- Added: min/max/step options for float and integer fieldtypes
- Switching from Googlefonts to our local version of Source Sans Pro. fixes #2038
- Ongoing fixes and changes to the translation files. (and added Chinese)
- A bunch of fixes to the automatic acceptance tests
- Fixed: Editable record list calls wrong listing template (for related content) #2028
- Added: Javascript form validation #2019
- Added: custom `error: "message"` for use with javascript form validation
- Fixed: Fix notice in `SearchPlugin::handle()` #2025
- Added: Added hints generation for removed columns in dbcheck 
- Fixed: Exception when viewing related items #2026
- Uploads from the "files" screens upload to the correct folder, instead of always to `files/`
- Updated HTML/CSS for the "Changelog" screen.
- Added Pathogen, in order to handle paths on Windows systems better ..
- .. and immediately factored out [Isolator](https://github.com/IcecaveStudios/isolator), because that shit's just wrong, man. 

Bolt 2.0 "Beta 2" 
-----------------

Released 2014-11-29

- Ongoing fixes to the 'Translation' module (for the backend): extra labels, updated translations, code cleanup
- Ongoing fixes to the 'Paths' module (for the backend): Fixed some missing paths and edge-cases.
- installing "Extensions" works much better on Windows servers now.
- Refactor: Translating using `__( )` has been moved to it's own class
- Refactor: Refactored `lib.php` into a proper class.
- Usage of 'icons' in various `.yml` files has been tweaked to make them futureproof. 
- Installing a theme copies `config.yml.dist` to `config.yml` in the new folder now.
- Stack upload button does not work  Blocking release bug
- Error in 'date' and 'datetime' fields fixed. Datepicker works correctly for a wider range of languages now.
- The "templateselect" field in records now does actually select that template to render the pages. 
- Cleanup a lot of issues in the code, as reported by [Sensiolabs Insight](https://insight.sensiolabs.com/projects/4d1713e3-be44-4c2e-ad92-35f65eee6bd5)
- CSS / HTML fixes in 'users' and 'edit file' screens
- fix to filesystem locations for asset installer  
- Jumping to "current status" from Edit Record screen works correctly now.

Bolt 2.0 "Beta Boltcamp" 
------------------------

Released 2014-10-19. Notable changes: 

- Everything[.](http://sandvoxxcheap.com/wp-content/uploads/2014/07/ZPWb7iM.gif) 
