

/*
	Masked Input plugin for jQuery
	Copyright (c) 2007-2011 Josh Bush (digitalbush.com)
	Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license)
	Version: 1.3
*/
(function(a){var b=(a.browser.msie?"paste":"input")+".mask",c=window.orientation!=undefined;a.mask={definitions:{9:"[0-9]",a:"[A-Za-z]","*":"[A-Za-z0-9]"},dataName:"rawMaskFn"},a.fn.extend({caret:function(a,b){if(this.length!=0){if(typeof a=="number"){b=typeof b=="number"?b:a;return this.each(function(){if(this.setSelectionRange)this.setSelectionRange(a,b);else if(this.createTextRange){var c=this.createTextRange();c.collapse(!0),c.moveEnd("character",b),c.moveStart("character",a),c.select()}})}if(this[0].setSelectionRange)a=this[0].selectionStart,b=this[0].selectionEnd;else if(document.selection&&document.selection.createRange){var c=document.selection.createRange();a=0-c.duplicate().moveStart("character",-1e5),b=a+c.text.length}return{begin:a,end:b}}},unmask:function(){return this.trigger("unmask")},mask:function(d,e){if(!d&&this.length>0){var f=a(this[0]);return f.data(a.mask.dataName)()}e=a.extend({placeholder:"_",completed:null},e);var g=a.mask.definitions,h=[],i=d.length,j=null,k=d.length;a.each(d.split(""),function(a,b){b=="?"?(k--,i=a):g[b]?(h.push(new RegExp(g[b])),j==null&&(j=h.length-1)):h.push(null)});return this.trigger("unmask").each(function(){function v(a){var b=f.val(),c=-1;for(var d=0,g=0;d<k;d++)if(h[d]){l[d]=e.placeholder;while(g++<b.length){var m=b.charAt(g-1);if(h[d].test(m)){l[d]=m,c=d;break}}if(g>b.length)break}else l[d]==b.charAt(g)&&d!=i&&(g++,c=d);if(!a&&c+1<i)f.val(""),t(0,k);else if(a||c+1>=i)u(),a||f.val(f.val().substring(0,c+1));return i?d:j}function u(){return f.val(l.join("")).val()}function t(a,b){for(var c=a;c<b&&c<k;c++)h[c]&&(l[c]=e.placeholder)}function s(a){var b=a.which,c=f.caret();if(a.ctrlKey||a.altKey||a.metaKey||b<32)return!0;if(b){c.end-c.begin!=0&&(t(c.begin,c.end),p(c.begin,c.end-1));var d=n(c.begin-1);if(d<k){var g=String.fromCharCode(b);if(h[d].test(g)){q(d),l[d]=g,u();var i=n(d);f.caret(i),e.completed&&i>=k&&e.completed.call(f)}}return!1}}function r(a){var b=a.which;if(b==8||b==46||c&&b==127){var d=f.caret(),e=d.begin,g=d.end;g-e==0&&(e=b!=46?o(e):g=n(e-1),g=b==46?n(g):g),t(e,g),p(e,g-1);return!1}if(b==27){f.val(m),f.caret(0,v());return!1}}function q(a){for(var b=a,c=e.placeholder;b<k;b++)if(h[b]){var d=n(b),f=l[b];l[b]=c;if(d<k&&h[d].test(f))c=f;else break}}function p(a,b){if(!(a<0)){for(var c=a,d=n(b);c<k;c++)if(h[c]){if(d<k&&h[c].test(l[d]))l[c]=l[d],l[d]=e.placeholder;else break;d=n(d)}u(),f.caret(Math.max(j,a))}}function o(a){while(--a>=0&&!h[a]);return a}function n(a){while(++a<=k&&!h[a]);return a}var f=a(this),l=a.map(d.split(""),function(a,b){if(a!="?")return g[a]?e.placeholder:a}),m=f.val();f.data(a.mask.dataName,function(){return a.map(l,function(a,b){return h[b]&&a!=e.placeholder?a:null}).join("")}),f.attr("readonly")||f.one("unmask",function(){f.unbind(".mask").removeData(a.mask.dataName)}).bind("focus.mask",function(){m=f.val();var b=v();u();var c=function(){b==d.length?f.caret(0,b):f.caret(b)};(a.browser.msie?c:function(){setTimeout(c,0)})()}).bind("blur.mask",function(){v(),f.val()!=m&&f.change()}).bind("keydown.mask",r).bind("keypress.mask",s).bind(b,function(){setTimeout(function(){f.caret(v(!0))},0)}),v()})}})})(jQuery);



