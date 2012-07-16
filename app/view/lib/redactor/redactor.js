/*
	Redactor v7.7.1
	Updated: July 7, 2012
	
	http://redactorjs.com/
	
	Copyright (c) 2009-2012, Imperavi Ltd.
	License: http://redactorjs.com/license/
	
	Usage: $('#content').redactor();
*/

if (typeof RELANG === 'undefined')
{
	var RELANG = {};
}

var RLANG = {
	html: 'HTML',
	video: 'Insert Video...',
	image: 'Insert Image...',
	table: 'Table',
	link: 'Link',
	link_insert: 'Insert Link ...',
	unlink: 'Unlink',
	formatting: 'Formatting',
	paragraph: 'Paragraph',
	quote: 'Quote',
	code: 'Code',
	header1: 'Header 1',
	header2: 'Header 2',
	header3: 'Header 3',
	header4: 'Header 4',
	bold:  'Bold',
	italic: 'Italic',
	fontcolor: 'Font Color',
	backcolor: 'Back Color',
	unorderedlist: 'Unordered List',
	orderedlist: 'Ordered List',
	outdent: 'Outdent',
	indent: 'Indent',
	cancel: 'Cancel',
	insert: 'Insert',
	save: 'Save',
	_delete: 'Delete',
	insert_table: 'Insert Table...',
	insert_row_above: 'Add Row Above',
	insert_row_below: 'Add Row Below',
	insert_column_left: 'Add Column Left',
	insert_column_right: 'Add Column Right',
	delete_column: 'Delete Column',
	delete_row: 'Delete Row',
	delete_table: 'Delete Table',
	rows: 'Rows',
	columns: 'Columns',	
	add_head: 'Add Head',
	delete_head: 'Delete Head',	
	title: 'Title',
	image_position: 'Position',
	none: 'None',
	left: 'Left',
	right: 'Right',
	image_web_link: 'Image Web Link',
	text: 'Text',
	mailto: 'Email',
	web: 'URL',
	video_html_code: 'Video Embed Code',
	file: 'Insert File...',	
	upload: 'Upload',
	download: 'Download',
	choose: 'Choose',
	or_choose: 'Or choose',
	drop_file_here: 'Drop file here',
	align_left:	'Align Left',	
	align_center: 'Align Center',
	align_right: 'Align Right',
	align_justify: 'Justify',
	horizontalrule: 'Insert Horizontal Rule',
	fullscreen: 'Fullscreen',
	deleted: 'Deleted',
	anchor: 'Anchor'
};




