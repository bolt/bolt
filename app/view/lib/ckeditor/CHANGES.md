CKEditor 4 Changelog
====================

## CKEditor 4.1

* [#10192](http://dev.ckeditor.com/ticket/10192): Closing lists with Enter key does not work with Advanced Content Filter in several cases.
* [#10191](http://dev.ckeditor.com/ticket/10191): Fixed allowed content rules unification, so the `filter.allowedContent` property always contains rules in the same format.
* [#10224](http://dev.ckeditor.com/ticket/10224): Advanced Content Filter does not remove non-empty `<a>` elements anymore.
* Minor issues in plugin integration with Advanced Content Filter:
  * [#10166](http://dev.ckeditor.com/ticket/10166): Added transformation from the `align` attribute to `float` style to preserve backward compatibility after the introduction of Advanced Content Filter.
  * [#10195](http://dev.ckeditor.com/ticket/10195): Image plugin no longer registers rules for links to Advanced Content Filter.
  * [#10213](http://dev.ckeditor.com/ticket/10213): Justify plugin is now correctly registering rules to Advanced Content Filter when `config.justifyClasses` is defined.

## CKEditor 4.1 RC

* [#9829](http://dev.ckeditor.com/ticket/9829): Data and features activation based on editor configuration.

  Brand new data filtering system that works in 2 modes:

  * based on loaded features (toolbar items, plugins) - the data will be filtered according to what the editor in its
  current configuration can handle,
  * based on `config.allowedContent` rules - the data will be filtered and the editor features (toolbar items, commands,
  keystrokes) will be enabled if they are allowed.

  See the `datafiltering.html` sample, [guides](http://docs.ckeditor.com/#!/guide/dev_advanced_content_filter) and [`CKEDITOR.filter` API documentation](http://docs.ckeditor.com/#!/api/CKEDITOR.filter).
* [#9387](http://dev.ckeditor.com/ticket/9387): Reintroduced "Shared Spaces" - the ability to display toolbar and bottom editor space in selected locations and to share them by different editor instances.
* [#9907](http://dev.ckeditor.com/ticket/9907): Added the `contentPreview` event for preview data manipulation.
* [#9713](http://dev.ckeditor.com/ticket/9713): Introduced the `sourcedialog` plugin that brings raw HTML editing for inline editor instances.
* Included in [#9829](http://dev.ckeditor.com/ticket/9829): Introduced new events, `toHtml` and `toDataFormat`, allowing for better integration with data processing. See API documentation: [`toHtml`](http://docs.ckeditor.com/#!/api/CKEDITOR.editor-event-toHtml), [`toDataFormat`](http://docs.ckeditor.com/#!/api/CKEDITOR.editor-event-toDataFormat).
* [#9981](http://dev.ckeditor.com/ticket/9981): Added ability to filter `htmlParser.fragment`, `htmlParser.element` etc. by many `htmlParser.filter`s before writing structure to an HTML string.
* Included in [#10103](http://dev.ckeditor.com/ticket/10103):
  * Introduced the `editor.status` property to make it easier to check the current status of the editor. See [API documentation](http://docs.ckeditor.com/#!/api/CKEDITOR.editor-property-status).
  * Default `command` state is now `CKEDITOR.TRISTATE_DISABLE`. It will be activated on `editor.instanceReady` or immediately after being added if the editor is already initialized.
* [#9796](http://dev.ckeditor.com/ticket/9796): Introduced `<s>` as a default tag for strikethrough, which replaces obsolete `<strike>` in HTML5.

## CKEditor 4.0.3

* [#10196](http://dev.ckeditor.com/ticket/10196): Fixed context menus not opening with keyboard shortcuts when Autogrow is enabled.
* [#10212](http://dev.ckeditor.com/ticket/10212): [IE7-10] Undo command throws errors after multiple switches between Source and WYSIWYG view.
* [#10219](http://dev.ckeditor.com/ticket/10219): [Inline editor] Error thrown after calling editor.destroy().

## CKEditor 4.0.2

* [#9779](http://dev.ckeditor.com/ticket/9779): Fixed overriding `CKEDITOR.getUrl` with `CKEDITOR_GETURL`.
* [#9772](http://dev.ckeditor.com/ticket/9772): Custom buttons in dialog window footer have different look and size (Moono, Kama).
* [#9029](http://dev.ckeditor.com/ticket/9029): Custom styles added with `styleSet.add()` are displayed in wrong order.
* [#9887](http://dev.ckeditor.com/ticket/9887): Disable magicline when `editor.readOnly` is set.
* [#9882](http://dev.ckeditor.com/ticket/9882): Fixed empty document title on `getData()` if set via the Document Properties dialog window.
* [#9773](http://dev.ckeditor.com/ticket/9773): Fixed rendering problems with selection fields in the Kama skin.
* [#9851](http://dev.ckeditor.com/ticket/9851): The `selectionChange` event is not fired when mouse selection ended outside editable.
* [#9903](http://dev.ckeditor.com/ticket/9903): [Inline editor] Bad positioning of floating space with page horizontal scroll.
* [#9872](http://dev.ckeditor.com/ticket/9872): `editor.checkDirty()` returns `true` when called onload. Removed the obsolete `editor.mayBeDirty` flag.
* [#9893](http://dev.ckeditor.com/ticket/9893): Fixed broken toolbar when editing mixed direction content in Quirks mode.
* [#9845](http://dev.ckeditor.com/ticket/9845): Fixed TAB navigation in the Link dialog window when the Anchor option is used and no anchors are available.
* [#9883](http://dev.ckeditor.com/ticket/9883): Maximizing was making the entire page editable with divarea-based editors.
* [#9940](http://dev.ckeditor.com/ticket/9940): [Firefox] Navigating back to a page with the editor was making the entire page editable.
* [#9966](http://dev.ckeditor.com/ticket/9966): Fixed: Unable to type square brackets with French keyboard layout. Changed magicline keystrokes.
* [#9507](http://dev.ckeditor.com/ticket/9507): [Firefox] Selection is moved before editable position when the editor is focused for the first time.
* [#9947](http://dev.ckeditor.com/ticket/9947): [Webkit] Editor overflows parent container in some edge cases.
* [#10105](http://dev.ckeditor.com/ticket/10105): Fixed: Broken sourcearea view when an RTL language is set.
* [#10123](http://dev.ckeditor.com/ticket/10123): [Webkit] Fixed: Several dialog windows have broken layout since the latest Webkit release.
* [#10152](http://dev.ckeditor.com/ticket/10152): Fixed: Invalid ARIA property used on menu items.

## CKEditor 4.0.1.1

* Security update: Added protection against XSS attack and possible path disclosure in PHP sample.

## CKEditor 4.0.1

Fixed issues:

* [#9655](http://dev.ckeditor.com/ticket/9655): Support for IE Quirks Mode in new Moono skin.
* Accessibility issues (mainly on inline editor): [#9364](http://dev.ckeditor.com/ticket/9364), [#9368](http://dev.ckeditor.com/ticket/9368), [#9369](http://dev.ckeditor.com/ticket/9369), [#9370](http://dev.ckeditor.com/ticket/9370), [#9541](http://dev.ckeditor.com/ticket/9541), [#9543](http://dev.ckeditor.com/ticket/9543), [#9841](http://dev.ckeditor.com/ticket/9841), [#9844](http://dev.ckeditor.com/ticket/9844).
* Magic-line:
    * [#9481](http://dev.ckeditor.com/ticket/9481): Added accessibility support for Magic-line.
    * [#9509](http://dev.ckeditor.com/ticket/9509): Added Magic-line support for forms.
    * [#9573](http://dev.ckeditor.com/ticket/9573): Magic-line doesn't disappear on `mouseout` in the specific case.
* [#9754](http://dev.ckeditor.com/ticket/9754): [Webkit] Cut & paste simple unformatted text generates inline wrapper in Webkits.
* [#9456](http://dev.ckeditor.com/ticket/9456): [Chrome] Properly paste bullet list style from MS-Word.
* [#9699](http://dev.ckeditor.com/ticket/9699), [#9758](http://dev.ckeditor.com/ticket/9758): Improved selection locking when selecting by dragging.
* Context menu:
    * [#9712](http://dev.ckeditor.com/ticket/9712): Context menu open destroys editor focus.
    * [#9366](http://dev.ckeditor.com/ticket/9366): Context menu should be displayed over floating toolbar.
    * [#9706](http://dev.ckeditor.com/ticket/9706): Context menu generates JS error in inline mode when editor attached to header element.
* [#9800](http://dev.ckeditor.com/ticket/9800): Hide float panel when resizing window.
* [#9721](http://dev.ckeditor.com/ticket/9721): Padding in content of div based editor puts editing area under bottom UI space.
* [#9528](http://dev.ckeditor.com/ticket/9528): Host page's `box-sizing` style shouldn't influence editor UI elements.
* [#9503](http://dev.ckeditor.com/ticket/9503): Forms plugin adds context menu listeners only on supported input types. Added support for `tel, email, search` and `url` input types.
* [#9769](http://dev.ckeditor.com/ticket/9769): Improved floating toolbar positioning in narrow window.
* [#9875](http://dev.ckeditor.com/ticket/9875): Table dialog doesn't populate width correctly.
* [#8675](http://dev.ckeditor.com/ticket/8675): Deleting cells in nested table removes outer table cell.
* [#9815](http://dev.ckeditor.com/ticket/9815): Can't edit dialog fields on editor initialized in jQuery UI modal dialog.
* [#8888](http://dev.ckeditor.com/ticket/8888): CKEditor dialogs do not show completely in small window.
* [#9360](http://dev.ckeditor.com/ticket/9360): [Inline editor] Blocks shown for a div stay permanently even after user exists editing the div.
* [#9531](http://dev.ckeditor.com/ticket/9531): [Firefox & Inline editor] Toolbar is lost when closing format combo by clicking on its button.
* [#9553](http://dev.ckeditor.com/ticket/9553): Table width incorrectly set when `border-width` style is specified.
* [#9594](http://dev.ckeditor.com/ticket/9594): Cannot tab past CKEditor when it is in read only mode.
* [#9658](http://dev.ckeditor.com/ticket/9658): [IE9] Justify not working on selected image.
* [#9686](http://dev.ckeditor.com/ticket/9686): Added missing contents styles for `<pre>`.
* [#9709](http://dev.ckeditor.com/ticket/9709): PasteFromWord should not depend on configuration from other styles.
* [#9726](http://dev.ckeditor.com/ticket/9726): Removed color dialog dependency from table tools.
* [#9765](http://dev.ckeditor.com/ticket/9765): Toolbar Collapse command documented incorrectly on Accessibility Instructions dialog.
* [#9771](http://dev.ckeditor.com/ticket/9771): [Webkit & Opera] Fixed scrolling issues when pasting.
* [#9787](http://dev.ckeditor.com/ticket/9787): [IE9] onChange isn't fired for checkboxes in dialogs.
* [#9842](http://dev.ckeditor.com/ticket/9842): [Firefox 17] When we open toolbar menu for the first time & press down arrow key, focus goes to next toolbar button instead of menu options.
* [#9847](http://dev.ckeditor.com/ticket/9847): Elements path shouldn't be initialized on inline editor.
* [#9853](http://dev.ckeditor.com/ticket/9853): `Editor#addRemoveFormatFilter` is exposed before it really works.
* [#8893](http://dev.ckeditor.com/ticket/8893): Value of `pasteFromWordCleanupFile` config is now taken from instance configuration.
* [#9693](http://dev.ckeditor.com/ticket/9693): Removed "live preview" checkbox from UI color picker.


## CKEditor 4.0

The first stable release of the new CKEditor 4 code line.

The CKEditor JavaScript API has been kept compatible with CKEditor 4, whenever
possible. The list of relevant changes can be found in the [API Changes page of
the CKEditor 4 documentation][1].

[1]: http://docs.ckeditor.com/#!/guide/dev_api_changes "API Changes"
