/*
	Redactor v8.0.3
	Updated: September 3, 2012
	
	http://redactorjs.com/
	
	Copyright (c) 2009-2012, Imperavi Inc.
	License: http://redactorjs.com/license/
	
	Usage: $('#content').redactor();
*/

// selection mechanism
var _0xf6db=["(6(){11 49=24;11 14={50:6(8,10){5 10.58(8)\x2692},57:6(8,10){7(8.58!=12){5 8.58(10)\x2616}46{5 8.57(10)}},55:6(8,18,10,20){7(8===10){5 18\x3C=20}7(14.29(8)\x26\x2614.29(10)){5 14.50(8,10)}7(14.29(8)\x26\x26!14.29(10)){5!14.55(10,20,8,18)}7(!14.57(8,10)){5 14.50(8,10)}7(8.47.85\x3C=18){5 40}7(8.47[18]===10){5 0\x3C=20}5 14.50(8.47[18],10)},29:6(61){5(61!=12?61.93==3:40)},81:6(41){11 62=0;88(41=41.59){62++}5 62}};11 4=49.4=(6(){6 4(2){24.2=2}4.34.31=6(){5 4.31(24.2)};4.34.37=6(){5 4.37(24.2)};4.34.38=6(){5 4.38(24.2)};4.34.45=6(){5 4.45(24.2)};4.34.44=6(){5 4.44(24.2)};4.34.52=6(25,26,23,22){5 4.52(24.2,25,26,23,22)};4.34.51=6(){5 4.51(24.2)};5 4})();7(49.35){4.67=43;4.31=6(2){11 9;5(9=2.35())\x26\x26(9.63!=12)\x26\x26(9.36!=12)};4.37=6(2){11 9;7(!((9=2.35())\x26\x26(9.36!=12))){5 12}5[9.36,9.97]};4.38=6(2){11 9;7(!((9=2.35())\x26\x26(9.63!=12))){5 12}5[9.63,9.91]};4.45=6(2){11 8,10,18,20,27,28;7(!4.31(2)){5 12}27=4.37(2),8=27[0],18=27[1];28=4.38(2),10=28[0],20=28[1];7(14.55(8,18,10,20)){5[8,18]}5[10,20]};4.44=6(2){11 8,10,18,20,27,28;7(!4.31(2)){5 12}27=4.37(2),8=27[0],18=27[1];28=4.38(2),10=28[0],20=28[1];7(14.55(8,18,10,20)){5[10,20]}5[8,18]};4.52=6(2,25,26,23,22){11 9=2.35();7(!9){5}7(23==12){23=25}7(22==12){22=26}7(9.60\x26\x269.79){9.60(25,26);9.79(23,22)}46{54=2.15.56();54.106(25,26);54.107(23,22);71{9.73()}80(41){}9.98(54)}};4.51=6(2){71{11 9=2.35();7(!9){5}9.73()}80(41){}}}46 7(49.15.39){11 69=6(42,32,30){11 19,13,21,33,64;13=42.90(\x2789\x27);19=32.103();19.60(30);64=19.77();88(43){64.86(13,13.59);19.82(13);7(!(19.87((30?\x2766\x27:\x2783\x27),32)\x3E0\x26\x26(13.59!=12))){99}}7(19.87((30?\x2766\x27:\x2783\x27),32)===-1\x26\x2613.84){19.74((30?\x27100\x27:\x2778\x27),32);21=13.84;33=19.101.85}46{21=13.48;33=14.81(13)}13.48.72(13);5[21,33]};11 68=6(42,32,30,21,33){11 36,65,19,13,53;53=0;36=14.29(21)?21:21.47[33];65=14.29(21)?21.48:21;7(14.29(21)){53=33}13=42.90(\x2789\x27);65.86(13,36||12);19=42.76.75();19.82(13);13.48.72(13);32.74((30?\x2766\x27:\x2778\x27),19);5 32[30?\x27105\x27:\x27104\x27](\x27102\x27,53)};4.67=43;4.31=6(2){11 17;2.70();7(!2.15.39){5 40}17=2.15.39.56();5 17\x26\x2617.77().15===2.15};4.45=6(2){11 17;2.70();7(!4.31(2)){5 12}17=2.15.39.56();5 69(2.15,17,43)};4.44=6(2){11 17;2.70();7(!4.31(2)){5 12}17=2.15.39.56();5 69(2.15,17,40)};4.37=6(2){5 4.45(2)};4.38=6(2){5 4.44(2)};4.52=6(2,25,26,23,22){7(23==12){23=25}7(22==12){22=26}11 17=2.15.76.75();68(2.15,17,40,23,22);68(2.15,17,43,25,26);5 17.96()};4.51=6(2){5 2.15.39.95()}}46{4.67=40}}).94(24);","|","split","||win||Selection|return|function|if|n1|sel|n2|var|null|cursorNode|Dom|document||range|o1|cursor|o2|node|foco|focn|this|orgn|orgo|_ref|_ref2|isText|bStart|hasSelection|textRange|offset|prototype|getSelection|anchorNode|getOrigin|getFocus|selection|false|e|doc|true|getEnd|getStart|else|childNodes|parentNode|root|isPreceding|clearSelection|setSelection|textOffset|r|isCursorPreceding|createRange|contains|compareDocumentPosition|previousSibling|collapse|d|k|focusNode|parent|anchorParent|StartToStart|supported|moveBoundary|getBoundary|focus|try|removeChild|removeAllRanges|setEndPoint|createTextRange|body|parentElement|EndToEnd|extend|catch|getChildIndex|moveToElementText|StartToEnd|nextSibling|length|insertBefore|compareEndPoints|while|a|createElement|focusOffset|0x02|nodeType|call|empty|select|anchorOffset|addRange|break|EndToStart|text|character|duplicate|moveEnd|moveStart|setStart|setEnd","replace","","\x5Cw+","\x5Cb","g"];eval(function (p,a,c,k,e,d){e=function (c){return c;} ;if(!_0xf6db[5][_0xf6db[4]](/^/,String)){while(c--){d[c]=k[c]||c;} ;k=[function (e){return d[e];} ];e=function (){return _0xf6db[6];} ;c=1;} ;while(c--){if(k[c]){p=p[_0xf6db[4]]( new RegExp(_0xf6db[7]+e(c)+_0xf6db[7],_0xf6db[8]),k[c]);} ;} ;return p;} (_0xf6db[0],10,108,_0xf6db[3][_0xf6db[2]](_0xf6db[1]),0,{}));

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
	deleted: 'Deleted',
	anchor: 'Anchor',
	link_new_tab: 'Open link in new tab'
};