/*
 Copyright (c) 2011 Sean Cusack

 MIT-LICENSE:

 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

(function($){

    var TEXTAREA_ID = 'jquery-catchpaste-textarea';
    function getTextArea() {
        var ta = $("#"+TEXTAREA_ID);
        if( ta.length <= 0 ) ta = $('<textarea id="'+TEXTAREA_ID+'">').css("left","-9999px").css("position","absolute").css("width","1px").css("height","1px").appendTo("body");
        return ta;
    }

    function waitForPasteData( callback, options, returnHereWhenDone ) {
        // the "this" here is the target of the paste command, so the <input>
        var textarea = getTextArea();
        textarea.css("display","block").val("");
        var target = $(this);
        function returnWhenDone(event) {
            // the "this" here is the target of the keyup event, so the <textarea>
            $(textarea).css("display","none");
            $(textarea).unbind("keyup");
            var pasted = $(textarea).val();
            var called = callback.call( $(target), pasted, options );
            //console.log(pasted,called);
            $(target).focus();
            if( called !== null )
            {
                $(target).val( $(target).caret().replace( called ) );
            }
        }
        textarea.focus();
        textarea.bind( "keyup", returnWhenDone );
    }

    // why? there's no logical way (that i can find) to trap text that's being dragged in, including newlines
    // --> by the time it hits the input, the newlines have been stripped, and before that point, there's no
    //     way to retrieve it from the events leading up to it, for things dragged in that were not explicitly
    //     set to draggable (and thus can be tracked nicely)A
    // why do i think this is impossible? because even googledocs spreadsheets don't support it. QED :-P
    function cancelDragDrop(event) {
        event.stopPropagation();
        event.preventDefault();
        return false;
    }

    var IS_FIREFOX = null;

    $.fn.catchpaste = function( callback, options ) {
        //console.log(callback,options);
        if( ! options  ) options = {};
        if( IS_FIREFOX === null )
        {
            if( ( "mozilla" in $.browser ) && $.browser["mozilla"] ) IS_FIREFOX = true;
            else                                                     IS_FIREFOX = false;
        }
        if( IS_FIREFOX )
        {
            // works for Firefox:
            var pasteEvent   = "keydown";
            var pasteClosure = function(event) {
                // the "this" here is the target of the paste command, so the <input>
                if( ( ( event.keyCode == 86 ) || ( event.keyCode == 118 ) ) && ( event.metaKey || event.ctrlKey ) )
                { // CTRL-V or COMMAND-V
                    waitForPasteData.call( $(this), callback, options );
                }
            }
        }
        else
        {
            // works for Chrome and Safari:
            var pasteEvent   = "paste";
            var pasteClosure = function(event) {
                // the "this" here is the target of the paste command, so the <input>
                waitForPasteData.call( $(this), callback, options );
            }
        }
        return $(this).each( function() { $(this).bind( pasteEvent, pasteClosure ); $(this).bind("dragover",cancelDragDrop); } );
    };

})(jQuery);


/*
 * jQuery UI Tag-it!
 *
 * @version v2.0 (06/2011)
 *
 * Copyright 2011, Levy Carneiro Jr.
 * Released under the MIT license.
 * http://aehlke.github.com/tag-it/LICENSE
 *
 * Homepage:
 *   http://aehlke.github.com/tag-it/
 *
 * Authors:
 *   Levy Carneiro Jr.
 *   Martin Rehfeld
 *   Tobias Schmidt
 *   Skylar Challand
 *   Alex Ehlke
 *
 * Maintainer:
 *   Alex Ehlke - Twitter: @aehlke
 *
 * Dependencies:
 *   jQuery v1.4+
 *   jQuery UI v1.8+
 */(function(e){e.widget("ui.tagit",{options:{itemName:"item",fieldName:"tags",availableTags:[],tagSource:null,removeConfirmation:!1,caseSensitive:!0,placeholderText:null,allowSpaces:!1,animate:!0,singleField:!1,singleFieldDelimiter:",",singleFieldNode:null,tabIndex:null,onTagAdded:null,onTagRemoved:null,onTagClicked:null},_create:function(){var t=this;if(this.element.is("input")){this.tagList=e("<ul></ul>").insertAfter(this.element);this.options.singleField=!0;this.options.singleFieldNode=this.element;this.element.css("display","none")}else this.tagList=this.element.find("ul, ol").andSelf().last();this._tagInput=e('<input type="text" />').addClass("ui-widget-content");this.options.tabIndex&&this._tagInput.attr("tabindex",this.options.tabIndex);this.options.placeholderText&&this._tagInput.attr("placeholder",this.options.placeholderText);this.options.tagSource=this.options.tagSource||function(t,n){var r=t.term.toLowerCase(),i=e.grep(this.options.availableTags,function(e){return e.toLowerCase().indexOf(r)===0});n(this._subtractArray(i,this.assignedTags()))};e.isFunction(this.options.tagSource)&&(this.options.tagSource=e.proxy(this.options.tagSource,this));this.tagList.addClass("tagit").addClass("ui-widget ui-widget-content ui-corner-all").append(e('<li class="tagit-new"></li>').append(this._tagInput)).click(function(n){var r=e(n.target);r.hasClass("tagit-label")?t._trigger("onTagClicked",n,r.closest(".tagit-choice")):t._tagInput.focus()});this.tagList.children("li").each(function(){if(!e(this).hasClass("tagit-new")){t.createTag(e(this).html(),e(this).attr("class"));e(this).remove()}});if(this.options.singleField)if(this.options.singleFieldNode){var n=e(this.options.singleFieldNode),r=n.val().split(this.options.singleFieldDelimiter);n.val("");e.each(r,function(e,n){t.createTag(n)})}else this.options.singleFieldNode=this.tagList.after('<input type="hidden" style="display:none;" value="" name="'+this.options.fieldName+'" />');this._tagInput.keydown(function(n){if(n.which==e.ui.keyCode.BACKSPACE&&t._tagInput.val()===""){var r=t._lastTag();!t.options.removeConfirmation||r.hasClass("remove")?t.removeTag(r):t.options.removeConfirmation&&r.addClass("remove ui-state-highlight")}else t.options.removeConfirmation&&t._lastTag().removeClass("remove ui-state-highlight");if(n.which==e.ui.keyCode.COMMA||n.which==e.ui.keyCode.ENTER||n.which==e.ui.keyCode.TAB&&t._tagInput.val()!==""||n.which==e.ui.keyCode.SPACE&&t.options.allowSpaces!==!0&&(e.trim(t._tagInput.val()).replace(/^s*/,"").charAt(0)!='"'||e.trim(t._tagInput.val()).charAt(0)=='"'&&e.trim(t._tagInput.val()).charAt(e.trim(t._tagInput.val()).length-1)=='"'&&e.trim(t._tagInput.val()).length-1!==0)){n.preventDefault();t.createTag(t._cleanedInput());t._tagInput.autocomplete("close")}}).blur(function(e){t.createTag(t._cleanedInput())});(this.options.availableTags||this.options.tagSource)&&this._tagInput.autocomplete({source:this.options.tagSource,select:function(e,n){t._tagInput.val()===""&&t.removeTag(t._lastTag(),!1);t.createTag(n.item.value);return!1}})},_cleanedInput:function(){return e.trim(this._tagInput.val().replace(/^"(.*)"$/,"$1"))},_lastTag:function(){return this.tagList.children(".tagit-choice:last")},assignedTags:function(){var t=this,n=[];if(this.options.singleField){n=e(this.options.singleFieldNode).val().split(this.options.singleFieldDelimiter);n[0]===""&&(n=[])}else this.tagList.children(".tagit-choice").each(function(){n.push(t.tagLabel(this))});return n},_updateSingleTagsField:function(t){e(this.options.singleFieldNode).val(t.join(this.options.singleFieldDelimiter))},_subtractArray:function(t,n){var r=[];for(var i=0;i<t.length;i++)e.inArray(t[i],n)==-1&&r.push(t[i]);return r},tagLabel:function(t){return this.options.singleField?e(t).children(".tagit-label").text():e(t).children("input").val()},_isNew:function(e){var t=this,n=!0;this.tagList.children(".tagit-choice").each(function(r){if(t._formatStr(e)==t._formatStr(t.tagLabel(this))){n=!1;return!1}});return n},_formatStr:function(t){return this.options.caseSensitive?t:e.trim(t.toLowerCase())},createTag:function(t,n){var r=this;t=e.trim(t);if(!this._isNew(t)||t==="")return!1;var i=e(this.options.onTagClicked?'<a class="tagit-label"></a>':'<span class="tagit-label"></span>').text(t),s=e("<li></li>").addClass("tagit-choice ui-widget-content ui-state-default ui-corner-all").addClass(n).append(i),o=e("<span></span>").addClass("ui-icon ui-icon-close"),u=e('<a><span class="text-icon">×</span></a>').addClass("tagit-close").append(o).click(function(e){r.removeTag(s)});s.append(u);if(this.options.singleField){var a=this.assignedTags();a.push(t);this._updateSingleTagsField(a)}else{var f=i.html();s.append('<input type="hidden" style="display:none;" value="'+f+'" name="'+this.options.itemName+"["+this.options.fieldName+'][]" />')}this._trigger("onTagAdded",null,s);this._tagInput.val("");this._tagInput.parent().before(s)},removeTag:function(t,n){n=n||this.options.animate;t=e(t);this._trigger("onTagRemoved",null,t);if(this.options.singleField){var r=this.assignedTags(),i=this.tagLabel(t);r=e.grep(r,function(e){return e!=i});this._updateSingleTagsField(r)}n?t.fadeOut("fast").hide("blind",{direction:"horizontal"},"fast",function(){t.remove()}).dequeue():t.remove()},removeAll:function(){var e=this;this.tagList.children(".tagit-choice").each(function(t,n){e.removeTag(n,!1)})}})})(jQuery);
