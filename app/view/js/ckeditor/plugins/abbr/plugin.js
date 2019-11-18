/**
 * Copyright (c) 2014-2018, CKSource - Frederico Knabben. All rights reserved.
 * Licensed under the terms of the MIT License (see LICENSE.md).
 *
 * Basic sample plugin inserting abbreviation elements into the CKEditor editing area.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/ckeditor4/docs/#!/guide/plugin_sdk_sample_1
 */

// Register the plugin within the editor.
CKEDITOR.plugins.add( 'abbr', {

	// Register the icons.
	icons: 'abbr',

	// The plugin initialization logic goes inside this method.
	init: function( editor ) {

		// Define an editor command that opens our dialog window.
		editor.addCommand( 'abbr', new CKEDITOR.dialogCommand( 'abbrDialog', {

			// Allow the abbr tag with an optional title attribute.
			allowedContent: 'abbr[title,id]',

			// Require the abbr tag to be allowed for the feature to work.
			requiredContent: 'abbr',

			// Prefer abbr over acronym. Transform acronym elements into abbr elements.
			contentForms: [
				'abbr',
				'acronym'
			]
		} ) );

		// Create a toolbar button that executes the above command.
		editor.ui.addButton( 'Abbr', {

			// The text part of the button (if available) and the tooltip.
			label: 'Insert Abbreviation',

			// The command to execute on click.
			command: 'abbr',

			// The button placement in the toolbar (toolbar group name).
			toolbar: 'insert'
		});

		if ( editor.contextMenu ) {
			
			// Add a context menu group with the Edit Abbreviation item.
			editor.addMenuGroup( 'abbrGroup' );
			editor.addMenuItem( 'abbrItem', {
				label: 'Edit Abbreviation',
				icon: this.path + 'icons/abbr.png',
				command: 'abbr',
				group: 'abbrGroup'
			});

			editor.contextMenu.addListener( function( element ) {
				if ( element.getAscendant( 'abbr', true ) ) {
					return { abbrItem: CKEDITOR.TRISTATE_OFF };
				}
			});
		}

		// Register our dialog file -- this.path is the plugin folder path.
		CKEDITOR.dialog.add( 'abbrDialog', this.path + 'dialogs/abbr.js' );
	}
});