(function($){

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
		
			cleanup: true,
		
			focus: false,
			tabindex: false,
			autoresize: true,
			minHeight: false,
			fixed: false,
			fixedTop: 0, // pixels
			fixedBox: false,
			source: true,
			shortcuts: true,
	
			mobile: true,
			air: false,
			wym: false,
			
			paragraphy: true,		
			convertLinks: true,
			convertDivs: true,
			protocol: 'http://', // for links http or https
	
			autosave: false, // false or url
			autosaveCallback: false, // function
			interval: 60, // seconds
	
			imageGetJson: false, // url (ex. /folder/images.json ) or false
			
			imageUpload: false, // url
			imageUploadCallback: false, // function
			
			fileUpload: false, // url
			fileUploadCallback: false, // function
	
			uploadCrossDomain: false,
			uploadFields: false,
	
			observeImages: true,
			overlay: true, // modal overlay
			
			allowedTags: ["code", "span", "div", "label", "a", "br", "p", "b", "i", "del", "strike", "u", 
					"img", "video", "audio", "iframe", "object", "embed", "param", "blockquote", 
					"mark", "cite", "small", "ul", "ol", "li", "hr", "dl", "dt", "dd", "sup", "sub", 
					"big", "pre", "code", "figure", "figcaption", "strong", "em", "table", "tr", "td", 
					"th", "tbody", "thead", "tfoot", "h1", "h2", "h3", "h4", "h5", "h6"],
			
			buttonsCustom: {},
			buttonsAdd: [],
			buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
					'image', 'video', 'file', 'table', 'link', '|',
					'fontcolor', 'backcolor', '|', 'alignleft', 'aligncenter', 'alignright', 'justify', '|', 'horizontalrule'],
	
			airButtons: ['formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|', 'fontcolor', 'backcolor'],
	
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
			buffer: false,
			visual: true,
						
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
						'<label><input type="checkbox" id="redactor_link_blank"> ' + RLANG.link_new_tab + 
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
	
			// get dimensions
			this.height = this.$el.css('height');
			this.width = this.$el.css('width');		
		
			// mobile
			if (this.opts.mobile === false && this.isMobile())
			{
				this.build(true);
				return false;
			}
			
			if (this.opts.paragraphy === false)
			{
				this.opts.convertDivs = false;
			}
		
			// extend buttons
			if (this.opts.air)
			{
				this.opts.buttons = this.opts.airButtons;
			}
			else if (this.opts.toolbar !== false)
			{
				if (this.opts.source === false)
				{
					var index = this.opts.buttons.indexOf('html');	
					var next = this.opts.buttons[index+1];				
					this.opts.buttons.splice(index, 1);
					if (typeof next !== 'undefined' && next === '|')
					{
						this.opts.buttons.splice(index, 1);
					}
				}				
			
				$.extend(this.opts.toolbar, this.opts.buttonsCustom);			
				$.each(this.opts.buttonsAdd, $.proxy(function(i,s)
				{
					this.opts.buttons.push(s);
					
				}, this));
			}
	
			// construct editor
			this.build();
			
			// air enable
			this.enableAir();		
			
			// toolbar
			this.buildToolbar();				
	
			// paste
			var oldsafari = false;
			if ($.browser.webkit && navigator.userAgent.indexOf('Chrome') === -1) 
			{
				var arr = $.browser.version.split('.');
				if (arr[0] < 536) oldsafari = true;
			}
			
			if (this.isMobile(true) === false && oldsafari === false)
			{
				this.$editor.bind('paste', $.proxy(function(e)
				{
					if (this.opts.cleanup === false)
					{
						return true;
					}
					
					this.setBuffer();
	
					if (this.opts.autoresize === true)
					{
						this.saveScroll = document.body.scrollTop;
					}
					else
					{
						this.saveScroll = this.$editor.scrollTop();
					}
		
					var frag = this.extractContent();
					
					setTimeout($.proxy(function()
					{				
						var pastedFrag = this.extractContent();
						this.$editor.append(frag);				
						this.restoreSelection();
						
						var html = this.getFragmentHtml(pastedFrag);
						this.pasteCleanUp(html);
					
					}, this), 1);
		
				}, this));	
			}
	
			// key handlers
			this.keyup();	
			this.keydown();			
	
			// autosave
			if (this.opts.autosave !== false)
			{
				this.autoSave();
			}			
	
			// observers
			this.observeImages();
			this.observeTables();	
			
			// FF fix
			if ($.browser.mozilla)
			{
				document.execCommand('enableObjectResizing', false, false);
				document.execCommand('enableInlineTableEditing', false, false);			
			}
				
			// focus
			if (this.opts.focus) 
			{
				this.$editor.focus();
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
		shortcuts: function(e, cmd)
		{
			e.preventDefault();		
			this.execCommand(cmd, false);
		},		
		keyup: function()
		{
			this.$editor.keyup($.proxy(function(e)
			{
				var key = e.keyCode || e.which;
				
				// callback as you type
				if (typeof this.opts.keyupCallback === 'function')
				{
					this.opts.keyupCallback(this, e);
				}
				
				// if empty
				if (key === 8 || key === 46)
				{
					this.observeImages();
					return this.formatEmpty(e);
				}
	
				// new line p
				if (key === 13 && !e.shiftKey && !e.ctrlKey && !e.metaKey)
				{
					if ($.browser.webkit && this.opts.paragraphy)
					{
						this.formatNewLine(e);
					}
					
					// convert links
					if (this.opts.convertLinks)
					{
						this.$editor.linkify();
					}							
				}
				
				this.syncCode();
	
			}, this));		
		},
		keydown: function()
		{
			this.$editor.keydown($.proxy(function(e)
			{
				var key = e.keyCode || e.which;
				var parent = this.getParentNode();
				var pre = false;
				var ctrl = e.ctrlKey || e.metaKey;
				
				if (parent && $(parent).get(0).tagName === 'PRE')
				{
					pre = true;
				}
	
				// callback keydown
				if (typeof this.opts.keydownCallback === 'function') 
				{
					this.opts.keydownCallback(this, e);	
				}
				
				// breakline
				if (this.opts.paragraphy === false && $.browser.webkit && key === 13 && !e.shiftKey && !e.ctrlKey && !e.metaKey)
				{
					e.preventDefault();			
					this.insertNodeAtCaret($('<span class="redactor-breakline"><br></span>').get(0));
					setTimeout($.proxy(function()
					{
						this.$editor.find('span.redactor-breakline').replaceWith('<br>');						
						setTimeout($.proxy(function()
						{
							this.syncCode();
						}, this), 10);
					
					}, this), 10);
					return false;
				}
				
				if (ctrl && this.opts.shortcuts)
				{
					if (key === 90)
					{
						if (this.opts.buffer !== false)
						{
							e.preventDefault();
							this.getBuffer();
						}
						else if (e.shiftKey)
						{
							this.shortcuts(e, 'redo');	// Ctrl + Shift + z
						}					
						else
						{
							this.shortcuts(e, 'undo'); // Ctrl + z
						}
					}
					else if (key === 77)
					{
						this.shortcuts(e, 'removeFormat'); // Ctrl + m
					}
					else if (key === 66)
					{
						this.shortcuts(e, 'bold'); // Ctrl + b
					}
					else if (key === 73)
					{
						this.shortcuts(e, 'italic'); // Ctrl + i
					}
					else if (key === 74) 
					{
						this.shortcuts(e, 'insertunorderedlist'); // Ctrl + j
					}
					else if (key === 75)
					{
						this.shortcuts(e, 'insertorderedlist'); // Ctrl + k
					}
					else if (key === 76)
					{
						this.shortcuts(e, 'superscript'); // Ctrl + l
					}
					else if (key === 72)
					{
						this.shortcuts(e, 'subscript'); // Ctrl + h
					}					
				}
	
				// clear undo buffer
				if (!ctrl && key !== 90)
				{
					this.opts.buffer = false;
				}
				
				// enter
				if (pre === true && key === 13)
				{
					e.preventDefault();					
					this.insertNodeAtCaret(document.createTextNode('\r\n'));
				}
	
				// tab
				if (this.opts.shortcuts && !e.shiftKey && key === 9)
				{
					if (pre === false)
					{
						this.shortcuts(e, 'indent'); // Tab
					}
					else
					{
						e.preventDefault();					
						this.insertNodeAtCaret(document.createTextNode('\t'));
					}
				}
				else if (this.opts.shortcuts && e.shiftKey && key === 9 )
				{
					this.shortcuts(e, 'outdent'); // Shift + tab
				}
				
				// safari shift key + enter
				if ($.browser.webkit && navigator.userAgent.indexOf('Chrome') === -1)
				{
					return this.safariShiftKeyEnter(e, key);
				}
			}, this));		
		},
		build: function(mobile)
		{
			if (mobile !== true)
			{		
				// container
				this.$box = $('<div class="redactor_box"></div>');
		
				// air box
				if (this.opts.air)
				{
					this.air = $('<div class="redactor_air" style="display: none;"></div>');
				}
		
				// editor
				this.textareamode = true;
				if (this.$el.get(0).tagName === 'TEXTAREA')
				{
					this.$editor = $('<div></div>');
				}
				else
				{
					this.textareamode = false;
					this.$editor = this.$el;				
					this.$el = $('<textarea name="' + this.$editor.attr('id') + '"></textarea>').css('height', this.height);
				}
				
				this.$editor.addClass('redactor_editor').attr('contenteditable', true).attr('dir', this.opts.direction);
				
				if (this.opts.tabindex !== false)
				{
					this.$editor.attr('tabindex', this.opts.tabindex);
				}
	
				if (this.opts.minHeight !== false)
				{
					this.$editor.css('min-height', this.opts.minHeight + 'px');
				}
	
				if (this.opts.wym === true)
				{
					this.$editor.addClass('redactor_editor_wym');
				}
	
				if (this.opts.autoresize === false)
				{
					this.$editor.css('height', this.height);
				}
	
				// hide textarea
				this.$el.hide();
	
				// append box and frame
				var html = '';
				if (this.textareamode)
				{
					// get html
					html = this.$el.val();
	
					this.$box.insertAfter(this.$el).append(this.$editor).append(this.$el);
				}
				else
				{	
					// get html
					html = this.$editor.html();	
					
					this.$box.insertAfter(this.$editor).append(this.$el).append(this.$editor);
								
				}
				
				// conver newlines to p
				if (this.opts.paragraphy)
				{
					html = this.paragraphy(html);
				}
				else
				{
					html = html.replace(/<p>([\w\W]*?)<\/p>/gi, '$1<br><br>');
				}
	
				// enable
				this.$editor.html(html);
				
				if (this.textareamode === false)
				{
					this.syncCode();
				}
			}
			else
			{
				if (this.$el.get(0).tagName !== 'TEXTAREA')
				{
					var html = this.$el.val();
					var textarea = $('<textarea name="' + this.$editor.attr('id') + '"></textarea>').css('height', this.height).val(html);
					this.$el.hide();
					this.$el.after(textarea);
				}
			}
			
		},
		enableAir: function()
		{
			if (this.opts.air === false)
			{
				return false;
			}
	
			this.air.hide();
			
			this.$editor.bind('textselect', $.proxy(function(e)
			{
				this.showAir(e);
				
			}, this));
			
			this.$editor.bind('textunselect', $.proxy(function()
			{
				this.air.hide();
			
			}, this));
	
		},	
		showAir: function(e)
		{
			$('.redactor_air').hide();
			
			var width = this.air.innerWidth();
			var left = e.clientX;
			
			if ($(document).width() < (left + width))
			{
				left = left - width;
			}
			
			this.air.css({ left: left + 'px', top: (e.clientY + $(document).scrollTop() + 14) + 'px' }).show();
		},
		syncCode: function()
		{
			this.$el.val(this.$editor.html());
		},
		
		// API functions
		setCode: function(html)
		{
			this.$editor.html(html).focus();
	
			this.syncCode();
		},
		getCode: function()
		{
			if (this.opts.visual)
			{
				return this.$editor.html()
			}
			else
			{
				return this.$el.val();
			}
		},
		insertHtml: function(html)
		{	
			this.$editor.focus();
			this.execCommand('inserthtml', html);
			this.observeImages();
		},
		destroy: function()
		{
			var html = this.getCode();
			
			if (this.textareamode)
			{
				this.$box.after(this.$el);
				this.$box.remove();
				this.$el.height(this.height).val(html).show();			
			}
			else
			{
				this.$box.after(this.$editor);
				this.$box.remove();
				this.$editor.removeClass('redactor_editor').removeClass('redactor_editor_wym').attr('contenteditable', false).html(html).show();					
			}
			
			$('.redactor_air').remove();
			
			for (var i = 0; i < this.dropdowns.length; i++)
			{
				this.dropdowns[i].remove();
				delete(this.dropdowns[i]);
			}			
			
		},
		// end API functions
	
		// OBSERVERS
		observeImages: function()
		{
			if (this.opts.observeImages === false)
			{
				return false;
			}
	
			this.$editor.find('img').each($.proxy(function(i,s)
			{
				if ($.browser.msie)
				{
					$(s).attr('unselectable', 'on');
				}		
		
				this.resizeImage(s);
		
			}, this));
		
		},
		observeTables: function()
		{
			this.$editor.find('table').click($.proxy(this.tableObserver, this));
		},
		observeScroll: function()
		{
			var scrolltop = $(document).scrollTop();
			var boxtop = this.$box.offset().top;
			var left = 0;

			if (scrolltop > boxtop)
			{
				var width = '100%';
				if (this.opts.fixedBox)
				{
					left = this.$editor.offset().left;
					width = this.$editor.innerWidth();
				}	
						
				this.fixed = true;
				this.$toolbar.css({ position: 'fixed', width: width, zIndex: 100, top: this.opts.fixedTop + 'px', left: left });
			}
			else
			{
				this.fixed = false;
				this.$toolbar.css({ position: 'relative', width: 'auto', zIndex: 1, top: 0, left: left });
			}
		},
	
		// BUFFER
		setBuffer: function()
		{
			this.saveSelection();
			this.opts.buffer = this.$editor.html();
		},
		getBuffer: function()
		{	
			if (this.opts.buffer === false)
			{
				return false;
			}

			this.$editor.html(this.opts.buffer);
			
			if (!$.browser.msie)
			{
				this.restoreSelection();
			}	
				
			this.opts.buffer = false;
		},
	
		// EXECCOMMAND
		execCommand: function(cmd, param)
		{
			if (this.opts.visual == false)
			{
				this.$el.focus();
				return false;
			}
		
			try
			{
				var parent;
	
				if (cmd === 'inserthtml' && $.browser.msie)
				{
					 /*** IE-Insertion-Fix by Fabio Poloni ***/
					if (!this.$editor.is(":focus"))
					{
						this.$editor.focus();
					} 
					
					document.selection.createRange().pasteHTML(param);					
				}
				else if (cmd === 'formatblock' && $.browser.msie)
				{
					document.execCommand(cmd, false, '<' + param + '>');
				}
				else if (cmd === 'unlink')
				{
					parent = this.getParentNode();
					if ($(parent).get(0).tagName === 'A')
					{
						$(parent).replaceWith($(parent).text());
					}
					else
					{
						document.execCommand(cmd, false, param);
					}
				}
				else if (cmd === 'formatblock' && param === 'blockquote')
				{
					parent = this.getParentNode();
					if ($(parent).get(0).tagName === 'BLOCKQUOTE')
					{
						document.execCommand(cmd, false, 'p');
					}
					else if ($(parent).get(0).tagName === 'P')
					{
						var parent2 = $(parent).parent();
						if ($(parent2).get(0).tagName === 'BLOCKQUOTE')
						{						
							var node = $('<p>' + $(parent).html() + '</p>');
							$(parent2).replaceWith(node);	
							this.setFocusNode(node.get(0));						
						}
						else
						{
							document.execCommand(cmd, false, param);
						}
					}
					else
					{
						document.execCommand(cmd, false, param);
					}
				}
				else if (cmd === 'formatblock' && param === 'pre')
				{
					parent = this.getParentNode();
					if ($(parent).get(0).tagName === 'PRE')
					{
						$(parent).replaceWith('<p>' + $(parent).text() + '</p>');
					}
					else
					{
						document.execCommand(cmd, false, param);
					}
				}				
				else
				{
					document.execCommand(cmd, false, param);
				}	
				
				if (cmd === 'inserthorizontalrule')
				{
					this.$editor.find('hr').removeAttr('id');
				}
				
				this.syncCode();
				
				if (this.oldIE())
				{
					this.$editor.focus();
				}
				
				if (typeof this.opts.execCommandCallback === 'function')
				{
					this.opts.execCommandCallback(this, cmd);
				}
								
				if (this.opts.air)
				{
					this.air.hide();	
				}
			}
			catch (e) { }
		},
	
		// FORMAT NEW LINE
		formatNewLine: function(e)
		{
			var parent = this.getParentNode();	
	
			if (parent.nodeName === 'DIV' && parent.className === 'redactor_editor')
			{
				var element = $(this.getCurrentNode());
	
				if (element.get(0).tagName === 'DIV' && (element.html() === '' || element.html() === '<br>'))
				{
					var newElement = $('<p>').append(element.clone().get(0).childNodes);
					element.replaceWith(newElement);
					newElement.html('<br />');
					this.setFocusNode(newElement.get(0));				
				}
			}			
		},
	
		// SAFARI SHIFT KEY + ENTER
		safariShiftKeyEnter: function(e, key)
		{
			if (e.shiftKey && key === 13)
			{
				e.preventDefault();
				this.insertNodeAtCaret($('<span><br /></span>').get(0));
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
				html = html.replace(/<br>/i, '');
			}
			
			if (html === '')
			{
				e.preventDefault();
				
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
			if (this.opts.convertDivs)
			{
				
				str = str.replace(/<div(.*?)>([\w\W]*?)<\/div>/gi, '<p>$2</p>');
			}
			
			// inner functions
			var X = function(x, a, b) { return x.replace(new RegExp(a, 'g'), b); };
			var R = function(a, b) { return X(str, a, b); };
	
			// block elements
			var blocks = '(table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|style|script|object|input|param|p|h[1-6])';
		
			//str = '<p>' + str;		
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
		
		// REMOVE TAGS
		stripTags: function(html) 
		{
			var allowed = this.opts.allowedTags;
			var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
			return html.replace(tags, function ($0, $1) 
			{
				return $.inArray($1.toLowerCase(), allowed) > '-1' ? $0 : '';
			});
		},
	
		
		// PASTE CLEANUP
		pasteCleanUp: function(html)
		{	
			// remove comments and php tags		
			html = html.replace(/<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi, '');
		
			// remove nbsp
			html = html.replace(/(&nbsp;){1,}/gi, '&nbsp;');					
		
			// remove google docs marker
			html = html.replace(/<b\sid="internal-source-marker(.*?)">([\w\W]*?)<\/b>/gi, "$2");		
		
			// strip tags
			html = this.stripTags(html);
				
			// prevert
			html = html.replace(/<td><br><\/td>/gi, '[td]');
			html = html.replace(/<a(.*?)>([\w\W]*?)<\/a>/gi, '[a$1]$2[/a]');
			html = html.replace(/<iframe(.*?)>([\w\W]*?)<\/iframe>/gi, '[iframe$1]$2[/iframe]');
			html = html.replace(/<video(.*?)>([\w\W]*?)<\/video>/gi, '[video$1]$2[/video]');
			html = html.replace(/<audio(.*?)>([\w\W]*?)<\/audio>/gi, '[audio$1]$2[/audio]');
			html = html.replace(/<embed(.*?)>([\w\W]*?)<\/embed>/gi, '[embed$1]$2[/embed]');
			html = html.replace(/<object(.*?)>([\w\W]*?)<\/object>/gi, '[object$1]$2[/object]');
			html = html.replace(/<param(.*?)>/gi, '[param$1]');
			html = html.replace(/<img(.*?)>/gi, '[img$1]');
		
			// remove attributes
			html = html.replace(/<(\w+)([\w\W]*?)>/gi, '<$1>');
			
			// remove empty
			html = html.replace(/<[^\/>][^>]*>(\s*|\t*|\n*|&nbsp;|<br>)<\/[^>]+>/gi, '');
			html = html.replace(/<[^\/>][^>]*>(\s*|\t*|\n*|&nbsp;|<br>)<\/[^>]+>/gi, '');
			
			// revert
			html = html.replace(/\[td\]/gi, '<td><br></td>');
			html = html.replace(/\[a(.*?)\]([\w\W]*?)\[\/a\]/gi, '<a$1>$2</a>');
			html = html.replace(/\[iframe(.*?)\]([\w\W]*?)\[\/iframe\]/gi, '<iframe$1>$2</iframe>');
			html = html.replace(/\[video(.*?)\]([\w\W]*?)\[\/video\]/gi, '<video$1>$2</video>');
			html = html.replace(/\[audio(.*?)\]([\w\W]*?)\[\/audio\]/gi, '<audio$1>$2</audio>');
			html = html.replace(/\[embed(.*?)\]([\w\W]*?)\[\/embed\]/gi, '<embed$1>$2</embed>');
			html = html.replace(/\[object(.*?)\]([\w\W]*?)\[\/object\]/gi, '<object$1>$2</object>');
			html = html.replace(/\[param(.*?)\]/gi, '<param$1>');
			html = html.replace(/\[img(.*?)\]/gi, '<img$1>');				
		
			// convert div to p
			if (this.opts.convertDivs)
			{
				html = html.replace(/<div(.*?)>([\w\W]*?)<\/div>/gi, '<p>$2</p>');	
			}
			
			if (this.opts.paragraphy === false)
			{
				html = html.replace(/<p>([\w\W]*?)<\/p>/gi, '$1<br>');
			}
			
			// remove span
			html = html.replace(/<span>([\w\W]*?)<\/span>/gi, '$1');
			
			html = html.replace(/\n{3,}/gi, '\n');
		
			// remove dirty p
			html = html.replace(/<p><p>/g, '<p>');
			html = html.replace(/<\/p><\/p>/g, '</p>');	
		
			this.execCommand('inserthtml', html);	
			
			if (this.opts.autoresize === true)
			{
				$(document.body).scrollTop(this.saveScroll);
			}
			else
			{
				this.$editor.scrollTop(this.saveScroll);
			}
			
		},	
		
			
		// TEXTAREA CODE FORMATTING
		formattingRemove: function(html)
		{
			// save pre
			var prebuffer = [];
			var pre = html.match(/<pre(.*?)>([\w\W]*?)<\/pre>/gi);
			if (pre !== null)
			{
				$.each(pre, function(i,s)
				{
					html = html.replace(s, 'prebuffer_' + i);
					prebuffer.push(s);
				});
			}
		
			html = html.replace(/\s{2,}/g, ' ');
			html = html.replace(/\n/g, ' ');	
			html = html.replace(/[\t]*/g, '');
			html = html.replace(/\n\s*\n/g, "\n");
			html = html.replace(/^[\s\n]*/g, '');
			html = html.replace(/[\s\n]*$/g, '');	
			html = html.replace(/>\s+</g, '><');
			
			if (prebuffer)
			{
				$.each(prebuffer, function(i,s)
				{
					html = html.replace('prebuffer_' + i, s);
				});		
			
				prebuffer = [];
			}
			
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
				this.$editor.hide();
				
				html = this.$editor.html();
				html = $.trim(this.formatting(html));
					
				this.$el.height(this.$editor.innerHeight()).val(html).show().focus();
				
				this.setBtnActive('html');
				this.opts.visual = false;
			}
			else
			{
				this.$el.hide();
				
				
				var html = this.stripTags(this.$el.val());
				this.$editor.html(html);
				this.$editor.show();
	
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
	
				this.$editor.focus();
				
				this.setBtnInactive('html');
				this.opts.visual = true;
				
				this.observeImages();
				this.observeTables();
			}
		},	
	
		// AUTOSAVE
		autoSave: function()
		{
			setInterval($.proxy(function()
			{
				$.ajax({
					url: this.opts.autosave,
					type: 'post',
					data: this.$el.attr('name') + '=' + this.getCode(),
					success: $.proxy(function(data)
					{
						// callback					
						if (typeof this.opts.autosaveCallback === 'function')
						{
							this.opts.autosaveCallback(data, this);
						}
						
					}, this)
				});
	
	
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
			
			if (this.opts.air)
			{
				$(this.air).append(this.$toolbar);
				$('body').prepend(this.air);
			}
			else
			{
				this.$box.prepend(this.$toolbar);
			}
			
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
			this.$editor.click(this.hdlHideDropDown);
	
		},
		buildButton: function(key, s)
		{
			var button = $('<a href="javascript:void(null);" title="' + s.title + '" class="redactor_btn_' + key + '"></a>');
			
			if (typeof s.func === 'undefined')
			{
				button.click($.proxy(function() { this.execCommand(s.exec, key); }, this));
			}
			else if (s.func !== 'show')
			{
				button.click($.proxy(function(e) {
				
					this[s.func](e); 
					
				}, this));
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
					_self.execCommand(mode, $(this).attr('rel'));
					
					if (mode === 'forecolor')
					{
						_self.$editor.find('font').replaceWith(function() {
							
							return $('<span style="color: ' + $(this).attr('color') + ';">' + $(this).html() + '</span>');
							
						});
					}
					
					if ($.browser.msie && mode === 'BackColor')
					{
						_self.$editor.find('font').replaceWith(function() {
							
							return $('<span style="' + $(this).attr('style') + '">' + $(this).html() + '</span>');
							
						});						
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
				
				if (this.opts.air)
				{
					var air_top = this.air.offset().top;	
	
					$(dropdown).css({ position: 'absolute', left: left + 'px', top: air_top+30 + 'px' }).show();		
				}				
				else if (this.opts.fixed && this.fixed)
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
			if (!$(e.target).hasClass('dropact'))
			{
				$(dropdown).removeClass('act');
				this.showedDropDown = false;
				this.hideAllDropDown();
			}
		},
		
		// SELECTION AND NODE MANIPULATION		
		getSelection: function ()
		{
			if (typeof window.getSelection !== 'undefined')
			{
				return document.getSelection();
			}
			else if (typeof document.selection !== 'undefined')
			{
				return document.selection.createRange();
			}
		},
		getFragmentHtml: function (fragment)
		{
			var cloned = fragment.cloneNode(true);
			var div = document.createElement('div');
			div.appendChild(cloned);
			return div.innerHTML;
		},
		extractContent: function()
		{   
			var node = this.$editor.get(0);
			var frag = document.createDocumentFragment(), child;
			while ((child = node.firstChild))
			{
				frag.appendChild(child);
			}
			
			return frag;
		},
		saveSelection: function()
		{
			this.savedSel = null;
			this.savedSelObj = null;
		
			if ($.browser.msie && parseInt($.browser.version, 10) < 9)
			{
				var node = this.$editor.get(0);
				this.savedSel = window.Selection.getOrigin(node);
				this.savedSelObj = window.Selection.getFocus(node);
			}
			else
			{
				this.savedSel = window.Selection.getOrigin(window);
				this.savedSelObj = window.Selection.getFocus(window);			
			}
		},
		restoreSelection: function()
		{	
			if (this.savedSel !== null && this.savedSelObj !== null && this.savedSel[0].tagName !== 'BODY')
			{		
				if ($(this.savedSel[0]).closest('.redactor_editor').size() == 0)
				{
					this.$editor.focus();
				}
				else
				{
					window.Selection.setSelection(window, this.savedSel[0], this.savedSel[1], this.savedSelObj[0], this.savedSelObj[1]);
				}
			}
			else 
			{
				this.$editor.focus();
			}	
		},
		getParentNode: function()
		{
			if (typeof window.getSelection !== 'undefined')
			{
				var s = window.getSelection();
				if (s.rangeCount > 0) 
				{
					return this.getSelection().getRangeAt(0).startContainer.parentNode;
				}
				else return false;
				
			}
			else if (typeof document.selection !== 'undefined')
			{
				return this.getSelection().parentElement();
			}
		},
		getCurrentNode: function()
		{
			if (typeof window.getSelection !== 'undefined')
			{
				return this.getSelection().getRangeAt(0).startContainer;
			}
			else if (typeof document.selection !== 'undefined')
			{
				return this.getSelection();
			}
		},
		setFocusNode: function(node)
		{
			if (typeof node === 'undefined')
			{
				return false;
			}
		
			try {
		
				var range = document.createRange();
				var selection = this.getSelection();
	
				if (selection !== null)
				{
					range.selectNodeContents(node);
					selection.addRange(range);
					selection.collapse(node, 0);
				}
	
				this.$editor.focus();
			
			} catch (e) { }
			
		},
		insertNodeAtCaret: function (node)
		{
			if (window.getSelection)
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
			else if (document.selection)
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
		getSelectedHtml: function()
		{
			var html = '';
			if (window.getSelection)
			{
				var sel = window.getSelection();
				if (sel.rangeCount)
				{
					var container = document.createElement("div");
					for (var i = 0, len = sel.rangeCount; i < len; ++i)
					{
						container.appendChild(sel.getRangeAt(i).cloneContents());
					}
					
					html = container.innerHTML;
		
				}
			}
			else if (document.selection)
			{
				if (document.selection.type === "Text")
				{
					html = document.selection.createRange().htmlText;
				}
			}
		
			return html;
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
		
		// RESIZE IMAGES
		resizeImage: function(resize)
		{
			var clicked = false;
			var clicker = false;
			var start_x;
			var start_y;
			var ratio = $(resize).width()/$(resize).height();
			var min_w = 10;
			var min_h = 10;
	
			$(resize).hover(function() { $(resize).css('cursor', 'nw-resize'); }, function() { $(resize).css('cursor','default'); clicked = false; });
	
			$(resize).mousedown(function(e)
			{
				e.preventDefault();
	
				ratio = $(resize).width()/$(resize).height();
	
				clicked = true;
				clicker = true;
	
				start_x = Math.round(e.pageX - $(resize).eq(0).offset().left);
				start_y = Math.round(e.pageY - $(resize).eq(0).offset().top);
			});
			
			$(resize).mouseup($.proxy(function(e)
			{
				clicked = false;
				this.syncCode();
				
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
	
					if (new_w > min_w)
					{
						$(resize).width(new_w);
					}
					
					if (new_h > min_h)
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
			this.saveSelection();
		
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
					var column = $('<td><br></td>');
					$(row).append(column);
				}
				$(table).append(row);
			}
			
			$(table_box).append(table);
			var html = $(table_box).html() + '<p></p>';
			
			this.restoreSelection();
			this.execCommand('inserthtml', html);
			this.modalClose();			
			this.observeTables();
	
		},
		tableObserver: function(e)
		{
			this.$table = $(e.target).closest('table');
	
			this.$table_tr = this.$table.find('tr');
			this.$table_td = this.$table.find('td');
	
			this.$table_td.removeClass('redactor-current-td');
	
			this.$tbody = $(e.target).closest('tbody');
			this.$thead = $(this.$table).find('thead');
	
			this.$current_td = $(e.target);
			this.$current_td.addClass('redactor-current-td');
			
			this.$current_tr = $(e.target).closest('tr');
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
	
			this.$current_td.addClass('redactor-current-td');
	
			this.$current_tr.find('td').each(function(i,s)
			{
				if ($(s).hasClass('redactor-current-td'))
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
			this.saveSelection();
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
			
			this.restoreSelection();
			this.execCommand('inserthtml', data);
			this.modalClose();
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
			this.saveSelection();
	
			var handler = $.proxy(function()
			{
				// json
				if (this.opts.imageGetJson !== false)
				{
					$.getJSON(this.opts.imageGetJson, $.proxy(function(data) {
						
						var folders = {};
						var z = 0;
						
						// folders
						$.each(data, $.proxy(function(key, val)
						{
							if (typeof val.folder !== 'undefined')
							{
								z++;
								folders[val.folder] = z;							
							}
												
						}, this));
						
						var folderclass = false;
						$.each(data, $.proxy(function(key, val)
						{
							// title
							var thumbtitle = '';
							if (typeof val.title !== 'undefined')
							{
								thumbtitle = val.title;
							}
							
							var folderkey = 0;						
							if (!$.isEmptyObject(folders) && typeof val.folder !== 'undefined')
							{
								folderkey = folders[val.folder];
								if (folderclass === false)
								{
									folderclass = '.redactorfolder' + folderkey;
								}
							}
							
							var img = $('<img src="' + val.thumb + '" class="redactorfolder redactorfolder' + folderkey + '" rel="' + val.image + '" title="' + thumbtitle + '" />');
							$('#redactor_image_box').append(img);
							$(img).click($.proxy(this.imageSetThumb, this));
							
							
						}, this));
						
						// folders
						if (!$.isEmptyObject(folders))
						{
							$('.redactorfolder').hide();
							$(folderclass).show();
												
							var onchangeFunc = function(e)
							{
								$('.redactorfolder').hide();
								$('.redactorfolder' + $(e.target).val()).show();
							}
						
							var select = $('<select id="redactor_image_box_select">');
							$.each(folders, function(k,v)
							{
								select.append($('<option value="' + v + '">' + k + '</option>'));
							});
							
							$('#redactor_image_box').before(select);
							select.change(onchangeFunc);
						}
	
					}, this));
				}
				else
				{
					$('#redactor_tabs a').eq(1).remove();
				}
				
				if (this.opts.imageUpload !== false)
				{
					
					// dragupload
					if (this.opts.uploadCrossDomain === false && this.isMobile() === false)
					{
						
						if ($('#redactor_file').size() !== 0)
						{
							$('#redactor_file').dragupload(
							{
								url: this.opts.imageUpload,
								uploadFields: this.opts.uploadFields,
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
			this._imageSet('<img src="' + $(e.target).attr('rel') + '" alt="' + $(e.target).attr('title') + '" />', true);
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
			this.restoreSelection();		
		
			if (json !== false)
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
				
				this.execCommand('inserthtml', html);
			
				// upload image callback
				if (link !== true && typeof this.opts.imageUploadCallback === 'function') 
				{
					this.opts.imageUploadCallback(this, data);
				}
			}
			
			this.modalClose();
			this.observeImages();
		},
	
		// INSERT LINK
		showLink: function()
		{
			this.saveSelection();
	
			var handler = $.proxy(function()
			{			
				this.insert_link_node = false;
				var sel = this.getSelection();
				var url = '', text = '', target = '';
				
				if ($.browser.msie)
				{
					var parent = this.getParentNode();
					if (parent.nodeName === 'A')
					{
						this.insert_link_node = $(parent);
						text = this.insert_link_node.text();
						url = this.insert_link_node.attr('href');
						target = this.insert_link_node.attr('target');
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
					}
				}
				else
				{					
					if (sel && sel.anchorNode && sel.anchorNode.parentNode.tagName === 'A')
					{
						url = sel.anchorNode.parentNode.href;
						text = sel.anchorNode.parentNode.text;
						target = sel.anchorNode.parentNode.target;
						
						if (sel.toString() === '')
						{
							this.insert_link_node = sel.anchorNode.parentNode;
						}
					}
					else
					{
						text = sel.toString();
					}
				}
				
				$('.redactor_link_text').val(text);
				
				var turl = url.replace(self.location.href, '');
				
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
				
				if (target === '_blank')
				{
					$('#redactor_link_blank').attr('checked', true);
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
			var link = '', text = '', target = '';
			
			if (tab_selected === '1') // url
			{
				link = $('#redactor_link_url').val();
				text = $('#redactor_link_url_text').val();
	
				if ($('#redactor_link_blank').attr('checked'))
				{
					target = '_blank';
				}
			
				// test http
				var re = new RegExp('^https?://', 'i');
				if (link.search(re) == -1)
				{
					link = this.opts.protocol + link;
				}
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
	
			this._insertLink('<a href="' + link + '" target="' + target + '">' +  text + '</a>&nbsp;', $.trim(text), link, target);
	
		},
		_insertLink: function(a, text, link, target)
		{
			this.$editor.focus();
			this.restoreSelection();	
		
			if (text !== '')
			{
				if (this.insert_link_node)
				{				
					$(this.insert_link_node).text(text);
					$(this.insert_link_node).attr('href', link);
					if (target !== '')
					{
						$(this.insert_link_node).attr('target', target);
					}
					this.syncCode();
				}
				else
				{
					this.execCommand('inserthtml', a);
				}
			}
			
			this.modalClose();
		},
	
		// INSERT FILE
		showFile: function()
		{
			this.saveSelection();
		
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
				if (this.opts.uploadCrossDomain === false && this.isMobile() === false)
				{						
					$('#redactor_file').dragupload(
					{
						url: this.opts.fileUpload,
						uploadFields: this.opts.uploadFields,
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
			this.restoreSelection();		
		
			if (json !== false)
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
	
				this.execCommand('inserthtml', link);
	
				// file upload callback
				if (typeof this.opts.fileUploadCallback === 'function') 
				{
					this.opts.fileUploadCallback(this, data);
				}
			}
			
			this.modalClose();
		},	
	
		
		
		// MODAL
		modalInit: function(title, url, width, handler, endCallback)
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
			this.$editor.keyup(this.hdlModalClose);
	
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
				$('#redactor_modal').css({ position: 'fixed', top: '50%', left: '50%', width: width + 'px', height: 'auto', minHeight: 'auto', marginTop: '-' + (height+10)/2 + 'px', marginLeft: '-' + (width+60)/2 + 'px' }).fadeIn('fast');
				
				this.modalSaveBodyOveflow = $(document.body).css('overflow');
				$(document.body).css('overflow', 'hidden');			
			}
			else
			{
				$('#redactor_modal').css({ position: 'fixed', width: '100%', height: '100%', top: '0', left: '0', margin: '0', minHeight: '300px' }).show();			
			}
	
			// end callback
			if (typeof(endCallback) === 'function')
			{
				endCallback();
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
				this.$editor.unbind('keyup', this.hdlModalClose);
	
			}, this));
			
			if (this.isMobile() === false)
			{
				$(document.body).css('overflow', this.modalSaveBodyOveflow);	
			}
	
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
			var iframe = '<iframe style="display:none" id="'+this.id+'" name="'+this.id+'"></iframe>';
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
				
				// append hidden fields
				if (this.opts.uploadFields !== false && typeof this.opts.uploadFields === 'object')
				{
					$.each(this.opts.uploadFields, $.proxy(function(k,v)
					{					
						if (v.indexOf('#') === 0)
						{
							v = $(v).val();
						}
						
						var hidden = $('<input type="hidden" name="' + k + '" value="' + v + '">');
						$(this.form).append(hidden);
			
					}, this));
				}				
				
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
			
			// Success
			if (this.uploadOptions.success)
			{
				if (typeof d !== 'undefined')
				{
					// Remove bizarre <pre> tag wrappers around our json data:				
					var rawString = d.body.innerHTML;
					var jsonString = rawString.match(/\{.*\}/)[0];
					this.uploadOptions.success(jsonString);
				}
				else
				{
					alert('Upload failed!');
					this.uploadOptions.success(false);
				}
			}
		
			this.element.attr('action', this.element_action);
			this.element.attr('target', '');
		
		},
		
		// UTILITY
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
		isMobile: function(ipad) 
		{
			if (ipad === true && /(iPhone|iPod|iPad|BlackBerry|Android)/.test(navigator.userAgent))
			{
				return true;
			}
			else if (/(iPhone|iPod|BlackBerry|Android)/.test(navigator.userAgent))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	
	};
	
	
	// API
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
	
	$.fn.getSelected = function() 
	{
		return this.data('redactor').getSelectedHtml();	
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
		this.data('redactor').$editor.focus();
	};
	
	$.fn.execCommand = function(cmd, param)
	{
		this.data('redactor').execCommand(cmd, param);
	};

})(jQuery);

/*
	Plugin Drag and drop Upload v1.0.2
	http://imperavi.com/ 
	Copyright 2012, Imperavi Inc.
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
			uploadFields: false,
			
			text: RLANG.drop_file_here,
			atext: RLANG.or_choose
			
		}, options);
		
		this.$el = $(el);
	}

	// Functionality
	Construct.prototype = {
		init: function()
		{	
			if (!$.browser.msie) 
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

					// append hidden fields
					if (this.opts.uploadFields !== false && typeof this.opts.uploadFields === 'object')
					{
						$.each(this.opts.uploadFields, $.proxy(function(k,v)
						{					
							if (v.indexOf('#') === 0)
							{
								v = $(v).val();
							}
							
							fd.append(k, v);
					
						}, this));
					}	
					
					// append file data
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

	var protocol = 'http://';
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
									.replace(url1, '$1<a href="' + protocol + '$2">$2</a>$3')
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




/* jQuery plugin textselect
 * version: 0.9
 * author: Josef Moravec, josef.moravec@gmail.com
 * updated: Imperavi Inc.
 * 
 */
(function($){$.event.special.textselect={setup:function(data,namespaces){$(this).data("textselected",false);$(this).data("ttt",data);$(this).bind('mouseup',$.event.special.textselect.handler)},teardown:function(data){$(this).unbind('mouseup',$.event.special.textselect.handler)},handler:function(event){var data=$(this).data("ttt");var text=$.event.special.textselect.getSelectedText(data).toString();if(text!=''){$(this).data("textselected",true);event.type="textselect";event.text=text;$.event.handle.apply(this,arguments)}},getSelectedText:function(data){var text='';if(window.getSelection)text=window.getSelection();else if(document.getSelection) text=document.getSelection();else if(document.selection)text=document.selection.createRange().text;return text}};$.event.special.textunselect={setup:function(data,namespaces){$(this).data("rttt",data);$(this).data("textselected",false);$(this).bind('mouseup',$.event.special.textunselect.handler);$(this).bind('keyup',$.event.special.textunselect.handlerKey)},teardown:function(data){$(this).unbind('mouseup',$.event.special.textunselect.handler)},handler:function(event){if($(this).data("textselected")){var data=$(this).data("rttt");var text=$.event.special.textselect.getSelectedText(data).toString();if(text==''){$(this).data("textselected",false);event.type="textunselect";$.event.handle.apply(this,arguments)}}},handlerKey:function(event){if($(this).data("textselected")){var data=$(this).data("rttt");var text=$.event.special.textselect.getSelectedText(data).toString();if((event.keyCode=27)&&(text=='')){$(this).data("textselected",false);event.type="textunselect";$.event.handle.apply(this,arguments)}}}}})(jQuery);