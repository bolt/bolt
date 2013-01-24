
/*
 Masked Input plugin for jQuery
 Copyright (c) 2007-2013 Josh Bush (digitalbush.com)
 Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license)
 Version: 1.3.1
 */
(function(e){function t(){var e=document.createElement("input"),t="onpaste";return e.setAttribute(t,""),"function"==typeof e[t]?"paste":"input"}var n,a=t()+".mask",r=navigator.userAgent,i=/iphone/i.test(r),o=/android/i.test(r);e.mask={definitions:{9:"[0-9]",a:"[A-Za-z]","*":"[A-Za-z0-9]"},dataName:"rawMaskFn",placeholder:"_"},e.fn.extend({caret:function(e,t){var n;if(0!==this.length&&!this.is(":hidden"))return"number"==typeof e?(t="number"==typeof t?t:e,this.each(function(){this.setSelectionRange?this.setSelectionRange(e,t):this.createTextRange&&(n=this.createTextRange(),n.collapse(!0),n.moveEnd("character",t),n.moveStart("character",e),n.select())})):(this[0].setSelectionRange?(e=this[0].selectionStart,t=this[0].selectionEnd):document.selection&&document.selection.createRange&&(n=document.selection.createRange(),e=0-n.duplicate().moveStart("character",-1e5),t=e+n.text.length),{begin:e,end:t})},unmask:function(){return this.trigger("unmask")},mask:function(t,r){var c,l,s,u,f,h;return!t&&this.length>0?(c=e(this[0]),c.data(e.mask.dataName)()):(r=e.extend({placeholder:e.mask.placeholder,completed:null},r),l=e.mask.definitions,s=[],u=h=t.length,f=null,e.each(t.split(""),function(e,t){"?"==t?(h--,u=e):l[t]?(s.push(RegExp(l[t])),null===f&&(f=s.length-1)):s.push(null)}),this.trigger("unmask").each(function(){function c(e){for(;h>++e&&!s[e];);return e}function d(e){for(;--e>=0&&!s[e];);return e}function m(e,t){var n,a;if(!(0>e)){for(n=e,a=c(t);h>n;n++)if(s[n]){if(!(h>a&&s[n].test(R[a])))break;R[n]=R[a],R[a]=r.placeholder,a=c(a)}b(),x.caret(Math.max(f,e))}}function p(e){var t,n,a,i;for(t=e,n=r.placeholder;h>t;t++)if(s[t]){if(a=c(t),i=R[t],R[t]=n,!(h>a&&s[a].test(i)))break;n=i}}function g(e){var t,n,a,r=e.which;8===r||46===r||i&&127===r?(t=x.caret(),n=t.begin,a=t.end,0===a-n&&(n=46!==r?d(n):a=c(n-1),a=46===r?c(a):a),k(n,a),m(n,a-1),e.preventDefault()):27==r&&(x.val(S),x.caret(0,y()),e.preventDefault())}function v(t){var n,a,i,l=t.which,u=x.caret();t.ctrlKey||t.altKey||t.metaKey||32>l||l&&(0!==u.end-u.begin&&(k(u.begin,u.end),m(u.begin,u.end-1)),n=c(u.begin-1),h>n&&(a=String.fromCharCode(l),s[n].test(a)&&(p(n),R[n]=a,b(),i=c(n),o?setTimeout(e.proxy(e.fn.caret,x,i),0):x.caret(i),r.completed&&i>=h&&r.completed.call(x))),t.preventDefault())}function k(e,t){var n;for(n=e;t>n&&h>n;n++)s[n]&&(R[n]=r.placeholder)}function b(){x.val(R.join(""))}function y(e){var t,n,a=x.val(),i=-1;for(t=0,pos=0;h>t;t++)if(s[t]){for(R[t]=r.placeholder;pos++<a.length;)if(n=a.charAt(pos-1),s[t].test(n)){R[t]=n,i=t;break}if(pos>a.length)break}else R[t]===a.charAt(pos)&&t!==u&&(pos++,i=t);return e?b():u>i+1?(x.val(""),k(0,h)):(b(),x.val(x.val().substring(0,i+1))),u?t:f}var x=e(this),R=e.map(t.split(""),function(e){return"?"!=e?l[e]?r.placeholder:e:void 0}),S=x.val();x.data(e.mask.dataName,function(){return e.map(R,function(e,t){return s[t]&&e!=r.placeholder?e:null}).join("")}),x.attr("readonly")||x.one("unmask",function(){x.unbind(".mask").removeData(e.mask.dataName)}).bind("focus.mask",function(){clearTimeout(n);var e;S=x.val(),e=y(),n=setTimeout(function(){b(),e==t.length?x.caret(0,e):x.caret(e)},10)}).bind("blur.mask",function(){y(),x.val()!=S&&x.change()}).bind("keydown.mask",g).bind("keypress.mask",v).bind(a,function(){setTimeout(function(){var e=y(!0);x.caret(e),r.completed&&e==x.val().length&&r.completed.call(x)},0)}),y()}))}})})(jQuery);


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
            if(navigator.userAgent.match(/Firefox/)) {
                IS_FIREFOX = true;
            } else {
                IS_FIREFOX = false;
            }
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