(function($){
	
	"use strict";
	
	// Plugin
	jQuery.fn.redactor = function(option)
	{
		return this.each(function() 
		{
			var $obj = $(this);
			
			var data = $obj.data('redactor');
			if (!data) 
			{
				$obj.data('redactor', (data = new Redactor(this, option)));
			}
		});
	};
	
	
	// Initialization
	var Redactor = function(element, options)
	{
		// Element
		this.$el = $(element);
		
		// Lang
		if (typeof options !== 'undefined' && typeof options.lang !== 'undefined' && options.lang !== 'en' && typeof RELANG[options.lang] !== 'undefined') 
		{
			RLANG = RELANG[options.lang];
		}		
	
		// Options
		this.opts = $.extend({
	
			lang: 'en',
			direction: 'ltr', // ltr or rtl

			callback: false, // function
			keyupCallback: false, // function
			keydownCallback: false, // function
			execCommandCallback: false, // function
		
			path: false,
			css: 'style.css',
			focus: false,
			resize: true,
			autoresize: false,
			fixed: false,
	
			autoformat: true,
			cleanUp: true,
			removeStyles: false,
			removeClasses: false,
			convertDivs: true,
			convertLinks: true,
			xhtml: false,
			
			handler: false, // false or url
			
			autosave: false, // false or url
			interval: 60, // seconds
	
			imageGetJson: false, // url (ex. /folder/images.json ) or false

			imageUpload: false, // url
			imageUploadCallback: false, // function
			
			fileUpload: false, // url
			fileUploadCallback: false, // function

			observeImages: true,
			visual: true,
			fullscreen: false,
			overlay: true, // modal overlay
			
			buttonsCustom: {},
			buttonsAdd: [],
			buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
					'image', 'video', 'file', 'table', 'link', '|',
					'fontcolor', 'backcolor', '|', 'alignleft', 'aligncenter', 'alignright', 'justify', '|',
					'horizontalrule', 'fullscreen'],

			colors: [
				'#ffffff', '#000000', '#eeece1', '#1f497d', '#4f81bd', '#c0504d', '#9bbb59', '#8064a2', '#4bacc6', '#f79646', '#ffff00',
				'#f2f2f2', '#7f7f7f', '#ddd9c3', '#c6d9f0', '#dbe5f1', '#f2dcdb', '#ebf1dd', '#e5e0ec', '#dbeef3', '#fdeada', '#fff2ca',
				'#d8d8d8', '#595959', '#c4bd97', '#8db3e2', '#b8cce4', '#e5b9b7', '#d7e3bc', '#ccc1d9', '#b7dde8', '#fbd5b5', '#ffe694',
				'#bfbfbf', '#3f3f3f', '#938953', '#548dd4', '#95b3d7', '#d99694', '#c3d69b', '#b2a2c7', '#b7dde8', '#fac08f', '#f2c314',
				'#a5a5a5', '#262626', '#494429', '#17365d', '#366092', '#953734', '#76923c', '#5f497a', '#92cddc', '#e36c09', '#c09100',
				'#7f7f7f', '#0c0c0c', '#1d1b10', '#0f243e', '#244061', '#632423', '#4f6128', '#3f3151', '#31859b', '#974806', '#7f6000'],

			// private
			allEmptyHtml: '<p><br /></p>',
			mozillaEmptyHtml: '<p>&nbsp;</p>',
						
			// modal windows container
			modal_file: String() + 
				'<form id="redactorUploadFileForm" method="post" action="" enctype="multipart/form-data">' +
					'<label>Name (optional)</label>' +
					'<input type="text" id="redactor_filename" class="redactor_input" />' +
					'<div style="margin-top: 7px;">' +
						'<input type="file" id="redactor_file" name="file" />' +
					'</div>' +
				'</form>',

			modal_image_edit: String() + 
				'<label>' + RLANG.title + '</label>' +
				'<input id="redactor_file_alt" class="redactor_input" />' +
				'<label>' + RLANG.link + '</label>' +
				'<input id="redactor_file_link" class="redactor_input" />' +
				'<label>' + RLANG.image_position + '</label>' +
				'<select id="redactor_form_image_align">' +
					'<option value="none">' + RLANG.none + '</option>' +
					'<option value="left">' + RLANG.left + '</option>' +
					'<option value="right">' + RLANG.right + '</option>' +
				'</select>' +
				'<div id="redactor_modal_footer">' +
					'<a href="javascript:void(null);" id="redactor_image_delete_btn" style="color: #000;">' + RLANG._delete + '</a>' +
					'<span class="redactor_btns_box">' +
						'<input type="button" name="save" id="redactorSaveBtn" value="' + RLANG.save + '" />' +
						'<a href="javascript:void(null);" id="redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
					'</span>' +
				'</div>',

			modal_image: String() + 
				'<div id="redactor_tabs">' +
					'<a href="javascript:void(null);" class="redactor_tabs_act">' + RLANG.upload + '</a>' +
					'<a href="javascript:void(null);">' + RLANG.choose + '</a>' +
					'<a href="javascript:void(null);">' + RLANG.link + '</a>' +
				'</div>' +
				'<form id="redactorInsertImageForm" method="post" action="" enctype="multipart/form-data">' +
					'<div id="redactor_tab1" class="redactor_tab">' +
						'<input type="file" id="redactor_file" name="file" />' +
					'</div>' +
					'<div id="redactor_tab2" class="redactor_tab" style="display: none;">' +
						'<div id="redactor_image_box"></div>' +
					'</div>' +
				'</form>' +
				'<div id="redactor_tab3" class="redactor_tab" style="display: none;">' +
					'<label>' + RLANG.image_web_link + '</label>' +
					'<input name="redactor_file_link" id="redactor_file_link" class="redactor_input"  />' +
				'</div>' +
				'<div id="redactor_modal_footer">' +
					'<span class="redactor_btns_box">' +
						'<input type="button" name="upload" id="redactor_upload_btn" value="' + RLANG.insert + '" />' +
						'<a href="javascript:void(null);" id="redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
					'</span>' +
				'</div>',

			modal_link: String() + 
				'<form id="redactorInsertLinkForm" method="post" action="">' +
					'<div id="redactor_tabs">' +
						'<a href="javascript:void(null);" class="redactor_tabs_act">URL</a>' +
						'<a href="javascript:void(null);">Email</a>' +
						'<a href="javascript:void(null);">' + RLANG.anchor + '</a>' +
					'</div>' +
					'<input type="hidden" id="redactor_tab_selected" value="1" />' +
					'<div class="redactor_tab" id="redactor_tab1">' +
						'<label>URL</label><input id="redactor_link_url" class="redactor_input"  />' +
						'<label>' + RLANG.text + '</label><input class="redactor_input redactor_link_text" id="redactor_link_url_text" />' +
					'</div>' +
					'<div class="redactor_tab" id="redactor_tab2" style="display: none;">' +
						'<label>Email</label><input id="redactor_link_mailto" class="redactor_input" />' +
						'<label>' + RLANG.text + '</label><input class="redactor_input redactor_link_text" id="redactor_link_mailto_text" />' +
					'</div>' +
					'<div class="redactor_tab" id="redactor_tab3" style="display: none;">' +
						'<label>' + RLANG.anchor + '</label><input class="redactor_input" id="redactor_link_anchor"  />' +
						'<label>' + RLANG.text + '</label><input class="redactor_input redactor_link_text" id="redactor_link_anchor_text" />' +
					'</div>' +
				'</form>' +
				'<div id="redactor_modal_footer">' +
					'<span class="redactor_btns_box">' +
						'<input type="button" id="redactor_insert_link_btn" value="' + RLANG.insert + '" />' +
						'<a href="javascript:void(null);" id="redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
					'</span>' +
				'</div>',
			
			modal_table: String() + 
					'<label>' + RLANG.rows + '</label>' +
					'<input size="5" value="2" id="redactor_table_rows" />' +
					'<label>' + RLANG.columns + '</label>' +
					'<input size="5" value="3" id="redactor_table_columns" />' +
					'<div id="redactor_modal_footer">' +
						'<span class="redactor_btns_box">' +
							'<input type="button" name="upload" id="redactor_insert_table_btn" value="' + RLANG.insert + '" />' +
							'<a href="javascript:void(null);" id="redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
						'</span>' +
					'</div>',
			
			modal_video: String() + 
				'<form id="redactorInsertVideoForm">' +
					'<label>' + RLANG.video_html_code + '</label>' +
					'<textarea id="redactor_insert_video_area" style="width: 99%; height: 160px;"></textarea>' +
				'</form>' +
				'<div id="redactor_modal_footer">' +
					'<span class="redactor_btns_box">' +
						'<input type="button" id="redactor_insert_video_btn" value="' + RLANG.insert + '" />' +
						'<a href="javascript:void(null);" id="redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
					'</span>' +
				'</div>',


			toolbar: {
				html:
				{
					title: RLANG.html,
					func: 'toggle'
				},
				formatting:
				{
					title: RLANG.formatting,
					func: 'show',
					dropdown: 
					{
						p:
						{
							title: RLANG.paragraph,
							exec: 'formatblock'
						},
						blockquote:
						{
							title: RLANG.quote,
							exec: 'formatblock',	
							className: 'redactor_format_blockquote'
						},
						pre:
						{
							title: RLANG.code,
							exec: 'formatblock',
							className: 'redactor_format_pre'
						},
						h1:
						{
							title: RLANG.header1,
							exec: 'formatblock',
							className: 'redactor_format_h1'
						},
						h2:
						{
							title: RLANG.header2,
							exec: 'formatblock',
							className: 'redactor_format_h2'
						},
						h3:
						{
							title: RLANG.header3,
							exec: 'formatblock',
							className: 'redactor_format_h3'
						},
						h4:
						{
							title: RLANG.header4,
							exec: 'formatblock',
							className: 'redactor_format_h4'
						}
					}
				},
				bold:
				{ 
					title: RLANG.bold,
					exec: 'bold'	
				}, 
				italic:
				{
					title: RLANG.italic,
					exec: 'italic'
				},
				deleted:
				{
					title: RLANG.deleted,
					exec: 'strikethrough'
				},	
				unorderedlist:
				{
					title: '&bull; ' + RLANG.unorderedlist,
					exec: 'insertunorderedlist'
				},
				orderedlist:
				{
					title: '1. ' + RLANG.orderedlist,
					exec: 'insertorderedlist'
				},
				outdent:
				{
					title: '< ' + RLANG.outdent,
					exec: 'outdent'	
				},
				indent:
				{
					title: '> ' + RLANG.indent,
					exec: 'indent'
				},
				image:
				{
					title: RLANG.image,
					func: 'showImage'
				},
				video:
				{
					title: RLANG.video,
					func: 'showVideo'
				},
				file:
				{
					title: RLANG.file,
					func: 'showFile'
				},	
				table:
				{ 
					title: RLANG.table,
					func: 'show',
					dropdown:
					{
						insert_table:	
						{
							title: RLANG.insert_table,
							func: 'showTable'
						},
						separator_drop1:
						{
							name: 'separator'
						},
						insert_row_above:
						{
							title: RLANG.insert_row_above,
							func: 'insertRowAbove'
						},
						insert_row_below:
						{
							title: RLANG.insert_row_below,
							func: 'insertRowBelow'
						},
						insert_column_left:
						{
							title: RLANG.insert_column_left,
							func: 'insertColumnLeft'
						},
						insert_column_right:
						{
							title: RLANG.insert_column_right,
							func: 'insertColumnRight'
						},
						separator_drop2:
						{
							name: 'separator'
						},
						add_head:
						{
							title: RLANG.add_head,
							func: 'addHead'
						},
						delete_head:
						{
							title: RLANG.delete_head,
							func: 'deleteHead'
						},
						separator_drop3:
						{
							name: 'separator'
						},
						delete_column:
						{
							title: RLANG.delete_column,
							func: 'deleteColumn'
						},
						delete_row:
						{
							title: RLANG.delete_row,
							func: 'deleteRow'
						},
						delete_table:
						{
							title: RLANG.delete_table,
							func: 'deleteTable'
						}
					}
				},
				link:
				{ 
					title: RLANG.link,
					func: 'show',
					dropdown:
					{
						link:
						{
							title: RLANG.link_insert,
							func: 'showLink'
						},
						unlink: 
						{
							title: RLANG.unlink,
							exec: 'unlink'
						}
					}
				},
				fontcolor:
				{
					title: RLANG.fontcolor,
					func: 'show'
				},	
				backcolor:
				{
					title: RLANG.backcolor,
					func: 'show'	
				},
				alignleft:
				{	
					exec: 'JustifyLeft',
					title: RLANG.align_left
				},					
				aligncenter:
				{
					exec: 'JustifyCenter',
					title: RLANG.align_center
				},
				alignright: 
				{
					exec: 'JustifyRight',
					title: RLANG.align_right
				},	
				justify: 
				{
					exec: 'justifyfull',
					title: RLANG.align_justify
				},	
				horizontalrule: 
				{
					exec: 'inserthorizontalrule',
					title: RLANG.horizontalrule
				},		
				fullscreen:
				{
					title: RLANG.fullscreen,
					func: 'fullscreen'
				}	
			}
			

		}, options, this.$el.data());
	
		this.dropdowns = [];
	
		// Init
		this.init();
	};

	// Functionality
	Redactor.prototype = {


		// Initialization
		init: function()
		{
			// get path to styles
			this.getPath();
			
			if (this.opts.toolbar !== false)
			{
				$.extend(this.opts.toolbar, this.opts.buttonsCustom);
			
				$.each(this.opts.buttonsAdd, $.proxy(function(i,s)
				{
					this.opts.buttons.push(s);
				}, this));
			}

			// get dimensions
			this.height = this.$el.css('height');
			this.width = this.$el.css('width');

			// construct editor
			this.build();

			// get html
			var html = this.$el.val();

			// preformatter
			html = this.preformater(html);
			
			// conver newlines to p
			if (this.opts.autoformat)
			{
				html = this.paragraphy(html);
			}

			// enable
			this.$editor = this.enable(html);

			// focus always on page
			this.observeFocus();
	
			// cleanup
			if (this.opts.cleanUp === true)
			{
				$(this.doc).bind('paste', $.proxy(function(e)
				{ 
					setTimeout($.proxy(function ()
					{
						var marker = Math.floor(Math.random() * 99999);
						var marker_text = '';
						if ($.browser.mozilla) marker_text = '&nbsp;';
						var node = $('<span rel="pastemarkerend" id="pastemarkerend' + marker + '">' + marker_text + '</span>');
						this.insertNodeAtCaret(node.get(0));
						
						this.pasteCleanUp(marker);
		
					}, this), 100);
	
				}, this));
			}

			// keyup
			$(this.doc).keyup($.proxy(function(e)
			{
				var key = e.keyCode || e.which;
				
				// callback as you type
				if (typeof this.opts.keyupCallback === 'function')
				{
					this.opts.keyupCallback(this, e);
				}
				
				if (this.opts.autoformat)
				{
					// if empty
					if (key === 8 || key === 46)
					{
						return this.formatEmpty(e);
					}

					// new line p
					if (key === 13 && !e.shiftKey && !e.ctrlKey && !e.metaKey)
					{
						return this.formatNewLine(e);
					}
				}

				this.syncCode();

			}, this));
			
			// toolbar
			this.buildToolbar();
			
			// resizer
			if (this.opts.autoresize === false) 
			{
				this.buildResizer();
			}
			else
			{
				this.observeAutoResize();
			}

			// shortcuts
			this.shortcuts();

			// autosave
			this.autoSave();

			// observers
			this.observeImages();
			this.observeTables();
	
			// fullscreen on start
			if (this.opts.fullscreen) 
			{
				this.opts.fullscreen = false;
				this.fullscreen();
			}
				
			// focus
			if (this.opts.focus) 
			{
				this.focus();
			}

			// fixed
			if (this.opts.fixed)
			{
				this.observeScroll();
				$(document).scroll($.proxy(this.observeScroll, this));
			}
			
			// callback
			if (typeof this.opts.callback === 'function')
			{
				this.opts.callback(this);
			}
			
		},
		shortcuts: function()
		{
			$(this.doc).keydown($.proxy(function(e)
			{
				var key = e.keyCode || e.which;
	
				// callback keydown
				if (typeof this.opts.keydownCallback === 'function') 
				{
					this.opts.keydownCallback(this, e);	
				}
	
				if (e.ctrlKey) 
				{
					if (key === 90)
					{
						this._shortcuts(e, 'undo'); // Ctrl + z
					}
					else if (key === 90 && e.shiftKey)
					{
						this._shortcuts(e, 'redo');	// Ctrl + Shift + z
					}
					else if (key === 77)
					{
						this._shortcuts(e, 'removeFormat'); // Ctrl + m
					}
					else if (key === 66)
					{
						this._shortcuts(e, 'bold'); // Ctrl + b
					}
					else if (key === 73)
					{
						this._shortcuts(e, 'italic'); // Ctrl + i
					}
					else if (key === 74) 
					{
						this._shortcuts(e, 'insertunorderedlist'); // Ctrl + j
					}
					else if (key === 75)
					{
						this._shortcuts(e, 'insertorderedlist'); // Ctrl + k
					}
					else if (key === 76)
					{
						this._shortcuts(e, 'superscript'); // Ctrl + l
					}
					else if (key === 72)
					{
						this._shortcuts(e, 'subscript'); // Ctrl + h
					}					
				}
	
				if (!e.shiftKey && key === 9)
				{
					this._shortcuts(e, 'indent'); // Tab
				}
				else if (e.shiftKey && key === 9 )
				{
					this._shortcuts(e, 'outdent'); // Shift + tab
				}

				// safari shift key + enter
				if ($.browser.webkit && navigator.userAgent.indexOf('Chrome') === -1)
				{
					return this.safariShiftKeyEnter(e, key);
				}

			}, this));

		},
		_shortcuts: function(e, cmd)
		{
			if (e.preventDefault) 
			{
				e.preventDefault();
			}
			this.execCommand(cmd, null);
		},
		getPath: function()
		{
			if (this.opts.path !== false) 
			{
				return this.opts.path;
			}

			$('script').each($.proxy(function(i,s)
			{
				if (s.src)
				{
					// Match redactor.js or redactor.min.js, followed by an optional querystring (often used for cache purposes)
					var regexp = new RegExp(/\/redactor(\.min)?\.js(\?.*)?/);
					if (s.src.match(regexp))
					{
						this.opts.path = s.src.replace(regexp, '');
					}
				}
			}, this));

		},
		build: function()
		{
			// container
			this.$box = $('<div class="redactor_box"></div>');
	
			// frame
			this.$frame = $('<iframe frameborder="0" scrolling="auto" style="height: ' + this.height + ';" class="redactor_frame"></iframe>');

			// hide textarea
			this.$el.css('width', '100%').hide();

			// append box and frame
			this.$box.insertAfter(this.$el).append(this.$frame).append(this.$el);

		},
		write: function(html)
		{
			this.doc.open();
			this.doc.write(html);
			this.doc.close();
		},
		enable: function(html)
		{
			this.doc = this.getDoc(this.$frame.get(0));

			if (this.doc !== null)
			{
				this.write(this.setDoc(html));
				if ($.browser.mozilla)
				{
					this.doc.execCommand("useCSS", false, true);
				}
				return $(this.doc).find('#page');
			}
			else
			{
				return false;
			}
		},
		setDoc: function(html)
		{
			var frameHtml = '<!DOCTYPE html>\n' +
			'<html><head><link media="all" type="text/css" href="' + this.opts.path + '/css/' + this.opts.css + '" rel="stylesheet"></head>' +
			'<body><div id="page" contenteditable="true" dir="' + this.opts.direction + '">' +
			html + 
			'</div></body></html>';
			
			return frameHtml;
		},		
		getDoc: function(frame)
		{
			if (frame.contentDocument)
			{
				return frame.contentDocument;
			}
			else if (frame.contentWindow && frame.contentWindow.document)
			{
				return frame.contentWindow.document;
			}
			else if (frame.document)
			{
				return frame.document;
			}
			else
			{
				return null;
			}
		},
		focus: function()
		{
			this.$editor.focus();
		},
		syncCode: function()
		{
			var html = this.formatting(this.$editor.html());
			this.$el.val(html);			
		},
		
		// API functions
		setCode: function(html)
		{
			html = this.preformater(html);

			this.$editor.html(html).focus();

			this.syncCode();
		},
		getCode: function()
		{
			var html = this.$editor ? this.$editor.html() : this.$el.val();
			html = this.reformater(html);

			return html;
		},
		insertHtml: function(html)
		{
			this.execCommand('inserthtml', html);
			this.observeImages();
		},
		destroy: function()
		{
			var html = this.getCode();
			
			this.$box.after(this.$el);
			this.$box.remove();
			this.$el.val(html).show();
			
			for (var i = 0; i < this.dropdowns.length; i++)
			{
				this.dropdowns[i].remove();
				delete(this.dropdowns[i]);
			}			
			
		},
		handler: function()
		{
			$.ajax({
				url: this.opts.handler,
				type: 'POST',
				data: 'redactor=' + escape(encodeURIComponent(this.getCode())),
				success: $.proxy(function(data)
				{
					this.setCode(data);
					this.syncCode();

				}, this)
			});

		},
		// end API functions

		// OBSERVERS
		observeFocus: function()
		{
			if ($(this.doc).find('body').height() < $(this.$frame.get(0).contentWindow).height())
			{		
				$(this.doc).click($.proxy(function(e) { this.$editor.focus(); }, this));
			}
		},
		observeImages: function()
		{
			if (this.opts.observeImages === false)
			{
				return false;
			}
		
			if ($.browser.mozilla)
			{
				this.doc.execCommand("enableObjectResizing", false, "false");
			}
			
			$(this.doc).find('img').each($.proxy(function(i,s)
			{
				if ($.browser.msie) $(s).attr('unselectable', 'on');
				this.resizeImage(s);
				
			}, this));
		
		},
		observeTables: function()
		{
			$(this.doc).find('body').find('table').click($.proxy(this.tableObserver, this));
		},
		observeScroll: function()
		{
			var scrolltop = $(document).scrollTop();
			var boxtop = this.$box.offset().top;
		
			if (scrolltop > boxtop)
			{
				this.fixed = true;
				this.$toolbar.css({ position: 'fixed', width: '100%' });
			}
			else
			{
				this.fixed = false;
				this.$toolbar.css({ position: 'relative', width: 'auto' });
			}
		},
		observeAutoResize: function()
		{
			this.$editor.css({ 'min-height': this.$el.height() + 'px' });
			$(this.doc).find('body').css({ 'overflow': 'hidden' });
			this.setAutoSize(false);
			$(this.doc).keyup($.proxy(this.setAutoSize, this));
		},
		setAutoSize: function(e)
		{
			var key = false;
			if (e !== false) 
			{
				key = e.keyCode || e.which;
			}
				
			var height = this.$editor.outerHeight(true);
				
			if (e === true && (key === 8 || key === 46))
			{
				this.$frame.height(height);
			}
			else
			{
				this.$frame.height(height+30);
			}
				
			
		},
		
		
		// EXECCOMMAND
		execCommand: function(cmd, param)
		{
			if (this.opts.visual && this.doc)
			{
				try
				{
					if ($.browser.msie)
					{
						this.focus();
					}

					if (cmd === 'inserthtml' && $.browser.msie)
					{
						this.doc.selection.createRange().pasteHTML(param);
					}
					else if (cmd === 'formatblock' && $.browser.msie)
					{
						this.doc.execCommand(cmd, false, '<' +param + '>');
					}
					else
					{
						this.doc.execCommand(cmd, false, param);
					}
					
					this.syncCode();
					this.focus();					
					
					if (typeof this.opts.execCommandCallback === 'function')
					{
						this.opts.execCommandCallback(this, cmd);
					}
				}
				catch (e) { }

			}
		},
		
		// FORMAT NEW LINE
		formatNewLine: function(e)
		{
			var parent = this.getParentNode();
			if (parent.nodeName === 'DIV' && parent.id === 'page')
			{
				if (e.preventDefault)
				{
					e.preventDefault();
				}
				
				var element = $(this.getCurrentNode());
				if (element.get(0).tagName === 'DIV' && (element.html() === '' || element.html() === '<br>'))
				{
					var newElement = $('<p>').append(element.clone().get(0).childNodes);
					element.replaceWith(newElement);
					newElement.html('<br />');
					this.setFocusNode(newElement.get(0));

					this.syncCode();
					return false;
				}
				else
				{
					this.syncCode();
				}

				// convert links
				if (this.opts.convertLinks)
				{
					this.$editor.linkify();
				}
			}
			else 
			{
				this.syncCode();
				return true;
			}
		},

		// SAFARI SHIFT KEY + ENTER
		safariShiftKeyEnter: function(e, key)
		{
			if (e.shiftKey && key === 13)
			{
				e.preventDefault();
			
				var node1 = $('<span><br /></span>');
				this.insertNodeAtCaret(node1.get(0));
				this.setFocusNode(node1.get(0));

				this.syncCode();

				return false;
			}
			else
			{
				return true;
			}
		},
		
		// FORMAT EMPTY
		formatEmpty: function(e)
		{
			var html = $.trim(this.$editor.html());
			
			if ($.browser.mozilla)
			{
				html = html.replace(/<br>/gi, '');
			}
			
			if (html === '')
			{
				if (e.preventDefault)
				{
					e.preventDefault();
				}
				
				var nodehtml = this.opts.allEmptyHtml;
				if ($.browser.mozilla)
				{
					nodehtml = this.opts.mozillaEmptyHtml;
				}
				
				var node = $(nodehtml).get(0);
				this.$editor.html(node);
				this.setFocusNode(node);
	
				this.syncCode();
				return false;
			}
			else
			{
				this.syncCode();
			}
		},
		
		// PARAGRAPHY
		paragraphy: function (str)
		{
			str = $.trim(str);
			if (str === '')
			{
				if (!$.browser.mozilla)
				{
					return this.opts.allEmptyHtml;
				}
				else
				{
					return this.opts.mozillaEmptyHtml;
				}
			}
			
			// convert div to p
			if (this.opts.convertDivs) str = str.replace(/<div(.*?)>([\w\W]*?)<\/div>/gi, '<p>$2</p>');

			// inner functions
			var X = function(x, a, b) { return x.replace(new RegExp(a, 'g'), b); };
			var R = function(a, b) { return X(str, a, b); };

			// block elements
			var blocks = '(table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|style|script|object|input|param|p|h[1-6])';
		
			str += '\n';

			R('<br />\\s*<br />', '\n\n');
			R('(<' + blocks + '[^>]*>)', '\n$1');
			R('(</' + blocks + '>)', '$1\n\n');
			R('\r\n|\r', '\n'); // newlines
			R('\n\n+', '\n\n'); // remove duplicates
			R('\n?((.|\n)+?)$', '<p>$1</p>\n'); // including one at the end
			R('<p>\\s*?</p>', ''); // remove empty p
			R('<p>(<div[^>]*>\\s*)', '$1<p>');
			R('<p>([^<]+)\\s*?(</(div|address|form)[^>]*>)', '<p>$1</p>$2');
			R('<p>\\s*(</?' + blocks + '[^>]*>)\\s*</p>', '$1');
			R('<p>(<li.+?)</p>', '$1');
			R('<p>\\s*(</?' + blocks + '[^>]*>)', '$1');
			R('(</?' + blocks + '[^>]*>)\\s*</p>', '$1');
			R('(</?' + blocks + '[^>]*>)\\s*<br />', '$1');
			R('<br />(\\s*</?(p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)', '$1');

			// pre
			if (str.indexOf('<pre') != -1)
			{
				R('(<pre(.|\n)*?>)((.|\n)*?)</pre>', function(m0, m1, m2, m3)
				{
					return X(m1, '\\\\([\'\"\\\\])', '$1') + X(X(X(m3, '<p>', '\n'), '</p>|<br />', ''), '\\\\([\'\"\\\\])', '$1') + '</pre>';
				});
			}

			return R('\n</p>$', '</p>');
		},	

		// PREPARE FORMATER
		preformater: function(html)
		{
			if (html !== null)
			{
				html = html.replace(/<br>/gi,'<br />');
				//html = html.replace(/<blockquote\b[^>]*>([\w\W]*?)<p>([\w\W]*?)<\/p>([\w\W]*?)<\/blockquote[^>]*>/gi,'<blockquote>$1$2<br />$3</blockquote>');
				html = html.replace(/<strong\b[^>]*>([\w\W]*?)<\/strong[^>]*>/gi,'<b>$1</b>');
				html = html.replace(/<em\b[^>]*>([\w\W]*?)<\/em[^>]*>/gi,'<i>$1</i>');
				html = html.replace(/<del\b[^>]*>([\w\W]*?)<\/del[^>]*>/gi,'<strike>$1</strike>');
			}
			else
			{
				html = '';
			}
			
			return html;
		},

		// REVERT FORMATER
		reformater: function(html)
		{
			html = html.replace(/<br>/gi,'<br />');
			html = html.replace(/<b\b[^>]*>([\w\W]*?)<\/b[^>]*>/gi,'<strong>$1</strong>');
			html = html.replace(/<i\b[^>]*>([\w\W]*?)<\/i[^>]*>/gi,'<em>$1</em>');
			html = html.replace(/<strike\b[^>]*>([\w\W]*?)<\/strike[^>]*>/gi,'<del>$1</del>');
			html = html.replace(/<span(.*?)style="font-weight: bold;">([\w\W]*?)<\/span>/gi, "<strong>$2</strong>");
			html = html.replace(/<span(.*?)style="font-style: italic;">([\w\W]*?)<\/span>/gi, "<em>$2</em>");
			html = html.replace(/<span(.*?)style="font-weight: bold; font-style: italic;">([\w\W]*?)<\/span>/gi, "<em><strong>$2</strong></em>");
			html = html.replace(/<span(.*?)style="font-style: italic; font-weight: bold;">([\w\W]*?)<\/span>/gi, "<strong><em>$2</em></strong>");

			return html;
		},
		
		// REMOVE TAGS
		stripTags: function(html) 
		{
			var allowed = ["code", "span", "div", "label", "a", "br", "p", "b", "i", "del", "strike", "img", "video", "audio", "iframe", "object", "embed", "param", "blockquote", "mark", "cite", "small", "ul", "ol", "li", "hr", "dl", "dt", "dd", "sup", "sub", "big", "pre", "code", "figure", "figcaption", "strong", "em", "table", "tr", "td", "th", "tbody", "thead", "tfoot", "h1", "h2", "h3", "h4", "h5", "h6"];
			var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
			return html.replace(tags, function ($0, $1) 
			{
				return $.inArray($1.toLowerCase(), allowed) > '-1' ? $0 : '';
			});
		},
		cleanStyles: function (properties, str, m)
		{

			var attr = m.split('=');
			var prop = attr[1].split(';');
		
			var t = '';
			$.each(prop, function(z,s)
			{
				if (typeof s == 'undefined') return true;
				if (s == '"' || s == "'") return true;
			
				s = s.replace(/(^"|^')/,'').replace(/("$|'$)/,'').replace(/"(.*?)"/gi, '');
				s = $.trim(s);
		
				var p = s.split(':');
				
				if (typeof p[1] !== 'undefined' && (p[1].search('pt') === -1 && p[1].search('cm') === -1)) 
				{
					if ($.inArray($.trim(p[0]), properties) != '-1') t += s + '; ';
				}
			});

			return str.replace(m, 'style="' + t + '"');
		},
	
		
		// PASTE CLEANUP
		pasteCleanUp: function(marker)
		{
			var html = this.$editor.html();
			
			// remove comments and php tags
			html = html.replace(/<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi, '')			
			
			// remove formatting
			html = this.formattingRemove(html);			
			
			// remove tags	
			html = this.stripTags(html);	
			
			// remove classes
			if (this.opts.removeClasses)
			{
				html = html.replace(/ class="([\w\W]*?)"/gi, '');
			}
			else
			{
				html = html.replace(/\s*class="TOC(.*?)"/gi, "" );
				html = html.replace(/\s*class="Heading(.*?)"/gi, "" );
				html = html.replace(/\s*class="Body(.*?)"/gi, "" );		
				html = html.replace(/\sclass="Nonformat"/gi, '');		
				html = html.replace(/\sclass="Mso(.*?)"/gi, '');				
				html = html.replace(/\sclass="Apple(.*?)"/gi, '');			
				html = html.replace(/"Times New Roman"/gi, '');				
			}
			
			var attributes = ['width', 'height', 'src', 'style', 'href', 'frameborder', 'class', 'id', 'rel', 'unselectable'];    
			var properties = ['color', 'background-color', 'font-style', 'margin', 'margin-top', 'margin-bottom', 'margin-left', 'margin-right', 'text-align', 'width', 'height', 'cursor', 'float'];
			
			// remove attributes
			var matches = html.match(/([\w\-.:]+)\s*=\s*("[^"]*"|'[^']*'|[\w\-.:]+)/gi);
			$.each(matches, $.proxy(function(i,m)
			{
				var attr = m.split('=');
				if ($.inArray(attr[0], attributes) == '-1') 
				{
					html = html.replace(m, '');
				}
				
				// remove styles
				if (this.opts.removeStyles === false && attr[0] == 'style') 
				{
					html = this.cleanStyles(properties, html, m);
				}

			}, this));
			
			// remove styles
			if (this.opts.removeStyles === false)
			{
				html = html.replace(/background-color:(\s+)transparent;\s/gi, '');
				html = html.replace(/font-style:(\s+)normal;\s/gi, '');	
			}
			else
			{
				html = html.replace(/ style="([\w\W]*?)"/gi, '');
			}

			// remove empty styles
			html = html.replace(/((\s{2,})?|\s)style=(""|'')/gi, '');

			// remove nbsp
			html = html.replace(/(&nbsp;){1,}/gi, '&nbsp;');
			
			// remove empty span
			html = html.replace(/<span(\s)?>(\s)?<\/span>/gi, ' ');
		
			// remove double white spaces
			html = html.replace(/\s{2,}/g, ' ');
		
			// remove span without styles
			html = html.replace(/<span>([\w\W]*?)<\/span>/gi, '$1');
			html = html.replace(/<span>([\w\W]*?)<\/span>/gi, '$1');
			html = html.replace(/<span(\s)?>([\w\W]*?)<\/span>/gi, '$2');

			// empty tags
			html = this.formattingEmptyTags(html);

			// add formatting before
			html = this.formattingAddBefore(html);
			
			// add formatting after
			html = this.formattingAddAfter(html);
		
			// indenting
			html = this.formattingIndenting(html);
		
			// dirty paragraphs
			html = html.replace(/<p><div>/gi, "");
			html = html.replace(/<p>\s+\n+<(.*?)/gi, "<$1");
			html = html.replace(/<\/(.*?)>\s+\n+<\/p>/gi, "</$1>");	
			html = html.replace(/<p style="(.*?)">(\s)?<\/p>/gi, '');
			html = html.replace(/<p style="(.*?)"><\/p>/gi, '');
		
			// remove google docs marker
			html = html.replace(/<b\sid="internal-source-marker(.*?)">([\w\W]*?)<\/b>/gi, "$2");	
		
			// trim spaces
			html = html.replace(/; ">/gi, ';">');

			// SET HTML
			this.$editor.html(html);

			// RETURN FOCUS
			var node = $(this.doc.body).find('#pastemarkerend' + marker).get(0);
			this.setFocusNode(node);

			this.syncCode();

			
			setTimeout($.proxy(function()
			{
				// remove pastemarker
				$(this.doc.body).find('span[rel=pastemarkerend]').remove();
				
				if (this.opts.autoresize === true) this.setAutoSize(false);
				
				this.observeImages();
				this.observeTables();
				
			}, this), 100);
			
			
		},
		
		// TEXTAREA CODE FORMATTING
		formattingRemove: function(html)
		{
			html = html.replace(/\s{2,}/g, ' ');
			html = html.replace(/\n/g, ' ');	
			html = html.replace(/[\t]*/g, '');
			html = html.replace(/\n\s*\n/g, "\n");
			html = html.replace(/^[\s\n]*/g, '');
			html = html.replace(/[\s\n]*$/g, '');	
			html = html.replace(/>\s+</g, '><');
			
			return html;		
		},
		formattingIndenting: function(html)
		{
			html = html.replace(/<li/g, "\t<li");
			html = html.replace(/<tr/g, "\t<tr");
			html = html.replace(/<td/g, "\t\t<td");
			html = html.replace(/<\/tr>/g, "\t</tr>");	
			
			return html;	
		},
		formattingEmptyTags: function(html)		
		{
			var etags = ["<pre></pre>","<blockquote>\\s*</blockquote>","<em>\\s*</em>","<ul></ul>","<ol></ol>","<li></li>","<table></table>","<tr></tr>","<span>\\s*<span>", "<span>&nbsp;<span>", "<b>\\s*</b>", "<b>&nbsp;</b>", "<p>\\s*</p>", "<p>&nbsp;</p>",  "<p>\\s*<br>\\s*</p>", "<div>\\s*</div>", "<div>\\s*<br>\\s*</div>"];
			for (var i = 0; i < etags.length; ++i)
			{
				var bbb = etags[i];
				html = html.replace(new RegExp(bbb,'gi'), "");
			}
			
			return html;
		},
		formattingAddBefore: function(html)
		{
			var lb = '\r\n';
			var btags = ["<p", "<form","</ul>", '</ol>', "<fieldset","<legend","<object","<embed","<select","<option","<input","<textarea","<pre","<blockquote","<ul","<ol","<li","<dl","<dt","<dd","<table", "<thead","<tbody","<caption","</caption>","<th","<tr","<td","<figure"];
			for (var i = 0; i < btags.length; ++i)
			{
				var eee = btags[i];
				html = html.replace(new RegExp(eee,'gi'),lb+eee);
			}
			
			return html;
		},
		formattingAddAfter: function(html)
		{
			var lb = '\r\n';		
			var atags = ['</p>', '</div>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>', '<br>', '<br />', '</dl>', '</dt>', '</dd>', '</form>', '</blockquote>', '</pre>', '</legend>', '</fieldset>', '</object>', '</embed>', '</textarea>', '</select>', '</option>', '</table>', '</thead>', '</tbody>', '</tr>', '</td>', '</th>', '</figure>'];
			for (var i = 0; i < atags.length; ++i)
			{
				var aaa = atags[i];
				html = html.replace(new RegExp(aaa,'gi'),aaa+lb);
			}
			
			return html;
		},
		formatting: function(html)
		{
			// lowercase
			if ($.browser.msie)
			{
				var match = html.match(/<(.*?)>/gi);
				$.each(match, function(i,s)
				{
					html = html.replace(s, s.toLowerCase());
				}) 
				html = html.replace(/style="(.*?)"/gi, function(w) { return w.toLowerCase(); });
				html = html.replace(/ jQuery(.*?)=\"(.*?)\"/gi, '');
			}

			html = html.replace(/<span([\w\W]*?)style="color:(.*?);"><span([\w\W]*?)style="background-color:(.*?);">([\w\W]*?)<\/span><\/span>/gi, '<span style="color: $2; background-color: $4;">$5</span>');
			html = html.replace(/<span([\w\W]*?)style="background-color:(.*?);"><span([\w\W]*?)style="color:(.*?);">([\w\W]*?)<\/span><\/span>/gi, '<span style="color: $2; background-color: $4;">$5</span>');

			html = html.replace(/<span([\w\W]*?)color="(.*?)">([\w\W]*?)<\/span\>/gi, '<span$1style="color: $2;">$3</span>');
			html = html.replace(/<font([\w\W]*?)color="(.*?)">([\w\W]*?)<\/font\>/gi, '<span$1style="color: $2;">$3</span>');
			html = html.replace(/<span style="(.*?)"\s+style="(.*?)"[\s]*>([\w\W]*?)<\/span\>/gi, '<span style="$1;$2">$3</span>');
			html = html.replace(/<font([\w\W]*?)>([\w\W]*?)<\/font\>/gi, "<span$1>$2</span>");
			html = html.replace(/<span>([\w\W]*?)<\/span>/gi, '$1');			

			if (this.opts.xhtml)
			{
				html = html.replace(/<br>/gi,'<br />');
				html = html.replace(/<br ?\/?>$/gi,'');
				html = html.replace(/^<br ?\/?>/gi,'');
				html = html.replace(/(<img [^>]+[^\/])>/gi,'$1 />');
			}

			// mini clean
			html = html.replace(/<p><p>/g, '<p>');
			html = html.replace(/<\/p><\/p>/g, '</p>');
			html = html.replace(/<hr(.*?)>/g, '<hr />');
			html = html.replace(/<p>&nbsp;/g, '<p>');
			html = html.replace(/<p><ul>/g, '<ul>');
			html = html.replace(/<p><ol>/g, '<ol>');
			html = html.replace(/<\/ul><\/p>/g, '</ul>');
			html = html.replace(/<\/ol><\/p>/g, '</ol>');
			html = html.replace( /<p(.*?)>&nbsp;<\/p>/gi, '');

			// remove formatting
			html = this.formattingRemove(html);

			// empty tags
			html = this.formattingEmptyTags(html);
						
			// add formatting before
			html = this.formattingAddBefore(html);
			
			// add formatting after
			html = this.formattingAddAfter(html);

			// indenting
			html = this.formattingIndenting(html);

			return html;
		},

		// TOGGLE
		toggle: function()
		{
			var html;
		
			if (this.opts.visual)
			{
				this.$frame.hide();
				
				html = this.$editor.html();
				html = $.trim(this.formatting(html));
					
				this.$el.val(html).show().focus();
				
				this.setBtnActive('html');
				this.opts.visual = false;
			}
			else
			{
				this.$el.hide();
				
				this.$editor.html(this.$el.val());

				this.$frame.show();

				if (this.$editor.html() === '')
				{
					if (!$.browser.mozilla)
					{
						html = this.opts.allEmptyHtml;
					}
					else
					{
						html = this.opts.mozillaEmptyHtml;
					}

					this.setCode(html);
				}

				this.focus();
				
				this.setBtnInactive('html');
				this.opts.visual = true;
				
				this.observeImages();
				this.observeTables();
				
				if (this.opts.autoresize === true) this.setAutoSize(false);
			}
		},

		// AUTOSAVE
		autoSave: function()
		{
			if (this.opts.autosave === false)
			{
				return false;
			}
	
			setInterval($.proxy(function()
			{
				$.post(this.opts.autosave, { data: this.getCode() });

			}, this), this.opts.interval*1000);
		},

		// TOOLBAR
		buildToolbar: function()
		{
			if (this.opts.toolbar === false)
			{
				return false;
			}
		
			this.$toolbar = $('<ul>').addClass('redactor_toolbar');
			this.$box.prepend(this.$toolbar);
			
			$.each(this.opts.buttons, $.proxy(function(i,key)
			{
				
				if (key !== '|' && typeof this.opts.toolbar[key] !== 'undefined') 
				{
					var s = this.opts.toolbar[key];
				
					if (this.opts.fileUpload === false && key === 'file')
					{
						return true;
					}
				
					var li = $('<li>');
					
					if (key === 'fullscreen') 
					{
						$(li).addClass('redactor_toolbar_right');
					}
	
					var a = this.buildButton(key, s);
	
					// dropdown
					if (key === 'backcolor' || key === 'fontcolor' || typeof(s.dropdown) !== 'undefined')
					{
						var dropdown = $('<div class="redactor_dropdown" style="display: none;">');
						
						if (key === 'backcolor' || key === 'fontcolor')
						{
							dropdown = this.buildColorPicker(dropdown, key);
						}
						else
						{
							dropdown = this.buildDropdown(dropdown, s.dropdown);
						}
	
						this.dropdowns.push(dropdown.appendTo($(document.body)));
	
						// observing dropdown
						this.hdlHideDropDown = $.proxy(function(e) { this.hideDropDown(e, dropdown, key); }, this);
						this.hdlShowDropDown = $.proxy(function(e) { this.showDropDown(e, dropdown, key); }, this);
	
						a.click(this.hdlShowDropDown);
					}
					
					this.$toolbar.append($(li).append(a));
				}

				
				if (key === '|')
				{
					this.$toolbar.append($('<li class="redactor_separator"></li>'));
				}

			}, this));

			$(document).click(this.hdlHideDropDown);
			$(this.doc).click(this.hdlHideDropDown);

		},
		buildButton: function(key, s)
		{
			var button = $('<a href="javascript:void(null);" title="' + s.title + '" class="redactor_btn_' + key + '"><span>&nbsp;</span></a>');
			
			if (typeof s.func === 'undefined')
			{
				button.click($.proxy(function() { this.execCommand(s.exec, key); }, this));
			}
			else if (s.func !== 'show')
			{
				button.click($.proxy(function(e) { this[s.func](e); }, this));
			}

			if (typeof s.callback !== 'undefined') 
			{
				button.click($.proxy(function(e) { s.callback(this, e, key); }, this));
			}

			return button;
		},
		buildDropdown: function(dropdown, obj)
		{
			$.each(obj, $.proxy(
				function (x, d)
				{
					if (typeof(d.className) === 'undefined')
					{
						d.className = '';
					}
					
					var drop_a;
					if (typeof d.name !== 'undefined' && d.name === 'separator')
					{
						drop_a = $('<a class="redactor_separator_drop">');
					}
					else
					{
						drop_a = $('<a href="javascript:void(null);" class="' + d.className + '">' + d.title + '</a>');

						if (typeof(d.func) === 'undefined')
						{
							$(drop_a).click($.proxy(function() { this.execCommand(d.exec, x); }, this));
						}
						else
						{
							$(drop_a).click($.proxy(function(e) { this[d.func](e); }, this));
						}
					}

					$(dropdown).append(drop_a);
					
				}, this)
			);

			return dropdown;

		},
		buildColorPicker: function(dropdown, key)
		{
			var mode;
			if (key === 'backcolor')
			{
				if ($.browser.msie)
				{
					mode = 'BackColor';
				}
				else
				{
					mode = 'hilitecolor';
				}
			}
			else
			{
				mode = 'forecolor';
			}
			
			$(dropdown).width(210);

			var len = this.opts.colors.length;
			for (var i = 0; i < len; ++i)
			{
				var color = this.opts.colors[i];

				var swatch = $('<a rel="' + color + '" href="javascript:void(null);" class="redactor_color_link"></a>').css({ 'backgroundColor': color });
				$(dropdown).append(swatch);

				var _self = this;
				$(swatch).click(function() 
				{ 
					if ($.browser.mozilla)
					{	
						if (mode == 'hilitecolor')
						{					
							_self.execCommand('useCSS', false, false);
							_self.execCommand(mode, $(this).attr('rel'));
							_self.execCommand('useCSS', false, true);
						}
						else
						{
							_self.execCommand('styleWithCSS', false, false);
							_self.execCommand(mode, $(this).attr('rel'));
							_self.execCommand('styleWithCSS', false, true);
						}
					}
					else
					{
						_self.execCommand(mode, $(this).attr('rel'));
					}
		
				});
			}

			var elnone = $('<a href="javascript:void(null);" class="redactor_color_none"></a>').html(RLANG.none);

			if (key === 'backcolor')
			{
				elnone.click($.proxy(this.setBackgroundNone, this));
			}
			else
			{
				elnone.click($.proxy(this.setColorNone, this));
			}

			$(dropdown).append(elnone);

			return dropdown;
		},
		setBackgroundNone: function()
		{
			$(this.getParentNode()).css('background-color', 'transparent');
			this.syncCode();
		},
		setColorNone: function()
		{
			$(this.getParentNode()).attr('color', '').css('color', '');
			this.syncCode();
		},

		
		// DROPDOWNS
		showDropDown: function(e, dropdown, key)
		{
			if (this.getBtn(key).hasClass('dropact'))
			{
				this.hideAllDropDown();
			}
			else
			{	
				this.hideAllDropDown();
	
				this.setBtnActive(key);
				this.getBtn(key).addClass('dropact');
	
				var left = this.getBtn(key).offset().left;
				
				
				if (this.opts.fixed && this.fixed)
				{
					$(dropdown).css({ position: 'fixed', left: left + 'px', top: '30px' }).show();
				}
				else 
				{
					var top = this.$toolbar.offset().top + 30;
					$(dropdown).css({ position: 'absolute', left: left + 'px', top: top + 'px' }).show();
				}
			}
			
		},
		hideAllDropDown: function()
		{
			this.$toolbar.find('a.dropact').removeClass('act').removeClass('dropact');
			$('.redactor_dropdown').hide();
		},
		hideDropDown: function(e, dropdown, key)
		{
			if (!$(e.target).parent().hasClass('dropact'))
			{
				$(dropdown).removeClass('act');
				this.showedDropDown = false;
				this.hideAllDropDown();
			}
		},
		
		// SELECTION AND NODE MANIPULATION
		getSelection: function ()
		{
			if (this.$frame.get(0).contentWindow.getSelection)
			{
				return this.$frame.get(0).contentWindow.getSelection();
			}
			else if (this.$frame.get(0).contentWindow.document.selection)
			{
				return this.$frame.get(0).contentWindow.document.selection.createRange();
			}
		},
		getParentNode: function()
		{
			if (window.getSelection)
			{
				return this.getSelection().getRangeAt(0).startContainer.parentNode;
			}
			else if (document.selection)
			{
				return this.getSelection().parentElement();
			}
		},
		getCurrentNode: function()
		{
			if (window.getSelection)
			{
				return this.getSelection().getRangeAt(0).startContainer;
			}
			else if (document.selection)
			{
				return this.getSelection();
			}
		},
		setFocusNode: function(node, toStart)
		{
			var range = this.doc.createRange();

			var selection = this.getSelection();
			toStart = toStart ? 0 : 1;
	
			if (selection !== null)
			{
				range.selectNodeContents(node);
				selection.addRange(range);
				selection.collapse(node, toStart);
			}

			this.focus();
		},
		insertNodeAtCaret: function (node)
		{
			if (typeof window.getSelection !== "undefined")
			{
				var sel = this.getSelection();
				if (sel.rangeCount) 
				{
					var range = sel.getRangeAt(0);
					range.collapse(false);
					range.insertNode(node);
					range = range.cloneRange();
					range.selectNodeContents(node);
					range.collapse(false);
					sel.removeAllRanges();
					sel.addRange(range);
				}
			}
			else if (typeof document.selection !== "undefined" && document.selection.type !== "Control")
			{
				var html = (node.nodeType === 1) ? node.outerHTML : node.data;
				var id = "marker_" + ("" + Math.random()).slice(2);
				html += '<span id="' + id + '"></span>';
				var textRange = this.getSelection();
				textRange.collapse(false);
				textRange.pasteHTML(html);
				var markerSpan = document.getElementById(id);
				textRange.moveToElementText(markerSpan);
				textRange.select();
				markerSpan.parentNode.removeChild(markerSpan);
			}
		},

		// BUTTONS MANIPULATIONS
		getBtn: function(key)
		{
			return $(this.$toolbar.find('a.redactor_btn_' + key));
		},
		setBtnActive: function(key)
		{
			this.getBtn(key).addClass('act');
		},
		setBtnInactive: function(key)
		{
			this.getBtn(key).removeClass('act');
		},
		changeBtnIcon: function(key, classname)
		{
			this.getBtn(key).addClass('redactor_btn_' + classname);
		},
		removeBtnIcon: function(key, classname)
		{
			this.getBtn(key).removeClass('redactor_btn_' + classname);
		},
		removeBtn: function(key)
		{
			this.getBtn(key).remove();
		},
		addBtn: function(key, obj)
		{
			this.$toolbar.append($('<li>').append(this.buildButton(key, obj)));
		},
		
		// FULLSCREEN
		fullscreen: function()
		{
			var html;
		
			if (this.opts.fullscreen === false)
			{
				this.changeBtnIcon('fullscreen', 'normalscreen');
				this.setBtnActive('fullscreen');
				this.opts.fullscreen = true;
				
				this.height = this.$frame.css('height');
				this.width = (this.$box.width() - 2) + 'px';
				
				html = this.getCode();
	
				this.tmpspan = $('<span></span>');
				this.$box.addClass('redactor_box_fullscreen').after(this.tmpspan);
				
				$(document.body).prepend(this.$box).css('overflow', 'hidden');
	
				this.$editor = this.enable(html);
				
				$(this.doc).click($.proxy(this.hideAllDropDown, this));

				// focus always on page
				this.observeFocus();
				
				this.observeImages();
				this.observeTables();
				
				this.$box.find('.redactor_resizer').hide();

				this.fullScreenResize();
				$(window).resize($.proxy(this.fullScreenResize, this));
				$(document).scrollTop(0,0);
				this.focus();
		
			}
			else
			{
				this.removeBtnIcon('fullscreen', 'normalscreen');
				this.setBtnInactive('fullscreen');
				this.opts.fullscreen = false;
	
				$(window).unbind('resize', $.proxy(this.fullScreenResize, this));
				$(document.body).css('overflow', '');
				
				html = this.getCode();
				
				this.$box.removeClass('redactor_box_fullscreen').css('width', 'auto');
				
				this.tmpspan.after(this.$box).remove();
			
				this.$editor = this.enable(html);
				
				this.observeImages();
				this.observeTables();
				
				if (this.opts.autoresize)
				{
					this.observeAutoResize();
				}
				this.$box.find('.redactor_resizer').show();
				
				$(this.doc).click($.proxy(this.hideAllDropDown, this));
				
				// focus always on page
				this.observeFocus();
				
				this.syncCode();
				
				this.$frame.css('height', this.height);
				this.$el.css('height', this.height);
				this.focus();
			}
		},
		fullScreenResize: function()
		{
			if (this.opts.fullscreen === false)
			{
				return false;
			}
			
			var height = $(window).height() - 42;
			this.$box.width($(window).width() - 2);
			this.$frame.height(height);
			this.$el.height(height);
		},

		// RESIZE
		buildResizer: function()
		{
			if (this.opts.resize === false)
			{
				return false;
			}
			
			this.$resizer = $('<div class="redactor_resizer">&mdash;</div>');
			this.$box.append(this.$resizer);	
			this.$resizer.mousedown($.proxy(this.initResize, this));

		},
		initResize: function(e)
		{
			if (e.preventDefault)
			{
				e.preventDefault();
			}

			this.splitter = e.target;

			if (this.opts.visual)
			{
				this.element_resize = this.$frame;
				this.element_resize.get(0).style.visibility = 'hidden';
				this.element_resize_parent = this.$el;
			}
			else
			{
				this.element_resize = this.$el;
				this.element_resize_parent = this.$frame;
			}

			this.stopResizeHdl = $.proxy(this.stopResize, this);
			this.startResizeHdl = $.proxy(this.startResize, this);
			this.resizeHdl =  $.proxy(this.resize, this);

			$(document).mousedown(this.startResizeHdl);
			$(document).mouseup(this.stopResizeHdl);
			$(this.splitter).mouseup(this.stopResizeHdl);

			this.null_point = false;
			this.h_new = false;
			this.h = this.element_resize.height();
		},
		startResize: function()
		{
			$(document).mousemove(this.resizeHdl);
		},
		resize: function(e)
		{
			if (e.preventDefault)
			{
				e.preventDefault();
			}

			var y = e.pageY;
			
			if (this.null_point === false)
			{
				this.null_point = y;
			}
			
			if (this.h_new === false)
			{
				this.h_new = this.element_resize.height();
			}
	
			var s_new = (this.h_new + y - this.null_point) - 10;
	
			if (s_new <= 30)
			{
				return true;
			}
	
			if (s_new >= 0)
			{
				this.element_resize.get(0).style.height = s_new + 'px';
				this.element_resize_parent.get(0).style.height = s_new + 'px';
			}
		},
		stopResize: function(e)
		{
			$(document).unbind('mousemove', this.resizeHdl);
			$(document).unbind('mousedown', this.startResizeHdl);
			$(document).unbind('mouseup', this.stopResizeHdl);
			$(this.splitter).unbind('mouseup', this.stopResizeHdl);
			
			this.element_resize.get(0).style.visibility = 'visible';
		},
		
		// RESIZE IMAGES
		resizeImage: function(resize)
		{
			var clicked = false;
			var clicker = false;
			var start_x;
			var start_y;
			var ratio = $(resize).width()/$(resize).height();

			var y = 1;
			var x = 1;
			var min_w = 1;
			var min_h = 1;

			$(resize).hover(function() { $(resize).css('cursor', 'nw-resize'); }, function() { $(resize).css('cursor','default'); clicked=false; });

			$(resize).mousedown(function(e)
			{
				if (e.preventDefault)
				{
					e.preventDefault();
				}

				clicked = true;
				clicker = true;

				start_x = Math.round(e.pageX - $(resize).eq(0).offset().left);
				start_y = Math.round(e.pageY - $(resize).eq(0).offset().top);
			});
			
			$(resize).mouseup($.proxy(function(e)
			{
				clicked = false;
				this.syncCode();
				if (this.opts.autoresize === true) this.setAutoSize(false);
				
			}, this));
			
			$(resize).click($.proxy(function(e)
			{
				if (clicker)
				{
					this.imageEdit(e);
				}
	
			}, this));

			$(resize).mousemove(function(e)
			{
				if (clicked)
				{
					clicker = false;
				
					var mouse_x = Math.round(e.pageX - $(this).eq(0).offset().left) - start_x;
					var mouse_y = Math.round(e.pageY - $(this).eq(0).offset().top) - start_y;
					
					var div_h = $(resize).height();
					
					var new_h = parseInt(div_h, 10) + mouse_y;
					var new_w = new_h*ratio;
					
					if (x === 1 || (typeof(x) === "number" && new_w < x && new_w > min_w))
					{
						$(resize).width(new_w);
					}
					if (y === 1 || (typeof(y) === "number" && new_h < y && new_h > min_h))
					{
						$(resize).height(new_h);
					}
					start_x = Math.round(e.pageX - $(this).eq(0).offset().left);
					start_y = Math.round(e.pageY - $(this).eq(0).offset().top);
				}
			});
		},

		// TABLE
		showTable: function()
		{
			this.modalInit(RLANG.table, 'table', 300, $.proxy(function()
				{				
					$('#redactor_insert_table_btn').click($.proxy(this.insertTable, this));
				}, this),
				
				function()
				{
					$('#redactor_table_rows').focus();
				}			
			);
		},
		insertTable: function()
		{
			var rows = $('#redactor_table_rows').val();
			var columns = $('#redactor_table_columns').val();
			
			var table_box = $('<div></div>');
			
			var tableid = Math.floor(Math.random() * 99999);
			var table = $('<table id="table' + tableid + '"><tbody></tbody></table>');
			
			for (var i = 0; i < rows; i++)
			{
				var row = $('<tr></tr>');
				for (var z = 0; z < columns; z++)
				{
					var column = $('<td>&nbsp;</td>');
					$(row).append(column);
				}
				$(table).append(row);
			}
			
			$(table_box).append(table);
			var html = $(table_box).html();
			
			if ($.browser.msie)
			{
				html += '<p></p>';
			}
			else
			{
				html += '<p>&nbsp;</p>';
			}

			this.execCommand('inserthtml', html);
			this.modalClose();
			
			this.$table = $(this.doc).find('body').find('#table' + tableid);
			this.$table.click($.proxy(this.tableObserver, this));
			
			if (this.opts.autoresize === true) this.setAutoSize(false);
		},
		tableObserver: function(e)
		{
			this.$table = $(e.target).parents('table');

			this.$table_tr = this.$table.find('tr');
			this.$table_td = this.$table.find('td');
	
			this.$table_td.removeClass('current');
	
			this.$tbody = $(e.target).parents('tbody');
			this.$thead = $(this.$table).find('thead');
	
			this.$current_td = $(e.target);
			this.$current_td.addClass('current');
			
			this.$current_tr = $(e.target).parents('tr');
		},
		deleteTable: function()
		{
			$(this.$table).remove();
			this.$table = false;
			this.syncCode();
		},
		deleteRow: function()
		{
			$(this.$current_tr).remove();
			this.syncCode();
		},
		deleteColumn: function()
		{
			var index = $(this.$current_td).get(0).cellIndex;
			
			$(this.$table).find('tr').each(function()
			{
				$(this).find('td').eq(index).remove();
			});
			
			this.syncCode();
		},
		addHead: function()
		{
			if ($(this.$table).find('thead').size() !== 0)
			{
				this.deleteHead();
			}
			else
			{
				var tr = $(this.$table).find('tr').first().clone();
				tr.find('td').html('&nbsp;');
				this.$thead = $('<thead></thead>');
				this.$thead.append(tr);
				$(this.$table).prepend(this.$thead);
				this.syncCode();
			}
		},
		deleteHead: function()
		{
			$(this.$thead).remove();
			this.$thead = false;
			this.syncCode();
		},
		insertRowAbove: function()
		{
			this.insertRow('before');
		},
		insertRowBelow: function()
		{
			this.insertRow('after');
		},
		insertColumnLeft: function()
		{
			this.insertColumn('before');
		},
		insertColumnRight: function()
		{
			this.insertColumn('after');
		},
		insertRow: function(type)
		{
			var new_tr = $(this.$current_tr).clone();
			new_tr.find('td').html('&nbsp;');
			if (type === 'after')
			{
				$(this.$current_tr).after(new_tr);
			}
			else
			{
				$(this.$current_tr).before(new_tr);
			}

			this.syncCode();
		},
		insertColumn: function(type)
		{
			var index = 0;

			this.$current_td.addClass('current');

			this.$current_tr.find('td').each(function(i,s)
			{
				if ($(s).hasClass('current'))
				{
					index = i;
				}
			});

			this.$table_tr.each(function(i,s)
			{
				var current = $(s).find('td').eq(index);
				
				var td = current.clone();
				td.html('&nbsp;');
				
				if (type === 'after')
				{
					$(current).after(td);
				}
				else
				{
					$(current).before(td);
				}

			});

			this.syncCode();
		},

		// INSERT VIDEO
		showVideo: function()
		{
			if ($.browser.msie)
			{
				this.markerIE();
			}

			this.modalInit(RLANG.video, 'video', 600, $.proxy(function()
				{
					$('#redactor_insert_video_btn').click($.proxy(this.insertVideo, this));
				}, this),
				
				function()
				{
					$('#redactor_insert_video_area').focus();
				}
			);
		},
		insertVideo: function()
		{
			var data = $('#redactor_insert_video_area').val();
			data = this.stripTags(data);

			if ($.browser.msie)
			{
				$(this.doc.getElementById('span' + this.spanid)).after(data).remove();
				this.syncCode();
			}
			else
			{
				this.execCommand('inserthtml', data);
			}

			this.modalClose();
			
			if (this.opts.autoresize === true)
			{
				this.setAutoSize(false);
			}
		},

		// INSERT IMAGE
		imageEdit: function(e)
		{
			var $el = $(e.target);
			var parent = $el.parent();

			var handler = $.proxy(function()
			{
				$('#redactor_file_alt').val($el.attr('alt'));
				$('#redactor_image_edit_src').attr('href', $el.attr('src'));
				$('#redactor_form_image_align').val($el.css('float'));

				if ($(parent).get(0).tagName === 'A')
				{
					$('#redactor_file_link').val($(parent).attr('href'));
				}

				$('#redactor_image_delete_btn').click($.proxy(function() { this.imageDelete($el); }, this));
				$('#redactorSaveBtn').click($.proxy(function() { this.imageSave($el); }, this));

			}, this);

			this.modalInit(RLANG.image, 'image_edit', 380,  handler);

		},
		imageDelete: function(el)
		{
			$(el).remove();
			this.modalClose();
			this.syncCode();
			
			if (this.opts.autoresize === true)
			{
				this.setAutoSize(false);
			}			
		},
		imageSave: function(el)
		{
			var parent = $(el).parent();

			$(el).attr('alt', $('#redactor_file_alt').val());
	
			var floating = $('#redactor_form_image_align').val();
		
			if (floating === 'left')
			{
				$(el).css({ 'float': 'left', margin: '0 10px 10px 0' });
			}
			else if (floating === 'right')
			{
				$(el).css({ 'float': 'right', margin: '0 0 10px 10px' });
			}
			else
			{
				$(el).css({ 'float': 'none', margin: '0' });
			}
			
			// as link
			var link = $.trim($('#redactor_file_link').val());
			if (link !== '')
			{
				if ($(parent).get(0).tagName !== 'A')
				{
					$(el).replaceWith('<a href="' + link + '">' + this.outerHTML(el) + '</a>');
				}
				else
				{
					$(parent).attr('href', link);
				}
			}
			else
			{
				if ($(parent).get(0).tagName === 'A')
				{
					$(parent).replaceWith(this.outerHTML(el));
				}
			}

			this.modalClose();
			this.observeImages();
			this.syncCode();
			
		},
		showImage: function()
		{
			if ($.browser.msie)
			{
				this.markerIE();
			}

			var handler = $.proxy(function()
			{
				// json
				if (this.opts.imageGetJson !== false)
				{
					$.getJSON(this.opts.imageGetJson, $.proxy(function(data) {
						
						$.each(data, $.proxy(function(key, val)
						{
							var img = $('<img src="' + val.thumb + '" rel="' + val.image + '" />');
							$('#redactor_image_box').append(img);
							$(img).click($.proxy(this.imageSetThumb, this));
							
						}, this));

					}, this));
				}
				else
				{
					$('#redactor_tabs a').eq(1).remove();
				}
				
				if (this.opts.imageUpload !== false)
				{
					
					// dragupload
					if (this.isMobile() === false)
					{
						if ($('#redactor_file').size() !== 0)
						{
							$('#redactor_file').dragupload(
							{
								url: this.opts.imageUpload,
								success: $.proxy(this.imageUploadCallback, this)
							});
						}
					}

					// ajax upload
					this.uploadInit('redactor_file', { auto: true, url: this.opts.imageUpload, success: $.proxy(this.imageUploadCallback, this)  });
				}
				else
				{
					$('.redactor_tab').hide();
					if (this.opts.imageGetJson === false) 
					{
						$('#redactor_tabs').remove();
						$('#redactor_tab3').show();
					}
					else 
					{
						var tabs = $('#redactor_tabs a');
						tabs.eq(0).remove();
						tabs.eq(1).addClass('redactor_tabs_act');
						$('#redactor_tab2').show();
					}
				}

				$('#redactor_upload_btn').click($.proxy(this.imageUploadCallbackLink, this));

			}, this);
			
			var endCallback = $.proxy(function()
			{
				if (this.opts.imageUpload === false && this.opts.imageGetJson === false)
				{
					$('#redactor_file_link').focus();
				}				
			}, this);
	
			this.modalInit(RLANG.image, 'image', 570, handler, endCallback, true);

		},
		imageSetThumb: function(e)
		{
			this._imageSet('<img alt="" src="' + $(e.target).attr('rel') + '" />', true);
		},
		imageUploadCallbackLink: function()
		{
			if ($('#redactor_file_link').val() !== '')
			{
				var data = '<img src="' + $('#redactor_file_link').val() + '" />';
				this._imageSet(data, true);
			}
			else
			{
				this.modalClose();
			}
		},
		imageUploadCallback: function(data)
		{        
			this._imageSet(data);
		},
		_imageSet: function(json, link)
		{
			var html = '', data = '';
			if (link !== true)
			{
				data = $.parseJSON(json);		
				html = '<p><img src="' + data.filelink + '" /></p>';
			}
			else
			{
				html = json;
			}
		
			this.focus();
			
			if ($.browser.msie)
			{
				$(this.doc.getElementById('span' + this.spanid)).after(html).remove();
				this.syncCode();
			}
			else
			{
				this.execCommand('inserthtml', html);
			}
		
			// upload image callback
			if (link !== true && typeof this.opts.imageUploadCallback === 'function') 
			{
				this.opts.imageUploadCallback(this, data);
			}
			
			this.modalClose();
			
			$(this.doc).find('img').load($.proxy(function()
			{
				this.setAutoSize(false); 
				
			}, this));
			 
			this.observeImages();
		},
	
		// INSERT LINK
		showLink: function()
		{
			var handler = $.proxy(function()
			{
				var sel = this.getSelection();
				var url = '', text = '';
				
				if ($.browser.msie)
				{
					var parent = this.getParentNode();
					if (parent.nodeName === 'A')
					{
						this.insert_link_node = $(parent);
						text = this.insert_link_node.text();
						url = this.insert_link_node.attr('href');
					}
					else
					{
						if (this.oldIE())
						{
							text = sel.text;
						}
						else
						{
							text = sel.toString();
						}
	
						this.spanid = Math.floor(Math.random() * 99999);
		
						var html = '<span id="span' + this.spanid + '">' + text + '</span>';
						
						if (text !== '')
						{
							html = '<span id="span' + this.spanid + '">' + text + '</span>';
						}
						
						this.execCommand('inserthtml', html);
					}
				}
				else
				{					
					if (sel && sel.anchorNode && sel.anchorNode.parentNode.tagName === 'A')
					{
						url = sel.anchorNode.parentNode.href;
						text = sel.anchorNode.parentNode.text;
						if (sel.toString() === '')
						{
							this.insert_link_node = sel.anchorNode.parentNode;
						}
					}
					else
					{
						text = sel.toString();
						url = '';
					}
				}
				
				$('.redactor_link_text').val(text);
				
				var turl = url.replace(top.location.href, '');
				
				if (url.search('mailto:') === 0)
				{
					this.setModalTab(2);

					$('#redactor_tab_selected').val(2);
					$('#redactor_link_mailto').val(url.replace('mailto:', ''));
				}
				else if (turl.search(/^#/gi) === 0)
				{
					this.setModalTab(3);	
					
					$('#redactor_tab_selected').val(3);
					$('#redactor_link_anchor').val(turl.replace(/^#/gi, ''));
				}
				else
				{
					$('#redactor_link_url').val(url);
				}
				
				$('#redactor_insert_link_btn').click($.proxy(this.insertLink, this));
				

			}, this);
			
			var endCallback = function(url)
			{
				$('#redactor_link_url').focus();
			};
			
			this.modalInit(RLANG.link, 'link', 460, handler, endCallback);

		},
		insertLink: function()
		{
			var tab_selected = $('#redactor_tab_selected').val();
			var link = '', text = '';
			
			if (tab_selected === '1') // url
			{
				link = $('#redactor_link_url').val();
				text = $('#redactor_link_url_text').val();
			}
			else if (tab_selected === '2') // mailto
			{
				link = 'mailto:' + $('#redactor_link_mailto').val();
				text = $('#redactor_link_mailto_text').val();
			}
			else if (tab_selected === '3') // anchor
			{
				link = '#' + $('#redactor_link_anchor').val();
				text = $('#redactor_link_anchor_text').val();
			}			

			this._insertLink('<a href="' + link + '">' +  text + '</a> ', $.trim(text), link);

		},
		_insertLink: function(a, text, link)
		{
			if (text != '')
			{
				if (this.insert_link_node)
				{
					$(this.insert_link_node).text(text);
					$(this.insert_link_node).attr('href', link);
					this.syncCode();
				}
				else
				{
					if ($.browser.msie)
					{
						$(this.doc.getElementById('span' + this.spanid)).replaceWith(a);
						this.syncCode();
					}
					else
					{
						this.execCommand('inserthtml', a);
					}
				}
			}

			this.modalClose();
		},

		// INSERT FILE
		showFile: function()
		{
			if ($.browser.msie)
			{
				this.markerIE();
			}

			var handler = $.proxy(function()
			{
				var sel = this.getSelection();
			
				var text = '';
				
				if (this.oldIE())
				{
					text = sel.text;
				}				
				else
				{
					text = sel.toString();
				}

				$('#redactor_filename').val(text);

				// dragupload
				if (this.isMobile() === false)
				{						
					$('#redactor_file').dragupload(
					{
						url: this.opts.fileUpload, 
						success: $.proxy(function(data)
						{
							this.fileUploadCallback(data);
						}, this)
					});
				}

				this.uploadInit('redactor_file', { auto: true, url: this.opts.fileUpload, success: $.proxy(function(data) {

					this.fileUploadCallback(data);

				}, this)});
			}, this);

			this.modalInit(RLANG.file, 'file', 500, handler);
		},
		fileUploadCallback: function(json)
		{
			var data = $.parseJSON(json);
			var text = $('#redactor_filename').val();
			
			if (text === '')
			{
				text = data.filename;
			}
			
			var link = '<a href="' + data.filelink + '">' + text + '</a>';
		
			// chrome fix
			if ($.browser.webkit && !!window.chrome)
			{
				link = link + '&nbsp;'; 
			}

			if ($.browser.msie) 
			{
				if (text !== '')
				{
					$(this.doc.getElementById('span' + this.spanid)).replaceWith(link);
				}
				else
				{
					$(this.doc.getElementById('span' + this.spanid)).after(link).remove();
				}
				
				this.syncCode();
			}
			else
			{
				this.execCommand('inserthtml', link);
			}

			// file upload callback
			if (typeof this.opts.fileUploadCallback === 'function') 
			{
				this.opts.fileUploadCallback(this, data);
			}
			
			this.modalClose();
		},	
	
		
		
		// MODAL
		modalInit: function(title, url, width, handler, endCallback, scroll)
		{
			// modal overlay
			if ($('#redactor_modal_overlay').size() === 0)
			{
				this.overlay = $('<div id="redactor_modal_overlay" style="display: none;"></div>');
				$('body').prepend(this.overlay);
			}

			if (this.opts.overlay)
			{
				$('#redactor_modal_overlay').show();
				$('#redactor_modal_overlay').click($.proxy(this.modalClose, this));
			}

			if ($('#redactor_modal').size() === 0)
			{
				this.modal = $('<div id="redactor_modal" style="display: none;"><div id="redactor_modal_close">&times;</div><div id="redactor_modal_header"></div><div id="redactor_modal_inner"></div></div>');
				$('body').append(this.modal);
			}

			$('#redactor_modal_close').click($.proxy(this.modalClose, this));

			this.hdlModalClose = $.proxy(function(e) { if ( e.keyCode === 27) { this.modalClose(); } }, this);
			
			$(document).keyup(this.hdlModalClose);
			$(this.doc).keyup(this.hdlModalClose);

			$('#redactor_modal_inner').html(this.opts['modal_' + url]);
			$('#redactor_modal_header').html(title);
							
			// tabs
			if ($('#redactor_tabs').size() !== 0)
			{
				var that = this;
				$('#redactor_tabs a').each(function(i,s)
				{
					i++;
					$(s).click(function()
					{
						$('#redactor_tabs a').removeClass('redactor_tabs_act');
						$(this).addClass('redactor_tabs_act');
						$('.redactor_tab').hide();
						$('#redactor_tab' + i).show();
						$('#redactor_tab_selected').val(i);
						
						if (that.isMobile() === false)
						{
							var height = $('#redactor_modal').outerHeight();
							$('#redactor_modal').css('margin-top', '-' + (height+10)/2 + 'px');
						}
					});
				});
			}

			$('#redactor_btn_modal_close').click($.proxy(this.modalClose, this));
			
			// callback
			if (typeof(handler) === 'function')
			{
				handler();
			}
			
			// setup
			var height = $('#redactor_modal').outerHeight();

			if (this.isMobile() === false)
			{
				$('#redactor_modal').css({ position: 'fixed', top: '50%', left: '50%', width: width + 'px', height: 'auto', marginTop: '-' + (height+10)/2 + 'px', marginLeft: '-' + (width+60)/2 + 'px' }).fadeIn('fast');
			}
			else
			{
				$('#redactor_modal').css({ position: 'absolute', width: '96%', height: 'auto', top: '20px', left: '0', marginTop: '0px', marginLeft: '2%' }).show();
				this.scrolltop();				
			}


			// end callback
			if (typeof(endCallback) === 'function')
			{
				endCallback();
			}

			if (scroll === true)
			{
				$('#redactor_image_box').height(300).css('overflow', 'auto');
			}

		},
		modalClose: function()
		{
	
			$('#redactor_modal_close').unbind('click', this.modalClose);
			$('#redactor_modal').fadeOut('fast', $.proxy(function()
			{
				$('#redactor_modal_inner').html('');

				if (this.opts.overlay)
				{
					$('#redactor_modal_overlay').hide();
					$('#redactor_modal_overlay').unbind('click', this.modalClose);
				}
				
				$(document).unbind('keyup', this.hdlModalClose);
				$(this.doc).unbind('keyup', this.hdlModalClose);

			}, this));

		},
		setModalTab: function(num)
		{
			$('.redactor_tab').hide();
			var tabs = $('#redactor_tabs a');
			tabs.removeClass('redactor_tabs_act');
			tabs.eq(num-1).addClass('redactor_tabs_act');
			$('#redactor_tab' + num).show();			
		},

		// UPLOAD
		uploadInit: function(element, options)
		{
			// Upload Options
			this.uploadOptions = {
				url: false,
				success: false,
				start: false,
				trigger: false,
				auto: false,
				input: false
			};

			$.extend(this.uploadOptions, options);
	
			// Test input or form
			if ($('#' + element).size() !== 0 && $('#' + element).get(0).tagName === 'INPUT')
			{
				this.uploadOptions.input = $('#' + element);
				this.element = $($('#' + element).get(0).form);
			}
			else
			{
				this.element = $('#' + element);
			}
			
			this.element_action = this.element.attr('action');
			
			// Auto or trigger
			if (this.uploadOptions.auto)
			{
				$(this.uploadOptions.input).change($.proxy(function()
				{
					this.element.submit(function(e) { return false; });
					this.uploadSubmit();
				}, this));
	
			}
			else if (this.uploadOptions.trigger)
			{
				$('#' + this.uploadOptions.trigger).click($.proxy(this.uploadSubmit, this));
			}
		},
		uploadSubmit : function()
		{
			this.uploadForm(this.element, this.uploadFrame());
		},
		uploadFrame : function()
		{
			this.id = 'f' + Math.floor(Math.random() * 99999);
		
			var d = document.createElement('div');
			var iframe = '<iframe style="display:none" src="about:blank" id="'+this.id+'" name="'+this.id+'"></iframe>';
			d.innerHTML = iframe;
			document.body.appendChild(d);
		
			// Start
			if (this.uploadOptions.start)
			{
				this.uploadOptions.start();
			}
		
			$('#' + this.id).load($.proxy(this.uploadLoaded, this));
		
			return this.id;
		},
		uploadForm : function(f, name)
		{
			if (this.uploadOptions.input)
			{
				var formId = 'redactorUploadForm' + this.id;
				var fileId = 'redactorUploadFile' + this.id;
				this.form = $('<form  action="' + this.uploadOptions.url + '" method="POST" target="' + name + '" name="' + formId + '" id="' + formId + '" enctype="multipart/form-data"></form>');
				
				var oldElement = this.uploadOptions.input;
				var newElement = $(oldElement).clone();
				$(oldElement).attr('id', fileId);
				$(oldElement).before(newElement);
				$(oldElement).appendTo(this.form);
				$(this.form).css('position', 'absolute');
				$(this.form).css('top', '-2000px');
				$(this.form).css('left', '-2000px');
				$(this.form).appendTo('body');  
				
				this.form.submit();
			}
			else
			{
				f.attr('target', name);
				f.attr('method', 'POST');
				f.attr('enctype', 'multipart/form-data');
				f.attr('action', this.uploadOptions.url);
		
				this.element.submit();
			}
		
		},
		uploadLoaded : function()
		{
			var i = $('#' + this.id);
			var d;
			
			if (i.contentDocument)
			{
				d = i.contentDocument;
			}
			else if (i.contentWindow)
			{
				d = i.contentWindow.document;
			}
			else
			{
				d = window.frames[this.id].document;
			}
			
			if (d.location.href === "about:blank")
			{
				return true;
			}
			
			// Success
			if (this.uploadOptions.success)
			{
				// Remove bizarre <pre> tag wrappers around our json data:
				var rawString = d.body.innerHTML;
				var jsonString = rawString.match(/\{.*\}/)[0];
				this.uploadOptions.success(jsonString);
			}
		
			this.element.attr('action', this.element_action);
			this.element.attr('target', '');
		
		},
		
		// UTILITY
		markerIE: function()
		{
			this.spanid = Math.floor(Math.random() * 99999);
			this.execCommand('inserthtml', '<span id="span' + this.spanid + '"></span>');
		},
		oldIE: function()
		{
			if ($.browser.msie && parseInt($.browser.version, 10) < 9)
			{
				return true;
			}
			
			return false;
		},
		outerHTML: function(s) 
		{
			return $("<p>").append($(s).eq(0).clone()).html();
		},
		normalize: function(str)
		{
			return parseInt(str.replace('px',''), 10);
		},
		isMobile: function() 
		{
			if (/(iPhone|iPod|iPad|BlackBerry|Android)/.test(navigator.userAgent))
			{
				return true;
			}
			else
			{
				return false;
			}
		},
		scrolltop: function()
		{
			$('html,body').animate({ scrollTop: 0 }, 500);
		}					

	};
	
	
	// API
	$.fn.getDoc = function() 
	{
		return $(this.data('redactor').doc);
	};
	
	$.fn.getFrame = function() 
	{
		return this.data('redactor').$frame;
	};
	
	$.fn.getEditor = function() 
	{
		return this.data('redactor').$editor;
	};
	
	$.fn.getCode = function() 
	{
		return this.data('redactor').getCode();
	};
	
	$.fn.getText = function() 
	{
		return this.data('redactor').$editor.text();	
	};	
	
	$.fn.setCode = function(html)
	{
		this.data('redactor').setCode(html);
	};
	
	$.fn.insertHtml = function(html)
	{
		this.data('redactor').insertHtml(html);
	};
	
	$.fn.destroyEditor = function()
	{
		this.data('redactor').destroy();
		this.removeData('redactor');
	};

	$.fn.setFocus = function()
	{
		this.data('redactor').focus();
	};
	
	$.fn.execCommand = function(cmd, param)
	{
		this.data('redactor').execCommand(cmd, param);
	};

})(jQuery);

/*
	Plugin Drag and drop Upload v1.0.1
	http://imperavi.com/ 
	Copyright 2012, Imperavi Ltd.
*/
(function($){
	
	"use strict";
	
	// Initialization	
	$.fn.dragupload = function(options)
	{		
		return this.each(function() {
			var obj = new Construct(this, options);
			obj.init();
		});
	};
	
	// Options and variables	
	function Construct(el, options) {

		this.opts = $.extend({
		
			url: false,
			success: false,
			preview: false,
			
			text: RLANG.drop_file_here,
			atext: RLANG.or_choose
			
		}, options);
		
		this.$el = $(el);
	}

	// Functionality
	Construct.prototype = {
		init: function()
		{	
			if (!$.browser.opera && !$.browser.msie) 
			{	

				this.droparea = $('<div class="redactor_droparea"></div>');
				this.dropareabox = $('<div class="redactor_dropareabox">' + this.opts.text + '</div>');	
				this.dropalternative = $('<div class="redactor_dropalternative">' + this.opts.atext + '</div>');
				
				this.droparea.append(this.dropareabox);
				
				this.$el.before(this.droparea);
				this.$el.before(this.dropalternative);

				// drag over
				this.dropareabox.bind('dragover', $.proxy(function() { return this.ondrag(); }, this));
				
				// drag leave
				this.dropareabox.bind('dragleave', $.proxy(function() { return this.ondragleave(); }, this));
		
				var uploadProgress = $.proxy(function(e) 
				{ 
					var percent = parseInt(e.loaded / e.total * 100, 10);
					this.dropareabox.text('Loading ' + percent + '%');
					
				}, this);
		
				var xhr = jQuery.ajaxSettings.xhr();
				
				if (xhr.upload)
				{
					xhr.upload.addEventListener('progress', uploadProgress, false);
				}
				
				var provider = function () { return xhr; };
		
				// drop
				this.dropareabox.get(0).ondrop = $.proxy(function(event)
				{
					event.preventDefault();
					
					this.dropareabox.removeClass('hover').addClass('drop');
					
					var file = event.dataTransfer.files[0];

					var fd = new FormData();
					fd.append('file', file);

					$.ajax({
						dataType: 'html',
						url: this.opts.url,
						data: fd,
						xhr: provider,
						cache: false,
						contentType: false,
						processData: false,
						type: 'POST',
						success: $.proxy(function(data)
						{
							if (this.opts.success !== false)
							{
								this.opts.success(data);
							}
							
							if (this.opts.preview === true)
							{
								this.dropareabox.html(data);
							}
							
						}, this)
					});

				}, this);
			}
		},
		ondrag: function()
		{
			this.dropareabox.addClass('hover');
			return false;
		},
		ondragleave: function()
		{
			this.dropareabox.removeClass('hover');
			return false;
		}
	};

})(jQuery);


// Define: Linkify plugin from stackoverflow
(function($){

	"use strict";

	var url1 = /(^|&lt;|\s)(www\..+?\..+?)(\s|&gt;|$)/g,
	url2 = /(^|&lt;|\s)(((https?|ftp):\/\/|mailto:).+?)(\s|&gt;|$)/g,

		linkifyThis = function () 
		{
			var childNodes = this.childNodes,
			i = childNodes.length;
			while(i--)
			{
				var n = childNodes[i];
				if (n.nodeType === 3) 
				{
					var html = n.nodeValue;
					if (html)
					{
						html = html.replace(/&/g, '&amp;')
									.replace(/</g, '&lt;')
									.replace(/>/g, '&gt;')
									.replace(url1, '$1<a href="http://$2">$2</a>$3')
									.replace(url2, '$1<a href="$2">$2</a>$5');

						$(n).after(html).remove();
					}
				}
				else if (n.nodeType === 1  &&  !/^(a|button|textarea)$/i.test(n.tagName))
				{
					linkifyThis.call(n);
				}
			}
		};
	
	$.fn.linkify = function ()
	{
		this.each(linkifyThis);
	};

})(jQuery);
