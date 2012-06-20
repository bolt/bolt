if (typeof RTOOLBAR == 'undefined') var RTOOLBAR = {};

RTOOLBAR['mini'] = {
	styles:
	{ 
		title: RLANG.styles,
		func: 'show', 				
		dropdown: 
	    {
			 p:
			 {
			 	title: RLANG.paragraph,			 
			 	exec: 'formatblock',
			 	param: '<p>'
			 },
			 blockquote:
			 {
			 	title: RLANG.quote,
			 	exec: 'formatblock',	
			 	param: '<blockquote>',
			 	style: 'font-style: italic; color: #666; padding-left: 10px;'			 			 	
			 },
			 pre:
			 {  
			 	title: RLANG.code,
			 	exec: 'formatblock',
			 	param: '<pre>',
			 	style: 'font-family: monospace, sans-serif;'
			 },
			 h1:
			 {
			 	title: RLANG.header1,			 
			 	exec: 'formatblock',   
			 	param: '<h1>',			 	
			 	style: 'font-size: 30px; line-height: 36px; font-weight: bold;'
			 },
			 h2:
			 {
			 	title: RLANG.header2,			 
			 	exec: 'formatblock',   
			 	param: '<h2>',			 	
			 	style: 'font-size: 24px; line-height: 36px; font-weight: bold;'
			 },
			 h3:
			 {
			 	title: RLANG.header3,			 
			 	exec: 'formatblock', 
			 	param: '<h3>',			 	  
			 	style: 'font-size: 20px; line-height: 30px;  font-weight: bold;'
			 },		
			 h4:
			 {
			 	title: RLANG.header4,			 
			 	exec: 'formatblock', 
			 	param: '<h3>',			 	  
			 	style: 'font-size: 16px; line-height: 26px;  font-weight: bold;'
			 }																	
		},
		separator: true
	},
	bold:
	{
		title: RLANG.bold,
		exec: 'bold'
	}, 
	italic: 
	{
		title: RLANG.italic,
		exec: 'italic',
		separator: true		
	},
	insertunorderedlist:
	{
		title: '&bull; ' + RLANG.unorderedlist,
		exec: 'insertunorderedlist'
	},
	insertorderedlist: 
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
		exec: 'indent',
		separator: true
	},
	link:
	{ 
		title: RLANG.link, 
		func: 'show', 				
		dropdown: 
		{
			link: 	{name: 'link', title: RLANG.link_insert, func: 'showLink'},
			unlink: {exec: 'unlink', name: 'unlink', title: RLANG.unlink}
		}															
	}
};