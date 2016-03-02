/*!
 * jQuery JavaScript Library v2.2.1
 * http://jquery.com/
 *
 * Includes Sizzle.js
 * http://sizzlejs.com/
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license
 * http://jquery.org/license
 *
 * Date: 2016-02-22T19:11Z
 */
!function(a,b){"object"==typeof module&&"object"==typeof module.exports?
// For CommonJS and CommonJS-like environments where a proper `window`
// is present, execute the factory and get jQuery.
// For environments that do not have a `window` with a `document`
// (such as Node.js), expose a factory as module.exports.
// This accentuates the need for the creation of a real `window`.
// e.g. var jQuery = require("jquery")(window);
// See ticket #14549 for more info.
module.exports=a.document?b(a,!0):function(a){if(!a.document)throw new Error("jQuery requires a window with a document");return b(a)}:b(a)}("undefined"!=typeof window?window:this,function(a,b){function c(a){
// Support: iOS 8.2 (not reproducible in simulator)
// `in` check used to prevent JIT error (gh-2145)
// hasOwn isn't used here due to false negatives
// regarding Nodelist length in IE
var b=!!a&&"length"in a&&a.length,c=fa.type(a);return"function"===c||fa.isWindow(a)?!1:"array"===c||0===b||"number"==typeof b&&b>0&&b-1 in a}
// Implement the identical functionality for filter and not
function d(a,b,c){if(fa.isFunction(b))return fa.grep(a,function(a,d){/* jshint -W018 */
return!!b.call(a,d,a)!==c});if(b.nodeType)return fa.grep(a,function(a){return a===b!==c});if("string"==typeof b){if(pa.test(b))return fa.filter(b,a,c);b=fa.filter(b,a)}return fa.grep(a,function(a){return _.call(b,a)>-1!==c})}function e(a,b){for(;(a=a[b])&&1!==a.nodeType;);return a}
// Convert String-formatted options into Object-formatted ones
function f(a){var b={};return fa.each(a.match(va)||[],function(a,c){b[c]=!0}),b}/**
 * The ready event handler and self cleanup method
 */
function g(){X.removeEventListener("DOMContentLoaded",g),a.removeEventListener("load",g),fa.ready()}function h(){this.expando=fa.expando+h.uid++}function i(a,b,c){var d;
// If nothing was found internally, try to fetch any
// data from the HTML5 data-* attribute
if(void 0===c&&1===a.nodeType)if(d="data-"+b.replace(Ca,"-$&").toLowerCase(),c=a.getAttribute(d),"string"==typeof c){try{c="true"===c?!0:"false"===c?!1:"null"===c?null:+c+""===c?+c:Ba.test(c)?fa.parseJSON(c):c}catch(e){}
// Make sure we set the data so it isn't changed later
Aa.set(a,b,c)}else c=void 0;return c}function j(a,b,c,d){var e,f=1,g=20,h=d?function(){return d.cur()}:function(){return fa.css(a,b,"")},i=h(),j=c&&c[3]||(fa.cssNumber[b]?"":"px"),
// Starting value computation is required for potential unit mismatches
k=(fa.cssNumber[b]||"px"!==j&&+i)&&Ea.exec(fa.css(a,b));if(k&&k[3]!==j){
// Trust units reported by jQuery.css
j=j||k[3],
// Make sure we update the tween properties later on
c=c||[],
// Iteratively approximate from a nonzero starting point
k=+i||1;do f=f||".5",k/=f,fa.style(a,b,k+j);while(f!==(f=h()/i)&&1!==f&&--g)}
// Apply relative offset (+=/-=) if specified
return c&&(k=+k||+i||0,e=c[1]?k+(c[1]+1)*c[2]:+c[2],d&&(d.unit=j,d.start=k,d.end=e)),e}function k(a,b){
// Support: IE9-11+
// Use typeof to avoid zero-argument method invocation on host objects (#15151)
var c="undefined"!=typeof a.getElementsByTagName?a.getElementsByTagName(b||"*"):"undefined"!=typeof a.querySelectorAll?a.querySelectorAll(b||"*"):[];return void 0===b||b&&fa.nodeName(a,b)?fa.merge([a],c):c}
// Mark scripts as having already been evaluated
function l(a,b){for(var c=0,d=a.length;d>c;c++)za.set(a[c],"globalEval",!b||za.get(b[c],"globalEval"))}function m(a,b,c,d,e){for(var f,g,h,i,j,m,n=b.createDocumentFragment(),o=[],p=0,q=a.length;q>p;p++)if(f=a[p],f||0===f)
// Add nodes directly
if("object"===fa.type(f))
// Support: Android<4.1, PhantomJS<2
// push.apply(_, arraylike) throws on ancient WebKit
fa.merge(o,f.nodeType?[f]:f);else if(La.test(f)){for(g=g||n.appendChild(b.createElement("div")),
// Deserialize a standard representation
h=(Ia.exec(f)||["",""])[1].toLowerCase(),i=Ka[h]||Ka._default,g.innerHTML=i[1]+fa.htmlPrefilter(f)+i[2],
// Descend through wrappers to the right content
m=i[0];m--;)g=g.lastChild;
// Support: Android<4.1, PhantomJS<2
// push.apply(_, arraylike) throws on ancient WebKit
fa.merge(o,g.childNodes),
// Remember the top-level container
g=n.firstChild,
// Ensure the created nodes are orphaned (#12392)
g.textContent=""}else o.push(b.createTextNode(f));for(
// Remove wrapper from fragment
n.textContent="",p=0;f=o[p++];)
// Skip elements already in the context collection (trac-4087)
if(d&&fa.inArray(f,d)>-1)e&&e.push(f);else
// Capture executables
if(j=fa.contains(f.ownerDocument,f),g=k(n.appendChild(f),"script"),j&&l(g),c)for(m=0;f=g[m++];)Ja.test(f.type||"")&&c.push(f);return n}function n(){return!0}function o(){return!1}
// Support: IE9
// See #13393 for more info
function p(){try{return X.activeElement}catch(a){}}function q(a,b,c,d,e,f){var g,h;
// Types can be a map of types/handlers
if("object"==typeof b){
// ( types-Object, selector, data )
"string"!=typeof c&&(d=d||c,c=void 0);for(h in b)q(a,h,c,d,b[h],f);return a}if(null==d&&null==e?(e=c,d=c=void 0):null==e&&("string"==typeof c?(e=d,d=void 0):(e=d,d=c,c=void 0)),e===!1)e=o;else if(!e)return a;
// Use same guid so caller can remove using origFn
return 1===f&&(g=e,e=function(a){return fa().off(a),g.apply(this,arguments)},e.guid=g.guid||(g.guid=fa.guid++)),a.each(function(){fa.event.add(this,b,e,d,c)})}
// Manipulating tables requires a tbody
function r(a,b){return fa.nodeName(a,"table")&&fa.nodeName(11!==b.nodeType?b:b.firstChild,"tr")?a.getElementsByTagName("tbody")[0]||a.appendChild(a.ownerDocument.createElement("tbody")):a}
// Replace/restore the type attribute of script elements for safe DOM manipulation
function s(a){return a.type=(null!==a.getAttribute("type"))+"/"+a.type,a}function t(a){var b=Sa.exec(a.type);return b?a.type=b[1]:a.removeAttribute("type"),a}function u(a,b){var c,d,e,f,g,h,i,j;if(1===b.nodeType){
// 1. Copy private data: events, handlers, etc.
if(za.hasData(a)&&(f=za.access(a),g=za.set(b,f),j=f.events)){delete g.handle,g.events={};for(e in j)for(c=0,d=j[e].length;d>c;c++)fa.event.add(b,e,j[e][c])}
// 2. Copy user data
Aa.hasData(a)&&(h=Aa.access(a),i=fa.extend({},h),Aa.set(b,i))}}
// Fix IE bugs, see support tests
function v(a,b){var c=b.nodeName.toLowerCase();
// Fails to persist the checked state of a cloned checkbox or radio button.
"input"===c&&Ha.test(a.type)?b.checked=a.checked:"input"!==c&&"textarea"!==c||(b.defaultValue=a.defaultValue)}function w(a,b,c,d){
// Flatten any nested arrays
b=Z.apply([],b);var e,f,g,h,i,j,l=0,n=a.length,o=n-1,p=b[0],q=fa.isFunction(p);
// We can't cloneNode fragments that contain checked, in WebKit
if(q||n>1&&"string"==typeof p&&!da.checkClone&&Ra.test(p))return a.each(function(e){var f=a.eq(e);q&&(b[0]=p.call(this,e,f.html())),w(f,b,c,d)});if(n&&(e=m(b,a[0].ownerDocument,!1,a,d),f=e.firstChild,1===e.childNodes.length&&(e=f),f||d)){
// Use the original fragment for the last item
// instead of the first because it can end up
// being emptied incorrectly in certain situations (#8070).
for(g=fa.map(k(e,"script"),s),h=g.length;n>l;l++)i=e,l!==o&&(i=fa.clone(i,!0,!0),h&&fa.merge(g,k(i,"script"))),c.call(a[l],i,l);if(h)
// Evaluate executable scripts on first document insertion
for(j=g[g.length-1].ownerDocument,fa.map(g,t),l=0;h>l;l++)i=g[l],Ja.test(i.type||"")&&!za.access(i,"globalEval")&&fa.contains(j,i)&&(i.src?fa._evalUrl&&fa._evalUrl(i.src):fa.globalEval(i.textContent.replace(Ta,"")))}return a}function x(a,b,c){for(var d,e=b?fa.filter(b,a):a,f=0;null!=(d=e[f]);f++)c||1!==d.nodeType||fa.cleanData(k(d)),d.parentNode&&(c&&fa.contains(d.ownerDocument,d)&&l(k(d,"script")),d.parentNode.removeChild(d));return a}/**
 * Retrieve the actual display of a element
 * @param {String} name nodeName of the element
 * @param {Object} doc Document object
 */
// Called only from within defaultDisplay
function y(a,b){var c=fa(b.createElement(a)).appendTo(b.body),d=fa.css(c[0],"display");
// We don't have any data stored on the element,
// so use "detach" method as fast way to get rid of the element
return c.detach(),d}/**
 * Try to determine the default display value of an element
 * @param {String} nodeName
 */
function z(a){var b=X,c=Va[a];
// If the simple way fails, read from inside an iframe
// Use the already-created iframe if possible
// Always write a new HTML skeleton so Webkit and Firefox don't choke on reuse
// Support: IE
// Store the correct default display
return c||(c=y(a,b),"none"!==c&&c||(Ua=(Ua||fa("<iframe frameborder='0' width='0' height='0'/>")).appendTo(b.documentElement),b=Ua[0].contentDocument,b.write(),b.close(),c=y(a,b),Ua.detach()),Va[a]=c),c}function A(a,b,c){var d,e,f,g,h=a.style;
// Support: Opera 12.1x only
// Fall back to style even without computed
// computed is undefined for elems on document fragments
// Support: IE9
// getPropertyValue is only needed for .css('filter') (#12537)
// Remember the original values
// Put in the new values to get a computed value out
// Revert the changed values
// Support: IE9-11+
// IE returns zIndex value as an integer.
return c=c||Ya(a),g=c?c.getPropertyValue(b)||c[b]:void 0,""!==g&&void 0!==g||fa.contains(a.ownerDocument,a)||(g=fa.style(a,b)),c&&!da.pixelMarginRight()&&Xa.test(g)&&Wa.test(b)&&(d=h.width,e=h.minWidth,f=h.maxWidth,h.minWidth=h.maxWidth=h.width=g,g=c.width,h.width=d,h.minWidth=e,h.maxWidth=f),void 0!==g?g+"":g}function B(a,b){
// Define the hook, we'll check on the first run if it's really needed.
return{get:function(){
// Hook not needed (or it's not possible to use it due
// to missing dependency), remove it.
return a()?void delete this.get:(this.get=b).apply(this,arguments)}}}
// Return a css property mapped to a potentially vendor prefixed property
function C(a){
// Shortcut for names that are not vendor prefixed
if(a in db)return a;for(
// Check for vendor prefixed names
var b=a[0].toUpperCase()+a.slice(1),c=cb.length;c--;)if(a=cb[c]+b,a in db)return a}function D(a,b,c){
// Any relative (+/-) values have already been
// normalized at this point
var d=Ea.exec(b);
// Guard against undefined "subtract", e.g., when used as in cssHooks
return d?Math.max(0,d[2]-(c||0))+(d[3]||"px"):b}function E(a,b,c,d,e){for(var f=c===(d?"border":"content")?
// If we already have the right measurement, avoid augmentation
4:"width"===b?1:0,g=0;4>f;f+=2)
// Both box models exclude margin, so add it if we want it
"margin"===c&&(g+=fa.css(a,c+Fa[f],!0,e)),d?(
// border-box includes padding, so remove it if we want content
"content"===c&&(g-=fa.css(a,"padding"+Fa[f],!0,e)),
// At this point, extra isn't border nor margin, so remove border
"margin"!==c&&(g-=fa.css(a,"border"+Fa[f]+"Width",!0,e))):(g+=fa.css(a,"padding"+Fa[f],!0,e),"padding"!==c&&(g+=fa.css(a,"border"+Fa[f]+"Width",!0,e)));return g}function F(b,c,d){
// Start with offset property, which is equivalent to the border-box value
var e=!0,f="width"===c?b.offsetWidth:b.offsetHeight,g=Ya(b),h="border-box"===fa.css(b,"boxSizing",!1,g);
// Some non-html elements return undefined for offsetWidth, so check for null/undefined
// svg - https://bugzilla.mozilla.org/show_bug.cgi?id=649285
// MathML - https://bugzilla.mozilla.org/show_bug.cgi?id=491668
if(
// Support: IE11 only
// In IE 11 fullscreen elements inside of an iframe have
// 100x too small dimensions (gh-1764).
X.msFullscreenElement&&a.top!==a&&b.getClientRects().length&&(f=Math.round(100*b.getBoundingClientRect()[c])),0>=f||null==f){
// Computed unit is not pixels. Stop here and return.
if(f=A(b,c,g),(0>f||null==f)&&(f=b.style[c]),Xa.test(f))return f;
// Check for style in case a browser which returns unreliable values
// for getComputedStyle silently falls back to the reliable elem.style
e=h&&(da.boxSizingReliable()||f===b.style[c]),
// Normalize "", auto, and prepare for extra
f=parseFloat(f)||0}
// Use the active box-sizing model to add/subtract irrelevant styles
return f+E(b,c,d||(h?"border":"content"),e,g)+"px"}function G(a,b){for(var c,d,e,f=[],g=0,h=a.length;h>g;g++)d=a[g],d.style&&(f[g]=za.get(d,"olddisplay"),c=d.style.display,b?(f[g]||"none"!==c||(d.style.display=""),""===d.style.display&&Ga(d)&&(f[g]=za.access(d,"olddisplay",z(d.nodeName)))):(e=Ga(d),"none"===c&&e||za.set(d,"olddisplay",e?c:fa.css(d,"display"))));
// Set the display of most of the elements in a second loop
// to avoid the constant reflow
for(g=0;h>g;g++)d=a[g],d.style&&(b&&"none"!==d.style.display&&""!==d.style.display||(d.style.display=b?f[g]||"":"none"));return a}function H(a,b,c,d,e){return new H.prototype.init(a,b,c,d,e)}
// Animations created synchronously will run synchronously
function I(){return a.setTimeout(function(){eb=void 0}),eb=fa.now()}
// Generate parameters to create a standard animation
function J(a,b){var c,d=0,e={height:a};for(
// If we include width, step value is 1 to do all cssExpand values,
// otherwise step value is 2 to skip over Left and Right
b=b?1:0;4>d;d+=2-b)c=Fa[d],e["margin"+c]=e["padding"+c]=a;return b&&(e.opacity=e.width=a),e}function K(a,b,c){for(var d,e=(N.tweeners[b]||[]).concat(N.tweeners["*"]),f=0,g=e.length;g>f;f++)if(d=e[f].call(c,b,a))
// We're done with this property
return d}function L(a,b,c){/* jshint validthis: true */
var d,e,f,g,h,i,j,k,l=this,m={},n=a.style,o=a.nodeType&&Ga(a),p=za.get(a,"fxshow");
// Handle queue: false promises
c.queue||(h=fa._queueHooks(a,"fx"),null==h.unqueued&&(h.unqueued=0,i=h.empty.fire,h.empty.fire=function(){h.unqueued||i()}),h.unqueued++,l.always(function(){l.always(function(){h.unqueued--,fa.queue(a,"fx").length||h.empty.fire()})})),
// Height/width overflow pass
1===a.nodeType&&("height"in b||"width"in b)&&(
// Make sure that nothing sneaks out
// Record all 3 overflow attributes because IE9-10 do not
// change the overflow attribute when overflowX and
// overflowY are set to the same value
c.overflow=[n.overflow,n.overflowX,n.overflowY],j=fa.css(a,"display"),k="none"===j?za.get(a,"olddisplay")||z(a.nodeName):j,"inline"===k&&"none"===fa.css(a,"float")&&(n.display="inline-block")),c.overflow&&(n.overflow="hidden",l.always(function(){n.overflow=c.overflow[0],n.overflowX=c.overflow[1],n.overflowY=c.overflow[2]}));
// show/hide pass
for(d in b)if(e=b[d],gb.exec(e)){if(delete b[d],f=f||"toggle"===e,e===(o?"hide":"show")){
// If there is dataShow left over from a stopped hide or show
// and we are going to proceed with show, we should pretend to be hidden
if("show"!==e||!p||void 0===p[d])continue;o=!0}m[d]=p&&p[d]||fa.style(a,d)}else j=void 0;if(fa.isEmptyObject(m))"inline"===("none"===j?z(a.nodeName):j)&&(n.display=j);else{p?"hidden"in p&&(o=p.hidden):p=za.access(a,"fxshow",{}),
// Store state if its toggle - enables .stop().toggle() to "reverse"
f&&(p.hidden=!o),o?fa(a).show():l.done(function(){fa(a).hide()}),l.done(function(){var b;za.remove(a,"fxshow");for(b in m)fa.style(a,b,m[b])});for(d in m)g=K(o?p[d]:0,d,l),d in p||(p[d]=g.start,o&&(g.end=g.start,g.start="width"===d||"height"===d?1:0))}}function M(a,b){var c,d,e,f,g;
// camelCase, specialEasing and expand cssHook pass
for(c in a)if(d=fa.camelCase(c),e=b[d],f=a[c],fa.isArray(f)&&(e=f[1],f=a[c]=f[0]),c!==d&&(a[d]=f,delete a[c]),g=fa.cssHooks[d],g&&"expand"in g){f=g.expand(f),delete a[d];
// Not quite $.extend, this won't overwrite existing keys.
// Reusing 'index' because we have the correct "name"
for(c in f)c in a||(a[c]=f[c],b[c]=e)}else b[d]=e}function N(a,b,c){var d,e,f=0,g=N.prefilters.length,h=fa.Deferred().always(function(){
// Don't match elem in the :animated selector
delete i.elem}),i=function(){if(e)return!1;for(var b=eb||I(),c=Math.max(0,j.startTime+j.duration-b),
// Support: Android 2.3
// Archaic crash bug won't allow us to use `1 - ( 0.5 || 0 )` (#12497)
d=c/j.duration||0,f=1-d,g=0,i=j.tweens.length;i>g;g++)j.tweens[g].run(f);return h.notifyWith(a,[j,f,c]),1>f&&i?c:(h.resolveWith(a,[j]),!1)},j=h.promise({elem:a,props:fa.extend({},b),opts:fa.extend(!0,{specialEasing:{},easing:fa.easing._default},c),originalProperties:b,originalOptions:c,startTime:eb||I(),duration:c.duration,tweens:[],createTween:function(b,c){var d=fa.Tween(a,j.opts,b,c,j.opts.specialEasing[b]||j.opts.easing);return j.tweens.push(d),d},stop:function(b){var c=0,
// If we are going to the end, we want to run all the tweens
// otherwise we skip this part
d=b?j.tweens.length:0;if(e)return this;for(e=!0;d>c;c++)j.tweens[c].run(1);
// Resolve when we played the last frame; otherwise, reject
return b?(h.notifyWith(a,[j,1,0]),h.resolveWith(a,[j,b])):h.rejectWith(a,[j,b]),this}}),k=j.props;for(M(k,j.opts.specialEasing);g>f;f++)if(d=N.prefilters[f].call(j,a,k,j.opts))return fa.isFunction(d.stop)&&(fa._queueHooks(j.elem,j.opts.queue).stop=fa.proxy(d.stop,d)),d;
// attach callbacks from options
return fa.map(k,K,j),fa.isFunction(j.opts.start)&&j.opts.start.call(a,j),fa.fx.timer(fa.extend(i,{elem:a,anim:j,queue:j.opts.queue})),j.progress(j.opts.progress).done(j.opts.done,j.opts.complete).fail(j.opts.fail).always(j.opts.always)}function O(a){return a.getAttribute&&a.getAttribute("class")||""}
// Base "constructor" for jQuery.ajaxPrefilter and jQuery.ajaxTransport
function P(a){
// dataTypeExpression is optional and defaults to "*"
return function(b,c){"string"!=typeof b&&(c=b,b="*");var d,e=0,f=b.toLowerCase().match(va)||[];if(fa.isFunction(c))
// For each dataType in the dataTypeExpression
for(;d=f[e++];)
// Prepend if requested
"+"===d[0]?(d=d.slice(1)||"*",(a[d]=a[d]||[]).unshift(c)):(a[d]=a[d]||[]).push(c)}}
// Base inspection function for prefilters and transports
function Q(a,b,c,d){function e(h){var i;return f[h]=!0,fa.each(a[h]||[],function(a,h){var j=h(b,c,d);return"string"!=typeof j||g||f[j]?g?!(i=j):void 0:(b.dataTypes.unshift(j),e(j),!1)}),i}var f={},g=a===zb;return e(b.dataTypes[0])||!f["*"]&&e("*")}
// A special extend for ajax options
// that takes "flat" options (not to be deep extended)
// Fixes #9887
function R(a,b){var c,d,e=fa.ajaxSettings.flatOptions||{};for(c in b)void 0!==b[c]&&((e[c]?a:d||(d={}))[c]=b[c]);return d&&fa.extend(!0,a,d),a}/* Handles responses to an ajax request:
 * - finds the right dataType (mediates between content-type and expected dataType)
 * - returns the corresponding response
 */
function S(a,b,c){
// Remove auto dataType and get content-type in the process
for(var d,e,f,g,h=a.contents,i=a.dataTypes;"*"===i[0];)i.shift(),void 0===d&&(d=a.mimeType||b.getResponseHeader("Content-Type"));
// Check if we're dealing with a known content-type
if(d)for(e in h)if(h[e]&&h[e].test(d)){i.unshift(e);break}
// Check to see if we have a response for the expected dataType
if(i[0]in c)f=i[0];else{
// Try convertible dataTypes
for(e in c){if(!i[0]||a.converters[e+" "+i[0]]){f=e;break}g||(g=e)}
// Or just use first one
f=f||g}
// If we found a dataType
// We add the dataType to the list if needed
// and return the corresponding response
// If we found a dataType
// We add the dataType to the list if needed
// and return the corresponding response
return f?(f!==i[0]&&i.unshift(f),c[f]):void 0}/* Chain conversions given the request and the original response
 * Also sets the responseXXX fields on the jqXHR instance
 */
function T(a,b,c,d){var e,f,g,h,i,j={},
// Work with a copy of dataTypes in case we need to modify it for conversion
k=a.dataTypes.slice();
// Create converters map with lowercased keys
if(k[1])for(g in a.converters)j[g.toLowerCase()]=a.converters[g];
// Convert to each sequential dataType
for(f=k.shift();f;)if(a.responseFields[f]&&(c[a.responseFields[f]]=b),
// Apply the dataFilter if provided
!i&&d&&a.dataFilter&&(b=a.dataFilter(b,a.dataType)),i=f,f=k.shift())
// There's only work to do if current dataType is non-auto
if("*"===f)f=i;else if("*"!==i&&i!==f){
// If none found, seek a pair
if(g=j[i+" "+f]||j["* "+f],!g)for(e in j)if(h=e.split(" "),h[1]===f&&(g=j[i+" "+h[0]]||j["* "+h[0]])){
// Condense equivalence converters
g===!0?g=j[e]:j[e]!==!0&&(f=h[0],k.unshift(h[1]));break}
// Apply converter (if not an equivalence)
if(g!==!0)
// Unless errors are allowed to bubble, catch and return them
if(g&&a["throws"])b=g(b);else try{b=g(b)}catch(l){return{state:"parsererror",error:g?l:"No conversion from "+i+" to "+f}}}return{state:"success",data:b}}function U(a,b,c,d){var e;if(fa.isArray(b))
// Serialize array item.
fa.each(b,function(b,e){c||Db.test(a)?
// Treat each array item as a scalar.
d(a,e):
// Item is non-scalar (array or object), encode its numeric index.
U(a+"["+("object"==typeof e&&null!=e?b:"")+"]",e,c,d)});else if(c||"object"!==fa.type(b))
// Serialize scalar item.
d(a,b);else
// Serialize object item.
for(e in b)U(a+"["+e+"]",b[e],c,d)}/**
 * Gets a window from an element
 */
function V(a){return fa.isWindow(a)?a:9===a.nodeType&&a.defaultView}
// Support: Firefox 18+
// Can't be in strict mode, several libs including ASP.NET trace
// the stack via arguments.caller.callee and Firefox dies if
// you try to trace through "use strict" call chains. (#13335)
//"use strict";
var W=[],X=a.document,Y=W.slice,Z=W.concat,$=W.push,_=W.indexOf,aa={},ba=aa.toString,ca=aa.hasOwnProperty,da={},ea="2.2.1",
// Define a local copy of jQuery
fa=function(a,b){
// The jQuery object is actually just the init constructor 'enhanced'
// Need init if jQuery is called (just allow error to be thrown if not included)
return new fa.fn.init(a,b)},
// Support: Android<4.1
// Make sure we trim BOM and NBSP
ga=/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,
// Matches dashed string for camelizing
ha=/^-ms-/,ia=/-([\da-z])/gi,
// Used by jQuery.camelCase as callback to replace()
ja=function(a,b){return b.toUpperCase()};fa.fn=fa.prototype={
// The current version of jQuery being used
jquery:ea,constructor:fa,
// Start with an empty selector
selector:"",
// The default length of a jQuery object is 0
length:0,toArray:function(){return Y.call(this)},
// Get the Nth element in the matched element set OR
// Get the whole matched element set as a clean array
get:function(a){
// Return just the one element from the set
// Return all the elements in a clean array
return null!=a?0>a?this[a+this.length]:this[a]:Y.call(this)},
// Take an array of elements and push it onto the stack
// (returning the new matched element set)
pushStack:function(a){
// Build a new jQuery matched element set
var b=fa.merge(this.constructor(),a);
// Return the newly-formed element set
// Add the old object onto the stack (as a reference)
return b.prevObject=this,b.context=this.context,b},
// Execute a callback for every element in the matched set.
each:function(a){return fa.each(this,a)},map:function(a){return this.pushStack(fa.map(this,function(b,c){return a.call(b,c,b)}))},slice:function(){return this.pushStack(Y.apply(this,arguments))},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},eq:function(a){var b=this.length,c=+a+(0>a?b:0);return this.pushStack(c>=0&&b>c?[this[c]]:[])},end:function(){return this.prevObject||this.constructor()},
// For internal use only.
// Behaves like an Array's method, not like a jQuery method.
push:$,sort:W.sort,splice:W.splice},fa.extend=fa.fn.extend=function(){var a,b,c,d,e,f,g=arguments[0]||{},h=1,i=arguments.length,j=!1;for(
// Handle a deep copy situation
"boolean"==typeof g&&(j=g,g=arguments[h]||{},h++),
// Handle case when target is a string or something (possible in deep copy)
"object"==typeof g||fa.isFunction(g)||(g={}),
// Extend jQuery itself if only one argument is passed
h===i&&(g=this,h--);i>h;h++)
// Only deal with non-null/undefined values
if(null!=(a=arguments[h]))
// Extend the base object
for(b in a)c=g[b],d=a[b],g!==d&&(j&&d&&(fa.isPlainObject(d)||(e=fa.isArray(d)))?(e?(e=!1,f=c&&fa.isArray(c)?c:[]):f=c&&fa.isPlainObject(c)?c:{},g[b]=fa.extend(j,f,d)):void 0!==d&&(g[b]=d));
// Return the modified object
return g},fa.extend({
// Unique for each copy of jQuery on the page
expando:"jQuery"+(ea+Math.random()).replace(/\D/g,""),
// Assume jQuery is ready without the ready module
isReady:!0,error:function(a){throw new Error(a)},noop:function(){},isFunction:function(a){return"function"===fa.type(a)},isArray:Array.isArray,isWindow:function(a){return null!=a&&a===a.window},isNumeric:function(a){
// parseFloat NaNs numeric-cast false positives (null|true|false|"")
// ...but misinterprets leading-number strings, particularly hex literals ("0x...")
// subtraction forces infinities to NaN
// adding 1 corrects loss of precision from parseFloat (#15100)
var b=a&&a.toString();return!fa.isArray(a)&&b-parseFloat(b)+1>=0},isPlainObject:function(a){
// Not plain objects:
// - Any object or value whose internal [[Class]] property is not "[object Object]"
// - DOM nodes
// - window
// Not plain objects:
// - Any object or value whose internal [[Class]] property is not "[object Object]"
// - DOM nodes
// - window
return"object"!==fa.type(a)||a.nodeType||fa.isWindow(a)?!1:!a.constructor||ca.call(a.constructor.prototype,"isPrototypeOf")},isEmptyObject:function(a){var b;for(b in a)return!1;return!0},type:function(a){return null==a?a+"":"object"==typeof a||"function"==typeof a?aa[ba.call(a)]||"object":typeof a},
// Evaluates a script in a global context
globalEval:function(a){var b,c=eval;a=fa.trim(a),a&&(1===a.indexOf("use strict")?(b=X.createElement("script"),b.text=a,X.head.appendChild(b).parentNode.removeChild(b)):c(a))},
// Convert dashed to camelCase; used by the css and data modules
// Support: IE9-11+
// Microsoft forgot to hump their vendor prefix (#9572)
camelCase:function(a){return a.replace(ha,"ms-").replace(ia,ja)},nodeName:function(a,b){return a.nodeName&&a.nodeName.toLowerCase()===b.toLowerCase()},each:function(a,b){var d,e=0;if(c(a))for(d=a.length;d>e&&b.call(a[e],e,a[e])!==!1;e++);else for(e in a)if(b.call(a[e],e,a[e])===!1)break;return a},
// Support: Android<4.1
trim:function(a){return null==a?"":(a+"").replace(ga,"")},
// results is for internal usage only
makeArray:function(a,b){var d=b||[];return null!=a&&(c(Object(a))?fa.merge(d,"string"==typeof a?[a]:a):$.call(d,a)),d},inArray:function(a,b,c){return null==b?-1:_.call(b,a,c)},merge:function(a,b){for(var c=+b.length,d=0,e=a.length;c>d;d++)a[e++]=b[d];return a.length=e,a},grep:function(a,b,c){
// Go through the array, only saving the items
// that pass the validator function
for(var d,e=[],f=0,g=a.length,h=!c;g>f;f++)d=!b(a[f],f),d!==h&&e.push(a[f]);return e},
// arg is for internal usage only
map:function(a,b,d){var e,f,g=0,h=[];
// Go through the array, translating each of the items to their new values
if(c(a))for(e=a.length;e>g;g++)f=b(a[g],g,d),null!=f&&h.push(f);else for(g in a)f=b(a[g],g,d),null!=f&&h.push(f);
// Flatten any nested arrays
return Z.apply([],h)},
// A global GUID counter for objects
guid:1,
// Bind a function to a context, optionally partially applying any
// arguments.
proxy:function(a,b){var c,d,e;
// Quick check to determine if target is callable, in the spec
// this throws a TypeError, but we will just return undefined.
// Quick check to determine if target is callable, in the spec
// this throws a TypeError, but we will just return undefined.
// Simulated bind
// Set the guid of unique handler to the same of original handler, so it can be removed
return"string"==typeof b&&(c=a[b],b=a,a=c),fa.isFunction(a)?(d=Y.call(arguments,2),e=function(){return a.apply(b||this,d.concat(Y.call(arguments)))},e.guid=a.guid=a.guid||fa.guid++,e):void 0},now:Date.now,
// jQuery.support is not used in Core but other projects attach their
// properties to it so it needs to exist.
support:da}),
// JSHint would error on this code due to the Symbol not being defined in ES5.
// Defining this global in .jshintrc would create a danger of using the global
// unguarded in another place, it seems safer to just disable JSHint for these
// three lines.
/* jshint ignore: start */
"function"==typeof Symbol&&(fa.fn[Symbol.iterator]=W[Symbol.iterator]),/* jshint ignore: end */
// Populate the class2type map
fa.each("Boolean Number String Function Array Date RegExp Object Error Symbol".split(" "),function(a,b){aa["[object "+b+"]"]=b.toLowerCase()});var ka=/*!
 * Sizzle CSS Selector Engine v2.2.1
 * http://sizzlejs.com/
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license
 * http://jquery.org/license
 *
 * Date: 2015-10-17
 */
function(a){function b(a,b,c,d){var e,f,g,h,i,j,l,n,o=b&&b.ownerDocument,
// nodeType defaults to 9, since context defaults to document
p=b?b.nodeType:9;
// Return early from calls with invalid selector or context
if(c=c||[],"string"!=typeof a||!a||1!==p&&9!==p&&11!==p)return c;
// Try to shortcut find operations (as opposed to filters) in HTML documents
if(!d&&((b?b.ownerDocument||b:O)!==G&&F(b),b=b||G,I)){
// If the selector is sufficiently simple, try using a "get*By*" DOM method
// (excepting DocumentFragment context, where the methods don't exist)
if(11!==p&&(j=ra.exec(a)))
// ID selector
if(e=j[1]){
// Document context
if(9===p){if(!(g=b.getElementById(e)))return c;
// Support: IE, Opera, Webkit
// TODO: identify versions
// getElementById can match elements by name instead of ID
if(g.id===e)return c.push(g),c}else
// Support: IE, Opera, Webkit
// TODO: identify versions
// getElementById can match elements by name instead of ID
if(o&&(g=o.getElementById(e))&&M(b,g)&&g.id===e)return c.push(g),c}else{if(j[2])return $.apply(c,b.getElementsByTagName(a)),c;if((e=j[3])&&v.getElementsByClassName&&b.getElementsByClassName)return $.apply(c,b.getElementsByClassName(e)),c}
// Take advantage of querySelectorAll
if(v.qsa&&!T[a+" "]&&(!J||!J.test(a))){if(1!==p)o=b,n=a;else if("object"!==b.nodeName.toLowerCase()){for(
// Capture the context ID, setting it first if necessary
(h=b.getAttribute("id"))?h=h.replace(ta,"\\$&"):b.setAttribute("id",h=N),
// Prefix every selector in the list
l=z(a),f=l.length,i=ma.test(h)?"#"+h:"[id='"+h+"']";f--;)l[f]=i+" "+m(l[f]);n=l.join(","),
// Expand context for sibling selectors
o=sa.test(a)&&k(b.parentNode)||b}if(n)try{return $.apply(c,o.querySelectorAll(n)),c}catch(q){}finally{h===N&&b.removeAttribute("id")}}}
// All others
return B(a.replace(ha,"$1"),b,c,d)}/**
 * Create key-value caches of limited size
 * @returns {function(string, object)} Returns the Object data after storing it on itself with
 *	property name the (space-suffixed) string and (if the cache is larger than Expr.cacheLength)
 *	deleting the oldest entry
 */
function c(){function a(c,d){
// Use (key + " ") to avoid collision with native prototype properties (see Issue #157)
// Only keep the most recent entries
return b.push(c+" ")>w.cacheLength&&delete a[b.shift()],a[c+" "]=d}var b=[];return a}/**
 * Mark a function for special use by Sizzle
 * @param {Function} fn The function to mark
 */
function d(a){return a[N]=!0,a}/**
 * Support testing using an element
 * @param {Function} fn Passed the created div and expects a boolean result
 */
function e(a){var b=G.createElement("div");try{return!!a(b)}catch(c){return!1}finally{
// Remove from its parent by default
b.parentNode&&b.parentNode.removeChild(b),
// release memory in IE
b=null}}/**
 * Adds the same handler for all of the specified attrs
 * @param {String} attrs Pipe-separated list of attributes
 * @param {Function} handler The method that will be applied
 */
function f(a,b){for(var c=a.split("|"),d=c.length;d--;)w.attrHandle[c[d]]=b}/**
 * Checks document order of two siblings
 * @param {Element} a
 * @param {Element} b
 * @returns {Number} Returns less than 0 if a precedes b, greater than 0 if a follows b
 */
function g(a,b){var c=b&&a,d=c&&1===a.nodeType&&1===b.nodeType&&(~b.sourceIndex||V)-(~a.sourceIndex||V);
// Use IE sourceIndex if available on both nodes
if(d)return d;
// Check if b follows a
if(c)for(;c=c.nextSibling;)if(c===b)return-1;return a?1:-1}/**
 * Returns a function to use in pseudos for input types
 * @param {String} type
 */
function h(a){return function(b){var c=b.nodeName.toLowerCase();return"input"===c&&b.type===a}}/**
 * Returns a function to use in pseudos for buttons
 * @param {String} type
 */
function i(a){return function(b){var c=b.nodeName.toLowerCase();return("input"===c||"button"===c)&&b.type===a}}/**
 * Returns a function to use in pseudos for positionals
 * @param {Function} fn
 */
function j(a){return d(function(b){return b=+b,d(function(c,d){for(var e,f=a([],c.length,b),g=f.length;g--;)c[e=f[g]]&&(c[e]=!(d[e]=c[e]))})})}/**
 * Checks a node for validity as a Sizzle context
 * @param {Element|Object=} context
 * @returns {Element|Object|Boolean} The input node if acceptable, otherwise a falsy value
 */
function k(a){return a&&"undefined"!=typeof a.getElementsByTagName&&a}
// Easy API for creating new setFilters
function l(){}function m(a){for(var b=0,c=a.length,d="";c>b;b++)d+=a[b].value;return d}function n(a,b,c){var d=b.dir,e=c&&"parentNode"===d,f=Q++;
// Check against closest ancestor/preceding element
// Check against all ancestor/preceding elements
return b.first?function(b,c,f){for(;b=b[d];)if(1===b.nodeType||e)return a(b,c,f)}:function(b,c,g){var h,i,j,k=[P,f];
// We can't set arbitrary data on XML nodes, so they don't benefit from combinator caching
if(g){for(;b=b[d];)if((1===b.nodeType||e)&&a(b,c,g))return!0}else for(;b=b[d];)if(1===b.nodeType||e){if(j=b[N]||(b[N]={}),i=j[b.uniqueID]||(j[b.uniqueID]={}),(h=i[d])&&h[0]===P&&h[1]===f)
// Assign to newCache so results back-propagate to previous elements
return k[2]=h[2];
// A match means we're done; a fail means we have to keep checking
if(
// Reuse newcache so results back-propagate to previous elements
i[d]=k,k[2]=a(b,c,g))return!0}}}function o(a){return a.length>1?function(b,c,d){for(var e=a.length;e--;)if(!a[e](b,c,d))return!1;return!0}:a[0]}function p(a,c,d){for(var e=0,f=c.length;f>e;e++)b(a,c[e],d);return d}function q(a,b,c,d,e){for(var f,g=[],h=0,i=a.length,j=null!=b;i>h;h++)(f=a[h])&&(c&&!c(f,d,e)||(g.push(f),j&&b.push(h)));return g}function r(a,b,c,e,f,g){return e&&!e[N]&&(e=r(e)),f&&!f[N]&&(f=r(f,g)),d(function(d,g,h,i){var j,k,l,m=[],n=[],o=g.length,
// Get initial elements from seed or context
r=d||p(b||"*",h.nodeType?[h]:h,[]),
// Prefilter to get matcher input, preserving a map for seed-results synchronization
s=!a||!d&&b?r:q(r,m,a,h,i),t=c?f||(d?a:o||e)?
// ...intermediate processing is necessary
[]:g:s;
// Apply postFilter
if(
// Find primary matches
c&&c(s,t,h,i),e)for(j=q(t,n),e(j,[],h,i),
// Un-match failing elements by moving them back to matcherIn
k=j.length;k--;)(l=j[k])&&(t[n[k]]=!(s[n[k]]=l));if(d){if(f||a){if(f){for(
// Get the final matcherOut by condensing this intermediate into postFinder contexts
j=[],k=t.length;k--;)(l=t[k])&&
// Restore matcherIn since elem is not yet a final match
j.push(s[k]=l);f(null,t=[],j,i)}for(
// Move matched elements from seed to results to keep them synchronized
k=t.length;k--;)(l=t[k])&&(j=f?aa(d,l):m[k])>-1&&(d[j]=!(g[j]=l))}}else t=q(t===g?t.splice(o,t.length):t),f?f(null,g,t,i):$.apply(g,t)})}function s(a){for(var b,c,d,e=a.length,f=w.relative[a[0].type],g=f||w.relative[" "],h=f?1:0,
// The foundational matcher ensures that elements are reachable from top-level context(s)
i=n(function(a){return a===b},g,!0),j=n(function(a){return aa(b,a)>-1},g,!0),k=[function(a,c,d){var e=!f&&(d||c!==C)||((b=c).nodeType?i(a,c,d):j(a,c,d));
// Avoid hanging onto element (issue #299)
return b=null,e}];e>h;h++)if(c=w.relative[a[h].type])k=[n(o(k),c)];else{
// Return special upon seeing a positional matcher
if(c=w.filter[a[h].type].apply(null,a[h].matches),c[N]){for(
// Find the next relative operator (if any) for proper handling
d=++h;e>d&&!w.relative[a[d].type];d++);
// If the preceding token was a descendant combinator, insert an implicit any-element `*`
return r(h>1&&o(k),h>1&&m(a.slice(0,h-1).concat({value:" "===a[h-2].type?"*":""})).replace(ha,"$1"),c,d>h&&s(a.slice(h,d)),e>d&&s(a=a.slice(d)),e>d&&m(a))}k.push(c)}return o(k)}function t(a,c){var e=c.length>0,f=a.length>0,g=function(d,g,h,i,j){var k,l,m,n=0,o="0",p=d&&[],r=[],s=C,
// We must always have either seed elements or outermost context
t=d||f&&w.find.TAG("*",j),
// Use integer dirruns iff this is the outermost matcher
u=P+=null==s?1:Math.random()||.1,v=t.length;
// Add elements passing elementMatchers directly to results
// Support: IE<9, Safari
// Tolerate NodeList properties (IE: "length"; Safari: <number>) matching elements by id
for(j&&(C=g===G||g||j);o!==v&&null!=(k=t[o]);o++){if(f&&k){for(l=0,g||k.ownerDocument===G||(F(k),h=!I);m=a[l++];)if(m(k,g||G,h)){i.push(k);break}j&&(P=u)}
// Track unmatched elements for set filters
e&&(
// They will have gone through all possible matchers
(k=!m&&k)&&n--,
// Lengthen the array for every element, matched or not
d&&p.push(k))}
// Apply set filters to unmatched elements
// NOTE: This can be skipped if there are no unmatched elements (i.e., `matchedCount`
// equals `i`), unless we didn't visit _any_ elements in the above loop because we have
// no element matchers and no seed.
// Incrementing an initially-string "0" `i` allows `i` to remain a string only in that
// case, which will result in a "00" `matchedCount` that differs from `i` but is also
// numerically zero.
if(n+=o,e&&o!==n){for(l=0;m=c[l++];)m(p,r,g,h);if(d){
// Reintegrate element matches to eliminate the need for sorting
if(n>0)for(;o--;)p[o]||r[o]||(r[o]=Y.call(i));
// Discard index placeholder values to get only actual matches
r=q(r)}
// Add matches to results
$.apply(i,r),
// Seedless set matches succeeding multiple successful matchers stipulate sorting
j&&!d&&r.length>0&&n+c.length>1&&b.uniqueSort(i)}
// Override manipulation of globals by nested matchers
return j&&(P=u,C=s),p};return e?d(g):g}var u,v,w,x,y,z,A,B,C,D,E,
// Local document vars
F,G,H,I,J,K,L,M,
// Instance-specific data
N="sizzle"+1*new Date,O=a.document,P=0,Q=0,R=c(),S=c(),T=c(),U=function(a,b){return a===b&&(E=!0),0},
// General-purpose constants
V=1<<31,
// Instance methods
W={}.hasOwnProperty,X=[],Y=X.pop,Z=X.push,$=X.push,_=X.slice,
// Use a stripped-down indexOf as it's faster than native
// http://jsperf.com/thor-indexof-vs-for/5
aa=function(a,b){for(var c=0,d=a.length;d>c;c++)if(a[c]===b)return c;return-1},ba="checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",
// Regular expressions
// http://www.w3.org/TR/css3-selectors/#whitespace
ca="[\\x20\\t\\r\\n\\f]",
// http://www.w3.org/TR/CSS21/syndata.html#value-def-identifier
da="(?:\\\\.|[\\w-]|[^\\x00-\\xa0])+",
// Attribute selectors: http://www.w3.org/TR/selectors/#attribute-selectors
ea="\\["+ca+"*("+da+")(?:"+ca+
// Operator (capture 2)
"*([*^$|!~]?=)"+ca+
// "Attribute values must be CSS identifiers [capture 5] or strings [capture 3 or capture 4]"
"*(?:'((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\"|("+da+"))|)"+ca+"*\\]",fa=":("+da+")(?:\\((('((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\")|((?:\\\\.|[^\\\\()[\\]]|"+ea+")*)|.*)\\)|)",
// Leading and non-escaped trailing whitespace, capturing some non-whitespace characters preceding the latter
ga=new RegExp(ca+"+","g"),ha=new RegExp("^"+ca+"+|((?:^|[^\\\\])(?:\\\\.)*)"+ca+"+$","g"),ia=new RegExp("^"+ca+"*,"+ca+"*"),ja=new RegExp("^"+ca+"*([>+~]|"+ca+")"+ca+"*"),ka=new RegExp("="+ca+"*([^\\]'\"]*?)"+ca+"*\\]","g"),la=new RegExp(fa),ma=new RegExp("^"+da+"$"),na={ID:new RegExp("^#("+da+")"),CLASS:new RegExp("^\\.("+da+")"),TAG:new RegExp("^("+da+"|[*])"),ATTR:new RegExp("^"+ea),PSEUDO:new RegExp("^"+fa),CHILD:new RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\("+ca+"*(even|odd|(([+-]|)(\\d*)n|)"+ca+"*(?:([+-]|)"+ca+"*(\\d+)|))"+ca+"*\\)|)","i"),bool:new RegExp("^(?:"+ba+")$","i"),
// For use in libraries implementing .is()
// We use this for POS matching in `select`
needsContext:new RegExp("^"+ca+"*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\("+ca+"*((?:-\\d)?\\d*)"+ca+"*\\)|)(?=[^-]|$)","i")},oa=/^(?:input|select|textarea|button)$/i,pa=/^h\d$/i,qa=/^[^{]+\{\s*\[native \w/,
// Easily-parseable/retrievable ID or TAG or CLASS selectors
ra=/^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,sa=/[+~]/,ta=/'|\\/g,
// CSS escapes http://www.w3.org/TR/CSS21/syndata.html#escaped-characters
ua=new RegExp("\\\\([\\da-f]{1,6}"+ca+"?|("+ca+")|.)","ig"),va=function(a,b,c){var d="0x"+b-65536;
// NaN means non-codepoint
// Support: Firefox<24
// Workaround erroneous numeric interpretation of +"0x"
// BMP codepoint
// Supplemental Plane codepoint (surrogate pair)
return d!==d||c?b:0>d?String.fromCharCode(d+65536):String.fromCharCode(d>>10|55296,1023&d|56320)},
// Used for iframes
// See setDocument()
// Removing the function wrapper causes a "Permission Denied"
// error in IE
wa=function(){F()};
// Optimize for push.apply( _, NodeList )
try{$.apply(X=_.call(O.childNodes),O.childNodes),
// Support: Android<4.0
// Detect silently failing push.apply
X[O.childNodes.length].nodeType}catch(xa){$={apply:X.length?
// Leverage slice if possible
function(a,b){Z.apply(a,_.call(b))}:
// Support: IE<9
// Otherwise append directly
function(a,b){
// Can't trust NodeList.length
for(var c=a.length,d=0;a[c++]=b[d++];);a.length=c-1}}}v=b.support={},y=b.isXML=function(a){var b=a&&(a.ownerDocument||a).documentElement;return b?"HTML"!==b.nodeName:!1},F=b.setDocument=function(a){var b,c,d=a?a.ownerDocument||a:O;return d!==G&&9===d.nodeType&&d.documentElement?(G=d,H=G.documentElement,I=!y(G),(c=G.defaultView)&&c.top!==c&&(c.addEventListener?c.addEventListener("unload",wa,!1):c.attachEvent&&c.attachEvent("onunload",wa)),v.attributes=e(function(a){return a.className="i",!a.getAttribute("className")}),v.getElementsByTagName=e(function(a){return a.appendChild(G.createComment("")),!a.getElementsByTagName("*").length}),v.getElementsByClassName=qa.test(G.getElementsByClassName),v.getById=e(function(a){return H.appendChild(a).id=N,!G.getElementsByName||!G.getElementsByName(N).length}),v.getById?(w.find.ID=function(a,b){if("undefined"!=typeof b.getElementById&&I){var c=b.getElementById(a);return c?[c]:[]}},w.filter.ID=function(a){var b=a.replace(ua,va);return function(a){return a.getAttribute("id")===b}}):(delete w.find.ID,w.filter.ID=function(a){var b=a.replace(ua,va);return function(a){var c="undefined"!=typeof a.getAttributeNode&&a.getAttributeNode("id");return c&&c.value===b}}),w.find.TAG=v.getElementsByTagName?function(a,b){return"undefined"!=typeof b.getElementsByTagName?b.getElementsByTagName(a):v.qsa?b.querySelectorAll(a):void 0}:function(a,b){var c,d=[],e=0,f=b.getElementsByTagName(a);if("*"===a){for(;c=f[e++];)1===c.nodeType&&d.push(c);return d}return f},w.find.CLASS=v.getElementsByClassName&&function(a,b){return"undefined"!=typeof b.getElementsByClassName&&I?b.getElementsByClassName(a):void 0},K=[],J=[],(v.qsa=qa.test(G.querySelectorAll))&&(e(function(a){H.appendChild(a).innerHTML="<a id='"+N+"'></a><select id='"+N+"-\r\\' msallowcapture=''><option selected=''></option></select>",a.querySelectorAll("[msallowcapture^='']").length&&J.push("[*^$]="+ca+"*(?:''|\"\")"),a.querySelectorAll("[selected]").length||J.push("\\["+ca+"*(?:value|"+ba+")"),a.querySelectorAll("[id~="+N+"-]").length||J.push("~="),a.querySelectorAll(":checked").length||J.push(":checked"),a.querySelectorAll("a#"+N+"+*").length||J.push(".#.+[+~]")}),e(function(a){var b=G.createElement("input");b.setAttribute("type","hidden"),a.appendChild(b).setAttribute("name","D"),a.querySelectorAll("[name=d]").length&&J.push("name"+ca+"*[*^$|!~]?="),a.querySelectorAll(":enabled").length||J.push(":enabled",":disabled"),a.querySelectorAll("*,:x"),J.push(",.*:")})),(v.matchesSelector=qa.test(L=H.matches||H.webkitMatchesSelector||H.mozMatchesSelector||H.oMatchesSelector||H.msMatchesSelector))&&e(function(a){v.disconnectedMatch=L.call(a,"div"),L.call(a,"[s!='']:x"),K.push("!=",fa)}),J=J.length&&new RegExp(J.join("|")),K=K.length&&new RegExp(K.join("|")),b=qa.test(H.compareDocumentPosition),M=b||qa.test(H.contains)?function(a,b){var c=9===a.nodeType?a.documentElement:a,d=b&&b.parentNode;return a===d||!(!d||1!==d.nodeType||!(c.contains?c.contains(d):a.compareDocumentPosition&&16&a.compareDocumentPosition(d)))}:function(a,b){if(b)for(;b=b.parentNode;)if(b===a)return!0;return!1},U=b?function(a,b){if(a===b)return E=!0,0;var c=!a.compareDocumentPosition-!b.compareDocumentPosition;return c?c:(c=(a.ownerDocument||a)===(b.ownerDocument||b)?a.compareDocumentPosition(b):1,1&c||!v.sortDetached&&b.compareDocumentPosition(a)===c?a===G||a.ownerDocument===O&&M(O,a)?-1:b===G||b.ownerDocument===O&&M(O,b)?1:D?aa(D,a)-aa(D,b):0:4&c?-1:1)}:function(a,b){if(a===b)return E=!0,0;var c,d=0,e=a.parentNode,f=b.parentNode,h=[a],i=[b];if(!e||!f)return a===G?-1:b===G?1:e?-1:f?1:D?aa(D,a)-aa(D,b):0;if(e===f)return g(a,b);for(c=a;c=c.parentNode;)h.unshift(c);for(c=b;c=c.parentNode;)i.unshift(c);for(;h[d]===i[d];)d++;return d?g(h[d],i[d]):h[d]===O?-1:i[d]===O?1:0},G):G},b.matches=function(a,c){return b(a,null,null,c)},b.matchesSelector=function(a,c){if((a.ownerDocument||a)!==G&&F(a),c=c.replace(ka,"='$1']"),v.matchesSelector&&I&&!T[c+" "]&&(!K||!K.test(c))&&(!J||!J.test(c)))try{var d=L.call(a,c);if(d||v.disconnectedMatch||a.document&&11!==a.document.nodeType)return d}catch(e){}return b(c,G,null,[a]).length>0},b.contains=function(a,b){return(a.ownerDocument||a)!==G&&F(a),M(a,b)},b.attr=function(a,b){(a.ownerDocument||a)!==G&&F(a);var c=w.attrHandle[b.toLowerCase()],d=c&&W.call(w.attrHandle,b.toLowerCase())?c(a,b,!I):void 0;return void 0!==d?d:v.attributes||!I?a.getAttribute(b):(d=a.getAttributeNode(b))&&d.specified?d.value:null},b.error=function(a){throw new Error("Syntax error, unrecognized expression: "+a)},b.uniqueSort=function(a){var b,c=[],d=0,e=0;if(E=!v.detectDuplicates,D=!v.sortStable&&a.slice(0),a.sort(U),E){for(;b=a[e++];)b===a[e]&&(d=c.push(e));for(;d--;)a.splice(c[d],1)}return D=null,a},x=b.getText=function(a){var b,c="",d=0,e=a.nodeType;if(e){if(1===e||9===e||11===e){if("string"==typeof a.textContent)return a.textContent;for(a=a.firstChild;a;a=a.nextSibling)c+=x(a)}else if(3===e||4===e)return a.nodeValue}else for(;b=a[d++];)c+=x(b);return c},w=b.selectors={cacheLength:50,createPseudo:d,match:na,attrHandle:{},find:{},relative:{">":{dir:"parentNode",first:!0}," ":{dir:"parentNode"},"+":{dir:"previousSibling",first:!0},"~":{dir:"previousSibling"}},preFilter:{ATTR:function(a){return a[1]=a[1].replace(ua,va),a[3]=(a[3]||a[4]||a[5]||"").replace(ua,va),"~="===a[2]&&(a[3]=" "+a[3]+" "),a.slice(0,4)},CHILD:function(a){return a[1]=a[1].toLowerCase(),"nth"===a[1].slice(0,3)?(a[3]||b.error(a[0]),a[4]=+(a[4]?a[5]+(a[6]||1):2*("even"===a[3]||"odd"===a[3])),a[5]=+(a[7]+a[8]||"odd"===a[3])):a[3]&&b.error(a[0]),a},PSEUDO:function(a){var b,c=!a[6]&&a[2];return na.CHILD.test(a[0])?null:(a[3]?a[2]=a[4]||a[5]||"":c&&la.test(c)&&(b=z(c,!0))&&(b=c.indexOf(")",c.length-b)-c.length)&&(a[0]=a[0].slice(0,b),a[2]=c.slice(0,b)),a.slice(0,3))}},filter:{TAG:function(a){var b=a.replace(ua,va).toLowerCase();return"*"===a?function(){return!0}:function(a){return a.nodeName&&a.nodeName.toLowerCase()===b}},CLASS:function(a){var b=R[a+" "];return b||(b=new RegExp("(^|"+ca+")"+a+"("+ca+"|$)"))&&R(a,function(a){return b.test("string"==typeof a.className&&a.className||"undefined"!=typeof a.getAttribute&&a.getAttribute("class")||"")})},ATTR:function(a,c,d){return function(e){var f=b.attr(e,a);return null==f?"!="===c:c?(f+="","="===c?f===d:"!="===c?f!==d:"^="===c?d&&0===f.indexOf(d):"*="===c?d&&f.indexOf(d)>-1:"$="===c?d&&f.slice(-d.length)===d:"~="===c?(" "+f.replace(ga," ")+" ").indexOf(d)>-1:"|="===c?f===d||f.slice(0,d.length+1)===d+"-":!1):!0}},CHILD:function(a,b,c,d,e){var f="nth"!==a.slice(0,3),g="last"!==a.slice(-4),h="of-type"===b;return 1===d&&0===e?function(a){return!!a.parentNode}:function(b,c,i){var j,k,l,m,n,o,p=f!==g?"nextSibling":"previousSibling",q=b.parentNode,r=h&&b.nodeName.toLowerCase(),s=!i&&!h,t=!1;if(q){if(f){for(;p;){for(m=b;m=m[p];)if(h?m.nodeName.toLowerCase()===r:1===m.nodeType)return!1;o=p="only"===a&&!o&&"nextSibling"}return!0}if(o=[g?q.firstChild:q.lastChild],g&&s){for(m=q,l=m[N]||(m[N]={}),k=l[m.uniqueID]||(l[m.uniqueID]={}),j=k[a]||[],n=j[0]===P&&j[1],t=n&&j[2],m=n&&q.childNodes[n];m=++n&&m&&m[p]||(t=n=0)||o.pop();)if(1===m.nodeType&&++t&&m===b){k[a]=[P,n,t];break}}else if(s&&(m=b,l=m[N]||(m[N]={}),k=l[m.uniqueID]||(l[m.uniqueID]={}),j=k[a]||[],n=j[0]===P&&j[1],t=n),t===!1)for(;(m=++n&&m&&m[p]||(t=n=0)||o.pop())&&((h?m.nodeName.toLowerCase()!==r:1!==m.nodeType)||!++t||(s&&(l=m[N]||(m[N]={}),k=l[m.uniqueID]||(l[m.uniqueID]={}),k[a]=[P,t]),m!==b)););return t-=e,t===d||t%d===0&&t/d>=0}}},PSEUDO:function(a,c){var e,f=w.pseudos[a]||w.setFilters[a.toLowerCase()]||b.error("unsupported pseudo: "+a);return f[N]?f(c):f.length>1?(e=[a,a,"",c],w.setFilters.hasOwnProperty(a.toLowerCase())?d(function(a,b){for(var d,e=f(a,c),g=e.length;g--;)d=aa(a,e[g]),a[d]=!(b[d]=e[g])}):function(a){return f(a,0,e)}):f}},pseudos:{not:d(function(a){var b=[],c=[],e=A(a.replace(ha,"$1"));return e[N]?d(function(a,b,c,d){for(var f,g=e(a,null,d,[]),h=a.length;h--;)(f=g[h])&&(a[h]=!(b[h]=f))}):function(a,d,f){return b[0]=a,e(b,null,f,c),b[0]=null,!c.pop()}}),has:d(function(a){return function(c){return b(a,c).length>0}}),contains:d(function(a){return a=a.replace(ua,va),function(b){return(b.textContent||b.innerText||x(b)).indexOf(a)>-1}}),lang:d(function(a){return ma.test(a||"")||b.error("unsupported lang: "+a),a=a.replace(ua,va).toLowerCase(),function(b){var c;do if(c=I?b.lang:b.getAttribute("xml:lang")||b.getAttribute("lang"))return c=c.toLowerCase(),c===a||0===c.indexOf(a+"-");while((b=b.parentNode)&&1===b.nodeType);return!1}}),target:function(b){var c=a.location&&a.location.hash;return c&&c.slice(1)===b.id},root:function(a){return a===H},focus:function(a){return a===G.activeElement&&(!G.hasFocus||G.hasFocus())&&!!(a.type||a.href||~a.tabIndex)},enabled:function(a){return a.disabled===!1},disabled:function(a){return a.disabled===!0},checked:function(a){var b=a.nodeName.toLowerCase();return"input"===b&&!!a.checked||"option"===b&&!!a.selected},selected:function(a){return a.parentNode&&a.parentNode.selectedIndex,a.selected===!0},empty:function(a){for(a=a.firstChild;a;a=a.nextSibling)if(a.nodeType<6)return!1;return!0},parent:function(a){return!w.pseudos.empty(a)},header:function(a){return pa.test(a.nodeName)},input:function(a){return oa.test(a.nodeName)},button:function(a){var b=a.nodeName.toLowerCase();return"input"===b&&"button"===a.type||"button"===b},text:function(a){var b;return"input"===a.nodeName.toLowerCase()&&"text"===a.type&&(null==(b=a.getAttribute("type"))||"text"===b.toLowerCase())},first:j(function(){return[0]}),last:j(function(a,b){return[b-1]}),eq:j(function(a,b,c){return[0>c?c+b:c]}),even:j(function(a,b){for(var c=0;b>c;c+=2)a.push(c);return a}),odd:j(function(a,b){for(var c=1;b>c;c+=2)a.push(c);return a}),lt:j(function(a,b,c){for(var d=0>c?c+b:c;--d>=0;)a.push(d);return a}),gt:j(function(a,b,c){for(var d=0>c?c+b:c;++d<b;)a.push(d);return a})}},w.pseudos.nth=w.pseudos.eq;
// Add button/input type pseudos
for(u in{radio:!0,checkbox:!0,file:!0,password:!0,image:!0})w.pseudos[u]=h(u);for(u in{submit:!0,reset:!0})w.pseudos[u]=i(u);/**
 * A low-level selection function that works with Sizzle's compiled
 *  selector functions
 * @param {String|Function} selector A selector or a pre-compiled
 *  selector function built with Sizzle.compile
 * @param {Element} context
 * @param {Array} [results]
 * @param {Array} [seed] A set of elements to match against
 */
// One-time assignments
// Sort stability
// Support: Chrome 14-35+
// Always assume duplicates if they aren't passed to the comparison function
// Initialize against the default document
// Support: Webkit<537.32 - Safari 6.0.3/Chrome 25 (fixed in Chrome 27)
// Detached nodes confoundingly follow *each other*
// Support: IE<8
// Prevent attribute/property "interpolation"
// http://msdn.microsoft.com/en-us/library/ms536429%28VS.85%29.aspx
// Support: IE<9
// Use defaultValue in place of getAttribute("value")
// Support: IE<9
// Use getAttributeNode to fetch booleans when getAttribute lies
return l.prototype=w.filters=w.pseudos,w.setFilters=new l,z=b.tokenize=function(a,c){var d,e,f,g,h,i,j,k=S[a+" "];if(k)return c?0:k.slice(0);for(h=a,i=[],j=w.preFilter;h;){
// Comma and first run
d&&!(e=ia.exec(h))||(e&&(
// Don't consume trailing commas as valid
h=h.slice(e[0].length)||h),i.push(f=[])),d=!1,
// Combinators
(e=ja.exec(h))&&(d=e.shift(),f.push({value:d,type:e[0].replace(ha," ")}),h=h.slice(d.length));
// Filters
for(g in w.filter)!(e=na[g].exec(h))||j[g]&&!(e=j[g](e))||(d=e.shift(),f.push({value:d,type:g,matches:e}),h=h.slice(d.length));if(!d)break}
// Return the length of the invalid excess
// if we're just parsing
// Otherwise, throw an error or return tokens
// Cache the tokens
return c?h.length:h?b.error(a):S(a,i).slice(0)},A=b.compile=function(a,b){var c,d=[],e=[],f=T[a+" "];if(!f){for(b||(b=z(a)),c=b.length;c--;)f=s(b[c]),f[N]?d.push(f):e.push(f);f=T(a,t(e,d)),f.selector=a}return f},B=b.select=function(a,b,c,d){var e,f,g,h,i,j="function"==typeof a&&a,l=!d&&z(a=j.selector||a);if(c=c||[],1===l.length){if(f=l[0]=l[0].slice(0),f.length>2&&"ID"===(g=f[0]).type&&v.getById&&9===b.nodeType&&I&&w.relative[f[1].type]){if(b=(w.find.ID(g.matches[0].replace(ua,va),b)||[])[0],!b)return c;j&&(b=b.parentNode),a=a.slice(f.shift().value.length)}for(e=na.needsContext.test(a)?0:f.length;e--&&(g=f[e],!w.relative[h=g.type]);)if((i=w.find[h])&&(d=i(g.matches[0].replace(ua,va),sa.test(f[0].type)&&k(b.parentNode)||b))){if(f.splice(e,1),a=d.length&&m(f),!a)return $.apply(c,d),c;break}}return(j||A(a,l))(d,b,!I,c,!b||sa.test(a)&&k(b.parentNode)||b),c},v.sortStable=N.split("").sort(U).join("")===N,v.detectDuplicates=!!E,F(),v.sortDetached=e(function(a){return 1&a.compareDocumentPosition(G.createElement("div"))}),e(function(a){return a.innerHTML="<a href='#'></a>","#"===a.firstChild.getAttribute("href")})||f("type|href|height|width",function(a,b,c){return c?void 0:a.getAttribute(b,"type"===b.toLowerCase()?1:2)}),v.attributes&&e(function(a){return a.innerHTML="<input/>",a.firstChild.setAttribute("value",""),""===a.firstChild.getAttribute("value")})||f("value",function(a,b,c){return c||"input"!==a.nodeName.toLowerCase()?void 0:a.defaultValue}),e(function(a){return null==a.getAttribute("disabled")})||f(ba,function(a,b,c){var d;return c?void 0:a[b]===!0?b.toLowerCase():(d=a.getAttributeNode(b))&&d.specified?d.value:null}),b}(a);fa.find=ka,fa.expr=ka.selectors,fa.expr[":"]=fa.expr.pseudos,fa.uniqueSort=fa.unique=ka.uniqueSort,fa.text=ka.getText,fa.isXMLDoc=ka.isXML,fa.contains=ka.contains;var la=function(a,b,c){for(var d=[],e=void 0!==c;(a=a[b])&&9!==a.nodeType;)if(1===a.nodeType){if(e&&fa(a).is(c))break;d.push(a)}return d},ma=function(a,b){for(var c=[];a;a=a.nextSibling)1===a.nodeType&&a!==b&&c.push(a);return c},na=fa.expr.match.needsContext,oa=/^<([\w-]+)\s*\/?>(?:<\/\1>|)$/,pa=/^.[^:#\[\.,]*$/;fa.filter=function(a,b,c){var d=b[0];return c&&(a=":not("+a+")"),1===b.length&&1===d.nodeType?fa.find.matchesSelector(d,a)?[d]:[]:fa.find.matches(a,fa.grep(b,function(a){return 1===a.nodeType}))},fa.fn.extend({find:function(a){var b,c=this.length,d=[],e=this;if("string"!=typeof a)return this.pushStack(fa(a).filter(function(){for(b=0;c>b;b++)if(fa.contains(e[b],this))return!0}));for(b=0;c>b;b++)fa.find(a,e[b],d);
// Needed because $( selector, context ) becomes $( context ).find( selector )
return d=this.pushStack(c>1?fa.unique(d):d),d.selector=this.selector?this.selector+" "+a:a,d},filter:function(a){return this.pushStack(d(this,a||[],!1))},not:function(a){return this.pushStack(d(this,a||[],!0))},is:function(a){
// If this is a positional/relative selector, check membership in the returned set
// so $("p:first").is("p:last") won't return true for a doc with two "p".
return!!d(this,"string"==typeof a&&na.test(a)?fa(a):a||[],!1).length}});
// Initialize a jQuery object
// A central reference to the root jQuery(document)
var qa,
// A simple way to check for HTML strings
// Prioritize #id over <tag> to avoid XSS via location.hash (#9521)
// Strict HTML recognition (#11290: must start with <)
ra=/^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]*))$/,sa=fa.fn.init=function(a,b,c){var d,e;
// HANDLE: $(""), $(null), $(undefined), $(false)
if(!a)return this;
// Handle HTML strings
if(c=c||qa,"string"==typeof a){
// Match html or make sure no context is specified for #id
if(d="<"===a[0]&&">"===a[a.length-1]&&a.length>=3?[null,a,null]:ra.exec(a),!d||!d[1]&&b)return!b||b.jquery?(b||c).find(a):this.constructor(b).find(a);
// HANDLE: $(html) -> $(array)
if(d[1]){
// HANDLE: $(html, props)
if(b=b instanceof fa?b[0]:b,fa.merge(this,fa.parseHTML(d[1],b&&b.nodeType?b.ownerDocument||b:X,!0)),oa.test(d[1])&&fa.isPlainObject(b))for(d in b)
// Properties of context are called as methods if possible
fa.isFunction(this[d])?this[d](b[d]):this.attr(d,b[d]);return this}
// Support: Blackberry 4.6
// gEBID returns nodes no longer in the document (#6963)
// Inject the element directly into the jQuery object
return e=X.getElementById(d[2]),e&&e.parentNode&&(this.length=1,this[0]=e),this.context=X,this.selector=a,this}
// Execute immediately if ready is not present
return a.nodeType?(this.context=this[0]=a,this.length=1,this):fa.isFunction(a)?void 0!==c.ready?c.ready(a):a(fa):(void 0!==a.selector&&(this.selector=a.selector,this.context=a.context),fa.makeArray(a,this))};
// Give the init function the jQuery prototype for later instantiation
sa.prototype=fa.fn,
// Initialize central reference
qa=fa(X);var ta=/^(?:parents|prev(?:Until|All))/,
// Methods guaranteed to produce a unique set when starting from a unique set
ua={children:!0,contents:!0,next:!0,prev:!0};fa.fn.extend({has:function(a){var b=fa(a,this),c=b.length;return this.filter(function(){for(var a=0;c>a;a++)if(fa.contains(this,b[a]))return!0})},closest:function(a,b){for(var c,d=0,e=this.length,f=[],g=na.test(a)||"string"!=typeof a?fa(a,b||this.context):0;e>d;d++)for(c=this[d];c&&c!==b;c=c.parentNode)
// Always skip document fragments
if(c.nodeType<11&&(g?g.index(c)>-1:1===c.nodeType&&fa.find.matchesSelector(c,a))){f.push(c);break}return this.pushStack(f.length>1?fa.uniqueSort(f):f)},
// Determine the position of an element within the set
index:function(a){
// No argument, return index in parent
// No argument, return index in parent
// Index in selector
// If it receives a jQuery object, the first element is used
return a?"string"==typeof a?_.call(fa(a),this[0]):_.call(this,a.jquery?a[0]:a):this[0]&&this[0].parentNode?this.first().prevAll().length:-1},add:function(a,b){return this.pushStack(fa.uniqueSort(fa.merge(this.get(),fa(a,b))))},addBack:function(a){return this.add(null==a?this.prevObject:this.prevObject.filter(a))}}),fa.each({parent:function(a){var b=a.parentNode;return b&&11!==b.nodeType?b:null},parents:function(a){return la(a,"parentNode")},parentsUntil:function(a,b,c){return la(a,"parentNode",c)},next:function(a){return e(a,"nextSibling")},prev:function(a){return e(a,"previousSibling")},nextAll:function(a){return la(a,"nextSibling")},prevAll:function(a){return la(a,"previousSibling")},nextUntil:function(a,b,c){return la(a,"nextSibling",c)},prevUntil:function(a,b,c){return la(a,"previousSibling",c)},siblings:function(a){return ma((a.parentNode||{}).firstChild,a)},children:function(a){return ma(a.firstChild)},contents:function(a){return a.contentDocument||fa.merge([],a.childNodes)}},function(a,b){fa.fn[a]=function(c,d){var e=fa.map(this,b,c);
// Remove duplicates
// Reverse order for parents* and prev-derivatives
return"Until"!==a.slice(-5)&&(d=c),d&&"string"==typeof d&&(e=fa.filter(d,e)),this.length>1&&(ua[a]||fa.uniqueSort(e),ta.test(a)&&e.reverse()),this.pushStack(e)}});var va=/\S+/g;/*
 * Create a callback list using the following parameters:
 *
 *	options: an optional list of space-separated options that will change how
 *			the callback list behaves or a more traditional option object
 *
 * By default a callback list will act like an event callback list and can be
 * "fired" multiple times.
 *
 * Possible options:
 *
 *	once:			will ensure the callback list can only be fired once (like a Deferred)
 *
 *	memory:			will keep track of previous values and will call any callback added
 *					after the list has been fired right away with the latest "memorized"
 *					values (like a Deferred)
 *
 *	unique:			will ensure a callback can only be added once (no duplicate in the list)
 *
 *	stopOnFalse:	interrupt callings when a callback returns false
 *
 */
fa.Callbacks=function(a){
// Convert options from String-formatted to Object-formatted if needed
// (we check in cache first)
a="string"==typeof a?f(a):fa.extend({},a);var// Flag to know if list is currently firing
b,
// Last fire value for non-forgettable lists
c,
// Flag to know if list was already fired
d,
// Flag to prevent firing
e,
// Actual callback list
g=[],
// Queue of execution data for repeatable lists
h=[],
// Index of currently firing callback (modified by add/remove as needed)
i=-1,
// Fire callbacks
j=function(){for(
// Enforce single-firing
e=a.once,
// Execute callbacks for all pending executions,
// respecting firingIndex overrides and runtime changes
d=b=!0;h.length;i=-1)for(c=h.shift();++i<g.length;)
// Run callback and check for early termination
g[i].apply(c[0],c[1])===!1&&a.stopOnFalse&&(i=g.length,c=!1);
// Forget the data if we're done with it
a.memory||(c=!1),b=!1,e&&(g=c?[]:"")},
// Actual Callbacks object
k={
// Add a callback or a collection of callbacks to the list
add:function(){
// If we have memory from a past run, we should fire after adding
return g&&(c&&!b&&(i=g.length-1,h.push(c)),function d(b){fa.each(b,function(b,c){fa.isFunction(c)?a.unique&&k.has(c)||g.push(c):c&&c.length&&"string"!==fa.type(c)&&
// Inspect recursively
d(c)})}(arguments),c&&!b&&j()),this},
// Remove a callback from the list
remove:function(){return fa.each(arguments,function(a,b){for(var c;(c=fa.inArray(b,g,c))>-1;)g.splice(c,1),
// Handle firing indexes
i>=c&&i--}),this},
// Check if a given callback is in the list.
// If no argument is given, return whether or not list has callbacks attached.
has:function(a){return a?fa.inArray(a,g)>-1:g.length>0},
// Remove all callbacks from the list
empty:function(){return g&&(g=[]),this},
// Disable .fire and .add
// Abort any current/pending executions
// Clear all callbacks and values
disable:function(){return e=h=[],g=c="",this},disabled:function(){return!g},
// Disable .fire
// Also disable .add unless we have memory (since it would have no effect)
// Abort any pending executions
lock:function(){return e=h=[],c||(g=c=""),this},locked:function(){return!!e},
// Call all callbacks with the given context and arguments
fireWith:function(a,c){return e||(c=c||[],c=[a,c.slice?c.slice():c],h.push(c),b||j()),this},
// Call all the callbacks with the given arguments
fire:function(){return k.fireWith(this,arguments),this},
// To know if the callbacks have already been called at least once
fired:function(){return!!d}};return k},fa.extend({Deferred:function(a){var b=[
// action, add listener, listener list, final state
["resolve","done",fa.Callbacks("once memory"),"resolved"],["reject","fail",fa.Callbacks("once memory"),"rejected"],["notify","progress",fa.Callbacks("memory")]],c="pending",d={state:function(){return c},always:function(){return e.done(arguments).fail(arguments),this},then:function(){var a=arguments;return fa.Deferred(function(c){fa.each(b,function(b,f){var g=fa.isFunction(a[b])&&a[b];
// deferred[ done | fail | progress ] for forwarding actions to newDefer
e[f[1]](function(){var a=g&&g.apply(this,arguments);a&&fa.isFunction(a.promise)?a.promise().progress(c.notify).done(c.resolve).fail(c.reject):c[f[0]+"With"](this===d?c.promise():this,g?[a]:arguments)})}),a=null}).promise()},
// Get a promise for this deferred
// If obj is provided, the promise aspect is added to the object
promise:function(a){return null!=a?fa.extend(a,d):d}},e={};
// All done!
// Keep pipe for back-compat
// Add list-specific methods
// Make the deferred a promise
// Call given func if any
return d.pipe=d.then,fa.each(b,function(a,f){var g=f[2],h=f[3];
// promise[ done | fail | progress ] = list.add
d[f[1]]=g.add,
// Handle state
h&&g.add(function(){
// state = [ resolved | rejected ]
c=h},b[1^a][2].disable,b[2][2].lock),
// deferred[ resolve | reject | notify ]
e[f[0]]=function(){return e[f[0]+"With"](this===e?d:this,arguments),this},e[f[0]+"With"]=g.fireWith}),d.promise(e),a&&a.call(e,e),e},
// Deferred helper
when:function(a){var b,c,d,e=0,f=Y.call(arguments),g=f.length,
// the count of uncompleted subordinates
h=1!==g||a&&fa.isFunction(a.promise)?g:0,
// the master Deferred.
// If resolveValues consist of only a single Deferred, just use that.
i=1===h?a:fa.Deferred(),
// Update function for both resolve and progress values
j=function(a,c,d){return function(e){c[a]=this,d[a]=arguments.length>1?Y.call(arguments):e,d===b?i.notifyWith(c,d):--h||i.resolveWith(c,d)}};
// Add listeners to Deferred subordinates; treat others as resolved
if(g>1)for(b=new Array(g),c=new Array(g),d=new Array(g);g>e;e++)f[e]&&fa.isFunction(f[e].promise)?f[e].promise().progress(j(e,c,b)).done(j(e,d,f)).fail(i.reject):--h;
// If we're not waiting on anything, resolve the master
return h||i.resolveWith(d,f),i.promise()}});
// The deferred used on DOM ready
var wa;fa.fn.ready=function(a){
// Add the callback
return fa.ready.promise().done(a),this},fa.extend({
// Is the DOM ready to be used? Set to true once it occurs.
isReady:!1,
// A counter to track how many items to wait for before
// the ready event fires. See #6781
readyWait:1,
// Hold (or release) the ready event
holdReady:function(a){a?fa.readyWait++:fa.ready(!0)},
// Handle when the DOM is ready
ready:function(a){
// Abort if there are pending holds or we're already ready
(a===!0?--fa.readyWait:fa.isReady)||(
// Remember that the DOM is ready
fa.isReady=!0,
// If a normal DOM Ready event fired, decrement, and wait if need be
a!==!0&&--fa.readyWait>0||(
// If there are functions bound, to execute
wa.resolveWith(X,[fa]),
// Trigger any bound ready events
fa.fn.triggerHandler&&(fa(X).triggerHandler("ready"),fa(X).off("ready"))))}}),fa.ready.promise=function(b){
// Catch cases where $(document).ready() is called
// after the browser event has already occurred.
// Support: IE9-10 only
// Older IE sometimes signals "interactive" too soon
// Handle it asynchronously to allow scripts the opportunity to delay ready
// Use the handy event callback
// A fallback to window.onload, that will always work
return wa||(wa=fa.Deferred(),"complete"===X.readyState||"loading"!==X.readyState&&!X.documentElement.doScroll?a.setTimeout(fa.ready):(X.addEventListener("DOMContentLoaded",g),a.addEventListener("load",g))),wa.promise(b)},
// Kick off the DOM ready check even if the user does not
fa.ready.promise();
// Multifunctional method to get and set values of a collection
// The value/s can optionally be executed if it's a function
var xa=function(a,b,c,d,e,f,g){var h=0,i=a.length,j=null==c;
// Sets many values
if("object"===fa.type(c)){e=!0;for(h in c)xa(a,b,h,c[h],!0,f,g)}else if(void 0!==d&&(e=!0,fa.isFunction(d)||(g=!0),j&&(g?(b.call(a,d),b=null):(j=b,b=function(a,b,c){return j.call(fa(a),c)})),b))for(;i>h;h++)b(a[h],c,g?d:d.call(a[h],h,b(a[h],c)));
// Gets
return e?a:j?b.call(a):i?b(a[0],c):f},ya=function(a){
// Accepts only:
//  - Node
//    - Node.ELEMENT_NODE
//    - Node.DOCUMENT_NODE
//  - Object
//    - Any
/* jshint -W018 */
return 1===a.nodeType||9===a.nodeType||!+a.nodeType};h.uid=1,h.prototype={register:function(a,b){var c=b||{};
// If it is a node unlikely to be stringify-ed or looped over
// use plain assignment
return a.nodeType?a[this.expando]=c:Object.defineProperty(a,this.expando,{value:c,writable:!0,configurable:!0}),a[this.expando]},cache:function(a){
// We can accept data for non-element nodes in modern browsers,
// but we should not, see #8335.
// Always return an empty object.
if(!ya(a))return{};
// Check if the owner object already has a cache
var b=a[this.expando];
// If not, create one
// We can accept data for non-element nodes in modern browsers,
// but we should not, see #8335.
// Always return an empty object.
// If it is a node unlikely to be stringify-ed or looped over
// use plain assignment
return b||(b={},ya(a)&&(a.nodeType?a[this.expando]=b:Object.defineProperty(a,this.expando,{value:b,configurable:!0}))),b},set:function(a,b,c){var d,e=this.cache(a);
// Handle: [ owner, key, value ] args
if("string"==typeof b)e[b]=c;else
// Copy the properties one-by-one to the cache object
for(d in b)e[d]=b[d];return e},get:function(a,b){return void 0===b?this.cache(a):a[this.expando]&&a[this.expando][b]},access:function(a,b,c){var d;
// In cases where either:
//
//   1. No key was specified
//   2. A string key was specified, but no value provided
//
// Take the "read" path and allow the get method to determine
// which value to return, respectively either:
//
//   1. The entire cache object
//   2. The data stored at the key
//
// In cases where either:
//
//   1. No key was specified
//   2. A string key was specified, but no value provided
//
// Take the "read" path and allow the get method to determine
// which value to return, respectively either:
//
//   1. The entire cache object
//   2. The data stored at the key
//
// When the key is not a string, or both a key and value
// are specified, set or extend (existing objects) with either:
//
//   1. An object of properties
//   2. A key and value
//
return void 0===b||b&&"string"==typeof b&&void 0===c?(d=this.get(a,b),void 0!==d?d:this.get(a,fa.camelCase(b))):(this.set(a,b,c),void 0!==c?c:b)},remove:function(a,b){var c,d,e,f=a[this.expando];if(void 0!==f){if(void 0===b)this.register(a);else{
// Support array or space separated string of keys
fa.isArray(b)?
// If "name" is an array of keys...
// When data is initially created, via ("key", "val") signature,
// keys will be converted to camelCase.
// Since there is no way to tell _how_ a key was added, remove
// both plain key and camelCase key. #12786
// This will only penalize the array argument path.
d=b.concat(b.map(fa.camelCase)):(e=fa.camelCase(b),b in f?d=[b,e]:(d=e,d=d in f?[d]:d.match(va)||[])),c=d.length;for(;c--;)delete f[d[c]]}
// Remove the expando if there's no more data
(void 0===b||fa.isEmptyObject(f))&&(
// Support: Chrome <= 35-45+
// Webkit & Blink performance suffers when deleting properties
// from DOM nodes, so set to undefined instead
// https://code.google.com/p/chromium/issues/detail?id=378607
a.nodeType?a[this.expando]=void 0:delete a[this.expando])}},hasData:function(a){var b=a[this.expando];return void 0!==b&&!fa.isEmptyObject(b)}};var za=new h,Aa=new h,Ba=/^(?:\{[\w\W]*\}|\[[\w\W]*\])$/,Ca=/[A-Z]/g;fa.extend({hasData:function(a){return Aa.hasData(a)||za.hasData(a)},data:function(a,b,c){return Aa.access(a,b,c)},removeData:function(a,b){Aa.remove(a,b)},
// TODO: Now that all calls to _data and _removeData have been replaced
// with direct calls to dataPriv methods, these can be deprecated.
_data:function(a,b,c){return za.access(a,b,c)},_removeData:function(a,b){za.remove(a,b)}}),fa.fn.extend({data:function(a,b){var c,d,e,f=this[0],g=f&&f.attributes;
// Gets all values
if(void 0===a){if(this.length&&(e=Aa.get(f),1===f.nodeType&&!za.get(f,"hasDataAttrs"))){for(c=g.length;c--;)
// Support: IE11+
// The attrs elements can be null (#14894)
g[c]&&(d=g[c].name,0===d.indexOf("data-")&&(d=fa.camelCase(d.slice(5)),i(f,d,e[d])));za.set(f,"hasDataAttrs",!0)}return e}
// Sets multiple values
// Sets multiple values
return"object"==typeof a?this.each(function(){Aa.set(this,a)}):xa(this,function(b){var c,d;
// The calling jQuery object (element matches) is not empty
// (and therefore has an element appears at this[ 0 ]) and the
// `value` parameter was not undefined. An empty jQuery object
// will result in `undefined` for elem = this[ 0 ] which will
// throw an exception if an attempt to read a data cache is made.
if(f&&void 0===b){if(c=Aa.get(f,a)||
// Try to find dashed key if it exists (gh-2779)
// This is for 2.2.x only
Aa.get(f,a.replace(Ca,"-$&").toLowerCase()),void 0!==c)return c;if(d=fa.camelCase(a),c=Aa.get(f,d),void 0!==c)return c;if(c=i(f,d,void 0),void 0!==c)return c}else d=fa.camelCase(a),this.each(function(){var c=Aa.get(this,d);Aa.set(this,d,b),a.indexOf("-")>-1&&void 0!==c&&Aa.set(this,a,b)})},null,b,arguments.length>1,null,!0)},removeData:function(a){return this.each(function(){Aa.remove(this,a)})}}),fa.extend({queue:function(a,b,c){var d;
// Speed up dequeue by getting out quickly if this is just a lookup
return a?(b=(b||"fx")+"queue",d=za.get(a,b),c&&(!d||fa.isArray(c)?d=za.access(a,b,fa.makeArray(c)):d.push(c)),d||[]):void 0},dequeue:function(a,b){b=b||"fx";var c=fa.queue(a,b),d=c.length,e=c.shift(),f=fa._queueHooks(a,b),g=function(){fa.dequeue(a,b)};
// If the fx queue is dequeued, always remove the progress sentinel
"inprogress"===e&&(e=c.shift(),d--),e&&(
// Add a progress sentinel to prevent the fx queue from being
// automatically dequeued
"fx"===b&&c.unshift("inprogress"),
// Clear up the last queue stop function
delete f.stop,e.call(a,g,f)),!d&&f&&f.empty.fire()},
// Not public - generate a queueHooks object, or return the current one
_queueHooks:function(a,b){var c=b+"queueHooks";return za.get(a,c)||za.access(a,c,{empty:fa.Callbacks("once memory").add(function(){za.remove(a,[b+"queue",c])})})}}),fa.fn.extend({queue:function(a,b){var c=2;return"string"!=typeof a&&(b=a,a="fx",c--),arguments.length<c?fa.queue(this[0],a):void 0===b?this:this.each(function(){var c=fa.queue(this,a,b);
// Ensure a hooks for this queue
fa._queueHooks(this,a),"fx"===a&&"inprogress"!==c[0]&&fa.dequeue(this,a)})},dequeue:function(a){return this.each(function(){fa.dequeue(this,a)})},clearQueue:function(a){return this.queue(a||"fx",[])},
// Get a promise resolved when queues of a certain type
// are emptied (fx is the type by default)
promise:function(a,b){var c,d=1,e=fa.Deferred(),f=this,g=this.length,h=function(){--d||e.resolveWith(f,[f])};for("string"!=typeof a&&(b=a,a=void 0),a=a||"fx";g--;)c=za.get(f[g],a+"queueHooks"),c&&c.empty&&(d++,c.empty.add(h));return h(),e.promise(b)}});var Da=/[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,Ea=new RegExp("^(?:([+-])=|)("+Da+")([a-z%]*)$","i"),Fa=["Top","Right","Bottom","Left"],Ga=function(a,b){
// isHidden might be called from jQuery#filter function;
// in that case, element will be second argument
return a=b||a,"none"===fa.css(a,"display")||!fa.contains(a.ownerDocument,a)},Ha=/^(?:checkbox|radio)$/i,Ia=/<([\w:-]+)/,Ja=/^$|\/(?:java|ecma)script/i,Ka={
// Support: IE9
option:[1,"<select multiple='multiple'>","</select>"],
// XHTML parsers do not magically insert elements in the
// same way that tag soup parsers do. So we cannot shorten
// this by omitting <tbody> or other required elements.
thead:[1,"<table>","</table>"],col:[2,"<table><colgroup>","</colgroup></table>"],tr:[2,"<table><tbody>","</tbody></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],_default:[0,"",""]};
// Support: IE9
Ka.optgroup=Ka.option,Ka.tbody=Ka.tfoot=Ka.colgroup=Ka.caption=Ka.thead,Ka.th=Ka.td;var La=/<|&#?\w+;/;!function(){var a=X.createDocumentFragment(),b=a.appendChild(X.createElement("div")),c=X.createElement("input");
// Support: Android 4.0-4.3, Safari<=5.1
// Check state lost if the name is set (#11217)
// Support: Windows Web Apps (WWA)
// `name` and `type` must use .setAttribute for WWA (#14901)
c.setAttribute("type","radio"),c.setAttribute("checked","checked"),c.setAttribute("name","t"),b.appendChild(c),
// Support: Safari<=5.1, Android<4.2
// Older WebKit doesn't clone checked state correctly in fragments
da.checkClone=b.cloneNode(!0).cloneNode(!0).lastChild.checked,
// Support: IE<=11+
// Make sure textarea (and checkbox) defaultValue is properly cloned
b.innerHTML="<textarea>x</textarea>",da.noCloneChecked=!!b.cloneNode(!0).lastChild.defaultValue}();var Ma=/^key/,Na=/^(?:mouse|pointer|contextmenu|drag|drop)|click/,Oa=/^([^.]*)(?:\.(.+)|)/;/*
 * Helper functions for managing events -- not part of the public interface.
 * Props to Dean Edwards' addEvent library for many of the ideas.
 */
fa.event={global:{},add:function(a,b,c,d,e){var f,g,h,i,j,k,l,m,n,o,p,q=za.get(a);
// Don't attach events to noData or text/comment nodes (but allow plain objects)
if(q)for(
// Caller can pass in an object of custom data in lieu of the handler
c.handler&&(f=c,c=f.handler,e=f.selector),
// Make sure that the handler has a unique ID, used to find/remove it later
c.guid||(c.guid=fa.guid++),
// Init the element's event structure and main handler, if this is the first
(i=q.events)||(i=q.events={}),(g=q.handle)||(g=q.handle=function(b){
// Discard the second event of a jQuery.event.trigger() and
// when an event is called after a page has unloaded
return"undefined"!=typeof fa&&fa.event.triggered!==b.type?fa.event.dispatch.apply(a,arguments):void 0}),
// Handle multiple events separated by a space
b=(b||"").match(va)||[""],j=b.length;j--;)h=Oa.exec(b[j])||[],n=p=h[1],o=(h[2]||"").split(".").sort(),n&&(l=fa.event.special[n]||{},n=(e?l.delegateType:l.bindType)||n,l=fa.event.special[n]||{},k=fa.extend({type:n,origType:p,data:d,handler:c,guid:c.guid,selector:e,needsContext:e&&fa.expr.match.needsContext.test(e),namespace:o.join(".")},f),(m=i[n])||(m=i[n]=[],m.delegateCount=0,l.setup&&l.setup.call(a,d,o,g)!==!1||a.addEventListener&&a.addEventListener(n,g)),l.add&&(l.add.call(a,k),k.handler.guid||(k.handler.guid=c.guid)),e?m.splice(m.delegateCount++,0,k):m.push(k),fa.event.global[n]=!0)},
// Detach an event or set of events from an element
remove:function(a,b,c,d,e){var f,g,h,i,j,k,l,m,n,o,p,q=za.hasData(a)&&za.get(a);if(q&&(i=q.events)){for(
// Once for each type.namespace in types; type may be omitted
b=(b||"").match(va)||[""],j=b.length;j--;)
// Unbind all events (on this namespace, if provided) for the element
if(h=Oa.exec(b[j])||[],n=p=h[1],o=(h[2]||"").split(".").sort(),n){for(l=fa.event.special[n]||{},n=(d?l.delegateType:l.bindType)||n,m=i[n]||[],h=h[2]&&new RegExp("(^|\\.)"+o.join("\\.(?:.*\\.|)")+"(\\.|$)"),
// Remove matching events
g=f=m.length;f--;)k=m[f],!e&&p!==k.origType||c&&c.guid!==k.guid||h&&!h.test(k.namespace)||d&&d!==k.selector&&("**"!==d||!k.selector)||(m.splice(f,1),k.selector&&m.delegateCount--,l.remove&&l.remove.call(a,k));
// Remove generic event handler if we removed something and no more handlers exist
// (avoids potential for endless recursion during removal of special event handlers)
g&&!m.length&&(l.teardown&&l.teardown.call(a,o,q.handle)!==!1||fa.removeEvent(a,n,q.handle),delete i[n])}else for(n in i)fa.event.remove(a,n+b[j],c,d,!0);
// Remove data and the expando if it's no longer used
fa.isEmptyObject(i)&&za.remove(a,"handle events")}},dispatch:function(a){
// Make a writable jQuery.Event from the native event object
a=fa.event.fix(a);var b,c,d,e,f,g=[],h=Y.call(arguments),i=(za.get(this,"events")||{})[a.type]||[],j=fa.event.special[a.type]||{};
// Call the preDispatch hook for the mapped type, and let it bail if desired
if(
// Use the fix-ed jQuery.Event rather than the (read-only) native event
h[0]=a,a.delegateTarget=this,!j.preDispatch||j.preDispatch.call(this,a)!==!1){for(
// Determine handlers
g=fa.event.handlers.call(this,a,i),
// Run delegates first; they may want to stop propagation beneath us
b=0;(e=g[b++])&&!a.isPropagationStopped();)for(a.currentTarget=e.elem,c=0;(f=e.handlers[c++])&&!a.isImmediatePropagationStopped();)
// Triggered event must either 1) have no namespace, or 2) have namespace(s)
// a subset or equal to those in the bound event (both can have no namespace).
a.rnamespace&&!a.rnamespace.test(f.namespace)||(a.handleObj=f,a.data=f.data,d=((fa.event.special[f.origType]||{}).handle||f.handler).apply(e.elem,h),void 0!==d&&(a.result=d)===!1&&(a.preventDefault(),a.stopPropagation()));
// Call the postDispatch hook for the mapped type
return j.postDispatch&&j.postDispatch.call(this,a),a.result}},handlers:function(a,b){var c,d,e,f,g=[],h=b.delegateCount,i=a.target;
// Support (at least): Chrome, IE9
// Find delegate handlers
// Black-hole SVG <use> instance trees (#13180)
//
// Support: Firefox<=42+
// Avoid non-left-click in FF but don't block IE radio events (#3861, gh-2343)
if(h&&i.nodeType&&("click"!==a.type||isNaN(a.button)||a.button<1))for(;i!==this;i=i.parentNode||this)
// Don't check non-elements (#13208)
// Don't process clicks on disabled elements (#6911, #8165, #11382, #11764)
if(1===i.nodeType&&(i.disabled!==!0||"click"!==a.type)){for(d=[],c=0;h>c;c++)f=b[c],e=f.selector+" ",void 0===d[e]&&(d[e]=f.needsContext?fa(e,this).index(i)>-1:fa.find(e,this,null,[i]).length),d[e]&&d.push(f);d.length&&g.push({elem:i,handlers:d})}
// Add the remaining (directly-bound) handlers
return h<b.length&&g.push({elem:this,handlers:b.slice(h)}),g},
// Includes some event props shared by KeyEvent and MouseEvent
props:"altKey bubbles cancelable ctrlKey currentTarget detail eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),fixHooks:{},keyHooks:{props:"char charCode key keyCode".split(" "),filter:function(a,b){
// Add which for key events
return null==a.which&&(a.which=null!=b.charCode?b.charCode:b.keyCode),a}},mouseHooks:{props:"button buttons clientX clientY offsetX offsetY pageX pageY screenX screenY toElement".split(" "),filter:function(a,b){var c,d,e,f=b.button;
// Calculate pageX/Y if missing and clientX/Y available
// Add which for click: 1 === left; 2 === middle; 3 === right
// Note: button is not normalized, so don't use it
return null==a.pageX&&null!=b.clientX&&(c=a.target.ownerDocument||X,d=c.documentElement,e=c.body,a.pageX=b.clientX+(d&&d.scrollLeft||e&&e.scrollLeft||0)-(d&&d.clientLeft||e&&e.clientLeft||0),a.pageY=b.clientY+(d&&d.scrollTop||e&&e.scrollTop||0)-(d&&d.clientTop||e&&e.clientTop||0)),a.which||void 0===f||(a.which=1&f?1:2&f?3:4&f?2:0),a}},fix:function(a){if(a[fa.expando])return a;
// Create a writable copy of the event object and normalize some properties
var b,c,d,e=a.type,f=a,g=this.fixHooks[e];for(g||(this.fixHooks[e]=g=Na.test(e)?this.mouseHooks:Ma.test(e)?this.keyHooks:{}),d=g.props?this.props.concat(g.props):this.props,a=new fa.Event(f),b=d.length;b--;)c=d[b],a[c]=f[c];
// Support: Cordova 2.5 (WebKit) (#13255)
// All events should have a target; Cordova deviceready doesn't
// Support: Safari 6.0+, Chrome<28
// Target should not be a text node (#504, #13143)
return a.target||(a.target=X),3===a.target.nodeType&&(a.target=a.target.parentNode),g.filter?g.filter(a,f):a},special:{load:{
// Prevent triggered image.load events from bubbling to window.load
noBubble:!0},focus:{
// Fire native event if possible so blur/focus sequence is correct
trigger:function(){return this!==p()&&this.focus?(this.focus(),!1):void 0},delegateType:"focusin"},blur:{trigger:function(){return this===p()&&this.blur?(this.blur(),!1):void 0},delegateType:"focusout"},click:{
// For checkbox, fire native event so checked state will be right
trigger:function(){return"checkbox"===this.type&&this.click&&fa.nodeName(this,"input")?(this.click(),!1):void 0},
// For cross-browser consistency, don't fire native .click() on links
_default:function(a){return fa.nodeName(a.target,"a")}},beforeunload:{postDispatch:function(a){
// Support: Firefox 20+
// Firefox doesn't alert if the returnValue field is not set.
void 0!==a.result&&a.originalEvent&&(a.originalEvent.returnValue=a.result)}}}},fa.removeEvent=function(a,b,c){
// This "if" is needed for plain objects
a.removeEventListener&&a.removeEventListener(b,c)},fa.Event=function(a,b){
// Allow instantiation without the 'new' keyword
// Allow instantiation without the 'new' keyword
// Event object
// Events bubbling up the document may have been marked as prevented
// by a handler lower down the tree; reflect the correct value.
// Support: Android<4.0
// Put explicitly provided properties onto the event object
// Create a timestamp if incoming event doesn't have one
// Mark it as fixed
return this instanceof fa.Event?(a&&a.type?(this.originalEvent=a,this.type=a.type,this.isDefaultPrevented=a.defaultPrevented||void 0===a.defaultPrevented&&a.returnValue===!1?n:o):this.type=a,b&&fa.extend(this,b),this.timeStamp=a&&a.timeStamp||fa.now(),void(this[fa.expando]=!0)):new fa.Event(a,b)},
// jQuery.Event is based on DOM3 Events as specified by the ECMAScript Language Binding
// http://www.w3.org/TR/2003/WD-DOM-Level-3-Events-20030331/ecma-script-binding.html
fa.Event.prototype={constructor:fa.Event,isDefaultPrevented:o,isPropagationStopped:o,isImmediatePropagationStopped:o,preventDefault:function(){var a=this.originalEvent;this.isDefaultPrevented=n,a&&a.preventDefault()},stopPropagation:function(){var a=this.originalEvent;this.isPropagationStopped=n,a&&a.stopPropagation()},stopImmediatePropagation:function(){var a=this.originalEvent;this.isImmediatePropagationStopped=n,a&&a.stopImmediatePropagation(),this.stopPropagation()}},
// Create mouseenter/leave events using mouseover/out and event-time checks
// so that event delegation works in jQuery.
// Do the same for pointerenter/pointerleave and pointerover/pointerout
//
// Support: Safari 7 only
// Safari sends mouseenter too often; see:
// https://code.google.com/p/chromium/issues/detail?id=470258
// for the description of the bug (it existed in older Chrome versions as well).
fa.each({mouseenter:"mouseover",mouseleave:"mouseout",pointerenter:"pointerover",pointerleave:"pointerout"},function(a,b){fa.event.special[a]={delegateType:b,bindType:b,handle:function(a){var c,d=this,e=a.relatedTarget,f=a.handleObj;
// For mouseenter/leave call the handler if related is outside the target.
// NB: No relatedTarget if the mouse left/entered the browser window
return e&&(e===d||fa.contains(d,e))||(a.type=f.origType,c=f.handler.apply(this,arguments),a.type=b),c}}}),fa.fn.extend({on:function(a,b,c,d){return q(this,a,b,c,d)},one:function(a,b,c,d){return q(this,a,b,c,d,1)},off:function(a,b,c){var d,e;if(a&&a.preventDefault&&a.handleObj)
// ( event )  dispatched jQuery.Event
return d=a.handleObj,fa(a.delegateTarget).off(d.namespace?d.origType+"."+d.namespace:d.origType,d.selector,d.handler),this;if("object"==typeof a){
// ( types-object [, selector] )
for(e in a)this.off(e,b,a[e]);return this}
// ( types [, fn] )
return b!==!1&&"function"!=typeof b||(c=b,b=void 0),c===!1&&(c=o),this.each(function(){fa.event.remove(this,a,c,b)})}});var Pa=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:-]+)[^>]*)\/>/gi,
// Support: IE 10-11, Edge 10240+
// In IE/Edge using regex groups here causes severe slowdowns.
// See https://connect.microsoft.com/IE/feedback/details/1736512/
Qa=/<script|<style|<link/i,
// checked="checked" or checked
Ra=/checked\s*(?:[^=]|=\s*.checked.)/i,Sa=/^true\/(.*)/,Ta=/^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g;fa.extend({htmlPrefilter:function(a){return a.replace(Pa,"<$1></$2>")},clone:function(a,b,c){var d,e,f,g,h=a.cloneNode(!0),i=fa.contains(a.ownerDocument,a);
// Fix IE cloning issues
if(!(da.noCloneChecked||1!==a.nodeType&&11!==a.nodeType||fa.isXMLDoc(a)))for(g=k(h),f=k(a),d=0,e=f.length;e>d;d++)v(f[d],g[d]);
// Copy the events from the original to the clone
if(b)if(c)for(f=f||k(a),g=g||k(h),d=0,e=f.length;e>d;d++)u(f[d],g[d]);else u(a,h);
// Return the cloned set
// Preserve script evaluation history
return g=k(h,"script"),g.length>0&&l(g,!i&&k(a,"script")),h},cleanData:function(a){for(var b,c,d,e=fa.event.special,f=0;void 0!==(c=a[f]);f++)if(ya(c)){if(b=c[za.expando]){if(b.events)for(d in b.events)e[d]?fa.event.remove(c,d):fa.removeEvent(c,d,b.handle);
// Support: Chrome <= 35-45+
// Assign undefined instead of using delete, see Data#remove
c[za.expando]=void 0}c[Aa.expando]&&(
// Support: Chrome <= 35-45+
// Assign undefined instead of using delete, see Data#remove
c[Aa.expando]=void 0)}}}),fa.fn.extend({
// Keep domManip exposed until 3.0 (gh-2225)
domManip:w,detach:function(a){return x(this,a,!0)},remove:function(a){return x(this,a)},text:function(a){return xa(this,function(a){return void 0===a?fa.text(this):this.empty().each(function(){1!==this.nodeType&&11!==this.nodeType&&9!==this.nodeType||(this.textContent=a)})},null,a,arguments.length)},append:function(){return w(this,arguments,function(a){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var b=r(this,a);b.appendChild(a)}})},prepend:function(){return w(this,arguments,function(a){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var b=r(this,a);b.insertBefore(a,b.firstChild)}})},before:function(){return w(this,arguments,function(a){this.parentNode&&this.parentNode.insertBefore(a,this)})},after:function(){return w(this,arguments,function(a){this.parentNode&&this.parentNode.insertBefore(a,this.nextSibling)})},empty:function(){for(var a,b=0;null!=(a=this[b]);b++)1===a.nodeType&&(
// Prevent memory leaks
fa.cleanData(k(a,!1)),
// Remove any remaining nodes
a.textContent="");return this},clone:function(a,b){return a=null==a?!1:a,b=null==b?a:b,this.map(function(){return fa.clone(this,a,b)})},html:function(a){return xa(this,function(a){var b=this[0]||{},c=0,d=this.length;if(void 0===a&&1===b.nodeType)return b.innerHTML;
// See if we can take a shortcut and just use innerHTML
if("string"==typeof a&&!Qa.test(a)&&!Ka[(Ia.exec(a)||["",""])[1].toLowerCase()]){a=fa.htmlPrefilter(a);try{for(;d>c;c++)b=this[c]||{},1===b.nodeType&&(fa.cleanData(k(b,!1)),b.innerHTML=a);b=0}catch(e){}}b&&this.empty().append(a)},null,a,arguments.length)},replaceWith:function(){var a=[];
// Make the changes, replacing each non-ignored context element with the new content
return w(this,arguments,function(b){var c=this.parentNode;fa.inArray(this,a)<0&&(fa.cleanData(k(this)),c&&c.replaceChild(b,this))},a)}}),fa.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(a,b){fa.fn[a]=function(a){for(var c,d=[],e=fa(a),f=e.length-1,g=0;f>=g;g++)c=g===f?this:this.clone(!0),fa(e[g])[b](c),$.apply(d,c.get());return this.pushStack(d)}});var Ua,Va={
// Support: Firefox
// We have to pre-define these values for FF (#10227)
HTML:"block",BODY:"block"},Wa=/^margin/,Xa=new RegExp("^("+Da+")(?!px)[a-z%]+$","i"),Ya=function(b){
// Support: IE<=11+, Firefox<=30+ (#15098, #14150)
// IE throws on elements created in popups
// FF meanwhile throws on frame elements through "defaultView.getComputedStyle"
var c=b.ownerDocument.defaultView;return c&&c.opener||(c=a),c.getComputedStyle(b)},Za=function(a,b,c,d){var e,f,g={};
// Remember the old values, and insert the new ones
for(f in b)g[f]=a.style[f],a.style[f]=b[f];e=c.apply(a,d||[]);
// Revert the old values
for(f in b)a.style[f]=g[f];return e},$a=X.documentElement;!function(){
// Executing both pixelPosition & boxSizingReliable tests require only one layout
// so they're executed at the same time to save the second computation.
function b(){h.style.cssText="-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;position:relative;display:block;margin:auto;border:1px;padding:1px;top:1%;width:50%",h.innerHTML="",$a.appendChild(g);var b=a.getComputedStyle(h);c="1%"!==b.top,f="2px"===b.marginLeft,d="4px"===b.width,h.style.marginRight="50%",e="4px"===b.marginRight,$a.removeChild(g)}var c,d,e,f,g=X.createElement("div"),h=X.createElement("div");
// Finish early in limited (non-browser) environments
h.style&&(
// Support: IE9-11+
// Style of cloned element affects source element cloned (#8908)
h.style.backgroundClip="content-box",h.cloneNode(!0).style.backgroundClip="",da.clearCloneStyle="content-box"===h.style.backgroundClip,g.style.cssText="border:0;width:8px;height:0;top:0;left:-9999px;padding:0;margin-top:1px;position:absolute",g.appendChild(h),fa.extend(da,{pixelPosition:function(){
// This test is executed only once but we still do memoizing
// since we can use the boxSizingReliable pre-computing.
// No need to check if the test was already performed, though.
return b(),c},boxSizingReliable:function(){return null==d&&b(),d},pixelMarginRight:function(){
// Support: Android 4.0-4.3
// We're checking for boxSizingReliableVal here instead of pixelMarginRightVal
// since that compresses better and they're computed together anyway.
return null==d&&b(),e},reliableMarginLeft:function(){
// Support: IE <=8 only, Android 4.0 - 4.3 only, Firefox <=3 - 37
return null==d&&b(),f},reliableMarginRight:function(){
// Support: Android 2.3
// Check if div with explicit width and no margin-right incorrectly
// gets computed margin-right based on width of container. (#3333)
// WebKit Bug 13343 - getComputedStyle returns wrong value for margin-right
// This support function is only executed once so no memoizing is needed.
var b,c=h.appendChild(X.createElement("div"));
// Reset CSS: box-sizing; display; margin; border; padding
// Support: Android 2.3
// Vendor-prefix box-sizing
return c.style.cssText=h.style.cssText="-webkit-box-sizing:content-box;box-sizing:content-box;display:block;margin:0;border:0;padding:0",c.style.marginRight=c.style.width="0",h.style.width="1px",$a.appendChild(g),b=!parseFloat(a.getComputedStyle(c).marginRight),$a.removeChild(g),h.removeChild(c),b}}))}();var
// Swappable if display is none or starts with table
// except "table", "table-cell", or "table-caption"
// See here for display values: https://developer.mozilla.org/en-US/docs/CSS/display
_a=/^(none|table(?!-c[ea]).+)/,ab={position:"absolute",visibility:"hidden",display:"block"},bb={letterSpacing:"0",fontWeight:"400"},cb=["Webkit","O","Moz","ms"],db=X.createElement("div").style;fa.extend({
// Add in style property hooks for overriding the default
// behavior of getting and setting a style property
cssHooks:{opacity:{get:function(a,b){if(b){
// We should always get a number back from opacity
var c=A(a,"opacity");return""===c?"1":c}}}},
// Don't automatically add "px" to these possibly-unitless properties
cssNumber:{animationIterationCount:!0,columnCount:!0,fillOpacity:!0,flexGrow:!0,flexShrink:!0,fontWeight:!0,lineHeight:!0,opacity:!0,order:!0,orphans:!0,widows:!0,zIndex:!0,zoom:!0},
// Add in properties whose names you wish to fix before
// setting or getting the value
cssProps:{"float":"cssFloat"},
// Get and set the style property on a DOM Node
style:function(a,b,c,d){
// Don't set styles on text and comment nodes
if(a&&3!==a.nodeType&&8!==a.nodeType&&a.style){
// Make sure that we're working with the right name
var e,f,g,h=fa.camelCase(b),i=a.style;
// Check if we're setting a value
// Gets hook for the prefixed version, then unprefixed version
// Check if we're setting a value
// If a hook was provided get the non-computed value from there
// Convert "+=" or "-=" to relative numbers (#7345)
// Fixes bug #9237
// Make sure that null and NaN values aren't set (#7116)
// If a number was passed in, add the unit (except for certain CSS properties)
// Support: IE9-11+
// background-* props affect original clone's values
// If a hook was provided, use that value, otherwise just set the specified value
return b=fa.cssProps[h]||(fa.cssProps[h]=C(h)||h),g=fa.cssHooks[b]||fa.cssHooks[h],void 0===c?g&&"get"in g&&void 0!==(e=g.get(a,!1,d))?e:i[b]:(f=typeof c,"string"===f&&(e=Ea.exec(c))&&e[1]&&(c=j(a,b,e),f="number"),null!=c&&c===c&&("number"===f&&(c+=e&&e[3]||(fa.cssNumber[h]?"":"px")),da.clearCloneStyle||""!==c||0!==b.indexOf("background")||(i[b]="inherit"),g&&"set"in g&&void 0===(c=g.set(a,c,d))||(i[b]=c)),void 0)}},css:function(a,b,c,d){var e,f,g,h=fa.camelCase(b);return b=fa.cssProps[h]||(fa.cssProps[h]=C(h)||h),g=fa.cssHooks[b]||fa.cssHooks[h],g&&"get"in g&&(e=g.get(a,!0,c)),void 0===e&&(e=A(a,b,d)),"normal"===e&&b in bb&&(e=bb[b]),""===c||c?(f=parseFloat(e),c===!0||isFinite(f)?f||0:e):e}}),fa.each(["height","width"],function(a,b){fa.cssHooks[b]={get:function(a,c,d){return c?_a.test(fa.css(a,"display"))&&0===a.offsetWidth?Za(a,ab,function(){return F(a,b,d)}):F(a,b,d):void 0},set:function(a,c,d){var e,f=d&&Ya(a),g=d&&E(a,b,d,"border-box"===fa.css(a,"boxSizing",!1,f),f);
// Convert to pixels if value adjustment is needed
return g&&(e=Ea.exec(c))&&"px"!==(e[3]||"px")&&(a.style[b]=c,c=fa.css(a,b)),D(a,c,g)}}}),fa.cssHooks.marginLeft=B(da.reliableMarginLeft,function(a,b){return b?(parseFloat(A(a,"marginLeft"))||a.getBoundingClientRect().left-Za(a,{marginLeft:0},function(){return a.getBoundingClientRect().left}))+"px":void 0}),
// Support: Android 2.3
fa.cssHooks.marginRight=B(da.reliableMarginRight,function(a,b){return b?Za(a,{display:"inline-block"},A,[a,"marginRight"]):void 0}),
// These hooks are used by animate to expand properties
fa.each({margin:"",padding:"",border:"Width"},function(a,b){fa.cssHooks[a+b]={expand:function(c){for(var d=0,e={},
// Assumes a single number if not a string
f="string"==typeof c?c.split(" "):[c];4>d;d++)e[a+Fa[d]+b]=f[d]||f[d-2]||f[0];return e}},Wa.test(a)||(fa.cssHooks[a+b].set=D)}),fa.fn.extend({css:function(a,b){return xa(this,function(a,b,c){var d,e,f={},g=0;if(fa.isArray(b)){for(d=Ya(a),e=b.length;e>g;g++)f[b[g]]=fa.css(a,b[g],!1,d);return f}return void 0!==c?fa.style(a,b,c):fa.css(a,b)},a,b,arguments.length>1)},show:function(){return G(this,!0)},hide:function(){return G(this)},toggle:function(a){return"boolean"==typeof a?a?this.show():this.hide():this.each(function(){Ga(this)?fa(this).show():fa(this).hide()})}}),fa.Tween=H,H.prototype={constructor:H,init:function(a,b,c,d,e,f){this.elem=a,this.prop=c,this.easing=e||fa.easing._default,this.options=b,this.start=this.now=this.cur(),this.end=d,this.unit=f||(fa.cssNumber[c]?"":"px")},cur:function(){var a=H.propHooks[this.prop];return a&&a.get?a.get(this):H.propHooks._default.get(this)},run:function(a){var b,c=H.propHooks[this.prop];return this.options.duration?this.pos=b=fa.easing[this.easing](a,this.options.duration*a,0,1,this.options.duration):this.pos=b=a,this.now=(this.end-this.start)*b+this.start,this.options.step&&this.options.step.call(this.elem,this.now,this),c&&c.set?c.set(this):H.propHooks._default.set(this),this}},H.prototype.init.prototype=H.prototype,H.propHooks={_default:{get:function(a){var b;
// Use a property on the element directly when it is not a DOM element,
// or when there is no matching style property that exists.
// Use a property on the element directly when it is not a DOM element,
// or when there is no matching style property that exists.
// Passing an empty string as a 3rd parameter to .css will automatically
// attempt a parseFloat and fallback to a string if the parse fails.
// Simple values such as "10px" are parsed to Float;
// complex values such as "rotate(1rad)" are returned as-is.
return 1!==a.elem.nodeType||null!=a.elem[a.prop]&&null==a.elem.style[a.prop]?a.elem[a.prop]:(b=fa.css(a.elem,a.prop,""),b&&"auto"!==b?b:0)},set:function(a){
// Use step hook for back compat.
// Use cssHook if its there.
// Use .style if available and use plain properties where available.
fa.fx.step[a.prop]?fa.fx.step[a.prop](a):1!==a.elem.nodeType||null==a.elem.style[fa.cssProps[a.prop]]&&!fa.cssHooks[a.prop]?a.elem[a.prop]=a.now:fa.style(a.elem,a.prop,a.now+a.unit)}}},
// Support: IE9
// Panic based approach to setting things on disconnected nodes
H.propHooks.scrollTop=H.propHooks.scrollLeft={set:function(a){a.elem.nodeType&&a.elem.parentNode&&(a.elem[a.prop]=a.now)}},fa.easing={linear:function(a){return a},swing:function(a){return.5-Math.cos(a*Math.PI)/2},_default:"swing"},fa.fx=H.prototype.init,
// Back Compat <1.8 extension point
fa.fx.step={};var eb,fb,gb=/^(?:toggle|show|hide)$/,hb=/queueHooks$/;fa.Animation=fa.extend(N,{tweeners:{"*":[function(a,b){var c=this.createTween(a,b);return j(c.elem,a,Ea.exec(b),c),c}]},tweener:function(a,b){fa.isFunction(a)?(b=a,a=["*"]):a=a.match(va);for(var c,d=0,e=a.length;e>d;d++)c=a[d],N.tweeners[c]=N.tweeners[c]||[],N.tweeners[c].unshift(b)},prefilters:[L],prefilter:function(a,b){b?N.prefilters.unshift(a):N.prefilters.push(a)}}),fa.speed=function(a,b,c){var d=a&&"object"==typeof a?fa.extend({},a):{complete:c||!c&&b||fa.isFunction(a)&&a,duration:a,easing:c&&b||b&&!fa.isFunction(b)&&b};
// Normalize opt.queue - true/undefined/null -> "fx"
// Queueing
return d.duration=fa.fx.off?0:"number"==typeof d.duration?d.duration:d.duration in fa.fx.speeds?fa.fx.speeds[d.duration]:fa.fx.speeds._default,null!=d.queue&&d.queue!==!0||(d.queue="fx"),d.old=d.complete,d.complete=function(){fa.isFunction(d.old)&&d.old.call(this),d.queue&&fa.dequeue(this,d.queue)},d},fa.fn.extend({fadeTo:function(a,b,c,d){
// Show any hidden elements after setting opacity to 0
return this.filter(Ga).css("opacity",0).show().end().animate({opacity:b},a,c,d)},animate:function(a,b,c,d){var e=fa.isEmptyObject(a),f=fa.speed(b,c,d),g=function(){
// Operate on a copy of prop so per-property easing won't be lost
var b=N(this,fa.extend({},a),f);
// Empty animations, or finishing resolves immediately
(e||za.get(this,"finish"))&&b.stop(!0)};return g.finish=g,e||f.queue===!1?this.each(g):this.queue(f.queue,g)},stop:function(a,b,c){var d=function(a){var b=a.stop;delete a.stop,b(c)};return"string"!=typeof a&&(c=b,b=a,a=void 0),b&&a!==!1&&this.queue(a||"fx",[]),this.each(function(){var b=!0,e=null!=a&&a+"queueHooks",f=fa.timers,g=za.get(this);if(e)g[e]&&g[e].stop&&d(g[e]);else for(e in g)g[e]&&g[e].stop&&hb.test(e)&&d(g[e]);for(e=f.length;e--;)f[e].elem!==this||null!=a&&f[e].queue!==a||(f[e].anim.stop(c),b=!1,f.splice(e,1));
// Start the next in the queue if the last step wasn't forced.
// Timers currently will call their complete callbacks, which
// will dequeue but only if they were gotoEnd.
!b&&c||fa.dequeue(this,a)})},finish:function(a){return a!==!1&&(a=a||"fx"),this.each(function(){var b,c=za.get(this),d=c[a+"queue"],e=c[a+"queueHooks"],f=fa.timers,g=d?d.length:0;
// Look for any active animations, and finish them
for(
// Enable finishing flag on private data
c.finish=!0,
// Empty the queue first
fa.queue(this,a,[]),e&&e.stop&&e.stop.call(this,!0),b=f.length;b--;)f[b].elem===this&&f[b].queue===a&&(f[b].anim.stop(!0),f.splice(b,1));
// Look for any animations in the old queue and finish them
for(b=0;g>b;b++)d[b]&&d[b].finish&&d[b].finish.call(this);
// Turn off finishing flag
delete c.finish})}}),fa.each(["toggle","show","hide"],function(a,b){var c=fa.fn[b];fa.fn[b]=function(a,d,e){return null==a||"boolean"==typeof a?c.apply(this,arguments):this.animate(J(b,!0),a,d,e)}}),
// Generate shortcuts for custom animations
fa.each({slideDown:J("show"),slideUp:J("hide"),slideToggle:J("toggle"),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(a,b){fa.fn[a]=function(a,c,d){return this.animate(b,a,c,d)}}),fa.timers=[],fa.fx.tick=function(){var a,b=0,c=fa.timers;for(eb=fa.now();b<c.length;b++)a=c[b],a()||c[b]!==a||c.splice(b--,1);c.length||fa.fx.stop(),eb=void 0},fa.fx.timer=function(a){fa.timers.push(a),a()?fa.fx.start():fa.timers.pop()},fa.fx.interval=13,fa.fx.start=function(){fb||(fb=a.setInterval(fa.fx.tick,fa.fx.interval))},fa.fx.stop=function(){a.clearInterval(fb),fb=null},fa.fx.speeds={slow:600,fast:200,
// Default speed
_default:400},
// Based off of the plugin by Clint Helfers, with permission.
// http://web.archive.org/web/20100324014747/http://blindsignals.com/index.php/2009/07/jquery-delay/
fa.fn.delay=function(b,c){return b=fa.fx?fa.fx.speeds[b]||b:b,c=c||"fx",this.queue(c,function(c,d){var e=a.setTimeout(c,b);d.stop=function(){a.clearTimeout(e)}})},function(){var a=X.createElement("input"),b=X.createElement("select"),c=b.appendChild(X.createElement("option"));a.type="checkbox",
// Support: iOS<=5.1, Android<=4.2+
// Default value for a checkbox should be "on"
da.checkOn=""!==a.value,
// Support: IE<=11+
// Must access selectedIndex to make default options select
da.optSelected=c.selected,
// Support: Android<=2.3
// Options inside disabled selects are incorrectly marked as disabled
b.disabled=!0,da.optDisabled=!c.disabled,a=X.createElement("input"),a.value="t",a.type="radio",da.radioValue="t"===a.value}();var ib,jb=fa.expr.attrHandle;fa.fn.extend({attr:function(a,b){return xa(this,fa.attr,a,b,arguments.length>1)},removeAttr:function(a){return this.each(function(){fa.removeAttr(this,a)})}}),fa.extend({attr:function(a,b,c){var d,e,f=a.nodeType;
// Don't get/set attributes on text, comment and attribute nodes
if(3!==f&&8!==f&&2!==f)
// Fallback to prop when attributes are not supported
// Fallback to prop when attributes are not supported
// All attributes are lowercase
// Grab necessary hook if one is defined
return"undefined"==typeof a.getAttribute?fa.prop(a,b,c):(1===f&&fa.isXMLDoc(a)||(b=b.toLowerCase(),e=fa.attrHooks[b]||(fa.expr.match.bool.test(b)?ib:void 0)),void 0!==c?null===c?void fa.removeAttr(a,b):e&&"set"in e&&void 0!==(d=e.set(a,c,b))?d:(a.setAttribute(b,c+""),c):e&&"get"in e&&null!==(d=e.get(a,b))?d:(d=fa.find.attr(a,b),null==d?void 0:d))},attrHooks:{type:{set:function(a,b){if(!da.radioValue&&"radio"===b&&fa.nodeName(a,"input")){var c=a.value;return a.setAttribute("type",b),c&&(a.value=c),b}}}},removeAttr:function(a,b){var c,d,e=0,f=b&&b.match(va);if(f&&1===a.nodeType)for(;c=f[e++];)d=fa.propFix[c]||c,fa.expr.match.bool.test(c)&&(a[d]=!1),a.removeAttribute(c)}}),ib={set:function(a,b,c){
// Remove boolean attributes when set to false
return b===!1?fa.removeAttr(a,c):a.setAttribute(c,c),c}},fa.each(fa.expr.match.bool.source.match(/\w+/g),function(a,b){var c=jb[b]||fa.find.attr;jb[b]=function(a,b,d){var e,f;return d||(f=jb[b],jb[b]=e,e=null!=c(a,b,d)?b.toLowerCase():null,jb[b]=f),e}});var kb=/^(?:input|select|textarea|button)$/i,lb=/^(?:a|area)$/i;fa.fn.extend({prop:function(a,b){return xa(this,fa.prop,a,b,arguments.length>1)},removeProp:function(a){return this.each(function(){delete this[fa.propFix[a]||a]})}}),fa.extend({prop:function(a,b,c){var d,e,f=a.nodeType;
// Don't get/set properties on text, comment and attribute nodes
if(3!==f&&8!==f&&2!==f)
// Fix name and attach hooks
return 1===f&&fa.isXMLDoc(a)||(b=fa.propFix[b]||b,e=fa.propHooks[b]),void 0!==c?e&&"set"in e&&void 0!==(d=e.set(a,c,b))?d:a[b]=c:e&&"get"in e&&null!==(d=e.get(a,b))?d:a[b]},propHooks:{tabIndex:{get:function(a){
// elem.tabIndex doesn't always return the
// correct value when it hasn't been explicitly set
// http://fluidproject.org/blog/2008/01/09/getting-setting-and-removing-tabindex-values-with-javascript/
// Use proper attribute retrieval(#12072)
var b=fa.find.attr(a,"tabindex");return b?parseInt(b,10):kb.test(a.nodeName)||lb.test(a.nodeName)&&a.href?0:-1}}},propFix:{"for":"htmlFor","class":"className"}}),da.optSelected||(fa.propHooks.selected={get:function(a){var b=a.parentNode;return b&&b.parentNode&&b.parentNode.selectedIndex,null}}),fa.each(["tabIndex","readOnly","maxLength","cellSpacing","cellPadding","rowSpan","colSpan","useMap","frameBorder","contentEditable"],function(){fa.propFix[this.toLowerCase()]=this});var mb=/[\t\r\n\f]/g;fa.fn.extend({addClass:function(a){var b,c,d,e,f,g,h,i=0;if(fa.isFunction(a))return this.each(function(b){fa(this).addClass(a.call(this,b,O(this)))});if("string"==typeof a&&a)for(b=a.match(va)||[];c=this[i++];)if(e=O(c),d=1===c.nodeType&&(" "+e+" ").replace(mb," ")){for(g=0;f=b[g++];)d.indexOf(" "+f+" ")<0&&(d+=f+" ");
// Only assign if different to avoid unneeded rendering.
h=fa.trim(d),e!==h&&c.setAttribute("class",h)}return this},removeClass:function(a){var b,c,d,e,f,g,h,i=0;if(fa.isFunction(a))return this.each(function(b){fa(this).removeClass(a.call(this,b,O(this)))});if(!arguments.length)return this.attr("class","");if("string"==typeof a&&a)for(b=a.match(va)||[];c=this[i++];)if(e=O(c),d=1===c.nodeType&&(" "+e+" ").replace(mb," ")){for(g=0;f=b[g++];)
// Remove *all* instances
for(;d.indexOf(" "+f+" ")>-1;)d=d.replace(" "+f+" "," ");
// Only assign if different to avoid unneeded rendering.
h=fa.trim(d),e!==h&&c.setAttribute("class",h)}return this},toggleClass:function(a,b){var c=typeof a;return"boolean"==typeof b&&"string"===c?b?this.addClass(a):this.removeClass(a):fa.isFunction(a)?this.each(function(c){fa(this).toggleClass(a.call(this,c,O(this),b),b)}):this.each(function(){var b,d,e,f;if("string"===c)for(d=0,e=fa(this),f=a.match(va)||[];b=f[d++];)e.hasClass(b)?e.removeClass(b):e.addClass(b);else void 0!==a&&"boolean"!==c||(b=O(this),b&&za.set(this,"__className__",b),this.setAttribute&&this.setAttribute("class",b||a===!1?"":za.get(this,"__className__")||""))})},hasClass:function(a){var b,c,d=0;for(b=" "+a+" ";c=this[d++];)if(1===c.nodeType&&(" "+O(c)+" ").replace(mb," ").indexOf(b)>-1)return!0;return!1}});var nb=/\r/g;fa.fn.extend({val:function(a){var b,c,d,e=this[0];{if(arguments.length)return d=fa.isFunction(a),this.each(function(c){var e;1===this.nodeType&&(e=d?a.call(this,c,fa(this).val()):a,null==e?e="":"number"==typeof e?e+="":fa.isArray(e)&&(e=fa.map(e,function(a){return null==a?"":a+""})),b=fa.valHooks[this.type]||fa.valHooks[this.nodeName.toLowerCase()],b&&"set"in b&&void 0!==b.set(this,e,"value")||(this.value=e))});if(e)
// Handle most common string cases
// Handle cases where value is null/undef or number
return b=fa.valHooks[e.type]||fa.valHooks[e.nodeName.toLowerCase()],b&&"get"in b&&void 0!==(c=b.get(e,"value"))?c:(c=e.value,"string"==typeof c?c.replace(nb,""):null==c?"":c)}}}),fa.extend({valHooks:{option:{get:function(a){
// Support: IE<11
// option.value not trimmed (#14858)
return fa.trim(a.value)}},select:{get:function(a){
// Loop through all the selected options
for(var b,c,d=a.options,e=a.selectedIndex,f="select-one"===a.type||0>e,g=f?null:[],h=f?e+1:d.length,i=0>e?h:f?e:0;h>i;i++)
// IE8-9 doesn't update selected after form reset (#2551)
if(c=d[i],(c.selected||i===e)&&(da.optDisabled?!c.disabled:null===c.getAttribute("disabled"))&&(!c.parentNode.disabled||!fa.nodeName(c.parentNode,"optgroup"))){
// We don't need an array for one selects
if(b=fa(c).val(),f)return b;
// Multi-Selects return an array
g.push(b)}return g},set:function(a,b){for(var c,d,e=a.options,f=fa.makeArray(b),g=e.length;g--;)d=e[g],(d.selected=fa.inArray(fa.valHooks.option.get(d),f)>-1)&&(c=!0);
// Force browsers to behave consistently when non-matching value is set
return c||(a.selectedIndex=-1),f}}}}),
// Radios and checkboxes getter/setter
fa.each(["radio","checkbox"],function(){fa.valHooks[this]={set:function(a,b){return fa.isArray(b)?a.checked=fa.inArray(fa(a).val(),b)>-1:void 0}},da.checkOn||(fa.valHooks[this].get=function(a){return null===a.getAttribute("value")?"on":a.value})});
// Return jQuery for attributes-only inclusion
var ob=/^(?:focusinfocus|focusoutblur)$/;fa.extend(fa.event,{trigger:function(b,c,d,e){var f,g,h,i,j,k,l,m=[d||X],n=ca.call(b,"type")?b.type:b,o=ca.call(b,"namespace")?b.namespace.split("."):[];
// Don't do events on text and comment nodes
if(g=h=d=d||X,3!==d.nodeType&&8!==d.nodeType&&!ob.test(n+fa.event.triggered)&&(n.indexOf(".")>-1&&(o=n.split("."),n=o.shift(),o.sort()),j=n.indexOf(":")<0&&"on"+n,b=b[fa.expando]?b:new fa.Event(n,"object"==typeof b&&b),b.isTrigger=e?2:3,b.namespace=o.join("."),b.rnamespace=b.namespace?new RegExp("(^|\\.)"+o.join("\\.(?:.*\\.|)")+"(\\.|$)"):null,b.result=void 0,b.target||(b.target=d),c=null==c?[b]:fa.makeArray(c,[b]),l=fa.event.special[n]||{},e||!l.trigger||l.trigger.apply(d,c)!==!1)){
// Determine event propagation path in advance, per W3C events spec (#9951)
// Bubble up to document, then to window; watch for a global ownerDocument var (#9724)
if(!e&&!l.noBubble&&!fa.isWindow(d)){for(i=l.delegateType||n,ob.test(i+n)||(g=g.parentNode);g;g=g.parentNode)m.push(g),h=g;
// Only add window if we got to document (e.g., not plain obj or detached DOM)
h===(d.ownerDocument||X)&&m.push(h.defaultView||h.parentWindow||a)}for(
// Fire handlers on the event path
f=0;(g=m[f++])&&!b.isPropagationStopped();)b.type=f>1?i:l.bindType||n,k=(za.get(g,"events")||{})[b.type]&&za.get(g,"handle"),k&&k.apply(g,c),k=j&&g[j],k&&k.apply&&ya(g)&&(b.result=k.apply(g,c),b.result===!1&&b.preventDefault());
// If nobody prevented the default action, do it now
// Call a native DOM method on the target with the same name name as the event.
// Don't do default actions on window, that's where global variables be (#6170)
// Don't re-trigger an onFOO event when we call its FOO() method
// Prevent re-triggering of the same event, since we already bubbled it above
return b.type=n,e||b.isDefaultPrevented()||l._default&&l._default.apply(m.pop(),c)!==!1||!ya(d)||j&&fa.isFunction(d[n])&&!fa.isWindow(d)&&(h=d[j],h&&(d[j]=null),fa.event.triggered=n,d[n](),fa.event.triggered=void 0,h&&(d[j]=h)),b.result}},
// Piggyback on a donor event to simulate a different one
simulate:function(a,b,c){var d=fa.extend(new fa.Event,c,{type:a,isSimulated:!0});fa.event.trigger(d,null,b),d.isDefaultPrevented()&&c.preventDefault()}}),fa.fn.extend({trigger:function(a,b){return this.each(function(){fa.event.trigger(a,b,this)})},triggerHandler:function(a,b){var c=this[0];return c?fa.event.trigger(a,b,c,!0):void 0}}),fa.each("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error contextmenu".split(" "),function(a,b){
// Handle event binding
fa.fn[b]=function(a,c){return arguments.length>0?this.on(b,null,a,c):this.trigger(b)}}),fa.fn.extend({hover:function(a,b){return this.mouseenter(a).mouseleave(b||a)}}),da.focusin="onfocusin"in a,
// Support: Firefox
// Firefox doesn't have focus(in | out) events
// Related ticket - https://bugzilla.mozilla.org/show_bug.cgi?id=687787
//
// Support: Chrome, Safari
// focus(in | out) events fire after focus & blur events,
// which is spec violation - http://www.w3.org/TR/DOM-Level-3-Events/#events-focusevent-event-order
// Related ticket - https://code.google.com/p/chromium/issues/detail?id=449857
da.focusin||fa.each({focus:"focusin",blur:"focusout"},function(a,b){
// Attach a single capturing handler on the document while someone wants focusin/focusout
var c=function(a){fa.event.simulate(b,a.target,fa.event.fix(a))};fa.event.special[b]={setup:function(){var d=this.ownerDocument||this,e=za.access(d,b);e||d.addEventListener(a,c,!0),za.access(d,b,(e||0)+1)},teardown:function(){var d=this.ownerDocument||this,e=za.access(d,b)-1;e?za.access(d,b,e):(d.removeEventListener(a,c,!0),za.remove(d,b))}}});var pb=a.location,qb=fa.now(),rb=/\?/;
// Support: Android 2.3
// Workaround failure to string-cast null input
fa.parseJSON=function(a){return JSON.parse(a+"")},
// Cross-browser xml parsing
fa.parseXML=function(b){var c;if(!b||"string"!=typeof b)return null;
// Support: IE9
try{c=(new a.DOMParser).parseFromString(b,"text/xml")}catch(d){c=void 0}return c&&!c.getElementsByTagName("parsererror").length||fa.error("Invalid XML: "+b),c};var sb=/#.*$/,tb=/([?&])_=[^&]*/,ub=/^(.*?):[ \t]*([^\r\n]*)$/gm,
// #7653, #8125, #8152: local protocol detection
vb=/^(?:about|app|app-storage|.+-extension|file|res|widget):$/,wb=/^(?:GET|HEAD)$/,xb=/^\/\//,/* Prefilters
	 * 1) They are useful to introduce custom dataTypes (see ajax/jsonp.js for an example)
	 * 2) These are called:
	 *    - BEFORE asking for a transport
	 *    - AFTER param serialization (s.data is a string if s.processData is true)
	 * 3) key is the dataType
	 * 4) the catchall symbol "*" can be used
	 * 5) execution will start with transport dataType and THEN continue down to "*" if needed
	 */
yb={},/* Transports bindings
	 * 1) key is the dataType
	 * 2) the catchall symbol "*" can be used
	 * 3) selection will start with transport dataType and THEN go to "*" if needed
	 */
zb={},
// Avoid comment-prolog char sequence (#10098); must appease lint and evade compression
Ab="*/".concat("*"),
// Anchor tag for parsing the document origin
Bb=X.createElement("a");Bb.href=pb.href,fa.extend({
// Counter for holding the number of active queries
active:0,
// Last-Modified header cache for next request
lastModified:{},etag:{},ajaxSettings:{url:pb.href,type:"GET",isLocal:vb.test(pb.protocol),global:!0,processData:!0,async:!0,contentType:"application/x-www-form-urlencoded; charset=UTF-8",/*
		timeout: 0,
		data: null,
		dataType: null,
		username: null,
		password: null,
		cache: null,
		throws: false,
		traditional: false,
		headers: {},
		*/
accepts:{"*":Ab,text:"text/plain",html:"text/html",xml:"application/xml, text/xml",json:"application/json, text/javascript"},contents:{xml:/\bxml\b/,html:/\bhtml/,json:/\bjson\b/},responseFields:{xml:"responseXML",text:"responseText",json:"responseJSON"},
// Data converters
// Keys separate source (or catchall "*") and destination types with a single space
converters:{
// Convert anything to text
"* text":String,
// Text to html (true = no transformation)
"text html":!0,
// Evaluate text as a json expression
"text json":fa.parseJSON,
// Parse text as xml
"text xml":fa.parseXML},
// For options that shouldn't be deep extended:
// you can add your own custom options here if
// and when you create one that shouldn't be
// deep extended (see ajaxExtend)
flatOptions:{url:!0,context:!0}},
// Creates a full fledged settings object into target
// with both ajaxSettings and settings fields.
// If target is omitted, writes into ajaxSettings.
ajaxSetup:function(a,b){
// Building a settings object
// Extending ajaxSettings
return b?R(R(a,fa.ajaxSettings),b):R(fa.ajaxSettings,a)},ajaxPrefilter:P(yb),ajaxTransport:P(zb),
// Main method
ajax:function(b,c){
// Callback for when everything is done
function d(b,c,d,h){var j,l,s,t,v,x=c;
// Called once
2!==u&&(u=2,i&&a.clearTimeout(i),e=void 0,g=h||"",w.readyState=b>0?4:0,j=b>=200&&300>b||304===b,d&&(t=S(m,w,d)),t=T(m,t,w,j),j?(m.ifModified&&(v=w.getResponseHeader("Last-Modified"),v&&(fa.lastModified[f]=v),v=w.getResponseHeader("etag"),v&&(fa.etag[f]=v)),204===b||"HEAD"===m.type?x="nocontent":304===b?x="notmodified":(x=t.state,l=t.data,s=t.error,j=!s)):(s=x,!b&&x||(x="error",0>b&&(b=0))),w.status=b,w.statusText=(c||x)+"",j?p.resolveWith(n,[l,x,w]):p.rejectWith(n,[w,x,s]),w.statusCode(r),r=void 0,k&&o.trigger(j?"ajaxSuccess":"ajaxError",[w,m,j?l:s]),q.fireWith(n,[w,x]),k&&(o.trigger("ajaxComplete",[w,m]),--fa.active||fa.event.trigger("ajaxStop")))}
// If url is an object, simulate pre-1.5 signature
"object"==typeof b&&(c=b,b=void 0),
// Force options to be an object
c=c||{};var e,
// URL without anti-cache param
f,
// Response headers
g,h,
// timeout handle
i,
// Url cleanup var
j,
// To know if global events are to be dispatched
k,
// Loop variable
l,
// Create the final options object
m=fa.ajaxSetup({},c),
// Callbacks context
n=m.context||m,
// Context for global events is callbackContext if it is a DOM node or jQuery collection
o=m.context&&(n.nodeType||n.jquery)?fa(n):fa.event,
// Deferreds
p=fa.Deferred(),q=fa.Callbacks("once memory"),
// Status-dependent callbacks
r=m.statusCode||{},
// Headers (they are sent all at once)
s={},t={},
// The jqXHR state
u=0,
// Default abort message
v="canceled",
// Fake xhr
w={readyState:0,
// Builds headers hashtable if needed
getResponseHeader:function(a){var b;if(2===u){if(!h)for(h={};b=ub.exec(g);)h[b[1].toLowerCase()]=b[2];b=h[a.toLowerCase()]}return null==b?null:b},
// Raw string
getAllResponseHeaders:function(){return 2===u?g:null},
// Caches the header
setRequestHeader:function(a,b){var c=a.toLowerCase();return u||(a=t[c]=t[c]||a,s[a]=b),this},
// Overrides response content-type header
overrideMimeType:function(a){return u||(m.mimeType=a),this},
// Status-dependent callbacks
statusCode:function(a){var b;if(a)if(2>u)for(b in a)
// Lazy-add the new callback in a way that preserves old ones
r[b]=[r[b],a[b]];else
// Execute the appropriate callbacks
w.always(a[w.status]);return this},
// Cancel the request
abort:function(a){var b=a||v;return e&&e.abort(b),d(0,b),this}};
// A cross-domain request is in order when the origin doesn't match the current origin.
if(
// Attach deferreds
p.promise(w).complete=q.add,w.success=w.done,w.error=w.fail,
// Remove hash character (#7531: and string promotion)
// Add protocol if not provided (prefilters might expect it)
// Handle falsy url in the settings object (#10093: consistency with old signature)
// We also use the url parameter if available
m.url=((b||m.url||pb.href)+"").replace(sb,"").replace(xb,pb.protocol+"//"),
// Alias method option to type as per ticket #12004
m.type=c.method||c.type||m.method||m.type,
// Extract dataTypes list
m.dataTypes=fa.trim(m.dataType||"*").toLowerCase().match(va)||[""],null==m.crossDomain){j=X.createElement("a");
// Support: IE8-11+
// IE throws exception if url is malformed, e.g. http://example.com:80x/
try{j.href=m.url,
// Support: IE8-11+
// Anchor's host property isn't correctly set when s.url is relative
j.href=j.href,m.crossDomain=Bb.protocol+"//"+Bb.host!=j.protocol+"//"+j.host}catch(x){
// If there is an error parsing the URL, assume it is crossDomain,
// it can be rejected by the transport if it is invalid
m.crossDomain=!0}}
// If request was aborted inside a prefilter, stop there
if(
// Convert data if not already a string
m.data&&m.processData&&"string"!=typeof m.data&&(m.data=fa.param(m.data,m.traditional)),
// Apply prefilters
Q(yb,m,c,w),2===u)return w;k=fa.event&&m.global,k&&0===fa.active++&&fa.event.trigger("ajaxStart"),m.type=m.type.toUpperCase(),m.hasContent=!wb.test(m.type),f=m.url,m.hasContent||(m.data&&(f=m.url+=(rb.test(f)?"&":"?")+m.data,delete m.data),m.cache===!1&&(m.url=tb.test(f)?f.replace(tb,"$1_="+qb++):f+(rb.test(f)?"&":"?")+"_="+qb++)),m.ifModified&&(fa.lastModified[f]&&w.setRequestHeader("If-Modified-Since",fa.lastModified[f]),fa.etag[f]&&w.setRequestHeader("If-None-Match",fa.etag[f])),(m.data&&m.hasContent&&m.contentType!==!1||c.contentType)&&w.setRequestHeader("Content-Type",m.contentType),w.setRequestHeader("Accept",m.dataTypes[0]&&m.accepts[m.dataTypes[0]]?m.accepts[m.dataTypes[0]]+("*"!==m.dataTypes[0]?", "+Ab+"; q=0.01":""):m.accepts["*"]);
// Check for headers option
for(l in m.headers)w.setRequestHeader(l,m.headers[l]);
// Allow custom headers/mimetypes and early abort
if(m.beforeSend&&(m.beforeSend.call(n,w,m)===!1||2===u))
// Abort if not done already and return
return w.abort();
// Aborting is no longer a cancellation
v="abort";
// Install callbacks on deferreds
for(l in{success:1,error:1,complete:1})w[l](m[l]);
// If no transport, we auto-abort
if(e=Q(zb,m,c,w)){
// If request was aborted inside ajaxSend, stop there
if(w.readyState=1,
// Send global event
k&&o.trigger("ajaxSend",[w,m]),2===u)return w;
// Timeout
m.async&&m.timeout>0&&(i=a.setTimeout(function(){w.abort("timeout")},m.timeout));try{u=1,e.send(s,d)}catch(x){
// Propagate exception as error if not done
if(!(2>u))throw x;d(-1,x)}}else d(-1,"No Transport");return w},getJSON:function(a,b,c){return fa.get(a,b,c,"json")},getScript:function(a,b){return fa.get(a,void 0,b,"script")}}),fa.each(["get","post"],function(a,b){fa[b]=function(a,c,d,e){
// The url can be an options object (which then must have .url)
// Shift arguments if data argument was omitted
return fa.isFunction(c)&&(e=e||d,d=c,c=void 0),fa.ajax(fa.extend({url:a,type:b,dataType:e,data:c,success:d},fa.isPlainObject(a)&&a))}}),fa._evalUrl=function(a){return fa.ajax({url:a,
// Make this explicit, since user can override this through ajaxSetup (#11264)
type:"GET",dataType:"script",async:!1,global:!1,"throws":!0})},fa.fn.extend({wrapAll:function(a){var b;
// The elements to wrap the target around
return fa.isFunction(a)?this.each(function(b){fa(this).wrapAll(a.call(this,b))}):(this[0]&&(b=fa(a,this[0].ownerDocument).eq(0).clone(!0),this[0].parentNode&&b.insertBefore(this[0]),b.map(function(){for(var a=this;a.firstElementChild;)a=a.firstElementChild;return a}).append(this)),this)},wrapInner:function(a){return fa.isFunction(a)?this.each(function(b){fa(this).wrapInner(a.call(this,b))}):this.each(function(){var b=fa(this),c=b.contents();c.length?c.wrapAll(a):b.append(a)})},wrap:function(a){var b=fa.isFunction(a);return this.each(function(c){fa(this).wrapAll(b?a.call(this,c):a)})},unwrap:function(){return this.parent().each(function(){fa.nodeName(this,"body")||fa(this).replaceWith(this.childNodes)}).end()}}),fa.expr.filters.hidden=function(a){return!fa.expr.filters.visible(a)},fa.expr.filters.visible=function(a){
// Support: Opera <= 12.12
// Opera reports offsetWidths and offsetHeights less than zero on some elements
// Use OR instead of AND as the element is not visible if either is true
// See tickets #10406 and #13132
return a.offsetWidth>0||a.offsetHeight>0||a.getClientRects().length>0};var Cb=/%20/g,Db=/\[\]$/,Eb=/\r?\n/g,Fb=/^(?:submit|button|image|reset|file)$/i,Gb=/^(?:input|select|textarea|keygen)/i;
// Serialize an array of form elements or a set of
// key/values into a query string
fa.param=function(a,b){var c,d=[],e=function(a,b){b=fa.isFunction(b)?b():null==b?"":b,d[d.length]=encodeURIComponent(a)+"="+encodeURIComponent(b)};
// If an array was passed in, assume that it is an array of form elements.
if(
// Set traditional to true for jQuery <= 1.3.2 behavior.
void 0===b&&(b=fa.ajaxSettings&&fa.ajaxSettings.traditional),fa.isArray(a)||a.jquery&&!fa.isPlainObject(a))
// Serialize the form elements
fa.each(a,function(){e(this.name,this.value)});else
// If traditional, encode the "old" way (the way 1.3.2 or older
// did it), otherwise encode params recursively.
for(c in a)U(c,a[c],b,e);
// Return the resulting serialization
return d.join("&").replace(Cb,"+")},fa.fn.extend({serialize:function(){return fa.param(this.serializeArray())},serializeArray:function(){return this.map(function(){
// Can add propHook for "elements" to filter or add form elements
var a=fa.prop(this,"elements");return a?fa.makeArray(a):this}).filter(function(){var a=this.type;
// Use .is( ":disabled" ) so that fieldset[disabled] works
return this.name&&!fa(this).is(":disabled")&&Gb.test(this.nodeName)&&!Fb.test(a)&&(this.checked||!Ha.test(a))}).map(function(a,b){var c=fa(this).val();return null==c?null:fa.isArray(c)?fa.map(c,function(a){return{name:b.name,value:a.replace(Eb,"\r\n")}}):{name:b.name,value:c.replace(Eb,"\r\n")}}).get()}}),fa.ajaxSettings.xhr=function(){try{return new a.XMLHttpRequest}catch(b){}};var Hb={
// File protocol always yields status code 0, assume 200
0:200,
// Support: IE9
// #1450: sometimes IE returns 1223 when it should be 204
1223:204},Ib=fa.ajaxSettings.xhr();da.cors=!!Ib&&"withCredentials"in Ib,da.ajax=Ib=!!Ib,fa.ajaxTransport(function(b){var c,d;
// Cross domain only allowed if supported through XMLHttpRequest
// Cross domain only allowed if supported through XMLHttpRequest
return da.cors||Ib&&!b.crossDomain?{send:function(e,f){var g,h=b.xhr();
// Apply custom fields if provided
if(h.open(b.type,b.url,b.async,b.username,b.password),b.xhrFields)for(g in b.xhrFields)h[g]=b.xhrFields[g];
// Override mime type if needed
b.mimeType&&h.overrideMimeType&&h.overrideMimeType(b.mimeType),
// X-Requested-With header
// For cross-domain requests, seeing as conditions for a preflight are
// akin to a jigsaw puzzle, we simply never set it to be sure.
// (it can always be set on a per-request basis or even using ajaxSetup)
// For same-domain requests, won't change header if already provided.
b.crossDomain||e["X-Requested-With"]||(e["X-Requested-With"]="XMLHttpRequest");
// Set headers
for(g in e)h.setRequestHeader(g,e[g]);c=function(a){return function(){c&&(c=d=h.onload=h.onerror=h.onabort=h.onreadystatechange=null,"abort"===a?h.abort():"error"===a?"number"!=typeof h.status?f(0,"error"):f(h.status,h.statusText):f(Hb[h.status]||h.status,h.statusText,"text"!==(h.responseType||"text")||"string"!=typeof h.responseText?{binary:h.response}:{text:h.responseText},h.getAllResponseHeaders()))}},h.onload=c(),d=h.onerror=c("error"),void 0!==h.onabort?h.onabort=d:h.onreadystatechange=function(){4===h.readyState&&a.setTimeout(function(){c&&d()})},c=c("abort");try{
// Do send the request (this may raise an exception)
h.send(b.hasContent&&b.data||null)}catch(i){
// #14683: Only rethrow if this hasn't been notified as an error yet
if(c)throw i}},abort:function(){c&&c()}}:void 0}),
// Install script dataType
fa.ajaxSetup({accepts:{script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},contents:{script:/\b(?:java|ecma)script\b/},converters:{"text script":function(a){return fa.globalEval(a),a}}}),
// Handle cache's special case and crossDomain
fa.ajaxPrefilter("script",function(a){void 0===a.cache&&(a.cache=!1),a.crossDomain&&(a.type="GET")}),
// Bind script tag hack transport
fa.ajaxTransport("script",function(a){
// This transport only deals with cross domain requests
if(a.crossDomain){var b,c;return{send:function(d,e){b=fa("<script>").prop({charset:a.scriptCharset,src:a.url}).on("load error",c=function(a){b.remove(),c=null,a&&e("error"===a.type?404:200,a.type)}),X.head.appendChild(b[0])},abort:function(){c&&c()}}}});var Jb=[],Kb=/(=)\?(?=&|$)|\?\?/;
// Default jsonp settings
fa.ajaxSetup({jsonp:"callback",jsonpCallback:function(){var a=Jb.pop()||fa.expando+"_"+qb++;return this[a]=!0,a}}),
// Detect, normalize options and install callbacks for jsonp requests
fa.ajaxPrefilter("json jsonp",function(b,c,d){var e,f,g,h=b.jsonp!==!1&&(Kb.test(b.url)?"url":"string"==typeof b.data&&0===(b.contentType||"").indexOf("application/x-www-form-urlencoded")&&Kb.test(b.data)&&"data");
// Handle iff the expected data type is "jsonp" or we have a parameter to set
// Handle iff the expected data type is "jsonp" or we have a parameter to set
// Get callback name, remembering preexisting value associated with it
// Insert callback into url or form data
// Use data converter to retrieve json after script execution
// Force json dataType
// Install callback
// Clean-up function (fires after converters)
return h||"jsonp"===b.dataTypes[0]?(e=b.jsonpCallback=fa.isFunction(b.jsonpCallback)?b.jsonpCallback():b.jsonpCallback,h?b[h]=b[h].replace(Kb,"$1"+e):b.jsonp!==!1&&(b.url+=(rb.test(b.url)?"&":"?")+b.jsonp+"="+e),b.converters["script json"]=function(){return g||fa.error(e+" was not called"),g[0]},b.dataTypes[0]="json",f=a[e],a[e]=function(){g=arguments},d.always(function(){void 0===f?fa(a).removeProp(e):a[e]=f,b[e]&&(b.jsonpCallback=c.jsonpCallback,Jb.push(e)),g&&fa.isFunction(f)&&f(g[0]),g=f=void 0}),"script"):void 0}),
// Support: Safari 8+
// In Safari 8 documents created via document.implementation.createHTMLDocument
// collapse sibling forms: the second one becomes a child of the first one.
// Because of that, this security measure has to be disabled in Safari 8.
// https://bugs.webkit.org/show_bug.cgi?id=137337
da.createHTMLDocument=function(){var a=X.implementation.createHTMLDocument("").body;return a.innerHTML="<form></form><form></form>",2===a.childNodes.length}(),
// Argument "data" should be string of html
// context (optional): If specified, the fragment will be created in this context,
// defaults to document
// keepScripts (optional): If true, will include scripts passed in the html string
fa.parseHTML=function(a,b,c){if(!a||"string"!=typeof a)return null;"boolean"==typeof b&&(c=b,b=!1),
// Stop scripts or inline event handlers from being executed immediately
// by using document.implementation
b=b||(da.createHTMLDocument?X.implementation.createHTMLDocument(""):X);var d=oa.exec(a),e=!c&&[];
// Single tag
// Single tag
return d?[b.createElement(d[1])]:(d=m([a],b,e),e&&e.length&&fa(e).remove(),fa.merge([],d.childNodes))};
// Keep a copy of the old load method
var Lb=fa.fn.load;/**
 * Load a url into a page
 */
fa.fn.load=function(a,b,c){if("string"!=typeof a&&Lb)return Lb.apply(this,arguments);var d,e,f,g=this,h=a.indexOf(" ");
// If it's a function
// We assume that it's the callback
// If we have elements to modify, make the request
return h>-1&&(d=fa.trim(a.slice(h)),a=a.slice(0,h)),fa.isFunction(b)?(c=b,b=void 0):b&&"object"==typeof b&&(e="POST"),g.length>0&&fa.ajax({url:a,
// If "type" variable is undefined, then "GET" method will be used.
// Make value of this field explicit since
// user can override it through ajaxSetup method
type:e||"GET",dataType:"html",data:b}).done(function(a){f=arguments,g.html(d?fa("<div>").append(fa.parseHTML(a)).find(d):a)}).always(c&&function(a,b){g.each(function(){c.apply(g,f||[a.responseText,b,a])})}),this},
// Attach a bunch of functions for handling common AJAX events
fa.each(["ajaxStart","ajaxStop","ajaxComplete","ajaxError","ajaxSuccess","ajaxSend"],function(a,b){fa.fn[b]=function(a){return this.on(b,a)}}),fa.expr.filters.animated=function(a){return fa.grep(fa.timers,function(b){return a===b.elem}).length},fa.offset={setOffset:function(a,b,c){var d,e,f,g,h,i,j,k=fa.css(a,"position"),l=fa(a),m={};
// Set position first, in-case top/left are set even on static elem
"static"===k&&(a.style.position="relative"),h=l.offset(),f=fa.css(a,"top"),i=fa.css(a,"left"),j=("absolute"===k||"fixed"===k)&&(f+i).indexOf("auto")>-1,j?(d=l.position(),g=d.top,e=d.left):(g=parseFloat(f)||0,e=parseFloat(i)||0),fa.isFunction(b)&&(b=b.call(a,c,fa.extend({},h))),null!=b.top&&(m.top=b.top-h.top+g),null!=b.left&&(m.left=b.left-h.left+e),"using"in b?b.using.call(a,m):l.css(m)}},fa.fn.extend({offset:function(a){if(arguments.length)return void 0===a?this:this.each(function(b){fa.offset.setOffset(this,a,b)});var b,c,d=this[0],e={top:0,left:0},f=d&&d.ownerDocument;if(f)
// Make sure it's not a disconnected DOM node
// Make sure it's not a disconnected DOM node
return b=f.documentElement,fa.contains(b,d)?(e=d.getBoundingClientRect(),c=V(f),{top:e.top+c.pageYOffset-b.clientTop,left:e.left+c.pageXOffset-b.clientLeft}):e},position:function(){if(this[0]){var a,b,c=this[0],d={top:0,left:0};
// Subtract parent offsets and element margins
// Fixed elements are offset from window (parentOffset = {top:0, left: 0},
// because it is its only offset parent
// Assume getBoundingClientRect is there when computed position is fixed
// Get *real* offsetParent
// Get correct offsets
// Add offsetParent borders
return"fixed"===fa.css(c,"position")?b=c.getBoundingClientRect():(a=this.offsetParent(),b=this.offset(),fa.nodeName(a[0],"html")||(d=a.offset()),d.top+=fa.css(a[0],"borderTopWidth",!0),d.left+=fa.css(a[0],"borderLeftWidth",!0)),{top:b.top-d.top-fa.css(c,"marginTop",!0),left:b.left-d.left-fa.css(c,"marginLeft",!0)}}},
// This method will return documentElement in the following cases:
// 1) For the element inside the iframe without offsetParent, this method will return
//    documentElement of the parent window
// 2) For the hidden or detached element
// 3) For body or html element, i.e. in case of the html node - it will return itself
//
// but those exceptions were never presented as a real life use-cases
// and might be considered as more preferable results.
//
// This logic, however, is not guaranteed and can change at any point in the future
offsetParent:function(){return this.map(function(){for(var a=this.offsetParent;a&&"static"===fa.css(a,"position");)a=a.offsetParent;return a||$a})}}),
// Create scrollLeft and scrollTop methods
fa.each({scrollLeft:"pageXOffset",scrollTop:"pageYOffset"},function(a,b){var c="pageYOffset"===b;fa.fn[a]=function(d){return xa(this,function(a,d,e){var f=V(a);return void 0===e?f?f[b]:a[d]:void(f?f.scrollTo(c?f.pageXOffset:e,c?e:f.pageYOffset):a[d]=e)},a,d,arguments.length)}}),
// Support: Safari<7-8+, Chrome<37-44+
// Add the top/left cssHooks using jQuery.fn.position
// Webkit bug: https://bugs.webkit.org/show_bug.cgi?id=29084
// Blink bug: https://code.google.com/p/chromium/issues/detail?id=229280
// getComputedStyle returns percent when specified for top/left/bottom/right;
// rather than make the css module depend on the offset module, just check for it here
fa.each(["top","left"],function(a,b){fa.cssHooks[b]=B(da.pixelPosition,function(a,c){return c?(c=A(a,b),Xa.test(c)?fa(a).position()[b]+"px":c):void 0})}),
// Create innerHeight, innerWidth, height, width, outerHeight and outerWidth methods
fa.each({Height:"height",Width:"width"},function(a,b){fa.each({padding:"inner"+a,content:b,"":"outer"+a},function(c,d){
// Margin is only for outerHeight, outerWidth
fa.fn[d]=function(d,e){var f=arguments.length&&(c||"boolean"!=typeof d),g=c||(d===!0||e===!0?"margin":"border");return xa(this,function(b,c,d){var e;
// Get document width or height
// Get width or height on the element, requesting but not forcing parseFloat
// Set width or height on the element
return fa.isWindow(b)?b.document.documentElement["client"+a]:9===b.nodeType?(e=b.documentElement,Math.max(b.body["scroll"+a],e["scroll"+a],b.body["offset"+a],e["offset"+a],e["client"+a])):void 0===d?fa.css(b,c,g):fa.style(b,c,d,g)},b,f?d:void 0,f,null)}})}),fa.fn.extend({bind:function(a,b,c){return this.on(a,null,b,c)},unbind:function(a,b){return this.off(a,null,b)},delegate:function(a,b,c,d){return this.on(b,a,c,d)},undelegate:function(a,b,c){
// ( namespace ) or ( selector, types [, fn] )
return 1===arguments.length?this.off(a,"**"):this.off(b,a||"**",c)},size:function(){return this.length}}),fa.fn.andSelf=fa.fn.addBack,
// Register as a named AMD module, since jQuery can be concatenated with other
// files that may use define, but not via a proper concatenation script that
// understands anonymous AMD modules. A named AMD is safest and most robust
// way to register. Lowercase jquery is used because AMD module names are
// derived from file names, and jQuery is normally delivered in a lowercase
// file name. Do this after creating the global so that if an AMD module wants
// to call noConflict to hide this version of jQuery, it will work.
// Note that for maximum portability, libraries that are not jQuery should
// declare themselves as anonymous modules, and avoid setting a global if an
// AMD loader is present. jQuery is a special case. For more information, see
// https://github.com/jrburke/requirejs/wiki/Updating-existing-libraries#wiki-anon
"function"==typeof define&&define.amd&&define("jquery",[],function(){return fa});var
// Map over jQuery in case of overwrite
Mb=a.jQuery,
// Map over the $ in case of overwrite
Nb=a.$;
// Expose jQuery and $ identifiers, even in AMD
// (#7102#comment:10, https://github.com/jquery/jquery/pull/557)
// and CommonJS for browser emulators (#13566)
return fa.noConflict=function(b){return a.$===fa&&(a.$=Nb),b&&a.jQuery===fa&&(a.jQuery=Mb),fa},b||(a.jQuery=a.$=fa),fa});

/*!
 * jQuery Cookie Plugin v1.4.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
!function(a){"function"==typeof define&&define.amd?
// AMD
define(["jquery"],a):a("object"==typeof exports?require("jquery"):jQuery)}(function(a){function b(a){return h.raw?a:encodeURIComponent(a)}function c(a){return h.raw?a:decodeURIComponent(a)}function d(a){return b(h.json?JSON.stringify(a):String(a))}function e(a){0===a.indexOf('"')&&(
// This is a quoted cookie as according to RFC2068, unescape...
a=a.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{
// Replace server-side written pluses with spaces.
// If we can't decode the cookie, ignore it, it's unusable.
// If we can't parse the cookie, ignore it, it's unusable.
return a=decodeURIComponent(a.replace(g," ")),h.json?JSON.parse(a):a}catch(b){}}function f(b,c){var d=h.raw?b:e(b);return a.isFunction(c)?c(d):d}var g=/\+/g,h=a.cookie=function(e,g,i){
// Write
if(void 0!==g&&!a.isFunction(g)){if(i=a.extend({},h.defaults,i),"number"==typeof i.expires){var j=i.expires,k=i.expires=new Date;k.setTime(+k+864e5*j)}return document.cookie=[b(e),"=",d(g),i.expires?"; expires="+i.expires.toUTCString():"",i.path?"; path="+i.path:"",i.domain?"; domain="+i.domain:"",i.secure?"; secure":""].join("")}for(var l=e?void 0:{},m=document.cookie?document.cookie.split("; "):[],n=0,o=m.length;o>n;n++){var p=m[n].split("="),q=c(p.shift()),r=p.join("=");if(e&&e===q){
// If second argument (value) is a function it's a converter...
l=f(r,g);break}
// Prevent storing a cookie that we couldn't decode.
e||void 0===(r=f(r))||(l[q]=r)}return l};h.defaults={},a.removeCookie=function(b,c){
// Must not alter options, thus extending a fresh object...
return void 0===a.cookie(b)?!1:(a.cookie(b,"",a.extend({},c,{expires:-1})),!a.cookie(b))}});

/*
 * jQuery formatDateTime Plugin
 *
 * Copyright 2012 Adam Gschwender
 * Dual licensed under the MIT or GPL Version 2 licenses.
 */
!function(a){"object"==typeof exports?
// Node/CommonJS style for Browserify
module.exports=a:"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery"],a):
// Browser globals: jQuery or jQuery-like library, such as Zepto
a(window.jQuery||window.$)}(function(a){var b=24*(718685+Math.floor(492.5)-Math.floor(19.7)+Math.floor(4.925))*60*60*1e7,c=function(c,d,e){var f="",g=!1,h=0,i=function(a){var b=h+1<c.length&&c.charAt(h+1)==a;return b&&h++,b},j=function(a,b,c){var d=""+b;if(i(a))for(;d.length<c;)d="0"+d;return d},k=function(a,b,c,d){return i(a)?d[b]:c[b]},l=function(a){var b=e.utc;switch(a){case"y":return b?d.getUTCFullYear():d.getFullYear();case"m":return(b?d.getUTCMonth():d.getMonth())+1;case"M":return b?d.getUTCMonth():d.getMonth();case"d":return b?d.getUTCDate():d.getDate();case"D":return b?d.getUTCDay():d.getDay();case"g":return(b?d.getUTCHours():d.getHours())%12||12;case"h":return b?d.getUTCHours():d.getHours();case"i":return b?d.getUTCMinutes():d.getMinutes();case"s":return b?d.getUTCSeconds():d.getSeconds();case"u":return b?d.getUTCMilliseconds():d.getMilliseconds();default:return""}};for(h=0;h<c.length;h++)if(g)"'"!=c.charAt(h)||i("'")?f+=c.charAt(h):g=!1;else switch(c.charAt(h)){case"a":f+=l("h")<12?e.ampmNames[0]:e.ampmNames[1];break;case"d":f+=j("d",l("d"),2);break;case"S":var m=l(h&&c.charAt(h-1));f+=m&&(e.getSuffix||a.noop)(m)||"";break;case"D":f+=k("D",l("D"),e.dayNamesShort,e.dayNames);break;case"o":var n=new Date(d.getFullYear(),d.getMonth(),d.getDate()).getTime(),o=new Date(d.getFullYear(),0,0).getTime();f+=j("o",Math.round((n-o)/864e5),3);break;case"g":f+=j("g",l("g"),2);break;case"h":f+=j("h",l("h"),2);break;case"u":f+=j("u",l("u"),3);break;case"i":f+=j("i",l("i"),2);break;case"m":f+=j("m",l("m"),2);break;case"M":f+=k("M",l("M"),e.monthNamesShort,e.monthNames);break;case"s":f+=j("s",l("s"),2);break;case"y":f+=i("y")?l("y"):(""+l("y")).substr(2);break;case"@":f+=d.getTime();break;case"!":f+=1e4*d.getTime()+b;break;case"'":i("'")?f+="'":g=!0;break;default:f+=c.charAt(h)}return f};a.fn.formatDateTime=function(b,d){return d=a.extend({},a.formatDateTime.defaults,d),this.each(function(){var e=a(this).attr(d.attribute),f=b||a(this).attr(d.formatAttribute);"undefined"!=typeof e&&e!==!1||(e=a(this).text()),""===e?a(this).text(""):a(this).text(c(f,new Date(e),d))}),this},/**
       Format a date object into a string value.
       The format can be combinations of the following:
       a - Ante meridiem and post meridiem
       d  - day of month (no leading zero)
       dd - day of month (two digit)
       o  - day of year (no leading zeros)
       oo - day of year (three digit)
       D  - day name short
       DD - day name long
       g  - 12-hour hour format of day (no leading zero)
       gg - 12-hour hour format of day (two digit)
       h  - 24-hour hour format of day (no leading zero)
       hh - 24-hour hour format of day (two digit)
       u  - millisecond of second (no leading zeros)
       uu - millisecond of second (three digit)
       i  - minute of hour (no leading zero)
       ii - minute of hour (two digit)
       m  - month of year (no leading zero)
       mm - month of year (two digit)
       M  - month name short
       MM - month name long
       S  - ordinal suffix for the previous unit
       s  - second of minute (no leading zero)
       ss - second of minute (two digit)
       y  - year (two digit)
       yy - year (four digit)
       @  - Unix timestamp (ms since 01/01/1970)
       !  - Windows ticks (100ns since 01/01/0001)
       '...' - literal text
       '' - single quote

       @param  format    string - the desired format of the date
       @param  date      Date - the date value to format
       @param  settings  Object - attributes include:
           ampmNames        string[2] - am/pm (optional)
           dayNamesShort    string[7] - abbreviated names of the days
                                        from Sunday (optional)
           dayNames         string[7] - names of the days from Sunday (optional)
           monthNamesShort  string[12] - abbreviated names of the months
                                         (optional)
           monthNames       string[12] - names of the months (optional)
           getSuffix        function(num) - accepts a number and returns
                                            its suffix
           attribute        string - Attribute which stores datetime, defaults
                                     to data-datetime, only valid when called
                                     on dom element(s). If not present,
                                     uses text.
           formatAttribute  string - Attribute which stores the format, defaults
                                     to data-dateformat.
           utc              bool - render dates using UTC instead of local time
       @return  string - the date in the above format
    */
a.formatDateTime=function(b,d,e){return e=a.extend({},a.formatDateTime.defaults,e),d?c(b,d,e):""},a.formatDateTime.defaults={monthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],dayNames:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],dayNamesShort:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],ampmNames:["AM","PM"],getSuffix:function(a){if(a>3&&21>a)return"th";switch(a%10){case 1:return"st";case 2:return"nd";case 3:return"rd";default:return"th"}},attribute:"data-datetime",formatAttribute:"data-dateformat",utc:!1}});

/*!
 * jquery.tagcloud.js
 * A Simple Tag Cloud Plugin for JQuery
 *
 * https://github.com/addywaddy/jquery.tagcloud.js
 * created by Adam Groves
 */
!function(a){/*global jQuery*/
"use strict";var b=function(a,b){return a-b},c=function(a){4===a.length&&(a=a.replace(/(\w)(\w)(\w)/gi,"$1$1$2$2$3$3"));var b=/(\w{2})(\w{2})(\w{2})/.exec(a);return[parseInt(b[1],16),parseInt(b[2],16),parseInt(b[3],16)]},d=function(a){return"#"+jQuery.map(a,function(a){var b=a.toString(16);return b=1===b.length?"0"+b:b}).join("")},e=function(a,b){return jQuery.map(c(a.end),function(d,e){return(d-c(a.start)[e])/b})},f=function(a,b,e){var f=jQuery.map(c(a.start),function(a,c){var d=Math.round(a+b[c]*e);return d>255?d=255:0>d&&(d=0),d});return d(f)};a.fn.tagcloud=function(c){var d=a.extend({},a.fn.tagcloud.defaults,c),g=this.map(function(){return a(this).attr("rel")});g=jQuery.makeArray(g).sort(b);var h=g[0],i=g.pop(),j=i-h;0===j&&(j=1);
// Sizes
var k,l;
// Colors
return d.size&&(k=(d.size.end-d.size.start)/j),d.color&&(l=e(d.color,j)),this.each(function(){var b=a(this).attr("rel")-h;d.size&&a(this).css({"font-size":d.size.start+b*k+d.size.unit}),d.color&&a(this).css({color:f(d.color,l,b)})})},a.fn.tagcloud.defaults={size:{start:14,end:18,unit:"pt"}}}(jQuery);

!function(a){a.fn.extend({complexify:function(b,c){function h(a,b){for(var c=a.length-1;c>=0;c--)if(b[0]<=a.charCodeAt(c)&&a.charCodeAt(c)<=b[1])return b[1]-b[0]+1;return 0}function i(c){if("strict"===b.banMode){for(var d=0;d<b.bannedPasswords.length;d++)if(-1!==c.toLowerCase().indexOf(b.bannedPasswords[d].toLowerCase()))return!0;return!1}return a.inArray(c,b.bannedPasswords)>-1?!0:!1}function j(){var g=a(this).val(),j=0,k=!1;if(i(g))j=1;else for(var l=f.length-1;l>=0;l--)j+=h(g,f[l]);j=Math.log(Math.pow(j,g.length))*(1/b.strengthScaleFactor),k=j>d&&g.length>=b.minimumChars,j=100*(j/e),j=j>100?100:j,c.call(this,k,j)}var d=49,e=120,f=[[32,32],[48,57],[65,90],[97,122],[33,47],[58,64],[91,96],[123,126],[128,255],[256,383],[384,591],[592,687],[688,767],[768,879],[880,1023],[1024,1279],[1328,1423],[1424,1535],[1536,1791],[1792,1871],[1920,1983],[2304,2431],[2432,2559],[2560,2687],[2688,2815],[2816,2943],[2944,3071],[3072,3199],[3200,3327],[3328,3455],[3456,3583],[3584,3711],[3712,3839],[3840,4095],[4096,4255],[4256,4351],[4352,4607],[4608,4991],[5024,5119],[5120,5759],[5760,5791],[5792,5887],[6016,6143],[6144,6319],[7680,7935],[7936,8191],[8192,8303],[8304,8351],[8352,8399],[8400,8447],[8448,8527],[8528,8591],[8592,8703],[8704,8959],[8960,9215],[9216,9279],[9280,9311],[9312,9471],[9472,9599],[9600,9631],[9632,9727],[9728,9983],[9984,10175],[10240,10495],[11904,12031],[12032,12255],[12272,12287],[12288,12351],[12352,12447],[12448,12543],[12544,12591],[12592,12687],[12688,12703],[12704,12735],[12800,13055],[13056,13311],[13312,19893],[19968,40959],[40960,42127],[42128,42191],[44032,55203],[55296,56191],[56192,56319],[56320,57343],[57344,63743],[63744,64255],[64256,64335],[64336,65023],[65056,65071],[65072,65103],[65104,65135],[65136,65278],[65279,65279],[65280,65519],[65520,65533]],g={minimumChars:8,strengthScaleFactor:1,bannedPasswords:window.COMPLEXIFY_BANLIST||[],banMode:"strict"};return a.isFunction(b)&&!c&&(c=b,b={}),b=a.extend(g,b),this.each(function(){a(this).val()&&j.apply(this)}),this.each(function(){a(this).bind("keyup focus input propertychange mouseup",j)})}})}(jQuery);

//     Underscore.js 1.8.3
//     http://underscorejs.org
//     (c) 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
//     Underscore may be freely distributed under the MIT license.
(function(){
// Create a reducing function iterating left or right.
function a(a){
// Optimized iterator function as using arguments.length
// in the main function will deoptimize the, see #1991.
function b(b,c,d,e,f,g){for(;f>=0&&g>f;f+=a){var h=e?e[f]:f;d=c(d,b[h],h,b)}return d}return function(c,d,e,f){d=t(d,f,4);var g=!A(c)&&s.keys(c),h=(g||c).length,i=a>0?0:h-1;
// Determine the initial value if none is provided.
return arguments.length<3&&(e=c[g?g[i]:i],i+=a),b(c,d,e,g,i,h)}}
// Generator function to create the findIndex and findLastIndex functions
function b(a){return function(b,c,d){c=u(c,d);for(var e=z(b),f=a>0?0:e-1;f>=0&&e>f;f+=a)if(c(b[f],f,b))return f;return-1}}
// Generator function to create the indexOf and lastIndexOf functions
function c(a,b,c){return function(d,e,f){var g=0,h=z(d);if("number"==typeof f)a>0?g=f>=0?f:Math.max(f+h,g):h=f>=0?Math.min(f+1,h):f+h+1;else if(c&&f&&h)return f=c(d,e),d[f]===e?f:-1;if(e!==e)return f=b(k.call(d,g,h),s.isNaN),f>=0?f+g:-1;for(f=a>0?g:h-1;f>=0&&h>f;f+=a)if(d[f]===e)return f;return-1}}function d(a,b){var c=F.length,d=a.constructor,e=s.isFunction(d)&&d.prototype||h,f="constructor";for(s.has(a,f)&&!s.contains(b,f)&&b.push(f);c--;)f=F[c],f in a&&a[f]!==e[f]&&!s.contains(b,f)&&b.push(f)}
// Baseline setup
// --------------
// Establish the root object, `window` in the browser, or `exports` on the server.
var e=this,f=e._,g=Array.prototype,h=Object.prototype,i=Function.prototype,j=g.push,k=g.slice,l=h.toString,m=h.hasOwnProperty,n=Array.isArray,o=Object.keys,p=i.bind,q=Object.create,r=function(){},s=function(a){return a instanceof s?a:this instanceof s?void(this._wrapped=a):new s(a)};
// Export the Underscore object for **Node.js**, with
// backwards-compatibility for the old `require()` API. If we're in
// the browser, add `_` as a global object.
"undefined"!=typeof exports?("undefined"!=typeof module&&module.exports&&(exports=module.exports=s),exports._=s):e._=s,
// Current version.
s.VERSION="1.8.3";
// Internal function that returns an efficient (for current engines) version
// of the passed-in callback, to be repeatedly applied in other Underscore
// functions.
var t=function(a,b,c){if(void 0===b)return a;switch(null==c?3:c){case 1:return function(c){return a.call(b,c)};case 2:return function(c,d){return a.call(b,c,d)};case 3:return function(c,d,e){return a.call(b,c,d,e)};case 4:return function(c,d,e,f){return a.call(b,c,d,e,f)}}return function(){return a.apply(b,arguments)}},u=function(a,b,c){return null==a?s.identity:s.isFunction(a)?t(a,b,c):s.isObject(a)?s.matcher(a):s.property(a)};s.iteratee=function(a,b){return u(a,b,1/0)};
// An internal function for creating assigner functions.
var v=function(a,b){return function(c){var d=arguments.length;if(2>d||null==c)return c;for(var e=1;d>e;e++)for(var f=arguments[e],g=a(f),h=g.length,i=0;h>i;i++){var j=g[i];b&&void 0!==c[j]||(c[j]=f[j])}return c}},w=function(a){if(!s.isObject(a))return{};if(q)return q(a);r.prototype=a;var b=new r;return r.prototype=null,b},x=function(a){return function(b){return null==b?void 0:b[a]}},y=Math.pow(2,53)-1,z=x("length"),A=function(a){var b=z(a);return"number"==typeof b&&b>=0&&y>=b};
// Collection Functions
// --------------------
// The cornerstone, an `each` implementation, aka `forEach`.
// Handles raw objects in addition to array-likes. Treats all
// sparse array-likes as if they were dense.
s.each=s.forEach=function(a,b,c){b=t(b,c);var d,e;if(A(a))for(d=0,e=a.length;e>d;d++)b(a[d],d,a);else{var f=s.keys(a);for(d=0,e=f.length;e>d;d++)b(a[f[d]],f[d],a)}return a},
// Return the results of applying the iteratee to each element.
s.map=s.collect=function(a,b,c){b=u(b,c);for(var d=!A(a)&&s.keys(a),e=(d||a).length,f=Array(e),g=0;e>g;g++){var h=d?d[g]:g;f[g]=b(a[h],h,a)}return f},
// **Reduce** builds up a single result from a list of values, aka `inject`,
// or `foldl`.
s.reduce=s.foldl=s.inject=a(1),
// The right-associative version of reduce, also known as `foldr`.
s.reduceRight=s.foldr=a(-1),
// Return the first value which passes a truth test. Aliased as `detect`.
s.find=s.detect=function(a,b,c){var d;return d=A(a)?s.findIndex(a,b,c):s.findKey(a,b,c),void 0!==d&&-1!==d?a[d]:void 0},
// Return all the elements that pass a truth test.
// Aliased as `select`.
s.filter=s.select=function(a,b,c){var d=[];return b=u(b,c),s.each(a,function(a,c,e){b(a,c,e)&&d.push(a)}),d},
// Return all the elements for which a truth test fails.
s.reject=function(a,b,c){return s.filter(a,s.negate(u(b)),c)},
// Determine whether all of the elements match a truth test.
// Aliased as `all`.
s.every=s.all=function(a,b,c){b=u(b,c);for(var d=!A(a)&&s.keys(a),e=(d||a).length,f=0;e>f;f++){var g=d?d[f]:f;if(!b(a[g],g,a))return!1}return!0},
// Determine if at least one element in the object matches a truth test.
// Aliased as `any`.
s.some=s.any=function(a,b,c){b=u(b,c);for(var d=!A(a)&&s.keys(a),e=(d||a).length,f=0;e>f;f++){var g=d?d[f]:f;if(b(a[g],g,a))return!0}return!1},
// Determine if the array or object contains a given item (using `===`).
// Aliased as `includes` and `include`.
s.contains=s.includes=s.include=function(a,b,c,d){return A(a)||(a=s.values(a)),("number"!=typeof c||d)&&(c=0),s.indexOf(a,b,c)>=0},
// Invoke a method (with arguments) on every item in a collection.
s.invoke=function(a,b){var c=k.call(arguments,2),d=s.isFunction(b);return s.map(a,function(a){var e=d?b:a[b];return null==e?e:e.apply(a,c)})},
// Convenience version of a common use case of `map`: fetching a property.
s.pluck=function(a,b){return s.map(a,s.property(b))},
// Convenience version of a common use case of `filter`: selecting only objects
// containing specific `key:value` pairs.
s.where=function(a,b){return s.filter(a,s.matcher(b))},
// Convenience version of a common use case of `find`: getting the first object
// containing specific `key:value` pairs.
s.findWhere=function(a,b){return s.find(a,s.matcher(b))},
// Return the maximum element (or element-based computation).
s.max=function(a,b,c){var d,e,f=-(1/0),g=-(1/0);if(null==b&&null!=a){a=A(a)?a:s.values(a);for(var h=0,i=a.length;i>h;h++)d=a[h],d>f&&(f=d)}else b=u(b,c),s.each(a,function(a,c,d){e=b(a,c,d),(e>g||e===-(1/0)&&f===-(1/0))&&(f=a,g=e)});return f},
// Return the minimum element (or element-based computation).
s.min=function(a,b,c){var d,e,f=1/0,g=1/0;if(null==b&&null!=a){a=A(a)?a:s.values(a);for(var h=0,i=a.length;i>h;h++)d=a[h],f>d&&(f=d)}else b=u(b,c),s.each(a,function(a,c,d){e=b(a,c,d),(g>e||e===1/0&&f===1/0)&&(f=a,g=e)});return f},
// Shuffle a collection, using the modern version of the
// [Fisher-Yates shuffle](http://en.wikipedia.org/wiki/Fisher–Yates_shuffle).
s.shuffle=function(a){for(var b,c=A(a)?a:s.values(a),d=c.length,e=Array(d),f=0;d>f;f++)b=s.random(0,f),b!==f&&(e[f]=e[b]),e[b]=c[f];return e},
// Sample **n** random values from a collection.
// If **n** is not specified, returns a single random element.
// The internal `guard` argument allows it to work with `map`.
s.sample=function(a,b,c){return null==b||c?(A(a)||(a=s.values(a)),a[s.random(a.length-1)]):s.shuffle(a).slice(0,Math.max(0,b))},
// Sort the object's values by a criterion produced by an iteratee.
s.sortBy=function(a,b,c){return b=u(b,c),s.pluck(s.map(a,function(a,c,d){return{value:a,index:c,criteria:b(a,c,d)}}).sort(function(a,b){var c=a.criteria,d=b.criteria;if(c!==d){if(c>d||void 0===c)return 1;if(d>c||void 0===d)return-1}return a.index-b.index}),"value")};
// An internal function used for aggregate "group by" operations.
var B=function(a){return function(b,c,d){var e={};return c=u(c,d),s.each(b,function(d,f){var g=c(d,f,b);a(e,d,g)}),e}};
// Groups the object's values by a criterion. Pass either a string attribute
// to group by, or a function that returns the criterion.
s.groupBy=B(function(a,b,c){s.has(a,c)?a[c].push(b):a[c]=[b]}),
// Indexes the object's values by a criterion, similar to `groupBy`, but for
// when you know that your index values will be unique.
s.indexBy=B(function(a,b,c){a[c]=b}),
// Counts instances of an object that group by a certain criterion. Pass
// either a string attribute to count by, or a function that returns the
// criterion.
s.countBy=B(function(a,b,c){s.has(a,c)?a[c]++:a[c]=1}),
// Safely create a real, live array from anything iterable.
s.toArray=function(a){return a?s.isArray(a)?k.call(a):A(a)?s.map(a,s.identity):s.values(a):[]},
// Return the number of elements in an object.
s.size=function(a){return null==a?0:A(a)?a.length:s.keys(a).length},
// Split a collection into two arrays: one whose elements all satisfy the given
// predicate, and one whose elements all do not satisfy the predicate.
s.partition=function(a,b,c){b=u(b,c);var d=[],e=[];return s.each(a,function(a,c,f){(b(a,c,f)?d:e).push(a)}),[d,e]},
// Array Functions
// ---------------
// Get the first element of an array. Passing **n** will return the first N
// values in the array. Aliased as `head` and `take`. The **guard** check
// allows it to work with `_.map`.
s.first=s.head=s.take=function(a,b,c){return null!=a?null==b||c?a[0]:s.initial(a,a.length-b):void 0},
// Returns everything but the last entry of the array. Especially useful on
// the arguments object. Passing **n** will return all the values in
// the array, excluding the last N.
s.initial=function(a,b,c){return k.call(a,0,Math.max(0,a.length-(null==b||c?1:b)))},
// Get the last element of an array. Passing **n** will return the last N
// values in the array.
s.last=function(a,b,c){return null!=a?null==b||c?a[a.length-1]:s.rest(a,Math.max(0,a.length-b)):void 0},
// Returns everything but the first entry of the array. Aliased as `tail` and `drop`.
// Especially useful on the arguments object. Passing an **n** will return
// the rest N values in the array.
s.rest=s.tail=s.drop=function(a,b,c){return k.call(a,null==b||c?1:b)},
// Trim out all falsy values from an array.
s.compact=function(a){return s.filter(a,s.identity)};
// Internal implementation of a recursive `flatten` function.
var C=function(a,b,c,d){for(var e=[],f=0,g=d||0,h=z(a);h>g;g++){var i=a[g];if(A(i)&&(s.isArray(i)||s.isArguments(i))){
//flatten current level of array or arguments object
b||(i=C(i,b,c));var j=0,k=i.length;for(e.length+=k;k>j;)e[f++]=i[j++]}else c||(e[f++]=i)}return e};
// Flatten out an array, either recursively (by default), or just one level.
s.flatten=function(a,b){return C(a,b,!1)},
// Return a version of the array that does not contain the specified value(s).
s.without=function(a){return s.difference(a,k.call(arguments,1))},
// Produce a duplicate-free version of the array. If the array has already
// been sorted, you have the option of using a faster algorithm.
// Aliased as `unique`.
s.uniq=s.unique=function(a,b,c,d){s.isBoolean(b)||(d=c,c=b,b=!1),null!=c&&(c=u(c,d));for(var e=[],f=[],g=0,h=z(a);h>g;g++){var i=a[g],j=c?c(i,g,a):i;b?(g&&f===j||e.push(i),f=j):c?s.contains(f,j)||(f.push(j),e.push(i)):s.contains(e,i)||e.push(i)}return e},
// Produce an array that contains the union: each distinct element from all of
// the passed-in arrays.
s.union=function(){return s.uniq(C(arguments,!0,!0))},
// Produce an array that contains every item shared between all the
// passed-in arrays.
s.intersection=function(a){for(var b=[],c=arguments.length,d=0,e=z(a);e>d;d++){var f=a[d];if(!s.contains(b,f)){for(var g=1;c>g&&s.contains(arguments[g],f);g++);g===c&&b.push(f)}}return b},
// Take the difference between one array and a number of other arrays.
// Only the elements present in just the first array will remain.
s.difference=function(a){var b=C(arguments,!0,!0,1);return s.filter(a,function(a){return!s.contains(b,a)})},
// Zip together multiple lists into a single array -- elements that share
// an index go together.
s.zip=function(){return s.unzip(arguments)},
// Complement of _.zip. Unzip accepts an array of arrays and groups
// each array's elements on shared indices
s.unzip=function(a){for(var b=a&&s.max(a,z).length||0,c=Array(b),d=0;b>d;d++)c[d]=s.pluck(a,d);return c},
// Converts lists into objects. Pass either a single array of `[key, value]`
// pairs, or two parallel arrays of the same length -- one of keys, and one of
// the corresponding values.
s.object=function(a,b){for(var c={},d=0,e=z(a);e>d;d++)b?c[a[d]]=b[d]:c[a[d][0]]=a[d][1];return c},
// Returns the first index on an array-like that passes a predicate test
s.findIndex=b(1),s.findLastIndex=b(-1),
// Use a comparator function to figure out the smallest index at which
// an object should be inserted so as to maintain order. Uses binary search.
s.sortedIndex=function(a,b,c,d){c=u(c,d,1);for(var e=c(b),f=0,g=z(a);g>f;){var h=Math.floor((f+g)/2);c(a[h])<e?f=h+1:g=h}return f},
// Return the position of the first occurrence of an item in an array,
// or -1 if the item is not included in the array.
// If the array is large and already in sort order, pass `true`
// for **isSorted** to use binary search.
s.indexOf=c(1,s.findIndex,s.sortedIndex),s.lastIndexOf=c(-1,s.findLastIndex),
// Generate an integer Array containing an arithmetic progression. A port of
// the native Python `range()` function. See
// [the Python documentation](http://docs.python.org/library/functions.html#range).
s.range=function(a,b,c){null==b&&(b=a||0,a=0),c=c||1;for(var d=Math.max(Math.ceil((b-a)/c),0),e=Array(d),f=0;d>f;f++,a+=c)e[f]=a;return e};
// Function (ahem) Functions
// ------------------
// Determines whether to execute a function as a constructor
// or a normal function with the provided arguments
var D=function(a,b,c,d,e){if(!(d instanceof b))return a.apply(c,e);var f=w(a.prototype),g=a.apply(f,e);return s.isObject(g)?g:f};
// Create a function bound to a given object (assigning `this`, and arguments,
// optionally). Delegates to **ECMAScript 5**'s native `Function.bind` if
// available.
s.bind=function(a,b){if(p&&a.bind===p)return p.apply(a,k.call(arguments,1));if(!s.isFunction(a))throw new TypeError("Bind must be called on a function");var c=k.call(arguments,2),d=function(){return D(a,d,b,this,c.concat(k.call(arguments)))};return d},
// Partially apply a function by creating a version that has had some of its
// arguments pre-filled, without changing its dynamic `this` context. _ acts
// as a placeholder, allowing any combination of arguments to be pre-filled.
s.partial=function(a){var b=k.call(arguments,1),c=function(){for(var d=0,e=b.length,f=Array(e),g=0;e>g;g++)f[g]=b[g]===s?arguments[d++]:b[g];for(;d<arguments.length;)f.push(arguments[d++]);return D(a,c,this,this,f)};return c},
// Bind a number of an object's methods to that object. Remaining arguments
// are the method names to be bound. Useful for ensuring that all callbacks
// defined on an object belong to it.
s.bindAll=function(a){var b,c,d=arguments.length;if(1>=d)throw new Error("bindAll must be passed function names");for(b=1;d>b;b++)c=arguments[b],a[c]=s.bind(a[c],a);return a},
// Memoize an expensive function by storing its results.
s.memoize=function(a,b){var c=function(d){var e=c.cache,f=""+(b?b.apply(this,arguments):d);return s.has(e,f)||(e[f]=a.apply(this,arguments)),e[f]};return c.cache={},c},
// Delays a function for the given number of milliseconds, and then calls
// it with the arguments supplied.
s.delay=function(a,b){var c=k.call(arguments,2);return setTimeout(function(){return a.apply(null,c)},b)},
// Defers a function, scheduling it to run after the current call stack has
// cleared.
s.defer=s.partial(s.delay,s,1),
// Returns a function, that, when invoked, will only be triggered at most once
// during a given window of time. Normally, the throttled function will run
// as much as it can, without ever going more than once per `wait` duration;
// but if you'd like to disable the execution on the leading edge, pass
// `{leading: false}`. To disable execution on the trailing edge, ditto.
s.throttle=function(a,b,c){var d,e,f,g=null,h=0;c||(c={});var i=function(){h=c.leading===!1?0:s.now(),g=null,f=a.apply(d,e),g||(d=e=null)};return function(){var j=s.now();h||c.leading!==!1||(h=j);var k=b-(j-h);return d=this,e=arguments,0>=k||k>b?(g&&(clearTimeout(g),g=null),h=j,f=a.apply(d,e),g||(d=e=null)):g||c.trailing===!1||(g=setTimeout(i,k)),f}},
// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
s.debounce=function(a,b,c){var d,e,f,g,h,i=function(){var j=s.now()-g;b>j&&j>=0?d=setTimeout(i,b-j):(d=null,c||(h=a.apply(f,e),d||(f=e=null)))};return function(){f=this,e=arguments,g=s.now();var j=c&&!d;return d||(d=setTimeout(i,b)),j&&(h=a.apply(f,e),f=e=null),h}},
// Returns the first function passed as an argument to the second,
// allowing you to adjust arguments, run code before and after, and
// conditionally execute the original function.
s.wrap=function(a,b){return s.partial(b,a)},
// Returns a negated version of the passed-in predicate.
s.negate=function(a){return function(){return!a.apply(this,arguments)}},
// Returns a function that is the composition of a list of functions, each
// consuming the return value of the function that follows.
s.compose=function(){var a=arguments,b=a.length-1;return function(){for(var c=b,d=a[b].apply(this,arguments);c--;)d=a[c].call(this,d);return d}},
// Returns a function that will only be executed on and after the Nth call.
s.after=function(a,b){return function(){return--a<1?b.apply(this,arguments):void 0}},
// Returns a function that will only be executed up to (but not including) the Nth call.
s.before=function(a,b){var c;return function(){return--a>0&&(c=b.apply(this,arguments)),1>=a&&(b=null),c}},
// Returns a function that will be executed at most one time, no matter how
// often you call it. Useful for lazy initialization.
s.once=s.partial(s.before,2);
// Object Functions
// ----------------
// Keys in IE < 9 that won't be iterated by `for key in ...` and thus missed.
var E=!{toString:null}.propertyIsEnumerable("toString"),F=["valueOf","isPrototypeOf","toString","propertyIsEnumerable","hasOwnProperty","toLocaleString"];
// Retrieve the names of an object's own properties.
// Delegates to **ECMAScript 5**'s native `Object.keys`
s.keys=function(a){if(!s.isObject(a))return[];if(o)return o(a);var b=[];for(var c in a)s.has(a,c)&&b.push(c);
// Ahem, IE < 9.
return E&&d(a,b),b},
// Retrieve all the property names of an object.
s.allKeys=function(a){if(!s.isObject(a))return[];var b=[];for(var c in a)b.push(c);
// Ahem, IE < 9.
return E&&d(a,b),b},
// Retrieve the values of an object's properties.
s.values=function(a){for(var b=s.keys(a),c=b.length,d=Array(c),e=0;c>e;e++)d[e]=a[b[e]];return d},
// Returns the results of applying the iteratee to each element of the object
// In contrast to _.map it returns an object
s.mapObject=function(a,b,c){b=u(b,c);for(var d,e=s.keys(a),f=e.length,g={},h=0;f>h;h++)d=e[h],g[d]=b(a[d],d,a);return g},
// Convert an object into a list of `[key, value]` pairs.
s.pairs=function(a){for(var b=s.keys(a),c=b.length,d=Array(c),e=0;c>e;e++)d[e]=[b[e],a[b[e]]];return d},
// Invert the keys and values of an object. The values must be serializable.
s.invert=function(a){for(var b={},c=s.keys(a),d=0,e=c.length;e>d;d++)b[a[c[d]]]=c[d];return b},
// Return a sorted list of the function names available on the object.
// Aliased as `methods`
s.functions=s.methods=function(a){var b=[];for(var c in a)s.isFunction(a[c])&&b.push(c);return b.sort()},
// Extend a given object with all the properties in passed-in object(s).
s.extend=v(s.allKeys),
// Assigns a given object with all the own properties in the passed-in object(s)
// (https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Object/assign)
s.extendOwn=s.assign=v(s.keys),
// Returns the first key on an object that passes a predicate test
s.findKey=function(a,b,c){b=u(b,c);for(var d,e=s.keys(a),f=0,g=e.length;g>f;f++)if(d=e[f],b(a[d],d,a))return d},
// Return a copy of the object only containing the whitelisted properties.
s.pick=function(a,b,c){var d,e,f={},g=a;if(null==g)return f;s.isFunction(b)?(e=s.allKeys(g),d=t(b,c)):(e=C(arguments,!1,!1,1),d=function(a,b,c){return b in c},g=Object(g));for(var h=0,i=e.length;i>h;h++){var j=e[h],k=g[j];d(k,j,g)&&(f[j]=k)}return f},
// Return a copy of the object without the blacklisted properties.
s.omit=function(a,b,c){if(s.isFunction(b))b=s.negate(b);else{var d=s.map(C(arguments,!1,!1,1),String);b=function(a,b){return!s.contains(d,b)}}return s.pick(a,b,c)},
// Fill in a given object with default properties.
s.defaults=v(s.allKeys,!0),
// Creates an object that inherits from the given prototype object.
// If additional properties are provided then they will be added to the
// created object.
s.create=function(a,b){var c=w(a);return b&&s.extendOwn(c,b),c},
// Create a (shallow-cloned) duplicate of an object.
s.clone=function(a){return s.isObject(a)?s.isArray(a)?a.slice():s.extend({},a):a},
// Invokes interceptor with the obj, and then returns obj.
// The primary purpose of this method is to "tap into" a method chain, in
// order to perform operations on intermediate results within the chain.
s.tap=function(a,b){return b(a),a},
// Returns whether an object has a given set of `key:value` pairs.
s.isMatch=function(a,b){var c=s.keys(b),d=c.length;if(null==a)return!d;for(var e=Object(a),f=0;d>f;f++){var g=c[f];if(b[g]!==e[g]||!(g in e))return!1}return!0};
// Internal recursive comparison function for `isEqual`.
var G=function(a,b,c,d){
// Identical objects are equal. `0 === -0`, but they aren't identical.
// See the [Harmony `egal` proposal](http://wiki.ecmascript.org/doku.php?id=harmony:egal).
if(a===b)return 0!==a||1/a===1/b;
// A strict comparison is necessary because `null == undefined`.
if(null==a||null==b)return a===b;
// Unwrap any wrapped objects.
a instanceof s&&(a=a._wrapped),b instanceof s&&(b=b._wrapped);
// Compare `[[Class]]` names.
var e=l.call(a);if(e!==l.call(b))return!1;switch(e){
// Strings, numbers, regular expressions, dates, and booleans are compared by value.
case"[object RegExp]":
// RegExps are coerced to strings for comparison (Note: '' + /a/i === '/a/i')
case"[object String]":
// Primitives and their corresponding object wrappers are equivalent; thus, `"5"` is
// equivalent to `new String("5")`.
return""+a==""+b;case"[object Number]":
// `NaN`s are equivalent, but non-reflexive.
// Object(NaN) is equivalent to NaN
// `NaN`s are equivalent, but non-reflexive.
// Object(NaN) is equivalent to NaN
return+a!==+a?+b!==+b:0===+a?1/+a===1/b:+a===+b;case"[object Date]":case"[object Boolean]":
// Coerce dates and booleans to numeric primitive values. Dates are compared by their
// millisecond representations. Note that invalid dates with millisecond representations
// of `NaN` are not equivalent.
return+a===+b}var f="[object Array]"===e;if(!f){if("object"!=typeof a||"object"!=typeof b)return!1;
// Objects with different constructors are not equivalent, but `Object`s or `Array`s
// from different frames are.
var g=a.constructor,h=b.constructor;if(g!==h&&!(s.isFunction(g)&&g instanceof g&&s.isFunction(h)&&h instanceof h)&&"constructor"in a&&"constructor"in b)return!1}c=c||[],d=d||[];for(var i=c.length;i--;)
// Linear search. Performance is inversely proportional to the number of
// unique nested structures.
if(c[i]===a)return d[i]===b;
// Recursively compare objects and arrays.
if(
// Add the first object to the stack of traversed objects.
c.push(a),d.push(b),f){if(i=a.length,i!==b.length)return!1;
// Deep compare the contents, ignoring non-numeric properties.
for(;i--;)if(!G(a[i],b[i],c,d))return!1}else{
// Deep compare objects.
var j,k=s.keys(a);
// Ensure that both objects contain the same number of properties before comparing deep equality.
if(i=k.length,s.keys(b).length!==i)return!1;for(;i--;)if(j=k[i],!s.has(b,j)||!G(a[j],b[j],c,d))return!1}
// Remove the first object from the stack of traversed objects.
return c.pop(),d.pop(),!0};
// Perform a deep comparison to check if two objects are equal.
s.isEqual=function(a,b){return G(a,b)},
// Is a given array, string, or object empty?
// An "empty" object has no enumerable own-properties.
s.isEmpty=function(a){return null==a?!0:A(a)&&(s.isArray(a)||s.isString(a)||s.isArguments(a))?0===a.length:0===s.keys(a).length},
// Is a given value a DOM element?
s.isElement=function(a){return!(!a||1!==a.nodeType)},
// Is a given value an array?
// Delegates to ECMA5's native Array.isArray
s.isArray=n||function(a){return"[object Array]"===l.call(a)},
// Is a given variable an object?
s.isObject=function(a){var b=typeof a;return"function"===b||"object"===b&&!!a},
// Add some isType methods: isArguments, isFunction, isString, isNumber, isDate, isRegExp, isError.
s.each(["Arguments","Function","String","Number","Date","RegExp","Error"],function(a){s["is"+a]=function(b){return l.call(b)==="[object "+a+"]"}}),
// Define a fallback version of the method in browsers (ahem, IE < 9), where
// there isn't any inspectable "Arguments" type.
s.isArguments(arguments)||(s.isArguments=function(a){return s.has(a,"callee")}),
// Optimize `isFunction` if appropriate. Work around some typeof bugs in old v8,
// IE 11 (#1621), and in Safari 8 (#1929).
"function"!=typeof/./&&"object"!=typeof Int8Array&&(s.isFunction=function(a){return"function"==typeof a||!1}),
// Is a given object a finite number?
s.isFinite=function(a){return isFinite(a)&&!isNaN(parseFloat(a))},
// Is the given value `NaN`? (NaN is the only number which does not equal itself).
s.isNaN=function(a){return s.isNumber(a)&&a!==+a},
// Is a given value a boolean?
s.isBoolean=function(a){return a===!0||a===!1||"[object Boolean]"===l.call(a)},
// Is a given value equal to null?
s.isNull=function(a){return null===a},
// Is a given variable undefined?
s.isUndefined=function(a){return void 0===a},
// Shortcut function for checking if an object has a given property directly
// on itself (in other words, not on a prototype).
s.has=function(a,b){return null!=a&&m.call(a,b)},
// Utility Functions
// -----------------
// Run Underscore.js in *noConflict* mode, returning the `_` variable to its
// previous owner. Returns a reference to the Underscore object.
s.noConflict=function(){return e._=f,this},
// Keep the identity function around for default iteratees.
s.identity=function(a){return a},
// Predicate-generating functions. Often useful outside of Underscore.
s.constant=function(a){return function(){return a}},s.noop=function(){},s.property=x,
// Generates a function for a given object that returns a given property.
s.propertyOf=function(a){return null==a?function(){}:function(b){return a[b]}},
// Returns a predicate for checking whether an object has a given set of
// `key:value` pairs.
s.matcher=s.matches=function(a){return a=s.extendOwn({},a),function(b){return s.isMatch(b,a)}},
// Run a function **n** times.
s.times=function(a,b,c){var d=Array(Math.max(0,a));b=t(b,c,1);for(var e=0;a>e;e++)d[e]=b(e);return d},
// Return a random integer between min and max (inclusive).
s.random=function(a,b){return null==b&&(b=a,a=0),a+Math.floor(Math.random()*(b-a+1))},
// A (possibly faster) way to get the current timestamp as an integer.
s.now=Date.now||function(){return(new Date).getTime()};
// List of HTML entities for escaping.
var H={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#x27;","`":"&#x60;"},I=s.invert(H),J=function(a){var b=function(b){return a[b]},c="(?:"+s.keys(a).join("|")+")",d=RegExp(c),e=RegExp(c,"g");return function(a){return a=null==a?"":""+a,d.test(a)?a.replace(e,b):a}};s.escape=J(H),s.unescape=J(I),
// If the value of the named `property` is a function then invoke it with the
// `object` as context; otherwise, return it.
s.result=function(a,b,c){var d=null==a?void 0:a[b];return void 0===d&&(d=c),s.isFunction(d)?d.call(a):d};
// Generate a unique integer id (unique within the entire client session).
// Useful for temporary DOM ids.
var K=0;s.uniqueId=function(a){var b=++K+"";return a?a+b:b},
// By default, Underscore uses ERB-style template delimiters, change the
// following template settings to use alternative delimiters.
s.templateSettings={evaluate:/<%([\s\S]+?)%>/g,interpolate:/<%=([\s\S]+?)%>/g,escape:/<%-([\s\S]+?)%>/g};
// When customizing `templateSettings`, if you don't want to define an
// interpolation, evaluation or escaping regex, we need one that is
// guaranteed not to match.
var L=/(.)^/,M={"'":"'","\\":"\\","\r":"r","\n":"n","\u2028":"u2028","\u2029":"u2029"},N=/\\|'|\r|\n|\u2028|\u2029/g,O=function(a){return"\\"+M[a]};
// JavaScript micro-templating, similar to John Resig's implementation.
// Underscore templating handles arbitrary delimiters, preserves whitespace,
// and correctly escapes quotes within interpolated code.
// NB: `oldSettings` only exists for backwards compatibility.
s.template=function(a,b,c){!b&&c&&(b=c),b=s.defaults({},b,s.templateSettings);
// Combine delimiters into one regular expression via alternation.
var d=RegExp([(b.escape||L).source,(b.interpolate||L).source,(b.evaluate||L).source].join("|")+"|$","g"),e=0,f="__p+='";a.replace(d,function(b,c,d,g,h){
// Adobe VMs need the match returned to produce the correct offest.
return f+=a.slice(e,h).replace(N,O),e=h+b.length,c?f+="'+\n((__t=("+c+"))==null?'':_.escape(__t))+\n'":d?f+="'+\n((__t=("+d+"))==null?'':__t)+\n'":g&&(f+="';\n"+g+"\n__p+='"),b}),f+="';\n",b.variable||(f="with(obj||{}){\n"+f+"}\n"),f="var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};\n"+f+"return __p;\n";try{var g=new Function(b.variable||"obj","_",f)}catch(h){throw h.source=f,h}var i=function(a){return g.call(this,a,s)},j=b.variable||"obj";return i.source="function("+j+"){\n"+f+"}",i},
// Add a "chain" function. Start chaining a wrapped Underscore object.
s.chain=function(a){var b=s(a);return b._chain=!0,b};
// OOP
// ---------------
// If Underscore is called as a function, it returns a wrapped object that
// can be used OO-style. This wrapper holds altered versions of all the
// underscore functions. Wrapped objects may be chained.
// Helper function to continue chaining intermediate results.
var P=function(a,b){return a._chain?s(b).chain():b};
// Add your own custom functions to the Underscore object.
s.mixin=function(a){s.each(s.functions(a),function(b){var c=s[b]=a[b];s.prototype[b]=function(){var a=[this._wrapped];return j.apply(a,arguments),P(this,c.apply(s,a))}})},
// Add all of the Underscore functions to the wrapper object.
s.mixin(s),
// Add all mutator Array functions to the wrapper.
s.each(["pop","push","reverse","shift","sort","splice","unshift"],function(a){var b=g[a];s.prototype[a]=function(){var c=this._wrapped;return b.apply(c,arguments),"shift"!==a&&"splice"!==a||0!==c.length||delete c[0],P(this,c)}}),
// Add all accessor Array functions to the wrapper.
s.each(["concat","join","slice"],function(a){var b=g[a];s.prototype[a]=function(){return P(this,b.apply(this._wrapped,arguments))}}),
// Extracts the result from a wrapped and chained object.
s.prototype.value=function(){return this._wrapped},
// Provide unwrapping proxy for some methods used in engine operations
// such as arithmetic and JSON stringification.
s.prototype.valueOf=s.prototype.toJSON=s.prototype.value,s.prototype.toString=function(){return""+this._wrapped},
// AMD registration happens at the end for compatibility with AMD loaders
// that may not enforce next-turn semantics on modules. Even though general
// practice for AMD registration is to be anonymous, underscore registers
// as a named module because, like jQuery, it is a base library that is
// popular enough to be bundled in a third party lib, but not be part of
// an AMD load request. Those cases could generate an error when an
// anonymous define() is called outside of a loader request.
"function"==typeof define&&define.amd&&define("underscore",[],function(){return s})}).call(this);

//     Backbone.js 1.2.3
//     (c) 2010-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
//     Backbone may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://backbonejs.org
!function(a){
// Establish the root object, `window` (`self`) in the browser, or `global` on the server.
// We use `self` instead of `window` for `WebWorker` support.
var b="object"==typeof self&&self.self==self&&self||"object"==typeof global&&global.global==global&&global;
// Set up Backbone appropriately for the environment. Start with AMD.
if("function"==typeof define&&define.amd)define(["underscore","jquery","exports"],function(c,d,e){
// Export global even in AMD case in case this script is loaded with
// others that may still expect a global Backbone.
b.Backbone=a(b,e,c,d)});else if("undefined"!=typeof exports){var c,d=require("underscore");try{c=require("jquery")}catch(e){}a(b,exports,d,c)}else b.Backbone=a(b,{},b._,b.jQuery||b.Zepto||b.ender||b.$)}(function(a,b,c,d){
// Initial Setup
// -------------
// Save the previous value of the `Backbone` variable, so that it can be
// restored later on, if `noConflict` is used.
var e=a.Backbone,f=Array.prototype.slice;
// Current version of the library. Keep in sync with `package.json`.
b.VERSION="1.2.3",
// For Backbone's purposes, jQuery, Zepto, Ender, or My Library (kidding) owns
// the `$` variable.
b.$=d,
// Runs Backbone.js in *noConflict* mode, returning the `Backbone` variable
// to its previous owner. Returns a reference to this Backbone object.
b.noConflict=function(){return a.Backbone=e,this},
// Turn on `emulateHTTP` to support legacy HTTP servers. Setting this option
// will fake `"PATCH"`, `"PUT"` and `"DELETE"` requests via the `_method` parameter and
// set a `X-Http-Method-Override` header.
b.emulateHTTP=!1,
// Turn on `emulateJSON` to support legacy servers that can't deal with direct
// `application/json` requests ... this will encode the body as
// `application/x-www-form-urlencoded` instead and will send the model in a
// form param named `model`.
b.emulateJSON=!1;
// Proxy Backbone class methods to Underscore functions, wrapping the model's
// `attributes` object or collection's `models` array behind the scenes.
//
// collection.filter(function(model) { return model.get('age') > 10 });
// collection.each(this.addView);
//
// `Function#apply` can be slow so we use the method's arg count, if we know it.
var g=function(a,b,d){switch(a){case 1:return function(){return c[b](this[d])};case 2:return function(a){return c[b](this[d],a)};case 3:return function(a,e){return c[b](this[d],i(a,this),e)};case 4:return function(a,e,f){return c[b](this[d],i(a,this),e,f)};default:return function(){var a=f.call(arguments);return a.unshift(this[d]),c[b].apply(c,a)}}},h=function(a,b,d){c.each(b,function(b,e){c[e]&&(a.prototype[e]=g(b,e,d))})},i=function(a,b){return c.isFunction(a)?a:c.isObject(a)&&!b._isModel(a)?j(a):c.isString(a)?function(b){return b.get(a)}:a},j=function(a){var b=c.matches(a);return function(a){return b(a.attributes)}},k=b.Events={},l=/\s+/,m=function(a,b,d,e,f){var g,h=0;if(d&&"object"==typeof d){
// Handle event maps.
void 0!==e&&"context"in f&&void 0===f.context&&(f.context=e);for(g=c.keys(d);h<g.length;h++)b=m(a,b,g[h],d[g[h]],f)}else if(d&&l.test(d))
// Handle space separated event names by delegating them individually.
for(g=d.split(l);h<g.length;h++)b=a(b,g[h],e,f);else
// Finally, standard events.
b=a(b,d,e,f);return b};
// Bind an event to a `callback` function. Passing `"all"` will bind
// the callback to all events fired.
k.on=function(a,b,c){return n(this,a,b,c)};
// Guard the `listening` argument from the public API.
var n=function(a,b,c,d,e){if(a._events=m(o,a._events||{},b,c,{context:d,ctx:a,listening:e}),e){var f=a._listeners||(a._listeners={});f[e.id]=e}return a};
// Inversion-of-control versions of `on`. Tell *this* object to listen to
// an event in another object... keeping track of what it's listening to
// for easier unbinding later.
k.listenTo=function(a,b,d){if(!a)return this;var e=a._listenId||(a._listenId=c.uniqueId("l")),f=this._listeningTo||(this._listeningTo={}),g=f[e];
// This object is not listening to any other events on `obj` yet.
// Setup the necessary references to track the listening callbacks.
if(!g){var h=this._listenId||(this._listenId=c.uniqueId("l"));g=f[e]={obj:a,objId:e,id:h,listeningTo:f,count:0}}
// Bind callbacks on obj, and keep track of them on listening.
return n(a,b,d,this,g),this};
// The reducing API that adds a callback to the `events` object.
var o=function(a,b,c,d){if(c){var e=a[b]||(a[b]=[]),f=d.context,g=d.ctx,h=d.listening;h&&h.count++,e.push({callback:c,context:f,ctx:f||g,listening:h})}return a};
// Remove one or many callbacks. If `context` is null, removes all
// callbacks with that function. If `callback` is null, removes all
// callbacks for the event. If `name` is null, removes all bound
// callbacks for all events.
k.off=function(a,b,c){return this._events?(this._events=m(p,this._events,a,b,{context:c,listeners:this._listeners}),this):this},
// Tell this object to stop listening to either specific events ... or
// to every object it's currently listening to.
k.stopListening=function(a,b,d){var e=this._listeningTo;if(!e)return this;for(var f=a?[a._listenId]:c.keys(e),g=0;g<f.length;g++){var h=e[f[g]];
// If listening doesn't exist, this object is not currently
// listening to obj. Break out early.
if(!h)break;h.obj.off(b,d,this)}return c.isEmpty(e)&&(this._listeningTo=void 0),this};
// The reducing API that removes a callback from the `events` object.
var p=function(a,b,d,e){if(a){var f,g=0,h=e.context,i=e.listeners;
// Delete all events listeners and "drop" events.
if(b||d||h){for(var j=b?[b]:c.keys(a);g<j.length;g++){b=j[g];var k=a[b];
// Bail out if there are no events stored.
if(!k)break;for(var l=[],m=0;m<k.length;m++){var n=k[m];d&&d!==n.callback&&d!==n.callback._callback||h&&h!==n.context?l.push(n):(f=n.listening,f&&0===--f.count&&(delete i[f.id],delete f.listeningTo[f.objId]))}
// Update tail event if the list has any events.  Otherwise, clean up.
l.length?a[b]=l:delete a[b]}return c.size(a)?a:void 0}for(var o=c.keys(i);g<o.length;g++)f=i[o[g]],delete i[f.id],delete f.listeningTo[f.objId]}};
// Bind an event to only be triggered a single time. After the first time
// the callback is invoked, its listener will be removed. If multiple events
// are passed in using the space-separated syntax, the handler will fire
// once for each event, not once for a combination of all events.
k.once=function(a,b,d){
// Map the event into a `{event: once}` object.
var e=m(q,{},a,b,c.bind(this.off,this));return this.on(e,void 0,d)},
// Inversion-of-control versions of `once`.
k.listenToOnce=function(a,b,d){
// Map the event into a `{event: once}` object.
var e=m(q,{},b,d,c.bind(this.stopListening,this,a));return this.listenTo(a,e)};
// Reduces the event callbacks into a map of `{event: onceWrapper}`.
// `offer` unbinds the `onceWrapper` after it has been called.
var q=function(a,b,d,e){if(d){var f=a[b]=c.once(function(){e(b,f),d.apply(this,arguments)});f._callback=d}return a};
// Trigger one or many events, firing all bound callbacks. Callbacks are
// passed the same arguments as `trigger` is, apart from the event name
// (unless you're listening on `"all"`, which will cause your callback to
// receive the true name of the event as the first argument).
k.trigger=function(a){if(!this._events)return this;for(var b=Math.max(0,arguments.length-1),c=Array(b),d=0;b>d;d++)c[d]=arguments[d+1];return m(r,this._events,a,void 0,c),this};
// Handles triggering the appropriate event callbacks.
var r=function(a,b,c,d){if(a){var e=a[b],f=a.all;e&&f&&(f=f.slice()),e&&s(e,d),f&&s(f,[b].concat(d))}return a},s=function(a,b){var c,d=-1,e=a.length,f=b[0],g=b[1],h=b[2];switch(b.length){case 0:for(;++d<e;)(c=a[d]).callback.call(c.ctx);return;case 1:for(;++d<e;)(c=a[d]).callback.call(c.ctx,f);return;case 2:for(;++d<e;)(c=a[d]).callback.call(c.ctx,f,g);return;case 3:for(;++d<e;)(c=a[d]).callback.call(c.ctx,f,g,h);return;default:for(;++d<e;)(c=a[d]).callback.apply(c.ctx,b);return}};
// Aliases for backwards compatibility.
k.bind=k.on,k.unbind=k.off,
// Allow the `Backbone` object to serve as a global event bus, for folks who
// want global "pubsub" in a convenient place.
c.extend(b,k);
// Backbone.Model
// --------------
// Backbone **Models** are the basic data object in the framework --
// frequently representing a row in a table in a database on your server.
// A discrete chunk of data and a bunch of useful, related methods for
// performing computations and transformations on that data.
// Create a new model with the specified attributes. A client id (`cid`)
// is automatically generated and assigned for you.
var t=b.Model=function(a,b){var d=a||{};b||(b={}),this.cid=c.uniqueId(this.cidPrefix),this.attributes={},b.collection&&(this.collection=b.collection),b.parse&&(d=this.parse(d,b)||{}),d=c.defaults({},d,c.result(this,"defaults")),this.set(d,b),this.changed={},this.initialize.apply(this,arguments)};
// Attach all inheritable methods to the Model prototype.
c.extend(t.prototype,k,{
// A hash of attributes whose current and previous value differ.
changed:null,
// The value returned during the last failed validation.
validationError:null,
// The default name for the JSON `id` attribute is `"id"`. MongoDB and
// CouchDB users may want to set this to `"_id"`.
idAttribute:"id",
// The prefix is used to create the client id which is used to identify models locally.
// You may want to override this if you're experiencing name clashes with model ids.
cidPrefix:"c",
// Initialize is an empty function by default. Override it with your own
// initialization logic.
initialize:function(){},
// Return a copy of the model's `attributes` object.
toJSON:function(a){return c.clone(this.attributes)},
// Proxy `Backbone.sync` by default -- but override this if you need
// custom syncing semantics for *this* particular model.
sync:function(){return b.sync.apply(this,arguments)},
// Get the value of an attribute.
get:function(a){return this.attributes[a]},
// Get the HTML-escaped value of an attribute.
escape:function(a){return c.escape(this.get(a))},
// Returns `true` if the attribute contains a value that is not null
// or undefined.
has:function(a){return null!=this.get(a)},
// Special-cased proxy to underscore's `_.matches` method.
matches:function(a){return!!c.iteratee(a,this)(this.attributes)},
// Set a hash of model attributes on the object, firing `"change"`. This is
// the core primitive operation of a model, updating the data and notifying
// anyone who needs to know about the change in state. The heart of the beast.
set:function(a,b,d){if(null==a)return this;
// Handle both `"key", value` and `{key: value}` -style arguments.
var e;
// Run validation.
if("object"==typeof a?(e=a,d=b):(e={})[a]=b,d||(d={}),!this._validate(e,d))return!1;
// Extract attributes and options.
var f=d.unset,g=d.silent,h=[],i=this._changing;this._changing=!0,i||(this._previousAttributes=c.clone(this.attributes),this.changed={});var j=this.attributes,k=this.changed,l=this._previousAttributes;
// For each `set` attribute, update or delete the current value.
for(var m in e)b=e[m],c.isEqual(j[m],b)||h.push(m),c.isEqual(l[m],b)?delete k[m]:k[m]=b,f?delete j[m]:j[m]=b;
// Trigger all relevant attribute changes.
if(
// Update the `id`.
this.id=this.get(this.idAttribute),!g){h.length&&(this._pending=d);for(var n=0;n<h.length;n++)this.trigger("change:"+h[n],this,j[h[n]],d)}
// You might be wondering why there's a `while` loop here. Changes can
// be recursively nested within `"change"` events.
if(i)return this;if(!g)for(;this._pending;)d=this._pending,this._pending=!1,this.trigger("change",this,d);return this._pending=!1,this._changing=!1,this},
// Remove an attribute from the model, firing `"change"`. `unset` is a noop
// if the attribute doesn't exist.
unset:function(a,b){return this.set(a,void 0,c.extend({},b,{unset:!0}))},
// Clear all attributes on the model, firing `"change"`.
clear:function(a){var b={};for(var d in this.attributes)b[d]=void 0;return this.set(b,c.extend({},a,{unset:!0}))},
// Determine if the model has changed since the last `"change"` event.
// If you specify an attribute name, determine if that attribute has changed.
hasChanged:function(a){return null==a?!c.isEmpty(this.changed):c.has(this.changed,a)},
// Return an object containing all the attributes that have changed, or
// false if there are no changed attributes. Useful for determining what
// parts of a view need to be updated and/or what attributes need to be
// persisted to the server. Unset attributes will be set to undefined.
// You can also pass an attributes object to diff against the model,
// determining if there *would be* a change.
changedAttributes:function(a){if(!a)return this.hasChanged()?c.clone(this.changed):!1;var b=this._changing?this._previousAttributes:this.attributes,d={};for(var e in a){var f=a[e];c.isEqual(b[e],f)||(d[e]=f)}return c.size(d)?d:!1},
// Get the previous value of an attribute, recorded at the time the last
// `"change"` event was fired.
previous:function(a){return null!=a&&this._previousAttributes?this._previousAttributes[a]:null},
// Get all of the attributes of the model at the time of the previous
// `"change"` event.
previousAttributes:function(){return c.clone(this._previousAttributes)},
// Fetch the model from the server, merging the response with the model's
// local attributes. Any changed attributes will trigger a "change" event.
fetch:function(a){a=c.extend({parse:!0},a);var b=this,d=a.success;return a.success=function(c){var e=a.parse?b.parse(c,a):c;return b.set(e,a)?(d&&d.call(a.context,b,c,a),void b.trigger("sync",b,c,a)):!1},P(this,a),this.sync("read",this,a)},
// Set a hash of model attributes, and sync the model to the server.
// If the server returns an attributes hash that differs, the model's
// state will be `set` again.
save:function(a,b,d){
// Handle both `"key", value` and `{key: value}` -style arguments.
var e;null==a||"object"==typeof a?(e=a,d=b):(e={})[a]=b,d=c.extend({validate:!0,parse:!0},d);var f=d.wait;
// If we're not waiting and attributes exist, save acts as
// `set(attr).save(null, opts)` with validation. Otherwise, check if
// the model will be valid when the attributes, if any, are set.
if(e&&!f){if(!this.set(e,d))return!1}else if(!this._validate(e,d))return!1;
// After a successful server-side save, the client is (optionally)
// updated with the server-side state.
var g=this,h=d.success,i=this.attributes;d.success=function(a){
// Ensure attributes are restored during synchronous saves.
g.attributes=i;var b=d.parse?g.parse(a,d):a;return f&&(b=c.extend({},e,b)),b&&!g.set(b,d)?!1:(h&&h.call(d.context,g,a,d),void g.trigger("sync",g,a,d))},P(this,d),
// Set temporary attributes if `{wait: true}` to properly find new ids.
e&&f&&(this.attributes=c.extend({},i,e));var j=this.isNew()?"create":d.patch?"patch":"update";"patch"!==j||d.attrs||(d.attrs=e);var k=this.sync(j,this,d);
// Restore attributes.
return this.attributes=i,k},
// Destroy this model on the server if it was already persisted.
// Optimistically removes the model from its collection, if it has one.
// If `wait: true` is passed, waits for the server to respond before removal.
destroy:function(a){a=a?c.clone(a):{};var b=this,d=a.success,e=a.wait,f=function(){b.stopListening(),b.trigger("destroy",b,b.collection,a)};a.success=function(c){e&&f(),d&&d.call(a.context,b,c,a),b.isNew()||b.trigger("sync",b,c,a)};var g=!1;return this.isNew()?c.defer(a.success):(P(this,a),g=this.sync("delete",this,a)),e||f(),g},
// Default URL for the model's representation on the server -- if you're
// using Backbone's restful methods, override this to change the endpoint
// that will be called.
url:function(){var a=c.result(this,"urlRoot")||c.result(this.collection,"url")||O();if(this.isNew())return a;var b=this.get(this.idAttribute);return a.replace(/[^\/]$/,"$&/")+encodeURIComponent(b)},
// **parse** converts a response into the hash of attributes to be `set` on
// the model. The default implementation is just to pass the response along.
parse:function(a,b){return a},
// Create a new model with identical attributes to this one.
clone:function(){return new this.constructor(this.attributes)},
// A model is new if it has never been saved to the server, and lacks an id.
isNew:function(){return!this.has(this.idAttribute)},
// Check if the model is currently in a valid state.
isValid:function(a){return this._validate({},c.defaults({validate:!0},a))},
// Run validation against the next complete set of model attributes,
// returning `true` if all is well. Otherwise, fire an `"invalid"` event.
_validate:function(a,b){if(!b.validate||!this.validate)return!0;a=c.extend({},this.attributes,a);var d=this.validationError=this.validate(a,b)||null;return d?(this.trigger("invalid",this,d,c.extend(b,{validationError:d})),!1):!0}});
// Underscore methods that we want to implement on the Model, mapped to the
// number of arguments they take.
var u={keys:1,values:1,pairs:1,invert:1,pick:0,omit:0,chain:1,isEmpty:1};
// Mix in each Underscore method as a proxy to `Model#attributes`.
h(t,u,"attributes");
// Backbone.Collection
// -------------------
// If models tend to represent a single row of data, a Backbone Collection is
// more analogous to a table full of data ... or a small slice or page of that
// table, or a collection of rows that belong together for a particular reason
// -- all of the messages in this particular folder, all of the documents
// belonging to this particular author, and so on. Collections maintain
// indexes of their models, both in order, and for lookup by `id`.
// Create a new **Collection**, perhaps to contain a specific type of `model`.
// If a `comparator` is specified, the Collection will maintain
// its models in sort order, as they're added and removed.
var v=b.Collection=function(a,b){b||(b={}),b.model&&(this.model=b.model),void 0!==b.comparator&&(this.comparator=b.comparator),this._reset(),this.initialize.apply(this,arguments),a&&this.reset(a,c.extend({silent:!0},b))},w={add:!0,remove:!0,merge:!0},x={add:!0,remove:!1},y=function(a,b,c){c=Math.min(Math.max(c,0),a.length);for(var d=Array(a.length-c),e=b.length,f=0;f<d.length;f++)d[f]=a[f+c];for(f=0;e>f;f++)a[f+c]=b[f];for(f=0;f<d.length;f++)a[f+e+c]=d[f]};
// Define the Collection's inheritable methods.
c.extend(v.prototype,k,{
// The default model for a collection is just a **Backbone.Model**.
// This should be overridden in most cases.
model:t,
// Initialize is an empty function by default. Override it with your own
// initialization logic.
initialize:function(){},
// The JSON representation of a Collection is an array of the
// models' attributes.
toJSON:function(a){return this.map(function(b){return b.toJSON(a)})},
// Proxy `Backbone.sync` by default.
sync:function(){return b.sync.apply(this,arguments)},
// Add a model, or list of models to the set. `models` may be Backbone
// Models or raw JavaScript objects to be converted to Models, or any
// combination of the two.
add:function(a,b){return this.set(a,c.extend({merge:!1},b,x))},
// Remove a model, or a list of models from the set.
remove:function(a,b){b=c.extend({},b);var d=!c.isArray(a);a=d?[a]:c.clone(a);var e=this._removeModels(a,b);return!b.silent&&e&&this.trigger("update",this,b),d?e[0]:e},
// Update a collection by `set`-ing a new list of models, adding new ones,
// removing models that are no longer present, and merging models that
// already exist in the collection, as necessary. Similar to **Model#set**,
// the core operation for updating the data contained by the collection.
set:function(a,b){if(null!=a){b=c.defaults({},b,w),b.parse&&!this._isModel(a)&&(a=this.parse(a,b));var d=!c.isArray(a);a=d?[a]:a.slice();var e=b.at;null!=e&&(e=+e),0>e&&(e+=this.length+1);for(var f,g=[],h=[],i=[],j={},k=b.add,l=b.merge,m=b.remove,n=!1,o=this.comparator&&null==e&&b.sort!==!1,p=c.isString(this.comparator)?this.comparator:null,q=0;q<a.length;q++){f=a[q];
// If a duplicate is found, prevent it from being added and
// optionally merge it into the existing model.
var r=this.get(f);if(r){if(l&&f!==r){var s=this._isModel(f)?f.attributes:f;b.parse&&(s=r.parse(s,b)),r.set(s,b),o&&!n&&(n=r.hasChanged(p))}j[r.cid]||(j[r.cid]=!0,g.push(r)),a[q]=r}else k&&(f=a[q]=this._prepareModel(f,b),f&&(h.push(f),this._addReference(f,b),j[f.cid]=!0,g.push(f)))}
// Remove stale models.
if(m){for(q=0;q<this.length;q++)f=this.models[q],j[f.cid]||i.push(f);i.length&&this._removeModels(i,b)}
// See if sorting is needed, update `length` and splice in new models.
var t=!1,u=!o&&k&&m;
// Unless silenced, it's time to fire all appropriate add/sort events.
if(g.length&&u?(t=this.length!=g.length||c.some(this.models,function(a,b){return a!==g[b]}),this.models.length=0,y(this.models,g,0),this.length=this.models.length):h.length&&(o&&(n=!0),y(this.models,h,null==e?this.length:e),this.length=this.models.length),
// Silently sort the collection if appropriate.
n&&this.sort({silent:!0}),!b.silent){for(q=0;q<h.length;q++)null!=e&&(b.index=e+q),f=h[q],f.trigger("add",f,this,b);(n||t)&&this.trigger("sort",this,b),(h.length||i.length)&&this.trigger("update",this,b)}
// Return the added (or merged) model (or models).
return d?a[0]:a}},
// When you have more items than you want to add or remove individually,
// you can reset the entire set with a new list of models, without firing
// any granular `add` or `remove` events. Fires `reset` when finished.
// Useful for bulk operations and optimizations.
reset:function(a,b){b=b?c.clone(b):{};for(var d=0;d<this.models.length;d++)this._removeReference(this.models[d],b);return b.previousModels=this.models,this._reset(),a=this.add(a,c.extend({silent:!0},b)),b.silent||this.trigger("reset",this,b),a},
// Add a model to the end of the collection.
push:function(a,b){return this.add(a,c.extend({at:this.length},b))},
// Remove a model from the end of the collection.
pop:function(a){var b=this.at(this.length-1);return this.remove(b,a)},
// Add a model to the beginning of the collection.
unshift:function(a,b){return this.add(a,c.extend({at:0},b))},
// Remove a model from the beginning of the collection.
shift:function(a){var b=this.at(0);return this.remove(b,a)},
// Slice out a sub-array of models from the collection.
slice:function(){return f.apply(this.models,arguments)},
// Get a model from the set by id.
get:function(a){if(null!=a){var b=this.modelId(this._isModel(a)?a.attributes:a);return this._byId[a]||this._byId[b]||this._byId[a.cid]}},
// Get the model at the given index.
at:function(a){return 0>a&&(a+=this.length),this.models[a]},
// Return models with matching attributes. Useful for simple cases of
// `filter`.
where:function(a,b){return this[b?"find":"filter"](a)},
// Return the first model with matching attributes. Useful for simple cases
// of `find`.
findWhere:function(a){return this.where(a,!0)},
// Force the collection to re-sort itself. You don't need to call this under
// normal circumstances, as the set will maintain sort order as each item
// is added.
sort:function(a){var b=this.comparator;if(!b)throw new Error("Cannot sort a set without a comparator");a||(a={});var d=b.length;
// Run sort based on type of `comparator`.
return c.isFunction(b)&&(b=c.bind(b,this)),1===d||c.isString(b)?this.models=this.sortBy(b):this.models.sort(b),a.silent||this.trigger("sort",this,a),this},
// Pluck an attribute from each model in the collection.
pluck:function(a){return c.invoke(this.models,"get",a)},
// Fetch the default set of models for this collection, resetting the
// collection when they arrive. If `reset: true` is passed, the response
// data will be passed through the `reset` method instead of `set`.
fetch:function(a){a=c.extend({parse:!0},a);var b=a.success,d=this;return a.success=function(c){var e=a.reset?"reset":"set";d[e](c,a),b&&b.call(a.context,d,c,a),d.trigger("sync",d,c,a)},P(this,a),this.sync("read",this,a)},
// Create a new instance of a model in this collection. Add the model to the
// collection immediately, unless `wait: true` is passed, in which case we
// wait for the server to agree.
create:function(a,b){b=b?c.clone(b):{};var d=b.wait;if(a=this._prepareModel(a,b),!a)return!1;d||this.add(a,b);var e=this,f=b.success;return b.success=function(a,b,c){d&&e.add(a,c),f&&f.call(c.context,a,b,c)},a.save(null,b),a},
// **parse** converts a response into a list of models to be added to the
// collection. The default implementation is just to pass it through.
parse:function(a,b){return a},
// Create a new collection with an identical list of models as this one.
clone:function(){return new this.constructor(this.models,{model:this.model,comparator:this.comparator})},
// Define how to uniquely identify models in the collection.
modelId:function(a){return a[this.model.prototype.idAttribute||"id"]},
// Private method to reset all internal state. Called when the collection
// is first initialized or reset.
_reset:function(){this.length=0,this.models=[],this._byId={}},
// Prepare a hash of attributes (or other model) to be added to this
// collection.
_prepareModel:function(a,b){if(this._isModel(a))return a.collection||(a.collection=this),a;b=b?c.clone(b):{},b.collection=this;var d=new this.model(a,b);return d.validationError?(this.trigger("invalid",this,d.validationError,b),!1):d},
// Internal method called by both remove and set.
_removeModels:function(a,b){for(var c=[],d=0;d<a.length;d++){var e=this.get(a[d]);if(e){var f=this.indexOf(e);this.models.splice(f,1),this.length--,b.silent||(b.index=f,e.trigger("remove",e,this,b)),c.push(e),this._removeReference(e,b)}}return c.length?c:!1},
// Method for checking whether an object should be considered a model for
// the purposes of adding to the collection.
_isModel:function(a){return a instanceof t},
// Internal method to create a model's ties to a collection.
_addReference:function(a,b){this._byId[a.cid]=a;var c=this.modelId(a.attributes);null!=c&&(this._byId[c]=a),a.on("all",this._onModelEvent,this)},
// Internal method to sever a model's ties to a collection.
_removeReference:function(a,b){delete this._byId[a.cid];var c=this.modelId(a.attributes);null!=c&&delete this._byId[c],this===a.collection&&delete a.collection,a.off("all",this._onModelEvent,this)},
// Internal method called every time a model in the set fires an event.
// Sets need to update their indexes when models change ids. All other
// events simply proxy through. "add" and "remove" events that originate
// in other collections are ignored.
_onModelEvent:function(a,b,c,d){if("add"!==a&&"remove"!==a||c===this){if("destroy"===a&&this.remove(b,d),"change"===a){var e=this.modelId(b.previousAttributes()),f=this.modelId(b.attributes);e!==f&&(null!=e&&delete this._byId[e],null!=f&&(this._byId[f]=b))}this.trigger.apply(this,arguments)}}});
// Underscore methods that we want to implement on the Collection.
// 90% of the core usefulness of Backbone Collections is actually implemented
// right here:
var z={forEach:3,each:3,map:3,collect:3,reduce:4,foldl:4,inject:4,reduceRight:4,foldr:4,find:3,detect:3,filter:3,select:3,reject:3,every:3,all:3,some:3,any:3,include:3,includes:3,contains:3,invoke:0,max:3,min:3,toArray:1,size:1,first:3,head:3,take:3,initial:3,rest:3,tail:3,drop:3,last:3,without:0,difference:0,indexOf:3,shuffle:1,lastIndexOf:3,isEmpty:1,chain:1,sample:3,partition:3,groupBy:3,countBy:3,sortBy:3,indexBy:3};
// Mix in each Underscore method as a proxy to `Collection#models`.
h(v,z,"models");
// Backbone.View
// -------------
// Backbone Views are almost more convention than they are actual code. A View
// is simply a JavaScript object that represents a logical chunk of UI in the
// DOM. This might be a single item, an entire list, a sidebar or panel, or
// even the surrounding frame which wraps your whole app. Defining a chunk of
// UI as a **View** allows you to define your DOM events declaratively, without
// having to worry about render order ... and makes it easy for the view to
// react to specific changes in the state of your models.
// Creating a Backbone.View creates its initial element outside of the DOM,
// if an existing element is not provided...
var A=b.View=function(a){this.cid=c.uniqueId("view"),c.extend(this,c.pick(a,C)),this._ensureElement(),this.initialize.apply(this,arguments)},B=/^(\S+)\s*(.*)$/,C=["model","collection","el","id","attributes","className","tagName","events"];
// Set up all inheritable **Backbone.View** properties and methods.
c.extend(A.prototype,k,{
// The default `tagName` of a View's element is `"div"`.
tagName:"div",
// jQuery delegate for element lookup, scoped to DOM elements within the
// current view. This should be preferred to global lookups where possible.
$:function(a){return this.$el.find(a)},
// Initialize is an empty function by default. Override it with your own
// initialization logic.
initialize:function(){},
// **render** is the core function that your view should override, in order
// to populate its element (`this.el`), with the appropriate HTML. The
// convention is for **render** to always return `this`.
render:function(){return this},
// Remove this view by taking the element out of the DOM, and removing any
// applicable Backbone.Events listeners.
remove:function(){return this._removeElement(),this.stopListening(),this},
// Remove this view's element from the document and all event listeners
// attached to it. Exposed for subclasses using an alternative DOM
// manipulation API.
_removeElement:function(){this.$el.remove()},
// Change the view's element (`this.el` property) and re-delegate the
// view's events on the new element.
setElement:function(a){return this.undelegateEvents(),this._setElement(a),this.delegateEvents(),this},
// Creates the `this.el` and `this.$el` references for this view using the
// given `el`. `el` can be a CSS selector or an HTML string, a jQuery
// context or an element. Subclasses can override this to utilize an
// alternative DOM manipulation API and are only required to set the
// `this.el` property.
_setElement:function(a){this.$el=a instanceof b.$?a:b.$(a),this.el=this.$el[0]},
// Set callbacks, where `this.events` is a hash of
//
// *{"event selector": "callback"}*
//
//     {
//       'mousedown .title':  'edit',
//       'click .button':     'save',
//       'click .open':       function(e) { ... }
//     }
//
// pairs. Callbacks will be bound to the view, with `this` set properly.
// Uses event delegation for efficiency.
// Omitting the selector binds the event to `this.el`.
delegateEvents:function(a){if(a||(a=c.result(this,"events")),!a)return this;this.undelegateEvents();for(var b in a){var d=a[b];if(c.isFunction(d)||(d=this[d]),d){var e=b.match(B);this.delegate(e[1],e[2],c.bind(d,this))}}return this},
// Add a single event listener to the view's element (or a child element
// using `selector`). This only works for delegate-able events: not `focus`,
// `blur`, and not `change`, `submit`, and `reset` in Internet Explorer.
delegate:function(a,b,c){return this.$el.on(a+".delegateEvents"+this.cid,b,c),this},
// Clears all callbacks previously bound to the view by `delegateEvents`.
// You usually don't need to use this, but may wish to if you have multiple
// Backbone views attached to the same DOM element.
undelegateEvents:function(){return this.$el&&this.$el.off(".delegateEvents"+this.cid),this},
// A finer-grained `undelegateEvents` for removing a single delegated event.
// `selector` and `listener` are both optional.
undelegate:function(a,b,c){return this.$el.off(a+".delegateEvents"+this.cid,b,c),this},
// Produces a DOM element to be assigned to your view. Exposed for
// subclasses using an alternative DOM manipulation API.
_createElement:function(a){return document.createElement(a)},
// Ensure that the View has a DOM element to render into.
// If `this.el` is a string, pass it through `$()`, take the first
// matching element, and re-assign it to `el`. Otherwise, create
// an element from the `id`, `className` and `tagName` properties.
_ensureElement:function(){if(this.el)this.setElement(c.result(this,"el"));else{var a=c.extend({},c.result(this,"attributes"));this.id&&(a.id=c.result(this,"id")),this.className&&(a["class"]=c.result(this,"className")),this.setElement(this._createElement(c.result(this,"tagName"))),this._setAttributes(a)}},
// Set attributes from a hash on this view's element.  Exposed for
// subclasses using an alternative DOM manipulation API.
_setAttributes:function(a){this.$el.attr(a)}}),
// Backbone.sync
// -------------
// Override this function to change the manner in which Backbone persists
// models to the server. You will be passed the type of request, and the
// model in question. By default, makes a RESTful Ajax request
// to the model's `url()`. Some possible customizations could be:
//
// * Use `setTimeout` to batch rapid-fire updates into a single request.
// * Send up the models as XML instead of JSON.
// * Persist models via WebSockets instead of Ajax.
//
// Turn on `Backbone.emulateHTTP` in order to send `PUT` and `DELETE` requests
// as `POST`, with a `_method` parameter containing the true HTTP method,
// as well as all requests with the body as `application/x-www-form-urlencoded`
// instead of `application/json` with the model in a param named `model`.
// Useful when interfacing with server-side languages like **PHP** that make
// it difficult to read the body of `PUT` requests.
b.sync=function(a,d,e){var f=D[a];
// Default options, unless specified.
c.defaults(e||(e={}),{emulateHTTP:b.emulateHTTP,emulateJSON:b.emulateJSON});
// Default JSON-request options.
var g={type:f,dataType:"json"};
// For older servers, emulate HTTP by mimicking the HTTP method with `_method`
// And an `X-HTTP-Method-Override` header.
if(
// Ensure that we have a URL.
e.url||(g.url=c.result(d,"url")||O()),
// Ensure that we have the appropriate request data.
null!=e.data||!d||"create"!==a&&"update"!==a&&"patch"!==a||(g.contentType="application/json",g.data=JSON.stringify(e.attrs||d.toJSON(e))),
// For older servers, emulate JSON by encoding the request into an HTML-form.
e.emulateJSON&&(g.contentType="application/x-www-form-urlencoded",g.data=g.data?{model:g.data}:{}),e.emulateHTTP&&("PUT"===f||"DELETE"===f||"PATCH"===f)){g.type="POST",e.emulateJSON&&(g.data._method=f);var h=e.beforeSend;e.beforeSend=function(a){return a.setRequestHeader("X-HTTP-Method-Override",f),h?h.apply(this,arguments):void 0}}
// Don't process data on a non-GET request.
"GET"===g.type||e.emulateJSON||(g.processData=!1);
// Pass along `textStatus` and `errorThrown` from jQuery.
var i=e.error;e.error=function(a,b,c){e.textStatus=b,e.errorThrown=c,i&&i.call(e.context,a,b,c)};
// Make the request, allowing the user to override any Ajax options.
var j=e.xhr=b.ajax(c.extend(g,e));return d.trigger("request",d,j,e),j};
// Map from CRUD to HTTP for our default `Backbone.sync` implementation.
var D={create:"POST",update:"PUT",patch:"PATCH","delete":"DELETE",read:"GET"};
// Set the default implementation of `Backbone.ajax` to proxy through to `$`.
// Override this if you'd like to use a different library.
b.ajax=function(){return b.$.ajax.apply(b.$,arguments)};
// Backbone.Router
// ---------------
// Routers map faux-URLs to actions, and fire events when routes are
// matched. Creating a new one sets its `routes` hash, if not set statically.
var E=b.Router=function(a){a||(a={}),a.routes&&(this.routes=a.routes),this._bindRoutes(),this.initialize.apply(this,arguments)},F=/\((.*?)\)/g,G=/(\(\?)?:\w+/g,H=/\*\w+/g,I=/[\-{}\[\]+?.,\\\^$|#\s]/g;
// Set up all inheritable **Backbone.Router** properties and methods.
c.extend(E.prototype,k,{
// Initialize is an empty function by default. Override it with your own
// initialization logic.
initialize:function(){},
// Manually bind a single named route to a callback. For example:
//
//     this.route('search/:query/p:num', 'search', function(query, num) {
//       ...
//     });
//
route:function(a,d,e){c.isRegExp(a)||(a=this._routeToRegExp(a)),c.isFunction(d)&&(e=d,d=""),e||(e=this[d]);var f=this;return b.history.route(a,function(c){var g=f._extractParameters(a,c);f.execute(e,g,d)!==!1&&(f.trigger.apply(f,["route:"+d].concat(g)),f.trigger("route",d,g),b.history.trigger("route",f,d,g))}),this},
// Execute a route handler with the provided parameters.  This is an
// excellent place to do pre-route setup or post-route cleanup.
execute:function(a,b,c){a&&a.apply(this,b)},
// Simple proxy to `Backbone.history` to save a fragment into the history.
navigate:function(a,c){return b.history.navigate(a,c),this},
// Bind all defined routes to `Backbone.history`. We have to reverse the
// order of the routes here to support behavior where the most general
// routes can be defined at the bottom of the route map.
_bindRoutes:function(){if(this.routes){this.routes=c.result(this,"routes");for(var a,b=c.keys(this.routes);null!=(a=b.pop());)this.route(a,this.routes[a])}},
// Convert a route string into a regular expression, suitable for matching
// against the current location hash.
_routeToRegExp:function(a){return a=a.replace(I,"\\$&").replace(F,"(?:$1)?").replace(G,function(a,b){return b?a:"([^/?]+)"}).replace(H,"([^?]*?)"),new RegExp("^"+a+"(?:\\?([\\s\\S]*))?$")},
// Given a route, and a URL fragment that it matches, return the array of
// extracted decoded parameters. Empty or unmatched parameters will be
// treated as `null` to normalize cross-browser behavior.
_extractParameters:function(a,b){var d=a.exec(b).slice(1);return c.map(d,function(a,b){
// Don't decode the search params.
// Don't decode the search params.
return b===d.length-1?a||null:a?decodeURIComponent(a):null})}});
// Backbone.History
// ----------------
// Handles cross-browser history management, based on either
// [pushState](http://diveintohtml5.info/history.html) and real URLs, or
// [onhashchange](https://developer.mozilla.org/en-US/docs/DOM/window.onhashchange)
// and URL fragments. If the browser supports neither (old IE, natch),
// falls back to polling.
var J=b.History=function(){this.handlers=[],this.checkUrl=c.bind(this.checkUrl,this),
// Ensure that `History` can be used outside of the browser.
"undefined"!=typeof window&&(this.location=window.location,this.history=window.history)},K=/^[#\/]|\s+$/g,L=/^\/+|\/+$/g,M=/#.*$/;
// Has the history handling already been started?
J.started=!1,
// Set up all inheritable **Backbone.History** properties and methods.
c.extend(J.prototype,k,{
// The default interval to poll for hash changes, if necessary, is
// twenty times a second.
interval:50,
// Are we at the app root?
atRoot:function(){var a=this.location.pathname.replace(/[^\/]$/,"$&/");return a===this.root&&!this.getSearch()},
// Does the pathname match the root?
matchRoot:function(){var a=this.decodeFragment(this.location.pathname),b=a.slice(0,this.root.length-1)+"/";return b===this.root},
// Unicode characters in `location.pathname` are percent encoded so they're
// decoded for comparison. `%25` should not be decoded since it may be part
// of an encoded parameter.
decodeFragment:function(a){return decodeURI(a.replace(/%25/g,"%2525"))},
// In IE6, the hash fragment and search params are incorrect if the
// fragment contains `?`.
getSearch:function(){var a=this.location.href.replace(/#.*/,"").match(/\?.+/);return a?a[0]:""},
// Gets the true hash value. Cannot use location.hash directly due to bug
// in Firefox where location.hash will always be decoded.
getHash:function(a){var b=(a||this).location.href.match(/#(.*)$/);return b?b[1]:""},
// Get the pathname and search params, without the root.
getPath:function(){var a=this.decodeFragment(this.location.pathname+this.getSearch()).slice(this.root.length-1);return"/"===a.charAt(0)?a.slice(1):a},
// Get the cross-browser normalized URL fragment from the path or hash.
getFragment:function(a){return null==a&&(a=this._usePushState||!this._wantsHashChange?this.getPath():this.getHash()),a.replace(K,"")},
// Start the hash change handling, returning `true` if the current URL matches
// an existing route, and `false` otherwise.
start:function(a){if(J.started)throw new Error("Backbone.history has already been started");
// Transition from hashChange to pushState or vice versa if both are
// requested.
if(J.started=!0,
// Figure out the initial configuration. Do we need an iframe?
// Is pushState desired ... is it available?
this.options=c.extend({root:"/"},this.options,a),this.root=this.options.root,this._wantsHashChange=this.options.hashChange!==!1,this._hasHashChange="onhashchange"in window&&(void 0===document.documentMode||document.documentMode>7),this._useHashChange=this._wantsHashChange&&this._hasHashChange,this._wantsPushState=!!this.options.pushState,this._hasPushState=!(!this.history||!this.history.pushState),this._usePushState=this._wantsPushState&&this._hasPushState,this.fragment=this.getFragment(),
// Normalize root to always include a leading and trailing slash.
this.root=("/"+this.root+"/").replace(L,"/"),this._wantsHashChange&&this._wantsPushState){
// If we've started off with a route from a `pushState`-enabled
// browser, but we're currently in a browser that doesn't support it...
if(!this._hasPushState&&!this.atRoot()){var b=this.root.slice(0,-1)||"/";
// Return immediately as browser will do redirect to new url
return this.location.replace(b+"#"+this.getPath()),!0}this._hasPushState&&this.atRoot()&&this.navigate(this.getHash(),{replace:!0})}
// Proxy an iframe to handle location events if the browser doesn't
// support the `hashchange` event, HTML5 history, or the user wants
// `hashChange` but not `pushState`.
if(!this._hasHashChange&&this._wantsHashChange&&!this._usePushState){this.iframe=document.createElement("iframe"),this.iframe.src="javascript:0",this.iframe.style.display="none",this.iframe.tabIndex=-1;var d=document.body,e=d.insertBefore(this.iframe,d.firstChild).contentWindow;e.document.open(),e.document.close(),e.location.hash="#"+this.fragment}
// Add a cross-platform `addEventListener` shim for older browsers.
var f=window.addEventListener||function(a,b){return attachEvent("on"+a,b)};
// Depending on whether we're using pushState or hashes, and whether
// 'onhashchange' is supported, determine how we check the URL state.
return this._usePushState?f("popstate",this.checkUrl,!1):this._useHashChange&&!this.iframe?f("hashchange",this.checkUrl,!1):this._wantsHashChange&&(this._checkUrlInterval=setInterval(this.checkUrl,this.interval)),this.options.silent?void 0:this.loadUrl()},
// Disable Backbone.history, perhaps temporarily. Not useful in a real app,
// but possibly useful for unit testing Routers.
stop:function(){
// Add a cross-platform `removeEventListener` shim for older browsers.
var a=window.removeEventListener||function(a,b){return detachEvent("on"+a,b)};
// Remove window listeners.
this._usePushState?a("popstate",this.checkUrl,!1):this._useHashChange&&!this.iframe&&a("hashchange",this.checkUrl,!1),
// Clean up the iframe if necessary.
this.iframe&&(document.body.removeChild(this.iframe),this.iframe=null),
// Some environments will throw when clearing an undefined interval.
this._checkUrlInterval&&clearInterval(this._checkUrlInterval),J.started=!1},
// Add a route to be tested when the fragment changes. Routes added later
// may override previous routes.
route:function(a,b){this.handlers.unshift({route:a,callback:b})},
// Checks the current URL to see if it has changed, and if it has,
// calls `loadUrl`, normalizing across the hidden iframe.
checkUrl:function(a){var b=this.getFragment();
// If the user pressed the back button, the iframe's hash will have
// changed and we should use that for comparison.
return b===this.fragment&&this.iframe&&(b=this.getHash(this.iframe.contentWindow)),b===this.fragment?!1:(this.iframe&&this.navigate(b),void this.loadUrl())},
// Attempt to load the current URL fragment. If a route succeeds with a
// match, returns `true`. If no defined routes matches the fragment,
// returns `false`.
loadUrl:function(a){
// If the root doesn't match, no routes can match either.
// If the root doesn't match, no routes can match either.
return this.matchRoot()?(a=this.fragment=this.getFragment(a),c.some(this.handlers,function(b){return b.route.test(a)?(b.callback(a),!0):void 0})):!1},
// Save a fragment into the hash history, or replace the URL state if the
// 'replace' option is passed. You are responsible for properly URL-encoding
// the fragment in advance.
//
// The options object can contain `trigger: true` if you wish to have the
// route callback be fired (not usually desirable), or `replace: true`, if
// you wish to modify the current URL without adding an entry to the history.
navigate:function(a,b){if(!J.started)return!1;b&&b!==!0||(b={trigger:!!b}),
// Normalize the fragment.
a=this.getFragment(a||"");
// Don't include a trailing slash on the root.
var c=this.root;""!==a&&"?"!==a.charAt(0)||(c=c.slice(0,-1)||"/");var d=c+a;if(a=this.decodeFragment(a.replace(M,"")),this.fragment!==a){
// If pushState is available, we use it to set the fragment as a real URL.
if(this.fragment=a,this._usePushState)this.history[b.replace?"replaceState":"pushState"]({},document.title,d);else{if(!this._wantsHashChange)return this.location.assign(d);if(this._updateHash(this.location,a,b.replace),this.iframe&&a!==this.getHash(this.iframe.contentWindow)){var e=this.iframe.contentWindow;
// Opening and closing the iframe tricks IE7 and earlier to push a
// history entry on hash-tag change.  When replace is true, we don't
// want this.
b.replace||(e.document.open(),e.document.close()),this._updateHash(e.location,a,b.replace)}}return b.trigger?this.loadUrl(a):void 0}},
// Update the hash location, either replacing the current entry, or adding
// a new one to the browser history.
_updateHash:function(a,b,c){if(c){var d=a.href.replace(/(javascript:|#).*$/,"");a.replace(d+"#"+b)}else
// Some browsers require that `hash` contains a leading #.
a.hash="#"+b}}),
// Create the default Backbone.history.
b.history=new J;
// Helpers
// -------
// Helper function to correctly set up the prototype chain for subclasses.
// Similar to `goog.inherits`, but uses a hash of prototype properties and
// class properties to be extended.
var N=function(a,b){var d,e=this;
// The constructor function for the new subclass is either defined by you
// (the "constructor" property in your `extend` definition), or defaulted
// by us to simply call the parent constructor.
d=a&&c.has(a,"constructor")?a.constructor:function(){return e.apply(this,arguments)},c.extend(d,e,b);
// Set the prototype chain to inherit from `parent`, without calling
// `parent` constructor function.
var f=function(){this.constructor=d};
// Add prototype properties (instance properties) to the subclass,
// if supplied.
// Set a convenience property in case the parent's prototype is needed
// later.
return f.prototype=e.prototype,d.prototype=new f,a&&c.extend(d.prototype,a),d.__super__=e.prototype,d};
// Set up inheritance for the model, collection, router, view and history.
t.extend=v.extend=E.extend=A.extend=J.extend=N;
// Throw an error when a URL is needed, and none is supplied.
var O=function(){throw new Error('A "url" property or function must be specified')},P=function(a,b){var c=b.error;b.error=function(d){c&&c.call(b.context,a,d,b),a.trigger("error",a,d,b)}};return b});

/**
 * bootbox.js [v4.4.0]
 *
 * http://bootboxjs.com/license.txt
 */
// @see https://github.com/makeusabrew/bootbox/issues/180
// @see https://github.com/makeusabrew/bootbox/issues/186
!function(a,b){"use strict";"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery"],b):"object"==typeof exports?module.exports=b(require("jquery")):a.bootbox=b(a.jQuery)}(this,function a(b,c){"use strict";/**
   * @private
   */
function d(a){var b=q[o.locale];return b?b[a]:q.en[a]}function e(a,c,d){a.stopPropagation(),a.preventDefault();
// by default we assume a callback will get rid of the dialog,
// although it is given the opportunity to override this
// so, if the callback can be invoked and it *explicitly returns false*
// then we'll set a flag to keep the dialog active...
var e=b.isFunction(d)&&d.call(c,a)===!1;
// ... otherwise we'll bin it
e||c.modal("hide")}function f(a){
// @TODO defer to Object.keys(x).length if available?
var b,c=0;for(b in a)c++;return c}function g(a,c){var d=0;b.each(a,function(a,b){c(a,b,d++)})}function h(a){var c,d;if("object"!=typeof a)throw new Error("Please supply an object of options");if(!a.message)throw new Error("Please specify a message");
// make sure any supplied options take precedence over defaults
return a=b.extend({},o,a),a.buttons||(a.buttons={}),c=a.buttons,d=f(c),g(c,function(a,e,f){if(b.isFunction(e)&&(e=c[a]={callback:e}),"object"!==b.type(e))throw new Error("button with key "+a+" must be an object");e.label||(e.label=a),e.className||(2>=d&&f===d-1?e.className="btn-primary":e.className="btn-default")}),a}/**
   * map a flexible set of arguments into a single returned object
   * if args.length is already one just return it, otherwise
   * use the properties argument to map the unnamed args to
   * object properties
   * so in the latter case:
   * mapArguments(["foo", $.noop], ["message", "callback"])
   * -> { message: "foo", callback: $.noop }
   */
function i(a,b){var c=a.length,d={};if(1>c||c>2)throw new Error("Invalid argument length");return 2===c||"string"==typeof a[0]?(d[b[0]]=a[0],d[b[1]]=a[1]):d=a[0],d}/**
   * merge a set of default dialog options with user supplied arguments
   */
function j(a,c,d){
// deep merge
// ensure the target is an empty, unreferenced object
// the base options object for this type of dialog (often just buttons)
// args could be an object or array; if it's an array properties will
// map it to a proper options object
return b.extend(!0,{},a,i(c,d))}/**
   * this entry-level method makes heavy use of composition to take a simple
   * range of inputs and return valid options suitable for passing to bootbox.dialog
   */
function k(a,b,c,d){
//  build up a base set of dialog properties
var e={className:"bootbox-"+a,buttons:l.apply(null,b)};
// ensure the buttons properties generated, *after* merging
// with user args are still valid against the supplied labels
// merge the generated base properties with user supplied arguments
// if args.length > 1, properties specify how each arg maps to an object key
return m(j(e,d,c),b)}/**
   * from a given list of arguments return a suitable object of button labels
   * all this does is normalise the given labels and translate them where possible
   * e.g. "ok", "confirm" -> { ok: "OK, cancel: "Annuleren" }
   */
function l(){for(var a={},b=0,c=arguments.length;c>b;b++){var e=arguments[b],f=e.toLowerCase(),g=e.toUpperCase();a[f]={label:d(g)}}return a}function m(a,b){var d={};return g(b,function(a,b){d[b]=!0}),g(a.buttons,function(a){if(d[a]===c)throw new Error("button key "+a+" is not allowed (options are "+b.join("\n")+")")}),a}
// the base DOM structure needed to create a modal
var n={dialog:"<div class='bootbox modal' tabindex='-1' role='dialog'><div class='modal-dialog'><div class='modal-content'><div class='modal-body'><div class='bootbox-body'></div></div></div></div></div>",header:"<div class='modal-header'><h4 class='modal-title'></h4></div>",footer:"<div class='modal-footer'></div>",closeButton:"<button type='button' class='bootbox-close-button close' data-dismiss='modal' aria-hidden='true'>&times;</button>",form:"<form class='bootbox-form'></form>",inputs:{text:"<input class='bootbox-input bootbox-input-text form-control' autocomplete=off type=text />",textarea:"<textarea class='bootbox-input bootbox-input-textarea form-control'></textarea>",email:"<input class='bootbox-input bootbox-input-email form-control' autocomplete='off' type='email' />",select:"<select class='bootbox-input bootbox-input-select form-control'></select>",checkbox:"<div class='checkbox'><label><input class='bootbox-input bootbox-input-checkbox' type='checkbox' /></label></div>",date:"<input class='bootbox-input bootbox-input-date form-control' autocomplete=off type='date' />",time:"<input class='bootbox-input bootbox-input-time form-control' autocomplete=off type='time' />",number:"<input class='bootbox-input bootbox-input-number form-control' autocomplete=off type='number' />",password:"<input class='bootbox-input bootbox-input-password form-control' autocomplete='off' type='password' />"}},o={
// default language
locale:"en",
// show backdrop or not. Default to static so user has to interact with dialog
backdrop:"static",
// animate the modal in/out
animate:!0,
// additional class string applied to the top level dialog
className:null,
// whether or not to include a close button
closeButton:!0,
// show the dialog immediately by default
show:!0,
// dialog container
container:"body"},p={};p.alert=function(){var a;if(a=k("alert",["ok"],["message","callback"],arguments),a.callback&&!b.isFunction(a.callback))throw new Error("alert requires callback property to be a function when provided");/**
     * overrides
     */
return a.buttons.ok.callback=a.onEscape=function(){return b.isFunction(a.callback)?a.callback.call(this):!0},p.dialog(a)},p.confirm=function(){var a;
// confirm specific validation
if(a=k("confirm",["cancel","confirm"],["message","callback"],arguments),a.buttons.cancel.callback=a.onEscape=function(){return a.callback.call(this,!1)},a.buttons.confirm.callback=function(){return a.callback.call(this,!0)},!b.isFunction(a.callback))throw new Error("confirm requires a callback");return p.dialog(a)},p.prompt=function(){var a,d,e,f,h,i,k;
// prompt specific validation
if(f=b(n.form),d={className:"bootbox-prompt",buttons:l("cancel","confirm"),value:"",inputType:"text"},a=m(j(d,arguments,["title","callback"]),["cancel","confirm"]),i=a.show===c?!0:a.show,a.message=f,a.buttons.cancel.callback=a.onEscape=function(){return a.callback.call(this,null)},a.buttons.confirm.callback=function(){var c;switch(a.inputType){case"text":case"textarea":case"email":case"select":case"date":case"time":case"number":case"password":c=h.val();break;case"checkbox":var d=h.find("input:checked");c=[],g(d,function(a,d){c.push(b(d).val())})}return a.callback.call(this,c)},a.show=!1,!a.title)throw new Error("prompt requires a title");if(!b.isFunction(a.callback))throw new Error("prompt requires a callback");if(!n.inputs[a.inputType])throw new Error("invalid prompt type");switch(h=b(n.inputs[a.inputType]),a.inputType){case"text":case"textarea":case"email":case"date":case"time":case"number":case"password":h.val(a.value);break;case"select":var o={};if(k=a.inputOptions||[],!b.isArray(k))throw new Error("Please pass an array of input options");if(!k.length)throw new Error("prompt with select requires options");g(k,function(a,d){
// assume the element to attach to is the input...
var e=h;if(d.value===c||d.text===c)throw new Error("given options in wrong format");
// ... but override that element if this option sits in a group
d.group&&(
// initialise group if necessary
o[d.group]||(o[d.group]=b("<optgroup/>").attr("label",d.group)),e=o[d.group]),e.append("<option value='"+d.value+"'>"+d.text+"</option>")}),g(o,function(a,b){h.append(b)}),
// safe to set a select's value as per a normal input
h.val(a.value);break;case"checkbox":var q=b.isArray(a.value)?a.value:[a.value];if(k=a.inputOptions||[],!k.length)throw new Error("prompt with checkbox requires options");if(!k[0].value||!k[0].text)throw new Error("given options in wrong format");
// checkboxes have to nest within a containing element, so
// they break the rules a bit and we end up re-assigning
// our 'input' element to this container instead
h=b("<div/>"),g(k,function(c,d){var e=b(n.inputs[a.inputType]);e.find("input").attr("value",d.value),e.find("label").append(d.text),
// we've ensured values is an array so we can always iterate over it
g(q,function(a,b){b===d.value&&e.find("input").prop("checked",!0)}),h.append(e)})}
// @TODO provide an attributes option instead
// and simply map that as keys: vals
// now place it in our form
// clear the existing handler focusing the submit button...
// ...and replace it with one focusing our input, if possible
return a.placeholder&&h.attr("placeholder",a.placeholder),a.pattern&&h.attr("pattern",a.pattern),a.maxlength&&h.attr("maxlength",a.maxlength),f.append(h),f.on("submit",function(a){a.preventDefault(),
// Fix for SammyJS (or similar JS routing library) hijacking the form post.
a.stopPropagation(),
// @TODO can we actually click *the* button object instead?
// e.g. buttons.confirm.click() or similar
e.find(".btn-primary").click()}),e=p.dialog(a),e.off("shown.bs.modal"),e.on("shown.bs.modal",function(){h.focus()}),i===!0&&e.modal("show"),e},p.dialog=function(a){a=h(a);var d=b(n.dialog),f=d.find(".modal-dialog"),i=d.find(".modal-body"),j=a.buttons,k="",l={onEscape:a.onEscape};if(b.fn.modal===c)throw new Error("$.fn.modal is not defined; please double check you have included the Bootstrap JavaScript library. See http://getbootstrap.com/javascript/ for more details.");if(g(j,function(a,b){k+="<button data-bb-handler='"+a+"' type='button' class='btn "+b.className+"'>"+b.label+"</button>",l[a]=b.callback}),i.find(".bootbox-body").html(a.message),a.animate===!0&&d.addClass("fade"),a.className&&d.addClass(a.className),"large"===a.size?f.addClass("modal-lg"):"small"===a.size&&f.addClass("modal-sm"),a.title&&i.before(n.header),a.closeButton){var m=b(n.closeButton);a.title?d.find(".modal-header").prepend(m):m.css("margin-top","-10px").prependTo(i)}
// @TODO should we return the raw element here or should
// we wrap it in an object on which we can expose some neater
// methods, e.g. var d = bootbox.alert(); d.hide(); instead
// of d.modal("hide");
/*
    function BBDialog(elem) {
      this.elem = elem;
    }

    BBDialog.prototype = {
      hide: function() {
        return this.elem.modal("hide");
      },
      show: function() {
        return this.elem.modal("show");
      }
    };
    */
/**
     * Bootstrap event listeners; used handle extra
     * setup & teardown required after the underlying
     * modal has performed certain actions
     */
/*
    dialog.on("show.bs.modal", function() {
      // sadly this doesn't work; show is called *just* before
      // the backdrop is added so we'd need a setTimeout hack or
      // otherwise... leaving in as would be nice
      if (options.backdrop) {
        dialog.next(".modal-backdrop").addClass("bootbox-backdrop");
      }
    });
    */
/**
     * Bootbox event listeners; experimental and may not last
     * just an attempt to decouple some behaviours from their
     * respective triggers
     */
// A boolean true/false according to the Bootstrap docs
// should show a dialog the user can dismiss by clicking on
// the background.
// We always only ever pass static/false to the actual
// $.modal function because with `true` we can't trap
// this event (the .modal-backdrop swallows it)
// However, we still want to sort of respect true
// and invoke the escape mechanism instead
/**
     * Standard jQuery event listeners; used to handle user
     * interaction with our dialog
     */
// the remainder of this method simply deals with adding our
// dialogent to the DOM, augmenting it with Bootstrap's modal
// functionality and then giving the resulting object back
// to our caller
return a.title&&d.find(".modal-title").html(a.title),k.length&&(i.after(n.footer),d.find(".modal-footer").html(k)),d.on("hidden.bs.modal",function(a){
// ensure we don't accidentally intercept hidden events triggered
// by children of the current dialog. We shouldn't anymore now BS
// namespaces its events; but still worth doing
a.target===this&&d.remove()}),d.on("shown.bs.modal",function(){d.find(".btn-primary:first").focus()}),"static"!==a.backdrop&&d.on("click.dismiss.bs.modal",function(a){
// @NOTE: the target varies in >= 3.3.x releases since the modal backdrop
// moved *inside* the outer dialog rather than *alongside* it
d.children(".modal-backdrop").length&&(a.currentTarget=d.children(".modal-backdrop").get(0)),a.target===a.currentTarget&&d.trigger("escape.close.bb")}),d.on("escape.close.bb",function(a){l.onEscape&&e(a,d,l.onEscape)}),d.on("click",".modal-footer button",function(a){var c=b(this).data("bb-handler");e(a,d,l[c])}),d.on("click",".bootbox-close-button",function(a){
// onEscape might be falsy but that's fine; the fact is
// if the user has managed to click the close button we
// have to close the dialog, callback or not
e(a,d,l.onEscape)}),d.on("keyup",function(a){27===a.which&&d.trigger("escape.close.bb")}),b(a.container).append(d),d.modal({backdrop:a.backdrop?"static":!1,keyboard:!1,show:!1}),a.show&&d.modal("show"),d},p.setDefaults=function(){var a={};2===arguments.length?a[arguments[0]]=arguments[1]:a=arguments[0],b.extend(o,a)},p.hideAll=function(){return b(".bootbox").modal("hide"),p};/**
   * standard locales. Please add more according to ISO 639-1 standard. Multiple language variants are
   * unlikely to be required. If this gets too large it can be split out into separate JS files.
   */
var q={bg_BG:{OK:"Ок",CANCEL:"Отказ",CONFIRM:"Потвърждавам"},br:{OK:"OK",CANCEL:"Cancelar",CONFIRM:"Sim"},cs:{OK:"OK",CANCEL:"Zrušit",CONFIRM:"Potvrdit"},da:{OK:"OK",CANCEL:"Annuller",CONFIRM:"Accepter"},de:{OK:"OK",CANCEL:"Abbrechen",CONFIRM:"Akzeptieren"},el:{OK:"Εντάξει",CANCEL:"Ακύρωση",CONFIRM:"Επιβεβαίωση"},en:{OK:"OK",CANCEL:"Cancel",CONFIRM:"OK"},es:{OK:"OK",CANCEL:"Cancelar",CONFIRM:"Aceptar"},et:{OK:"OK",CANCEL:"Katkesta",CONFIRM:"OK"},fa:{OK:"قبول",CANCEL:"لغو",CONFIRM:"تایید"},fi:{OK:"OK",CANCEL:"Peruuta",CONFIRM:"OK"},fr:{OK:"OK",CANCEL:"Annuler",CONFIRM:"D'accord"},he:{OK:"אישור",CANCEL:"ביטול",CONFIRM:"אישור"},hu:{OK:"OK",CANCEL:"Mégsem",CONFIRM:"Megerősít"},hr:{OK:"OK",CANCEL:"Odustani",CONFIRM:"Potvrdi"},id:{OK:"OK",CANCEL:"Batal",CONFIRM:"OK"},it:{OK:"OK",CANCEL:"Annulla",CONFIRM:"Conferma"},ja:{OK:"OK",CANCEL:"キャンセル",CONFIRM:"確認"},lt:{OK:"Gerai",CANCEL:"Atšaukti",CONFIRM:"Patvirtinti"},lv:{OK:"Labi",CANCEL:"Atcelt",CONFIRM:"Apstiprināt"},nl:{OK:"OK",CANCEL:"Annuleren",CONFIRM:"Accepteren"},no:{OK:"OK",CANCEL:"Avbryt",CONFIRM:"OK"},pl:{OK:"OK",CANCEL:"Anuluj",CONFIRM:"Potwierdź"},pt:{OK:"OK",CANCEL:"Cancelar",CONFIRM:"Confirmar"},ru:{OK:"OK",CANCEL:"Отмена",CONFIRM:"Применить"},sq:{OK:"OK",CANCEL:"Anulo",CONFIRM:"Prano"},sv:{OK:"OK",CANCEL:"Avbryt",CONFIRM:"OK"},th:{OK:"ตกลง",CANCEL:"ยกเลิก",CONFIRM:"ยืนยัน"},tr:{OK:"Tamam",CANCEL:"İptal",CONFIRM:"Onayla"},zh_CN:{OK:"OK",CANCEL:"取消",CONFIRM:"确认"},zh_TW:{OK:"OK",CANCEL:"取消",CONFIRM:"確認"}};return p.addLocale=function(a,c){return b.each(["OK","CANCEL","CONFIRM"],function(a,b){if(!c[b])throw new Error("Please supply a translation for '"+b+"'")}),q[a]={OK:c.OK,CANCEL:c.CANCEL,CONFIRM:c.CONFIRM},p},p.removeLocale=function(a){return delete q[a],p},p.setLocale=function(a){return p.setDefaults("locale",a)},p.init=function(c){return a(c||b)},p});

/*! Magnific Popup - v1.1.0 - 2016-02-20
* http://dimsemenov.com/plugins/magnific-popup/
* Copyright (c) 2016 Dmitry Semenov; */
!function(a){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module. 
define(["jquery"],a):a("object"==typeof exports?require("jquery"):window.jQuery||window.Zepto)}(function(a){/*>>core*/
/**
 * 
 * Magnific Popup Core JS file
 * 
 */
/**
 * Private static constants
 */
var b,c,d,e,f,g,h="Close",i="BeforeClose",j="AfterClose",k="BeforeAppend",l="MarkupParse",m="Open",n="Change",o="mfp",p="."+o,q="mfp-ready",r="mfp-removing",s="mfp-prevent-close",// As we have only one instance of MagnificPopup object, we define it locally to not to use 'this'
t=function(){},u=!!window.jQuery,v=a(window),w=function(a,c){b.ev.on(o+a+p,c)},x=function(b,c,d,e){var f=document.createElement("div");return f.className="mfp-"+b,d&&(f.innerHTML=d),e?c&&c.appendChild(f):(f=a(f),c&&f.appendTo(c)),f},y=function(c,d){b.ev.triggerHandler(o+c,d),b.st.callbacks&&(c=c.charAt(0).toLowerCase()+c.slice(1),b.st.callbacks[c]&&b.st.callbacks[c].apply(b,a.isArray(d)?d:[d]))},z=function(c){return c===g&&b.currTemplate.closeBtn||(b.currTemplate.closeBtn=a(b.st.closeMarkup.replace("%title%",b.st.tClose)),g=c),b.currTemplate.closeBtn},
// Initialize Magnific Popup only when called at least once
A=function(){a.magnificPopup.instance||(b=new t,b.init(),a.magnificPopup.instance=b)},
// CSS transition detection, http://stackoverflow.com/questions/7264899/detect-css-transitions-using-javascript-and-without-modernizr
B=function(){var a=document.createElement("p").style,// 's' for style. better to create an element if body yet to exist
b=["ms","O","Moz","Webkit"];// 'v' for vendor
if(void 0!==a.transition)return!0;for(;b.length;)if(b.pop()+"Transition"in a)return!0;return!1};/**
 * Public functions
 */
t.prototype={constructor:t,/**
	 * Initializes Magnific Popup plugin. 
	 * This function is triggered only once when $.fn.magnificPopup or $.magnificPopup is executed
	 */
init:function(){var c=navigator.appVersion;b.isLowIE=b.isIE8=document.all&&!document.addEventListener,b.isAndroid=/android/gi.test(c),b.isIOS=/iphone|ipad|ipod/gi.test(c),b.supportsTransition=B(),
// We disable fixed positioned lightbox on devices that don't handle it nicely.
// If you know a better way of detecting this - let me know.
b.probablyMobile=b.isAndroid||b.isIOS||/(Opera Mini)|Kindle|webOS|BlackBerry|(Opera Mobi)|(Windows Phone)|IEMobile/i.test(navigator.userAgent),d=a(document),b.popupsCache={}},/**
	 * Opens popup
	 * @param  data [description]
	 */
open:function(c){var e;if(c.isObj===!1){
// convert jQuery collection to array to avoid conflicts later
b.items=c.items.toArray(),b.index=0;var g,h=c.items;for(e=0;e<h.length;e++)if(g=h[e],g.parsed&&(g=g.el[0]),g===c.el[0]){b.index=e;break}}else b.items=a.isArray(c.items)?c.items:[c.items],b.index=c.index||0;
// if popup is already opened - we just update the content
if(b.isOpen)return void b.updateItemHTML();b.types=[],f="",c.mainEl&&c.mainEl.length?b.ev=c.mainEl.eq(0):b.ev=d,c.key?(b.popupsCache[c.key]||(b.popupsCache[c.key]={}),b.currTemplate=b.popupsCache[c.key]):b.currTemplate={},b.st=a.extend(!0,{},a.magnificPopup.defaults,c),b.fixedContentPos="auto"===b.st.fixedContentPos?!b.probablyMobile:b.st.fixedContentPos,b.st.modal&&(b.st.closeOnContentClick=!1,b.st.closeOnBgClick=!1,b.st.showCloseBtn=!1,b.st.enableEscapeKey=!1),b.bgOverlay||(b.bgOverlay=x("bg").on("click"+p,function(){b.close()}),b.wrap=x("wrap").attr("tabindex",-1).on("click"+p,function(a){b._checkIfClose(a.target)&&b.close()}),b.container=x("container",b.wrap)),b.contentContainer=x("content"),b.st.preloader&&(b.preloader=x("preloader",b.container,b.st.tLoading));
// Initializing modules
var i=a.magnificPopup.modules;for(e=0;e<i.length;e++){var j=i[e];j=j.charAt(0).toUpperCase()+j.slice(1),b["init"+j].call(b)}y("BeforeOpen"),b.st.showCloseBtn&&(
// Close button
b.st.closeBtnInside?(w(l,function(a,b,c,d){c.close_replaceWith=z(d.type)}),f+=" mfp-close-btn-in"):b.wrap.append(z())),b.st.alignTop&&(f+=" mfp-align-top"),b.fixedContentPos?b.wrap.css({overflow:b.st.overflowY,overflowX:"hidden",overflowY:b.st.overflowY}):b.wrap.css({top:v.scrollTop(),position:"absolute"}),(b.st.fixedBgPos===!1||"auto"===b.st.fixedBgPos&&!b.fixedContentPos)&&b.bgOverlay.css({height:d.height(),position:"absolute"}),b.st.enableEscapeKey&&
// Close on ESC key
d.on("keyup"+p,function(a){27===a.keyCode&&b.close()}),v.on("resize"+p,function(){b.updateSize()}),b.st.closeOnContentClick||(f+=" mfp-auto-cursor"),f&&b.wrap.addClass(f);
// this triggers recalculation of layout, so we get it once to not to trigger twice
var k=b.wH=v.height(),n={};if(b.fixedContentPos&&b._hasScrollBar(k)){var o=b._getScrollbarSize();o&&(n.marginRight=o)}b.fixedContentPos&&(b.isIE7?
// ie7 double-scroll bug
a("body, html").css("overflow","hidden"):n.overflow="hidden");var r=b.st.mainClass;
// add content
// remove scrollbar, add margin e.t.c
// add everything to DOM
// Save last focused element
// Wait for next cycle to allow CSS transition
return b.isIE7&&(r+=" mfp-ie7"),r&&b._addClassToMFP(r),b.updateItemHTML(),y("BuildControls"),a("html").css(n),b.bgOverlay.add(b.wrap).prependTo(b.st.prependTo||a(document.body)),b._lastFocusedEl=document.activeElement,setTimeout(function(){b.content?(b._addClassToMFP(q),b._setFocus()):
// if content is not defined (not loaded e.t.c) we add class only for BG
b.bgOverlay.addClass(q),
// Trap the focus in popup
d.on("focusin"+p,b._onFocusIn)},16),b.isOpen=!0,b.updateSize(k),y(m),c},/**
	 * Closes the popup
	 */
close:function(){b.isOpen&&(y(i),b.isOpen=!1,
// for CSS3 animation
b.st.removalDelay&&!b.isLowIE&&b.supportsTransition?(b._addClassToMFP(r),setTimeout(function(){b._close()},b.st.removalDelay)):b._close())},/**
	 * Helper for close() function
	 */
_close:function(){y(h);var c=r+" "+q+" ";if(b.bgOverlay.detach(),b.wrap.detach(),b.container.empty(),b.st.mainClass&&(c+=b.st.mainClass+" "),b._removeClassFromMFP(c),b.fixedContentPos){var e={marginRight:""};b.isIE7?a("body, html").css("overflow",""):e.overflow="",a("html").css(e)}d.off("keyup"+p+" focusin"+p),b.ev.off(p),
// clean up DOM elements that aren't removed
b.wrap.attr("class","mfp-wrap").removeAttr("style"),b.bgOverlay.attr("class","mfp-bg"),b.container.attr("class","mfp-container"),
// remove close button from target element
!b.st.showCloseBtn||b.st.closeBtnInside&&b.currTemplate[b.currItem.type]!==!0||b.currTemplate.closeBtn&&b.currTemplate.closeBtn.detach(),b.st.autoFocusLast&&b._lastFocusedEl&&a(b._lastFocusedEl).focus(),b.currItem=null,b.content=null,b.currTemplate=null,b.prevHeight=0,y(j)},updateSize:function(a){if(b.isIOS){
// fixes iOS nav bars https://github.com/dimsemenov/Magnific-Popup/issues/2
var c=document.documentElement.clientWidth/window.innerWidth,d=window.innerHeight*c;b.wrap.css("height",d),b.wH=d}else b.wH=a||v.height();
// Fixes #84: popup incorrectly positioned with position:relative on body
b.fixedContentPos||b.wrap.css("height",b.wH),y("Resize")},/**
	 * Set content of popup based on current index
	 */
updateItemHTML:function(){var c=b.items[b.index];
// Detach and perform modifications
b.contentContainer.detach(),b.content&&b.content.detach(),c.parsed||(c=b.parseEl(b.index));var d=c.type;if(y("BeforeChange",[b.currItem?b.currItem.type:"",d]),
// BeforeChange event works like so:
// _mfpOn('BeforeChange', function(e, prevType, newType) { });
b.currItem=c,!b.currTemplate[d]){var f=b.st[d]?b.st[d].markup:!1;
// allows to modify markup
y("FirstMarkupParse",f),f?b.currTemplate[d]=a(f):b.currTemplate[d]=!0}e&&e!==c.type&&b.container.removeClass("mfp-"+e+"-holder");var g=b["get"+d.charAt(0).toUpperCase()+d.slice(1)](c,b.currTemplate[d]);b.appendContent(g,d),c.preloaded=!0,y(n,c),e=c.type,b.container.prepend(b.contentContainer),y("AfterChange")},/**
	 * Set HTML content of popup
	 */
appendContent:function(a,c){b.content=a,a?b.st.showCloseBtn&&b.st.closeBtnInside&&b.currTemplate[c]===!0?
// if there is no markup, we just append close button element inside
b.content.find(".mfp-close").length||b.content.append(z()):b.content=a:b.content="",y(k),b.container.addClass("mfp-"+c+"-holder"),b.contentContainer.append(b.content)},/**
	 * Creates Magnific Popup data object based on given data
	 * @param  {int} index Index of item to parse
	 */
parseEl:function(c){var d,e=b.items[c];if(e.tagName?e={el:a(e)}:(d=e.type,e={data:e,src:e.src}),e.el){
// check for 'mfp-TYPE' class
for(var f=b.types,g=0;g<f.length;g++)if(e.el.hasClass("mfp-"+f[g])){d=f[g];break}e.src=e.el.attr("data-mfp-src"),e.src||(e.src=e.el.attr("href"))}return e.type=d||b.st.type||"inline",e.index=c,e.parsed=!0,b.items[c]=e,y("ElementParse",e),b.items[c]},/**
	 * Initializes single popup or a group of popups
	 */
addGroup:function(a,c){var d=function(d){d.mfpEl=this,b._openClick(d,a,c)};c||(c={});var e="click.magnificPopup";c.mainEl=a,c.items?(c.isObj=!0,a.off(e).on(e,d)):(c.isObj=!1,c.delegate?a.off(e).on(e,c.delegate,d):(c.items=a,a.off(e).on(e,d)))},_openClick:function(c,d,e){var f=void 0!==e.midClick?e.midClick:a.magnificPopup.defaults.midClick;if(f||!(2===c.which||c.ctrlKey||c.metaKey||c.altKey||c.shiftKey)){var g=void 0!==e.disableOn?e.disableOn:a.magnificPopup.defaults.disableOn;if(g)if(a.isFunction(g)){if(!g.call(b))return!0}else// else it's number
if(v.width()<g)return!0;c.type&&(c.preventDefault(),
// This will prevent popup from closing if element is inside and popup is already opened
b.isOpen&&c.stopPropagation()),e.el=a(c.mfpEl),e.delegate&&(e.items=d.find(e.delegate)),b.open(e)}},/**
	 * Updates text on preloader
	 */
updateStatus:function(a,d){if(b.preloader){c!==a&&b.container.removeClass("mfp-s-"+c),d||"loading"!==a||(d=b.st.tLoading);var e={status:a,text:d};
// allows to modify status
y("UpdateStatus",e),a=e.status,d=e.text,b.preloader.html(d),b.preloader.find("a").on("click",function(a){a.stopImmediatePropagation()}),b.container.addClass("mfp-s-"+a),c=a}},/*
		"Private" helpers that aren't private at all
	 */
// Check to close popup or not
// "target" is an element that was clicked
_checkIfClose:function(c){if(!a(c).hasClass(s)){var d=b.st.closeOnContentClick,e=b.st.closeOnBgClick;if(d&&e)return!0;
// We close the popup if click is on close button or on preloader. Or if there is no content.
if(!b.content||a(c).hasClass("mfp-close")||b.preloader&&c===b.preloader[0])return!0;
// if click is outside the content
if(c===b.content[0]||a.contains(b.content[0],c)){if(d)return!0}else if(e&&a.contains(document,c))return!0;return!1}},_addClassToMFP:function(a){b.bgOverlay.addClass(a),b.wrap.addClass(a)},_removeClassFromMFP:function(a){this.bgOverlay.removeClass(a),b.wrap.removeClass(a)},_hasScrollBar:function(a){return(b.isIE7?d.height():document.body.scrollHeight)>(a||v.height())},_setFocus:function(){(b.st.focus?b.content.find(b.st.focus).eq(0):b.wrap).focus()},_onFocusIn:function(c){return c.target===b.wrap[0]||a.contains(b.wrap[0],c.target)?void 0:(b._setFocus(),!1)},_parseMarkup:function(b,c,d){var e;d.data&&(c=a.extend(d.data,c)),y(l,[b,c,d]),a.each(c,function(c,d){if(void 0===d||d===!1)return!0;if(e=c.split("_"),e.length>1){var f=b.find(p+"-"+e[0]);if(f.length>0){var g=e[1];"replaceWith"===g?f[0]!==d[0]&&f.replaceWith(d):"img"===g?f.is("img")?f.attr("src",d):f.replaceWith(a("<img>").attr("src",d).attr("class",f.attr("class"))):f.attr(e[1],d)}}else b.find(p+"-"+c).html(d)})},_getScrollbarSize:function(){
// thx David
if(void 0===b.scrollbarSize){var a=document.createElement("div");a.style.cssText="width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;",document.body.appendChild(a),b.scrollbarSize=a.offsetWidth-a.clientWidth,document.body.removeChild(a)}return b.scrollbarSize}},/* MagnificPopup core prototype end */
/**
 * Public static functions
 */
a.magnificPopup={instance:null,proto:t.prototype,modules:[],open:function(b,c){return A(),b=b?a.extend(!0,{},b):{},b.isObj=!0,b.index=c||0,this.instance.open(b)},close:function(){return a.magnificPopup.instance&&a.magnificPopup.instance.close()},registerModule:function(b,c){c.options&&(a.magnificPopup.defaults[b]=c.options),a.extend(this.proto,c.proto),this.modules.push(b)},defaults:{
// Info about options is in docs:
// http://dimsemenov.com/plugins/magnific-popup/documentation.html#options
disableOn:0,key:null,midClick:!1,mainClass:"",preloader:!0,focus:"",// CSS selector of input to focus after popup is opened
closeOnContentClick:!1,closeOnBgClick:!0,closeBtnInside:!0,showCloseBtn:!0,enableEscapeKey:!0,modal:!1,alignTop:!1,removalDelay:0,prependTo:null,fixedContentPos:"auto",fixedBgPos:"auto",overflowY:"auto",closeMarkup:'<button title="%title%" type="button" class="mfp-close">&#215;</button>',tClose:"Close (Esc)",tLoading:"Loading...",autoFocusLast:!0}},a.fn.magnificPopup=function(c){A();var d=a(this);
// We call some API method of first param is a string
if("string"==typeof c)if("open"===c){var e,f=u?d.data("magnificPopup"):d[0].magnificPopup,g=parseInt(arguments[1],10)||0;f.items?e=f.items[g]:(e=d,f.delegate&&(e=e.find(f.delegate)),e=e.eq(g)),b._openClick({mfpEl:e},d,f)}else b.isOpen&&b[c].apply(b,Array.prototype.slice.call(arguments,1));else c=a.extend(!0,{},c),u?d.data("magnificPopup",c):d[0].magnificPopup=c,b.addGroup(d,c);return d};/*>>core*/
/*>>inline*/
var C,D,E,F="inline",G=function(){E&&(D.after(E.addClass(C)).detach(),E=null)};a.magnificPopup.registerModule(F,{options:{hiddenClass:"hide",// will be appended with `mfp-` prefix
markup:"",tNotFound:"Content not found"},proto:{initInline:function(){b.types.push(F),w(h+"."+F,function(){G()})},getInline:function(c,d){if(G(),c.src){var e=b.st.inline,f=a(c.src);if(f.length){
// If target element has parent - we replace it with placeholder and put it back after popup is closed
var g=f[0].parentNode;g&&g.tagName&&(D||(C=e.hiddenClass,D=x(C),C="mfp-"+C),
// replace target inline element with placeholder
E=f.after(D).detach().removeClass(C)),b.updateStatus("ready")}else b.updateStatus("error",e.tNotFound),f=a("<div>");return c.inlineElement=f,f}return b.updateStatus("ready"),b._parseMarkup(d,{},c),d}}});/*>>inline*/
/*>>ajax*/
var H,I="ajax",J=function(){H&&a(document.body).removeClass(H)},K=function(){J(),b.req&&b.req.abort()};a.magnificPopup.registerModule(I,{options:{settings:null,cursor:"mfp-ajax-cur",tError:'<a href="%url%">The content</a> could not be loaded.'},proto:{initAjax:function(){b.types.push(I),H=b.st.ajax.cursor,w(h+"."+I,K),w("BeforeChange."+I,K)},getAjax:function(c){H&&a(document.body).addClass(H),b.updateStatus("loading");var d=a.extend({url:c.src,success:function(d,e,f){var g={data:d,xhr:f};y("ParseAjax",g),b.appendContent(a(g.data),I),c.finished=!0,J(),b._setFocus(),setTimeout(function(){b.wrap.addClass(q)},16),b.updateStatus("ready"),y("AjaxContentAdded")},error:function(){J(),c.finished=c.loadError=!0,b.updateStatus("error",b.st.ajax.tError.replace("%url%",c.src))}},b.st.ajax.settings);return b.req=a.ajax(d),""}}});/*>>ajax*/
/*>>image*/
var L,M=function(c){if(c.data&&void 0!==c.data.title)return c.data.title;var d=b.st.image.titleSrc;if(d){if(a.isFunction(d))return d.call(b,c);if(c.el)return c.el.attr(d)||""}return""};a.magnificPopup.registerModule("image",{options:{markup:'<div class="mfp-figure"><div class="mfp-close"></div><figure><div class="mfp-img"></div><figcaption><div class="mfp-bottom-bar"><div class="mfp-title"></div><div class="mfp-counter"></div></div></figcaption></figure></div>',cursor:"mfp-zoom-out-cur",titleSrc:"title",verticalFit:!0,tError:'<a href="%url%">The image</a> could not be loaded.'},proto:{initImage:function(){var c=b.st.image,d=".image";b.types.push("image"),w(m+d,function(){"image"===b.currItem.type&&c.cursor&&a(document.body).addClass(c.cursor)}),w(h+d,function(){c.cursor&&a(document.body).removeClass(c.cursor),v.off("resize"+p)}),w("Resize"+d,b.resizeImage),b.isLowIE&&w("AfterChange",b.resizeImage)},resizeImage:function(){var a=b.currItem;if(a&&a.img&&b.st.image.verticalFit){var c=0;
// fix box-sizing in ie7/8
b.isLowIE&&(c=parseInt(a.img.css("padding-top"),10)+parseInt(a.img.css("padding-bottom"),10)),a.img.css("max-height",b.wH-c)}},_onImageHasSize:function(a){a.img&&(a.hasSize=!0,L&&clearInterval(L),a.isCheckingImgSize=!1,y("ImageHasSize",a),a.imgHidden&&(b.content&&b.content.removeClass("mfp-loading"),a.imgHidden=!1))},/**
		 * Function that loops until the image has size to display elements that rely on it asap
		 */
findImageSize:function(a){var c=0,d=a.img[0],e=function(f){L&&clearInterval(L),
// decelerating interval that checks for size of an image
L=setInterval(function(){return d.naturalWidth>0?void b._onImageHasSize(a):(c>200&&clearInterval(L),c++,void(3===c?e(10):40===c?e(50):100===c&&e(500)))},f)};e(1)},getImage:function(c,d){var e=0,
// image load complete handler
f=function(){c&&(c.img[0].complete?(c.img.off(".mfploader"),c===b.currItem&&(b._onImageHasSize(c),b.updateStatus("ready")),c.hasSize=!0,c.loaded=!0,y("ImageLoadComplete")):(
// if image complete check fails 200 times (20 sec), we assume that there was an error.
e++,200>e?setTimeout(f,100):g()))},
// image error handler
g=function(){c&&(c.img.off(".mfploader"),c===b.currItem&&(b._onImageHasSize(c),b.updateStatus("error",h.tError.replace("%url%",c.src))),c.hasSize=!0,c.loaded=!0,c.loadError=!0)},h=b.st.image,i=d.find(".mfp-img");if(i.length){var j=document.createElement("img");j.className="mfp-img",c.el&&c.el.find("img").length&&(j.alt=c.el.find("img").attr("alt")),c.img=a(j).on("load.mfploader",f).on("error.mfploader",g),j.src=c.src,
// without clone() "error" event is not firing when IMG is replaced by new IMG
// TODO: find a way to avoid such cloning
i.is("img")&&(c.img=c.img.clone()),j=c.img[0],j.naturalWidth>0?c.hasSize=!0:j.width||(c.hasSize=!1)}return b._parseMarkup(d,{title:M(c),img_replaceWith:c.img},c),b.resizeImage(),c.hasSize?(L&&clearInterval(L),c.loadError?(d.addClass("mfp-loading"),b.updateStatus("error",h.tError.replace("%url%",c.src))):(d.removeClass("mfp-loading"),b.updateStatus("ready")),d):(b.updateStatus("loading"),c.loading=!0,c.hasSize||(c.imgHidden=!0,d.addClass("mfp-loading"),b.findImageSize(c)),d)}}});/*>>image*/
/*>>zoom*/
var N,O=function(){return void 0===N&&(N=void 0!==document.createElement("p").style.MozTransform),N};a.magnificPopup.registerModule("zoom",{options:{enabled:!1,easing:"ease-in-out",duration:300,opener:function(a){return a.is("img")?a:a.find("img")}},proto:{initZoom:function(){var a,c=b.st.zoom,d=".zoom";if(c.enabled&&b.supportsTransition){var e,f,g=c.duration,j=function(a){var b=a.clone().removeAttr("style").removeAttr("class").addClass("mfp-animated-image"),d="all "+c.duration/1e3+"s "+c.easing,e={position:"fixed",zIndex:9999,left:0,top:0,"-webkit-backface-visibility":"hidden"},f="transition";return e["-webkit-"+f]=e["-moz-"+f]=e["-o-"+f]=e[f]=d,b.css(e),b},k=function(){b.content.css("visibility","visible")};w("BuildControls"+d,function(){if(b._allowZoom()){if(clearTimeout(e),b.content.css("visibility","hidden"),a=b._getItemToZoom(),!a)return void k();f=j(a),f.css(b._getOffset()),b.wrap.append(f),e=setTimeout(function(){f.css(b._getOffset(!0)),e=setTimeout(function(){k(),setTimeout(function(){f.remove(),a=f=null,y("ZoomAnimationEnded")},16)},g)},16)}}),w(i+d,function(){if(b._allowZoom()){if(clearTimeout(e),b.st.removalDelay=g,!a){if(a=b._getItemToZoom(),!a)return;f=j(a)}f.css(b._getOffset(!0)),b.wrap.append(f),b.content.css("visibility","hidden"),setTimeout(function(){f.css(b._getOffset())},16)}}),w(h+d,function(){b._allowZoom()&&(k(),f&&f.remove(),a=null)})}},_allowZoom:function(){return"image"===b.currItem.type},_getItemToZoom:function(){return b.currItem.hasSize?b.currItem.img:!1},
// Get element postion relative to viewport
_getOffset:function(c){var d;d=c?b.currItem.img:b.st.zoom.opener(b.currItem.el||b.currItem);var e=d.offset(),f=parseInt(d.css("padding-top"),10),g=parseInt(d.css("padding-bottom"),10);e.top-=a(window).scrollTop()-f;/*

			Animating left + top + width/height looks glitchy in Firefox, but perfect in Chrome. And vice-versa.

			 */
var h={width:d.width(),
// fix Zepto height+padding issue
height:(u?d.innerHeight():d[0].offsetHeight)-g-f};
// I hate to do this, but there is no another option
return O()?h["-moz-transform"]=h.transform="translate("+e.left+"px,"+e.top+"px)":(h.left=e.left,h.top=e.top),h}}});/*>>zoom*/
/*>>iframe*/
var P="iframe",Q="//about:blank",R=function(a){if(b.currTemplate[P]){var c=b.currTemplate[P].find("iframe");c.length&&(
// reset src after the popup is closed to avoid "video keeps playing after popup is closed" bug
a||(c[0].src=Q),
// IE8 black screen bug fix
b.isIE8&&c.css("display",a?"block":"none"))}};a.magnificPopup.registerModule(P,{options:{markup:'<div class="mfp-iframe-scaler"><div class="mfp-close"></div><iframe class="mfp-iframe" src="//about:blank" frameborder="0" allowfullscreen></iframe></div>',srcAction:"iframe_src",
// we don't care and support only one default type of URL by default
patterns:{youtube:{index:"youtube.com",id:"v=",src:"//www.youtube.com/embed/%id%?autoplay=1"},vimeo:{index:"vimeo.com/",id:"/",src:"//player.vimeo.com/video/%id%?autoplay=1"},gmaps:{index:"//maps.google.",src:"%id%&output=embed"}}},proto:{initIframe:function(){b.types.push(P),w("BeforeChange",function(a,b,c){b!==c&&(b===P?R():c===P&&R(!0))}),w(h+"."+P,function(){R()})},getIframe:function(c,d){var e=c.src,f=b.st.iframe;a.each(f.patterns,function(){return e.indexOf(this.index)>-1?(this.id&&(e="string"==typeof this.id?e.substr(e.lastIndexOf(this.id)+this.id.length,e.length):this.id.call(this,e)),e=this.src.replace("%id%",e),!1):void 0});var g={};return f.srcAction&&(g[f.srcAction]=e),b._parseMarkup(d,g,c),b.updateStatus("ready"),d}}});/*>>iframe*/
/*>>gallery*/
/**
 * Get looped index depending on number of slides
 */
var S=function(a){var c=b.items.length;return a>c-1?a-c:0>a?c+a:a},T=function(a,b,c){return a.replace(/%curr%/gi,b+1).replace(/%total%/gi,c)};a.magnificPopup.registerModule("gallery",{options:{enabled:!1,arrowMarkup:'<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',preload:[0,2],navigateByImgClick:!0,arrows:!0,tPrev:"Previous (Left arrow key)",tNext:"Next (Right arrow key)",tCounter:"%curr% of %total%"},proto:{initGallery:function(){var c=b.st.gallery,e=".mfp-gallery";// true - next, false - prev
// true - next, false - prev
return b.direction=!0,c&&c.enabled?(f+=" mfp-gallery",w(m+e,function(){c.navigateByImgClick&&b.wrap.on("click"+e,".mfp-img",function(){return b.items.length>1?(b.next(),!1):void 0}),d.on("keydown"+e,function(a){37===a.keyCode?b.prev():39===a.keyCode&&b.next()})}),w("UpdateStatus"+e,function(a,c){c.text&&(c.text=T(c.text,b.currItem.index,b.items.length))}),w(l+e,function(a,d,e,f){var g=b.items.length;e.counter=g>1?T(c.tCounter,f.index,g):""}),w("BuildControls"+e,function(){if(b.items.length>1&&c.arrows&&!b.arrowLeft){var d=c.arrowMarkup,e=b.arrowLeft=a(d.replace(/%title%/gi,c.tPrev).replace(/%dir%/gi,"left")).addClass(s),f=b.arrowRight=a(d.replace(/%title%/gi,c.tNext).replace(/%dir%/gi,"right")).addClass(s);e.click(function(){b.prev()}),f.click(function(){b.next()}),b.container.append(e.add(f))}}),w(n+e,function(){b._preloadTimeout&&clearTimeout(b._preloadTimeout),b._preloadTimeout=setTimeout(function(){b.preloadNearbyImages(),b._preloadTimeout=null},16)}),void w(h+e,function(){d.off(e),b.wrap.off("click"+e),b.arrowRight=b.arrowLeft=null})):!1},next:function(){b.direction=!0,b.index=S(b.index+1),b.updateItemHTML()},prev:function(){b.direction=!1,b.index=S(b.index-1),b.updateItemHTML()},goTo:function(a){b.direction=a>=b.index,b.index=a,b.updateItemHTML()},preloadNearbyImages:function(){var a,c=b.st.gallery.preload,d=Math.min(c[0],b.items.length),e=Math.min(c[1],b.items.length);for(a=1;a<=(b.direction?e:d);a++)b._preloadItem(b.index+a);for(a=1;a<=(b.direction?d:e);a++)b._preloadItem(b.index-a)},_preloadItem:function(c){if(c=S(c),!b.items[c].preloaded){var d=b.items[c];d.parsed||(d=b.parseEl(c)),y("LazyLoad",d),"image"===d.type&&(d.img=a('<img class="mfp-img" />').on("load.mfploader",function(){d.hasSize=!0}).on("error.mfploader",function(){d.hasSize=!0,d.loadError=!0,y("LazyLoadError",d)}).attr("src",d.src)),d.preloaded=!0}}}});/*>>gallery*/
/*>>retina*/
var U="retina";a.magnificPopup.registerModule(U,{options:{replaceSrc:function(a){return a.src.replace(/\.\w+$/,function(a){return"@2x"+a})},ratio:1},proto:{initRetina:function(){if(window.devicePixelRatio>1){var a=b.st.retina,c=a.ratio;c=isNaN(c)?c():c,c>1&&(w("ImageHasSize."+U,function(a,b){b.img.css({"max-width":b.img[0].naturalWidth/c,width:"100%"})}),w("ElementParse."+U,function(b,d){d.src=a.replaceSrc(d,c)}))}}}}),/*>>retina*/
A()});

/*! jQuery UI - v1.11.4 - 2015-05-25
* http://jqueryui.com
* Includes: core.js, widget.js, mouse.js, position.js, sortable.js, autocomplete.js, datepicker.js, menu.js
* Copyright 2015 jQuery Foundation and other contributors; Licensed MIT */

(function(e){"function"==typeof define&&define.amd?define(["jquery"],e):e(jQuery)})(function(e){function t(t,s){var n,a,o,r=t.nodeName.toLowerCase();return"area"===r?(n=t.parentNode,a=n.name,t.href&&a&&"map"===n.nodeName.toLowerCase()?(o=e("img[usemap='#"+a+"']")[0],!!o&&i(o)):!1):(/^(input|select|textarea|button|object)$/.test(r)?!t.disabled:"a"===r?t.href||s:s)&&i(t)}function i(t){return e.expr.filters.visible(t)&&!e(t).parents().addBack().filter(function(){return"hidden"===e.css(this,"visibility")}).length}function s(e){for(var t,i;e.length&&e[0]!==document;){if(t=e.css("position"),("absolute"===t||"relative"===t||"fixed"===t)&&(i=parseInt(e.css("zIndex"),10),!isNaN(i)&&0!==i))return i;e=e.parent()}return 0}function n(){this._curInst=null,this._keyEvent=!1,this._disabledInputs=[],this._datepickerShowing=!1,this._inDialog=!1,this._mainDivId="ui-datepicker-div",this._inlineClass="ui-datepicker-inline",this._appendClass="ui-datepicker-append",this._triggerClass="ui-datepicker-trigger",this._dialogClass="ui-datepicker-dialog",this._disableClass="ui-datepicker-disabled",this._unselectableClass="ui-datepicker-unselectable",this._currentClass="ui-datepicker-current-day",this._dayOverClass="ui-datepicker-days-cell-over",this.regional=[],this.regional[""]={closeText:"Done",prevText:"Prev",nextText:"Next",currentText:"Today",monthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],dayNames:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],dayNamesShort:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],dayNamesMin:["Su","Mo","Tu","We","Th","Fr","Sa"],weekHeader:"Wk",dateFormat:"mm/dd/yy",firstDay:0,isRTL:!1,showMonthAfterYear:!1,yearSuffix:""},this._defaults={showOn:"focus",showAnim:"fadeIn",showOptions:{},defaultDate:null,appendText:"",buttonText:"...",buttonImage:"",buttonImageOnly:!1,hideIfNoPrevNext:!1,navigationAsDateFormat:!1,gotoCurrent:!1,changeMonth:!1,changeYear:!1,yearRange:"c-10:c+10",showOtherMonths:!1,selectOtherMonths:!1,showWeek:!1,calculateWeek:this.iso8601Week,shortYearCutoff:"+10",minDate:null,maxDate:null,duration:"fast",beforeShowDay:null,beforeShow:null,onSelect:null,onChangeMonthYear:null,onClose:null,numberOfMonths:1,showCurrentAtPos:0,stepMonths:1,stepBigMonths:12,altField:"",altFormat:"",constrainInput:!0,showButtonPanel:!1,autoSize:!1,disabled:!1},e.extend(this._defaults,this.regional[""]),this.regional.en=e.extend(!0,{},this.regional[""]),this.regional["en-US"]=e.extend(!0,{},this.regional.en),this.dpDiv=a(e("<div id='"+this._mainDivId+"' class='ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all'></div>"))}function a(t){var i="button, .ui-datepicker-prev, .ui-datepicker-next, .ui-datepicker-calendar td a";return t.delegate(i,"mouseout",function(){e(this).removeClass("ui-state-hover"),-1!==this.className.indexOf("ui-datepicker-prev")&&e(this).removeClass("ui-datepicker-prev-hover"),-1!==this.className.indexOf("ui-datepicker-next")&&e(this).removeClass("ui-datepicker-next-hover")}).delegate(i,"mouseover",o)}function o(){e.datepicker._isDisabledDatepicker(d.inline?d.dpDiv.parent()[0]:d.input[0])||(e(this).parents(".ui-datepicker-calendar").find("a").removeClass("ui-state-hover"),e(this).addClass("ui-state-hover"),-1!==this.className.indexOf("ui-datepicker-prev")&&e(this).addClass("ui-datepicker-prev-hover"),-1!==this.className.indexOf("ui-datepicker-next")&&e(this).addClass("ui-datepicker-next-hover"))}function r(t,i){e.extend(t,i);for(var s in i)null==i[s]&&(t[s]=i[s]);return t}e.ui=e.ui||{},e.extend(e.ui,{version:"1.11.4",keyCode:{BACKSPACE:8,COMMA:188,DELETE:46,DOWN:40,END:35,ENTER:13,ESCAPE:27,HOME:36,LEFT:37,PAGE_DOWN:34,PAGE_UP:33,PERIOD:190,RIGHT:39,SPACE:32,TAB:9,UP:38}}),e.fn.extend({scrollParent:function(t){var i=this.css("position"),s="absolute"===i,n=t?/(auto|scroll|hidden)/:/(auto|scroll)/,a=this.parents().filter(function(){var t=e(this);return s&&"static"===t.css("position")?!1:n.test(t.css("overflow")+t.css("overflow-y")+t.css("overflow-x"))}).eq(0);return"fixed"!==i&&a.length?a:e(this[0].ownerDocument||document)},uniqueId:function(){var e=0;return function(){return this.each(function(){this.id||(this.id="ui-id-"+ ++e)})}}(),removeUniqueId:function(){return this.each(function(){/^ui-id-\d+$/.test(this.id)&&e(this).removeAttr("id")})}}),e.extend(e.expr[":"],{data:e.expr.createPseudo?e.expr.createPseudo(function(t){return function(i){return!!e.data(i,t)}}):function(t,i,s){return!!e.data(t,s[3])},focusable:function(i){return t(i,!isNaN(e.attr(i,"tabindex")))},tabbable:function(i){var s=e.attr(i,"tabindex"),n=isNaN(s);return(n||s>=0)&&t(i,!n)}}),e("<a>").outerWidth(1).jquery||e.each(["Width","Height"],function(t,i){function s(t,i,s,a){return e.each(n,function(){i-=parseFloat(e.css(t,"padding"+this))||0,s&&(i-=parseFloat(e.css(t,"border"+this+"Width"))||0),a&&(i-=parseFloat(e.css(t,"margin"+this))||0)}),i}var n="Width"===i?["Left","Right"]:["Top","Bottom"],a=i.toLowerCase(),o={innerWidth:e.fn.innerWidth,innerHeight:e.fn.innerHeight,outerWidth:e.fn.outerWidth,outerHeight:e.fn.outerHeight};e.fn["inner"+i]=function(t){return void 0===t?o["inner"+i].call(this):this.each(function(){e(this).css(a,s(this,t)+"px")})},e.fn["outer"+i]=function(t,n){return"number"!=typeof t?o["outer"+i].call(this,t):this.each(function(){e(this).css(a,s(this,t,!0,n)+"px")})}}),e.fn.addBack||(e.fn.addBack=function(e){return this.add(null==e?this.prevObject:this.prevObject.filter(e))}),e("<a>").data("a-b","a").removeData("a-b").data("a-b")&&(e.fn.removeData=function(t){return function(i){return arguments.length?t.call(this,e.camelCase(i)):t.call(this)}}(e.fn.removeData)),e.ui.ie=!!/msie [\w.]+/.exec(navigator.userAgent.toLowerCase()),e.fn.extend({focus:function(t){return function(i,s){return"number"==typeof i?this.each(function(){var t=this;setTimeout(function(){e(t).focus(),s&&s.call(t)},i)}):t.apply(this,arguments)}}(e.fn.focus),disableSelection:function(){var e="onselectstart"in document.createElement("div")?"selectstart":"mousedown";return function(){return this.bind(e+".ui-disableSelection",function(e){e.preventDefault()})}}(),enableSelection:function(){return this.unbind(".ui-disableSelection")},zIndex:function(t){if(void 0!==t)return this.css("zIndex",t);if(this.length)for(var i,s,n=e(this[0]);n.length&&n[0]!==document;){if(i=n.css("position"),("absolute"===i||"relative"===i||"fixed"===i)&&(s=parseInt(n.css("zIndex"),10),!isNaN(s)&&0!==s))return s;n=n.parent()}return 0}}),e.ui.plugin={add:function(t,i,s){var n,a=e.ui[t].prototype;for(n in s)a.plugins[n]=a.plugins[n]||[],a.plugins[n].push([i,s[n]])},call:function(e,t,i,s){var n,a=e.plugins[t];if(a&&(s||e.element[0].parentNode&&11!==e.element[0].parentNode.nodeType))for(n=0;a.length>n;n++)e.options[a[n][0]]&&a[n][1].apply(e.element,i)}};var h=0,l=Array.prototype.slice;e.cleanData=function(t){return function(i){var s,n,a;for(a=0;null!=(n=i[a]);a++)try{s=e._data(n,"events"),s&&s.remove&&e(n).triggerHandler("remove")}catch(o){}t(i)}}(e.cleanData),e.widget=function(t,i,s){var n,a,o,r,h={},l=t.split(".")[0];return t=t.split(".")[1],n=l+"-"+t,s||(s=i,i=e.Widget),e.expr[":"][n.toLowerCase()]=function(t){return!!e.data(t,n)},e[l]=e[l]||{},a=e[l][t],o=e[l][t]=function(e,t){return this._createWidget?(arguments.length&&this._createWidget(e,t),void 0):new o(e,t)},e.extend(o,a,{version:s.version,_proto:e.extend({},s),_childConstructors:[]}),r=new i,r.options=e.widget.extend({},r.options),e.each(s,function(t,s){return e.isFunction(s)?(h[t]=function(){var e=function(){return i.prototype[t].apply(this,arguments)},n=function(e){return i.prototype[t].apply(this,e)};return function(){var t,i=this._super,a=this._superApply;return this._super=e,this._superApply=n,t=s.apply(this,arguments),this._super=i,this._superApply=a,t}}(),void 0):(h[t]=s,void 0)}),o.prototype=e.widget.extend(r,{widgetEventPrefix:a?r.widgetEventPrefix||t:t},h,{constructor:o,namespace:l,widgetName:t,widgetFullName:n}),a?(e.each(a._childConstructors,function(t,i){var s=i.prototype;e.widget(s.namespace+"."+s.widgetName,o,i._proto)}),delete a._childConstructors):i._childConstructors.push(o),e.widget.bridge(t,o),o},e.widget.extend=function(t){for(var i,s,n=l.call(arguments,1),a=0,o=n.length;o>a;a++)for(i in n[a])s=n[a][i],n[a].hasOwnProperty(i)&&void 0!==s&&(t[i]=e.isPlainObject(s)?e.isPlainObject(t[i])?e.widget.extend({},t[i],s):e.widget.extend({},s):s);return t},e.widget.bridge=function(t,i){var s=i.prototype.widgetFullName||t;e.fn[t]=function(n){var a="string"==typeof n,o=l.call(arguments,1),r=this;return a?this.each(function(){var i,a=e.data(this,s);return"instance"===n?(r=a,!1):a?e.isFunction(a[n])&&"_"!==n.charAt(0)?(i=a[n].apply(a,o),i!==a&&void 0!==i?(r=i&&i.jquery?r.pushStack(i.get()):i,!1):void 0):e.error("no such method '"+n+"' for "+t+" widget instance"):e.error("cannot call methods on "+t+" prior to initialization; "+"attempted to call method '"+n+"'")}):(o.length&&(n=e.widget.extend.apply(null,[n].concat(o))),this.each(function(){var t=e.data(this,s);t?(t.option(n||{}),t._init&&t._init()):e.data(this,s,new i(n,this))})),r}},e.Widget=function(){},e.Widget._childConstructors=[],e.Widget.prototype={widgetName:"widget",widgetEventPrefix:"",defaultElement:"<div>",options:{disabled:!1,create:null},_createWidget:function(t,i){i=e(i||this.defaultElement||this)[0],this.element=e(i),this.uuid=h++,this.eventNamespace="."+this.widgetName+this.uuid,this.bindings=e(),this.hoverable=e(),this.focusable=e(),i!==this&&(e.data(i,this.widgetFullName,this),this._on(!0,this.element,{remove:function(e){e.target===i&&this.destroy()}}),this.document=e(i.style?i.ownerDocument:i.document||i),this.window=e(this.document[0].defaultView||this.document[0].parentWindow)),this.options=e.widget.extend({},this.options,this._getCreateOptions(),t),this._create(),this._trigger("create",null,this._getCreateEventData()),this._init()},_getCreateOptions:e.noop,_getCreateEventData:e.noop,_create:e.noop,_init:e.noop,destroy:function(){this._destroy(),this.element.unbind(this.eventNamespace).removeData(this.widgetFullName).removeData(e.camelCase(this.widgetFullName)),this.widget().unbind(this.eventNamespace).removeAttr("aria-disabled").removeClass(this.widgetFullName+"-disabled "+"ui-state-disabled"),this.bindings.unbind(this.eventNamespace),this.hoverable.removeClass("ui-state-hover"),this.focusable.removeClass("ui-state-focus")},_destroy:e.noop,widget:function(){return this.element},option:function(t,i){var s,n,a,o=t;if(0===arguments.length)return e.widget.extend({},this.options);if("string"==typeof t)if(o={},s=t.split("."),t=s.shift(),s.length){for(n=o[t]=e.widget.extend({},this.options[t]),a=0;s.length-1>a;a++)n[s[a]]=n[s[a]]||{},n=n[s[a]];if(t=s.pop(),1===arguments.length)return void 0===n[t]?null:n[t];n[t]=i}else{if(1===arguments.length)return void 0===this.options[t]?null:this.options[t];o[t]=i}return this._setOptions(o),this},_setOptions:function(e){var t;for(t in e)this._setOption(t,e[t]);return this},_setOption:function(e,t){return this.options[e]=t,"disabled"===e&&(this.widget().toggleClass(this.widgetFullName+"-disabled",!!t),t&&(this.hoverable.removeClass("ui-state-hover"),this.focusable.removeClass("ui-state-focus"))),this},enable:function(){return this._setOptions({disabled:!1})},disable:function(){return this._setOptions({disabled:!0})},_on:function(t,i,s){var n,a=this;"boolean"!=typeof t&&(s=i,i=t,t=!1),s?(i=n=e(i),this.bindings=this.bindings.add(i)):(s=i,i=this.element,n=this.widget()),e.each(s,function(s,o){function r(){return t||a.options.disabled!==!0&&!e(this).hasClass("ui-state-disabled")?("string"==typeof o?a[o]:o).apply(a,arguments):void 0}"string"!=typeof o&&(r.guid=o.guid=o.guid||r.guid||e.guid++);var h=s.match(/^([\w:-]*)\s*(.*)$/),l=h[1]+a.eventNamespace,u=h[2];u?n.delegate(u,l,r):i.bind(l,r)})},_off:function(t,i){i=(i||"").split(" ").join(this.eventNamespace+" ")+this.eventNamespace,t.unbind(i).undelegate(i),this.bindings=e(this.bindings.not(t).get()),this.focusable=e(this.focusable.not(t).get()),this.hoverable=e(this.hoverable.not(t).get())},_delay:function(e,t){function i(){return("string"==typeof e?s[e]:e).apply(s,arguments)}var s=this;return setTimeout(i,t||0)},_hoverable:function(t){this.hoverable=this.hoverable.add(t),this._on(t,{mouseenter:function(t){e(t.currentTarget).addClass("ui-state-hover")},mouseleave:function(t){e(t.currentTarget).removeClass("ui-state-hover")}})},_focusable:function(t){this.focusable=this.focusable.add(t),this._on(t,{focusin:function(t){e(t.currentTarget).addClass("ui-state-focus")},focusout:function(t){e(t.currentTarget).removeClass("ui-state-focus")}})},_trigger:function(t,i,s){var n,a,o=this.options[t];if(s=s||{},i=e.Event(i),i.type=(t===this.widgetEventPrefix?t:this.widgetEventPrefix+t).toLowerCase(),i.target=this.element[0],a=i.originalEvent)for(n in a)n in i||(i[n]=a[n]);return this.element.trigger(i,s),!(e.isFunction(o)&&o.apply(this.element[0],[i].concat(s))===!1||i.isDefaultPrevented())}},e.each({show:"fadeIn",hide:"fadeOut"},function(t,i){e.Widget.prototype["_"+t]=function(s,n,a){"string"==typeof n&&(n={effect:n});var o,r=n?n===!0||"number"==typeof n?i:n.effect||i:t;n=n||{},"number"==typeof n&&(n={duration:n}),o=!e.isEmptyObject(n),n.complete=a,n.delay&&s.delay(n.delay),o&&e.effects&&e.effects.effect[r]?s[t](n):r!==t&&s[r]?s[r](n.duration,n.easing,a):s.queue(function(i){e(this)[t](),a&&a.call(s[0]),i()})}}),e.widget;var u=!1;e(document).mouseup(function(){u=!1}),e.widget("ui.mouse",{version:"1.11.4",options:{cancel:"input,textarea,button,select,option",distance:1,delay:0},_mouseInit:function(){var t=this;this.element.bind("mousedown."+this.widgetName,function(e){return t._mouseDown(e)}).bind("click."+this.widgetName,function(i){return!0===e.data(i.target,t.widgetName+".preventClickEvent")?(e.removeData(i.target,t.widgetName+".preventClickEvent"),i.stopImmediatePropagation(),!1):void 0}),this.started=!1},_mouseDestroy:function(){this.element.unbind("."+this.widgetName),this._mouseMoveDelegate&&this.document.unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate)},_mouseDown:function(t){if(!u){this._mouseMoved=!1,this._mouseStarted&&this._mouseUp(t),this._mouseDownEvent=t;var i=this,s=1===t.which,n="string"==typeof this.options.cancel&&t.target.nodeName?e(t.target).closest(this.options.cancel).length:!1;return s&&!n&&this._mouseCapture(t)?(this.mouseDelayMet=!this.options.delay,this.mouseDelayMet||(this._mouseDelayTimer=setTimeout(function(){i.mouseDelayMet=!0},this.options.delay)),this._mouseDistanceMet(t)&&this._mouseDelayMet(t)&&(this._mouseStarted=this._mouseStart(t)!==!1,!this._mouseStarted)?(t.preventDefault(),!0):(!0===e.data(t.target,this.widgetName+".preventClickEvent")&&e.removeData(t.target,this.widgetName+".preventClickEvent"),this._mouseMoveDelegate=function(e){return i._mouseMove(e)},this._mouseUpDelegate=function(e){return i._mouseUp(e)},this.document.bind("mousemove."+this.widgetName,this._mouseMoveDelegate).bind("mouseup."+this.widgetName,this._mouseUpDelegate),t.preventDefault(),u=!0,!0)):!0}},_mouseMove:function(t){if(this._mouseMoved){if(e.ui.ie&&(!document.documentMode||9>document.documentMode)&&!t.button)return this._mouseUp(t);if(!t.which)return this._mouseUp(t)}return(t.which||t.button)&&(this._mouseMoved=!0),this._mouseStarted?(this._mouseDrag(t),t.preventDefault()):(this._mouseDistanceMet(t)&&this._mouseDelayMet(t)&&(this._mouseStarted=this._mouseStart(this._mouseDownEvent,t)!==!1,this._mouseStarted?this._mouseDrag(t):this._mouseUp(t)),!this._mouseStarted)},_mouseUp:function(t){return this.document.unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate),this._mouseStarted&&(this._mouseStarted=!1,t.target===this._mouseDownEvent.target&&e.data(t.target,this.widgetName+".preventClickEvent",!0),this._mouseStop(t)),u=!1,!1},_mouseDistanceMet:function(e){return Math.max(Math.abs(this._mouseDownEvent.pageX-e.pageX),Math.abs(this._mouseDownEvent.pageY-e.pageY))>=this.options.distance},_mouseDelayMet:function(){return this.mouseDelayMet},_mouseStart:function(){},_mouseDrag:function(){},_mouseStop:function(){},_mouseCapture:function(){return!0}}),function(){function t(e,t,i){return[parseFloat(e[0])*(p.test(e[0])?t/100:1),parseFloat(e[1])*(p.test(e[1])?i/100:1)]}function i(t,i){return parseInt(e.css(t,i),10)||0}function s(t){var i=t[0];return 9===i.nodeType?{width:t.width(),height:t.height(),offset:{top:0,left:0}}:e.isWindow(i)?{width:t.width(),height:t.height(),offset:{top:t.scrollTop(),left:t.scrollLeft()}}:i.preventDefault?{width:0,height:0,offset:{top:i.pageY,left:i.pageX}}:{width:t.outerWidth(),height:t.outerHeight(),offset:t.offset()}}e.ui=e.ui||{};var n,a,o=Math.max,r=Math.abs,h=Math.round,l=/left|center|right/,u=/top|center|bottom/,d=/[\+\-]\d+(\.[\d]+)?%?/,c=/^\w+/,p=/%$/,f=e.fn.position;e.position={scrollbarWidth:function(){if(void 0!==n)return n;var t,i,s=e("<div style='display:block;position:absolute;width:50px;height:50px;overflow:hidden;'><div style='height:100px;width:auto;'></div></div>"),a=s.children()[0];return e("body").append(s),t=a.offsetWidth,s.css("overflow","scroll"),i=a.offsetWidth,t===i&&(i=s[0].clientWidth),s.remove(),n=t-i},getScrollInfo:function(t){var i=t.isWindow||t.isDocument?"":t.element.css("overflow-x"),s=t.isWindow||t.isDocument?"":t.element.css("overflow-y"),n="scroll"===i||"auto"===i&&t.width<t.element[0].scrollWidth,a="scroll"===s||"auto"===s&&t.height<t.element[0].scrollHeight;return{width:a?e.position.scrollbarWidth():0,height:n?e.position.scrollbarWidth():0}},getWithinInfo:function(t){var i=e(t||window),s=e.isWindow(i[0]),n=!!i[0]&&9===i[0].nodeType;return{element:i,isWindow:s,isDocument:n,offset:i.offset()||{left:0,top:0},scrollLeft:i.scrollLeft(),scrollTop:i.scrollTop(),width:s||n?i.width():i.outerWidth(),height:s||n?i.height():i.outerHeight()}}},e.fn.position=function(n){if(!n||!n.of)return f.apply(this,arguments);n=e.extend({},n);var p,m,g,v,y,b,_=e(n.of),x=e.position.getWithinInfo(n.within),w=e.position.getScrollInfo(x),k=(n.collision||"flip").split(" "),T={};return b=s(_),_[0].preventDefault&&(n.at="left top"),m=b.width,g=b.height,v=b.offset,y=e.extend({},v),e.each(["my","at"],function(){var e,t,i=(n[this]||"").split(" ");1===i.length&&(i=l.test(i[0])?i.concat(["center"]):u.test(i[0])?["center"].concat(i):["center","center"]),i[0]=l.test(i[0])?i[0]:"center",i[1]=u.test(i[1])?i[1]:"center",e=d.exec(i[0]),t=d.exec(i[1]),T[this]=[e?e[0]:0,t?t[0]:0],n[this]=[c.exec(i[0])[0],c.exec(i[1])[0]]}),1===k.length&&(k[1]=k[0]),"right"===n.at[0]?y.left+=m:"center"===n.at[0]&&(y.left+=m/2),"bottom"===n.at[1]?y.top+=g:"center"===n.at[1]&&(y.top+=g/2),p=t(T.at,m,g),y.left+=p[0],y.top+=p[1],this.each(function(){var s,l,u=e(this),d=u.outerWidth(),c=u.outerHeight(),f=i(this,"marginLeft"),b=i(this,"marginTop"),D=d+f+i(this,"marginRight")+w.width,S=c+b+i(this,"marginBottom")+w.height,N=e.extend({},y),M=t(T.my,u.outerWidth(),u.outerHeight());"right"===n.my[0]?N.left-=d:"center"===n.my[0]&&(N.left-=d/2),"bottom"===n.my[1]?N.top-=c:"center"===n.my[1]&&(N.top-=c/2),N.left+=M[0],N.top+=M[1],a||(N.left=h(N.left),N.top=h(N.top)),s={marginLeft:f,marginTop:b},e.each(["left","top"],function(t,i){e.ui.position[k[t]]&&e.ui.position[k[t]][i](N,{targetWidth:m,targetHeight:g,elemWidth:d,elemHeight:c,collisionPosition:s,collisionWidth:D,collisionHeight:S,offset:[p[0]+M[0],p[1]+M[1]],my:n.my,at:n.at,within:x,elem:u})}),n.using&&(l=function(e){var t=v.left-N.left,i=t+m-d,s=v.top-N.top,a=s+g-c,h={target:{element:_,left:v.left,top:v.top,width:m,height:g},element:{element:u,left:N.left,top:N.top,width:d,height:c},horizontal:0>i?"left":t>0?"right":"center",vertical:0>a?"top":s>0?"bottom":"middle"};d>m&&m>r(t+i)&&(h.horizontal="center"),c>g&&g>r(s+a)&&(h.vertical="middle"),h.important=o(r(t),r(i))>o(r(s),r(a))?"horizontal":"vertical",n.using.call(this,e,h)}),u.offset(e.extend(N,{using:l}))})},e.ui.position={fit:{left:function(e,t){var i,s=t.within,n=s.isWindow?s.scrollLeft:s.offset.left,a=s.width,r=e.left-t.collisionPosition.marginLeft,h=n-r,l=r+t.collisionWidth-a-n;t.collisionWidth>a?h>0&&0>=l?(i=e.left+h+t.collisionWidth-a-n,e.left+=h-i):e.left=l>0&&0>=h?n:h>l?n+a-t.collisionWidth:n:h>0?e.left+=h:l>0?e.left-=l:e.left=o(e.left-r,e.left)},top:function(e,t){var i,s=t.within,n=s.isWindow?s.scrollTop:s.offset.top,a=t.within.height,r=e.top-t.collisionPosition.marginTop,h=n-r,l=r+t.collisionHeight-a-n;t.collisionHeight>a?h>0&&0>=l?(i=e.top+h+t.collisionHeight-a-n,e.top+=h-i):e.top=l>0&&0>=h?n:h>l?n+a-t.collisionHeight:n:h>0?e.top+=h:l>0?e.top-=l:e.top=o(e.top-r,e.top)}},flip:{left:function(e,t){var i,s,n=t.within,a=n.offset.left+n.scrollLeft,o=n.width,h=n.isWindow?n.scrollLeft:n.offset.left,l=e.left-t.collisionPosition.marginLeft,u=l-h,d=l+t.collisionWidth-o-h,c="left"===t.my[0]?-t.elemWidth:"right"===t.my[0]?t.elemWidth:0,p="left"===t.at[0]?t.targetWidth:"right"===t.at[0]?-t.targetWidth:0,f=-2*t.offset[0];0>u?(i=e.left+c+p+f+t.collisionWidth-o-a,(0>i||r(u)>i)&&(e.left+=c+p+f)):d>0&&(s=e.left-t.collisionPosition.marginLeft+c+p+f-h,(s>0||d>r(s))&&(e.left+=c+p+f))},top:function(e,t){var i,s,n=t.within,a=n.offset.top+n.scrollTop,o=n.height,h=n.isWindow?n.scrollTop:n.offset.top,l=e.top-t.collisionPosition.marginTop,u=l-h,d=l+t.collisionHeight-o-h,c="top"===t.my[1],p=c?-t.elemHeight:"bottom"===t.my[1]?t.elemHeight:0,f="top"===t.at[1]?t.targetHeight:"bottom"===t.at[1]?-t.targetHeight:0,m=-2*t.offset[1];0>u?(s=e.top+p+f+m+t.collisionHeight-o-a,(0>s||r(u)>s)&&(e.top+=p+f+m)):d>0&&(i=e.top-t.collisionPosition.marginTop+p+f+m-h,(i>0||d>r(i))&&(e.top+=p+f+m))}},flipfit:{left:function(){e.ui.position.flip.left.apply(this,arguments),e.ui.position.fit.left.apply(this,arguments)},top:function(){e.ui.position.flip.top.apply(this,arguments),e.ui.position.fit.top.apply(this,arguments)}}},function(){var t,i,s,n,o,r=document.getElementsByTagName("body")[0],h=document.createElement("div");t=document.createElement(r?"div":"body"),s={visibility:"hidden",width:0,height:0,border:0,margin:0,background:"none"},r&&e.extend(s,{position:"absolute",left:"-1000px",top:"-1000px"});for(o in s)t.style[o]=s[o];t.appendChild(h),i=r||document.documentElement,i.insertBefore(t,i.firstChild),h.style.cssText="position: absolute; left: 10.7432222px;",n=e(h).offset().left,a=n>10&&11>n,t.innerHTML="",i.removeChild(t)}()}(),e.ui.position,e.widget("ui.sortable",e.ui.mouse,{version:"1.11.4",widgetEventPrefix:"sort",ready:!1,options:{appendTo:"parent",axis:!1,connectWith:!1,containment:!1,cursor:"auto",cursorAt:!1,dropOnEmpty:!0,forcePlaceholderSize:!1,forceHelperSize:!1,grid:!1,handle:!1,helper:"original",items:"> *",opacity:!1,placeholder:!1,revert:!1,scroll:!0,scrollSensitivity:20,scrollSpeed:20,scope:"default",tolerance:"intersect",zIndex:1e3,activate:null,beforeStop:null,change:null,deactivate:null,out:null,over:null,receive:null,remove:null,sort:null,start:null,stop:null,update:null},_isOverAxis:function(e,t,i){return e>=t&&t+i>e},_isFloating:function(e){return/left|right/.test(e.css("float"))||/inline|table-cell/.test(e.css("display"))},_create:function(){this.containerCache={},this.element.addClass("ui-sortable"),this.refresh(),this.offset=this.element.offset(),this._mouseInit(),this._setHandleClassName(),this.ready=!0},_setOption:function(e,t){this._super(e,t),"handle"===e&&this._setHandleClassName()},_setHandleClassName:function(){this.element.find(".ui-sortable-handle").removeClass("ui-sortable-handle"),e.each(this.items,function(){(this.instance.options.handle?this.item.find(this.instance.options.handle):this.item).addClass("ui-sortable-handle")})},_destroy:function(){this.element.removeClass("ui-sortable ui-sortable-disabled").find(".ui-sortable-handle").removeClass("ui-sortable-handle"),this._mouseDestroy();for(var e=this.items.length-1;e>=0;e--)this.items[e].item.removeData(this.widgetName+"-item");return this},_mouseCapture:function(t,i){var s=null,n=!1,a=this;return this.reverting?!1:this.options.disabled||"static"===this.options.type?!1:(this._refreshItems(t),e(t.target).parents().each(function(){return e.data(this,a.widgetName+"-item")===a?(s=e(this),!1):void 0}),e.data(t.target,a.widgetName+"-item")===a&&(s=e(t.target)),s?!this.options.handle||i||(e(this.options.handle,s).find("*").addBack().each(function(){this===t.target&&(n=!0)}),n)?(this.currentItem=s,this._removeCurrentsFromItems(),!0):!1:!1)},_mouseStart:function(t,i,s){var n,a,o=this.options;if(this.currentContainer=this,this.refreshPositions(),this.helper=this._createHelper(t),this._cacheHelperProportions(),this._cacheMargins(),this.scrollParent=this.helper.scrollParent(),this.offset=this.currentItem.offset(),this.offset={top:this.offset.top-this.margins.top,left:this.offset.left-this.margins.left},e.extend(this.offset,{click:{left:t.pageX-this.offset.left,top:t.pageY-this.offset.top},parent:this._getParentOffset(),relative:this._getRelativeOffset()}),this.helper.css("position","absolute"),this.cssPosition=this.helper.css("position"),this.originalPosition=this._generatePosition(t),this.originalPageX=t.pageX,this.originalPageY=t.pageY,o.cursorAt&&this._adjustOffsetFromHelper(o.cursorAt),this.domPosition={prev:this.currentItem.prev()[0],parent:this.currentItem.parent()[0]},this.helper[0]!==this.currentItem[0]&&this.currentItem.hide(),this._createPlaceholder(),o.containment&&this._setContainment(),o.cursor&&"auto"!==o.cursor&&(a=this.document.find("body"),this.storedCursor=a.css("cursor"),a.css("cursor",o.cursor),this.storedStylesheet=e("<style>*{ cursor: "+o.cursor+" !important; }</style>").appendTo(a)),o.opacity&&(this.helper.css("opacity")&&(this._storedOpacity=this.helper.css("opacity")),this.helper.css("opacity",o.opacity)),o.zIndex&&(this.helper.css("zIndex")&&(this._storedZIndex=this.helper.css("zIndex")),this.helper.css("zIndex",o.zIndex)),this.scrollParent[0]!==this.document[0]&&"HTML"!==this.scrollParent[0].tagName&&(this.overflowOffset=this.scrollParent.offset()),this._trigger("start",t,this._uiHash()),this._preserveHelperProportions||this._cacheHelperProportions(),!s)for(n=this.containers.length-1;n>=0;n--)this.containers[n]._trigger("activate",t,this._uiHash(this));return e.ui.ddmanager&&(e.ui.ddmanager.current=this),e.ui.ddmanager&&!o.dropBehaviour&&e.ui.ddmanager.prepareOffsets(this,t),this.dragging=!0,this.helper.addClass("ui-sortable-helper"),this._mouseDrag(t),!0},_mouseDrag:function(t){var i,s,n,a,o=this.options,r=!1;for(this.position=this._generatePosition(t),this.positionAbs=this._convertPositionTo("absolute"),this.lastPositionAbs||(this.lastPositionAbs=this.positionAbs),this.options.scroll&&(this.scrollParent[0]!==this.document[0]&&"HTML"!==this.scrollParent[0].tagName?(this.overflowOffset.top+this.scrollParent[0].offsetHeight-t.pageY<o.scrollSensitivity?this.scrollParent[0].scrollTop=r=this.scrollParent[0].scrollTop+o.scrollSpeed:t.pageY-this.overflowOffset.top<o.scrollSensitivity&&(this.scrollParent[0].scrollTop=r=this.scrollParent[0].scrollTop-o.scrollSpeed),this.overflowOffset.left+this.scrollParent[0].offsetWidth-t.pageX<o.scrollSensitivity?this.scrollParent[0].scrollLeft=r=this.scrollParent[0].scrollLeft+o.scrollSpeed:t.pageX-this.overflowOffset.left<o.scrollSensitivity&&(this.scrollParent[0].scrollLeft=r=this.scrollParent[0].scrollLeft-o.scrollSpeed)):(t.pageY-this.document.scrollTop()<o.scrollSensitivity?r=this.document.scrollTop(this.document.scrollTop()-o.scrollSpeed):this.window.height()-(t.pageY-this.document.scrollTop())<o.scrollSensitivity&&(r=this.document.scrollTop(this.document.scrollTop()+o.scrollSpeed)),t.pageX-this.document.scrollLeft()<o.scrollSensitivity?r=this.document.scrollLeft(this.document.scrollLeft()-o.scrollSpeed):this.window.width()-(t.pageX-this.document.scrollLeft())<o.scrollSensitivity&&(r=this.document.scrollLeft(this.document.scrollLeft()+o.scrollSpeed))),r!==!1&&e.ui.ddmanager&&!o.dropBehaviour&&e.ui.ddmanager.prepareOffsets(this,t)),this.positionAbs=this._convertPositionTo("absolute"),this.options.axis&&"y"===this.options.axis||(this.helper[0].style.left=this.position.left+"px"),this.options.axis&&"x"===this.options.axis||(this.helper[0].style.top=this.position.top+"px"),i=this.items.length-1;i>=0;i--)if(s=this.items[i],n=s.item[0],a=this._intersectsWithPointer(s),a&&s.instance===this.currentContainer&&n!==this.currentItem[0]&&this.placeholder[1===a?"next":"prev"]()[0]!==n&&!e.contains(this.placeholder[0],n)&&("semi-dynamic"===this.options.type?!e.contains(this.element[0],n):!0)){if(this.direction=1===a?"down":"up","pointer"!==this.options.tolerance&&!this._intersectsWithSides(s))break;this._rearrange(t,s),this._trigger("change",t,this._uiHash());break}return this._contactContainers(t),e.ui.ddmanager&&e.ui.ddmanager.drag(this,t),this._trigger("sort",t,this._uiHash()),this.lastPositionAbs=this.positionAbs,!1},_mouseStop:function(t,i){if(t){if(e.ui.ddmanager&&!this.options.dropBehaviour&&e.ui.ddmanager.drop(this,t),this.options.revert){var s=this,n=this.placeholder.offset(),a=this.options.axis,o={};a&&"x"!==a||(o.left=n.left-this.offset.parent.left-this.margins.left+(this.offsetParent[0]===this.document[0].body?0:this.offsetParent[0].scrollLeft)),a&&"y"!==a||(o.top=n.top-this.offset.parent.top-this.margins.top+(this.offsetParent[0]===this.document[0].body?0:this.offsetParent[0].scrollTop)),this.reverting=!0,e(this.helper).animate(o,parseInt(this.options.revert,10)||500,function(){s._clear(t)})}else this._clear(t,i);return!1}},cancel:function(){if(this.dragging){this._mouseUp({target:null}),"original"===this.options.helper?this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper"):this.currentItem.show();for(var t=this.containers.length-1;t>=0;t--)this.containers[t]._trigger("deactivate",null,this._uiHash(this)),this.containers[t].containerCache.over&&(this.containers[t]._trigger("out",null,this._uiHash(this)),this.containers[t].containerCache.over=0)}return this.placeholder&&(this.placeholder[0].parentNode&&this.placeholder[0].parentNode.removeChild(this.placeholder[0]),"original"!==this.options.helper&&this.helper&&this.helper[0].parentNode&&this.helper.remove(),e.extend(this,{helper:null,dragging:!1,reverting:!1,_noFinalSort:null}),this.domPosition.prev?e(this.domPosition.prev).after(this.currentItem):e(this.domPosition.parent).prepend(this.currentItem)),this},serialize:function(t){var i=this._getItemsAsjQuery(t&&t.connected),s=[];return t=t||{},e(i).each(function(){var i=(e(t.item||this).attr(t.attribute||"id")||"").match(t.expression||/(.+)[\-=_](.+)/);i&&s.push((t.key||i[1]+"[]")+"="+(t.key&&t.expression?i[1]:i[2]))}),!s.length&&t.key&&s.push(t.key+"="),s.join("&")},toArray:function(t){var i=this._getItemsAsjQuery(t&&t.connected),s=[];return t=t||{},i.each(function(){s.push(e(t.item||this).attr(t.attribute||"id")||"")}),s},_intersectsWith:function(e){var t=this.positionAbs.left,i=t+this.helperProportions.width,s=this.positionAbs.top,n=s+this.helperProportions.height,a=e.left,o=a+e.width,r=e.top,h=r+e.height,l=this.offset.click.top,u=this.offset.click.left,d="x"===this.options.axis||s+l>r&&h>s+l,c="y"===this.options.axis||t+u>a&&o>t+u,p=d&&c;return"pointer"===this.options.tolerance||this.options.forcePointerForContainers||"pointer"!==this.options.tolerance&&this.helperProportions[this.floating?"width":"height"]>e[this.floating?"width":"height"]?p:t+this.helperProportions.width/2>a&&o>i-this.helperProportions.width/2&&s+this.helperProportions.height/2>r&&h>n-this.helperProportions.height/2},_intersectsWithPointer:function(e){var t="x"===this.options.axis||this._isOverAxis(this.positionAbs.top+this.offset.click.top,e.top,e.height),i="y"===this.options.axis||this._isOverAxis(this.positionAbs.left+this.offset.click.left,e.left,e.width),s=t&&i,n=this._getDragVerticalDirection(),a=this._getDragHorizontalDirection();return s?this.floating?a&&"right"===a||"down"===n?2:1:n&&("down"===n?2:1):!1},_intersectsWithSides:function(e){var t=this._isOverAxis(this.positionAbs.top+this.offset.click.top,e.top+e.height/2,e.height),i=this._isOverAxis(this.positionAbs.left+this.offset.click.left,e.left+e.width/2,e.width),s=this._getDragVerticalDirection(),n=this._getDragHorizontalDirection();
return this.floating&&n?"right"===n&&i||"left"===n&&!i:s&&("down"===s&&t||"up"===s&&!t)},_getDragVerticalDirection:function(){var e=this.positionAbs.top-this.lastPositionAbs.top;return 0!==e&&(e>0?"down":"up")},_getDragHorizontalDirection:function(){var e=this.positionAbs.left-this.lastPositionAbs.left;return 0!==e&&(e>0?"right":"left")},refresh:function(e){return this._refreshItems(e),this._setHandleClassName(),this.refreshPositions(),this},_connectWith:function(){var e=this.options;return e.connectWith.constructor===String?[e.connectWith]:e.connectWith},_getItemsAsjQuery:function(t){function i(){r.push(this)}var s,n,a,o,r=[],h=[],l=this._connectWith();if(l&&t)for(s=l.length-1;s>=0;s--)for(a=e(l[s],this.document[0]),n=a.length-1;n>=0;n--)o=e.data(a[n],this.widgetFullName),o&&o!==this&&!o.options.disabled&&h.push([e.isFunction(o.options.items)?o.options.items.call(o.element):e(o.options.items,o.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"),o]);for(h.push([e.isFunction(this.options.items)?this.options.items.call(this.element,null,{options:this.options,item:this.currentItem}):e(this.options.items,this.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"),this]),s=h.length-1;s>=0;s--)h[s][0].each(i);return e(r)},_removeCurrentsFromItems:function(){var t=this.currentItem.find(":data("+this.widgetName+"-item)");this.items=e.grep(this.items,function(e){for(var i=0;t.length>i;i++)if(t[i]===e.item[0])return!1;return!0})},_refreshItems:function(t){this.items=[],this.containers=[this];var i,s,n,a,o,r,h,l,u=this.items,d=[[e.isFunction(this.options.items)?this.options.items.call(this.element[0],t,{item:this.currentItem}):e(this.options.items,this.element),this]],c=this._connectWith();if(c&&this.ready)for(i=c.length-1;i>=0;i--)for(n=e(c[i],this.document[0]),s=n.length-1;s>=0;s--)a=e.data(n[s],this.widgetFullName),a&&a!==this&&!a.options.disabled&&(d.push([e.isFunction(a.options.items)?a.options.items.call(a.element[0],t,{item:this.currentItem}):e(a.options.items,a.element),a]),this.containers.push(a));for(i=d.length-1;i>=0;i--)for(o=d[i][1],r=d[i][0],s=0,l=r.length;l>s;s++)h=e(r[s]),h.data(this.widgetName+"-item",o),u.push({item:h,instance:o,width:0,height:0,left:0,top:0})},refreshPositions:function(t){this.floating=this.items.length?"x"===this.options.axis||this._isFloating(this.items[0].item):!1,this.offsetParent&&this.helper&&(this.offset.parent=this._getParentOffset());var i,s,n,a;for(i=this.items.length-1;i>=0;i--)s=this.items[i],s.instance!==this.currentContainer&&this.currentContainer&&s.item[0]!==this.currentItem[0]||(n=this.options.toleranceElement?e(this.options.toleranceElement,s.item):s.item,t||(s.width=n.outerWidth(),s.height=n.outerHeight()),a=n.offset(),s.left=a.left,s.top=a.top);if(this.options.custom&&this.options.custom.refreshContainers)this.options.custom.refreshContainers.call(this);else for(i=this.containers.length-1;i>=0;i--)a=this.containers[i].element.offset(),this.containers[i].containerCache.left=a.left,this.containers[i].containerCache.top=a.top,this.containers[i].containerCache.width=this.containers[i].element.outerWidth(),this.containers[i].containerCache.height=this.containers[i].element.outerHeight();return this},_createPlaceholder:function(t){t=t||this;var i,s=t.options;s.placeholder&&s.placeholder.constructor!==String||(i=s.placeholder,s.placeholder={element:function(){var s=t.currentItem[0].nodeName.toLowerCase(),n=e("<"+s+">",t.document[0]).addClass(i||t.currentItem[0].className+" ui-sortable-placeholder").removeClass("ui-sortable-helper");return"tbody"===s?t._createTrPlaceholder(t.currentItem.find("tr").eq(0),e("<tr>",t.document[0]).appendTo(n)):"tr"===s?t._createTrPlaceholder(t.currentItem,n):"img"===s&&n.attr("src",t.currentItem.attr("src")),i||n.css("visibility","hidden"),n},update:function(e,n){(!i||s.forcePlaceholderSize)&&(n.height()||n.height(t.currentItem.innerHeight()-parseInt(t.currentItem.css("paddingTop")||0,10)-parseInt(t.currentItem.css("paddingBottom")||0,10)),n.width()||n.width(t.currentItem.innerWidth()-parseInt(t.currentItem.css("paddingLeft")||0,10)-parseInt(t.currentItem.css("paddingRight")||0,10)))}}),t.placeholder=e(s.placeholder.element.call(t.element,t.currentItem)),t.currentItem.after(t.placeholder),s.placeholder.update(t,t.placeholder)},_createTrPlaceholder:function(t,i){var s=this;t.children().each(function(){e("<td>&#160;</td>",s.document[0]).attr("colspan",e(this).attr("colspan")||1).appendTo(i)})},_contactContainers:function(t){var i,s,n,a,o,r,h,l,u,d,c=null,p=null;for(i=this.containers.length-1;i>=0;i--)if(!e.contains(this.currentItem[0],this.containers[i].element[0]))if(this._intersectsWith(this.containers[i].containerCache)){if(c&&e.contains(this.containers[i].element[0],c.element[0]))continue;c=this.containers[i],p=i}else this.containers[i].containerCache.over&&(this.containers[i]._trigger("out",t,this._uiHash(this)),this.containers[i].containerCache.over=0);if(c)if(1===this.containers.length)this.containers[p].containerCache.over||(this.containers[p]._trigger("over",t,this._uiHash(this)),this.containers[p].containerCache.over=1);else{for(n=1e4,a=null,u=c.floating||this._isFloating(this.currentItem),o=u?"left":"top",r=u?"width":"height",d=u?"clientX":"clientY",s=this.items.length-1;s>=0;s--)e.contains(this.containers[p].element[0],this.items[s].item[0])&&this.items[s].item[0]!==this.currentItem[0]&&(h=this.items[s].item.offset()[o],l=!1,t[d]-h>this.items[s][r]/2&&(l=!0),n>Math.abs(t[d]-h)&&(n=Math.abs(t[d]-h),a=this.items[s],this.direction=l?"up":"down"));if(!a&&!this.options.dropOnEmpty)return;if(this.currentContainer===this.containers[p])return this.currentContainer.containerCache.over||(this.containers[p]._trigger("over",t,this._uiHash()),this.currentContainer.containerCache.over=1),void 0;a?this._rearrange(t,a,null,!0):this._rearrange(t,null,this.containers[p].element,!0),this._trigger("change",t,this._uiHash()),this.containers[p]._trigger("change",t,this._uiHash(this)),this.currentContainer=this.containers[p],this.options.placeholder.update(this.currentContainer,this.placeholder),this.containers[p]._trigger("over",t,this._uiHash(this)),this.containers[p].containerCache.over=1}},_createHelper:function(t){var i=this.options,s=e.isFunction(i.helper)?e(i.helper.apply(this.element[0],[t,this.currentItem])):"clone"===i.helper?this.currentItem.clone():this.currentItem;return s.parents("body").length||e("parent"!==i.appendTo?i.appendTo:this.currentItem[0].parentNode)[0].appendChild(s[0]),s[0]===this.currentItem[0]&&(this._storedCSS={width:this.currentItem[0].style.width,height:this.currentItem[0].style.height,position:this.currentItem.css("position"),top:this.currentItem.css("top"),left:this.currentItem.css("left")}),(!s[0].style.width||i.forceHelperSize)&&s.width(this.currentItem.width()),(!s[0].style.height||i.forceHelperSize)&&s.height(this.currentItem.height()),s},_adjustOffsetFromHelper:function(t){"string"==typeof t&&(t=t.split(" ")),e.isArray(t)&&(t={left:+t[0],top:+t[1]||0}),"left"in t&&(this.offset.click.left=t.left+this.margins.left),"right"in t&&(this.offset.click.left=this.helperProportions.width-t.right+this.margins.left),"top"in t&&(this.offset.click.top=t.top+this.margins.top),"bottom"in t&&(this.offset.click.top=this.helperProportions.height-t.bottom+this.margins.top)},_getParentOffset:function(){this.offsetParent=this.helper.offsetParent();var t=this.offsetParent.offset();return"absolute"===this.cssPosition&&this.scrollParent[0]!==this.document[0]&&e.contains(this.scrollParent[0],this.offsetParent[0])&&(t.left+=this.scrollParent.scrollLeft(),t.top+=this.scrollParent.scrollTop()),(this.offsetParent[0]===this.document[0].body||this.offsetParent[0].tagName&&"html"===this.offsetParent[0].tagName.toLowerCase()&&e.ui.ie)&&(t={top:0,left:0}),{top:t.top+(parseInt(this.offsetParent.css("borderTopWidth"),10)||0),left:t.left+(parseInt(this.offsetParent.css("borderLeftWidth"),10)||0)}},_getRelativeOffset:function(){if("relative"===this.cssPosition){var e=this.currentItem.position();return{top:e.top-(parseInt(this.helper.css("top"),10)||0)+this.scrollParent.scrollTop(),left:e.left-(parseInt(this.helper.css("left"),10)||0)+this.scrollParent.scrollLeft()}}return{top:0,left:0}},_cacheMargins:function(){this.margins={left:parseInt(this.currentItem.css("marginLeft"),10)||0,top:parseInt(this.currentItem.css("marginTop"),10)||0}},_cacheHelperProportions:function(){this.helperProportions={width:this.helper.outerWidth(),height:this.helper.outerHeight()}},_setContainment:function(){var t,i,s,n=this.options;"parent"===n.containment&&(n.containment=this.helper[0].parentNode),("document"===n.containment||"window"===n.containment)&&(this.containment=[0-this.offset.relative.left-this.offset.parent.left,0-this.offset.relative.top-this.offset.parent.top,"document"===n.containment?this.document.width():this.window.width()-this.helperProportions.width-this.margins.left,("document"===n.containment?this.document.width():this.window.height()||this.document[0].body.parentNode.scrollHeight)-this.helperProportions.height-this.margins.top]),/^(document|window|parent)$/.test(n.containment)||(t=e(n.containment)[0],i=e(n.containment).offset(),s="hidden"!==e(t).css("overflow"),this.containment=[i.left+(parseInt(e(t).css("borderLeftWidth"),10)||0)+(parseInt(e(t).css("paddingLeft"),10)||0)-this.margins.left,i.top+(parseInt(e(t).css("borderTopWidth"),10)||0)+(parseInt(e(t).css("paddingTop"),10)||0)-this.margins.top,i.left+(s?Math.max(t.scrollWidth,t.offsetWidth):t.offsetWidth)-(parseInt(e(t).css("borderLeftWidth"),10)||0)-(parseInt(e(t).css("paddingRight"),10)||0)-this.helperProportions.width-this.margins.left,i.top+(s?Math.max(t.scrollHeight,t.offsetHeight):t.offsetHeight)-(parseInt(e(t).css("borderTopWidth"),10)||0)-(parseInt(e(t).css("paddingBottom"),10)||0)-this.helperProportions.height-this.margins.top])},_convertPositionTo:function(t,i){i||(i=this.position);var s="absolute"===t?1:-1,n="absolute"!==this.cssPosition||this.scrollParent[0]!==this.document[0]&&e.contains(this.scrollParent[0],this.offsetParent[0])?this.scrollParent:this.offsetParent,a=/(html|body)/i.test(n[0].tagName);return{top:i.top+this.offset.relative.top*s+this.offset.parent.top*s-("fixed"===this.cssPosition?-this.scrollParent.scrollTop():a?0:n.scrollTop())*s,left:i.left+this.offset.relative.left*s+this.offset.parent.left*s-("fixed"===this.cssPosition?-this.scrollParent.scrollLeft():a?0:n.scrollLeft())*s}},_generatePosition:function(t){var i,s,n=this.options,a=t.pageX,o=t.pageY,r="absolute"!==this.cssPosition||this.scrollParent[0]!==this.document[0]&&e.contains(this.scrollParent[0],this.offsetParent[0])?this.scrollParent:this.offsetParent,h=/(html|body)/i.test(r[0].tagName);return"relative"!==this.cssPosition||this.scrollParent[0]!==this.document[0]&&this.scrollParent[0]!==this.offsetParent[0]||(this.offset.relative=this._getRelativeOffset()),this.originalPosition&&(this.containment&&(t.pageX-this.offset.click.left<this.containment[0]&&(a=this.containment[0]+this.offset.click.left),t.pageY-this.offset.click.top<this.containment[1]&&(o=this.containment[1]+this.offset.click.top),t.pageX-this.offset.click.left>this.containment[2]&&(a=this.containment[2]+this.offset.click.left),t.pageY-this.offset.click.top>this.containment[3]&&(o=this.containment[3]+this.offset.click.top)),n.grid&&(i=this.originalPageY+Math.round((o-this.originalPageY)/n.grid[1])*n.grid[1],o=this.containment?i-this.offset.click.top>=this.containment[1]&&i-this.offset.click.top<=this.containment[3]?i:i-this.offset.click.top>=this.containment[1]?i-n.grid[1]:i+n.grid[1]:i,s=this.originalPageX+Math.round((a-this.originalPageX)/n.grid[0])*n.grid[0],a=this.containment?s-this.offset.click.left>=this.containment[0]&&s-this.offset.click.left<=this.containment[2]?s:s-this.offset.click.left>=this.containment[0]?s-n.grid[0]:s+n.grid[0]:s)),{top:o-this.offset.click.top-this.offset.relative.top-this.offset.parent.top+("fixed"===this.cssPosition?-this.scrollParent.scrollTop():h?0:r.scrollTop()),left:a-this.offset.click.left-this.offset.relative.left-this.offset.parent.left+("fixed"===this.cssPosition?-this.scrollParent.scrollLeft():h?0:r.scrollLeft())}},_rearrange:function(e,t,i,s){i?i[0].appendChild(this.placeholder[0]):t.item[0].parentNode.insertBefore(this.placeholder[0],"down"===this.direction?t.item[0]:t.item[0].nextSibling),this.counter=this.counter?++this.counter:1;var n=this.counter;this._delay(function(){n===this.counter&&this.refreshPositions(!s)})},_clear:function(e,t){function i(e,t,i){return function(s){i._trigger(e,s,t._uiHash(t))}}this.reverting=!1;var s,n=[];if(!this._noFinalSort&&this.currentItem.parent().length&&this.placeholder.before(this.currentItem),this._noFinalSort=null,this.helper[0]===this.currentItem[0]){for(s in this._storedCSS)("auto"===this._storedCSS[s]||"static"===this._storedCSS[s])&&(this._storedCSS[s]="");this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper")}else this.currentItem.show();for(this.fromOutside&&!t&&n.push(function(e){this._trigger("receive",e,this._uiHash(this.fromOutside))}),!this.fromOutside&&this.domPosition.prev===this.currentItem.prev().not(".ui-sortable-helper")[0]&&this.domPosition.parent===this.currentItem.parent()[0]||t||n.push(function(e){this._trigger("update",e,this._uiHash())}),this!==this.currentContainer&&(t||(n.push(function(e){this._trigger("remove",e,this._uiHash())}),n.push(function(e){return function(t){e._trigger("receive",t,this._uiHash(this))}}.call(this,this.currentContainer)),n.push(function(e){return function(t){e._trigger("update",t,this._uiHash(this))}}.call(this,this.currentContainer)))),s=this.containers.length-1;s>=0;s--)t||n.push(i("deactivate",this,this.containers[s])),this.containers[s].containerCache.over&&(n.push(i("out",this,this.containers[s])),this.containers[s].containerCache.over=0);if(this.storedCursor&&(this.document.find("body").css("cursor",this.storedCursor),this.storedStylesheet.remove()),this._storedOpacity&&this.helper.css("opacity",this._storedOpacity),this._storedZIndex&&this.helper.css("zIndex","auto"===this._storedZIndex?"":this._storedZIndex),this.dragging=!1,t||this._trigger("beforeStop",e,this._uiHash()),this.placeholder[0].parentNode.removeChild(this.placeholder[0]),this.cancelHelperRemoval||(this.helper[0]!==this.currentItem[0]&&this.helper.remove(),this.helper=null),!t){for(s=0;n.length>s;s++)n[s].call(this,e);this._trigger("stop",e,this._uiHash())}return this.fromOutside=!1,!this.cancelHelperRemoval},_trigger:function(){e.Widget.prototype._trigger.apply(this,arguments)===!1&&this.cancel()},_uiHash:function(t){var i=t||this;return{helper:i.helper,placeholder:i.placeholder||e([]),position:i.position,originalPosition:i.originalPosition,offset:i.positionAbs,item:i.currentItem,sender:t?t.element:null}}}),e.widget("ui.menu",{version:"1.11.4",defaultElement:"<ul>",delay:300,options:{icons:{submenu:"ui-icon-carat-1-e"},items:"> *",menus:"ul",position:{my:"left-1 top",at:"right top"},role:"menu",blur:null,focus:null,select:null},_create:function(){this.activeMenu=this.element,this.mouseHandled=!1,this.element.uniqueId().addClass("ui-menu ui-widget ui-widget-content").toggleClass("ui-menu-icons",!!this.element.find(".ui-icon").length).attr({role:this.options.role,tabIndex:0}),this.options.disabled&&this.element.addClass("ui-state-disabled").attr("aria-disabled","true"),this._on({"mousedown .ui-menu-item":function(e){e.preventDefault()},"click .ui-menu-item":function(t){var i=e(t.target);!this.mouseHandled&&i.not(".ui-state-disabled").length&&(this.select(t),t.isPropagationStopped()||(this.mouseHandled=!0),i.has(".ui-menu").length?this.expand(t):!this.element.is(":focus")&&e(this.document[0].activeElement).closest(".ui-menu").length&&(this.element.trigger("focus",[!0]),this.active&&1===this.active.parents(".ui-menu").length&&clearTimeout(this.timer)))},"mouseenter .ui-menu-item":function(t){if(!this.previousFilter){var i=e(t.currentTarget);i.siblings(".ui-state-active").removeClass("ui-state-active"),this.focus(t,i)}},mouseleave:"collapseAll","mouseleave .ui-menu":"collapseAll",focus:function(e,t){var i=this.active||this.element.find(this.options.items).eq(0);t||this.focus(e,i)},blur:function(t){this._delay(function(){e.contains(this.element[0],this.document[0].activeElement)||this.collapseAll(t)})},keydown:"_keydown"}),this.refresh(),this._on(this.document,{click:function(e){this._closeOnDocumentClick(e)&&this.collapseAll(e),this.mouseHandled=!1}})},_destroy:function(){this.element.removeAttr("aria-activedescendant").find(".ui-menu").addBack().removeClass("ui-menu ui-widget ui-widget-content ui-menu-icons ui-front").removeAttr("role").removeAttr("tabIndex").removeAttr("aria-labelledby").removeAttr("aria-expanded").removeAttr("aria-hidden").removeAttr("aria-disabled").removeUniqueId().show(),this.element.find(".ui-menu-item").removeClass("ui-menu-item").removeAttr("role").removeAttr("aria-disabled").removeUniqueId().removeClass("ui-state-hover").removeAttr("tabIndex").removeAttr("role").removeAttr("aria-haspopup").children().each(function(){var t=e(this);t.data("ui-menu-submenu-carat")&&t.remove()}),this.element.find(".ui-menu-divider").removeClass("ui-menu-divider ui-widget-content")},_keydown:function(t){var i,s,n,a,o=!0;switch(t.keyCode){case e.ui.keyCode.PAGE_UP:this.previousPage(t);break;case e.ui.keyCode.PAGE_DOWN:this.nextPage(t);break;case e.ui.keyCode.HOME:this._move("first","first",t);break;case e.ui.keyCode.END:this._move("last","last",t);break;case e.ui.keyCode.UP:this.previous(t);break;case e.ui.keyCode.DOWN:this.next(t);break;case e.ui.keyCode.LEFT:this.collapse(t);break;case e.ui.keyCode.RIGHT:this.active&&!this.active.is(".ui-state-disabled")&&this.expand(t);break;case e.ui.keyCode.ENTER:case e.ui.keyCode.SPACE:this._activate(t);break;case e.ui.keyCode.ESCAPE:this.collapse(t);break;default:o=!1,s=this.previousFilter||"",n=String.fromCharCode(t.keyCode),a=!1,clearTimeout(this.filterTimer),n===s?a=!0:n=s+n,i=this._filterMenuItems(n),i=a&&-1!==i.index(this.active.next())?this.active.nextAll(".ui-menu-item"):i,i.length||(n=String.fromCharCode(t.keyCode),i=this._filterMenuItems(n)),i.length?(this.focus(t,i),this.previousFilter=n,this.filterTimer=this._delay(function(){delete this.previousFilter},1e3)):delete this.previousFilter}o&&t.preventDefault()},_activate:function(e){this.active.is(".ui-state-disabled")||(this.active.is("[aria-haspopup='true']")?this.expand(e):this.select(e))},refresh:function(){var t,i,s=this,n=this.options.icons.submenu,a=this.element.find(this.options.menus);this.element.toggleClass("ui-menu-icons",!!this.element.find(".ui-icon").length),a.filter(":not(.ui-menu)").addClass("ui-menu ui-widget ui-widget-content ui-front").hide().attr({role:this.options.role,"aria-hidden":"true","aria-expanded":"false"}).each(function(){var t=e(this),i=t.parent(),s=e("<span>").addClass("ui-menu-icon ui-icon "+n).data("ui-menu-submenu-carat",!0);i.attr("aria-haspopup","true").prepend(s),t.attr("aria-labelledby",i.attr("id"))}),t=a.add(this.element),i=t.find(this.options.items),i.not(".ui-menu-item").each(function(){var t=e(this);s._isDivider(t)&&t.addClass("ui-widget-content ui-menu-divider")}),i.not(".ui-menu-item, .ui-menu-divider").addClass("ui-menu-item").uniqueId().attr({tabIndex:-1,role:this._itemRole()}),i.filter(".ui-state-disabled").attr("aria-disabled","true"),this.active&&!e.contains(this.element[0],this.active[0])&&this.blur()},_itemRole:function(){return{menu:"menuitem",listbox:"option"}[this.options.role]},_setOption:function(e,t){"icons"===e&&this.element.find(".ui-menu-icon").removeClass(this.options.icons.submenu).addClass(t.submenu),"disabled"===e&&this.element.toggleClass("ui-state-disabled",!!t).attr("aria-disabled",t),this._super(e,t)},focus:function(e,t){var i,s;this.blur(e,e&&"focus"===e.type),this._scrollIntoView(t),this.active=t.first(),s=this.active.addClass("ui-state-focus").removeClass("ui-state-active"),this.options.role&&this.element.attr("aria-activedescendant",s.attr("id")),this.active.parent().closest(".ui-menu-item").addClass("ui-state-active"),e&&"keydown"===e.type?this._close():this.timer=this._delay(function(){this._close()},this.delay),i=t.children(".ui-menu"),i.length&&e&&/^mouse/.test(e.type)&&this._startOpening(i),this.activeMenu=t.parent(),this._trigger("focus",e,{item:t})},_scrollIntoView:function(t){var i,s,n,a,o,r;this._hasScroll()&&(i=parseFloat(e.css(this.activeMenu[0],"borderTopWidth"))||0,s=parseFloat(e.css(this.activeMenu[0],"paddingTop"))||0,n=t.offset().top-this.activeMenu.offset().top-i-s,a=this.activeMenu.scrollTop(),o=this.activeMenu.height(),r=t.outerHeight(),0>n?this.activeMenu.scrollTop(a+n):n+r>o&&this.activeMenu.scrollTop(a+n-o+r))},blur:function(e,t){t||clearTimeout(this.timer),this.active&&(this.active.removeClass("ui-state-focus"),this.active=null,this._trigger("blur",e,{item:this.active}))},_startOpening:function(e){clearTimeout(this.timer),"true"===e.attr("aria-hidden")&&(this.timer=this._delay(function(){this._close(),this._open(e)},this.delay))},_open:function(t){var i=e.extend({of:this.active},this.options.position);clearTimeout(this.timer),this.element.find(".ui-menu").not(t.parents(".ui-menu")).hide().attr("aria-hidden","true"),t.show().removeAttr("aria-hidden").attr("aria-expanded","true").position(i)},collapseAll:function(t,i){clearTimeout(this.timer),this.timer=this._delay(function(){var s=i?this.element:e(t&&t.target).closest(this.element.find(".ui-menu"));s.length||(s=this.element),this._close(s),this.blur(t),this.activeMenu=s},this.delay)},_close:function(e){e||(e=this.active?this.active.parent():this.element),e.find(".ui-menu").hide().attr("aria-hidden","true").attr("aria-expanded","false").end().find(".ui-state-active").not(".ui-state-focus").removeClass("ui-state-active")},_closeOnDocumentClick:function(t){return!e(t.target).closest(".ui-menu").length},_isDivider:function(e){return!/[^\-\u2014\u2013\s]/.test(e.text())},collapse:function(e){var t=this.active&&this.active.parent().closest(".ui-menu-item",this.element);t&&t.length&&(this._close(),this.focus(e,t))},expand:function(e){var t=this.active&&this.active.children(".ui-menu ").find(this.options.items).first();t&&t.length&&(this._open(t.parent()),this._delay(function(){this.focus(e,t)}))},next:function(e){this._move("next","first",e)},previous:function(e){this._move("prev","last",e)},isFirstItem:function(){return this.active&&!this.active.prevAll(".ui-menu-item").length},isLastItem:function(){return this.active&&!this.active.nextAll(".ui-menu-item").length},_move:function(e,t,i){var s;this.active&&(s="first"===e||"last"===e?this.active["first"===e?"prevAll":"nextAll"](".ui-menu-item").eq(-1):this.active[e+"All"](".ui-menu-item").eq(0)),s&&s.length&&this.active||(s=this.activeMenu.find(this.options.items)[t]()),this.focus(i,s)},nextPage:function(t){var i,s,n;return this.active?(this.isLastItem()||(this._hasScroll()?(s=this.active.offset().top,n=this.element.height(),this.active.nextAll(".ui-menu-item").each(function(){return i=e(this),0>i.offset().top-s-n}),this.focus(t,i)):this.focus(t,this.activeMenu.find(this.options.items)[this.active?"last":"first"]())),void 0):(this.next(t),void 0)},previousPage:function(t){var i,s,n;return this.active?(this.isFirstItem()||(this._hasScroll()?(s=this.active.offset().top,n=this.element.height(),this.active.prevAll(".ui-menu-item").each(function(){return i=e(this),i.offset().top-s+n>0}),this.focus(t,i)):this.focus(t,this.activeMenu.find(this.options.items).first())),void 0):(this.next(t),void 0)},_hasScroll:function(){return this.element.outerHeight()<this.element.prop("scrollHeight")},select:function(t){this.active=this.active||e(t.target).closest(".ui-menu-item");var i={item:this.active};this.active.has(".ui-menu").length||this.collapseAll(t,!0),this._trigger("select",t,i)},_filterMenuItems:function(t){var i=t.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g,"\\$&"),s=RegExp("^"+i,"i");return this.activeMenu.find(this.options.items).filter(".ui-menu-item").filter(function(){return s.test(e.trim(e(this).text()))})}}),e.widget("ui.autocomplete",{version:"1.11.4",defaultElement:"<input>",options:{appendTo:null,autoFocus:!1,delay:300,minLength:1,position:{my:"left top",at:"left bottom",collision:"none"},source:null,change:null,close:null,focus:null,open:null,response:null,search:null,select:null},requestIndex:0,pending:0,_create:function(){var t,i,s,n=this.element[0].nodeName.toLowerCase(),a="textarea"===n,o="input"===n;this.isMultiLine=a?!0:o?!1:this.element.prop("isContentEditable"),this.valueMethod=this.element[a||o?"val":"text"],this.isNewMenu=!0,this.element.addClass("ui-autocomplete-input").attr("autocomplete","off"),this._on(this.element,{keydown:function(n){if(this.element.prop("readOnly"))return t=!0,s=!0,i=!0,void 0;t=!1,s=!1,i=!1;var a=e.ui.keyCode;switch(n.keyCode){case a.PAGE_UP:t=!0,this._move("previousPage",n);break;case a.PAGE_DOWN:t=!0,this._move("nextPage",n);break;case a.UP:t=!0,this._keyEvent("previous",n);break;case a.DOWN:t=!0,this._keyEvent("next",n);break;case a.ENTER:this.menu.active&&(t=!0,n.preventDefault(),this.menu.select(n));break;case a.TAB:this.menu.active&&this.menu.select(n);break;case a.ESCAPE:this.menu.element.is(":visible")&&(this.isMultiLine||this._value(this.term),this.close(n),n.preventDefault());break;default:i=!0,this._searchTimeout(n)}},keypress:function(s){if(t)return t=!1,(!this.isMultiLine||this.menu.element.is(":visible"))&&s.preventDefault(),void 0;if(!i){var n=e.ui.keyCode;switch(s.keyCode){case n.PAGE_UP:this._move("previousPage",s);break;case n.PAGE_DOWN:this._move("nextPage",s);break;case n.UP:this._keyEvent("previous",s);break;case n.DOWN:this._keyEvent("next",s)}}},input:function(e){return s?(s=!1,e.preventDefault(),void 0):(this._searchTimeout(e),void 0)},focus:function(){this.selectedItem=null,this.previous=this._value()},blur:function(e){return this.cancelBlur?(delete this.cancelBlur,void 0):(clearTimeout(this.searching),this.close(e),this._change(e),void 0)}}),this._initSource(),this.menu=e("<ul>").addClass("ui-autocomplete ui-front").appendTo(this._appendTo()).menu({role:null}).hide().menu("instance"),this._on(this.menu.element,{mousedown:function(t){t.preventDefault(),this.cancelBlur=!0,this._delay(function(){delete this.cancelBlur});var i=this.menu.element[0];e(t.target).closest(".ui-menu-item").length||this._delay(function(){var t=this;this.document.one("mousedown",function(s){s.target===t.element[0]||s.target===i||e.contains(i,s.target)||t.close()})})},menufocus:function(t,i){var s,n;return this.isNewMenu&&(this.isNewMenu=!1,t.originalEvent&&/^mouse/.test(t.originalEvent.type))?(this.menu.blur(),this.document.one("mousemove",function(){e(t.target).trigger(t.originalEvent)}),void 0):(n=i.item.data("ui-autocomplete-item"),!1!==this._trigger("focus",t,{item:n})&&t.originalEvent&&/^key/.test(t.originalEvent.type)&&this._value(n.value),s=i.item.attr("aria-label")||n.value,s&&e.trim(s).length&&(this.liveRegion.children().hide(),e("<div>").text(s).appendTo(this.liveRegion)),void 0)},menuselect:function(e,t){var i=t.item.data("ui-autocomplete-item"),s=this.previous;this.element[0]!==this.document[0].activeElement&&(this.element.focus(),this.previous=s,this._delay(function(){this.previous=s,this.selectedItem=i})),!1!==this._trigger("select",e,{item:i})&&this._value(i.value),this.term=this._value(),this.close(e),this.selectedItem=i}}),this.liveRegion=e("<span>",{role:"status","aria-live":"assertive","aria-relevant":"additions"}).addClass("ui-helper-hidden-accessible").appendTo(this.document[0].body),this._on(this.window,{beforeunload:function(){this.element.removeAttr("autocomplete")}})},_destroy:function(){clearTimeout(this.searching),this.element.removeClass("ui-autocomplete-input").removeAttr("autocomplete"),this.menu.element.remove(),this.liveRegion.remove()},_setOption:function(e,t){this._super(e,t),"source"===e&&this._initSource(),"appendTo"===e&&this.menu.element.appendTo(this._appendTo()),"disabled"===e&&t&&this.xhr&&this.xhr.abort()},_appendTo:function(){var t=this.options.appendTo;return t&&(t=t.jquery||t.nodeType?e(t):this.document.find(t).eq(0)),t&&t[0]||(t=this.element.closest(".ui-front")),t.length||(t=this.document[0].body),t},_initSource:function(){var t,i,s=this;e.isArray(this.options.source)?(t=this.options.source,this.source=function(i,s){s(e.ui.autocomplete.filter(t,i.term))}):"string"==typeof this.options.source?(i=this.options.source,this.source=function(t,n){s.xhr&&s.xhr.abort(),s.xhr=e.ajax({url:i,data:t,dataType:"json",success:function(e){n(e)},error:function(){n([])}})}):this.source=this.options.source},_searchTimeout:function(e){clearTimeout(this.searching),this.searching=this._delay(function(){var t=this.term===this._value(),i=this.menu.element.is(":visible"),s=e.altKey||e.ctrlKey||e.metaKey||e.shiftKey;(!t||t&&!i&&!s)&&(this.selectedItem=null,this.search(null,e))},this.options.delay)},search:function(e,t){return e=null!=e?e:this._value(),this.term=this._value(),e.length<this.options.minLength?this.close(t):this._trigger("search",t)!==!1?this._search(e):void 0},_search:function(e){this.pending++,this.element.addClass("ui-autocomplete-loading"),this.cancelSearch=!1,this.source({term:e},this._response())},_response:function(){var t=++this.requestIndex;return e.proxy(function(e){t===this.requestIndex&&this.__response(e),this.pending--,this.pending||this.element.removeClass("ui-autocomplete-loading")},this)},__response:function(e){e&&(e=this._normalize(e)),this._trigger("response",null,{content:e}),!this.options.disabled&&e&&e.length&&!this.cancelSearch?(this._suggest(e),this._trigger("open")):this._close()},close:function(e){this.cancelSearch=!0,this._close(e)},_close:function(e){this.menu.element.is(":visible")&&(this.menu.element.hide(),this.menu.blur(),this.isNewMenu=!0,this._trigger("close",e))},_change:function(e){this.previous!==this._value()&&this._trigger("change",e,{item:this.selectedItem})},_normalize:function(t){return t.length&&t[0].label&&t[0].value?t:e.map(t,function(t){return"string"==typeof t?{label:t,value:t}:e.extend({},t,{label:t.label||t.value,value:t.value||t.label})})},_suggest:function(t){var i=this.menu.element.empty();this._renderMenu(i,t),this.isNewMenu=!0,this.menu.refresh(),i.show(),this._resizeMenu(),i.position(e.extend({of:this.element},this.options.position)),this.options.autoFocus&&this.menu.next()},_resizeMenu:function(){var e=this.menu.element;e.outerWidth(Math.max(e.width("").outerWidth()+1,this.element.outerWidth()))},_renderMenu:function(t,i){var s=this;e.each(i,function(e,i){s._renderItemData(t,i)})},_renderItemData:function(e,t){return this._renderItem(e,t).data("ui-autocomplete-item",t)},_renderItem:function(t,i){return e("<li>").text(i.label).appendTo(t)},_move:function(e,t){return this.menu.element.is(":visible")?this.menu.isFirstItem()&&/^previous/.test(e)||this.menu.isLastItem()&&/^next/.test(e)?(this.isMultiLine||this._value(this.term),this.menu.blur(),void 0):(this.menu[e](t),void 0):(this.search(null,t),void 0)},widget:function(){return this.menu.element},_value:function(){return this.valueMethod.apply(this.element,arguments)},_keyEvent:function(e,t){(!this.isMultiLine||this.menu.element.is(":visible"))&&(this._move(e,t),t.preventDefault())}}),e.extend(e.ui.autocomplete,{escapeRegex:function(e){return e.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g,"\\$&")},filter:function(t,i){var s=RegExp(e.ui.autocomplete.escapeRegex(i),"i");return e.grep(t,function(e){return s.test(e.label||e.value||e)})}}),e.widget("ui.autocomplete",e.ui.autocomplete,{options:{messages:{noResults:"No search results.",results:function(e){return e+(e>1?" results are":" result is")+" available, use up and down arrow keys to navigate."}}},__response:function(t){var i;this._superApply(arguments),this.options.disabled||this.cancelSearch||(i=t&&t.length?this.options.messages.results(t.length):this.options.messages.noResults,this.liveRegion.children().hide(),e("<div>").text(i).appendTo(this.liveRegion))}}),e.ui.autocomplete,e.extend(e.ui,{datepicker:{version:"1.11.4"}});
var d;e.extend(n.prototype,{markerClassName:"hasDatepicker",maxRows:4,_widgetDatepicker:function(){return this.dpDiv},setDefaults:function(e){return r(this._defaults,e||{}),this},_attachDatepicker:function(t,i){var s,n,a;s=t.nodeName.toLowerCase(),n="div"===s||"span"===s,t.id||(this.uuid+=1,t.id="dp"+this.uuid),a=this._newInst(e(t),n),a.settings=e.extend({},i||{}),"input"===s?this._connectDatepicker(t,a):n&&this._inlineDatepicker(t,a)},_newInst:function(t,i){var s=t[0].id.replace(/([^A-Za-z0-9_\-])/g,"\\\\$1");return{id:s,input:t,selectedDay:0,selectedMonth:0,selectedYear:0,drawMonth:0,drawYear:0,inline:i,dpDiv:i?a(e("<div class='"+this._inlineClass+" ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all'></div>")):this.dpDiv}},_connectDatepicker:function(t,i){var s=e(t);i.append=e([]),i.trigger=e([]),s.hasClass(this.markerClassName)||(this._attachments(s,i),s.addClass(this.markerClassName).keydown(this._doKeyDown).keypress(this._doKeyPress).keyup(this._doKeyUp),this._autoSize(i),e.data(t,"datepicker",i),i.settings.disabled&&this._disableDatepicker(t))},_attachments:function(t,i){var s,n,a,o=this._get(i,"appendText"),r=this._get(i,"isRTL");i.append&&i.append.remove(),o&&(i.append=e("<span class='"+this._appendClass+"'>"+o+"</span>"),t[r?"before":"after"](i.append)),t.unbind("focus",this._showDatepicker),i.trigger&&i.trigger.remove(),s=this._get(i,"showOn"),("focus"===s||"both"===s)&&t.focus(this._showDatepicker),("button"===s||"both"===s)&&(n=this._get(i,"buttonText"),a=this._get(i,"buttonImage"),i.trigger=e(this._get(i,"buttonImageOnly")?e("<img/>").addClass(this._triggerClass).attr({src:a,alt:n,title:n}):e("<button type='button'></button>").addClass(this._triggerClass).html(a?e("<img/>").attr({src:a,alt:n,title:n}):n)),t[r?"before":"after"](i.trigger),i.trigger.click(function(){return e.datepicker._datepickerShowing&&e.datepicker._lastInput===t[0]?e.datepicker._hideDatepicker():e.datepicker._datepickerShowing&&e.datepicker._lastInput!==t[0]?(e.datepicker._hideDatepicker(),e.datepicker._showDatepicker(t[0])):e.datepicker._showDatepicker(t[0]),!1}))},_autoSize:function(e){if(this._get(e,"autoSize")&&!e.inline){var t,i,s,n,a=new Date(2009,11,20),o=this._get(e,"dateFormat");o.match(/[DM]/)&&(t=function(e){for(i=0,s=0,n=0;e.length>n;n++)e[n].length>i&&(i=e[n].length,s=n);return s},a.setMonth(t(this._get(e,o.match(/MM/)?"monthNames":"monthNamesShort"))),a.setDate(t(this._get(e,o.match(/DD/)?"dayNames":"dayNamesShort"))+20-a.getDay())),e.input.attr("size",this._formatDate(e,a).length)}},_inlineDatepicker:function(t,i){var s=e(t);s.hasClass(this.markerClassName)||(s.addClass(this.markerClassName).append(i.dpDiv),e.data(t,"datepicker",i),this._setDate(i,this._getDefaultDate(i),!0),this._updateDatepicker(i),this._updateAlternate(i),i.settings.disabled&&this._disableDatepicker(t),i.dpDiv.css("display","block"))},_dialogDatepicker:function(t,i,s,n,a){var o,h,l,u,d,c=this._dialogInst;return c||(this.uuid+=1,o="dp"+this.uuid,this._dialogInput=e("<input type='text' id='"+o+"' style='position: absolute; top: -100px; width: 0px;'/>"),this._dialogInput.keydown(this._doKeyDown),e("body").append(this._dialogInput),c=this._dialogInst=this._newInst(this._dialogInput,!1),c.settings={},e.data(this._dialogInput[0],"datepicker",c)),r(c.settings,n||{}),i=i&&i.constructor===Date?this._formatDate(c,i):i,this._dialogInput.val(i),this._pos=a?a.length?a:[a.pageX,a.pageY]:null,this._pos||(h=document.documentElement.clientWidth,l=document.documentElement.clientHeight,u=document.documentElement.scrollLeft||document.body.scrollLeft,d=document.documentElement.scrollTop||document.body.scrollTop,this._pos=[h/2-100+u,l/2-150+d]),this._dialogInput.css("left",this._pos[0]+20+"px").css("top",this._pos[1]+"px"),c.settings.onSelect=s,this._inDialog=!0,this.dpDiv.addClass(this._dialogClass),this._showDatepicker(this._dialogInput[0]),e.blockUI&&e.blockUI(this.dpDiv),e.data(this._dialogInput[0],"datepicker",c),this},_destroyDatepicker:function(t){var i,s=e(t),n=e.data(t,"datepicker");s.hasClass(this.markerClassName)&&(i=t.nodeName.toLowerCase(),e.removeData(t,"datepicker"),"input"===i?(n.append.remove(),n.trigger.remove(),s.removeClass(this.markerClassName).unbind("focus",this._showDatepicker).unbind("keydown",this._doKeyDown).unbind("keypress",this._doKeyPress).unbind("keyup",this._doKeyUp)):("div"===i||"span"===i)&&s.removeClass(this.markerClassName).empty(),d===n&&(d=null))},_enableDatepicker:function(t){var i,s,n=e(t),a=e.data(t,"datepicker");n.hasClass(this.markerClassName)&&(i=t.nodeName.toLowerCase(),"input"===i?(t.disabled=!1,a.trigger.filter("button").each(function(){this.disabled=!1}).end().filter("img").css({opacity:"1.0",cursor:""})):("div"===i||"span"===i)&&(s=n.children("."+this._inlineClass),s.children().removeClass("ui-state-disabled"),s.find("select.ui-datepicker-month, select.ui-datepicker-year").prop("disabled",!1)),this._disabledInputs=e.map(this._disabledInputs,function(e){return e===t?null:e}))},_disableDatepicker:function(t){var i,s,n=e(t),a=e.data(t,"datepicker");n.hasClass(this.markerClassName)&&(i=t.nodeName.toLowerCase(),"input"===i?(t.disabled=!0,a.trigger.filter("button").each(function(){this.disabled=!0}).end().filter("img").css({opacity:"0.5",cursor:"default"})):("div"===i||"span"===i)&&(s=n.children("."+this._inlineClass),s.children().addClass("ui-state-disabled"),s.find("select.ui-datepicker-month, select.ui-datepicker-year").prop("disabled",!0)),this._disabledInputs=e.map(this._disabledInputs,function(e){return e===t?null:e}),this._disabledInputs[this._disabledInputs.length]=t)},_isDisabledDatepicker:function(e){if(!e)return!1;for(var t=0;this._disabledInputs.length>t;t++)if(this._disabledInputs[t]===e)return!0;return!1},_getInst:function(t){try{return e.data(t,"datepicker")}catch(i){throw"Missing instance data for this datepicker"}},_optionDatepicker:function(t,i,s){var n,a,o,h,l=this._getInst(t);return 2===arguments.length&&"string"==typeof i?"defaults"===i?e.extend({},e.datepicker._defaults):l?"all"===i?e.extend({},l.settings):this._get(l,i):null:(n=i||{},"string"==typeof i&&(n={},n[i]=s),l&&(this._curInst===l&&this._hideDatepicker(),a=this._getDateDatepicker(t,!0),o=this._getMinMaxDate(l,"min"),h=this._getMinMaxDate(l,"max"),r(l.settings,n),null!==o&&void 0!==n.dateFormat&&void 0===n.minDate&&(l.settings.minDate=this._formatDate(l,o)),null!==h&&void 0!==n.dateFormat&&void 0===n.maxDate&&(l.settings.maxDate=this._formatDate(l,h)),"disabled"in n&&(n.disabled?this._disableDatepicker(t):this._enableDatepicker(t)),this._attachments(e(t),l),this._autoSize(l),this._setDate(l,a),this._updateAlternate(l),this._updateDatepicker(l)),void 0)},_changeDatepicker:function(e,t,i){this._optionDatepicker(e,t,i)},_refreshDatepicker:function(e){var t=this._getInst(e);t&&this._updateDatepicker(t)},_setDateDatepicker:function(e,t){var i=this._getInst(e);i&&(this._setDate(i,t),this._updateDatepicker(i),this._updateAlternate(i))},_getDateDatepicker:function(e,t){var i=this._getInst(e);return i&&!i.inline&&this._setDateFromField(i,t),i?this._getDate(i):null},_doKeyDown:function(t){var i,s,n,a=e.datepicker._getInst(t.target),o=!0,r=a.dpDiv.is(".ui-datepicker-rtl");if(a._keyEvent=!0,e.datepicker._datepickerShowing)switch(t.keyCode){case 9:e.datepicker._hideDatepicker(),o=!1;break;case 13:return n=e("td."+e.datepicker._dayOverClass+":not(."+e.datepicker._currentClass+")",a.dpDiv),n[0]&&e.datepicker._selectDay(t.target,a.selectedMonth,a.selectedYear,n[0]),i=e.datepicker._get(a,"onSelect"),i?(s=e.datepicker._formatDate(a),i.apply(a.input?a.input[0]:null,[s,a])):e.datepicker._hideDatepicker(),!1;case 27:e.datepicker._hideDatepicker();break;case 33:e.datepicker._adjustDate(t.target,t.ctrlKey?-e.datepicker._get(a,"stepBigMonths"):-e.datepicker._get(a,"stepMonths"),"M");break;case 34:e.datepicker._adjustDate(t.target,t.ctrlKey?+e.datepicker._get(a,"stepBigMonths"):+e.datepicker._get(a,"stepMonths"),"M");break;case 35:(t.ctrlKey||t.metaKey)&&e.datepicker._clearDate(t.target),o=t.ctrlKey||t.metaKey;break;case 36:(t.ctrlKey||t.metaKey)&&e.datepicker._gotoToday(t.target),o=t.ctrlKey||t.metaKey;break;case 37:(t.ctrlKey||t.metaKey)&&e.datepicker._adjustDate(t.target,r?1:-1,"D"),o=t.ctrlKey||t.metaKey,t.originalEvent.altKey&&e.datepicker._adjustDate(t.target,t.ctrlKey?-e.datepicker._get(a,"stepBigMonths"):-e.datepicker._get(a,"stepMonths"),"M");break;case 38:(t.ctrlKey||t.metaKey)&&e.datepicker._adjustDate(t.target,-7,"D"),o=t.ctrlKey||t.metaKey;break;case 39:(t.ctrlKey||t.metaKey)&&e.datepicker._adjustDate(t.target,r?-1:1,"D"),o=t.ctrlKey||t.metaKey,t.originalEvent.altKey&&e.datepicker._adjustDate(t.target,t.ctrlKey?+e.datepicker._get(a,"stepBigMonths"):+e.datepicker._get(a,"stepMonths"),"M");break;case 40:(t.ctrlKey||t.metaKey)&&e.datepicker._adjustDate(t.target,7,"D"),o=t.ctrlKey||t.metaKey;break;default:o=!1}else 36===t.keyCode&&t.ctrlKey?e.datepicker._showDatepicker(this):o=!1;o&&(t.preventDefault(),t.stopPropagation())},_doKeyPress:function(t){var i,s,n=e.datepicker._getInst(t.target);return e.datepicker._get(n,"constrainInput")?(i=e.datepicker._possibleChars(e.datepicker._get(n,"dateFormat")),s=String.fromCharCode(null==t.charCode?t.keyCode:t.charCode),t.ctrlKey||t.metaKey||" ">s||!i||i.indexOf(s)>-1):void 0},_doKeyUp:function(t){var i,s=e.datepicker._getInst(t.target);if(s.input.val()!==s.lastVal)try{i=e.datepicker.parseDate(e.datepicker._get(s,"dateFormat"),s.input?s.input.val():null,e.datepicker._getFormatConfig(s)),i&&(e.datepicker._setDateFromField(s),e.datepicker._updateAlternate(s),e.datepicker._updateDatepicker(s))}catch(n){}return!0},_showDatepicker:function(t){if(t=t.target||t,"input"!==t.nodeName.toLowerCase()&&(t=e("input",t.parentNode)[0]),!e.datepicker._isDisabledDatepicker(t)&&e.datepicker._lastInput!==t){var i,n,a,o,h,l,u;i=e.datepicker._getInst(t),e.datepicker._curInst&&e.datepicker._curInst!==i&&(e.datepicker._curInst.dpDiv.stop(!0,!0),i&&e.datepicker._datepickerShowing&&e.datepicker._hideDatepicker(e.datepicker._curInst.input[0])),n=e.datepicker._get(i,"beforeShow"),a=n?n.apply(t,[t,i]):{},a!==!1&&(r(i.settings,a),i.lastVal=null,e.datepicker._lastInput=t,e.datepicker._setDateFromField(i),e.datepicker._inDialog&&(t.value=""),e.datepicker._pos||(e.datepicker._pos=e.datepicker._findPos(t),e.datepicker._pos[1]+=t.offsetHeight),o=!1,e(t).parents().each(function(){return o|="fixed"===e(this).css("position"),!o}),h={left:e.datepicker._pos[0],top:e.datepicker._pos[1]},e.datepicker._pos=null,i.dpDiv.empty(),i.dpDiv.css({position:"absolute",display:"block",top:"-1000px"}),e.datepicker._updateDatepicker(i),h=e.datepicker._checkOffset(i,h,o),i.dpDiv.css({position:e.datepicker._inDialog&&e.blockUI?"static":o?"fixed":"absolute",display:"none",left:h.left+"px",top:h.top+"px"}),i.inline||(l=e.datepicker._get(i,"showAnim"),u=e.datepicker._get(i,"duration"),i.dpDiv.css("z-index",s(e(t))+1),e.datepicker._datepickerShowing=!0,e.effects&&e.effects.effect[l]?i.dpDiv.show(l,e.datepicker._get(i,"showOptions"),u):i.dpDiv[l||"show"](l?u:null),e.datepicker._shouldFocusInput(i)&&i.input.focus(),e.datepicker._curInst=i))}},_updateDatepicker:function(t){this.maxRows=4,d=t,t.dpDiv.empty().append(this._generateHTML(t)),this._attachHandlers(t);var i,s=this._getNumberOfMonths(t),n=s[1],a=17,r=t.dpDiv.find("."+this._dayOverClass+" a");r.length>0&&o.apply(r.get(0)),t.dpDiv.removeClass("ui-datepicker-multi-2 ui-datepicker-multi-3 ui-datepicker-multi-4").width(""),n>1&&t.dpDiv.addClass("ui-datepicker-multi-"+n).css("width",a*n+"em"),t.dpDiv[(1!==s[0]||1!==s[1]?"add":"remove")+"Class"]("ui-datepicker-multi"),t.dpDiv[(this._get(t,"isRTL")?"add":"remove")+"Class"]("ui-datepicker-rtl"),t===e.datepicker._curInst&&e.datepicker._datepickerShowing&&e.datepicker._shouldFocusInput(t)&&t.input.focus(),t.yearshtml&&(i=t.yearshtml,setTimeout(function(){i===t.yearshtml&&t.yearshtml&&t.dpDiv.find("select.ui-datepicker-year:first").replaceWith(t.yearshtml),i=t.yearshtml=null},0))},_shouldFocusInput:function(e){return e.input&&e.input.is(":visible")&&!e.input.is(":disabled")&&!e.input.is(":focus")},_checkOffset:function(t,i,s){var n=t.dpDiv.outerWidth(),a=t.dpDiv.outerHeight(),o=t.input?t.input.outerWidth():0,r=t.input?t.input.outerHeight():0,h=document.documentElement.clientWidth+(s?0:e(document).scrollLeft()),l=document.documentElement.clientHeight+(s?0:e(document).scrollTop());return i.left-=this._get(t,"isRTL")?n-o:0,i.left-=s&&i.left===t.input.offset().left?e(document).scrollLeft():0,i.top-=s&&i.top===t.input.offset().top+r?e(document).scrollTop():0,i.left-=Math.min(i.left,i.left+n>h&&h>n?Math.abs(i.left+n-h):0),i.top-=Math.min(i.top,i.top+a>l&&l>a?Math.abs(a+r):0),i},_findPos:function(t){for(var i,s=this._getInst(t),n=this._get(s,"isRTL");t&&("hidden"===t.type||1!==t.nodeType||e.expr.filters.hidden(t));)t=t[n?"previousSibling":"nextSibling"];return i=e(t).offset(),[i.left,i.top]},_hideDatepicker:function(t){var i,s,n,a,o=this._curInst;!o||t&&o!==e.data(t,"datepicker")||this._datepickerShowing&&(i=this._get(o,"showAnim"),s=this._get(o,"duration"),n=function(){e.datepicker._tidyDialog(o)},e.effects&&(e.effects.effect[i]||e.effects[i])?o.dpDiv.hide(i,e.datepicker._get(o,"showOptions"),s,n):o.dpDiv["slideDown"===i?"slideUp":"fadeIn"===i?"fadeOut":"hide"](i?s:null,n),i||n(),this._datepickerShowing=!1,a=this._get(o,"onClose"),a&&a.apply(o.input?o.input[0]:null,[o.input?o.input.val():"",o]),this._lastInput=null,this._inDialog&&(this._dialogInput.css({position:"absolute",left:"0",top:"-100px"}),e.blockUI&&(e.unblockUI(),e("body").append(this.dpDiv))),this._inDialog=!1)},_tidyDialog:function(e){e.dpDiv.removeClass(this._dialogClass).unbind(".ui-datepicker-calendar")},_checkExternalClick:function(t){if(e.datepicker._curInst){var i=e(t.target),s=e.datepicker._getInst(i[0]);(i[0].id!==e.datepicker._mainDivId&&0===i.parents("#"+e.datepicker._mainDivId).length&&!i.hasClass(e.datepicker.markerClassName)&&!i.closest("."+e.datepicker._triggerClass).length&&e.datepicker._datepickerShowing&&(!e.datepicker._inDialog||!e.blockUI)||i.hasClass(e.datepicker.markerClassName)&&e.datepicker._curInst!==s)&&e.datepicker._hideDatepicker()}},_adjustDate:function(t,i,s){var n=e(t),a=this._getInst(n[0]);this._isDisabledDatepicker(n[0])||(this._adjustInstDate(a,i+("M"===s?this._get(a,"showCurrentAtPos"):0),s),this._updateDatepicker(a))},_gotoToday:function(t){var i,s=e(t),n=this._getInst(s[0]);this._get(n,"gotoCurrent")&&n.currentDay?(n.selectedDay=n.currentDay,n.drawMonth=n.selectedMonth=n.currentMonth,n.drawYear=n.selectedYear=n.currentYear):(i=new Date,n.selectedDay=i.getDate(),n.drawMonth=n.selectedMonth=i.getMonth(),n.drawYear=n.selectedYear=i.getFullYear()),this._notifyChange(n),this._adjustDate(s)},_selectMonthYear:function(t,i,s){var n=e(t),a=this._getInst(n[0]);a["selected"+("M"===s?"Month":"Year")]=a["draw"+("M"===s?"Month":"Year")]=parseInt(i.options[i.selectedIndex].value,10),this._notifyChange(a),this._adjustDate(n)},_selectDay:function(t,i,s,n){var a,o=e(t);e(n).hasClass(this._unselectableClass)||this._isDisabledDatepicker(o[0])||(a=this._getInst(o[0]),a.selectedDay=a.currentDay=e("a",n).html(),a.selectedMonth=a.currentMonth=i,a.selectedYear=a.currentYear=s,this._selectDate(t,this._formatDate(a,a.currentDay,a.currentMonth,a.currentYear)))},_clearDate:function(t){var i=e(t);this._selectDate(i,"")},_selectDate:function(t,i){var s,n=e(t),a=this._getInst(n[0]);i=null!=i?i:this._formatDate(a),a.input&&a.input.val(i),this._updateAlternate(a),s=this._get(a,"onSelect"),s?s.apply(a.input?a.input[0]:null,[i,a]):a.input&&a.input.trigger("change"),a.inline?this._updateDatepicker(a):(this._hideDatepicker(),this._lastInput=a.input[0],"object"!=typeof a.input[0]&&a.input.focus(),this._lastInput=null)},_updateAlternate:function(t){var i,s,n,a=this._get(t,"altField");a&&(i=this._get(t,"altFormat")||this._get(t,"dateFormat"),s=this._getDate(t),n=this.formatDate(i,s,this._getFormatConfig(t)),e(a).each(function(){e(this).val(n)}))},noWeekends:function(e){var t=e.getDay();return[t>0&&6>t,""]},iso8601Week:function(e){var t,i=new Date(e.getTime());return i.setDate(i.getDate()+4-(i.getDay()||7)),t=i.getTime(),i.setMonth(0),i.setDate(1),Math.floor(Math.round((t-i)/864e5)/7)+1},parseDate:function(t,i,s){if(null==t||null==i)throw"Invalid arguments";if(i="object"==typeof i?""+i:i+"",""===i)return null;var n,a,o,r,h=0,l=(s?s.shortYearCutoff:null)||this._defaults.shortYearCutoff,u="string"!=typeof l?l:(new Date).getFullYear()%100+parseInt(l,10),d=(s?s.dayNamesShort:null)||this._defaults.dayNamesShort,c=(s?s.dayNames:null)||this._defaults.dayNames,p=(s?s.monthNamesShort:null)||this._defaults.monthNamesShort,f=(s?s.monthNames:null)||this._defaults.monthNames,m=-1,g=-1,v=-1,y=-1,b=!1,_=function(e){var i=t.length>n+1&&t.charAt(n+1)===e;return i&&n++,i},x=function(e){var t=_(e),s="@"===e?14:"!"===e?20:"y"===e&&t?4:"o"===e?3:2,n="y"===e?s:1,a=RegExp("^\\d{"+n+","+s+"}"),o=i.substring(h).match(a);if(!o)throw"Missing number at position "+h;return h+=o[0].length,parseInt(o[0],10)},w=function(t,s,n){var a=-1,o=e.map(_(t)?n:s,function(e,t){return[[t,e]]}).sort(function(e,t){return-(e[1].length-t[1].length)});if(e.each(o,function(e,t){var s=t[1];return i.substr(h,s.length).toLowerCase()===s.toLowerCase()?(a=t[0],h+=s.length,!1):void 0}),-1!==a)return a+1;throw"Unknown name at position "+h},k=function(){if(i.charAt(h)!==t.charAt(n))throw"Unexpected literal at position "+h;h++};for(n=0;t.length>n;n++)if(b)"'"!==t.charAt(n)||_("'")?k():b=!1;else switch(t.charAt(n)){case"d":v=x("d");break;case"D":w("D",d,c);break;case"o":y=x("o");break;case"m":g=x("m");break;case"M":g=w("M",p,f);break;case"y":m=x("y");break;case"@":r=new Date(x("@")),m=r.getFullYear(),g=r.getMonth()+1,v=r.getDate();break;case"!":r=new Date((x("!")-this._ticksTo1970)/1e4),m=r.getFullYear(),g=r.getMonth()+1,v=r.getDate();break;case"'":_("'")?k():b=!0;break;default:k()}if(i.length>h&&(o=i.substr(h),!/^\s+/.test(o)))throw"Extra/unparsed characters found in date: "+o;if(-1===m?m=(new Date).getFullYear():100>m&&(m+=(new Date).getFullYear()-(new Date).getFullYear()%100+(u>=m?0:-100)),y>-1)for(g=1,v=y;;){if(a=this._getDaysInMonth(m,g-1),a>=v)break;g++,v-=a}if(r=this._daylightSavingAdjust(new Date(m,g-1,v)),r.getFullYear()!==m||r.getMonth()+1!==g||r.getDate()!==v)throw"Invalid date";return r},ATOM:"yy-mm-dd",COOKIE:"D, dd M yy",ISO_8601:"yy-mm-dd",RFC_822:"D, d M y",RFC_850:"DD, dd-M-y",RFC_1036:"D, d M y",RFC_1123:"D, d M yy",RFC_2822:"D, d M yy",RSS:"D, d M y",TICKS:"!",TIMESTAMP:"@",W3C:"yy-mm-dd",_ticksTo1970:1e7*60*60*24*(718685+Math.floor(492.5)-Math.floor(19.7)+Math.floor(4.925)),formatDate:function(e,t,i){if(!t)return"";var s,n=(i?i.dayNamesShort:null)||this._defaults.dayNamesShort,a=(i?i.dayNames:null)||this._defaults.dayNames,o=(i?i.monthNamesShort:null)||this._defaults.monthNamesShort,r=(i?i.monthNames:null)||this._defaults.monthNames,h=function(t){var i=e.length>s+1&&e.charAt(s+1)===t;return i&&s++,i},l=function(e,t,i){var s=""+t;if(h(e))for(;i>s.length;)s="0"+s;return s},u=function(e,t,i,s){return h(e)?s[t]:i[t]},d="",c=!1;if(t)for(s=0;e.length>s;s++)if(c)"'"!==e.charAt(s)||h("'")?d+=e.charAt(s):c=!1;else switch(e.charAt(s)){case"d":d+=l("d",t.getDate(),2);break;case"D":d+=u("D",t.getDay(),n,a);break;case"o":d+=l("o",Math.round((new Date(t.getFullYear(),t.getMonth(),t.getDate()).getTime()-new Date(t.getFullYear(),0,0).getTime())/864e5),3);break;case"m":d+=l("m",t.getMonth()+1,2);break;case"M":d+=u("M",t.getMonth(),o,r);break;case"y":d+=h("y")?t.getFullYear():(10>t.getYear()%100?"0":"")+t.getYear()%100;break;case"@":d+=t.getTime();break;case"!":d+=1e4*t.getTime()+this._ticksTo1970;break;case"'":h("'")?d+="'":c=!0;break;default:d+=e.charAt(s)}return d},_possibleChars:function(e){var t,i="",s=!1,n=function(i){var s=e.length>t+1&&e.charAt(t+1)===i;return s&&t++,s};for(t=0;e.length>t;t++)if(s)"'"!==e.charAt(t)||n("'")?i+=e.charAt(t):s=!1;else switch(e.charAt(t)){case"d":case"m":case"y":case"@":i+="0123456789";break;case"D":case"M":return null;case"'":n("'")?i+="'":s=!0;break;default:i+=e.charAt(t)}return i},_get:function(e,t){return void 0!==e.settings[t]?e.settings[t]:this._defaults[t]},_setDateFromField:function(e,t){if(e.input.val()!==e.lastVal){var i=this._get(e,"dateFormat"),s=e.lastVal=e.input?e.input.val():null,n=this._getDefaultDate(e),a=n,o=this._getFormatConfig(e);try{a=this.parseDate(i,s,o)||n}catch(r){s=t?"":s}e.selectedDay=a.getDate(),e.drawMonth=e.selectedMonth=a.getMonth(),e.drawYear=e.selectedYear=a.getFullYear(),e.currentDay=s?a.getDate():0,e.currentMonth=s?a.getMonth():0,e.currentYear=s?a.getFullYear():0,this._adjustInstDate(e)}},_getDefaultDate:function(e){return this._restrictMinMax(e,this._determineDate(e,this._get(e,"defaultDate"),new Date))},_determineDate:function(t,i,s){var n=function(e){var t=new Date;return t.setDate(t.getDate()+e),t},a=function(i){try{return e.datepicker.parseDate(e.datepicker._get(t,"dateFormat"),i,e.datepicker._getFormatConfig(t))}catch(s){}for(var n=(i.toLowerCase().match(/^c/)?e.datepicker._getDate(t):null)||new Date,a=n.getFullYear(),o=n.getMonth(),r=n.getDate(),h=/([+\-]?[0-9]+)\s*(d|D|w|W|m|M|y|Y)?/g,l=h.exec(i);l;){switch(l[2]||"d"){case"d":case"D":r+=parseInt(l[1],10);break;case"w":case"W":r+=7*parseInt(l[1],10);break;case"m":case"M":o+=parseInt(l[1],10),r=Math.min(r,e.datepicker._getDaysInMonth(a,o));break;case"y":case"Y":a+=parseInt(l[1],10),r=Math.min(r,e.datepicker._getDaysInMonth(a,o))}l=h.exec(i)}return new Date(a,o,r)},o=null==i||""===i?s:"string"==typeof i?a(i):"number"==typeof i?isNaN(i)?s:n(i):new Date(i.getTime());return o=o&&"Invalid Date"==""+o?s:o,o&&(o.setHours(0),o.setMinutes(0),o.setSeconds(0),o.setMilliseconds(0)),this._daylightSavingAdjust(o)},_daylightSavingAdjust:function(e){return e?(e.setHours(e.getHours()>12?e.getHours()+2:0),e):null},_setDate:function(e,t,i){var s=!t,n=e.selectedMonth,a=e.selectedYear,o=this._restrictMinMax(e,this._determineDate(e,t,new Date));e.selectedDay=e.currentDay=o.getDate(),e.drawMonth=e.selectedMonth=e.currentMonth=o.getMonth(),e.drawYear=e.selectedYear=e.currentYear=o.getFullYear(),n===e.selectedMonth&&a===e.selectedYear||i||this._notifyChange(e),this._adjustInstDate(e),e.input&&e.input.val(s?"":this._formatDate(e))},_getDate:function(e){var t=!e.currentYear||e.input&&""===e.input.val()?null:this._daylightSavingAdjust(new Date(e.currentYear,e.currentMonth,e.currentDay));return t},_attachHandlers:function(t){var i=this._get(t,"stepMonths"),s="#"+t.id.replace(/\\\\/g,"\\");t.dpDiv.find("[data-handler]").map(function(){var t={prev:function(){e.datepicker._adjustDate(s,-i,"M")},next:function(){e.datepicker._adjustDate(s,+i,"M")},hide:function(){e.datepicker._hideDatepicker()},today:function(){e.datepicker._gotoToday(s)},selectDay:function(){return e.datepicker._selectDay(s,+this.getAttribute("data-month"),+this.getAttribute("data-year"),this),!1},selectMonth:function(){return e.datepicker._selectMonthYear(s,this,"M"),!1},selectYear:function(){return e.datepicker._selectMonthYear(s,this,"Y"),!1}};e(this).bind(this.getAttribute("data-event"),t[this.getAttribute("data-handler")])})},_generateHTML:function(e){var t,i,s,n,a,o,r,h,l,u,d,c,p,f,m,g,v,y,b,_,x,w,k,T,D,S,N,M,C,A,P,I,H,z,F,E,W,O,L,j=new Date,R=this._daylightSavingAdjust(new Date(j.getFullYear(),j.getMonth(),j.getDate())),Y=this._get(e,"isRTL"),J=this._get(e,"showButtonPanel"),B=this._get(e,"hideIfNoPrevNext"),K=this._get(e,"navigationAsDateFormat"),V=this._getNumberOfMonths(e),U=this._get(e,"showCurrentAtPos"),q=this._get(e,"stepMonths"),G=1!==V[0]||1!==V[1],X=this._daylightSavingAdjust(e.currentDay?new Date(e.currentYear,e.currentMonth,e.currentDay):new Date(9999,9,9)),Q=this._getMinMaxDate(e,"min"),$=this._getMinMaxDate(e,"max"),Z=e.drawMonth-U,et=e.drawYear;if(0>Z&&(Z+=12,et--),$)for(t=this._daylightSavingAdjust(new Date($.getFullYear(),$.getMonth()-V[0]*V[1]+1,$.getDate())),t=Q&&Q>t?Q:t;this._daylightSavingAdjust(new Date(et,Z,1))>t;)Z--,0>Z&&(Z=11,et--);for(e.drawMonth=Z,e.drawYear=et,i=this._get(e,"prevText"),i=K?this.formatDate(i,this._daylightSavingAdjust(new Date(et,Z-q,1)),this._getFormatConfig(e)):i,s=this._canAdjustMonth(e,-1,et,Z)?"<a class='ui-datepicker-prev ui-corner-all' data-handler='prev' data-event='click' title='"+i+"'><span class='ui-icon ui-icon-circle-triangle-"+(Y?"e":"w")+"'>"+i+"</span></a>":B?"":"<a class='ui-datepicker-prev ui-corner-all ui-state-disabled' title='"+i+"'><span class='ui-icon ui-icon-circle-triangle-"+(Y?"e":"w")+"'>"+i+"</span></a>",n=this._get(e,"nextText"),n=K?this.formatDate(n,this._daylightSavingAdjust(new Date(et,Z+q,1)),this._getFormatConfig(e)):n,a=this._canAdjustMonth(e,1,et,Z)?"<a class='ui-datepicker-next ui-corner-all' data-handler='next' data-event='click' title='"+n+"'><span class='ui-icon ui-icon-circle-triangle-"+(Y?"w":"e")+"'>"+n+"</span></a>":B?"":"<a class='ui-datepicker-next ui-corner-all ui-state-disabled' title='"+n+"'><span class='ui-icon ui-icon-circle-triangle-"+(Y?"w":"e")+"'>"+n+"</span></a>",o=this._get(e,"currentText"),r=this._get(e,"gotoCurrent")&&e.currentDay?X:R,o=K?this.formatDate(o,r,this._getFormatConfig(e)):o,h=e.inline?"":"<button type='button' class='ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all' data-handler='hide' data-event='click'>"+this._get(e,"closeText")+"</button>",l=J?"<div class='ui-datepicker-buttonpane ui-widget-content'>"+(Y?h:"")+(this._isInRange(e,r)?"<button type='button' class='ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all' data-handler='today' data-event='click'>"+o+"</button>":"")+(Y?"":h)+"</div>":"",u=parseInt(this._get(e,"firstDay"),10),u=isNaN(u)?0:u,d=this._get(e,"showWeek"),c=this._get(e,"dayNames"),p=this._get(e,"dayNamesMin"),f=this._get(e,"monthNames"),m=this._get(e,"monthNamesShort"),g=this._get(e,"beforeShowDay"),v=this._get(e,"showOtherMonths"),y=this._get(e,"selectOtherMonths"),b=this._getDefaultDate(e),_="",w=0;V[0]>w;w++){for(k="",this.maxRows=4,T=0;V[1]>T;T++){if(D=this._daylightSavingAdjust(new Date(et,Z,e.selectedDay)),S=" ui-corner-all",N="",G){if(N+="<div class='ui-datepicker-group",V[1]>1)switch(T){case 0:N+=" ui-datepicker-group-first",S=" ui-corner-"+(Y?"right":"left");break;case V[1]-1:N+=" ui-datepicker-group-last",S=" ui-corner-"+(Y?"left":"right");break;default:N+=" ui-datepicker-group-middle",S=""}N+="'>"}for(N+="<div class='ui-datepicker-header ui-widget-header ui-helper-clearfix"+S+"'>"+(/all|left/.test(S)&&0===w?Y?a:s:"")+(/all|right/.test(S)&&0===w?Y?s:a:"")+this._generateMonthYearHeader(e,Z,et,Q,$,w>0||T>0,f,m)+"</div><table class='ui-datepicker-calendar'><thead>"+"<tr>",M=d?"<th class='ui-datepicker-week-col'>"+this._get(e,"weekHeader")+"</th>":"",x=0;7>x;x++)C=(x+u)%7,M+="<th scope='col'"+((x+u+6)%7>=5?" class='ui-datepicker-week-end'":"")+">"+"<span title='"+c[C]+"'>"+p[C]+"</span></th>";for(N+=M+"</tr></thead><tbody>",A=this._getDaysInMonth(et,Z),et===e.selectedYear&&Z===e.selectedMonth&&(e.selectedDay=Math.min(e.selectedDay,A)),P=(this._getFirstDayOfMonth(et,Z)-u+7)%7,I=Math.ceil((P+A)/7),H=G?this.maxRows>I?this.maxRows:I:I,this.maxRows=H,z=this._daylightSavingAdjust(new Date(et,Z,1-P)),F=0;H>F;F++){for(N+="<tr>",E=d?"<td class='ui-datepicker-week-col'>"+this._get(e,"calculateWeek")(z)+"</td>":"",x=0;7>x;x++)W=g?g.apply(e.input?e.input[0]:null,[z]):[!0,""],O=z.getMonth()!==Z,L=O&&!y||!W[0]||Q&&Q>z||$&&z>$,E+="<td class='"+((x+u+6)%7>=5?" ui-datepicker-week-end":"")+(O?" ui-datepicker-other-month":"")+(z.getTime()===D.getTime()&&Z===e.selectedMonth&&e._keyEvent||b.getTime()===z.getTime()&&b.getTime()===D.getTime()?" "+this._dayOverClass:"")+(L?" "+this._unselectableClass+" ui-state-disabled":"")+(O&&!v?"":" "+W[1]+(z.getTime()===X.getTime()?" "+this._currentClass:"")+(z.getTime()===R.getTime()?" ui-datepicker-today":""))+"'"+(O&&!v||!W[2]?"":" title='"+W[2].replace(/'/g,"&#39;")+"'")+(L?"":" data-handler='selectDay' data-event='click' data-month='"+z.getMonth()+"' data-year='"+z.getFullYear()+"'")+">"+(O&&!v?"&#xa0;":L?"<span class='ui-state-default'>"+z.getDate()+"</span>":"<a class='ui-state-default"+(z.getTime()===R.getTime()?" ui-state-highlight":"")+(z.getTime()===X.getTime()?" ui-state-active":"")+(O?" ui-priority-secondary":"")+"' href='#'>"+z.getDate()+"</a>")+"</td>",z.setDate(z.getDate()+1),z=this._daylightSavingAdjust(z);N+=E+"</tr>"}Z++,Z>11&&(Z=0,et++),N+="</tbody></table>"+(G?"</div>"+(V[0]>0&&T===V[1]-1?"<div class='ui-datepicker-row-break'></div>":""):""),k+=N}_+=k}return _+=l,e._keyEvent=!1,_},_generateMonthYearHeader:function(e,t,i,s,n,a,o,r){var h,l,u,d,c,p,f,m,g=this._get(e,"changeMonth"),v=this._get(e,"changeYear"),y=this._get(e,"showMonthAfterYear"),b="<div class='ui-datepicker-title'>",_="";if(a||!g)_+="<span class='ui-datepicker-month'>"+o[t]+"</span>";else{for(h=s&&s.getFullYear()===i,l=n&&n.getFullYear()===i,_+="<select class='ui-datepicker-month' data-handler='selectMonth' data-event='change'>",u=0;12>u;u++)(!h||u>=s.getMonth())&&(!l||n.getMonth()>=u)&&(_+="<option value='"+u+"'"+(u===t?" selected='selected'":"")+">"+r[u]+"</option>");_+="</select>"}if(y||(b+=_+(!a&&g&&v?"":"&#xa0;")),!e.yearshtml)if(e.yearshtml="",a||!v)b+="<span class='ui-datepicker-year'>"+i+"</span>";else{for(d=this._get(e,"yearRange").split(":"),c=(new Date).getFullYear(),p=function(e){var t=e.match(/c[+\-].*/)?i+parseInt(e.substring(1),10):e.match(/[+\-].*/)?c+parseInt(e,10):parseInt(e,10);return isNaN(t)?c:t},f=p(d[0]),m=Math.max(f,p(d[1]||"")),f=s?Math.max(f,s.getFullYear()):f,m=n?Math.min(m,n.getFullYear()):m,e.yearshtml+="<select class='ui-datepicker-year' data-handler='selectYear' data-event='change'>";m>=f;f++)e.yearshtml+="<option value='"+f+"'"+(f===i?" selected='selected'":"")+">"+f+"</option>";e.yearshtml+="</select>",b+=e.yearshtml,e.yearshtml=null}return b+=this._get(e,"yearSuffix"),y&&(b+=(!a&&g&&v?"":"&#xa0;")+_),b+="</div>"},_adjustInstDate:function(e,t,i){var s=e.drawYear+("Y"===i?t:0),n=e.drawMonth+("M"===i?t:0),a=Math.min(e.selectedDay,this._getDaysInMonth(s,n))+("D"===i?t:0),o=this._restrictMinMax(e,this._daylightSavingAdjust(new Date(s,n,a)));e.selectedDay=o.getDate(),e.drawMonth=e.selectedMonth=o.getMonth(),e.drawYear=e.selectedYear=o.getFullYear(),("M"===i||"Y"===i)&&this._notifyChange(e)},_restrictMinMax:function(e,t){var i=this._getMinMaxDate(e,"min"),s=this._getMinMaxDate(e,"max"),n=i&&i>t?i:t;return s&&n>s?s:n},_notifyChange:function(e){var t=this._get(e,"onChangeMonthYear");t&&t.apply(e.input?e.input[0]:null,[e.selectedYear,e.selectedMonth+1,e])},_getNumberOfMonths:function(e){var t=this._get(e,"numberOfMonths");return null==t?[1,1]:"number"==typeof t?[1,t]:t},_getMinMaxDate:function(e,t){return this._determineDate(e,this._get(e,t+"Date"),null)},_getDaysInMonth:function(e,t){return 32-this._daylightSavingAdjust(new Date(e,t,32)).getDate()},_getFirstDayOfMonth:function(e,t){return new Date(e,t,1).getDay()},_canAdjustMonth:function(e,t,i,s){var n=this._getNumberOfMonths(e),a=this._daylightSavingAdjust(new Date(i,s+(0>t?t:n[0]*n[1]),1));return 0>t&&a.setDate(this._getDaysInMonth(a.getFullYear(),a.getMonth())),this._isInRange(e,a)},_isInRange:function(e,t){var i,s,n=this._getMinMaxDate(e,"min"),a=this._getMinMaxDate(e,"max"),o=null,r=null,h=this._get(e,"yearRange");return h&&(i=h.split(":"),s=(new Date).getFullYear(),o=parseInt(i[0],10),r=parseInt(i[1],10),i[0].match(/[+\-].*/)&&(o+=s),i[1].match(/[+\-].*/)&&(r+=s)),(!n||t.getTime()>=n.getTime())&&(!a||t.getTime()<=a.getTime())&&(!o||t.getFullYear()>=o)&&(!r||r>=t.getFullYear())},_getFormatConfig:function(e){var t=this._get(e,"shortYearCutoff");return t="string"!=typeof t?t:(new Date).getFullYear()%100+parseInt(t,10),{shortYearCutoff:t,dayNamesShort:this._get(e,"dayNamesShort"),dayNames:this._get(e,"dayNames"),monthNamesShort:this._get(e,"monthNamesShort"),monthNames:this._get(e,"monthNames")}},_formatDate:function(e,t,i,s){t||(e.currentDay=e.selectedDay,e.currentMonth=e.selectedMonth,e.currentYear=e.selectedYear);var n=t?"object"==typeof t?t:this._daylightSavingAdjust(new Date(s,i,t)):this._daylightSavingAdjust(new Date(e.currentYear,e.currentMonth,e.currentDay));
return this.formatDate(this._get(e,"dateFormat"),n,this._getFormatConfig(e))}}),e.fn.datepicker=function(t){if(!this.length)return this;e.datepicker.initialized||(e(document).mousedown(e.datepicker._checkExternalClick),e.datepicker.initialized=!0),0===e("#"+e.datepicker._mainDivId).length&&e("body").append(e.datepicker.dpDiv);var i=Array.prototype.slice.call(arguments,1);return"string"!=typeof t||"isDisabled"!==t&&"getDate"!==t&&"widget"!==t?"option"===t&&2===arguments.length&&"string"==typeof arguments[1]?e.datepicker["_"+t+"Datepicker"].apply(e.datepicker,[this[0]].concat(i)):this.each(function(){"string"==typeof t?e.datepicker["_"+t+"Datepicker"].apply(e.datepicker,[this].concat(i)):e.datepicker._attachDatepicker(this,t)}):e.datepicker["_"+t+"Datepicker"].apply(e.datepicker,[this[0]].concat(i))},e.datepicker=new n,e.datepicker.initialized=!1,e.datepicker.uuid=(new Date).getTime(),e.datepicker.version="1.11.4",e.datepicker});

/*
 Bootstrap - File Input
 ======================

 This is meant to convert all file input tags into a set of elements that displays consistently in all browsers.

 Converts all
 <input type="file">
 into Bootstrap buttons
 <a class="btn">Browse</a>

 */
$(function(){$.fn.bootstrapFileInput=function(){this.each(function(a,b){var c=$(b);
// Maybe some fields don't need to be standardized.
if("undefined"==typeof c.attr("data-bfi-disabled")){
// Set the word to be displayed on the button
var d="Browse";"undefined"!=typeof c.attr("title")&&(d=c.attr("title"));
// Start by getting the HTML of the input element.
// Thanks for the tip http://stackoverflow.com/a/1299069
var e=$("<div>").append(c.eq(0).clone()).html(),f="";c.attr("class")&&(f=" "+c.attr("class")),
// Now we're going to replace that input field with a Bootstrap button.
// The input will actually still be there, it will just be float above and transparent (done with the CSS).
c.replaceWith('<a class="file-input-wrapper btn'+f+'">'+d+e+"</a>")}}).promise().done(function(){
// As the cursor moves over our new Bootstrap button we need to adjust the position of the invisible file input Browse button to be under the cursor.
// This gives us the pointer cursor that FF denies us
$(".file-input-wrapper").mousemove(function(a){var b,c,d,e,f,g,h,i;c=$(this),b=c.find("input"),d=c.offset().left,e=c.offset().top,f=b.width(),g=b.height(),h=a.pageX,i=a.pageY,moveInputX=h-d-f+20,moveInputY=i-e-g/2,b.css({left:moveInputX,top:moveInputY})}),$(".file-input-wrapper input[type=file]").change(function(){var a;a=$(this).val(),$(this).parent().next(".file-input-name").remove(),a=$(this).prop("files")&&$(this).prop("files").length>1?$(this)[0].files.length+" files":a.substring(a.lastIndexOf("\\")+1,a.length),$(this).parent().after('<span class="file-input-name">'+a+"</span>")})})};
// Add the styles before the first stylesheet
// This ensures they can be easily overridden with developer styles
var a="<style>.file-input-wrapper { overflow: hidden; position: relative; cursor: pointer; z-index: 1; }.file-input-wrapper input[type=file], .file-input-wrapper input[type=file]:focus, .file-input-wrapper input[type=file]:hover { position: absolute; top: 0; left: 0; cursor: pointer; opacity: 0; filter: alpha(opacity=0); z-index: 99; outline: 0; }.file-input-name { margin-left: 8px; }</style>";$("link[rel=stylesheet]").eq(0).before(a)});

/*!
 * jQuery Hotkeys Plugin
 * Copyright 2010, John Resig
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Based upon the plugin by Tzury Bar Yochay:
 * http://github.com/tzuryby/hotkeys
 *
 * Original idea by:
 * Binny V A, http://www.openjs.com/scripts/events/keyboard_shortcuts/
 */
!function(a){function b(b){var c=b.handler,
//use namespace as keys so it works with event delegation as well
//will also allow removing listeners of a specific key combination
//and support data objects
d=(b.namespace||"").toLowerCase().split(" ");d=a.map(d,function(a){return a.split(".")}),(1!==d.length||""!==d[0]&&"autocomplete"!==d[0])&&(b.handler=function(b){if(this===b.target||!/textarea|select/i.test(b.target.nodeName)&&"text"!==b.target.type&&"true"!=$(b.target).prop("contenteditable")){var e="keypress"!==b.type&&a.hotkeys.specialKeys[b.which],f=String.fromCharCode(b.which).toLowerCase(),g="",h={};b.altKey&&"alt"!==e&&(g+="alt_"),b.ctrlKey&&"ctrl"!==e&&(g+="ctrl_"),b.metaKey&&!b.ctrlKey&&"meta"!==e&&(g+="meta_"),b.shiftKey&&"shift"!==e&&(g+="shift_"),e?h[g+e]=!0:(h[g+f]=!0,h[g+a.hotkeys.shiftNums[f]]=!0,"shift_"===g&&(h[a.hotkeys.shiftNums[f]]=!0));for(var i=0,j=d.length;j>i;i++)if(h[d[i]])return c.apply(this,arguments)}})}a.hotkeys={version:"0.8+",specialKeys:{8:"backspace",9:"tab",13:"return",16:"shift",17:"ctrl",18:"alt",19:"pause",20:"capslock",27:"esc",32:"space",33:"pageup",34:"pagedown",35:"end",36:"home",37:"left",38:"up",39:"right",40:"down",45:"insert",46:"del",96:"0",97:"1",98:"2",99:"3",100:"4",101:"5",102:"6",103:"7",104:"8",105:"9",106:"*",107:"+",109:"-",110:".",111:"/",112:"f1",113:"f2",114:"f3",115:"f4",116:"f5",117:"f6",118:"f7",119:"f8",120:"f9",121:"f10",122:"f11",123:"f12",144:"numlock",145:"scroll",188:",",190:".",191:"/",224:"meta"},shiftNums:{"`":"~",1:"!",2:"@",3:"#",4:"$",5:"%",6:"^",7:"&",8:"*",9:"(",0:")","-":"_","=":"+",";":": ","'":'"',",":"<",".":">","/":"?","\\":"|"}},a.each(["keydown","keyup","keypress"],function(){a.event.special[this]={add:b}})}(jQuery);

/*
 * jQuery Iframe Transport Plugin
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2011, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
/* global define, require, window, document */
!function(a){"use strict";"function"==typeof define&&define.amd?
// Register as an anonymous AMD module:
define(["jquery"],a):a("object"==typeof exports?require("jquery"):window.jQuery)}(function(a){"use strict";
// Helper variable to create unique names for the transport iframes:
var b=0;
// The iframe transport accepts four additional options:
// options.fileInput: a jQuery collection of file input fields
// options.paramName: the parameter name for the file form data,
//  overrides the name property of the file input field(s),
//  can be a string or an array of strings.
// options.formData: an array of objects with name and value properties,
//  equivalent to the return data of .serializeArray(), e.g.:
//  [{name: 'a', value: 1}, {name: 'b', value: 2}]
// options.initialIframeSrc: the URL of the initial iframe src,
//  by default set to "javascript:false;"
a.ajaxTransport("iframe",function(c){if(c.async){
// javascript:false as initial iframe src
// prevents warning popups on HTTPS in IE6:
/*jshint scripturl: true */
var/*jshint scripturl: false */
d,e,f,g=c.initialIframeSrc||"javascript:false;";return{send:function(h,i){d=a('<form style="display:none;"></form>'),d.attr("accept-charset",c.formAcceptCharset),f=/\?/.test(c.url)?"&":"?","DELETE"===c.type?(c.url=c.url+f+"_method=DELETE",c.type="POST"):"PUT"===c.type?(c.url=c.url+f+"_method=PUT",c.type="POST"):"PATCH"===c.type&&(c.url=c.url+f+"_method=PATCH",c.type="POST"),b+=1,e=a('<iframe src="'+g+'" name="iframe-transport-'+b+'"></iframe>').bind("load",function(){var b,f=a.isArray(c.paramName)?c.paramName:[c.paramName];e.unbind("load").bind("load",function(){var b;try{if(b=e.contents(),!b.length||!b[0].firstChild)throw new Error}catch(c){b=void 0}i(200,"success",{iframe:b}),a('<iframe src="'+g+'"></iframe>').appendTo(d),window.setTimeout(function(){d.remove()},0)}),d.prop("target",e.prop("name")).prop("action",c.url).prop("method",c.type),c.formData&&a.each(c.formData,function(b,c){a('<input type="hidden"/>').prop("name",c.name).val(c.value).appendTo(d)}),c.fileInput&&c.fileInput.length&&"POST"===c.type&&(b=c.fileInput.clone(),c.fileInput.after(function(a){return b[a]}),c.paramName&&c.fileInput.each(function(b){a(this).prop("name",f[b]||c.paramName)}),d.append(c.fileInput).prop("enctype","multipart/form-data").prop("encoding","multipart/form-data"),c.fileInput.removeAttr("form")),d.submit(),b&&b.length&&c.fileInput.each(function(c,d){var e=a(b[c]);a(d).prop("name",e.prop("name")).attr("form",e.attr("form")),e.replaceWith(d)})}),d.append(e).appendTo(document.body)},abort:function(){e&&
// javascript:false as iframe src aborts the request
// and prevents warning popups on HTTPS in IE6.
// concat is used to avoid the "Script URL" JSLint error:
e.unbind("load").prop("src",g),d&&d.remove()}}}}),
// The iframe transport returns the iframe content document as response.
// The following adds converters from iframe to text, json, html, xml
// and script.
// Please note that the Content-Type for JSON responses has to be text/plain
// or text/html, if the browser doesn't include application/json in the
// Accept header, else IE will show a download dialog.
// The Content-Type for XML responses on the other hand has to be always
// application/xml or text/xml, so IE properly parses the XML response.
// See also
// https://github.com/blueimp/jQuery-File-Upload/wiki/Setup#content-type-negotiation
a.ajaxSetup({converters:{"iframe text":function(b){return b&&a(b[0].body).text()},"iframe json":function(b){return b&&a.parseJSON(a(b[0].body).text())},"iframe html":function(b){return b&&a(b[0].body).html()},"iframe xml":function(b){var c=b&&b[0];return c&&a.isXMLDoc(c)?c:a.parseXML(c.XMLDocument&&c.XMLDocument.xml||a(c.body).html())},"iframe script":function(b){return b&&a.globalEval(a(b[0].body).text())}}})});

/*
 * jQuery File Upload Plugin
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
/* jshint nomen:false */
/* global define, require, window, document, location, Blob, FormData */
!function(a){"use strict";"function"==typeof define&&define.amd?
// Register as an anonymous AMD module:
define(["jquery","jquery.ui.widget"],a):"object"==typeof exports?
// Node/CommonJS:
a(require("jquery"),require("./vendor/jquery.ui.widget")):
// Browser globals:
a(window.jQuery)}(function(a){"use strict";
// Helper function to create drag handlers for dragover/dragenter/dragleave:
function b(b){var c="dragover"===b;return function(d){d.dataTransfer=d.originalEvent&&d.originalEvent.dataTransfer;var e=d.dataTransfer;e&&-1!==a.inArray("Files",e.types)&&this._trigger(b,a.Event(b,{delegatedEvent:d}))!==!1&&(d.preventDefault(),c&&(e.dropEffect="copy"))}}
// Detect file input support, based on
// http://viljamis.com/blog/2012/file-upload-support-on-mobile/
a.support.fileInput=!(new RegExp("(Android (1\\.[0156]|2\\.[01]))|(Windows Phone (OS 7|8\\.0))|(XBLWP)|(ZuneWP)|(WPDesktop)|(w(eb)?OSBrowser)|(webOS)|(Kindle/(1\\.0|2\\.[05]|3\\.0))").test(window.navigator.userAgent)||
// Feature detection for all other devices:
a('<input type="file">').prop("disabled")),
// The FileReader API is not actually used, but works as feature detection,
// as some Safari versions (5?) support XHR file uploads via the FormData API,
// but not non-multipart XHR file uploads.
// window.XMLHttpRequestUpload is not available on IE10, so we check for
// window.ProgressEvent instead to detect XHR2 file upload capability:
a.support.xhrFileUpload=!(!window.ProgressEvent||!window.FileReader),a.support.xhrFormDataFileUpload=!!window.FormData,
// Detect support for Blob slicing (required for chunked uploads):
a.support.blobSlice=window.Blob&&(Blob.prototype.slice||Blob.prototype.webkitSlice||Blob.prototype.mozSlice),
// The fileupload widget listens for change events on file input fields defined
// via fileInput setting and paste or drop events of the given dropZone.
// In addition to the default jQuery Widget methods, the fileupload widget
// exposes the "add" and "send" methods, to add or directly send files using
// the fileupload API.
// By default, files added via file input selection, paste, drag & drop or
// "add" method are uploaded immediately, but it is possible to override
// the "add" callback option to queue file uploads.
a.widget("blueimp.fileupload",{options:{
// The drop target element(s), by the default the complete document.
// Set to null to disable drag & drop support:
dropZone:a(document),
// The paste target element(s), by the default undefined.
// Set to a DOM node or jQuery object to enable file pasting:
pasteZone:void 0,
// The file input field(s), that are listened to for change events.
// If undefined, it is set to the file input fields inside
// of the widget element on plugin initialization.
// Set to null to disable the change listener.
fileInput:void 0,
// By default, the file input field is replaced with a clone after
// each input field change event. This is required for iframe transport
// queues and allows change events to be fired for the same file
// selection, but can be disabled by setting the following option to false:
replaceFileInput:!0,
// The parameter name for the file form data (the request argument name).
// If undefined or empty, the name property of the file input field is
// used, or "files[]" if the file input name property is also empty,
// can be a string or an array of strings:
paramName:void 0,
// By default, each file of a selection is uploaded using an individual
// request for XHR type uploads. Set to false to upload file
// selections in one request each:
singleFileUploads:!0,
// To limit the number of files uploaded with one XHR request,
// set the following option to an integer greater than 0:
limitMultiFileUploads:void 0,
// The following option limits the number of files uploaded with one
// XHR request to keep the request size under or equal to the defined
// limit in bytes:
limitMultiFileUploadSize:void 0,
// Multipart file uploads add a number of bytes to each uploaded file,
// therefore the following option adds an overhead for each file used
// in the limitMultiFileUploadSize configuration:
limitMultiFileUploadSizeOverhead:512,
// Set the following option to true to issue all file upload requests
// in a sequential order:
sequentialUploads:!1,
// To limit the number of concurrent uploads,
// set the following option to an integer greater than 0:
limitConcurrentUploads:void 0,
// Set the following option to true to force iframe transport uploads:
forceIframeTransport:!1,
// Set the following option to the location of a redirect url on the
// origin server, for cross-domain iframe transport uploads:
redirect:void 0,
// The parameter name for the redirect url, sent as part of the form
// data and set to 'redirect' if this option is empty:
redirectParamName:void 0,
// Set the following option to the location of a postMessage window,
// to enable postMessage transport uploads:
postMessage:void 0,
// By default, XHR file uploads are sent as multipart/form-data.
// The iframe transport is always using multipart/form-data.
// Set to false to enable non-multipart XHR uploads:
multipart:!0,
// To upload large files in smaller chunks, set the following option
// to a preferred maximum chunk size. If set to 0, null or undefined,
// or the browser does not support the required Blob API, files will
// be uploaded as a whole.
maxChunkSize:void 0,
// When a non-multipart upload or a chunked multipart upload has been
// aborted, this option can be used to resume the upload by setting
// it to the size of the already uploaded bytes. This option is most
// useful when modifying the options object inside of the "add" or
// "send" callbacks, as the options are cloned for each file upload.
uploadedBytes:void 0,
// By default, failed (abort or error) file uploads are removed from the
// global progress calculation. Set the following option to false to
// prevent recalculating the global progress data:
recalculateProgress:!0,
// Interval in milliseconds to calculate and trigger progress events:
progressInterval:100,
// Interval in milliseconds to calculate progress bitrate:
bitrateInterval:500,
// By default, uploads are started automatically when adding files:
autoUpload:!0,
// Error and info messages:
messages:{uploadedBytes:"Uploaded bytes exceed file size"},
// Translation function, gets the message key to be translated
// and an object with context specific data as arguments:
i18n:function(b,c){return b=this.messages[b]||b.toString(),c&&a.each(c,function(a,c){b=b.replace("{"+a+"}",c)}),b},
// Additional form data to be sent along with the file uploads can be set
// using this option, which accepts an array of objects with name and
// value properties, a function returning such an array, a FormData
// object (for XHR file uploads), or a simple object.
// The form of the first fileInput is given as parameter to the function:
formData:function(a){return a.serializeArray()},
// The add callback is invoked as soon as files are added to the fileupload
// widget (via file input selection, drag & drop, paste or add API call).
// If the singleFileUploads option is enabled, this callback will be
// called once for each file in the selection for XHR file uploads, else
// once for each file selection.
//
// The upload starts when the submit method is invoked on the data parameter.
// The data object contains a files property holding the added files
// and allows you to override plugin options as well as define ajax settings.
//
// Listeners for this callback can also be bound the following way:
// .bind('fileuploadadd', func);
//
// data.submit() returns a Promise object and allows to attach additional
// handlers using jQuery's Deferred callbacks:
// data.submit().done(func).fail(func).always(func);
add:function(b,c){return b.isDefaultPrevented()?!1:void((c.autoUpload||c.autoUpload!==!1&&a(this).fileupload("option","autoUpload"))&&c.process().done(function(){c.submit()}))},
// Other callbacks:
// Callback for the submit event of each file upload:
// submit: function (e, data) {}, // .bind('fileuploadsubmit', func);
// Callback for the start of each file upload request:
// send: function (e, data) {}, // .bind('fileuploadsend', func);
// Callback for successful uploads:
// done: function (e, data) {}, // .bind('fileuploaddone', func);
// Callback for failed (abort or error) uploads:
// fail: function (e, data) {}, // .bind('fileuploadfail', func);
// Callback for completed (success, abort or error) requests:
// always: function (e, data) {}, // .bind('fileuploadalways', func);
// Callback for upload progress events:
// progress: function (e, data) {}, // .bind('fileuploadprogress', func);
// Callback for global upload progress events:
// progressall: function (e, data) {}, // .bind('fileuploadprogressall', func);
// Callback for uploads start, equivalent to the global ajaxStart event:
// start: function (e) {}, // .bind('fileuploadstart', func);
// Callback for uploads stop, equivalent to the global ajaxStop event:
// stop: function (e) {}, // .bind('fileuploadstop', func);
// Callback for change events of the fileInput(s):
// change: function (e, data) {}, // .bind('fileuploadchange', func);
// Callback for paste events to the pasteZone(s):
// paste: function (e, data) {}, // .bind('fileuploadpaste', func);
// Callback for drop events of the dropZone(s):
// drop: function (e, data) {}, // .bind('fileuploaddrop', func);
// Callback for dragover events of the dropZone(s):
// dragover: function (e) {}, // .bind('fileuploaddragover', func);
// Callback for the start of each chunk upload request:
// chunksend: function (e, data) {}, // .bind('fileuploadchunksend', func);
// Callback for successful chunk uploads:
// chunkdone: function (e, data) {}, // .bind('fileuploadchunkdone', func);
// Callback for failed (abort or error) chunk uploads:
// chunkfail: function (e, data) {}, // .bind('fileuploadchunkfail', func);
// Callback for completed (success, abort or error) chunk upload requests:
// chunkalways: function (e, data) {}, // .bind('fileuploadchunkalways', func);
// The plugin options are used as settings object for the ajax calls.
// The following are jQuery ajax settings required for the file uploads:
processData:!1,contentType:!1,cache:!1,timeout:0},
// A list of options that require reinitializing event listeners and/or
// special initialization code:
_specialOptions:["fileInput","dropZone","pasteZone","multipart","forceIframeTransport"],_blobSlice:a.support.blobSlice&&function(){var a=this.slice||this.webkitSlice||this.mozSlice;return a.apply(this,arguments)},_BitrateTimer:function(){this.timestamp=Date.now?Date.now():(new Date).getTime(),this.loaded=0,this.bitrate=0,this.getBitrate=function(a,b,c){var d=a-this.timestamp;return(!this.bitrate||!c||d>c)&&(this.bitrate=(b-this.loaded)*(1e3/d)*8,this.loaded=b,this.timestamp=a),this.bitrate}},_isXHRUpload:function(b){return!b.forceIframeTransport&&(!b.multipart&&a.support.xhrFileUpload||a.support.xhrFormDataFileUpload)},_getFormData:function(b){var c;return"function"===a.type(b.formData)?b.formData(b.form):a.isArray(b.formData)?b.formData:"object"===a.type(b.formData)?(c=[],a.each(b.formData,function(a,b){c.push({name:a,value:b})}),c):[]},_getTotal:function(b){var c=0;return a.each(b,function(a,b){c+=b.size||1}),c},_initProgressObject:function(b){var c={loaded:0,total:0,bitrate:0};b._progress?a.extend(b._progress,c):b._progress=c},_initResponseObject:function(a){var b;if(a._response)for(b in a._response)a._response.hasOwnProperty(b)&&delete a._response[b];else a._response={}},_onProgress:function(b,c){if(b.lengthComputable){var d,e=Date.now?Date.now():(new Date).getTime();if(c._time&&c.progressInterval&&e-c._time<c.progressInterval&&b.loaded!==b.total)return;c._time=e,d=Math.floor(b.loaded/b.total*(c.chunkSize||c._progress.total))+(c.uploadedBytes||0),
// Add the difference from the previously loaded state
// to the global loaded counter:
this._progress.loaded+=d-c._progress.loaded,this._progress.bitrate=this._bitrateTimer.getBitrate(e,this._progress.loaded,c.bitrateInterval),c._progress.loaded=c.loaded=d,c._progress.bitrate=c.bitrate=c._bitrateTimer.getBitrate(e,d,c.bitrateInterval),
// Trigger a custom progress event with a total data property set
// to the file size(s) of the current upload and a loaded data
// property calculated accordingly:
this._trigger("progress",a.Event("progress",{delegatedEvent:b}),c),
// Trigger a global progress event for all current file uploads,
// including ajax calls queued for sequential file uploads:
this._trigger("progressall",a.Event("progressall",{delegatedEvent:b}),this._progress)}},_initProgressListener:function(b){var c=this,d=b.xhr?b.xhr():a.ajaxSettings.xhr();
// Accesss to the native XHR object is required to add event listeners
// for the upload progress event:
d.upload&&(a(d.upload).bind("progress",function(a){var d=a.originalEvent;
// Make sure the progress event properties get copied over:
a.lengthComputable=d.lengthComputable,a.loaded=d.loaded,a.total=d.total,c._onProgress(a,b)}),b.xhr=function(){return d})},_isInstanceOf:function(a,b){
// Cross-frame instanceof check
return Object.prototype.toString.call(b)==="[object "+a+"]"},_initXHRData:function(b){var c,d=this,e=b.files[0],
// Ignore non-multipart setting if not supported:
f=b.multipart||!a.support.xhrFileUpload,g="array"===a.type(b.paramName)?b.paramName[0]:b.paramName;b.headers=a.extend({},b.headers),b.contentRange&&(b.headers["Content-Range"]=b.contentRange),f&&!b.blob&&this._isInstanceOf("File",e)||(b.headers["Content-Disposition"]='attachment; filename="'+encodeURI(e.name)+'"'),f?a.support.xhrFormDataFileUpload&&(b.postMessage?(c=this._getFormData(b),b.blob?c.push({name:g,value:b.blob}):a.each(b.files,function(d,e){c.push({name:"array"===a.type(b.paramName)&&b.paramName[d]||g,value:e})})):(d._isInstanceOf("FormData",b.formData)?c=b.formData:(c=new FormData,a.each(this._getFormData(b),function(a,b){c.append(b.name,b.value)})),b.blob?c.append(g,b.blob,e.name):a.each(b.files,function(e,f){
// This check allows the tests to run with
// dummy objects:
(d._isInstanceOf("File",f)||d._isInstanceOf("Blob",f))&&c.append("array"===a.type(b.paramName)&&b.paramName[e]||g,f,f.uploadName||f.name)})),b.data=c):(b.contentType=e.type||"application/octet-stream",b.data=b.blob||e),
// Blob reference is not needed anymore, free memory:
b.blob=null},_initIframeSettings:function(b){var c=a("<a></a>").prop("href",b.url).prop("host");
// Setting the dataType to iframe enables the iframe transport:
b.dataType="iframe "+(b.dataType||""),
// The iframe transport accepts a serialized array as form data:
b.formData=this._getFormData(b),
// Add redirect url to form data on cross-domain uploads:
b.redirect&&c&&c!==location.host&&b.formData.push({name:b.redirectParamName||"redirect",value:b.redirect})},_initDataSettings:function(a){this._isXHRUpload(a)?(this._chunkedUpload(a,!0)||(a.data||this._initXHRData(a),this._initProgressListener(a)),a.postMessage&&(
// Setting the dataType to postmessage enables the
// postMessage transport:
a.dataType="postmessage "+(a.dataType||""))):this._initIframeSettings(a)},_getParamName:function(b){var c=a(b.fileInput),d=b.paramName;return d?a.isArray(d)||(d=[d]):(d=[],c.each(function(){for(var b=a(this),c=b.prop("name")||"files[]",e=(b.prop("files")||[1]).length;e;)d.push(c),e-=1}),d.length||(d=[c.prop("name")||"files[]"])),d},_initFormSettings:function(b){
// Retrieve missing options from the input field and the
// associated form, if available:
b.form&&b.form.length||(b.form=a(b.fileInput.prop("form")),
// If the given file input doesn't have an associated form,
// use the default widget file input's form:
b.form.length||(b.form=a(this.options.fileInput.prop("form")))),b.paramName=this._getParamName(b),b.url||(b.url=b.form.prop("action")||location.href),
// The HTTP request method must be "POST" or "PUT":
b.type=(b.type||"string"===a.type(b.form.prop("method"))&&b.form.prop("method")||"").toUpperCase(),"POST"!==b.type&&"PUT"!==b.type&&"PATCH"!==b.type&&(b.type="POST"),b.formAcceptCharset||(b.formAcceptCharset=b.form.attr("accept-charset"))},_getAJAXSettings:function(b){var c=a.extend({},this.options,b);return this._initFormSettings(c),this._initDataSettings(c),c},
// jQuery 1.6 doesn't provide .state(),
// while jQuery 1.8+ removed .isRejected() and .isResolved():
_getDeferredState:function(a){return a.state?a.state():a.isResolved()?"resolved":a.isRejected()?"rejected":"pending"},
// Maps jqXHR callbacks to the equivalent
// methods of the given Promise object:
_enhancePromise:function(a){return a.success=a.done,a.error=a.fail,a.complete=a.always,a},
// Creates and returns a Promise object enhanced with
// the jqXHR methods abort, success, error and complete:
_getXHRPromise:function(b,c,d){var e=a.Deferred(),f=e.promise();return c=c||this.options.context||f,b===!0?e.resolveWith(c,d):b===!1&&e.rejectWith(c,d),f.abort=e.promise,this._enhancePromise(f)},
// Adds convenience methods to the data callback argument:
_addConvenienceMethods:function(b,c){var d=this,e=function(b){return a.Deferred().resolveWith(d,b).promise()};c.process=function(b,f){return(b||f)&&(c._processQueue=this._processQueue=(this._processQueue||e([this])).pipe(function(){return c.errorThrown?a.Deferred().rejectWith(d,[c]).promise():e(arguments)}).pipe(b,f)),this._processQueue||e([this])},c.submit=function(){return"pending"!==this.state()&&(c.jqXHR=this.jqXHR=d._trigger("submit",a.Event("submit",{delegatedEvent:b}),this)!==!1&&d._onSend(b,this)),this.jqXHR||d._getXHRPromise()},c.abort=function(){return this.jqXHR?this.jqXHR.abort():(this.errorThrown="abort",d._trigger("fail",null,this),d._getXHRPromise(!1))},c.state=function(){return this.jqXHR?d._getDeferredState(this.jqXHR):this._processQueue?d._getDeferredState(this._processQueue):void 0},c.processing=function(){return!this.jqXHR&&this._processQueue&&"pending"===d._getDeferredState(this._processQueue)},c.progress=function(){return this._progress},c.response=function(){return this._response}},
// Parses the Range header from the server response
// and returns the uploaded bytes:
_getUploadedBytes:function(a){var b=a.getResponseHeader("Range"),c=b&&b.split("-"),d=c&&c.length>1&&parseInt(c[1],10);return d&&d+1},
// Uploads a file in multiple, sequential requests
// by splitting the file up in multiple blob chunks.
// If the second parameter is true, only tests if the file
// should be uploaded in chunks, but does not invoke any
// upload requests:
_chunkedUpload:function(b,c){b.uploadedBytes=b.uploadedBytes||0;var d,e,f=this,g=b.files[0],h=g.size,i=b.uploadedBytes,j=b.maxChunkSize||h,k=this._blobSlice,l=a.Deferred(),m=l.promise();
// The chunk upload method:
return this._isXHRUpload(b)&&k&&(i||h>j)&&!b.data?c?!0:i>=h?(g.error=b.i18n("uploadedBytes"),this._getXHRPromise(!1,b.context,[null,"error",g.error])):(e=function(){var c=a.extend({},b),m=c._progress.loaded;c.blob=k.call(g,i,i+j,g.type),c.chunkSize=c.blob.size,c.contentRange="bytes "+i+"-"+(i+c.chunkSize-1)+"/"+h,f._initXHRData(c),f._initProgressListener(c),d=(f._trigger("chunksend",null,c)!==!1&&a.ajax(c)||f._getXHRPromise(!1,c.context)).done(function(d,g,j){i=f._getUploadedBytes(j)||i+c.chunkSize,m+c.chunkSize-c._progress.loaded&&f._onProgress(a.Event("progress",{lengthComputable:!0,loaded:i-c.uploadedBytes,total:i-c.uploadedBytes}),c),b.uploadedBytes=c.uploadedBytes=i,c.result=d,c.textStatus=g,c.jqXHR=j,f._trigger("chunkdone",null,c),f._trigger("chunkalways",null,c),h>i?e():l.resolveWith(c.context,[d,g,j])}).fail(function(a,b,d){c.jqXHR=a,c.textStatus=b,c.errorThrown=d,f._trigger("chunkfail",null,c),f._trigger("chunkalways",null,c),l.rejectWith(c.context,[a,b,d])})},this._enhancePromise(m),m.abort=function(){return d.abort()},e(),m):!1},_beforeSend:function(a,b){0===this._active&&(
// the start callback is triggered when an upload starts
// and no other uploads are currently running,
// equivalent to the global ajaxStart event:
this._trigger("start"),
// Set timer for global bitrate progress calculation:
this._bitrateTimer=new this._BitrateTimer,
// Reset the global progress values:
this._progress.loaded=this._progress.total=0,this._progress.bitrate=0),
// Make sure the container objects for the .response() and
// .progress() methods on the data object are available
// and reset to their initial state:
this._initResponseObject(b),this._initProgressObject(b),b._progress.loaded=b.loaded=b.uploadedBytes||0,b._progress.total=b.total=this._getTotal(b.files)||1,b._progress.bitrate=b.bitrate=0,this._active+=1,
// Initialize the global progress values:
this._progress.loaded+=b.loaded,this._progress.total+=b.total},_onDone:function(b,c,d,e){var f=e._progress.total,g=e._response;e._progress.loaded<f&&
// Create a progress event if no final progress event
// with loaded equaling total has been triggered:
this._onProgress(a.Event("progress",{lengthComputable:!0,loaded:f,total:f}),e),g.result=e.result=b,g.textStatus=e.textStatus=c,g.jqXHR=e.jqXHR=d,this._trigger("done",null,e)},_onFail:function(a,b,c,d){var e=d._response;d.recalculateProgress&&(
// Remove the failed (error or abort) file upload from
// the global progress calculation:
this._progress.loaded-=d._progress.loaded,this._progress.total-=d._progress.total),e.jqXHR=d.jqXHR=a,e.textStatus=d.textStatus=b,e.errorThrown=d.errorThrown=c,this._trigger("fail",null,d)},_onAlways:function(a,b,c,d){
// jqXHRorResult, textStatus and jqXHRorError are added to the
// options object via done and fail callbacks
this._trigger("always",null,d)},_onSend:function(b,c){c.submit||this._addConvenienceMethods(b,c);var d,e,f,g,h=this,i=h._getAJAXSettings(c),j=function(){
// Set timer for bitrate progress calculation:
return h._sending+=1,i._bitrateTimer=new h._BitrateTimer,d=d||((e||h._trigger("send",a.Event("send",{delegatedEvent:b}),i)===!1)&&h._getXHRPromise(!1,i.context,e)||h._chunkedUpload(i)||a.ajax(i)).done(function(a,b,c){h._onDone(a,b,c,i)}).fail(function(a,b,c){h._onFail(a,b,c,i)}).always(function(a,b,c){if(h._onAlways(a,b,c,i),h._sending-=1,h._active-=1,i.limitConcurrentUploads&&i.limitConcurrentUploads>h._sending)for(
// Start the next queued upload,
// that has not been aborted:
var d=h._slots.shift();d;){if("pending"===h._getDeferredState(d)){d.resolve();break}d=h._slots.shift()}0===h._active&&
// The stop callback is triggered when all uploads have
// been completed, equivalent to the global ajaxStop event:
h._trigger("stop")})};
// Return the piped Promise object, enhanced with an abort method,
// which is delegated to the jqXHR object of the current upload,
// and jqXHR callbacks mapped to the equivalent Promise methods:
return this._beforeSend(b,i),this.options.sequentialUploads||this.options.limitConcurrentUploads&&this.options.limitConcurrentUploads<=this._sending?(this.options.limitConcurrentUploads>1?(f=a.Deferred(),this._slots.push(f),g=f.pipe(j)):(this._sequence=this._sequence.pipe(j,j),g=this._sequence),g.abort=function(){return e=[void 0,"abort","abort"],d?d.abort():(f&&f.rejectWith(i.context,e),j())},this._enhancePromise(g)):j()},_onAdd:function(b,c){var d,e,f,g,h=this,i=!0,j=a.extend({},this.options,c),k=c.files,l=k.length,m=j.limitMultiFileUploads,n=j.limitMultiFileUploadSize,o=j.limitMultiFileUploadSizeOverhead,p=0,q=this._getParamName(j),r=0;if(!l)return!1;if(n&&void 0===k[0].size&&(n=void 0),(j.singleFileUploads||m||n)&&this._isXHRUpload(j))if(j.singleFileUploads||n||!m)if(!j.singleFileUploads&&n)for(f=[],d=[],g=0;l>g;g+=1)p+=k[g].size+o,(g+1===l||p+k[g+1].size+o>n||m&&g+1-r>=m)&&(f.push(k.slice(r,g+1)),e=q.slice(r,g+1),e.length||(e=q),d.push(e),r=g+1,p=0);else d=q;else for(f=[],d=[],g=0;l>g;g+=m)f.push(k.slice(g,g+m)),e=q.slice(g,g+m),e.length||(e=q),d.push(e);else f=[k],d=[q];return c.originalFiles=k,a.each(f||k,function(e,g){var j=a.extend({},c);return j.files=f?g:[g],j.paramName=d[e],h._initResponseObject(j),h._initProgressObject(j),h._addConvenienceMethods(b,j),i=h._trigger("add",a.Event("add",{delegatedEvent:b}),j)}),i},_replaceFileInput:function(b){var c=b.fileInput,d=c.clone(!0),e=c.is(document.activeElement);
// Add a reference for the new cloned file input to the data argument:
b.fileInputClone=d,a("<form></form>").append(d)[0].reset(),
// Detaching allows to insert the fileInput on another form
// without loosing the file input value:
c.after(d).detach(),
// If the fileInput had focus before it was detached,
// restore focus to the inputClone.
e&&d.focus(),
// Avoid memory leaks with the detached file input:
a.cleanData(c.unbind("remove")),
// Replace the original file input element in the fileInput
// elements set with the clone, which has been copied including
// event handlers:
this.options.fileInput=this.options.fileInput.map(function(a,b){return b===c[0]?d[0]:b}),
// If the widget has been initialized on the file input itself,
// override this.element with the file input clone:
c[0]===this.element[0]&&(this.element=d)},_handleFileTreeEntry:function(b,c){var d,e=this,f=a.Deferred(),g=function(a){a&&!a.entry&&(a.entry=b),
// Since $.when returns immediately if one
// Deferred is rejected, we use resolve instead.
// This allows valid files and invalid items
// to be returned together in one set:
f.resolve([a])},h=function(a){e._handleFileTreeEntries(a,c+b.name+"/").done(function(a){f.resolve(a)}).fail(g)},i=function(){d.readEntries(function(a){a.length?(j=j.concat(a),i()):h(j)},g)},j=[];
// Workaround for Chrome bug #149735
// Return an empy list for file system items
// other than files or directories:
return c=c||"",b.isFile?b._file?(b._file.relativePath=c,f.resolve(b._file)):b.file(function(a){a.relativePath=c,f.resolve(a)},g):b.isDirectory?(d=b.createReader(),i()):f.resolve([]),f.promise()},_handleFileTreeEntries:function(b,c){var d=this;return a.when.apply(a,a.map(b,function(a){return d._handleFileTreeEntry(a,c)})).pipe(function(){return Array.prototype.concat.apply([],arguments)})},_getDroppedFiles:function(b){b=b||{};var c=b.items;return c&&c.length&&(c[0].webkitGetAsEntry||c[0].getAsEntry)?this._handleFileTreeEntries(a.map(c,function(a){var b;
// Workaround for Chrome bug #149735:
return a.webkitGetAsEntry?(b=a.webkitGetAsEntry(),b&&(b._file=a.getAsFile()),b):a.getAsEntry()})):a.Deferred().resolve(a.makeArray(b.files)).promise()},_getSingleFileInputFiles:function(b){b=a(b);var c,d,e=b.prop("webkitEntries")||b.prop("entries");if(e&&e.length)return this._handleFileTreeEntries(e);if(c=a.makeArray(b.prop("files")),c.length)void 0===c[0].name&&c[0].fileName&&
// File normalization for Safari 4 and Firefox 3:
a.each(c,function(a,b){b.name=b.fileName,b.size=b.fileSize});else{if(d=b.prop("value"),!d)return a.Deferred().resolve([]).promise();
// If the files property is not available, the browser does not
// support the File API and we add a pseudo File object with
// the input value as name with path information removed:
c=[{name:d.replace(/^.*\\/,"")}]}return a.Deferred().resolve(c).promise()},_getFileInputFiles:function(b){return b instanceof a&&1!==b.length?a.when.apply(a,a.map(b,this._getSingleFileInputFiles)).pipe(function(){return Array.prototype.concat.apply([],arguments)}):this._getSingleFileInputFiles(b)},_onChange:function(b){var c=this,d={fileInput:a(b.target),form:a(b.target.form)};this._getFileInputFiles(d.fileInput).always(function(e){d.files=e,c.options.replaceFileInput&&c._replaceFileInput(d),c._trigger("change",a.Event("change",{delegatedEvent:b}),d)!==!1&&c._onAdd(b,d)})},_onPaste:function(b){var c=b.originalEvent&&b.originalEvent.clipboardData&&b.originalEvent.clipboardData.items,d={files:[]};c&&c.length&&(a.each(c,function(a,b){var c=b.getAsFile&&b.getAsFile();c&&d.files.push(c)}),this._trigger("paste",a.Event("paste",{delegatedEvent:b}),d)!==!1&&this._onAdd(b,d))},_onDrop:function(b){b.dataTransfer=b.originalEvent&&b.originalEvent.dataTransfer;var c=this,d=b.dataTransfer,e={};d&&d.files&&d.files.length&&(b.preventDefault(),this._getDroppedFiles(d).always(function(d){e.files=d,c._trigger("drop",a.Event("drop",{delegatedEvent:b}),e)!==!1&&c._onAdd(b,e)}))},_onDragOver:b("dragover"),_onDragEnter:b("dragenter"),_onDragLeave:b("dragleave"),_initEventHandlers:function(){this._isXHRUpload(this.options)&&(this._on(this.options.dropZone,{dragover:this._onDragOver,drop:this._onDrop,
// event.preventDefault() on dragenter is required for IE10+:
dragenter:this._onDragEnter,
// dragleave is not required, but added for completeness:
dragleave:this._onDragLeave}),this._on(this.options.pasteZone,{paste:this._onPaste})),a.support.fileInput&&this._on(this.options.fileInput,{change:this._onChange})},_destroyEventHandlers:function(){this._off(this.options.dropZone,"dragenter dragleave dragover drop"),this._off(this.options.pasteZone,"paste"),this._off(this.options.fileInput,"change")},_setOption:function(b,c){var d=-1!==a.inArray(b,this._specialOptions);d&&this._destroyEventHandlers(),this._super(b,c),d&&(this._initSpecialOptions(),this._initEventHandlers())},_initSpecialOptions:function(){var b=this.options;void 0===b.fileInput?b.fileInput=this.element.is('input[type="file"]')?this.element:this.element.find('input[type="file"]'):b.fileInput instanceof a||(b.fileInput=a(b.fileInput)),b.dropZone instanceof a||(b.dropZone=a(b.dropZone)),b.pasteZone instanceof a||(b.pasteZone=a(b.pasteZone))},_getRegExp:function(a){var b=a.split("/"),c=b.pop();return b.shift(),new RegExp(b.join("/"),c)},_isRegExpOption:function(b,c){return"url"!==b&&"string"===a.type(c)&&/^\/.*\/[igm]{0,3}$/.test(c)},_initDataAttributes:function(){var b=this,c=this.options,d=this.element.data();
// Initialize options set via HTML5 data-attributes:
a.each(this.element[0].attributes,function(a,e){var f,g=e.name.toLowerCase();/^data-/.test(g)&&(g=g.slice(5).replace(/-[a-z]/g,function(a){return a.charAt(1).toUpperCase()}),f=d[g],b._isRegExpOption(g,f)&&(f=b._getRegExp(f)),c[g]=f)})},_create:function(){this._initDataAttributes(),this._initSpecialOptions(),this._slots=[],this._sequence=this._getXHRPromise(!0),this._sending=this._active=0,this._initProgressObject(this),this._initEventHandlers()},
// This method is exposed to the widget API and allows to query
// the number of active uploads:
active:function(){return this._active},
// This method is exposed to the widget API and allows to query
// the widget upload progress.
// It returns an object with loaded, total and bitrate properties
// for the running uploads:
progress:function(){return this._progress},
// This method is exposed to the widget API and allows adding files
// using the fileupload API. The data parameter accepts an object which
// must have a files property and can contain additional options:
// .fileupload('add', {files: filesList});
add:function(b){var c=this;b&&!this.options.disabled&&(b.fileInput&&!b.files?this._getFileInputFiles(b.fileInput).always(function(a){b.files=a,c._onAdd(null,b)}):(b.files=a.makeArray(b.files),this._onAdd(null,b)))},
// This method is exposed to the widget API and allows sending files
// using the fileupload API. The data parameter accepts an object which
// must have a files or fileInput property and can contain additional options:
// .fileupload('send', {files: filesList});
// The method returns a Promise object for the file upload call.
send:function(b){if(b&&!this.options.disabled){if(b.fileInput&&!b.files){var c,d,e=this,f=a.Deferred(),g=f.promise();return g.abort=function(){return d=!0,c?c.abort():(f.reject(null,"abort","abort"),g)},this._getFileInputFiles(b.fileInput).always(function(a){if(!d){if(!a.length)return void f.reject();b.files=a,c=e._onSend(null,b),c.then(function(a,b,c){f.resolve(a,b,c)},function(a,b,c){f.reject(a,b,c)})}}),this._enhancePromise(g)}if(b.files=a.makeArray(b.files),b.files.length)return this._onSend(null,b)}return this._getXHRPromise(!1,b&&b.context)}})});

/*
 * jQuery File Upload Processing Plugin
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2012, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
/* jshint nomen:false */
/* global define, require, window */
!function(a){"use strict";"function"==typeof define&&define.amd?
// Register as an anonymous AMD module:
define(["jquery","./jquery.fileupload"],a):a("object"==typeof exports?require("jquery"):window.jQuery)}(function(a){"use strict";var b=a.blueimp.fileupload.prototype.options.add;
// The File Upload Processing plugin extends the fileupload widget
// with file processing functionality:
a.widget("blueimp.fileupload",a.blueimp.fileupload,{options:{
// The list of processing actions:
processQueue:[],add:function(c,d){var e=a(this);d.process(function(){return e.fileupload("process",d)}),b.call(this,c,d)}},processActions:{},_processFile:function(b,c){var d=this,e=a.Deferred().resolveWith(d,[b]),f=e.promise();return this._trigger("process",null,b),a.each(b.processQueue,function(b,e){var g=function(b){return c.errorThrown?a.Deferred().rejectWith(d,[c]).promise():d.processActions[e.action].call(d,b,e)};f=f.pipe(g,e.always&&g)}),f.done(function(){d._trigger("processdone",null,b),d._trigger("processalways",null,b)}).fail(function(){d._trigger("processfail",null,b),d._trigger("processalways",null,b)}),f},
// Replaces the settings of each processQueue item that
// are strings starting with an "@", using the remaining
// substring as key for the option map,
// e.g. "@autoUpload" is replaced with options.autoUpload:
_transformProcessQueue:function(b){var c=[];a.each(b.processQueue,function(){var d={},e=this.action,f=this.prefix===!0?e:this.prefix;a.each(this,function(c,e){"string"===a.type(e)&&"@"===e.charAt(0)?d[c]=b[e.slice(1)||(f?f+c.charAt(0).toUpperCase()+c.slice(1):c)]:d[c]=e}),c.push(d)}),b.processQueue=c},
// Returns the number of files currently in the processsing queue:
processing:function(){return this._processing},
// Processes the files given as files property of the data parameter,
// returns a Promise object that allows to bind callbacks:
process:function(b){var c=this,d=a.extend({},this.options,b);return d.processQueue&&d.processQueue.length&&(this._transformProcessQueue(d),0===this._processing&&this._trigger("processstart"),a.each(b.files,function(e){var f=e?a.extend({},d):d,g=function(){return b.errorThrown?a.Deferred().rejectWith(c,[b]).promise():c._processFile(f,b)};f.index=e,c._processing+=1,c._processingQueue=c._processingQueue.pipe(g,g).always(function(){c._processing-=1,0===c._processing&&c._trigger("processstop")})})),this._processingQueue},_create:function(){this._super(),this._processing=0,this._processingQueue=a.Deferred().resolveWith(this).promise()}})});

/*
 * jQuery File Upload Validation Plugin
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2013, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
/* global define, require, window */
!function(a){"use strict";"function"==typeof define&&define.amd?
// Register as an anonymous AMD module:
define(["jquery","./jquery.fileupload-process"],a):a("object"==typeof exports?require("jquery"):window.jQuery)}(function(a){"use strict";
// Append to the default processQueue:
a.blueimp.fileupload.prototype.options.processQueue.push({action:"validate",
// Always trigger this action,
// even if the previous action was rejected:
always:!0,
// Options taken from the global options map:
acceptFileTypes:"@",maxFileSize:"@",minFileSize:"@",maxNumberOfFiles:"@",disabled:"@disableValidation"}),
// The File Upload Validation plugin extends the fileupload widget
// with file validation functionality:
a.widget("blueimp.fileupload",a.blueimp.fileupload,{options:{/*
            // The regular expression for allowed file types, matches
            // against either file type or file name:
            acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
            // The maximum allowed file size in bytes:
            maxFileSize: 10000000, // 10 MB
            // The minimum allowed file size in bytes:
            minFileSize: undefined, // No minimal file size
            // The limit of files to be uploaded:
            maxNumberOfFiles: 10,
            */
// Function returning the current number of files,
// has to be overriden for maxNumberOfFiles validation:
getNumberOfFiles:a.noop,
// Error and info messages:
messages:{maxNumberOfFiles:"Maximum number of files exceeded",acceptFileTypes:"File type not allowed",maxFileSize:"File is too large",minFileSize:"File is too small"}},processActions:{validate:function(b,c){if(c.disabled)return b;var d,e=a.Deferred(),f=this.options,g=b.files[b.index];return(c.minFileSize||c.maxFileSize)&&(d=g.size),"number"===a.type(c.maxNumberOfFiles)&&(f.getNumberOfFiles()||0)+b.files.length>c.maxNumberOfFiles?g.error=f.i18n("maxNumberOfFiles"):!c.acceptFileTypes||c.acceptFileTypes.test(g.type)||c.acceptFileTypes.test(g.name)?d>c.maxFileSize?g.error=f.i18n("maxFileSize"):"number"===a.type(d)&&d<c.minFileSize?g.error=f.i18n("minFileSize"):delete g.error:g.error=f.i18n("acceptFileTypes"),g.error||b.files.error?(b.files.error=!0,e.rejectWith(this,[b])):e.resolveWith(this,[b]),e.promise()}}})});

+function(a){"use strict";function b(b){return this.each(function(){var c=a(this),e=c.data("bs.alert");e||c.data("bs.alert",e=new d(this)),"string"==typeof b&&e[b].call(c)})}var c='[data-dismiss="alert"]',d=function(b){a(b).on("click",c,this.close)};d.VERSION="3.3.6",d.TRANSITION_DURATION=150,d.prototype.close=function(b){function c(){g.detach().trigger("closed.bs.alert").remove()}var e=a(this),f=e.attr("data-target");f||(f=e.attr("href"),f=f&&f.replace(/.*(?=#[^\s]*$)/,""));var g=a(f);b&&b.preventDefault(),g.length||(g=e.closest(".alert")),g.trigger(b=a.Event("close.bs.alert")),b.isDefaultPrevented()||(g.removeClass("in"),a.support.transition&&g.hasClass("fade")?g.one("bsTransitionEnd",c).emulateTransitionEnd(d.TRANSITION_DURATION):c())};var e=a.fn.alert;a.fn.alert=b,a.fn.alert.Constructor=d,a.fn.alert.noConflict=function(){return a.fn.alert=e,this},a(document).on("click.bs.alert.data-api",c,d.prototype.close)}(jQuery),+function(a){"use strict";function b(b){return this.each(function(){var d=a(this),e=d.data("bs.button"),f="object"==typeof b&&b;e||d.data("bs.button",e=new c(this,f)),"toggle"==b?e.toggle():b&&e.setState(b)})}var c=function(b,d){this.$element=a(b),this.options=a.extend({},c.DEFAULTS,d),this.isLoading=!1};c.VERSION="3.3.6",c.DEFAULTS={loadingText:"loading..."},c.prototype.setState=function(b){var c="disabled",d=this.$element,e=d.is("input")?"val":"html",f=d.data();b+="Text",null==f.resetText&&d.data("resetText",d[e]()),setTimeout(a.proxy(function(){d[e](null==f[b]?this.options[b]:f[b]),"loadingText"==b?(this.isLoading=!0,d.addClass(c).attr(c,c)):this.isLoading&&(this.isLoading=!1,d.removeClass(c).removeAttr(c))},this),0)},c.prototype.toggle=function(){var a=!0,b=this.$element.closest('[data-toggle="buttons"]');if(b.length){var c=this.$element.find("input");"radio"==c.prop("type")?(c.prop("checked")&&(a=!1),b.find(".active").removeClass("active"),this.$element.addClass("active")):"checkbox"==c.prop("type")&&(c.prop("checked")!==this.$element.hasClass("active")&&(a=!1),this.$element.toggleClass("active")),c.prop("checked",this.$element.hasClass("active")),a&&c.trigger("change")}else this.$element.attr("aria-pressed",!this.$element.hasClass("active")),this.$element.toggleClass("active")};var d=a.fn.button;a.fn.button=b,a.fn.button.Constructor=c,a.fn.button.noConflict=function(){return a.fn.button=d,this},a(document).on("click.bs.button.data-api",'[data-toggle^="button"]',function(c){var d=a(c.target);d.hasClass("btn")||(d=d.closest(".btn")),b.call(d,"toggle"),a(c.target).is('input[type="radio"]')||a(c.target).is('input[type="checkbox"]')||c.preventDefault()}).on("focus.bs.button.data-api blur.bs.button.data-api",'[data-toggle^="button"]',function(b){a(b.target).closest(".btn").toggleClass("focus",/^focus(in)?$/.test(b.type))})}(jQuery),+function(a){"use strict";function b(b){var c=b.attr("data-target");c||(c=b.attr("href"),c=c&&/#[A-Za-z]/.test(c)&&c.replace(/.*(?=#[^\s]*$)/,""));var d=c&&a(c);return d&&d.length?d:b.parent()}function c(c){c&&3===c.which||(a(e).remove(),a(f).each(function(){var d=a(this),e=b(d),f={relatedTarget:this};e.hasClass("open")&&(c&&"click"==c.type&&/input|textarea/i.test(c.target.tagName)&&a.contains(e[0],c.target)||(e.trigger(c=a.Event("hide.bs.dropdown",f)),c.isDefaultPrevented()||(d.attr("aria-expanded","false"),e.removeClass("open").trigger(a.Event("hidden.bs.dropdown",f)))))}))}function d(b){return this.each(function(){var c=a(this),d=c.data("bs.dropdown");d||c.data("bs.dropdown",d=new g(this)),"string"==typeof b&&d[b].call(c)})}var e=".dropdown-backdrop",f='[data-toggle="dropdown"]',g=function(b){a(b).on("click.bs.dropdown",this.toggle)};g.VERSION="3.3.6",g.prototype.toggle=function(d){var e=a(this);if(!e.is(".disabled, :disabled")){var f=b(e),g=f.hasClass("open");if(c(),!g){"ontouchstart"in document.documentElement&&!f.closest(".navbar-nav").length&&a(document.createElement("div")).addClass("dropdown-backdrop").insertAfter(a(this)).on("click",c);var h={relatedTarget:this};if(f.trigger(d=a.Event("show.bs.dropdown",h)),d.isDefaultPrevented())return;e.trigger("focus").attr("aria-expanded","true"),f.toggleClass("open").trigger(a.Event("shown.bs.dropdown",h))}return!1}},g.prototype.keydown=function(c){if(/(38|40|27|32)/.test(c.which)&&!/input|textarea/i.test(c.target.tagName)){var d=a(this);if(c.preventDefault(),c.stopPropagation(),!d.is(".disabled, :disabled")){var e=b(d),g=e.hasClass("open");if(!g&&27!=c.which||g&&27==c.which)return 27==c.which&&e.find(f).trigger("focus"),d.trigger("click");var h=" li:not(.disabled):visible a",i=e.find(".dropdown-menu"+h);if(i.length){var j=i.index(c.target);38==c.which&&j>0&&j--,40==c.which&&j<i.length-1&&j++,~j||(j=0),i.eq(j).trigger("focus")}}}};var h=a.fn.dropdown;a.fn.dropdown=d,a.fn.dropdown.Constructor=g,a.fn.dropdown.noConflict=function(){return a.fn.dropdown=h,this},a(document).on("click.bs.dropdown.data-api",c).on("click.bs.dropdown.data-api",".dropdown form",function(a){a.stopPropagation()}).on("click.bs.dropdown.data-api",f,g.prototype.toggle).on("keydown.bs.dropdown.data-api",f,g.prototype.keydown).on("keydown.bs.dropdown.data-api",".dropdown-menu",g.prototype.keydown)}(jQuery),+function(a){"use strict";function b(b){return this.each(function(){var d=a(this),e=d.data("bs.tab");e||d.data("bs.tab",e=new c(this)),"string"==typeof b&&e[b]()})}var c=function(b){this.element=a(b)};c.VERSION="3.3.6",c.TRANSITION_DURATION=150,c.prototype.show=function(){var b=this.element,c=b.closest("ul:not(.dropdown-menu)"),d=b.data("target");if(d||(d=b.attr("href"),d=d&&d.replace(/.*(?=#[^\s]*$)/,"")),!b.parent("li").hasClass("active")){var e=c.find(".active:last a"),f=a.Event("hide.bs.tab",{relatedTarget:b[0]}),g=a.Event("show.bs.tab",{relatedTarget:e[0]});if(e.trigger(f),b.trigger(g),!g.isDefaultPrevented()&&!f.isDefaultPrevented()){var h=a(d);this.activate(b.closest("li"),c),this.activate(h,h.parent(),function(){e.trigger({type:"hidden.bs.tab",relatedTarget:b[0]}),b.trigger({type:"shown.bs.tab",relatedTarget:e[0]})})}}},c.prototype.activate=function(b,d,e){function f(){g.removeClass("active").find("> .dropdown-menu > .active").removeClass("active").end().find('[data-toggle="tab"]').attr("aria-expanded",!1),b.addClass("active").find('[data-toggle="tab"]').attr("aria-expanded",!0),h?(b[0].offsetWidth,b.addClass("in")):b.removeClass("fade"),b.parent(".dropdown-menu").length&&b.closest("li.dropdown").addClass("active").end().find('[data-toggle="tab"]').attr("aria-expanded",!0),e&&e()}var g=d.find("> .active"),h=e&&a.support.transition&&(g.length&&g.hasClass("fade")||!!d.find("> .fade").length);g.length&&h?g.one("bsTransitionEnd",f).emulateTransitionEnd(c.TRANSITION_DURATION):f(),g.removeClass("in")};var d=a.fn.tab;a.fn.tab=b,a.fn.tab.Constructor=c,a.fn.tab.noConflict=function(){return a.fn.tab=d,this};var e=function(c){c.preventDefault(),b.call(a(this),"show")};a(document).on("click.bs.tab.data-api",'[data-toggle="tab"]',e).on("click.bs.tab.data-api",'[data-toggle="pill"]',e)}(jQuery),+function(a){"use strict";function b(){var a=document.createElement("bootstrap"),b={WebkitTransition:"webkitTransitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd otransitionend",transition:"transitionend"};for(var c in b)if(void 0!==a.style[c])return{end:b[c]};return!1}a.fn.emulateTransitionEnd=function(b){var c=!1,d=this;a(this).one("bsTransitionEnd",function(){c=!0});var e=function(){c||a(d).trigger(a.support.transition.end)};return setTimeout(e,b),this},a(function(){a.support.transition=b(),a.support.transition&&(a.event.special.bsTransitionEnd={bindType:a.support.transition.end,delegateType:a.support.transition.end,handle:function(b){return a(b.target).is(this)?b.handleObj.handler.apply(this,arguments):void 0}})})}(jQuery),+function(a){"use strict";function b(b,d){return this.each(function(){var e=a(this),f=e.data("bs.modal"),g=a.extend({},c.DEFAULTS,e.data(),"object"==typeof b&&b);f||e.data("bs.modal",f=new c(this,g)),"string"==typeof b?f[b](d):g.show&&f.show(d)})}var c=function(b,c){this.options=c,this.$body=a(document.body),this.$element=a(b),this.$dialog=this.$element.find(".modal-dialog"),this.$backdrop=null,this.isShown=null,this.originalBodyPad=null,this.scrollbarWidth=0,this.ignoreBackdropClick=!1,this.options.remote&&this.$element.find(".modal-content").load(this.options.remote,a.proxy(function(){this.$element.trigger("loaded.bs.modal")},this))};c.VERSION="3.3.6",c.TRANSITION_DURATION=300,c.BACKDROP_TRANSITION_DURATION=150,c.DEFAULTS={backdrop:!0,keyboard:!0,show:!0},c.prototype.toggle=function(a){return this.isShown?this.hide():this.show(a)},c.prototype.show=function(b){var d=this,e=a.Event("show.bs.modal",{relatedTarget:b});this.$element.trigger(e),this.isShown||e.isDefaultPrevented()||(this.isShown=!0,this.checkScrollbar(),this.setScrollbar(),this.$body.addClass("modal-open"),this.escape(),this.resize(),this.$element.on("click.dismiss.bs.modal",'[data-dismiss="modal"]',a.proxy(this.hide,this)),this.$dialog.on("mousedown.dismiss.bs.modal",function(){d.$element.one("mouseup.dismiss.bs.modal",function(b){a(b.target).is(d.$element)&&(d.ignoreBackdropClick=!0)})}),this.backdrop(function(){var e=a.support.transition&&d.$element.hasClass("fade");d.$element.parent().length||d.$element.appendTo(d.$body),d.$element.show().scrollTop(0),d.adjustDialog(),e&&d.$element[0].offsetWidth,d.$element.addClass("in"),d.enforceFocus();var f=a.Event("shown.bs.modal",{relatedTarget:b});e?d.$dialog.one("bsTransitionEnd",function(){d.$element.trigger("focus").trigger(f)}).emulateTransitionEnd(c.TRANSITION_DURATION):d.$element.trigger("focus").trigger(f)}))},c.prototype.hide=function(b){b&&b.preventDefault(),b=a.Event("hide.bs.modal"),this.$element.trigger(b),this.isShown&&!b.isDefaultPrevented()&&(this.isShown=!1,this.escape(),this.resize(),a(document).off("focusin.bs.modal"),this.$element.removeClass("in").off("click.dismiss.bs.modal").off("mouseup.dismiss.bs.modal"),this.$dialog.off("mousedown.dismiss.bs.modal"),a.support.transition&&this.$element.hasClass("fade")?this.$element.one("bsTransitionEnd",a.proxy(this.hideModal,this)).emulateTransitionEnd(c.TRANSITION_DURATION):this.hideModal())},c.prototype.enforceFocus=function(){a(document).off("focusin.bs.modal").on("focusin.bs.modal",a.proxy(function(a){this.$element[0]===a.target||this.$element.has(a.target).length||this.$element.trigger("focus")},this))},c.prototype.escape=function(){this.isShown&&this.options.keyboard?this.$element.on("keydown.dismiss.bs.modal",a.proxy(function(a){27==a.which&&this.hide()},this)):this.isShown||this.$element.off("keydown.dismiss.bs.modal")},c.prototype.resize=function(){this.isShown?a(window).on("resize.bs.modal",a.proxy(this.handleUpdate,this)):a(window).off("resize.bs.modal")},c.prototype.hideModal=function(){var a=this;this.$element.hide(),this.backdrop(function(){a.$body.removeClass("modal-open"),a.resetAdjustments(),a.resetScrollbar(),a.$element.trigger("hidden.bs.modal")})},c.prototype.removeBackdrop=function(){this.$backdrop&&this.$backdrop.remove(),this.$backdrop=null},c.prototype.backdrop=function(b){var d=this,e=this.$element.hasClass("fade")?"fade":"";if(this.isShown&&this.options.backdrop){var f=a.support.transition&&e;if(this.$backdrop=a(document.createElement("div")).addClass("modal-backdrop "+e).appendTo(this.$body),this.$element.on("click.dismiss.bs.modal",a.proxy(function(a){return this.ignoreBackdropClick?void(this.ignoreBackdropClick=!1):void(a.target===a.currentTarget&&("static"==this.options.backdrop?this.$element[0].focus():this.hide()))},this)),f&&this.$backdrop[0].offsetWidth,this.$backdrop.addClass("in"),!b)return;f?this.$backdrop.one("bsTransitionEnd",b).emulateTransitionEnd(c.BACKDROP_TRANSITION_DURATION):b()}else if(!this.isShown&&this.$backdrop){this.$backdrop.removeClass("in");var g=function(){d.removeBackdrop(),b&&b()};a.support.transition&&this.$element.hasClass("fade")?this.$backdrop.one("bsTransitionEnd",g).emulateTransitionEnd(c.BACKDROP_TRANSITION_DURATION):g()}else b&&b()},c.prototype.handleUpdate=function(){this.adjustDialog()},c.prototype.adjustDialog=function(){var a=this.$element[0].scrollHeight>document.documentElement.clientHeight;this.$element.css({paddingLeft:!this.bodyIsOverflowing&&a?this.scrollbarWidth:"",paddingRight:this.bodyIsOverflowing&&!a?this.scrollbarWidth:""})},c.prototype.resetAdjustments=function(){this.$element.css({paddingLeft:"",paddingRight:""})},c.prototype.checkScrollbar=function(){var a=window.innerWidth;if(!a){var b=document.documentElement.getBoundingClientRect();a=b.right-Math.abs(b.left)}this.bodyIsOverflowing=document.body.clientWidth<a,this.scrollbarWidth=this.measureScrollbar()},c.prototype.setScrollbar=function(){var a=parseInt(this.$body.css("padding-right")||0,10);this.originalBodyPad=document.body.style.paddingRight||"",this.bodyIsOverflowing&&this.$body.css("padding-right",a+this.scrollbarWidth)},c.prototype.resetScrollbar=function(){this.$body.css("padding-right",this.originalBodyPad)},c.prototype.measureScrollbar=function(){var a=document.createElement("div");a.className="modal-scrollbar-measure",this.$body.append(a);var b=a.offsetWidth-a.clientWidth;return this.$body[0].removeChild(a),b};var d=a.fn.modal;a.fn.modal=b,a.fn.modal.Constructor=c,a.fn.modal.noConflict=function(){return a.fn.modal=d,this},a(document).on("click.bs.modal.data-api",'[data-toggle="modal"]',function(c){var d=a(this),e=d.attr("href"),f=a(d.attr("data-target")||e&&e.replace(/.*(?=#[^\s]+$)/,"")),g=f.data("bs.modal")?"toggle":a.extend({remote:!/#/.test(e)&&e},f.data(),d.data());d.is("a")&&c.preventDefault(),f.one("show.bs.modal",function(a){a.isDefaultPrevented()||f.one("hidden.bs.modal",function(){d.is(":visible")&&d.trigger("focus")})}),b.call(f,g,this)})}(jQuery),+function(a){"use strict";function b(b){return this.each(function(){var d=a(this),e=d.data("bs.tooltip"),f="object"==typeof b&&b;!e&&/destroy|hide/.test(b)||(e||d.data("bs.tooltip",e=new c(this,f)),"string"==typeof b&&e[b]())})}var c=function(a,b){this.type=null,this.options=null,this.enabled=null,this.timeout=null,this.hoverState=null,this.$element=null,this.inState=null,this.init("tooltip",a,b)};c.VERSION="3.3.6",c.TRANSITION_DURATION=150,c.DEFAULTS={animation:!0,placement:"top",selector:!1,template:'<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",title:"",delay:0,html:!1,container:!1,viewport:{selector:"body",padding:0}},c.prototype.init=function(b,c,d){if(this.enabled=!0,this.type=b,this.$element=a(c),this.options=this.getOptions(d),this.$viewport=this.options.viewport&&a(a.isFunction(this.options.viewport)?this.options.viewport.call(this,this.$element):this.options.viewport.selector||this.options.viewport),this.inState={click:!1,hover:!1,focus:!1},this.$element[0]instanceof document.constructor&&!this.options.selector)throw new Error("`selector` option must be specified when initializing "+this.type+" on the window.document object!");for(var e=this.options.trigger.split(" "),f=e.length;f--;){var g=e[f];if("click"==g)this.$element.on("click."+this.type,this.options.selector,a.proxy(this.toggle,this));else if("manual"!=g){var h="hover"==g?"mouseenter":"focusin",i="hover"==g?"mouseleave":"focusout";this.$element.on(h+"."+this.type,this.options.selector,a.proxy(this.enter,this)),this.$element.on(i+"."+this.type,this.options.selector,a.proxy(this.leave,this))}}this.options.selector?this._options=a.extend({},this.options,{trigger:"manual",selector:""}):this.fixTitle()},c.prototype.getDefaults=function(){return c.DEFAULTS},c.prototype.getOptions=function(b){return b=a.extend({},this.getDefaults(),this.$element.data(),b),b.delay&&"number"==typeof b.delay&&(b.delay={show:b.delay,hide:b.delay}),b},c.prototype.getDelegateOptions=function(){var b={},c=this.getDefaults();return this._options&&a.each(this._options,function(a,d){c[a]!=d&&(b[a]=d)}),b},c.prototype.enter=function(b){var c=b instanceof this.constructor?b:a(b.currentTarget).data("bs."+this.type);return c||(c=new this.constructor(b.currentTarget,this.getDelegateOptions()),a(b.currentTarget).data("bs."+this.type,c)),b instanceof a.Event&&(c.inState["focusin"==b.type?"focus":"hover"]=!0),c.tip().hasClass("in")||"in"==c.hoverState?void(c.hoverState="in"):(clearTimeout(c.timeout),c.hoverState="in",c.options.delay&&c.options.delay.show?void(c.timeout=setTimeout(function(){"in"==c.hoverState&&c.show()},c.options.delay.show)):c.show())},c.prototype.isInStateTrue=function(){for(var a in this.inState)if(this.inState[a])return!0;return!1},c.prototype.leave=function(b){var c=b instanceof this.constructor?b:a(b.currentTarget).data("bs."+this.type);return c||(c=new this.constructor(b.currentTarget,this.getDelegateOptions()),a(b.currentTarget).data("bs."+this.type,c)),b instanceof a.Event&&(c.inState["focusout"==b.type?"focus":"hover"]=!1),c.isInStateTrue()?void 0:(clearTimeout(c.timeout),c.hoverState="out",c.options.delay&&c.options.delay.hide?void(c.timeout=setTimeout(function(){"out"==c.hoverState&&c.hide()},c.options.delay.hide)):c.hide())},c.prototype.show=function(){var b=a.Event("show.bs."+this.type);if(this.hasContent()&&this.enabled){this.$element.trigger(b);var d=a.contains(this.$element[0].ownerDocument.documentElement,this.$element[0]);if(b.isDefaultPrevented()||!d)return;var e=this,f=this.tip(),g=this.getUID(this.type);this.setContent(),f.attr("id",g),this.$element.attr("aria-describedby",g),this.options.animation&&f.addClass("fade");var h="function"==typeof this.options.placement?this.options.placement.call(this,f[0],this.$element[0]):this.options.placement,i=/\s?auto?\s?/i,j=i.test(h);j&&(h=h.replace(i,"")||"top"),f.detach().css({top:0,left:0,display:"block"}).addClass(h).data("bs."+this.type,this),this.options.container?f.appendTo(this.options.container):f.insertAfter(this.$element),this.$element.trigger("inserted.bs."+this.type);var k=this.getPosition(),l=f[0].offsetWidth,m=f[0].offsetHeight;if(j){var n=h,o=this.getPosition(this.$viewport);h="bottom"==h&&k.bottom+m>o.bottom?"top":"top"==h&&k.top-m<o.top?"bottom":"right"==h&&k.right+l>o.width?"left":"left"==h&&k.left-l<o.left?"right":h,f.removeClass(n).addClass(h)}var p=this.getCalculatedOffset(h,k,l,m);this.applyPlacement(p,h);var q=function(){var a=e.hoverState;e.$element.trigger("shown.bs."+e.type),e.hoverState=null,"out"==a&&e.leave(e)};a.support.transition&&this.$tip.hasClass("fade")?f.one("bsTransitionEnd",q).emulateTransitionEnd(c.TRANSITION_DURATION):q()}},c.prototype.applyPlacement=function(b,c){var d=this.tip(),e=d[0].offsetWidth,f=d[0].offsetHeight,g=parseInt(d.css("margin-top"),10),h=parseInt(d.css("margin-left"),10);isNaN(g)&&(g=0),isNaN(h)&&(h=0),b.top+=g,b.left+=h,a.offset.setOffset(d[0],a.extend({using:function(a){d.css({top:Math.round(a.top),left:Math.round(a.left)})}},b),0),d.addClass("in");var i=d[0].offsetWidth,j=d[0].offsetHeight;"top"==c&&j!=f&&(b.top=b.top+f-j);var k=this.getViewportAdjustedDelta(c,b,i,j);k.left?b.left+=k.left:b.top+=k.top;var l=/top|bottom/.test(c),m=l?2*k.left-e+i:2*k.top-f+j,n=l?"offsetWidth":"offsetHeight";d.offset(b),this.replaceArrow(m,d[0][n],l)},c.prototype.replaceArrow=function(a,b,c){this.arrow().css(c?"left":"top",50*(1-a/b)+"%").css(c?"top":"left","")},c.prototype.setContent=function(){var a=this.tip(),b=this.getTitle();a.find(".tooltip-inner")[this.options.html?"html":"text"](b),a.removeClass("fade in top bottom left right")},c.prototype.hide=function(b){function d(){"in"!=e.hoverState&&f.detach(),e.$element.removeAttr("aria-describedby").trigger("hidden.bs."+e.type),b&&b()}var e=this,f=a(this.$tip),g=a.Event("hide.bs."+this.type);return this.$element.trigger(g),g.isDefaultPrevented()?void 0:(f.removeClass("in"),a.support.transition&&f.hasClass("fade")?f.one("bsTransitionEnd",d).emulateTransitionEnd(c.TRANSITION_DURATION):d(),this.hoverState=null,this)},c.prototype.fixTitle=function(){var a=this.$element;(a.attr("title")||"string"!=typeof a.attr("data-original-title"))&&a.attr("data-original-title",a.attr("title")||"").attr("title","")},c.prototype.hasContent=function(){return this.getTitle()},c.prototype.getPosition=function(b){b=b||this.$element;var c=b[0],d="BODY"==c.tagName,e=c.getBoundingClientRect();null==e.width&&(e=a.extend({},e,{width:e.right-e.left,height:e.bottom-e.top}));var f=d?{top:0,left:0}:b.offset(),g={scroll:d?document.documentElement.scrollTop||document.body.scrollTop:b.scrollTop()},h=d?{width:a(window).width(),height:a(window).height()}:null;return a.extend({},e,g,h,f)},c.prototype.getCalculatedOffset=function(a,b,c,d){return"bottom"==a?{top:b.top+b.height,left:b.left+b.width/2-c/2}:"top"==a?{top:b.top-d,left:b.left+b.width/2-c/2}:"left"==a?{top:b.top+b.height/2-d/2,left:b.left-c}:{top:b.top+b.height/2-d/2,left:b.left+b.width}},c.prototype.getViewportAdjustedDelta=function(a,b,c,d){var e={top:0,left:0};if(!this.$viewport)return e;var f=this.options.viewport&&this.options.viewport.padding||0,g=this.getPosition(this.$viewport);if(/right|left/.test(a)){var h=b.top-f-g.scroll,i=b.top+f-g.scroll+d;h<g.top?e.top=g.top-h:i>g.top+g.height&&(e.top=g.top+g.height-i)}else{var j=b.left-f,k=b.left+f+c;j<g.left?e.left=g.left-j:k>g.right&&(e.left=g.left+g.width-k)}return e},c.prototype.getTitle=function(){var a,b=this.$element,c=this.options;return a=b.attr("data-original-title")||("function"==typeof c.title?c.title.call(b[0]):c.title)},c.prototype.getUID=function(a){do a+=~~(1e6*Math.random());while(document.getElementById(a));return a},c.prototype.tip=function(){if(!this.$tip&&(this.$tip=a(this.options.template),1!=this.$tip.length))throw new Error(this.type+" `template` option must consist of exactly 1 top-level element!");return this.$tip},c.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".tooltip-arrow")},c.prototype.enable=function(){this.enabled=!0},c.prototype.disable=function(){this.enabled=!1},c.prototype.toggleEnabled=function(){this.enabled=!this.enabled},c.prototype.toggle=function(b){var c=this;b&&(c=a(b.currentTarget).data("bs."+this.type),c||(c=new this.constructor(b.currentTarget,this.getDelegateOptions()),a(b.currentTarget).data("bs."+this.type,c))),b?(c.inState.click=!c.inState.click,c.isInStateTrue()?c.enter(c):c.leave(c)):c.tip().hasClass("in")?c.leave(c):c.enter(c)},c.prototype.destroy=function(){var a=this;clearTimeout(this.timeout),this.hide(function(){a.$element.off("."+a.type).removeData("bs."+a.type),a.$tip&&a.$tip.detach(),a.$tip=null,a.$arrow=null,a.$viewport=null})};var d=a.fn.tooltip;a.fn.tooltip=b,a.fn.tooltip.Constructor=c,a.fn.tooltip.noConflict=function(){return a.fn.tooltip=d,this}}(jQuery),+function(a){"use strict";function b(b){return this.each(function(){var d=a(this),e=d.data("bs.popover"),f="object"==typeof b&&b;!e&&/destroy|hide/.test(b)||(e||d.data("bs.popover",e=new c(this,f)),"string"==typeof b&&e[b]())})}var c=function(a,b){this.init("popover",a,b)};if(!a.fn.tooltip)throw new Error("Popover requires tooltip.js");c.VERSION="3.3.6",c.DEFAULTS=a.extend({},a.fn.tooltip.Constructor.DEFAULTS,{placement:"right",trigger:"click",content:"",template:'<div class="popover" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>'}),c.prototype=a.extend({},a.fn.tooltip.Constructor.prototype),c.prototype.constructor=c,c.prototype.getDefaults=function(){return c.DEFAULTS},c.prototype.setContent=function(){var a=this.tip(),b=this.getTitle(),c=this.getContent();a.find(".popover-title")[this.options.html?"html":"text"](b),a.find(".popover-content").children().detach().end()[this.options.html?"string"==typeof c?"html":"append":"text"](c),a.removeClass("fade top bottom left right in"),a.find(".popover-title").html()||a.find(".popover-title").hide()},c.prototype.hasContent=function(){return this.getTitle()||this.getContent()},c.prototype.getContent=function(){var a=this.$element,b=this.options;return a.attr("data-content")||("function"==typeof b.content?b.content.call(a[0]):b.content)},c.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".arrow")};var d=a.fn.popover;a.fn.popover=b,a.fn.popover.Constructor=c,a.fn.popover.noConflict=function(){return a.fn.popover=d,this}}(jQuery);

/*! Select2 4.0.1 | https://github.com/select2/select2/blob/master/LICENSE.md */!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):jQuery)}(function(a){var b=function(){if(a&&a.fn&&a.fn.select2&&a.fn.select2.amd)var b=a.fn.select2.amd;var b;return function(){if(!b||!b.requirejs){b?c=b:b={};var a,c,d;!function(b){function e(a,b){return u.call(a,b)}function f(a,b){var c,d,e,f,g,h,i,j,k,l,m,n=b&&b.split("/"),o=s.map,p=o&&o["*"]||{};if(a&&"."===a.charAt(0))if(b){for(a=a.split("/"),g=a.length-1,s.nodeIdCompat&&w.test(a[g])&&(a[g]=a[g].replace(w,"")),a=n.slice(0,n.length-1).concat(a),k=0;k<a.length;k+=1)if(m=a[k],"."===m)a.splice(k,1),k-=1;else if(".."===m){if(1===k&&(".."===a[2]||".."===a[0]))break;k>0&&(a.splice(k-1,2),k-=2)}a=a.join("/")}else 0===a.indexOf("./")&&(a=a.substring(2));if((n||p)&&o){for(c=a.split("/"),k=c.length;k>0;k-=1){if(d=c.slice(0,k).join("/"),n)for(l=n.length;l>0;l-=1)if(e=o[n.slice(0,l).join("/")],e&&(e=e[d])){f=e,h=k;break}if(f)break;!i&&p&&p[d]&&(i=p[d],j=k)}!f&&i&&(f=i,h=j),f&&(c.splice(0,h,f),a=c.join("/"))}return a}function g(a,c){return function(){var d=v.call(arguments,0);return"string"!=typeof d[0]&&1===d.length&&d.push(null),n.apply(b,d.concat([a,c]))}}function h(a){return function(b){return f(b,a)}}function i(a){return function(b){q[a]=b}}function j(a){if(e(r,a)){var c=r[a];delete r[a],t[a]=!0,m.apply(b,c)}if(!e(q,a)&&!e(t,a))throw new Error("No "+a);return q[a]}function k(a){var b,c=a?a.indexOf("!"):-1;return c>-1&&(b=a.substring(0,c),a=a.substring(c+1,a.length)),[b,a]}function l(a){return function(){return s&&s.config&&s.config[a]||{}}}var m,n,o,p,q={},r={},s={},t={},u=Object.prototype.hasOwnProperty,v=[].slice,w=/\.js$/;o=function(a,b){var c,d=k(a),e=d[0];return a=d[1],e&&(e=f(e,b),c=j(e)),e?a=c&&c.normalize?c.normalize(a,h(b)):f(a,b):(a=f(a,b),d=k(a),e=d[0],a=d[1],e&&(c=j(e))),{f:e?e+"!"+a:a,n:a,pr:e,p:c}},p={require:function(a){return g(a)},exports:function(a){var b=q[a];return"undefined"!=typeof b?b:q[a]={}},module:function(a){return{id:a,uri:"",exports:q[a],config:l(a)}}},m=function(a,c,d,f){var h,k,l,m,n,s,u=[],v=typeof d;if(f=f||a,"undefined"===v||"function"===v){for(c=!c.length&&d.length?["require","exports","module"]:c,n=0;n<c.length;n+=1)if(m=o(c[n],f),k=m.f,"require"===k)u[n]=p.require(a);else if("exports"===k)u[n]=p.exports(a),s=!0;else if("module"===k)h=u[n]=p.module(a);else if(e(q,k)||e(r,k)||e(t,k))u[n]=j(k);else{if(!m.p)throw new Error(a+" missing "+k);m.p.load(m.n,g(f,!0),i(k),{}),u[n]=q[k]}l=d?d.apply(q[a],u):void 0,a&&(h&&h.exports!==b&&h.exports!==q[a]?q[a]=h.exports:l===b&&s||(q[a]=l))}else a&&(q[a]=d)},a=c=n=function(a,c,d,e,f){if("string"==typeof a)return p[a]?p[a](c):j(o(a,c).f);if(!a.splice){if(s=a,s.deps&&n(s.deps,s.callback),!c)return;c.splice?(a=c,c=d,d=null):a=b}return c=c||function(){},"function"==typeof d&&(d=e,e=f),e?m(b,a,c,d):setTimeout(function(){m(b,a,c,d)},4),n},n.config=function(a){return n(a)},a._defined=q,d=function(a,b,c){if("string"!=typeof a)throw new Error("See almond README: incorrect module build, no module name");b.splice||(c=b,b=[]),e(q,a)||e(r,a)||(r[a]=[a,b,c])},d.amd={jQuery:!0}}(),b.requirejs=a,b.require=c,b.define=d}}(),b.define("almond",function(){}),b.define("jquery",[],function(){var b=a||$;return null==b&&console&&console.error&&console.error("Select2: An instance of jQuery or a jQuery-compatible library was not found. Make sure that you are including jQuery before Select2 on your web page."),b}),b.define("select2/utils",["jquery"],function(a){function b(a){var b=a.prototype,c=[];for(var d in b){var e=b[d];"function"==typeof e&&"constructor"!==d&&c.push(d)}return c}var c={};c.Extend=function(a,b){function c(){this.constructor=a}var d={}.hasOwnProperty;for(var e in b)d.call(b,e)&&(a[e]=b[e]);return c.prototype=b.prototype,a.prototype=new c,a.__super__=b.prototype,a},c.Decorate=function(a,c){function d(){var b=Array.prototype.unshift,d=c.prototype.constructor.length,e=a.prototype.constructor;d>0&&(b.call(arguments,a.prototype.constructor),e=c.prototype.constructor),e.apply(this,arguments)}function e(){this.constructor=d}var f=b(c),g=b(a);c.displayName=a.displayName,d.prototype=new e;for(var h=0;h<g.length;h++){var i=g[h];d.prototype[i]=a.prototype[i]}for(var j=(function(a){var b=function(){};a in d.prototype&&(b=d.prototype[a]);var e=c.prototype[a];return function(){var a=Array.prototype.unshift;return a.call(arguments,b),e.apply(this,arguments)}}),k=0;k<f.length;k++){var l=f[k];d.prototype[l]=j(l)}return d};var d=function(){this.listeners={}};return d.prototype.on=function(a,b){this.listeners=this.listeners||{},a in this.listeners?this.listeners[a].push(b):this.listeners[a]=[b]},d.prototype.trigger=function(a){var b=Array.prototype.slice;this.listeners=this.listeners||{},a in this.listeners&&this.invoke(this.listeners[a],b.call(arguments,1)),"*"in this.listeners&&this.invoke(this.listeners["*"],arguments)},d.prototype.invoke=function(a,b){for(var c=0,d=a.length;d>c;c++)a[c].apply(this,b)},c.Observable=d,c.generateChars=function(a){for(var b="",c=0;a>c;c++){var d=Math.floor(36*Math.random());b+=d.toString(36)}return b},c.bind=function(a,b){return function(){a.apply(b,arguments)}},c._convertData=function(a){for(var b in a){var c=b.split("-"),d=a;if(1!==c.length){for(var e=0;e<c.length;e++){var f=c[e];f=f.substring(0,1).toLowerCase()+f.substring(1),f in d||(d[f]={}),e==c.length-1&&(d[f]=a[b]),d=d[f]}delete a[b]}}return a},c.hasScroll=function(b,c){var d=a(c),e=c.style.overflowX,f=c.style.overflowY;return e!==f||"hidden"!==f&&"visible"!==f?"scroll"===e||"scroll"===f?!0:d.innerHeight()<c.scrollHeight||d.innerWidth()<c.scrollWidth:!1},c.escapeMarkup=function(a){var b={"\\":"&#92;","&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;","/":"&#47;"};return"string"!=typeof a?a:String(a).replace(/[&<>"'\/\\]/g,function(a){return b[a]})},c.appendMany=function(b,c){if("1.7"===a.fn.jquery.substr(0,3)){var d=a();a.map(c,function(a){d=d.add(a)}),c=d}b.append(c)},c}),b.define("select2/results",["jquery","./utils"],function(a,b){function c(a,b,d){this.$element=a,this.data=d,this.options=b,c.__super__.constructor.call(this)}return b.Extend(c,b.Observable),c.prototype.render=function(){var b=a('<ul class="select2-results__options" role="tree"></ul>');return this.options.get("multiple")&&b.attr("aria-multiselectable","true"),this.$results=b,b},c.prototype.clear=function(){this.$results.empty()},c.prototype.displayMessage=function(b){var c=this.options.get("escapeMarkup");this.clear(),this.hideLoading();var d=a('<li role="treeitem" aria-live="assertive" class="select2-results__option"></li>'),e=this.options.get("translations").get(b.message);d.append(c(e(b.args))),d[0].className+=" select2-results__message",this.$results.append(d)},c.prototype.hideMessages=function(){this.$results.find(".select2-results__message").remove()},c.prototype.append=function(a){this.hideLoading();var b=[];if(null==a.results||0===a.results.length)return void(0===this.$results.children().length&&this.trigger("results:message",{message:"noResults"}));a.results=this.sort(a.results);for(var c=0;c<a.results.length;c++){var d=a.results[c],e=this.option(d);b.push(e)}this.$results.append(b)},c.prototype.position=function(a,b){var c=b.find(".select2-results");c.append(a)},c.prototype.sort=function(a){var b=this.options.get("sorter");return b(a)},c.prototype.setClasses=function(){var b=this;this.data.current(function(c){var d=a.map(c,function(a){return a.id.toString()}),e=b.$results.find(".select2-results__option[aria-selected]");e.each(function(){var b=a(this),c=a.data(this,"data"),e=""+c.id;null!=c.element&&c.element.selected||null==c.element&&a.inArray(e,d)>-1?b.attr("aria-selected","true"):b.attr("aria-selected","false")});var f=e.filter("[aria-selected=true]");f.length>0?f.first().trigger("mouseenter"):e.first().trigger("mouseenter")})},c.prototype.showLoading=function(a){this.hideLoading();var b=this.options.get("translations").get("searching"),c={disabled:!0,loading:!0,text:b(a)},d=this.option(c);d.className+=" loading-results",this.$results.prepend(d)},c.prototype.hideLoading=function(){this.$results.find(".loading-results").remove()},c.prototype.option=function(b){var c=document.createElement("li");c.className="select2-results__option";var d={role:"treeitem","aria-selected":"false"};b.disabled&&(delete d["aria-selected"],d["aria-disabled"]="true"),null==b.id&&delete d["aria-selected"],null!=b._resultId&&(c.id=b._resultId),b.title&&(c.title=b.title),b.children&&(d.role="group",d["aria-label"]=b.text,delete d["aria-selected"]);for(var e in d){var f=d[e];c.setAttribute(e,f)}if(b.children){var g=a(c),h=document.createElement("strong");h.className="select2-results__group";a(h);this.template(b,h);for(var i=[],j=0;j<b.children.length;j++){var k=b.children[j],l=this.option(k);i.push(l)}var m=a("<ul></ul>",{"class":"select2-results__options select2-results__options--nested"});m.append(i),g.append(h),g.append(m)}else this.template(b,c);return a.data(c,"data",b),c},c.prototype.bind=function(b,c){var d=this,e=b.id+"-results";this.$results.attr("id",e),b.on("results:all",function(a){d.clear(),d.append(a.data),b.isOpen()&&d.setClasses()}),b.on("results:append",function(a){d.append(a.data),b.isOpen()&&d.setClasses()}),b.on("query",function(a){d.hideMessages(),d.showLoading(a)}),b.on("select",function(){b.isOpen()&&d.setClasses()}),b.on("unselect",function(){b.isOpen()&&d.setClasses()}),b.on("open",function(){d.$results.attr("aria-expanded","true"),d.$results.attr("aria-hidden","false"),d.setClasses(),d.ensureHighlightVisible()}),b.on("close",function(){d.$results.attr("aria-expanded","false"),d.$results.attr("aria-hidden","true"),d.$results.removeAttr("aria-activedescendant")}),b.on("results:toggle",function(){var a=d.getHighlightedResults();0!==a.length&&a.trigger("mouseup")}),b.on("results:select",function(){var a=d.getHighlightedResults();if(0!==a.length){var b=a.data("data");"true"==a.attr("aria-selected")?d.trigger("close",{}):d.trigger("select",{data:b})}}),b.on("results:previous",function(){var a=d.getHighlightedResults(),b=d.$results.find("[aria-selected]"),c=b.index(a);if(0!==c){var e=c-1;0===a.length&&(e=0);var f=b.eq(e);f.trigger("mouseenter");var g=d.$results.offset().top,h=f.offset().top,i=d.$results.scrollTop()+(h-g);0===e?d.$results.scrollTop(0):0>h-g&&d.$results.scrollTop(i)}}),b.on("results:next",function(){var a=d.getHighlightedResults(),b=d.$results.find("[aria-selected]"),c=b.index(a),e=c+1;if(!(e>=b.length)){var f=b.eq(e);f.trigger("mouseenter");var g=d.$results.offset().top+d.$results.outerHeight(!1),h=f.offset().top+f.outerHeight(!1),i=d.$results.scrollTop()+h-g;0===e?d.$results.scrollTop(0):h>g&&d.$results.scrollTop(i)}}),b.on("results:focus",function(a){a.element.addClass("select2-results__option--highlighted")}),b.on("results:message",function(a){d.displayMessage(a)}),a.fn.mousewheel&&this.$results.on("mousewheel",function(a){var b=d.$results.scrollTop(),c=d.$results.get(0).scrollHeight-d.$results.scrollTop()+a.deltaY,e=a.deltaY>0&&b-a.deltaY<=0,f=a.deltaY<0&&c<=d.$results.height();e?(d.$results.scrollTop(0),a.preventDefault(),a.stopPropagation()):f&&(d.$results.scrollTop(d.$results.get(0).scrollHeight-d.$results.height()),a.preventDefault(),a.stopPropagation())}),this.$results.on("mouseup",".select2-results__option[aria-selected]",function(b){var c=a(this),e=c.data("data");return"true"===c.attr("aria-selected")?void(d.options.get("multiple")?d.trigger("unselect",{originalEvent:b,data:e}):d.trigger("close",{})):void d.trigger("select",{originalEvent:b,data:e})}),this.$results.on("mouseenter",".select2-results__option[aria-selected]",function(b){var c=a(this).data("data");d.getHighlightedResults().removeClass("select2-results__option--highlighted"),d.trigger("results:focus",{data:c,element:a(this)})})},c.prototype.getHighlightedResults=function(){var a=this.$results.find(".select2-results__option--highlighted");return a},c.prototype.destroy=function(){this.$results.remove()},c.prototype.ensureHighlightVisible=function(){var a=this.getHighlightedResults();if(0!==a.length){var b=this.$results.find("[aria-selected]"),c=b.index(a),d=this.$results.offset().top,e=a.offset().top,f=this.$results.scrollTop()+(e-d),g=e-d;f-=2*a.outerHeight(!1),2>=c?this.$results.scrollTop(0):(g>this.$results.outerHeight()||0>g)&&this.$results.scrollTop(f)}},c.prototype.template=function(b,c){var d=this.options.get("templateResult"),e=this.options.get("escapeMarkup"),f=d(b,c);null==f?c.style.display="none":"string"==typeof f?c.innerHTML=e(f):a(c).append(f)},c}),b.define("select2/keys",[],function(){var a={BACKSPACE:8,TAB:9,ENTER:13,SHIFT:16,CTRL:17,ALT:18,ESC:27,SPACE:32,PAGE_UP:33,PAGE_DOWN:34,END:35,HOME:36,LEFT:37,UP:38,RIGHT:39,DOWN:40,DELETE:46};return a}),b.define("select2/selection/base",["jquery","../utils","../keys"],function(a,b,c){function d(a,b){this.$element=a,this.options=b,d.__super__.constructor.call(this)}return b.Extend(d,b.Observable),d.prototype.render=function(){var b=a('<span class="select2-selection" role="combobox"  aria-haspopup="true" aria-expanded="false"></span>');return this._tabindex=0,null!=this.$element.data("old-tabindex")?this._tabindex=this.$element.data("old-tabindex"):null!=this.$element.attr("tabindex")&&(this._tabindex=this.$element.attr("tabindex")),b.attr("title",this.$element.attr("title")),b.attr("tabindex",this._tabindex),this.$selection=b,b},d.prototype.bind=function(a,b){var d=this,e=(a.id+"-container",a.id+"-results");this.container=a,this.$selection.on("focus",function(a){d.trigger("focus",a)}),this.$selection.on("blur",function(a){d._handleBlur(a)}),this.$selection.on("keydown",function(a){d.trigger("keypress",a),a.which===c.SPACE&&a.preventDefault()}),a.on("results:focus",function(a){d.$selection.attr("aria-activedescendant",a.data._resultId)}),a.on("selection:update",function(a){d.update(a.data)}),a.on("open",function(){d.$selection.attr("aria-expanded","true"),d.$selection.attr("aria-owns",e),d._attachCloseHandler(a)}),a.on("close",function(){d.$selection.attr("aria-expanded","false"),d.$selection.removeAttr("aria-activedescendant"),d.$selection.removeAttr("aria-owns"),d.$selection.focus(),d._detachCloseHandler(a)}),a.on("enable",function(){d.$selection.attr("tabindex",d._tabindex)}),a.on("disable",function(){d.$selection.attr("tabindex","-1")})},d.prototype._handleBlur=function(b){var c=this;window.setTimeout(function(){document.activeElement==c.$selection[0]||a.contains(c.$selection[0],document.activeElement)||c.trigger("blur",b)},1)},d.prototype._attachCloseHandler=function(b){a(document.body).on("mousedown.select2."+b.id,function(b){var c=a(b.target),d=c.closest(".select2"),e=a(".select2.select2-container--open");e.each(function(){var b=a(this);if(this!=d[0]){var c=b.data("element");c.select2("close")}})})},d.prototype._detachCloseHandler=function(b){a(document.body).off("mousedown.select2."+b.id)},d.prototype.position=function(a,b){var c=b.find(".selection");c.append(a)},d.prototype.destroy=function(){this._detachCloseHandler(this.container)},d.prototype.update=function(a){throw new Error("The `update` method must be defined in child classes.")},d}),b.define("select2/selection/single",["jquery","./base","../utils","../keys"],function(a,b,c,d){function e(){e.__super__.constructor.apply(this,arguments)}return c.Extend(e,b),e.prototype.render=function(){var a=e.__super__.render.call(this);return a.addClass("select2-selection--single"),a.html('<span class="select2-selection__rendered"></span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span>'),a},e.prototype.bind=function(a,b){var c=this;e.__super__.bind.apply(this,arguments);var d=a.id+"-container";this.$selection.find(".select2-selection__rendered").attr("id",d),this.$selection.attr("aria-labelledby",d),this.$selection.on("mousedown",function(a){1===a.which&&c.trigger("toggle",{originalEvent:a})}),this.$selection.on("focus",function(a){}),this.$selection.on("blur",function(a){}),a.on("selection:update",function(a){c.update(a.data)})},e.prototype.clear=function(){this.$selection.find(".select2-selection__rendered").empty()},e.prototype.display=function(a,b){var c=this.options.get("templateSelection"),d=this.options.get("escapeMarkup");return d(c(a,b))},e.prototype.selectionContainer=function(){return a("<span></span>")},e.prototype.update=function(a){if(0===a.length)return void this.clear();var b=a[0],c=this.$selection.find(".select2-selection__rendered"),d=this.display(b,c);c.empty().append(d),c.prop("title",b.title||b.text)},e}),b.define("select2/selection/multiple",["jquery","./base","../utils"],function(a,b,c){function d(a,b){d.__super__.constructor.apply(this,arguments)}return c.Extend(d,b),d.prototype.render=function(){var a=d.__super__.render.call(this);return a.addClass("select2-selection--multiple"),a.html('<ul class="select2-selection__rendered"></ul>'),a},d.prototype.bind=function(b,c){var e=this;d.__super__.bind.apply(this,arguments),this.$selection.on("click",function(a){e.trigger("toggle",{originalEvent:a})}),this.$selection.on("click",".select2-selection__choice__remove",function(b){if(!e.options.get("disabled")){var c=a(this),d=c.parent(),f=d.data("data");e.trigger("unselect",{originalEvent:b,data:f})}})},d.prototype.clear=function(){this.$selection.find(".select2-selection__rendered").empty()},d.prototype.display=function(a,b){var c=this.options.get("templateSelection"),d=this.options.get("escapeMarkup");return d(c(a,b))},d.prototype.selectionContainer=function(){var b=a('<li class="select2-selection__choice"><span class="select2-selection__choice__remove" role="presentation">&times;</span></li>');return b},d.prototype.update=function(a){if(this.clear(),0!==a.length){for(var b=[],d=0;d<a.length;d++){var e=a[d],f=this.selectionContainer(),g=this.display(e,f);f.append(g),f.prop("title",e.title||e.text),f.data("data",e),b.push(f)}var h=this.$selection.find(".select2-selection__rendered");c.appendMany(h,b)}},d}),b.define("select2/selection/placeholder",["../utils"],function(a){function b(a,b,c){this.placeholder=this.normalizePlaceholder(c.get("placeholder")),a.call(this,b,c)}return b.prototype.normalizePlaceholder=function(a,b){return"string"==typeof b&&(b={id:"",text:b}),b},b.prototype.createPlaceholder=function(a,b){var c=this.selectionContainer();return c.html(this.display(b)),c.addClass("select2-selection__placeholder").removeClass("select2-selection__choice"),c},b.prototype.update=function(a,b){var c=1==b.length&&b[0].id!=this.placeholder.id,d=b.length>1;if(d||c)return a.call(this,b);this.clear();var e=this.createPlaceholder(this.placeholder);this.$selection.find(".select2-selection__rendered").append(e)},b}),b.define("select2/selection/allowClear",["jquery","../keys"],function(a,b){function c(){}return c.prototype.bind=function(a,b,c){var d=this;a.call(this,b,c),null==this.placeholder&&this.options.get("debug")&&window.console&&console.error&&console.error("Select2: The `allowClear` option should be used in combination with the `placeholder` option."),this.$selection.on("mousedown",".select2-selection__clear",function(a){d._handleClear(a)}),b.on("keypress",function(a){d._handleKeyboardClear(a,b)})},c.prototype._handleClear=function(a,b){if(!this.options.get("disabled")){var c=this.$selection.find(".select2-selection__clear");if(0!==c.length){b.stopPropagation();for(var d=c.data("data"),e=0;e<d.length;e++){var f={data:d[e]};if(this.trigger("unselect",f),f.prevented)return}this.$element.val(this.placeholder.id).trigger("change"),this.trigger("toggle",{})}}},c.prototype._handleKeyboardClear=function(a,c,d){d.isOpen()||(c.which==b.DELETE||c.which==b.BACKSPACE)&&this._handleClear(c)},c.prototype.update=function(b,c){if(b.call(this,c),!(this.$selection.find(".select2-selection__placeholder").length>0||0===c.length)){var d=a('<span class="select2-selection__clear">&times;</span>');d.data("data",c),this.$selection.find(".select2-selection__rendered").prepend(d)}},c}),b.define("select2/selection/search",["jquery","../utils","../keys"],function(a,b,c){function d(a,b,c){a.call(this,b,c)}return d.prototype.render=function(b){var c=a('<li class="select2-search select2-search--inline"><input class="select2-search__field" type="search" tabindex="-1" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" role="textbox" aria-autocomplete="list" /></li>');this.$searchContainer=c,this.$search=c.find("input");var d=b.call(this);return this._transferTabIndex(),d},d.prototype.bind=function(a,b,d){var e=this;a.call(this,b,d),b.on("open",function(){e.$search.trigger("focus")}),b.on("close",function(){e.$search.val(""),e.$search.removeAttr("aria-activedescendant"),e.$search.trigger("focus")}),b.on("enable",function(){e.$search.prop("disabled",!1),e._transferTabIndex()}),b.on("disable",function(){e.$search.prop("disabled",!0)}),b.on("focus",function(a){e.$search.trigger("focus")}),b.on("results:focus",function(a){e.$search.attr("aria-activedescendant",a.id)}),this.$selection.on("focusin",".select2-search--inline",function(a){e.trigger("focus",a)}),this.$selection.on("focusout",".select2-search--inline",function(a){e._handleBlur(a)}),this.$selection.on("keydown",".select2-search--inline",function(a){a.stopPropagation(),e.trigger("keypress",a),e._keyUpPrevented=a.isDefaultPrevented();var b=a.which;if(b===c.BACKSPACE&&""===e.$search.val()){var d=e.$searchContainer.prev(".select2-selection__choice");if(d.length>0){var f=d.data("data");e.searchRemoveChoice(f),a.preventDefault()}}});var f=document.documentMode,g=f&&11>=f;this.$selection.on("input.searchcheck",".select2-search--inline",function(a){return g?void e.$selection.off("input.search input.searchcheck"):void e.$selection.off("keyup.search")}),this.$selection.on("keyup.search input.search",".select2-search--inline",function(a){if(g&&"input"===a.type)return void e.$selection.off("input.search input.searchcheck");var b=a.which;b!=c.SHIFT&&b!=c.CTRL&&b!=c.ALT&&b!=c.TAB&&e.handleSearch(a)})},d.prototype._transferTabIndex=function(a){this.$search.attr("tabindex",this.$selection.attr("tabindex")),this.$selection.attr("tabindex","-1")},d.prototype.createPlaceholder=function(a,b){this.$search.attr("placeholder",b.text)},d.prototype.update=function(a,b){var c=this.$search[0]==document.activeElement;this.$search.attr("placeholder",""),a.call(this,b),this.$selection.find(".select2-selection__rendered").append(this.$searchContainer),this.resizeSearch(),c&&this.$search.focus()},d.prototype.handleSearch=function(){if(this.resizeSearch(),!this._keyUpPrevented){var a=this.$search.val();this.trigger("query",{term:a})}this._keyUpPrevented=!1},d.prototype.searchRemoveChoice=function(a,b){this.trigger("unselect",{data:b}),this.$search.val(b.text),this.handleSearch()},d.prototype.resizeSearch=function(){this.$search.css("width","25px");var a="";if(""!==this.$search.attr("placeholder"))a=this.$selection.find(".select2-selection__rendered").innerWidth();else{var b=this.$search.val().length+1;a=.75*b+"em"}this.$search.css("width",a)},d}),b.define("select2/selection/eventRelay",["jquery"],function(a){function b(){}return b.prototype.bind=function(b,c,d){var e=this,f=["open","opening","close","closing","select","selecting","unselect","unselecting"],g=["opening","closing","selecting","unselecting"];b.call(this,c,d),c.on("*",function(b,c){if(-1!==a.inArray(b,f)){c=c||{};var d=a.Event("select2:"+b,{params:c});e.$element.trigger(d),-1!==a.inArray(b,g)&&(c.prevented=d.isDefaultPrevented())}})},b}),b.define("select2/translation",["jquery","require"],function(a,b){function c(a){this.dict=a||{}}return c.prototype.all=function(){return this.dict},c.prototype.get=function(a){return this.dict[a]},c.prototype.extend=function(b){this.dict=a.extend({},b.all(),this.dict)},c._cache={},c.loadPath=function(a){if(!(a in c._cache)){var d=b(a);c._cache[a]=d}return new c(c._cache[a])},c}),b.define("select2/diacritics",[],function(){var a={"Ⓐ":"A","Ａ":"A","À":"A","Á":"A","Â":"A","Ầ":"A","Ấ":"A","Ẫ":"A","Ẩ":"A","Ã":"A","Ā":"A","Ă":"A","Ằ":"A","Ắ":"A","Ẵ":"A","Ẳ":"A","Ȧ":"A","Ǡ":"A","Ä":"A","Ǟ":"A","Ả":"A","Å":"A","Ǻ":"A","Ǎ":"A","Ȁ":"A","Ȃ":"A","Ạ":"A","Ậ":"A","Ặ":"A","Ḁ":"A","Ą":"A","Ⱥ":"A","Ɐ":"A","Ꜳ":"AA","Æ":"AE","Ǽ":"AE","Ǣ":"AE","Ꜵ":"AO","Ꜷ":"AU","Ꜹ":"AV","Ꜻ":"AV","Ꜽ":"AY","Ⓑ":"B","Ｂ":"B","Ḃ":"B","Ḅ":"B","Ḇ":"B","Ƀ":"B","Ƃ":"B","Ɓ":"B","Ⓒ":"C","Ｃ":"C","Ć":"C","Ĉ":"C","Ċ":"C","Č":"C","Ç":"C","Ḉ":"C","Ƈ":"C","Ȼ":"C","Ꜿ":"C","Ⓓ":"D","Ｄ":"D","Ḋ":"D","Ď":"D","Ḍ":"D","Ḑ":"D","Ḓ":"D","Ḏ":"D","Đ":"D","Ƌ":"D","Ɗ":"D","Ɖ":"D","Ꝺ":"D","Ǳ":"DZ","Ǆ":"DZ","ǲ":"Dz","ǅ":"Dz","Ⓔ":"E","Ｅ":"E","È":"E","É":"E","Ê":"E","Ề":"E","Ế":"E","Ễ":"E","Ể":"E","Ẽ":"E","Ē":"E","Ḕ":"E","Ḗ":"E","Ĕ":"E","Ė":"E","Ë":"E","Ẻ":"E","Ě":"E","Ȅ":"E","Ȇ":"E","Ẹ":"E","Ệ":"E","Ȩ":"E","Ḝ":"E","Ę":"E","Ḙ":"E","Ḛ":"E","Ɛ":"E","Ǝ":"E","Ⓕ":"F","Ｆ":"F","Ḟ":"F","Ƒ":"F","Ꝼ":"F","Ⓖ":"G","Ｇ":"G","Ǵ":"G","Ĝ":"G","Ḡ":"G","Ğ":"G","Ġ":"G","Ǧ":"G","Ģ":"G","Ǥ":"G","Ɠ":"G","Ꞡ":"G","Ᵹ":"G","Ꝿ":"G","Ⓗ":"H","Ｈ":"H","Ĥ":"H","Ḣ":"H","Ḧ":"H","Ȟ":"H","Ḥ":"H","Ḩ":"H","Ḫ":"H","Ħ":"H","Ⱨ":"H","Ⱶ":"H","Ɥ":"H","Ⓘ":"I","Ｉ":"I","Ì":"I","Í":"I","Î":"I","Ĩ":"I","Ī":"I","Ĭ":"I","İ":"I","Ï":"I","Ḯ":"I","Ỉ":"I","Ǐ":"I","Ȉ":"I","Ȋ":"I","Ị":"I","Į":"I","Ḭ":"I","Ɨ":"I","Ⓙ":"J","Ｊ":"J","Ĵ":"J","Ɉ":"J","Ⓚ":"K","Ｋ":"K","Ḱ":"K","Ǩ":"K","Ḳ":"K","Ķ":"K","Ḵ":"K","Ƙ":"K","Ⱪ":"K","Ꝁ":"K","Ꝃ":"K","Ꝅ":"K","Ꞣ":"K","Ⓛ":"L","Ｌ":"L","Ŀ":"L","Ĺ":"L","Ľ":"L","Ḷ":"L","Ḹ":"L","Ļ":"L","Ḽ":"L","Ḻ":"L","Ł":"L","Ƚ":"L","Ɫ":"L","Ⱡ":"L","Ꝉ":"L","Ꝇ":"L","Ꞁ":"L","Ǉ":"LJ","ǈ":"Lj","Ⓜ":"M","Ｍ":"M","Ḿ":"M","Ṁ":"M","Ṃ":"M","Ɱ":"M","Ɯ":"M","Ⓝ":"N","Ｎ":"N","Ǹ":"N","Ń":"N","Ñ":"N","Ṅ":"N","Ň":"N","Ṇ":"N","Ņ":"N","Ṋ":"N","Ṉ":"N","Ƞ":"N","Ɲ":"N","Ꞑ":"N","Ꞥ":"N","Ǌ":"NJ","ǋ":"Nj","Ⓞ":"O","Ｏ":"O","Ò":"O","Ó":"O","Ô":"O","Ồ":"O","Ố":"O","Ỗ":"O","Ổ":"O","Õ":"O","Ṍ":"O","Ȭ":"O","Ṏ":"O","Ō":"O","Ṑ":"O","Ṓ":"O","Ŏ":"O","Ȯ":"O","Ȱ":"O","Ö":"O","Ȫ":"O","Ỏ":"O","Ő":"O","Ǒ":"O","Ȍ":"O","Ȏ":"O","Ơ":"O","Ờ":"O","Ớ":"O","Ỡ":"O","Ở":"O","Ợ":"O","Ọ":"O","Ộ":"O","Ǫ":"O","Ǭ":"O","Ø":"O","Ǿ":"O","Ɔ":"O","Ɵ":"O","Ꝋ":"O","Ꝍ":"O","Ƣ":"OI","Ꝏ":"OO","Ȣ":"OU","Ⓟ":"P","Ｐ":"P","Ṕ":"P","Ṗ":"P","Ƥ":"P","Ᵽ":"P","Ꝑ":"P","Ꝓ":"P","Ꝕ":"P","Ⓠ":"Q","Ｑ":"Q","Ꝗ":"Q","Ꝙ":"Q","Ɋ":"Q","Ⓡ":"R","Ｒ":"R","Ŕ":"R","Ṙ":"R","Ř":"R","Ȑ":"R","Ȓ":"R","Ṛ":"R","Ṝ":"R","Ŗ":"R","Ṟ":"R","Ɍ":"R","Ɽ":"R","Ꝛ":"R","Ꞧ":"R","Ꞃ":"R","Ⓢ":"S","Ｓ":"S","ẞ":"S","Ś":"S","Ṥ":"S","Ŝ":"S","Ṡ":"S","Š":"S","Ṧ":"S","Ṣ":"S","Ṩ":"S","Ș":"S","Ş":"S","Ȿ":"S","Ꞩ":"S","Ꞅ":"S","Ⓣ":"T","Ｔ":"T","Ṫ":"T","Ť":"T","Ṭ":"T","Ț":"T","Ţ":"T","Ṱ":"T","Ṯ":"T","Ŧ":"T","Ƭ":"T","Ʈ":"T","Ⱦ":"T","Ꞇ":"T","Ꜩ":"TZ","Ⓤ":"U","Ｕ":"U","Ù":"U","Ú":"U","Û":"U","Ũ":"U","Ṹ":"U","Ū":"U","Ṻ":"U","Ŭ":"U","Ü":"U","Ǜ":"U","Ǘ":"U","Ǖ":"U","Ǚ":"U","Ủ":"U","Ů":"U","Ű":"U","Ǔ":"U","Ȕ":"U","Ȗ":"U","Ư":"U","Ừ":"U","Ứ":"U","Ữ":"U","Ử":"U","Ự":"U","Ụ":"U","Ṳ":"U","Ų":"U","Ṷ":"U","Ṵ":"U","Ʉ":"U","Ⓥ":"V","Ｖ":"V","Ṽ":"V","Ṿ":"V","Ʋ":"V","Ꝟ":"V","Ʌ":"V","Ꝡ":"VY","Ⓦ":"W","Ｗ":"W","Ẁ":"W","Ẃ":"W","Ŵ":"W","Ẇ":"W","Ẅ":"W","Ẉ":"W","Ⱳ":"W","Ⓧ":"X","Ｘ":"X","Ẋ":"X","Ẍ":"X","Ⓨ":"Y","Ｙ":"Y","Ỳ":"Y","Ý":"Y","Ŷ":"Y","Ỹ":"Y","Ȳ":"Y","Ẏ":"Y","Ÿ":"Y","Ỷ":"Y","Ỵ":"Y","Ƴ":"Y","Ɏ":"Y","Ỿ":"Y","Ⓩ":"Z","Ｚ":"Z","Ź":"Z","Ẑ":"Z","Ż":"Z","Ž":"Z","Ẓ":"Z","Ẕ":"Z","Ƶ":"Z","Ȥ":"Z","Ɀ":"Z","Ⱬ":"Z","Ꝣ":"Z","ⓐ":"a","ａ":"a","ẚ":"a","à":"a","á":"a","â":"a","ầ":"a","ấ":"a","ẫ":"a","ẩ":"a","ã":"a","ā":"a","ă":"a","ằ":"a","ắ":"a","ẵ":"a","ẳ":"a","ȧ":"a","ǡ":"a","ä":"a","ǟ":"a","ả":"a","å":"a","ǻ":"a","ǎ":"a","ȁ":"a","ȃ":"a","ạ":"a","ậ":"a","ặ":"a","ḁ":"a","ą":"a","ⱥ":"a","ɐ":"a","ꜳ":"aa","æ":"ae","ǽ":"ae","ǣ":"ae","ꜵ":"ao","ꜷ":"au","ꜹ":"av","ꜻ":"av","ꜽ":"ay","ⓑ":"b","ｂ":"b","ḃ":"b","ḅ":"b","ḇ":"b","ƀ":"b","ƃ":"b","ɓ":"b","ⓒ":"c","ｃ":"c","ć":"c","ĉ":"c","ċ":"c","č":"c","ç":"c","ḉ":"c","ƈ":"c","ȼ":"c","ꜿ":"c","ↄ":"c","ⓓ":"d","ｄ":"d","ḋ":"d","ď":"d","ḍ":"d","ḑ":"d","ḓ":"d","ḏ":"d","đ":"d","ƌ":"d","ɖ":"d","ɗ":"d","ꝺ":"d","ǳ":"dz","ǆ":"dz","ⓔ":"e","ｅ":"e","è":"e","é":"e","ê":"e","ề":"e","ế":"e","ễ":"e","ể":"e","ẽ":"e","ē":"e","ḕ":"e","ḗ":"e","ĕ":"e","ė":"e","ë":"e","ẻ":"e","ě":"e","ȅ":"e","ȇ":"e","ẹ":"e","ệ":"e","ȩ":"e","ḝ":"e","ę":"e","ḙ":"e","ḛ":"e","ɇ":"e","ɛ":"e","ǝ":"e","ⓕ":"f","ｆ":"f","ḟ":"f","ƒ":"f","ꝼ":"f","ⓖ":"g","ｇ":"g","ǵ":"g","ĝ":"g","ḡ":"g","ğ":"g","ġ":"g","ǧ":"g","ģ":"g","ǥ":"g","ɠ":"g","ꞡ":"g","ᵹ":"g","ꝿ":"g","ⓗ":"h","ｈ":"h","ĥ":"h","ḣ":"h","ḧ":"h","ȟ":"h","ḥ":"h","ḩ":"h","ḫ":"h","ẖ":"h","ħ":"h","ⱨ":"h","ⱶ":"h","ɥ":"h","ƕ":"hv","ⓘ":"i","ｉ":"i","ì":"i","í":"i","î":"i","ĩ":"i","ī":"i","ĭ":"i","ï":"i","ḯ":"i","ỉ":"i","ǐ":"i","ȉ":"i","ȋ":"i","ị":"i","į":"i","ḭ":"i","ɨ":"i","ı":"i","ⓙ":"j","ｊ":"j","ĵ":"j","ǰ":"j","ɉ":"j","ⓚ":"k","ｋ":"k","ḱ":"k","ǩ":"k","ḳ":"k","ķ":"k","ḵ":"k","ƙ":"k","ⱪ":"k","ꝁ":"k","ꝃ":"k","ꝅ":"k","ꞣ":"k","ⓛ":"l","ｌ":"l","ŀ":"l","ĺ":"l","ľ":"l","ḷ":"l","ḹ":"l","ļ":"l","ḽ":"l","ḻ":"l","ſ":"l","ł":"l","ƚ":"l","ɫ":"l","ⱡ":"l","ꝉ":"l","ꞁ":"l","ꝇ":"l","ǉ":"lj","ⓜ":"m","ｍ":"m","ḿ":"m","ṁ":"m","ṃ":"m","ɱ":"m","ɯ":"m","ⓝ":"n","ｎ":"n","ǹ":"n","ń":"n","ñ":"n","ṅ":"n","ň":"n","ṇ":"n","ņ":"n","ṋ":"n","ṉ":"n","ƞ":"n","ɲ":"n","ŉ":"n","ꞑ":"n","ꞥ":"n","ǌ":"nj","ⓞ":"o","ｏ":"o","ò":"o","ó":"o","ô":"o","ồ":"o","ố":"o","ỗ":"o","ổ":"o","õ":"o","ṍ":"o","ȭ":"o","ṏ":"o","ō":"o","ṑ":"o","ṓ":"o","ŏ":"o","ȯ":"o","ȱ":"o","ö":"o","ȫ":"o","ỏ":"o","ő":"o","ǒ":"o","ȍ":"o","ȏ":"o","ơ":"o","ờ":"o","ớ":"o","ỡ":"o","ở":"o","ợ":"o","ọ":"o","ộ":"o","ǫ":"o","ǭ":"o","ø":"o","ǿ":"o","ɔ":"o","ꝋ":"o","ꝍ":"o","ɵ":"o","ƣ":"oi","ȣ":"ou","ꝏ":"oo","ⓟ":"p","ｐ":"p","ṕ":"p","ṗ":"p","ƥ":"p","ᵽ":"p","ꝑ":"p","ꝓ":"p","ꝕ":"p","ⓠ":"q","ｑ":"q","ɋ":"q","ꝗ":"q","ꝙ":"q","ⓡ":"r","ｒ":"r","ŕ":"r","ṙ":"r","ř":"r","ȑ":"r","ȓ":"r","ṛ":"r","ṝ":"r","ŗ":"r","ṟ":"r","ɍ":"r","ɽ":"r","ꝛ":"r","ꞧ":"r","ꞃ":"r","ⓢ":"s","ｓ":"s","ß":"s","ś":"s","ṥ":"s","ŝ":"s","ṡ":"s","š":"s","ṧ":"s","ṣ":"s","ṩ":"s","ș":"s","ş":"s","ȿ":"s","ꞩ":"s","ꞅ":"s","ẛ":"s","ⓣ":"t","ｔ":"t","ṫ":"t","ẗ":"t","ť":"t","ṭ":"t","ț":"t","ţ":"t","ṱ":"t","ṯ":"t","ŧ":"t","ƭ":"t","ʈ":"t","ⱦ":"t","ꞇ":"t","ꜩ":"tz","ⓤ":"u","ｕ":"u","ù":"u","ú":"u","û":"u","ũ":"u","ṹ":"u","ū":"u","ṻ":"u","ŭ":"u","ü":"u","ǜ":"u","ǘ":"u","ǖ":"u","ǚ":"u","ủ":"u","ů":"u","ű":"u","ǔ":"u","ȕ":"u","ȗ":"u","ư":"u","ừ":"u","ứ":"u","ữ":"u","ử":"u","ự":"u","ụ":"u","ṳ":"u","ų":"u","ṷ":"u","ṵ":"u","ʉ":"u","ⓥ":"v","ｖ":"v","ṽ":"v","ṿ":"v","ʋ":"v","ꝟ":"v","ʌ":"v","ꝡ":"vy","ⓦ":"w","ｗ":"w","ẁ":"w","ẃ":"w","ŵ":"w","ẇ":"w","ẅ":"w","ẘ":"w","ẉ":"w","ⱳ":"w","ⓧ":"x","ｘ":"x","ẋ":"x","ẍ":"x","ⓨ":"y","ｙ":"y","ỳ":"y","ý":"y","ŷ":"y","ỹ":"y","ȳ":"y","ẏ":"y","ÿ":"y","ỷ":"y","ẙ":"y","ỵ":"y","ƴ":"y","ɏ":"y","ỿ":"y","ⓩ":"z","ｚ":"z","ź":"z","ẑ":"z","ż":"z","ž":"z","ẓ":"z","ẕ":"z","ƶ":"z","ȥ":"z","ɀ":"z","ⱬ":"z","ꝣ":"z","Ά":"Α","Έ":"Ε","Ή":"Η","Ί":"Ι","Ϊ":"Ι","Ό":"Ο","Ύ":"Υ","Ϋ":"Υ","Ώ":"Ω","ά":"α","έ":"ε","ή":"η","ί":"ι","ϊ":"ι","ΐ":"ι","ό":"ο","ύ":"υ","ϋ":"υ","ΰ":"υ","ω":"ω","ς":"σ"};return a}),b.define("select2/data/base",["../utils"],function(a){function b(a,c){b.__super__.constructor.call(this)}return a.Extend(b,a.Observable),b.prototype.current=function(a){throw new Error("The `current` method must be defined in child classes.")},b.prototype.query=function(a,b){throw new Error("The `query` method must be defined in child classes.")},b.prototype.bind=function(a,b){},b.prototype.destroy=function(){},b.prototype.generateResultId=function(b,c){var d=b.id+"-result-";return d+=a.generateChars(4),d+=null!=c.id?"-"+c.id.toString():"-"+a.generateChars(4)},b}),b.define("select2/data/select",["./base","../utils","jquery"],function(a,b,c){function d(a,b){this.$element=a,this.options=b,d.__super__.constructor.call(this)}return b.Extend(d,a),d.prototype.current=function(a){var b=[],d=this;this.$element.find(":selected").each(function(){var a=c(this),e=d.item(a);b.push(e)}),a(b)},d.prototype.select=function(a){var b=this;if(a.selected=!0,c(a.element).is("option"))return a.element.selected=!0,void this.$element.trigger("change");if(this.$element.prop("multiple"))this.current(function(d){var e=[];a=[a],a.push.apply(a,d);for(var f=0;f<a.length;f++){var g=a[f].id;-1===c.inArray(g,e)&&e.push(g)}b.$element.val(e),b.$element.trigger("change")});else{var d=a.id;this.$element.val(d),this.$element.trigger("change")}},d.prototype.unselect=function(a){
var b=this;if(this.$element.prop("multiple"))return a.selected=!1,c(a.element).is("option")?(a.element.selected=!1,void this.$element.trigger("change")):void this.current(function(d){for(var e=[],f=0;f<d.length;f++){var g=d[f].id;g!==a.id&&-1===c.inArray(g,e)&&e.push(g)}b.$element.val(e),b.$element.trigger("change")})},d.prototype.bind=function(a,b){var c=this;this.container=a,a.on("select",function(a){c.select(a.data)}),a.on("unselect",function(a){c.unselect(a.data)})},d.prototype.destroy=function(){this.$element.find("*").each(function(){c.removeData(this,"data")})},d.prototype.query=function(a,b){var d=[],e=this,f=this.$element.children();f.each(function(){var b=c(this);if(b.is("option")||b.is("optgroup")){var f=e.item(b),g=e.matches(a,f);null!==g&&d.push(g)}}),b({results:d})},d.prototype.addOptions=function(a){b.appendMany(this.$element,a)},d.prototype.option=function(a){var b;a.children?(b=document.createElement("optgroup"),b.label=a.text):(b=document.createElement("option"),void 0!==b.textContent?b.textContent=a.text:b.innerText=a.text),a.id&&(b.value=a.id),a.disabled&&(b.disabled=!0),a.selected&&(b.selected=!0),a.title&&(b.title=a.title);var d=c(b),e=this._normalizeItem(a);return e.element=b,c.data(b,"data",e),d},d.prototype.item=function(a){var b={};if(b=c.data(a[0],"data"),null!=b)return b;if(a.is("option"))b={id:a.val(),text:a.text(),disabled:a.prop("disabled"),selected:a.prop("selected"),title:a.prop("title")};else if(a.is("optgroup")){b={text:a.prop("label"),children:[],title:a.prop("title")};for(var d=a.children("option"),e=[],f=0;f<d.length;f++){var g=c(d[f]),h=this.item(g);e.push(h)}b.children=e}return b=this._normalizeItem(b),b.element=a[0],c.data(a[0],"data",b),b},d.prototype._normalizeItem=function(a){c.isPlainObject(a)||(a={id:a,text:a}),a=c.extend({},{text:""},a);var b={selected:!1,disabled:!1};return null!=a.id&&(a.id=a.id.toString()),null!=a.text&&(a.text=a.text.toString()),null==a._resultId&&a.id&&null!=this.container&&(a._resultId=this.generateResultId(this.container,a)),c.extend({},b,a)},d.prototype.matches=function(a,b){var c=this.options.get("matcher");return c(a,b)},d}),b.define("select2/data/array",["./select","../utils","jquery"],function(a,b,c){function d(a,b){var c=b.get("data")||[];d.__super__.constructor.call(this,a,b),this.addOptions(this.convertToOptions(c))}return b.Extend(d,a),d.prototype.select=function(a){var b=this.$element.find("option").filter(function(b,c){return c.value==a.id.toString()});0===b.length&&(b=this.option(a),this.addOptions(b)),d.__super__.select.call(this,a)},d.prototype.convertToOptions=function(a){function d(a){return function(){return c(this).val()==a.id}}for(var e=this,f=this.$element.find("option"),g=f.map(function(){return e.item(c(this)).id}).get(),h=[],i=0;i<a.length;i++){var j=this._normalizeItem(a[i]);if(c.inArray(j.id,g)>=0){var k=f.filter(d(j)),l=this.item(k),m=c.extend(!0,{},l,j),n=this.option(m);k.replaceWith(n)}else{var o=this.option(j);if(j.children){var p=this.convertToOptions(j.children);b.appendMany(o,p)}h.push(o)}}return h},d}),b.define("select2/data/ajax",["./array","../utils","jquery"],function(a,b,c){function d(a,b){this.ajaxOptions=this._applyDefaults(b.get("ajax")),null!=this.ajaxOptions.processResults&&(this.processResults=this.ajaxOptions.processResults),d.__super__.constructor.call(this,a,b)}return b.Extend(d,a),d.prototype._applyDefaults=function(a){var b={data:function(a){return c.extend({},a,{q:a.term})},transport:function(a,b,d){var e=c.ajax(a);return e.then(b),e.fail(d),e}};return c.extend({},b,a,!0)},d.prototype.processResults=function(a){return a},d.prototype.query=function(a,b){function d(){var d=f.transport(f,function(d){var f=e.processResults(d,a);e.options.get("debug")&&window.console&&console.error&&(f&&f.results&&c.isArray(f.results)||console.error("Select2: The AJAX results did not return an array in the `results` key of the response.")),b(f)},function(){});e._request=d}var e=this;null!=this._request&&(c.isFunction(this._request.abort)&&this._request.abort(),this._request=null);var f=c.extend({type:"GET"},this.ajaxOptions);"function"==typeof f.url&&(f.url=f.url.call(this.$element,a)),"function"==typeof f.data&&(f.data=f.data.call(this.$element,a)),this.ajaxOptions.delay&&""!==a.term?(this._queryTimeout&&window.clearTimeout(this._queryTimeout),this._queryTimeout=window.setTimeout(d,this.ajaxOptions.delay)):d()},d}),b.define("select2/data/tags",["jquery"],function(a){function b(b,c,d){var e=d.get("tags"),f=d.get("createTag");if(void 0!==f&&(this.createTag=f),b.call(this,c,d),a.isArray(e))for(var g=0;g<e.length;g++){var h=e[g],i=this._normalizeItem(h),j=this.option(i);this.$element.append(j)}}return b.prototype.query=function(a,b,c){function d(a,f){for(var g=a.results,h=0;h<g.length;h++){var i=g[h],j=null!=i.children&&!d({results:i.children},!0),k=i.text===b.term;if(k||j)return f?!1:(a.data=g,void c(a))}if(f)return!0;var l=e.createTag(b);if(null!=l){var m=e.option(l);m.attr("data-select2-tag",!0),e.addOptions([m]),e.insertTag(g,l)}a.results=g,c(a)}var e=this;return this._removeOldTags(),null==b.term||null!=b.page?void a.call(this,b,c):void a.call(this,b,d)},b.prototype.createTag=function(b,c){var d=a.trim(c.term);return""===d?null:{id:d,text:d}},b.prototype.insertTag=function(a,b,c){b.unshift(c)},b.prototype._removeOldTags=function(b){var c=(this._lastTag,this.$element.find("option[data-select2-tag]"));c.each(function(){this.selected||a(this).remove()})},b}),b.define("select2/data/tokenizer",["jquery"],function(a){function b(a,b,c){var d=c.get("tokenizer");void 0!==d&&(this.tokenizer=d),a.call(this,b,c)}return b.prototype.bind=function(a,b,c){a.call(this,b,c),this.$search=b.dropdown.$search||b.selection.$search||c.find(".select2-search__field")},b.prototype.query=function(a,b,c){function d(a){e.trigger("select",{data:a})}var e=this;b.term=b.term||"";var f=this.tokenizer(b,this.options,d);f.term!==b.term&&(this.$search.length&&(this.$search.val(f.term),this.$search.focus()),b.term=f.term),a.call(this,b,c)},b.prototype.tokenizer=function(b,c,d,e){for(var f=d.get("tokenSeparators")||[],g=c.term,h=0,i=this.createTag||function(a){return{id:a.term,text:a.term}};h<g.length;){var j=g[h];if(-1!==a.inArray(j,f)){var k=g.substr(0,h),l=a.extend({},c,{term:k}),m=i(l);null!=m?(e(m),g=g.substr(h+1)||"",h=0):h++}else h++}return{term:g}},b}),b.define("select2/data/minimumInputLength",[],function(){function a(a,b,c){this.minimumInputLength=c.get("minimumInputLength"),a.call(this,b,c)}return a.prototype.query=function(a,b,c){return b.term=b.term||"",b.term.length<this.minimumInputLength?void this.trigger("results:message",{message:"inputTooShort",args:{minimum:this.minimumInputLength,input:b.term,params:b}}):void a.call(this,b,c)},a}),b.define("select2/data/maximumInputLength",[],function(){function a(a,b,c){this.maximumInputLength=c.get("maximumInputLength"),a.call(this,b,c)}return a.prototype.query=function(a,b,c){return b.term=b.term||"",this.maximumInputLength>0&&b.term.length>this.maximumInputLength?void this.trigger("results:message",{message:"inputTooLong",args:{maximum:this.maximumInputLength,input:b.term,params:b}}):void a.call(this,b,c)},a}),b.define("select2/data/maximumSelectionLength",[],function(){function a(a,b,c){this.maximumSelectionLength=c.get("maximumSelectionLength"),a.call(this,b,c)}return a.prototype.query=function(a,b,c){var d=this;this.current(function(e){var f=null!=e?e.length:0;return d.maximumSelectionLength>0&&f>=d.maximumSelectionLength?void d.trigger("results:message",{message:"maximumSelected",args:{maximum:d.maximumSelectionLength}}):void a.call(d,b,c)})},a}),b.define("select2/dropdown",["jquery","./utils"],function(a,b){function c(a,b){this.$element=a,this.options=b,c.__super__.constructor.call(this)}return b.Extend(c,b.Observable),c.prototype.render=function(){var b=a('<span class="select2-dropdown"><span class="select2-results"></span></span>');return b.attr("dir",this.options.get("dir")),this.$dropdown=b,b},c.prototype.bind=function(){},c.prototype.position=function(a,b){},c.prototype.destroy=function(){this.$dropdown.remove()},c}),b.define("select2/dropdown/search",["jquery","../utils"],function(a,b){function c(){}return c.prototype.render=function(b){var c=b.call(this),d=a('<span class="select2-search select2-search--dropdown"><input class="select2-search__field" type="search" tabindex="-1" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" role="textbox" /></span>');return this.$searchContainer=d,this.$search=d.find("input"),c.prepend(d),c},c.prototype.bind=function(b,c,d){var e=this;b.call(this,c,d),this.$search.on("keydown",function(a){e.trigger("keypress",a),e._keyUpPrevented=a.isDefaultPrevented()}),this.$search.on("input",function(b){a(this).off("keyup")}),this.$search.on("keyup input",function(a){e.handleSearch(a)}),c.on("open",function(){e.$search.attr("tabindex",0),e.$search.focus(),window.setTimeout(function(){e.$search.focus()},0)}),c.on("close",function(){e.$search.attr("tabindex",-1),e.$search.val("")}),c.on("results:all",function(a){if(null==a.query.term||""===a.query.term){var b=e.showSearch(a);b?e.$searchContainer.removeClass("select2-search--hide"):e.$searchContainer.addClass("select2-search--hide")}})},c.prototype.handleSearch=function(a){if(!this._keyUpPrevented){var b=this.$search.val();this.trigger("query",{term:b})}this._keyUpPrevented=!1},c.prototype.showSearch=function(a,b){return!0},c}),b.define("select2/dropdown/hidePlaceholder",[],function(){function a(a,b,c,d){this.placeholder=this.normalizePlaceholder(c.get("placeholder")),a.call(this,b,c,d)}return a.prototype.append=function(a,b){b.results=this.removePlaceholder(b.results),a.call(this,b)},a.prototype.normalizePlaceholder=function(a,b){return"string"==typeof b&&(b={id:"",text:b}),b},a.prototype.removePlaceholder=function(a,b){for(var c=b.slice(0),d=b.length-1;d>=0;d--){var e=b[d];this.placeholder.id===e.id&&c.splice(d,1)}return c},a}),b.define("select2/dropdown/infiniteScroll",["jquery"],function(a){function b(a,b,c,d){this.lastParams={},a.call(this,b,c,d),this.$loadingMore=this.createLoadingMore(),this.loading=!1}return b.prototype.append=function(a,b){this.$loadingMore.remove(),this.loading=!1,a.call(this,b),this.showLoadingMore(b)&&this.$results.append(this.$loadingMore)},b.prototype.bind=function(b,c,d){var e=this;b.call(this,c,d),c.on("query",function(a){e.lastParams=a,e.loading=!0}),c.on("query:append",function(a){e.lastParams=a,e.loading=!0}),this.$results.on("scroll",function(){var b=a.contains(document.documentElement,e.$loadingMore[0]);if(!e.loading&&b){var c=e.$results.offset().top+e.$results.outerHeight(!1),d=e.$loadingMore.offset().top+e.$loadingMore.outerHeight(!1);c+50>=d&&e.loadMore()}})},b.prototype.loadMore=function(){this.loading=!0;var b=a.extend({},{page:1},this.lastParams);b.page++,this.trigger("query:append",b)},b.prototype.showLoadingMore=function(a,b){return b.pagination&&b.pagination.more},b.prototype.createLoadingMore=function(){var b=a('<li class="select2-results__option select2-results__option--load-more"role="treeitem" aria-disabled="true"></li>'),c=this.options.get("translations").get("loadingMore");return b.html(c(this.lastParams)),b},b}),b.define("select2/dropdown/attachBody",["jquery","../utils"],function(a,b){function c(b,c,d){this.$dropdownParent=d.get("dropdownParent")||a(document.body),b.call(this,c,d)}return c.prototype.bind=function(a,b,c){var d=this,e=!1;a.call(this,b,c),b.on("open",function(){d._showDropdown(),d._attachPositioningHandler(b),e||(e=!0,b.on("results:all",function(){d._positionDropdown(),d._resizeDropdown()}),b.on("results:append",function(){d._positionDropdown(),d._resizeDropdown()}))}),b.on("close",function(){d._hideDropdown(),d._detachPositioningHandler(b)}),this.$dropdownContainer.on("mousedown",function(a){a.stopPropagation()})},c.prototype.destroy=function(a){a.call(this),this.$dropdownContainer.remove()},c.prototype.position=function(a,b,c){b.attr("class",c.attr("class")),b.removeClass("select2"),b.addClass("select2-container--open"),b.css({position:"absolute",top:-999999}),this.$container=c},c.prototype.render=function(b){var c=a("<span></span>"),d=b.call(this);return c.append(d),this.$dropdownContainer=c,c},c.prototype._hideDropdown=function(a){this.$dropdownContainer.detach()},c.prototype._attachPositioningHandler=function(c,d){var e=this,f="scroll.select2."+d.id,g="resize.select2."+d.id,h="orientationchange.select2."+d.id,i=this.$container.parents().filter(b.hasScroll);i.each(function(){a(this).data("select2-scroll-position",{x:a(this).scrollLeft(),y:a(this).scrollTop()})}),i.on(f,function(b){var c=a(this).data("select2-scroll-position");a(this).scrollTop(c.y)}),a(window).on(f+" "+g+" "+h,function(a){e._positionDropdown(),e._resizeDropdown()})},c.prototype._detachPositioningHandler=function(c,d){var e="scroll.select2."+d.id,f="resize.select2."+d.id,g="orientationchange.select2."+d.id,h=this.$container.parents().filter(b.hasScroll);h.off(e),a(window).off(e+" "+f+" "+g)},c.prototype._positionDropdown=function(){var b=a(window),c=this.$dropdown.hasClass("select2-dropdown--above"),d=this.$dropdown.hasClass("select2-dropdown--below"),e=null,f=(this.$container.position(),this.$container.offset());f.bottom=f.top+this.$container.outerHeight(!1);var g={height:this.$container.outerHeight(!1)};g.top=f.top,g.bottom=f.top+g.height;var h={height:this.$dropdown.outerHeight(!1)},i={top:b.scrollTop(),bottom:b.scrollTop()+b.height()},j=i.top<f.top-h.height,k=i.bottom>f.bottom+h.height,l={left:f.left,top:g.bottom};if("static"!==this.$dropdownParent[0].style.position){var m=this.$dropdownParent.offset();l.top-=m.top,l.left-=m.left}c||d||(e="below"),k||!j||c?!j&&k&&c&&(e="below"):e="above",("above"==e||c&&"below"!==e)&&(l.top=g.top-h.height),null!=e&&(this.$dropdown.removeClass("select2-dropdown--below select2-dropdown--above").addClass("select2-dropdown--"+e),this.$container.removeClass("select2-container--below select2-container--above").addClass("select2-container--"+e)),this.$dropdownContainer.css(l)},c.prototype._resizeDropdown=function(){var a={width:this.$container.outerWidth(!1)+"px"};this.options.get("dropdownAutoWidth")&&(a.minWidth=a.width,a.width="auto"),this.$dropdown.css(a)},c.prototype._showDropdown=function(a){this.$dropdownContainer.appendTo(this.$dropdownParent),this._positionDropdown(),this._resizeDropdown()},c}),b.define("select2/dropdown/minimumResultsForSearch",[],function(){function a(b){for(var c=0,d=0;d<b.length;d++){var e=b[d];e.children?c+=a(e.children):c++}return c}function b(a,b,c,d){this.minimumResultsForSearch=c.get("minimumResultsForSearch"),this.minimumResultsForSearch<0&&(this.minimumResultsForSearch=1/0),a.call(this,b,c,d)}return b.prototype.showSearch=function(b,c){return a(c.data.results)<this.minimumResultsForSearch?!1:b.call(this,c)},b}),b.define("select2/dropdown/selectOnClose",[],function(){function a(){}return a.prototype.bind=function(a,b,c){var d=this;a.call(this,b,c),b.on("close",function(){d._handleSelectOnClose()})},a.prototype._handleSelectOnClose=function(){var a=this.getHighlightedResults();if(!(a.length<1)){var b=a.data("data");null!=b.element&&b.element.selected||null==b.element&&b.selected||this.trigger("select",{data:b})}},a}),b.define("select2/dropdown/closeOnSelect",[],function(){function a(){}return a.prototype.bind=function(a,b,c){var d=this;a.call(this,b,c),b.on("select",function(a){d._selectTriggered(a)}),b.on("unselect",function(a){d._selectTriggered(a)})},a.prototype._selectTriggered=function(a,b){var c=b.originalEvent;c&&c.ctrlKey||this.trigger("close",{})},a}),b.define("select2/i18n/en",[],function(){return{errorLoading:function(){return"The results could not be loaded."},inputTooLong:function(a){var b=a.input.length-a.maximum,c="Please delete "+b+" character";return 1!=b&&(c+="s"),c},inputTooShort:function(a){var b=a.minimum-a.input.length,c="Please enter "+b+" or more characters";return c},loadingMore:function(){return"Loading more results…"},maximumSelected:function(a){var b="You can only select "+a.maximum+" item";return 1!=a.maximum&&(b+="s"),b},noResults:function(){return"No results found"},searching:function(){return"Searching…"}}}),b.define("select2/defaults",["jquery","require","./results","./selection/single","./selection/multiple","./selection/placeholder","./selection/allowClear","./selection/search","./selection/eventRelay","./utils","./translation","./diacritics","./data/select","./data/array","./data/ajax","./data/tags","./data/tokenizer","./data/minimumInputLength","./data/maximumInputLength","./data/maximumSelectionLength","./dropdown","./dropdown/search","./dropdown/hidePlaceholder","./dropdown/infiniteScroll","./dropdown/attachBody","./dropdown/minimumResultsForSearch","./dropdown/selectOnClose","./dropdown/closeOnSelect","./i18n/en"],function(a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C){function D(){this.reset()}D.prototype.apply=function(l){if(l=a.extend({},this.defaults,l),null==l.dataAdapter){if(null!=l.ajax?l.dataAdapter=o:null!=l.data?l.dataAdapter=n:l.dataAdapter=m,l.minimumInputLength>0&&(l.dataAdapter=j.Decorate(l.dataAdapter,r)),l.maximumInputLength>0&&(l.dataAdapter=j.Decorate(l.dataAdapter,s)),l.maximumSelectionLength>0&&(l.dataAdapter=j.Decorate(l.dataAdapter,t)),l.tags&&(l.dataAdapter=j.Decorate(l.dataAdapter,p)),(null!=l.tokenSeparators||null!=l.tokenizer)&&(l.dataAdapter=j.Decorate(l.dataAdapter,q)),null!=l.query){var C=b(l.amdBase+"compat/query");l.dataAdapter=j.Decorate(l.dataAdapter,C)}if(null!=l.initSelection){var D=b(l.amdBase+"compat/initSelection");l.dataAdapter=j.Decorate(l.dataAdapter,D)}}if(null==l.resultsAdapter&&(l.resultsAdapter=c,null!=l.ajax&&(l.resultsAdapter=j.Decorate(l.resultsAdapter,x)),null!=l.placeholder&&(l.resultsAdapter=j.Decorate(l.resultsAdapter,w)),l.selectOnClose&&(l.resultsAdapter=j.Decorate(l.resultsAdapter,A))),null==l.dropdownAdapter){if(l.multiple)l.dropdownAdapter=u;else{var E=j.Decorate(u,v);l.dropdownAdapter=E}if(0!==l.minimumResultsForSearch&&(l.dropdownAdapter=j.Decorate(l.dropdownAdapter,z)),l.closeOnSelect&&(l.dropdownAdapter=j.Decorate(l.dropdownAdapter,B)),null!=l.dropdownCssClass||null!=l.dropdownCss||null!=l.adaptDropdownCssClass){var F=b(l.amdBase+"compat/dropdownCss");l.dropdownAdapter=j.Decorate(l.dropdownAdapter,F)}l.dropdownAdapter=j.Decorate(l.dropdownAdapter,y)}if(null==l.selectionAdapter){if(l.multiple?l.selectionAdapter=e:l.selectionAdapter=d,null!=l.placeholder&&(l.selectionAdapter=j.Decorate(l.selectionAdapter,f)),l.allowClear&&(l.selectionAdapter=j.Decorate(l.selectionAdapter,g)),l.multiple&&(l.selectionAdapter=j.Decorate(l.selectionAdapter,h)),null!=l.containerCssClass||null!=l.containerCss||null!=l.adaptContainerCssClass){var G=b(l.amdBase+"compat/containerCss");l.selectionAdapter=j.Decorate(l.selectionAdapter,G)}l.selectionAdapter=j.Decorate(l.selectionAdapter,i)}if("string"==typeof l.language)if(l.language.indexOf("-")>0){var H=l.language.split("-"),I=H[0];l.language=[l.language,I]}else l.language=[l.language];if(a.isArray(l.language)){var J=new k;l.language.push("en");for(var K=l.language,L=0;L<K.length;L++){var M=K[L],N={};try{N=k.loadPath(M)}catch(O){try{M=this.defaults.amdLanguageBase+M,N=k.loadPath(M)}catch(P){l.debug&&window.console&&console.warn&&console.warn('Select2: The language file for "'+M+'" could not be automatically loaded. A fallback will be used instead.');continue}}J.extend(N)}l.translations=J}else{var Q=k.loadPath(this.defaults.amdLanguageBase+"en"),R=new k(l.language);R.extend(Q),l.translations=R}return l},D.prototype.reset=function(){function b(a){function b(a){return l[a]||a}return a.replace(/[^\u0000-\u007E]/g,b)}function c(d,e){if(""===a.trim(d.term))return e;if(e.children&&e.children.length>0){for(var f=a.extend(!0,{},e),g=e.children.length-1;g>=0;g--){var h=e.children[g],i=c(d,h);null==i&&f.children.splice(g,1)}return f.children.length>0?f:c(d,f)}var j=b(e.text).toUpperCase(),k=b(d.term).toUpperCase();return j.indexOf(k)>-1?e:null}this.defaults={amdBase:"./",amdLanguageBase:"./i18n/",closeOnSelect:!0,debug:!1,dropdownAutoWidth:!1,escapeMarkup:j.escapeMarkup,language:C,matcher:c,minimumInputLength:0,maximumInputLength:0,maximumSelectionLength:0,minimumResultsForSearch:0,selectOnClose:!1,sorter:function(a){return a},templateResult:function(a){return a.text},templateSelection:function(a){return a.text},theme:"default",width:"resolve"}},D.prototype.set=function(b,c){var d=a.camelCase(b),e={};e[d]=c;var f=j._convertData(e);a.extend(this.defaults,f)};var E=new D;return E}),b.define("select2/options",["require","jquery","./defaults","./utils"],function(a,b,c,d){function e(b,e){if(this.options=b,null!=e&&this.fromElement(e),this.options=c.apply(this.options),e&&e.is("input")){var f=a(this.get("amdBase")+"compat/inputData");this.options.dataAdapter=d.Decorate(this.options.dataAdapter,f)}}return e.prototype.fromElement=function(a){var c=["select2"];null==this.options.multiple&&(this.options.multiple=a.prop("multiple")),null==this.options.disabled&&(this.options.disabled=a.prop("disabled")),null==this.options.language&&(a.prop("lang")?this.options.language=a.prop("lang").toLowerCase():a.closest("[lang]").prop("lang")&&(this.options.language=a.closest("[lang]").prop("lang"))),null==this.options.dir&&(a.prop("dir")?this.options.dir=a.prop("dir"):a.closest("[dir]").prop("dir")?this.options.dir=a.closest("[dir]").prop("dir"):this.options.dir="ltr"),a.prop("disabled",this.options.disabled),a.prop("multiple",this.options.multiple),a.data("select2Tags")&&(this.options.debug&&window.console&&console.warn&&console.warn('Select2: The `data-select2-tags` attribute has been changed to use the `data-data` and `data-tags="true"` attributes and will be removed in future versions of Select2.'),a.data("data",a.data("select2Tags")),a.data("tags",!0)),a.data("ajaxUrl")&&(this.options.debug&&window.console&&console.warn&&console.warn("Select2: The `data-ajax-url` attribute has been changed to `data-ajax--url` and support for the old attribute will be removed in future versions of Select2."),a.attr("ajax--url",a.data("ajaxUrl")),a.data("ajax--url",a.data("ajaxUrl")));var e={};e=b.fn.jquery&&"1."==b.fn.jquery.substr(0,2)&&a[0].dataset?b.extend(!0,{},a[0].dataset,a.data()):a.data();var f=b.extend(!0,{},e);f=d._convertData(f);for(var g in f)b.inArray(g,c)>-1||(b.isPlainObject(this.options[g])?b.extend(this.options[g],f[g]):this.options[g]=f[g]);return this},e.prototype.get=function(a){return this.options[a]},e.prototype.set=function(a,b){this.options[a]=b},e}),b.define("select2/core",["jquery","./options","./utils","./keys"],function(a,b,c,d){var e=function(a,c){null!=a.data("select2")&&a.data("select2").destroy(),this.$element=a,this.id=this._generateId(a),c=c||{},this.options=new b(c,a),e.__super__.constructor.call(this);var d=a.attr("tabindex")||0;a.data("old-tabindex",d),a.attr("tabindex","-1");var f=this.options.get("dataAdapter");this.dataAdapter=new f(a,this.options);var g=this.render();this._placeContainer(g);var h=this.options.get("selectionAdapter");this.selection=new h(a,this.options),this.$selection=this.selection.render(),this.selection.position(this.$selection,g);var i=this.options.get("dropdownAdapter");this.dropdown=new i(a,this.options),this.$dropdown=this.dropdown.render(),this.dropdown.position(this.$dropdown,g);var j=this.options.get("resultsAdapter");this.results=new j(a,this.options,this.dataAdapter),this.$results=this.results.render(),this.results.position(this.$results,this.$dropdown);var k=this;this._bindAdapters(),this._registerDomEvents(),this._registerDataEvents(),this._registerSelectionEvents(),this._registerDropdownEvents(),this._registerResultsEvents(),this._registerEvents(),this.dataAdapter.current(function(a){k.trigger("selection:update",{data:a})}),a.addClass("select2-hidden-accessible"),a.attr("aria-hidden","true"),this._syncAttributes(),a.data("select2",this)};return c.Extend(e,c.Observable),e.prototype._generateId=function(a){var b="";return b=null!=a.attr("id")?a.attr("id"):null!=a.attr("name")?a.attr("name")+"-"+c.generateChars(2):c.generateChars(4),b="select2-"+b},e.prototype._placeContainer=function(a){a.insertAfter(this.$element);var b=this._resolveWidth(this.$element,this.options.get("width"));null!=b&&a.css("width",b)},e.prototype._resolveWidth=function(a,b){var c=/^width:(([-+]?([0-9]*\.)?[0-9]+)(px|em|ex|%|in|cm|mm|pt|pc))/i;if("resolve"==b){var d=this._resolveWidth(a,"style");return null!=d?d:this._resolveWidth(a,"element")}if("element"==b){var e=a.outerWidth(!1);return 0>=e?"auto":e+"px"}if("style"==b){var f=a.attr("style");if("string"!=typeof f)return null;for(var g=f.split(";"),h=0,i=g.length;i>h;h+=1){var j=g[h].replace(/\s/g,""),k=j.match(c);if(null!==k&&k.length>=1)return k[1]}return null}return b},e.prototype._bindAdapters=function(){this.dataAdapter.bind(this,this.$container),this.selection.bind(this,this.$container),this.dropdown.bind(this,this.$container),this.results.bind(this,this.$container)},e.prototype._registerDomEvents=function(){var b=this;this.$element.on("change.select2",function(){b.dataAdapter.current(function(a){b.trigger("selection:update",{data:a})})}),this._sync=c.bind(this._syncAttributes,this),this.$element[0].attachEvent&&this.$element[0].attachEvent("onpropertychange",this._sync);var d=window.MutationObserver||window.WebKitMutationObserver||window.MozMutationObserver;null!=d?(this._observer=new d(function(c){a.each(c,b._sync)}),this._observer.observe(this.$element[0],{attributes:!0,subtree:!1})):this.$element[0].addEventListener&&this.$element[0].addEventListener("DOMAttrModified",b._sync,!1)},e.prototype._registerDataEvents=function(){var a=this;this.dataAdapter.on("*",function(b,c){a.trigger(b,c)})},e.prototype._registerSelectionEvents=function(){var b=this,c=["toggle","focus"];this.selection.on("toggle",function(){b.toggleDropdown()}),this.selection.on("focus",function(a){b.focus(a)}),this.selection.on("*",function(d,e){-1===a.inArray(d,c)&&b.trigger(d,e)})},e.prototype._registerDropdownEvents=function(){var a=this;this.dropdown.on("*",function(b,c){a.trigger(b,c)})},e.prototype._registerResultsEvents=function(){var a=this;this.results.on("*",function(b,c){a.trigger(b,c)})},e.prototype._registerEvents=function(){var a=this;this.on("open",function(){a.$container.addClass("select2-container--open")}),this.on("close",function(){a.$container.removeClass("select2-container--open")}),this.on("enable",function(){a.$container.removeClass("select2-container--disabled")}),this.on("disable",function(){a.$container.addClass("select2-container--disabled")}),this.on("blur",function(){a.$container.removeClass("select2-container--focus")}),this.on("query",function(b){a.isOpen()||a.trigger("open",{}),this.dataAdapter.query(b,function(c){a.trigger("results:all",{data:c,query:b})})}),this.on("query:append",function(b){this.dataAdapter.query(b,function(c){a.trigger("results:append",{data:c,query:b})})}),this.on("keypress",function(b){var c=b.which;a.isOpen()?c===d.ESC||c===d.TAB||c===d.UP&&b.altKey?(a.close(),b.preventDefault()):c===d.ENTER?(a.trigger("results:select",{}),b.preventDefault()):c===d.SPACE&&b.ctrlKey?(a.trigger("results:toggle",{}),b.preventDefault()):c===d.UP?(a.trigger("results:previous",{}),b.preventDefault()):c===d.DOWN&&(a.trigger("results:next",{}),b.preventDefault()):(c===d.ENTER||c===d.SPACE||c===d.DOWN&&b.altKey)&&(a.open(),b.preventDefault())})},e.prototype._syncAttributes=function(){this.options.set("disabled",this.$element.prop("disabled")),this.options.get("disabled")?(this.isOpen()&&this.close(),this.trigger("disable",{})):this.trigger("enable",{})},e.prototype.trigger=function(a,b){var c=e.__super__.trigger,d={open:"opening",close:"closing",select:"selecting",unselect:"unselecting"};if(void 0===b&&(b={}),a in d){var f=d[a],g={prevented:!1,name:a,args:b};if(c.call(this,f,g),g.prevented)return void(b.prevented=!0)}c.call(this,a,b)},e.prototype.toggleDropdown=function(){this.options.get("disabled")||(this.isOpen()?this.close():this.open())},e.prototype.open=function(){this.isOpen()||this.trigger("query",{})},e.prototype.close=function(){this.isOpen()&&this.trigger("close",{})},e.prototype.isOpen=function(){return this.$container.hasClass("select2-container--open")},e.prototype.hasFocus=function(){return this.$container.hasClass("select2-container--focus")},e.prototype.focus=function(a){this.hasFocus()||(this.$container.addClass("select2-container--focus"),this.trigger("focus",{}))},e.prototype.enable=function(a){this.options.get("debug")&&window.console&&console.warn&&console.warn('Select2: The `select2("enable")` method has been deprecated and will be removed in later Select2 versions. Use $element.prop("disabled") instead.'),(null==a||0===a.length)&&(a=[!0]);var b=!a[0];this.$element.prop("disabled",b)},e.prototype.data=function(){this.options.get("debug")&&arguments.length>0&&window.console&&console.warn&&console.warn('Select2: Data can no longer be set using `select2("data")`. You should consider setting the value instead using `$element.val()`.');var a=[];return this.dataAdapter.current(function(b){a=b}),a},e.prototype.val=function(b){if(this.options.get("debug")&&window.console&&console.warn&&console.warn('Select2: The `select2("val")` method has been deprecated and will be removed in later Select2 versions. Use $element.val() instead.'),null==b||0===b.length)return this.$element.val();var c=b[0];a.isArray(c)&&(c=a.map(c,function(a){return a.toString()})),this.$element.val(c).trigger("change")},e.prototype.destroy=function(){this.$container.remove(),this.$element[0].detachEvent&&this.$element[0].detachEvent("onpropertychange",this._sync),null!=this._observer?(this._observer.disconnect(),this._observer=null):this.$element[0].removeEventListener&&this.$element[0].removeEventListener("DOMAttrModified",this._sync,!1),this._sync=null,this.$element.off(".select2"),this.$element.attr("tabindex",this.$element.data("old-tabindex")),this.$element.removeClass("select2-hidden-accessible"),this.$element.attr("aria-hidden","false"),this.$element.removeData("select2"),this.dataAdapter.destroy(),this.selection.destroy(),this.dropdown.destroy(),this.results.destroy(),this.dataAdapter=null,this.selection=null,this.dropdown=null,this.results=null},e.prototype.render=function(){var b=a('<span class="select2 select2-container"><span class="selection"></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>');return b.attr("dir",this.options.get("dir")),this.$container=b,this.$container.addClass("select2-container--"+this.options.get("theme")),b.data("element",this.$element),b},e}),b.define("jquery-mousewheel",["jquery"],function(a){return a}),b.define("jquery.select2",["jquery","jquery-mousewheel","./select2/core","./select2/defaults"],function(a,b,c,d){if(null==a.fn.select2){var e=["open","close","destroy"];a.fn.select2=function(b){if(b=b||{},"object"==typeof b)return this.each(function(){var d=a.extend(!0,{},b);new c(a(this),d)}),this;if("string"==typeof b){var d;return this.each(function(){var c=a(this).data("select2");null==c&&window.console&&console.error&&console.error("The select2('"+b+"') method was called on an element that is not using Select2.");var e=Array.prototype.slice.call(arguments,1);d=c[b].apply(c,e)}),a.inArray(b,e)>-1?this:d}throw new Error("Invalid arguments for Select2: "+b)}}return null==a.fn.select2.defaults&&(a.fn.select2.defaults=d),c}),{define:b.define,require:b.require}}(),c=b.require("jquery.select2");return a.fn.select2.amd=b,c});

//! moment.js
//! version : 2.11.2
//! authors : Tim Wood, Iskren Chernev, Moment.js contributors
//! license : MIT
//! momentjs.com
!function(a,b){"object"==typeof exports&&"undefined"!=typeof module?module.exports=b():"function"==typeof define&&define.amd?define(b):a.moment=b()}(this,function(){"use strict";function a(){return Uc.apply(null,arguments)}
// This is done to register the method called with moment()
// without creating circular dependencies.
function b(a){Uc=a}function c(a){return"[object Array]"===Object.prototype.toString.call(a)}function d(a){return a instanceof Date||"[object Date]"===Object.prototype.toString.call(a)}function e(a,b){var c,d=[];for(c=0;c<a.length;++c)d.push(b(a[c],c));return d}function f(a,b){return Object.prototype.hasOwnProperty.call(a,b)}function g(a,b){for(var c in b)f(b,c)&&(a[c]=b[c]);return f(b,"toString")&&(a.toString=b.toString),f(b,"valueOf")&&(a.valueOf=b.valueOf),a}function h(a,b,c,d){return Da(a,b,c,d,!0).utc()}function i(){
// We need to deep clone this object.
return{empty:!1,unusedTokens:[],unusedInput:[],overflow:-2,charsLeftOver:0,nullInput:!1,invalidMonth:null,invalidFormat:!1,userInvalidated:!1,iso:!1}}function j(a){return null==a._pf&&(a._pf=i()),a._pf}function k(a){if(null==a._isValid){var b=j(a);a._isValid=!(isNaN(a._d.getTime())||!(b.overflow<0)||b.empty||b.invalidMonth||b.invalidWeekday||b.nullInput||b.invalidFormat||b.userInvalidated),a._strict&&(a._isValid=a._isValid&&0===b.charsLeftOver&&0===b.unusedTokens.length&&void 0===b.bigHour)}return a._isValid}function l(a){var b=h(NaN);return null!=a?g(j(b),a):j(b).userInvalidated=!0,b}function m(a){return void 0===a}function n(a,b){var c,d,e;if(m(b._isAMomentObject)||(a._isAMomentObject=b._isAMomentObject),m(b._i)||(a._i=b._i),m(b._f)||(a._f=b._f),m(b._l)||(a._l=b._l),m(b._strict)||(a._strict=b._strict),m(b._tzm)||(a._tzm=b._tzm),m(b._isUTC)||(a._isUTC=b._isUTC),m(b._offset)||(a._offset=b._offset),m(b._pf)||(a._pf=j(b)),m(b._locale)||(a._locale=b._locale),Wc.length>0)for(c in Wc)d=Wc[c],e=b[d],m(e)||(a[d]=e);return a}
// Moment prototype object
function o(b){n(this,b),this._d=new Date(null!=b._d?b._d.getTime():NaN),Xc===!1&&(Xc=!0,a.updateOffset(this),Xc=!1)}function p(a){return a instanceof o||null!=a&&null!=a._isAMomentObject}function q(a){return 0>a?Math.ceil(a):Math.floor(a)}function r(a){var b=+a,c=0;return 0!==b&&isFinite(b)&&(c=q(b)),c}
// compare two arrays, return the number of differences
function s(a,b,c){var d,e=Math.min(a.length,b.length),f=Math.abs(a.length-b.length),g=0;for(d=0;e>d;d++)(c&&a[d]!==b[d]||!c&&r(a[d])!==r(b[d]))&&g++;return g+f}function t(){}function u(a){return a?a.toLowerCase().replace("_","-"):a}
// pick the locale from the array
// try ['en-au', 'en-gb'] as 'en-au', 'en-gb', 'en', as in move through the list trying each
// substring from most specific to least, but move to the next array item if it's a more specific variant than the current root
function v(a){for(var b,c,d,e,f=0;f<a.length;){for(e=u(a[f]).split("-"),b=e.length,c=u(a[f+1]),c=c?c.split("-"):null;b>0;){if(d=w(e.slice(0,b).join("-")))return d;if(c&&c.length>=b&&s(e,c,!0)>=b-1)
//the next array item is better than a shallower substring of this one
break;b--}f++}return null}function w(a){var b=null;
// TODO: Find a better way to register and load all the locales in Node
if(!Yc[a]&&"undefined"!=typeof module&&module&&module.exports)try{b=Vc._abbr,require("./locale/"+a),
// because defineLocale currently also sets the global locale, we
// want to undo that for lazy loaded locales
x(b)}catch(c){}return Yc[a]}
// This function will load locale and then set the global locale.  If
// no arguments are passed in, it will simply return the current global
// locale key.
function x(a,b){var c;
// moment.duration._locale = moment._locale = data;
return a&&(c=m(b)?z(a):y(a,b),c&&(Vc=c)),Vc._abbr}function y(a,b){
// backwards compat for now: also set the locale
// useful for testing
return null!==b?(b.abbr=a,Yc[a]=Yc[a]||new t,Yc[a].set(b),x(a),Yc[a]):(delete Yc[a],null)}
// returns locale data
function z(a){var b;if(a&&a._locale&&a._locale._abbr&&(a=a._locale._abbr),!a)return Vc;if(!c(a)){if(b=w(a))return b;a=[a]}return v(a)}function A(a,b){var c=a.toLowerCase();Zc[c]=Zc[c+"s"]=Zc[b]=a}function B(a){return"string"==typeof a?Zc[a]||Zc[a.toLowerCase()]:void 0}function C(a){var b,c,d={};for(c in a)f(a,c)&&(b=B(c),b&&(d[b]=a[c]));return d}function D(a){return a instanceof Function||"[object Function]"===Object.prototype.toString.call(a)}function E(b,c){return function(d){return null!=d?(G(this,b,d),a.updateOffset(this,c),this):F(this,b)}}function F(a,b){return a.isValid()?a._d["get"+(a._isUTC?"UTC":"")+b]():NaN}function G(a,b,c){a.isValid()&&a._d["set"+(a._isUTC?"UTC":"")+b](c)}
// MOMENTS
function H(a,b){var c;if("object"==typeof a)for(c in a)this.set(c,a[c]);else if(a=B(a),D(this[a]))return this[a](b);return this}function I(a,b,c){var d=""+Math.abs(a),e=b-d.length,f=a>=0;return(f?c?"+":"":"-")+Math.pow(10,Math.max(0,e)).toString().substr(1)+d}
// token:    'M'
// padded:   ['MM', 2]
// ordinal:  'Mo'
// callback: function () { this.month() + 1 }
function J(a,b,c,d){var e=d;"string"==typeof d&&(e=function(){return this[d]()}),a&&(bd[a]=e),b&&(bd[b[0]]=function(){return I(e.apply(this,arguments),b[1],b[2])}),c&&(bd[c]=function(){return this.localeData().ordinal(e.apply(this,arguments),a)})}function K(a){return a.match(/\[[\s\S]/)?a.replace(/^\[|\]$/g,""):a.replace(/\\/g,"")}function L(a){var b,c,d=a.match($c);for(b=0,c=d.length;c>b;b++)bd[d[b]]?d[b]=bd[d[b]]:d[b]=K(d[b]);return function(e){var f="";for(b=0;c>b;b++)f+=d[b]instanceof Function?d[b].call(e,a):d[b];return f}}
// format date using native date object
function M(a,b){return a.isValid()?(b=N(b,a.localeData()),ad[b]=ad[b]||L(b),ad[b](a)):a.localeData().invalidDate()}function N(a,b){function c(a){return b.longDateFormat(a)||a}var d=5;for(_c.lastIndex=0;d>=0&&_c.test(a);)a=a.replace(_c,c),_c.lastIndex=0,d-=1;return a}function O(a,b,c){td[a]=D(b)?b:function(a,d){return a&&c?c:b}}function P(a,b){return f(td,a)?td[a](b._strict,b._locale):new RegExp(Q(a))}
// Code from http://stackoverflow.com/questions/3561493/is-there-a-regexp-escape-function-in-javascript
function Q(a){return R(a.replace("\\","").replace(/\\(\[)|\\(\])|\[([^\]\[]*)\]|\\(.)/g,function(a,b,c,d,e){return b||c||d||e}))}function R(a){return a.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&")}function S(a,b){var c,d=b;for("string"==typeof a&&(a=[a]),"number"==typeof b&&(d=function(a,c){c[b]=r(a)}),c=0;c<a.length;c++)ud[a[c]]=d}function T(a,b){S(a,function(a,c,d,e){d._w=d._w||{},b(a,d._w,d,e)})}function U(a,b,c){null!=b&&f(ud,a)&&ud[a](b,c._a,c,a)}function V(a,b){return new Date(Date.UTC(a,b+1,0)).getUTCDate()}function W(a,b){return c(this._months)?this._months[a.month()]:this._months[Ed.test(b)?"format":"standalone"][a.month()]}function X(a,b){return c(this._monthsShort)?this._monthsShort[a.month()]:this._monthsShort[Ed.test(b)?"format":"standalone"][a.month()]}function Y(a,b,c){var d,e,f;for(this._monthsParse||(this._monthsParse=[],this._longMonthsParse=[],this._shortMonthsParse=[]),d=0;12>d;d++){
// test the regex
if(e=h([2e3,d]),c&&!this._longMonthsParse[d]&&(this._longMonthsParse[d]=new RegExp("^"+this.months(e,"").replace(".","")+"$","i"),this._shortMonthsParse[d]=new RegExp("^"+this.monthsShort(e,"").replace(".","")+"$","i")),c||this._monthsParse[d]||(f="^"+this.months(e,"")+"|^"+this.monthsShort(e,""),this._monthsParse[d]=new RegExp(f.replace(".",""),"i")),c&&"MMMM"===b&&this._longMonthsParse[d].test(a))return d;if(c&&"MMM"===b&&this._shortMonthsParse[d].test(a))return d;if(!c&&this._monthsParse[d].test(a))return d}}
// MOMENTS
function Z(a,b){var c;
// TODO: Move this out of here!
return a.isValid()?"string"==typeof b&&(b=a.localeData().monthsParse(b),"number"!=typeof b)?a:(c=Math.min(a.date(),V(a.year(),b)),a._d["set"+(a._isUTC?"UTC":"")+"Month"](b,c),a):a}function $(b){return null!=b?(Z(this,b),a.updateOffset(this,!0),this):F(this,"Month")}function _(){return V(this.year(),this.month())}function aa(a){return this._monthsParseExact?(f(this,"_monthsRegex")||ca.call(this),a?this._monthsShortStrictRegex:this._monthsShortRegex):this._monthsShortStrictRegex&&a?this._monthsShortStrictRegex:this._monthsShortRegex}function ba(a){return this._monthsParseExact?(f(this,"_monthsRegex")||ca.call(this),a?this._monthsStrictRegex:this._monthsRegex):this._monthsStrictRegex&&a?this._monthsStrictRegex:this._monthsRegex}function ca(){function a(a,b){return b.length-a.length}var b,c,d=[],e=[],f=[];for(b=0;12>b;b++)c=h([2e3,b]),d.push(this.monthsShort(c,"")),e.push(this.months(c,"")),f.push(this.months(c,"")),f.push(this.monthsShort(c,""));for(
// Sorting makes sure if one month (or abbr) is a prefix of another it
// will match the longer piece.
d.sort(a),e.sort(a),f.sort(a),b=0;12>b;b++)d[b]=R(d[b]),e[b]=R(e[b]),f[b]=R(f[b]);this._monthsRegex=new RegExp("^("+f.join("|")+")","i"),this._monthsShortRegex=this._monthsRegex,this._monthsStrictRegex=new RegExp("^("+e.join("|")+")$","i"),this._monthsShortStrictRegex=new RegExp("^("+d.join("|")+")$","i")}function da(a){var b,c=a._a;return c&&-2===j(a).overflow&&(b=c[wd]<0||c[wd]>11?wd:c[xd]<1||c[xd]>V(c[vd],c[wd])?xd:c[yd]<0||c[yd]>24||24===c[yd]&&(0!==c[zd]||0!==c[Ad]||0!==c[Bd])?yd:c[zd]<0||c[zd]>59?zd:c[Ad]<0||c[Ad]>59?Ad:c[Bd]<0||c[Bd]>999?Bd:-1,j(a)._overflowDayOfYear&&(vd>b||b>xd)&&(b=xd),j(a)._overflowWeeks&&-1===b&&(b=Cd),j(a)._overflowWeekday&&-1===b&&(b=Dd),j(a).overflow=b),a}function ea(b){a.suppressDeprecationWarnings===!1&&"undefined"!=typeof console&&console.warn&&console.warn("Deprecation warning: "+b)}function fa(a,b){var c=!0;return g(function(){return c&&(ea(a+"\nArguments: "+Array.prototype.slice.call(arguments).join(", ")+"\n"+(new Error).stack),c=!1),b.apply(this,arguments)},b)}function ga(a,b){Jd[a]||(ea(b),Jd[a]=!0)}
// date from iso format
function ha(a){var b,c,d,e,f,g,h=a._i,i=Kd.exec(h)||Ld.exec(h);if(i){for(j(a).iso=!0,b=0,c=Nd.length;c>b;b++)if(Nd[b][1].exec(i[1])){e=Nd[b][0],d=Nd[b][2]!==!1;break}if(null==e)return void(a._isValid=!1);if(i[3]){for(b=0,c=Od.length;c>b;b++)if(Od[b][1].exec(i[3])){
// match[2] should be 'T' or space
f=(i[2]||" ")+Od[b][0];break}if(null==f)return void(a._isValid=!1)}if(!d&&null!=f)return void(a._isValid=!1);if(i[4]){if(!Md.exec(i[4]))return void(a._isValid=!1);g="Z"}a._f=e+(f||"")+(g||""),wa(a)}else a._isValid=!1}
// date from iso format or fallback
function ia(b){var c=Pd.exec(b._i);return null!==c?void(b._d=new Date(+c[1])):(ha(b),void(b._isValid===!1&&(delete b._isValid,a.createFromInputFallback(b))))}function ja(a,b,c,d,e,f,g){
//can't just apply() to create a date:
//http://stackoverflow.com/questions/181348/instantiating-a-javascript-object-by-calling-prototype-constructor-apply
var h=new Date(a,b,c,d,e,f,g);
//the date constructor remaps years 0-99 to 1900-1999
return 100>a&&a>=0&&isFinite(h.getFullYear())&&h.setFullYear(a),h}function ka(a){var b=new Date(Date.UTC.apply(null,arguments));
//the Date.UTC function remaps years 0-99 to 1900-1999
return 100>a&&a>=0&&isFinite(b.getUTCFullYear())&&b.setUTCFullYear(a),b}
// HELPERS
function la(a){return ma(a)?366:365}function ma(a){return a%4===0&&a%100!==0||a%400===0}function na(){return ma(this.year())}
// start-of-first-week - start-of-year
function oa(a,b,c){var// first-week day -- which january is always in the first week (4 for iso, 1 for other)
d=7+b-c,
// first-week day local weekday -- which local weekday is fwd
e=(7+ka(a,0,d).getUTCDay()-b)%7;return-e+d-1}
//http://en.wikipedia.org/wiki/ISO_week_date#Calculating_a_date_given_the_year.2C_week_number_and_weekday
function pa(a,b,c,d,e){var f,g,h=(7+c-d)%7,i=oa(a,d,e),j=1+7*(b-1)+h+i;return 0>=j?(f=a-1,g=la(f)+j):j>la(a)?(f=a+1,g=j-la(a)):(f=a,g=j),{year:f,dayOfYear:g}}function qa(a,b,c){var d,e,f=oa(a.year(),b,c),g=Math.floor((a.dayOfYear()-f-1)/7)+1;return 1>g?(e=a.year()-1,d=g+ra(e,b,c)):g>ra(a.year(),b,c)?(d=g-ra(a.year(),b,c),e=a.year()+1):(e=a.year(),d=g),{week:d,year:e}}function ra(a,b,c){var d=oa(a,b,c),e=oa(a+1,b,c);return(la(a)-d+e)/7}
// Pick the first defined of two or three arguments.
function sa(a,b,c){return null!=a?a:null!=b?b:c}function ta(b){
// hooks is actually the exported moment object
var c=new Date(a.now());return b._useUTC?[c.getUTCFullYear(),c.getUTCMonth(),c.getUTCDate()]:[c.getFullYear(),c.getMonth(),c.getDate()]}
// convert an array to a date.
// the array should mirror the parameters below
// note: all values past the year are optional and will default to the lowest possible value.
// [year, month, day , hour, minute, second, millisecond]
function ua(a){var b,c,d,e,f=[];if(!a._d){
// Default to current date.
// * if no year, month, day of month are given, default to today
// * if day of month is given, default month and year
// * if month is given, default only year
// * if year is given, don't default anything
for(d=ta(a),a._w&&null==a._a[xd]&&null==a._a[wd]&&va(a),a._dayOfYear&&(e=sa(a._a[vd],d[vd]),a._dayOfYear>la(e)&&(j(a)._overflowDayOfYear=!0),c=ka(e,0,a._dayOfYear),a._a[wd]=c.getUTCMonth(),a._a[xd]=c.getUTCDate()),b=0;3>b&&null==a._a[b];++b)a._a[b]=f[b]=d[b];
// Zero out whatever was not defaulted, including time
for(;7>b;b++)a._a[b]=f[b]=null==a._a[b]?2===b?1:0:a._a[b];
// Check for 24:00:00.000
24===a._a[yd]&&0===a._a[zd]&&0===a._a[Ad]&&0===a._a[Bd]&&(a._nextDay=!0,a._a[yd]=0),a._d=(a._useUTC?ka:ja).apply(null,f),
// Apply timezone offset from input. The actual utcOffset can be changed
// with parseZone.
null!=a._tzm&&a._d.setUTCMinutes(a._d.getUTCMinutes()-a._tzm),a._nextDay&&(a._a[yd]=24)}}function va(a){var b,c,d,e,f,g,h,i;b=a._w,null!=b.GG||null!=b.W||null!=b.E?(f=1,g=4,c=sa(b.GG,a._a[vd],qa(Ea(),1,4).year),d=sa(b.W,1),e=sa(b.E,1),(1>e||e>7)&&(i=!0)):(f=a._locale._week.dow,g=a._locale._week.doy,c=sa(b.gg,a._a[vd],qa(Ea(),f,g).year),d=sa(b.w,1),null!=b.d?(e=b.d,(0>e||e>6)&&(i=!0)):null!=b.e?(e=b.e+f,(b.e<0||b.e>6)&&(i=!0)):e=f),1>d||d>ra(c,f,g)?j(a)._overflowWeeks=!0:null!=i?j(a)._overflowWeekday=!0:(h=pa(c,d,e,f,g),a._a[vd]=h.year,a._dayOfYear=h.dayOfYear)}
// date from string and format string
function wa(b){
// TODO: Move this to another part of the creation flow to prevent circular deps
if(b._f===a.ISO_8601)return void ha(b);b._a=[],j(b).empty=!0;
// This array is used to make a Date, either with `new Date` or `Date.UTC`
var c,d,e,f,g,h=""+b._i,i=h.length,k=0;for(e=N(b._f,b._locale).match($c)||[],c=0;c<e.length;c++)f=e[c],d=(h.match(P(f,b))||[])[0],d&&(g=h.substr(0,h.indexOf(d)),g.length>0&&j(b).unusedInput.push(g),h=h.slice(h.indexOf(d)+d.length),k+=d.length),bd[f]?(d?j(b).empty=!1:j(b).unusedTokens.push(f),U(f,d,b)):b._strict&&!d&&j(b).unusedTokens.push(f);
// add remaining unparsed input length to the string
j(b).charsLeftOver=i-k,h.length>0&&j(b).unusedInput.push(h),
// clear _12h flag if hour is <= 12
j(b).bigHour===!0&&b._a[yd]<=12&&b._a[yd]>0&&(j(b).bigHour=void 0),
// handle meridiem
b._a[yd]=xa(b._locale,b._a[yd],b._meridiem),ua(b),da(b)}function xa(a,b,c){var d;
// Fallback
return null==c?b:null!=a.meridiemHour?a.meridiemHour(b,c):null!=a.isPM?(d=a.isPM(c),d&&12>b&&(b+=12),d||12!==b||(b=0),b):b}
// date from string and array of format strings
function ya(a){var b,c,d,e,f;if(0===a._f.length)return j(a).invalidFormat=!0,void(a._d=new Date(NaN));for(e=0;e<a._f.length;e++)f=0,b=n({},a),null!=a._useUTC&&(b._useUTC=a._useUTC),b._f=a._f[e],wa(b),k(b)&&(f+=j(b).charsLeftOver,f+=10*j(b).unusedTokens.length,j(b).score=f,(null==d||d>f)&&(d=f,c=b));g(a,c||b)}function za(a){if(!a._d){var b=C(a._i);a._a=e([b.year,b.month,b.day||b.date,b.hour,b.minute,b.second,b.millisecond],function(a){return a&&parseInt(a,10)}),ua(a)}}function Aa(a){var b=new o(da(Ba(a)));
// Adding is smart enough around DST
return b._nextDay&&(b.add(1,"d"),b._nextDay=void 0),b}function Ba(a){var b=a._i,e=a._f;return a._locale=a._locale||z(a._l),null===b||void 0===e&&""===b?l({nullInput:!0}):("string"==typeof b&&(a._i=b=a._locale.preparse(b)),p(b)?new o(da(b)):(c(e)?ya(a):e?wa(a):d(b)?a._d=b:Ca(a),k(a)||(a._d=null),a))}function Ca(b){var f=b._i;void 0===f?b._d=new Date(a.now()):d(f)?b._d=new Date(+f):"string"==typeof f?ia(b):c(f)?(b._a=e(f.slice(0),function(a){return parseInt(a,10)}),ua(b)):"object"==typeof f?za(b):"number"==typeof f?
// from milliseconds
b._d=new Date(f):a.createFromInputFallback(b)}function Da(a,b,c,d,e){var f={};
// object construction must be done this way.
// https://github.com/moment/moment/issues/1423
return"boolean"==typeof c&&(d=c,c=void 0),f._isAMomentObject=!0,f._useUTC=f._isUTC=e,f._l=c,f._i=a,f._f=b,f._strict=d,Aa(f)}function Ea(a,b,c,d){return Da(a,b,c,d,!1)}
// Pick a moment m from moments so that m[fn](other) is true for all
// other. This relies on the function fn to be transitive.
//
// moments should either be an array of moment objects or an array, whose
// first element is an array of moment objects.
function Fa(a,b){var d,e;if(1===b.length&&c(b[0])&&(b=b[0]),!b.length)return Ea();for(d=b[0],e=1;e<b.length;++e)b[e].isValid()&&!b[e][a](d)||(d=b[e]);return d}
// TODO: Use [].sort instead?
function Ga(){var a=[].slice.call(arguments,0);return Fa("isBefore",a)}function Ha(){var a=[].slice.call(arguments,0);return Fa("isAfter",a)}function Ia(a){var b=C(a),c=b.year||0,d=b.quarter||0,e=b.month||0,f=b.week||0,g=b.day||0,h=b.hour||0,i=b.minute||0,j=b.second||0,k=b.millisecond||0;
// representation for dateAddRemove
this._milliseconds=+k+1e3*j+// 1000
6e4*i+// 1000 * 60
36e5*h,// 1000 * 60 * 60
// Because of dateAddRemove treats 24 hours as different from a
// day when working around DST, we need to store them separately
this._days=+g+7*f,
// It is impossible translate months into days without knowing
// which months you are are talking about, so we have to store
// it separately.
this._months=+e+3*d+12*c,this._data={},this._locale=z(),this._bubble()}function Ja(a){return a instanceof Ia}
// FORMATTING
function Ka(a,b){J(a,0,0,function(){var a=this.utcOffset(),c="+";return 0>a&&(a=-a,c="-"),c+I(~~(a/60),2)+b+I(~~a%60,2)})}function La(a,b){var c=(b||"").match(a)||[],d=c[c.length-1]||[],e=(d+"").match(Ud)||["-",0,0],f=+(60*e[1])+r(e[2]);return"+"===e[0]?f:-f}
// Return a moment from input, that is local/utc/zone equivalent to model.
function Ma(b,c){var e,f;
// Use low-level api, because this fn is low-level api.
return c._isUTC?(e=c.clone(),f=(p(b)||d(b)?+b:+Ea(b))-+e,e._d.setTime(+e._d+f),a.updateOffset(e,!1),e):Ea(b).local()}function Na(a){
// On Firefox.24 Date#getTimezoneOffset returns a floating point.
// https://github.com/moment/moment/pull/1871
return 15*-Math.round(a._d.getTimezoneOffset()/15)}
// MOMENTS
// keepLocalTime = true means only change the timezone, without
// affecting the local hour. So 5:31:26 +0300 --[utcOffset(2, true)]-->
// 5:31:26 +0200 It is possible that 5:31:26 doesn't exist with offset
// +0200, so we adjust the time as needed, to be valid.
//
// Keeping the time actually adds/subtracts (one hour)
// from the actual represented time. That is why we call updateOffset
// a second time. In case it wants us to change the offset again
// _changeInProgress == true case, then we have to adjust, because
// there is no such time in the given timezone.
function Oa(b,c){var d,e=this._offset||0;return this.isValid()?null!=b?("string"==typeof b?b=La(qd,b):Math.abs(b)<16&&(b=60*b),!this._isUTC&&c&&(d=Na(this)),this._offset=b,this._isUTC=!0,null!=d&&this.add(d,"m"),e!==b&&(!c||this._changeInProgress?cb(this,Za(b-e,"m"),1,!1):this._changeInProgress||(this._changeInProgress=!0,a.updateOffset(this,!0),this._changeInProgress=null)),this):this._isUTC?e:Na(this):null!=b?this:NaN}function Pa(a,b){return null!=a?("string"!=typeof a&&(a=-a),this.utcOffset(a,b),this):-this.utcOffset()}function Qa(a){return this.utcOffset(0,a)}function Ra(a){return this._isUTC&&(this.utcOffset(0,a),this._isUTC=!1,a&&this.subtract(Na(this),"m")),this}function Sa(){return this._tzm?this.utcOffset(this._tzm):"string"==typeof this._i&&this.utcOffset(La(pd,this._i)),this}function Ta(a){return this.isValid()?(a=a?Ea(a).utcOffset():0,(this.utcOffset()-a)%60===0):!1}function Ua(){return this.utcOffset()>this.clone().month(0).utcOffset()||this.utcOffset()>this.clone().month(5).utcOffset()}function Va(){if(!m(this._isDSTShifted))return this._isDSTShifted;var a={};if(n(a,this),a=Ba(a),a._a){var b=a._isUTC?h(a._a):Ea(a._a);this._isDSTShifted=this.isValid()&&s(a._a,b.toArray())>0}else this._isDSTShifted=!1;return this._isDSTShifted}function Wa(){return this.isValid()?!this._isUTC:!1}function Xa(){return this.isValid()?this._isUTC:!1}function Ya(){return this.isValid()?this._isUTC&&0===this._offset:!1}function Za(a,b){var c,d,e,g=a,
// matching against regexp is expensive, do it on demand
h=null;// checks for null or undefined
return Ja(a)?g={ms:a._milliseconds,d:a._days,M:a._months}:"number"==typeof a?(g={},b?g[b]=a:g.milliseconds=a):(h=Vd.exec(a))?(c="-"===h[1]?-1:1,g={y:0,d:r(h[xd])*c,h:r(h[yd])*c,m:r(h[zd])*c,s:r(h[Ad])*c,ms:r(h[Bd])*c}):(h=Wd.exec(a))?(c="-"===h[1]?-1:1,g={y:$a(h[2],c),M:$a(h[3],c),d:$a(h[4],c),h:$a(h[5],c),m:$a(h[6],c),s:$a(h[7],c),w:$a(h[8],c)}):null==g?g={}:"object"==typeof g&&("from"in g||"to"in g)&&(e=ab(Ea(g.from),Ea(g.to)),g={},g.ms=e.milliseconds,g.M=e.months),d=new Ia(g),Ja(a)&&f(a,"_locale")&&(d._locale=a._locale),d}function $a(a,b){
// We'd normally use ~~inp for this, but unfortunately it also
// converts floats to ints.
// inp may be undefined, so careful calling replace on it.
var c=a&&parseFloat(a.replace(",","."));
// apply sign while we're at it
return(isNaN(c)?0:c)*b}function _a(a,b){var c={milliseconds:0,months:0};return c.months=b.month()-a.month()+12*(b.year()-a.year()),a.clone().add(c.months,"M").isAfter(b)&&--c.months,c.milliseconds=+b-+a.clone().add(c.months,"M"),c}function ab(a,b){var c;return a.isValid()&&b.isValid()?(b=Ma(b,a),a.isBefore(b)?c=_a(a,b):(c=_a(b,a),c.milliseconds=-c.milliseconds,c.months=-c.months),c):{milliseconds:0,months:0}}
// TODO: remove 'name' arg after deprecation is removed
function bb(a,b){return function(c,d){var e,f;
//invert the arguments, but complain about it
return null===d||isNaN(+d)||(ga(b,"moment()."+b+"(period, number) is deprecated. Please use moment()."+b+"(number, period)."),f=c,c=d,d=f),c="string"==typeof c?+c:c,e=Za(c,d),cb(this,e,a),this}}function cb(b,c,d,e){var f=c._milliseconds,g=c._days,h=c._months;b.isValid()&&(e=null==e?!0:e,f&&b._d.setTime(+b._d+f*d),g&&G(b,"Date",F(b,"Date")+g*d),h&&Z(b,F(b,"Month")+h*d),e&&a.updateOffset(b,g||h))}function db(a,b){
// We want to compare the start of today, vs this.
// Getting start-of-today depends on whether we're local/utc/offset or not.
var c=a||Ea(),d=Ma(c,this).startOf("day"),e=this.diff(d,"days",!0),f=-6>e?"sameElse":-1>e?"lastWeek":0>e?"lastDay":1>e?"sameDay":2>e?"nextDay":7>e?"nextWeek":"sameElse",g=b&&(D(b[f])?b[f]():b[f]);return this.format(g||this.localeData().calendar(f,this,Ea(c)))}function eb(){return new o(this)}function fb(a,b){var c=p(a)?a:Ea(a);return this.isValid()&&c.isValid()?(b=B(m(b)?"millisecond":b),"millisecond"===b?+this>+c:+c<+this.clone().startOf(b)):!1}function gb(a,b){var c=p(a)?a:Ea(a);return this.isValid()&&c.isValid()?(b=B(m(b)?"millisecond":b),"millisecond"===b?+c>+this:+this.clone().endOf(b)<+c):!1}function hb(a,b,c){return this.isAfter(a,c)&&this.isBefore(b,c)}function ib(a,b){var c,d=p(a)?a:Ea(a);return this.isValid()&&d.isValid()?(b=B(b||"millisecond"),"millisecond"===b?+this===+d:(c=+d,+this.clone().startOf(b)<=c&&c<=+this.clone().endOf(b))):!1}function jb(a,b){return this.isSame(a,b)||this.isAfter(a,b)}function kb(a,b){return this.isSame(a,b)||this.isBefore(a,b)}function lb(a,b,c){var d,e,f,g;// 1000
// 1000 * 60
// 1000 * 60 * 60
// 1000 * 60 * 60 * 24, negate dst
// 1000 * 60 * 60 * 24 * 7, negate dst
return this.isValid()?(d=Ma(a,this),d.isValid()?(e=6e4*(d.utcOffset()-this.utcOffset()),b=B(b),"year"===b||"month"===b||"quarter"===b?(g=mb(this,d),"quarter"===b?g/=3:"year"===b&&(g/=12)):(f=this-d,g="second"===b?f/1e3:"minute"===b?f/6e4:"hour"===b?f/36e5:"day"===b?(f-e)/864e5:"week"===b?(f-e)/6048e5:f),c?g:q(g)):NaN):NaN}function mb(a,b){
// difference in months
var c,d,e=12*(b.year()-a.year())+(b.month()-a.month()),
// b is in (anchor - 1 month, anchor + 1 month)
f=a.clone().add(e,"months");
// linear across the month
// linear across the month
return 0>b-f?(c=a.clone().add(e-1,"months"),d=(b-f)/(f-c)):(c=a.clone().add(e+1,"months"),d=(b-f)/(c-f)),-(e+d)}function nb(){return this.clone().locale("en").format("ddd MMM DD YYYY HH:mm:ss [GMT]ZZ")}function ob(){var a=this.clone().utc();return 0<a.year()&&a.year()<=9999?D(Date.prototype.toISOString)?this.toDate().toISOString():M(a,"YYYY-MM-DD[T]HH:mm:ss.SSS[Z]"):M(a,"YYYYYY-MM-DD[T]HH:mm:ss.SSS[Z]")}function pb(b){var c=M(this,b||a.defaultFormat);return this.localeData().postformat(c)}function qb(a,b){return this.isValid()&&(p(a)&&a.isValid()||Ea(a).isValid())?Za({to:this,from:a}).locale(this.locale()).humanize(!b):this.localeData().invalidDate()}function rb(a){return this.from(Ea(),a)}function sb(a,b){return this.isValid()&&(p(a)&&a.isValid()||Ea(a).isValid())?Za({from:this,to:a}).locale(this.locale()).humanize(!b):this.localeData().invalidDate()}function tb(a){return this.to(Ea(),a)}
// If passed a locale key, it will set the locale for this
// instance.  Otherwise, it will return the locale configuration
// variables for this instance.
function ub(a){var b;return void 0===a?this._locale._abbr:(b=z(a),null!=b&&(this._locale=b),this)}function vb(){return this._locale}function wb(a){
// the following switch intentionally omits break keywords
// to utilize falling through the cases.
switch(a=B(a)){case"year":this.month(0);/* falls through */
case"quarter":case"month":this.date(1);/* falls through */
case"week":case"isoWeek":case"day":this.hours(0);/* falls through */
case"hour":this.minutes(0);/* falls through */
case"minute":this.seconds(0);/* falls through */
case"second":this.milliseconds(0)}
// weeks are a special case
// quarters are also special
return"week"===a&&this.weekday(0),"isoWeek"===a&&this.isoWeekday(1),"quarter"===a&&this.month(3*Math.floor(this.month()/3)),this}function xb(a){return a=B(a),void 0===a||"millisecond"===a?this:this.startOf(a).add(1,"isoWeek"===a?"week":a).subtract(1,"ms")}function yb(){return+this._d-6e4*(this._offset||0)}function zb(){return Math.floor(+this/1e3)}function Ab(){return this._offset?new Date(+this):this._d}function Bb(){var a=this;return[a.year(),a.month(),a.date(),a.hour(),a.minute(),a.second(),a.millisecond()]}function Cb(){var a=this;return{years:a.year(),months:a.month(),date:a.date(),hours:a.hours(),minutes:a.minutes(),seconds:a.seconds(),milliseconds:a.milliseconds()}}function Db(){
// JSON.stringify(new Date(NaN)) === 'null'
return this.isValid()?this.toISOString():"null"}function Eb(){return k(this)}function Fb(){return g({},j(this))}function Gb(){return j(this).overflow}function Hb(){return{input:this._i,format:this._f,locale:this._locale,isUTC:this._isUTC,strict:this._strict}}function Ib(a,b){J(0,[a,a.length],0,b)}
// MOMENTS
function Jb(a){return Nb.call(this,a,this.week(),this.weekday(),this.localeData()._week.dow,this.localeData()._week.doy)}function Kb(a){return Nb.call(this,a,this.isoWeek(),this.isoWeekday(),1,4)}function Lb(){return ra(this.year(),1,4)}function Mb(){var a=this.localeData()._week;return ra(this.year(),a.dow,a.doy)}function Nb(a,b,c,d,e){var f;return null==a?qa(this,d,e).year:(f=ra(a,d,e),b>f&&(b=f),Ob.call(this,a,b,c,d,e))}function Ob(a,b,c,d,e){var f=pa(a,b,c,d,e),g=ka(f.year,0,f.dayOfYear);
// console.log("got", weekYear, week, weekday, "set", date.toISOString());
return this.year(g.getUTCFullYear()),this.month(g.getUTCMonth()),this.date(g.getUTCDate()),this}
// MOMENTS
function Pb(a){return null==a?Math.ceil((this.month()+1)/3):this.month(3*(a-1)+this.month()%3)}
// HELPERS
// LOCALES
function Qb(a){return qa(a,this._week.dow,this._week.doy).week}function Rb(){return this._week.dow}function Sb(){return this._week.doy}
// MOMENTS
function Tb(a){var b=this.localeData().week(this);return null==a?b:this.add(7*(a-b),"d")}function Ub(a){var b=qa(this,1,4).week;return null==a?b:this.add(7*(a-b),"d")}
// HELPERS
function Vb(a,b){return"string"!=typeof a?a:isNaN(a)?(a=b.weekdaysParse(a),"number"==typeof a?a:null):parseInt(a,10)}function Wb(a,b){return c(this._weekdays)?this._weekdays[a.day()]:this._weekdays[this._weekdays.isFormat.test(b)?"format":"standalone"][a.day()]}function Xb(a){return this._weekdaysShort[a.day()]}function Yb(a){return this._weekdaysMin[a.day()]}function Zb(a,b,c){var d,e,f;for(this._weekdaysParse||(this._weekdaysParse=[],this._minWeekdaysParse=[],this._shortWeekdaysParse=[],this._fullWeekdaysParse=[]),d=0;7>d;d++){
// test the regex
if(e=Ea([2e3,1]).day(d),c&&!this._fullWeekdaysParse[d]&&(this._fullWeekdaysParse[d]=new RegExp("^"+this.weekdays(e,"").replace(".",".?")+"$","i"),this._shortWeekdaysParse[d]=new RegExp("^"+this.weekdaysShort(e,"").replace(".",".?")+"$","i"),this._minWeekdaysParse[d]=new RegExp("^"+this.weekdaysMin(e,"").replace(".",".?")+"$","i")),this._weekdaysParse[d]||(f="^"+this.weekdays(e,"")+"|^"+this.weekdaysShort(e,"")+"|^"+this.weekdaysMin(e,""),this._weekdaysParse[d]=new RegExp(f.replace(".",""),"i")),c&&"dddd"===b&&this._fullWeekdaysParse[d].test(a))return d;if(c&&"ddd"===b&&this._shortWeekdaysParse[d].test(a))return d;if(c&&"dd"===b&&this._minWeekdaysParse[d].test(a))return d;if(!c&&this._weekdaysParse[d].test(a))return d}}
// MOMENTS
function $b(a){if(!this.isValid())return null!=a?this:NaN;var b=this._isUTC?this._d.getUTCDay():this._d.getDay();return null!=a?(a=Vb(a,this.localeData()),this.add(a-b,"d")):b}function _b(a){if(!this.isValid())return null!=a?this:NaN;var b=(this.day()+7-this.localeData()._week.dow)%7;return null==a?b:this.add(a-b,"d")}function ac(a){return this.isValid()?null==a?this.day()||7:this.day(this.day()%7?a:a-7):null!=a?this:NaN}
// HELPERS
// MOMENTS
function bc(a){var b=Math.round((this.clone().startOf("day")-this.clone().startOf("year"))/864e5)+1;return null==a?b:this.add(a-b,"d")}
// FORMATTING
function cc(){return this.hours()%12||12}function dc(a,b){J(a,0,0,function(){return this.localeData().meridiem(this.hours(),this.minutes(),b)})}
// PARSING
function ec(a,b){return b._meridiemParse}
// LOCALES
function fc(a){
// IE8 Quirks Mode & IE7 Standards Mode do not allow accessing strings like arrays
// Using charAt should be more compatible.
return"p"===(a+"").toLowerCase().charAt(0)}function gc(a,b,c){return a>11?c?"pm":"PM":c?"am":"AM"}function hc(a,b){b[Bd]=r(1e3*("0."+a))}
// MOMENTS
function ic(){return this._isUTC?"UTC":""}function jc(){return this._isUTC?"Coordinated Universal Time":""}function kc(a){return Ea(1e3*a)}function lc(){return Ea.apply(null,arguments).parseZone()}function mc(a,b,c){var d=this._calendar[a];return D(d)?d.call(b,c):d}function nc(a){var b=this._longDateFormat[a],c=this._longDateFormat[a.toUpperCase()];return b||!c?b:(this._longDateFormat[a]=c.replace(/MMMM|MM|DD|dddd/g,function(a){return a.slice(1)}),this._longDateFormat[a])}function oc(){return this._invalidDate}function pc(a){return this._ordinal.replace("%d",a)}function qc(a){return a}function rc(a,b,c,d){var e=this._relativeTime[c];return D(e)?e(a,b,c,d):e.replace(/%d/i,a)}function sc(a,b){var c=this._relativeTime[a>0?"future":"past"];return D(c)?c(b):c.replace(/%s/i,b)}function tc(a){var b,c;for(c in a)b=a[c],D(b)?this[c]=b:this["_"+c]=b;
// Lenient ordinal parsing accepts just a number in addition to
// number + (possibly) stuff coming from _ordinalParseLenient.
this._ordinalParseLenient=new RegExp(this._ordinalParse.source+"|"+/\d{1,2}/.source)}function uc(a,b,c,d){var e=z(),f=h().set(d,b);return e[c](f,a)}function vc(a,b,c,d,e){if("number"==typeof a&&(b=a,a=void 0),a=a||"",null!=b)return uc(a,b,c,e);var f,g=[];for(f=0;d>f;f++)g[f]=uc(a,f,c,e);return g}function wc(a,b){return vc(a,b,"months",12,"month")}function xc(a,b){return vc(a,b,"monthsShort",12,"month")}function yc(a,b){return vc(a,b,"weekdays",7,"day")}function zc(a,b){return vc(a,b,"weekdaysShort",7,"day")}function Ac(a,b){return vc(a,b,"weekdaysMin",7,"day")}function Bc(){var a=this._data;return this._milliseconds=se(this._milliseconds),this._days=se(this._days),this._months=se(this._months),a.milliseconds=se(a.milliseconds),a.seconds=se(a.seconds),a.minutes=se(a.minutes),a.hours=se(a.hours),a.months=se(a.months),a.years=se(a.years),this}function Cc(a,b,c,d){var e=Za(b,c);return a._milliseconds+=d*e._milliseconds,a._days+=d*e._days,a._months+=d*e._months,a._bubble()}
// supports only 2.0-style add(1, 's') or add(duration)
function Dc(a,b){return Cc(this,a,b,1)}
// supports only 2.0-style subtract(1, 's') or subtract(duration)
function Ec(a,b){return Cc(this,a,b,-1)}function Fc(a){return 0>a?Math.floor(a):Math.ceil(a)}function Gc(){var a,b,c,d,e,f=this._milliseconds,g=this._days,h=this._months,i=this._data;
// if we have a mix of positive and negative values, bubble down first
// check: https://github.com/moment/moment/issues/2166
// The following code bubbles up values, see the tests for
// examples of what that means.
// convert days to months
// 12 months -> 1 year
return f>=0&&g>=0&&h>=0||0>=f&&0>=g&&0>=h||(f+=864e5*Fc(Ic(h)+g),g=0,h=0),i.milliseconds=f%1e3,a=q(f/1e3),i.seconds=a%60,b=q(a/60),i.minutes=b%60,c=q(b/60),i.hours=c%24,g+=q(c/24),e=q(Hc(g)),h+=e,g-=Fc(Ic(e)),d=q(h/12),h%=12,i.days=g,i.months=h,i.years=d,this}function Hc(a){
// 400 years have 146097 days (taking into account leap year rules)
// 400 years have 12 months === 4800
return 4800*a/146097}function Ic(a){
// the reverse of daysToMonths
return 146097*a/4800}function Jc(a){var b,c,d=this._milliseconds;if(a=B(a),"month"===a||"year"===a)return b=this._days+d/864e5,c=this._months+Hc(b),"month"===a?c:c/12;switch(b=this._days+Math.round(Ic(this._months)),a){case"week":return b/7+d/6048e5;case"day":return b+d/864e5;case"hour":return 24*b+d/36e5;case"minute":return 1440*b+d/6e4;case"second":return 86400*b+d/1e3;
// Math.floor prevents floating point math errors here
case"millisecond":return Math.floor(864e5*b)+d;default:throw new Error("Unknown unit "+a)}}
// TODO: Use this.as('ms')?
function Kc(){return this._milliseconds+864e5*this._days+this._months%12*2592e6+31536e6*r(this._months/12)}function Lc(a){return function(){return this.as(a)}}function Mc(a){return a=B(a),this[a+"s"]()}function Nc(a){return function(){return this._data[a]}}function Oc(){return q(this.days()/7)}
// helper function for moment.fn.from, moment.fn.fromNow, and moment.duration.fn.humanize
function Pc(a,b,c,d,e){return e.relativeTime(b||1,!!c,a,d)}function Qc(a,b,c){var d=Za(a).abs(),e=Ie(d.as("s")),f=Ie(d.as("m")),g=Ie(d.as("h")),h=Ie(d.as("d")),i=Ie(d.as("M")),j=Ie(d.as("y")),k=e<Je.s&&["s",e]||1>=f&&["m"]||f<Je.m&&["mm",f]||1>=g&&["h"]||g<Je.h&&["hh",g]||1>=h&&["d"]||h<Je.d&&["dd",h]||1>=i&&["M"]||i<Je.M&&["MM",i]||1>=j&&["y"]||["yy",j];return k[2]=b,k[3]=+a>0,k[4]=c,Pc.apply(null,k)}
// This function allows you to set a threshold for relative time strings
function Rc(a,b){return void 0===Je[a]?!1:void 0===b?Je[a]:(Je[a]=b,!0)}function Sc(a){var b=this.localeData(),c=Qc(this,!a,b);return a&&(c=b.pastFuture(+this,c)),b.postformat(c)}function Tc(){
// for ISO strings we do not use the normal bubbling rules:
//  * milliseconds bubble up until they become hours
//  * days do not bubble at all
//  * months bubble up until they become years
// This is because there is no context-free conversion between hours and days
// (think of clock changes)
// and also not between days and months (28-31 days per month)
var a,b,c,d=Ke(this._milliseconds)/1e3,e=Ke(this._days),f=Ke(this._months);a=q(d/60),b=q(a/60),d%=60,a%=60,c=q(f/12),f%=12;
// inspired by https://github.com/dordille/moment-isoduration/blob/master/moment.isoduration.js
var g=c,h=f,i=e,j=b,k=a,l=d,m=this.asSeconds();return m?(0>m?"-":"")+"P"+(g?g+"Y":"")+(h?h+"M":"")+(i?i+"D":"")+(j||k||l?"T":"")+(j?j+"H":"")+(k?k+"M":"")+(l?l+"S":""):"P0D"}var Uc,Vc,Wc=a.momentProperties=[],Xc=!1,Yc={},Zc={},$c=/(\[[^\[]*\])|(\\)?([Hh]mm(ss)?|Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|Qo?|YYYYYY|YYYYY|YYYY|YY|gg(ggg?)?|GG(GGG?)?|e|E|a|A|hh?|HH?|mm?|ss?|S{1,9}|x|X|zz?|ZZ?|.)/g,_c=/(\[[^\[]*\])|(\\)?(LTS|LT|LL?L?L?|l{1,4})/g,ad={},bd={},cd=/\d/,dd=/\d\d/,ed=/\d{3}/,fd=/\d{4}/,gd=/[+-]?\d{6}/,hd=/\d\d?/,id=/\d\d\d\d?/,jd=/\d\d\d\d\d\d?/,kd=/\d{1,3}/,ld=/\d{1,4}/,md=/[+-]?\d{1,6}/,nd=/\d+/,od=/[+-]?\d+/,pd=/Z|[+-]\d\d:?\d\d/gi,qd=/Z|[+-]\d\d(?::?\d\d)?/gi,rd=/[+-]?\d+(\.\d{1,3})?/,sd=/[0-9]*['a-z\u00A0-\u05FF\u0700-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+|[\u0600-\u06FF\/]+(\s*?[\u0600-\u06FF]+){1,2}/i,td={},ud={},vd=0,wd=1,xd=2,yd=3,zd=4,Ad=5,Bd=6,Cd=7,Dd=8;
// FORMATTING
J("M",["MM",2],"Mo",function(){return this.month()+1}),J("MMM",0,0,function(a){return this.localeData().monthsShort(this,a)}),J("MMMM",0,0,function(a){return this.localeData().months(this,a)}),
// ALIASES
A("month","M"),
// PARSING
O("M",hd),O("MM",hd,dd),O("MMM",function(a,b){return b.monthsShortRegex(a)}),O("MMMM",function(a,b){return b.monthsRegex(a)}),S(["M","MM"],function(a,b){b[wd]=r(a)-1}),S(["MMM","MMMM"],function(a,b,c,d){var e=c._locale.monthsParse(a,d,c._strict);
// if we didn't find a month name, mark the date as invalid.
null!=e?b[wd]=e:j(c).invalidMonth=a});
// LOCALES
var Ed=/D[oD]?(\[[^\[\]]*\]|\s+)+MMMM?/,Fd="January_February_March_April_May_June_July_August_September_October_November_December".split("_"),Gd="Jan_Feb_Mar_Apr_May_Jun_Jul_Aug_Sep_Oct_Nov_Dec".split("_"),Hd=sd,Id=sd,Jd={};a.suppressDeprecationWarnings=!1;
// iso 8601 regex
// 0000-00-00 0000-W00 or 0000-W00-0 + T + 00 or 00:00 or 00:00:00 or 00:00:00.000 + +00:00 or +0000 or +00)
var Kd=/^\s*((?:[+-]\d{6}|\d{4})-(?:\d\d-\d\d|W\d\d-\d|W\d\d|\d\d\d|\d\d))(?:(T| )(\d\d(?::\d\d(?::\d\d(?:[.,]\d+)?)?)?)([\+\-]\d\d(?::?\d\d)?|\s*Z)?)?/,Ld=/^\s*((?:[+-]\d{6}|\d{4})(?:\d\d\d\d|W\d\d\d|W\d\d|\d\d\d|\d\d))(?:(T| )(\d\d(?:\d\d(?:\d\d(?:[.,]\d+)?)?)?)([\+\-]\d\d(?::?\d\d)?|\s*Z)?)?/,Md=/Z|[+-]\d\d(?::?\d\d)?/,Nd=[["YYYYYY-MM-DD",/[+-]\d{6}-\d\d-\d\d/],["YYYY-MM-DD",/\d{4}-\d\d-\d\d/],["GGGG-[W]WW-E",/\d{4}-W\d\d-\d/],["GGGG-[W]WW",/\d{4}-W\d\d/,!1],["YYYY-DDD",/\d{4}-\d{3}/],["YYYY-MM",/\d{4}-\d\d/,!1],["YYYYYYMMDD",/[+-]\d{10}/],["YYYYMMDD",/\d{8}/],
// YYYYMM is NOT allowed by the standard
["GGGG[W]WWE",/\d{4}W\d{3}/],["GGGG[W]WW",/\d{4}W\d{2}/,!1],["YYYYDDD",/\d{7}/]],Od=[["HH:mm:ss.SSSS",/\d\d:\d\d:\d\d\.\d+/],["HH:mm:ss,SSSS",/\d\d:\d\d:\d\d,\d+/],["HH:mm:ss",/\d\d:\d\d:\d\d/],["HH:mm",/\d\d:\d\d/],["HHmmss.SSSS",/\d\d\d\d\d\d\.\d+/],["HHmmss,SSSS",/\d\d\d\d\d\d,\d+/],["HHmmss",/\d\d\d\d\d\d/],["HHmm",/\d\d\d\d/],["HH",/\d\d/]],Pd=/^\/?Date\((\-?\d+)/i;a.createFromInputFallback=fa("moment construction falls back to js Date. This is discouraged and will be removed in upcoming major release. Please refer to https://github.com/moment/moment/issues/1407 for more info.",function(a){a._d=new Date(a._i+(a._useUTC?" UTC":""))}),
// FORMATTING
J("Y",0,0,function(){var a=this.year();return 9999>=a?""+a:"+"+a}),J(0,["YY",2],0,function(){return this.year()%100}),J(0,["YYYY",4],0,"year"),J(0,["YYYYY",5],0,"year"),J(0,["YYYYYY",6,!0],0,"year"),
// ALIASES
A("year","y"),
// PARSING
O("Y",od),O("YY",hd,dd),O("YYYY",ld,fd),O("YYYYY",md,gd),O("YYYYYY",md,gd),S(["YYYYY","YYYYYY"],vd),S("YYYY",function(b,c){c[vd]=2===b.length?a.parseTwoDigitYear(b):r(b)}),S("YY",function(b,c){c[vd]=a.parseTwoDigitYear(b)}),S("Y",function(a,b){b[vd]=parseInt(a,10)}),
// HOOKS
a.parseTwoDigitYear=function(a){return r(a)+(r(a)>68?1900:2e3)};
// MOMENTS
var Qd=E("FullYear",!1);
// constant that refers to the ISO standard
a.ISO_8601=function(){};var Rd=fa("moment().min is deprecated, use moment.min instead. https://github.com/moment/moment/issues/1548",function(){var a=Ea.apply(null,arguments);return this.isValid()&&a.isValid()?this>a?this:a:l()}),Sd=fa("moment().max is deprecated, use moment.max instead. https://github.com/moment/moment/issues/1548",function(){var a=Ea.apply(null,arguments);return this.isValid()&&a.isValid()?a>this?this:a:l()}),Td=function(){return Date.now?Date.now():+new Date};Ka("Z",":"),Ka("ZZ",""),
// PARSING
O("Z",qd),O("ZZ",qd),S(["Z","ZZ"],function(a,b,c){c._useUTC=!0,c._tzm=La(qd,a)});
// HELPERS
// timezone chunker
// '+10:00' > ['10',  '00']
// '-1530'  > ['-15', '30']
var Ud=/([\+\-]|\d\d)/gi;
// HOOKS
// This function will be called whenever a moment is mutated.
// It is intended to keep the offset in sync with the timezone.
a.updateOffset=function(){};
// ASP.NET json date format regex
var Vd=/^(\-)?(?:(\d*)[. ])?(\d+)\:(\d+)(?:\:(\d+)\.?(\d{3})?\d*)?$/,Wd=/^(-)?P(?:(?:([0-9,.]*)Y)?(?:([0-9,.]*)M)?(?:([0-9,.]*)D)?(?:T(?:([0-9,.]*)H)?(?:([0-9,.]*)M)?(?:([0-9,.]*)S)?)?|([0-9,.]*)W)$/;Za.fn=Ia.prototype;var Xd=bb(1,"add"),Yd=bb(-1,"subtract");a.defaultFormat="YYYY-MM-DDTHH:mm:ssZ";var Zd=fa("moment().lang() is deprecated. Instead, use moment().localeData() to get the language configuration. Use moment().locale() to change languages.",function(a){return void 0===a?this.localeData():this.locale(a)});
// FORMATTING
J(0,["gg",2],0,function(){return this.weekYear()%100}),J(0,["GG",2],0,function(){return this.isoWeekYear()%100}),Ib("gggg","weekYear"),Ib("ggggg","weekYear"),Ib("GGGG","isoWeekYear"),Ib("GGGGG","isoWeekYear"),
// ALIASES
A("weekYear","gg"),A("isoWeekYear","GG"),
// PARSING
O("G",od),O("g",od),O("GG",hd,dd),O("gg",hd,dd),O("GGGG",ld,fd),O("gggg",ld,fd),O("GGGGG",md,gd),O("ggggg",md,gd),T(["gggg","ggggg","GGGG","GGGGG"],function(a,b,c,d){b[d.substr(0,2)]=r(a)}),T(["gg","GG"],function(b,c,d,e){c[e]=a.parseTwoDigitYear(b)}),
// FORMATTING
J("Q",0,"Qo","quarter"),
// ALIASES
A("quarter","Q"),
// PARSING
O("Q",cd),S("Q",function(a,b){b[wd]=3*(r(a)-1)}),
// FORMATTING
J("w",["ww",2],"wo","week"),J("W",["WW",2],"Wo","isoWeek"),
// ALIASES
A("week","w"),A("isoWeek","W"),
// PARSING
O("w",hd),O("ww",hd,dd),O("W",hd),O("WW",hd,dd),T(["w","ww","W","WW"],function(a,b,c,d){b[d.substr(0,1)]=r(a)});var $d={dow:0,// Sunday is the first day of the week.
doy:6};
// FORMATTING
J("D",["DD",2],"Do","date"),
// ALIASES
A("date","D"),
// PARSING
O("D",hd),O("DD",hd,dd),O("Do",function(a,b){return a?b._ordinalParse:b._ordinalParseLenient}),S(["D","DD"],xd),S("Do",function(a,b){b[xd]=r(a.match(hd)[0],10)});
// MOMENTS
var _d=E("Date",!0);
// FORMATTING
J("d",0,"do","day"),J("dd",0,0,function(a){return this.localeData().weekdaysMin(this,a)}),J("ddd",0,0,function(a){return this.localeData().weekdaysShort(this,a)}),J("dddd",0,0,function(a){return this.localeData().weekdays(this,a)}),J("e",0,0,"weekday"),J("E",0,0,"isoWeekday"),
// ALIASES
A("day","d"),A("weekday","e"),A("isoWeekday","E"),
// PARSING
O("d",hd),O("e",hd),O("E",hd),O("dd",sd),O("ddd",sd),O("dddd",sd),T(["dd","ddd","dddd"],function(a,b,c,d){var e=c._locale.weekdaysParse(a,d,c._strict);
// if we didn't get a weekday name, mark the date as invalid
null!=e?b.d=e:j(c).invalidWeekday=a}),T(["d","e","E"],function(a,b,c,d){b[d]=r(a)});
// LOCALES
var ae="Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),be="Sun_Mon_Tue_Wed_Thu_Fri_Sat".split("_"),ce="Su_Mo_Tu_We_Th_Fr_Sa".split("_");
// FORMATTING
J("DDD",["DDDD",3],"DDDo","dayOfYear"),
// ALIASES
A("dayOfYear","DDD"),
// PARSING
O("DDD",kd),O("DDDD",ed),S(["DDD","DDDD"],function(a,b,c){c._dayOfYear=r(a)}),J("H",["HH",2],0,"hour"),J("h",["hh",2],0,cc),J("hmm",0,0,function(){return""+cc.apply(this)+I(this.minutes(),2)}),J("hmmss",0,0,function(){return""+cc.apply(this)+I(this.minutes(),2)+I(this.seconds(),2)}),J("Hmm",0,0,function(){return""+this.hours()+I(this.minutes(),2)}),J("Hmmss",0,0,function(){return""+this.hours()+I(this.minutes(),2)+I(this.seconds(),2)}),dc("a",!0),dc("A",!1),
// ALIASES
A("hour","h"),O("a",ec),O("A",ec),O("H",hd),O("h",hd),O("HH",hd,dd),O("hh",hd,dd),O("hmm",id),O("hmmss",jd),O("Hmm",id),O("Hmmss",jd),S(["H","HH"],yd),S(["a","A"],function(a,b,c){c._isPm=c._locale.isPM(a),c._meridiem=a}),S(["h","hh"],function(a,b,c){b[yd]=r(a),j(c).bigHour=!0}),S("hmm",function(a,b,c){var d=a.length-2;b[yd]=r(a.substr(0,d)),b[zd]=r(a.substr(d)),j(c).bigHour=!0}),S("hmmss",function(a,b,c){var d=a.length-4,e=a.length-2;b[yd]=r(a.substr(0,d)),b[zd]=r(a.substr(d,2)),b[Ad]=r(a.substr(e)),j(c).bigHour=!0}),S("Hmm",function(a,b,c){var d=a.length-2;b[yd]=r(a.substr(0,d)),b[zd]=r(a.substr(d))}),S("Hmmss",function(a,b,c){var d=a.length-4,e=a.length-2;b[yd]=r(a.substr(0,d)),b[zd]=r(a.substr(d,2)),b[Ad]=r(a.substr(e))});var de=/[ap]\.?m?\.?/i,ee=E("Hours",!0);
// FORMATTING
J("m",["mm",2],0,"minute"),
// ALIASES
A("minute","m"),
// PARSING
O("m",hd),O("mm",hd,dd),S(["m","mm"],zd);
// MOMENTS
var fe=E("Minutes",!1);
// FORMATTING
J("s",["ss",2],0,"second"),
// ALIASES
A("second","s"),
// PARSING
O("s",hd),O("ss",hd,dd),S(["s","ss"],Ad);
// MOMENTS
var ge=E("Seconds",!1);
// FORMATTING
J("S",0,0,function(){return~~(this.millisecond()/100)}),J(0,["SS",2],0,function(){return~~(this.millisecond()/10)}),J(0,["SSS",3],0,"millisecond"),J(0,["SSSS",4],0,function(){return 10*this.millisecond()}),J(0,["SSSSS",5],0,function(){return 100*this.millisecond()}),J(0,["SSSSSS",6],0,function(){return 1e3*this.millisecond()}),J(0,["SSSSSSS",7],0,function(){return 1e4*this.millisecond()}),J(0,["SSSSSSSS",8],0,function(){return 1e5*this.millisecond()}),J(0,["SSSSSSSSS",9],0,function(){return 1e6*this.millisecond()}),
// ALIASES
A("millisecond","ms"),
// PARSING
O("S",kd,cd),O("SS",kd,dd),O("SSS",kd,ed);var he;for(he="SSSS";he.length<=9;he+="S")O(he,nd);for(he="S";he.length<=9;he+="S")S(he,hc);
// MOMENTS
var ie=E("Milliseconds",!1);
// FORMATTING
J("z",0,0,"zoneAbbr"),J("zz",0,0,"zoneName");var je=o.prototype;je.add=Xd,je.calendar=db,je.clone=eb,je.diff=lb,je.endOf=xb,je.format=pb,je.from=qb,je.fromNow=rb,je.to=sb,je.toNow=tb,je.get=H,je.invalidAt=Gb,je.isAfter=fb,je.isBefore=gb,je.isBetween=hb,je.isSame=ib,je.isSameOrAfter=jb,je.isSameOrBefore=kb,je.isValid=Eb,je.lang=Zd,je.locale=ub,je.localeData=vb,je.max=Sd,je.min=Rd,je.parsingFlags=Fb,je.set=H,je.startOf=wb,je.subtract=Yd,je.toArray=Bb,je.toObject=Cb,je.toDate=Ab,je.toISOString=ob,je.toJSON=Db,je.toString=nb,je.unix=zb,je.valueOf=yb,je.creationData=Hb,
// Year
je.year=Qd,je.isLeapYear=na,
// Week Year
je.weekYear=Jb,je.isoWeekYear=Kb,
// Quarter
je.quarter=je.quarters=Pb,
// Month
je.month=$,je.daysInMonth=_,
// Week
je.week=je.weeks=Tb,je.isoWeek=je.isoWeeks=Ub,je.weeksInYear=Mb,je.isoWeeksInYear=Lb,
// Day
je.date=_d,je.day=je.days=$b,je.weekday=_b,je.isoWeekday=ac,je.dayOfYear=bc,
// Hour
je.hour=je.hours=ee,
// Minute
je.minute=je.minutes=fe,
// Second
je.second=je.seconds=ge,
// Millisecond
je.millisecond=je.milliseconds=ie,
// Offset
je.utcOffset=Oa,je.utc=Qa,je.local=Ra,je.parseZone=Sa,je.hasAlignedHourOffset=Ta,je.isDST=Ua,je.isDSTShifted=Va,je.isLocal=Wa,je.isUtcOffset=Xa,je.isUtc=Ya,je.isUTC=Ya,
// Timezone
je.zoneAbbr=ic,je.zoneName=jc,
// Deprecations
je.dates=fa("dates accessor is deprecated. Use date instead.",_d),je.months=fa("months accessor is deprecated. Use month instead",$),je.years=fa("years accessor is deprecated. Use year instead",Qd),je.zone=fa("moment().zone is deprecated, use moment().utcOffset instead. https://github.com/moment/moment/issues/1779",Pa);var ke=je,le={sameDay:"[Today at] LT",nextDay:"[Tomorrow at] LT",nextWeek:"dddd [at] LT",lastDay:"[Yesterday at] LT",lastWeek:"[Last] dddd [at] LT",sameElse:"L"},me={LTS:"h:mm:ss A",LT:"h:mm A",L:"MM/DD/YYYY",LL:"MMMM D, YYYY",LLL:"MMMM D, YYYY h:mm A",LLLL:"dddd, MMMM D, YYYY h:mm A"},ne="Invalid date",oe="%d",pe=/\d{1,2}/,qe={future:"in %s",past:"%s ago",s:"a few seconds",m:"a minute",mm:"%d minutes",h:"an hour",hh:"%d hours",d:"a day",dd:"%d days",M:"a month",MM:"%d months",y:"a year",yy:"%d years"},re=t.prototype;re._calendar=le,re.calendar=mc,re._longDateFormat=me,re.longDateFormat=nc,re._invalidDate=ne,re.invalidDate=oc,re._ordinal=oe,re.ordinal=pc,re._ordinalParse=pe,re.preparse=qc,re.postformat=qc,re._relativeTime=qe,re.relativeTime=rc,re.pastFuture=sc,re.set=tc,
// Month
re.months=W,re._months=Fd,re.monthsShort=X,re._monthsShort=Gd,re.monthsParse=Y,re._monthsRegex=Id,re.monthsRegex=ba,re._monthsShortRegex=Hd,re.monthsShortRegex=aa,
// Week
re.week=Qb,re._week=$d,re.firstDayOfYear=Sb,re.firstDayOfWeek=Rb,
// Day of Week
re.weekdays=Wb,re._weekdays=ae,re.weekdaysMin=Yb,re._weekdaysMin=ce,re.weekdaysShort=Xb,re._weekdaysShort=be,re.weekdaysParse=Zb,
// Hours
re.isPM=fc,re._meridiemParse=de,re.meridiem=gc,x("en",{ordinalParse:/\d{1,2}(th|st|nd|rd)/,ordinal:function(a){var b=a%10,c=1===r(a%100/10)?"th":1===b?"st":2===b?"nd":3===b?"rd":"th";return a+c}}),
// Side effect imports
a.lang=fa("moment.lang is deprecated. Use moment.locale instead.",x),a.langData=fa("moment.langData is deprecated. Use moment.localeData instead.",z);var se=Math.abs,te=Lc("ms"),ue=Lc("s"),ve=Lc("m"),we=Lc("h"),xe=Lc("d"),ye=Lc("w"),ze=Lc("M"),Ae=Lc("y"),Be=Nc("milliseconds"),Ce=Nc("seconds"),De=Nc("minutes"),Ee=Nc("hours"),Fe=Nc("days"),Ge=Nc("months"),He=Nc("years"),Ie=Math.round,Je={s:45,// seconds to minute
m:45,// minutes to hour
h:22,// hours to day
d:26,// days to month
M:11},Ke=Math.abs,Le=Ia.prototype;Le.abs=Bc,Le.add=Dc,Le.subtract=Ec,Le.as=Jc,Le.asMilliseconds=te,Le.asSeconds=ue,Le.asMinutes=ve,Le.asHours=we,Le.asDays=xe,Le.asWeeks=ye,Le.asMonths=ze,Le.asYears=Ae,Le.valueOf=Kc,Le._bubble=Gc,Le.get=Mc,Le.milliseconds=Be,Le.seconds=Ce,Le.minutes=De,Le.hours=Ee,Le.days=Fe,Le.weeks=Oc,Le.months=Ge,Le.years=He,Le.humanize=Sc,Le.toISOString=Tc,Le.toString=Tc,Le.toJSON=Tc,Le.locale=ub,Le.localeData=vb,
// Deprecations
Le.toIsoString=fa("toIsoString() is deprecated. Please use toISOString() instead (notice the capitals)",Tc),Le.lang=Zd,
// Side effect imports
// FORMATTING
J("X",0,0,"unix"),J("x",0,0,"valueOf"),
// PARSING
O("x",od),O("X",rd),S("X",function(a,b,c){c._d=new Date(1e3*parseFloat(a,10))}),S("x",function(a,b,c){c._d=new Date(r(a))}),
// Side effect imports
a.version="2.11.2",b(Ea),a.fn=ke,a.min=Ga,a.max=Ha,a.now=Td,a.utc=h,a.unix=kc,a.months=wc,a.isDate=d,a.locale=x,a.invalid=l,a.duration=Za,a.isMoment=p,a.weekdays=yc,a.parseZone=lc,a.localeData=z,a.isDuration=Ja,a.monthsShort=xc,a.weekdaysMin=Ac,a.defineLocale=y,a.weekdaysShort=zc,a.normalizeUnits=B,a.relativeTimeThreshold=Rc,a.prototype=ke;var Me=a;return Me});

/*!
 * modernizr v3.3.1
 * Build http://modernizr.com/download?-contenteditable-cookies-setclasses-shiv-dontmin-cssclassprefix:modernizr-
 *
 * Copyright (c)
 *  Faruk Ates
 *  Paul Irish
 *  Alex Sexton
 *  Ryan Seddon
 *  Patrick Kettner
 *  Stu Cox
 *  Richard Herrera

 * MIT License
 */
/*
 * Modernizr tests which native CSS3 and HTML5 features are available in the
 * current UA and makes the results available to you in two ways: as properties on
 * a global `Modernizr` object, and as classes on the `<html>` element. This
 * information allows you to progressively enhance your pages with a granular level
 * of control over the experience.
*/
!function(a,b,c){/**
   * is returns a boolean if the typeof an obj is exactly type.
   *
   * @access private
   * @function is
   * @param {*} obj - A thing we want to check the type of
   * @param {string} type - A string to compare the typeof against
   * @returns {boolean}
   */
function d(a,b){return typeof a===b}/**
   * Run through all tests and detect their support in the current UA.
   *
   * @access private
   */
function e(){var a,b,c,e,f,g,i;for(var l in h)if(h.hasOwnProperty(l)){
// run the test, throw the return value into the Modernizr,
// then based on that boolean, define an appropriate className
// and push it into an array of classes we'll join later.
//
// If there is no name, it's an 'async' test that is run,
// but not directly added to the object. That should
// be done with a post-run addTest call.
if(a=[],b=h[l],b.name&&(a.push(b.name.toLowerCase()),b.options&&b.options.aliases&&b.options.aliases.length))
// Add all the aliases into the names list
for(c=0;c<b.options.aliases.length;c++)a.push(b.options.aliases[c].toLowerCase());
// Set each of the names on the Modernizr object
for(e=d(b.fn,"function")?b.fn():b.fn,f=0;f<a.length;f++)g=a[f],i=g.split("."),1===i.length?j[i[0]]=e:(!j[i[0]]||j[i[0]]instanceof Boolean||(j[i[0]]=new Boolean(j[i[0]])),j[i[0]][i[1]]=e),k.push((e?"":"no-")+i.join("-"))}}/**
   * setClasses takes an array of class names and adds them to the root element
   *
   * @access private
   * @function setClasses
   * @param {string[]} classes - Array of class names
   */
// Pass in an and array of class names, e.g.:
//  ['no-webp', 'borderradius', ...]
function f(a){var b=l.className,c=j._config.classPrefix||"";
// Change `no-js` to `js` (independently of the `enableClasses` option)
// Handle classPrefix on this too
if(m&&(b=b.baseVal),j._config.enableJSClass){var d=new RegExp("(^|\\s)"+c+"no-js(\\s|$)");b=b.replace(d,"$1"+c+"js$2")}j._config.enableClasses&&(b+=" "+c+a.join(" "+c),m?l.className.baseVal=b:l.className=b)}/**
   * createElement is a convenience wrapper around document.createElement. Since we
   * use createElement all over the place, this allows for (slightly) smaller code
   * as well as abstracting away issues with creating elements in contexts other than
   * HTML documents (e.g. SVG documents).
   *
   * @access private
   * @function createElement
   * @returns {HTMLElement|SVGElement} An HTML or SVG element
   */
function g(){return"function"!=typeof b.createElement?b.createElement(arguments[0]):m?b.createElementNS.call(b,"http://www.w3.org/2000/svg",arguments[0]):b.createElement.apply(b,arguments)}var h=[],i={
// The current version, dummy
_version:"3.3.1",
// Any settings that don't work as separate modules
// can go in here as configuration.
_config:{classPrefix:"modernizr-",enableClasses:!0,enableJSClass:!0,usePrefixes:!0},
// Queue of tests
_q:[],
// Stub these for people who are listening
on:function(a,b){
// I don't really think people should do this, but we can
// safe guard it a bit.
// -- NOTE:: this gets WAY overridden in src/addTest for actual async tests.
// This is in case people listen to synchronous tests. I would leave it out,
// but the code to *disallow* sync tests in the real version of this
// function is actually larger than this.
var c=this;setTimeout(function(){b(c[a])},0)},addTest:function(a,b,c){h.push({name:a,fn:b,options:c})},addAsyncTest:function(a){h.push({name:null,fn:a})}},j=function(){};j.prototype=i,
// Leak modernizr globally when you `require` it rather than force it here.
// Overwrite name so constructor name is nicer :D
j=new j;var k=[],l=b.documentElement,m="svg"===l.nodeName.toLowerCase();m||!function(a,b){/*--------------------------------------------------------------------------*/
/**
       * Creates a style sheet with the given CSS text and adds it to the document.
       * @private
       * @param {Document} ownerDocument The document.
       * @param {String} cssText The CSS text.
       * @returns {StyleSheet} The style element.
       */
function c(a,b){var c=a.createElement("p"),d=a.getElementsByTagName("head")[0]||a.documentElement;return c.innerHTML="x<style>"+b+"</style>",d.insertBefore(c.lastChild,d.firstChild)}/**
       * Returns the value of `html5.elements` as an array.
       * @private
       * @returns {Array} An array of shived element node names.
       */
function d(){var a=t.elements;return"string"==typeof a?a.split(" "):a}/**
       * Extends the built-in list of html5 elements
       * @memberOf html5
       * @param {String|Array} newElements whitespace separated list or array of new element names to shiv
       * @param {Document} ownerDocument The context document.
       */
function e(a,b){var c=t.elements;"string"!=typeof c&&(c=c.join(" ")),"string"!=typeof a&&(a=a.join(" ")),t.elements=c+" "+a,j(b)}/**
       * Returns the data associated to the given document
       * @private
       * @param {Document} ownerDocument The document.
       * @returns {Object} An object of data.
       */
function f(a){var b=s[a[q]];return b||(b={},r++,a[q]=r,s[r]=b),b}/**
       * returns a shived element for the given nodeName and document
       * @memberOf html5
       * @param {String} nodeName name of the element
       * @param {Document|DocumentFragment} ownerDocument The context document.
       * @returns {Object} The shived element.
       */
function g(a,c,d){if(c||(c=b),l)return c.createElement(a);d||(d=f(c));var e;
// Avoid adding some elements to fragments in IE < 9 because
// * Attributes like `name` or `type` cannot be set/changed once an element
//   is inserted into a document/fragment
// * Link elements with `src` attributes that are inaccessible, as with
//   a 403 response, will cause the tab/window to crash
// * Script elements appended to fragments will execute when their `src`
//   or `text` property is set
return e=d.cache[a]?d.cache[a].cloneNode():p.test(a)?(d.cache[a]=d.createElem(a)).cloneNode():d.createElem(a),!e.canHaveChildren||o.test(a)||e.tagUrn?e:d.frag.appendChild(e)}/**
       * returns a shived DocumentFragment for the given document
       * @memberOf html5
       * @param {Document} ownerDocument The context document.
       * @returns {Object} The shived DocumentFragment.
       */
function h(a,c){if(a||(a=b),l)return a.createDocumentFragment();c=c||f(a);for(var e=c.frag.cloneNode(),g=0,h=d(),i=h.length;i>g;g++)e.createElement(h[g]);return e}/**
       * Shivs the `createElement` and `createDocumentFragment` methods of the document.
       * @private
       * @param {Document|DocumentFragment} ownerDocument The document.
       * @param {Object} data of the document.
       */
function i(a,b){b.cache||(b.cache={},b.createElem=a.createElement,b.createFrag=a.createDocumentFragment,b.frag=b.createFrag()),a.createElement=function(c){
//abort shiv
//abort shiv
return t.shivMethods?g(c,a,b):b.createElem(c)},a.createDocumentFragment=Function("h,f","return function(){var n=f.cloneNode(),c=n.createElement;h.shivMethods&&("+
// unroll the `createElement` calls
d().join().replace(/[\w\-:]+/g,function(a){return b.createElem(a),b.frag.createElement(a),'c("'+a+'")'})+");return n}")(t,b.frag)}/*--------------------------------------------------------------------------*/
/**
       * Shivs the given document.
       * @memberOf html5
       * @param {Document} ownerDocument The document to shiv.
       * @returns {Document} The shived document.
       */
function j(a){a||(a=b);var d=f(a);
// corrects block display not defined in IE6/7/8/9
return!t.shivCSS||k||d.hasCSS||(d.hasCSS=!!c(a,"article,aside,dialog,figcaption,figure,footer,header,hgroup,main,nav,section{display:block}mark{background:#FF0;color:#000}template{display:none}")),l||i(a,d),a}/*jshint evil:true */
/** version */
var k,l,m="3.7.3",n=a.html5||{},o=/^<|^(?:button|map|select|textarea|object|iframe|option|optgroup)$/i,p=/^(?:a|b|code|div|fieldset|h1|h2|h3|h4|h5|h6|i|label|li|ol|p|q|span|strong|style|table|tbody|td|th|tr|ul)$/i,q="_html5shiv",r=0,s={};!function(){try{var a=b.createElement("a");a.innerHTML="<xyz></xyz>",
//if the hidden property is implemented we can assume, that the browser supports basic HTML5 Styles
k="hidden"in a,l=1==a.childNodes.length||function(){
// assign a false positive if unable to shiv
b.createElement("a");var a=b.createDocumentFragment();return"undefined"==typeof a.cloneNode||"undefined"==typeof a.createDocumentFragment||"undefined"==typeof a.createElement}()}catch(c){
// assign a false positive if detection fails => unable to shiv
k=!0,l=!0}}();/*--------------------------------------------------------------------------*/
/**
       * The `html5` object is exposed so that more elements can be shived and
       * existing shiving can be detected on iframes.
       * @type Object
       * @example
       *
       * // options can be changed before the script is included
       * html5 = { 'elements': 'mark section', 'shivCSS': false, 'shivMethods': false };
       */
var t={/**
         * An array or space separated string of node names of the elements to shiv.
         * @memberOf html5
         * @type Array|String
         */
elements:n.elements||"abbr article aside audio bdi canvas data datalist details dialog figcaption figure footer header hgroup main mark meter nav output picture progress section summary template time video",/**
         * current version of html5shiv
         */
version:m,/**
         * A flag to indicate that the HTML5 style sheet should be inserted.
         * @memberOf html5
         * @type Boolean
         */
shivCSS:n.shivCSS!==!1,/**
         * Is equal to true if a browser supports creating unknown/HTML5 elements
         * @memberOf html5
         * @type boolean
         */
supportsUnknownElements:l,/**
         * A flag to indicate that the document's `createElement` and `createDocumentFragment`
         * methods should be overwritten.
         * @memberOf html5
         * @type Boolean
         */
shivMethods:n.shivMethods!==!1,/**
         * A string to describe the type of `html5` object ("default" or "default print").
         * @memberOf html5
         * @type String
         */
type:"default",
// shivs the document according to the specified `html5` object options
shivDocument:j,
//creates a shived element
createElement:g,
//creates a shived documentFragment
createDocumentFragment:h,
//extends list of elements
addElements:e};/*--------------------------------------------------------------------------*/
// expose html5
a.html5=t,
// shiv the document
j(b),"object"==typeof module&&module.exports&&(module.exports=t)}("undefined"!=typeof a?a:this,b),/*!
{
  "name": "Content Editable",
  "property": "contenteditable",
  "caniuse": "contenteditable",
  "notes": [{
    "name": "WHATWG spec",
    "href": "https://html.spec.whatwg.org/multipage/interaction.html#contenteditable"
  }]
}
!*/
/* DOC
Detects support for the `contenteditable` attribute of elements, allowing their DOM text contents to be edited directly by the user.
*/
j.addTest("contenteditable",function(){
// early bail out
if("contentEditable"in l){
// some mobile browsers (android < 3.0, iOS < 5) claim to support
// contentEditable, but but don't really. This test checks to see
// confirms whether or not it actually supports it.
var a=g("div");return a.contentEditable=!0,"true"===a.contentEditable}}),/*!
{
  "name": "Cookies",
  "property": "cookies",
  "tags": ["storage"],
  "authors": ["tauren"]
}
!*/
/* DOC
Detects whether cookie support is enabled.
*/
// https://github.com/Modernizr/Modernizr/issues/191
j.addTest("cookies",function(){
// navigator.cookieEnabled cannot detect custom or nuanced cookie blocking
// configurations. For example, when blocking cookies via the Advanced
// Privacy Settings in IE9, it always returns true. And there have been
// issues in the past with site-specific exceptions.
// Don't rely on it.
// try..catch because some in situations `document.cookie` is exposed but throws a
// SecurityError if you try to access it; e.g. documents created from data URIs
// or in sandboxed iframes (depending on flags/context)
try{
// Create cookie
b.cookie="cookietest=1";var a=-1!=b.cookie.indexOf("cookietest=");
// Delete cookie
return b.cookie="cookietest=1; expires=Thu, 01-Jan-1970 00:00:01 GMT",a}catch(c){return!1}}),
// Run each test
e(),
// Remove the "no-js" class if it exists
f(k),delete i.addTest,delete i.addAsyncTest;
// Run the things that are supposed to run after the tests
for(var n=0;n<j._q.length;n++)j._q[n]();
// Leak Modernizr namespace
a.Modernizr=j}(window,document);