<?php

/*
htmLawedTest.php, 8 August 2012
htmLawed 1.1.14, 8 August 2012
Copyright Santosh Patnaik
Dual licensed with LGPL 3 and GPL 2+
A PHP Labware internal utility - http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed

Test htmLawed; user provides text input; input and processed input are shown as highlighted code and rendered HTML; also shown are execution time and peak memory usage
*/

// config
$_errs = 0; // display PHP errors
$_limit = 8000; // input character limit

// more config
$_hlimit = 1000; // input character limit for showing hexdumps
$_hilite = 1; // 0 turns off slow Javascript-based code-highlighting, e.g., if $_limit is high
$_w3c_validate = 1; // 1 to show buttons to send input/output to w3c validator
$_sid = 'sid'; // session name; alphanum.
$_slife = 30; // session life in min.

// errors
error_reporting(E_ALL | (defined('E_STRICT') ? E_STRICT : 0));
ini_set('display_errors', $_errs);

// session
session_name($_sid);
session_cache_limiter('private');
session_cache_expire($_slife);
ini_set('session.gc_maxlifetime', $_slife * 60);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', 0);
session_start();
if(!isset($_SESSION['token'])){
 $_SESSION['token'] = md5(uniqid(rand(), 1));
}

// slashes
if(get_magic_quotes_gpc()){
 foreach($_POST as $k => $v){
  $_POST[$k] = stripslashes($v);
 }
 ini_set('magic_quotes_gpc', 0);
}
if(get_magic_quotes_runtime()){
 set_magic_quotes_runtime(0);
}

$_POST['enc'] = (isset($_POST['enc']) and preg_match('`^[-\w]+$`', $_POST['enc'])) ? $_POST['enc'] : 'utf-8';

// token for anti-CSRF
if(count($_POST)){
 if((empty($_GET['pre']) and ((!empty($_POST['token']) and !empty($_SESSION['token']) and $_POST['token'] != $_SESSION['token']) or empty($_POST[$_sid]) or $_POST[$_sid] != session_id() or empty($_COOKIE[$_sid]) or $_COOKIE[$_sid] != session_id())) or ($_POST[$_sid] != session_id())){
  $_POST = array('enc'=>'utf-8');
 }
}
if(empty($_GET['pre'])){
 $_SESSION['token'] = md5(uniqid(rand(), 1));
 $token = $_SESSION['token'];
 session_regenerate_id(1);
}

// compress
if(function_exists('gzencode') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && preg_match('`gzip|deflate`i', $_SERVER['HTTP_ACCEPT_ENCODING']) && !ini_get('zlib.output_compression')){
 ob_start('ob_gzhandler');
}

// HTM for unprocessed
if(isset($_POST['inputH'])){
 echo '<html><head><title>htmLawed test: HTML view of unprocessed input</title></head><body style="margin:0; padding: 0;"><p style="background-color: black; color: white; padding: 2px;">&nbsp; Rendering of unprocessed input without an HTML doctype or charset declaration &nbsp; &nbsp; <small><a style="color: white; text-decoration: none;" href="1" onclick="javascript:window.close(this); return false;">close window</a> | <a style="color: white; text-decoration: none;" href="htmLawedTest.php" onclick="javascript: window.open(\'htmLawedTest.php\', \'hlmain\'); window.close(this); return false;">htmLawed test page</a></small></p><div>', $_POST['inputH'], '</div></body></html>';
 exit;
}

// main
$_POST['text'] = isset($_POST['text']) ? $_POST['text'] : 'text to process; < '. $_limit. ' characters'. ($_hlimit ? ' (for binary hexdump view, < '. $_hlimit. ')' : '');
$do = (!empty($_POST[$_sid]) && isset($_POST['text'][0]) && !isset($_POST['text'][$_limit])) ? 1 : 0;
$limit_exceeded = isset($_POST['text'][$_limit]) ? 1 : 0;
$pre_mem = memory_get_usage();
$validation = (!empty($_POST[$_sid]) and isset($_POST['w3c_validate'][0])) ? 1 : 0;
include './htmLawed.php';

function format($t){
 $t = "\n". str_replace(array("\t", "\r\n", "\r", '&', '<', '>', "\n"), array('    ', "\n", "\n", '&amp;', '&lt;', '&gt;', "<span class=\"newline\">&#172;</span><br />\n"), $t);
 return str_replace(array('<br />', "\n ", '  '), array("\n<br />\n", "\n&nbsp;", ' &nbsp;'), $t);
}

function hexdump($d){
// Mainly by Aidan Lister <aidan@php.net>, Peter Waller <iridum@php.net>
 $hexi = '';
 $ascii = '';
 ob_start();
 echo '<pre>';
 $offset = 0;
 $len = strlen($d);
 for($i=$j=0; $i<$len; $i++)
 {
  // Convert to hexidecimal
  $hexi .= sprintf("%02X ", ord($d[$i]));
  // Replace non-viewable bytes with '.'
  if(ord($d[$i]) >= 32){
   $ascii .= htmlspecialchars($d[$i]);
  }else{
   $ascii .= '.';
  } 
  // Add extra column spacing
  if($j == 7){
   $hexi .= ' ';
   $ascii .= '  ';
  }
  // Add row
  if(++$j == 16 || $i == $len-1){
   // Join the hexi / ascii output
   echo sprintf("%04X   %-49s   %s", $offset, $hexi, $ascii);   
   // Reset vars
   $hexi = $ascii = '';
   $offset += 16;
   $j = 0;  
   // Add newline   
   if ($i !== $len-1){
    echo "\n";
   }
  }
 }
 echo '</pre>';
 $o = ob_get_contents();
 ob_end_clean();
 return $o;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en" xml:lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="description" content="htmLawed <?php echo hl_version();?> test page" />
<style type="text/css"><!--/*--><![CDATA[/*><!--*/
a, a.resizer{text-decoration:none;}
a:hover, a.resizer:hover{color:red;}
a.resizer{color:green; float:right;}
body{background-color:#efefef;}
body, button, div, html, input, p{font-size:13px; font-family:'Lucida grande', Verdana, Arial, Helvetica, sans-serif;}
button, input{font-size: 85%;}
div.help{border-top: 1px dotted gray; margin-top: 15px; padding-top: 15px; color:#999999;}
#inputC, #inputD, #inputF, #inputR, #outputD, #outputF, #outputH, #outputR, #settingF, #diff{display:block;}
#inputC, #settingF{background-color:white; border:1px gray solid; padding:3px;}
#inputC li{margin: 0; padding: 0;}
#inputC ul{margin: 0; padding: 0; margin-left: 14px;}
#inputC input{margin: 0; margin-left: 2px; margin-right: 2px; padding: 1px; vertical-align: middle;}
#inputD{overflow:auto; background-color:#ffff99; border:1px #cc9966 solid; padding:3px;}
#inputR{overflow:auto; background-color:#ffffcc; border:1px #ffcc99 solid; padding:3px;}
#inputC, #settingF, #inputD, #inputR, #outputD, #outputR, #diff, textarea{font-size:100%; font-family:'Bitstream vera sans mono', 'courier new', 'courier', monospace;}
#outputD{overflow:auto; background-color: #99ffcc; border:1px #66cc99 solid; padding:3px;}
#diff{overflow:auto; background-color: white; border:1px #dcdcdc solid; padding:3px;}
#outputH{overflow:auto; background-color:white; padding:3px; border:1px #dcdcdc solid;} 
#outputR{overflow:auto; background-color: #ccffcc; border:1px #99cc99 solid; padding:3px;} 
span.cmtcdata{color: orange;}
span.ctag{color:red;}
span.ent{border-bottom:1px dotted #999999;}
span.etag{color:purple;}
span.help{color:#999999;}
span.newline{color:#dcdcdc;}
span.notice{color:green;}
span.otag{color:blue;}
#topmost{margin:auto; width:98%;}
/*]]>*/--></style>
<script type="text/javascript"><!--//--><![CDATA[//><!-- 
window.name = 'hlmain';
function hl(i){
 <?php if(!$_hilite){echo 'return;'; }?>
 var e = document.getElementById(i);
 if(!e){return;}
 run(e, '</[a-z1-6]+>', 'ctag');
 run(e, '<[a-z]+(?:[^>]*)/>', 'etag');
 run(e, '<[a-z1-6]+(?:[^>]*)>', 'otag');
 run(e, '&[#a-z0-9]+;', 'ent');
 run(e, '<!(?:(?:--(?:.|\n)*?--)|(?:\\[CDATA\\[(?:.|\n)*?\\]\\]))>', 'cmtcdata');
}
function sndProc(){
 var f = document.getElementById('testform');
 if(!f){return;}
 var e = document.createElement('input');
 e.type = 'hidden';
 e.name = '<?php echo htmlspecialchars($_sid); ?>';
 e.id = '<?php echo htmlspecialchars($_sid); ?>';
 e.value = readCookie('<?php echo htmlspecialchars($_sid); ?>');
 f.appendChild(e);
 f.submit();
}
function readCookie(n){
 var ne = n + '=';
 var ca = document.cookie.split(';');
 for(var i=0;i < ca.length;i++){
  var c = ca[i];
  while(c.charAt(0)==' '){
   c = c.substring(1,c.length);
  }
  if(c.indexOf(ne) == 0){
   return c.substring(ne.length,c.length);
  }
 }
 return null;
}
function run(e, q, c){
 var q = new RegExp(q);
 if(e.firstChild == null){
  var m = q.exec(e.data);
  if(m){
   var v = m[0];
   var k2 = e.splitText(m.index);
   var k3 = k2.splitText(v.length);
   var s = e.ownerDocument.createElement('span');
   e.parentNode.replaceChild(s, k2);
   s.className = c; s.appendChild(k2);
  }
 }
 for(var k = e.firstChild; k != null; k = k.nextSibling){
  if(k.nodeType == 3){     
   var m = q.exec(k.data);
   if(m){
    var v = m[0];
    var k2 = k.splitText(m.index);
    var k3 = k2.splitText(v.length);
    var s = k.ownerDocument.createElement('span');
    k.parentNode.replaceChild(s, k2);
    s.className = c; s.appendChild(k2);
   }
  }
  else if(c == 'ent' && k.nodeType == 1){
   var d = k.firstChild;
   if(d){
    var m = q.exec(d.data);
    if(m){
     var v = m[0];
     var d2 = d.splitText(m.index);
     var d3 = d2.splitText(v.length);
     var s = d.ownerDocument.createElement('span');
     d.parentNode.replaceChild(s, d2);
     s.className = c; s.appendChild(d2);
    }
   }
  }
 }
}
function toggle(i){  
 var e = document.getElementById(i);  
 if(!e){return;}
 if(e.style){
  var a = e.style.display;   
  if(a == 'block'){e.style.display = 'none'; return;}
  if(a == 'none'){e.style.display = 'block';}
  else{e.style.display = 'none';}
  return;   
 }
 var a = e.visibility;
 if(a == 'hidden'){e.visibility = 'show'; return;}
 if(a == 'show'){e.visibility = 'hidden';}
}
function sndUnproc(){
 var i = document.getElementById('text');
 if(!i){return;}
 i = i.value;
 i = i.replace(/>/g, '&gt;');
 i = i.replace(/</g, '&lt;');
 i = i.replace(/"/g, '&quot;');
 var w = window.open('htmLawedTest.php?pre=1', 'hlprehtm');
 var f = document.createElement('form');
 f.enctype = 'application/x-www-form-urlencoded';
 f.method = 'post';
 f.acceptCharset = '<?php echo htmlspecialchars($_POST['enc']); ?>';
 if(f.style){f.style.display = 'none';}
 else{f.visibility = 'hidden';}
 f.innerHTML = '<p style="display:none;"><input style="display:none;" type="hidden" name="token" id="token" value="<?php echo $token; ?>" /><input style="display:none;" type="hidden" name="<?php echo htmlspecialchars($_sid); ?>" id="<?php echo htmlspecialchars($_sid); ?>" value="' + readCookie('<?php echo htmlspecialchars($_sid); ?>') + '" /><input style="display:none;" type="hidden" name="inputH" id="inputH" value="'+ i+ '" /></p>';
 f.action = 'htmLawedTest.php?pre=1';
 f.target = 'hlprehtm';
 f.method = 'post';
 var b = document.getElementsByTagName('body')[0];
 b.appendChild(f);
 f.submit();
 w.focus;
}
function sndValidn(id, type){
 var i = document.getElementById(id);
 if(!i){return;}
 i = i.value;
 i = i.replace(/>/g, '&gt;');
 i = i.replace(/</g, '&lt;');
 i = i.replace(/"/g, '&quot;');
 var w = window.open('http://validator.w3.org/check', 'validate'+id+type);
 var f = document.createElement('form');
 f.enctype = 'application/x-www-form-urlencoded';
 f.method = 'post';
 f.acceptCharset = '<?php echo htmlspecialchars($_POST['enc']); ?>';
 if(f.style){f.style.display = 'none';}
 else{f.visibility = 'hidden';}
 f.innerHTML = '<p style="display:none;"><input style="display:none;" type="hidden" name="fragment" id="fragment" value="'+ i+ '" /><input style="display:none;" type="hidden" name="prefill" id="prefill" value="1" /><input style="display:none;" type="hidden" name="prefill_doctype" id="prefill_doctype" value="'+ type+ '" /><input style="display:none;" type="hidden" name="group" id="group" value="1" /><input type="hidden" name="ss" id="ss" value="1" /></p>';
 f.action = 'http://validator.w3.org/check';
 f.target = 'validate'+id+type;
 var b = document.getElementsByTagName('body')[0];
 b.appendChild(f);
 f.submit();
 w.focus;
}
tRs = {
 formEl: null,
 resizeClass: 'textarea',
 adEv: function(t,ev,fn){
  if(typeof document.addEventListener != 'undefined'){
   t.addEventListener(ev,fn,false);
  }else{
   t.attachEvent('on' + ev, fn);
  }
 },
 rmEv: function(t,ev,fn){
  if(typeof document.removeEventListener != 'undefined'){
   t.removeEventListener(ev,fn,false);
  }else
  {
   t.detachEvent('on' + ev, fn);
  }
 },
 adBtn: function(){
  var textareas = document.getElementsByTagName('textarea');
  for(var i = 0; i < textareas.length; i++){	
   var txtclass=textareas[i].className;
   if(txtclass.substring(0,tRs.resizeClass.length)==tRs.resizeClass ||
   txtclass.substring(txtclass.length -tRs.resizeClass.length)==tRs.resizeClass){
    var a = document.createElement('a');
    a.appendChild(document.createTextNode("\u2195"));
    a.style.cursor = 'n-resize';
    a.className= 'resizer';
    a.title = 'click-drag to resize textarea'
    tRs.adEv(a, 'mousedown', tRs.initResize);
    textareas[i].parentNode.appendChild(a);
   }	
  }
 },
 initResize: function(event){
  if(typeof event == 'undefined'){
   event = window.event;
  }
  if(event.srcElement){
   var target = event.srcElement.previousSibling;
  }else{
   var target = event.target.previousSibling;
  }
  if(target.nodeName.toLowerCase() == 'textarea' || (target.nodeName.toLowerCase() == 'input' && target.type == 'text')){
   tRs.formEl = target;
   tRs.formEl.startHeight = tRs.formEl.clientHeight;
   tRs.formEl.startY = event.clientY;
   tRs.adEv(document, 'mousemove', tRs.resize);
   tRs.adEv(document, 'mouseup', tRs.stopResize);
   tRs.formEl.parentNode.style.cursor = 'n-resize';
   tRs.formEl.style.cursor = 'n-resize';
   try{
    event.preventDefault();
   }catch(e){
   }
  }
 },
 resize: function(event){
  if(typeof event == 'undefined'){
   event = window.event;
  }
	if(tRs.formEl.nodeName.toLowerCase() == 'textarea'){
   tRs.formEl.style.height = event.clientY - tRs.formEl.startY + tRs.formEl.startHeight + 'px';
  }
 },
 stopResize: function(event){
  tRs.rmEv(document, 'mousedown', tRs.initResize);
  tRs.rmEv(document, 'mousemove', tRs.resize);
  tRs.formEl.style.cursor = 'text';
  tRs.formEl.parentNode.style.cursor = 'auto';
  return false;
 }
};
tRs.adEv(window, 'load', tRs.adBtn);
// Diff Match and Patch javascript code by Neil Fraser; Apache license 2.0; http://code.google.com/p/google-diff-match-patch/
(function(){function diff_match_patch(){this.Diff_Timeout=1;this.Diff_EditCost=4;this.Match_Threshold=0.5;this.Match_Distance=1E3;this.Patch_DeleteThreshold=0.5;this.Patch_Margin=4;this.Match_MaxBits=32}
diff_match_patch.prototype.diff_main=function(a,b,c,d){"undefined"==typeof d&&(d=0>=this.Diff_Timeout?Number.MAX_VALUE:(new Date).getTime()+1E3*this.Diff_Timeout);if(null==a||null==b)throw Error("Null input. (diff_main)");if(a==b)return a?[[0,a]]:[];"undefined"==typeof c&&(c=!0);var e=c,f=this.diff_commonPrefix(a,b),c=a.substring(0,f),a=a.substring(f),b=b.substring(f),f=this.diff_commonSuffix(a,b),g=a.substring(a.length-f),a=a.substring(0,a.length-f),b=b.substring(0,b.length-f),a=this.diff_compute_(a,
b,e,d);c&&a.unshift([0,c]);g&&a.push([0,g]);this.diff_cleanupMerge(a);return a};
diff_match_patch.prototype.diff_compute_=function(a,b,c,d){if(!a)return[[1,b]];if(!b)return[[-1,a]];var e=a.length>b.length?a:b,f=a.length>b.length?b:a,g=e.indexOf(f);if(-1!=g)return c=[[1,e.substring(0,g)],[0,f],[1,e.substring(g+f.length)]],a.length>b.length&&(c[0][0]=c[2][0]=-1),c;if(1==f.length)return[[-1,a],[1,b]];return(e=this.diff_halfMatch_(a,b))?(f=e[0],a=e[1],g=e[2],b=e[3],e=e[4],f=this.diff_main(f,g,c,d),c=this.diff_main(a,b,c,d),f.concat([[0,e]],c)):c&&100<a.length&&100<b.length?this.diff_lineMode_(a,
b,d):this.diff_bisect_(a,b,d)};
diff_match_patch.prototype.diff_lineMode_=function(a,b,c){var d=this.diff_linesToChars_(a,b),a=d.chars1,b=d.chars2,d=d.lineArray,a=this.diff_main(a,b,!1,c);this.diff_charsToLines_(a,d);this.diff_cleanupSemantic(a);a.push([0,""]);for(var e=d=b=0,f="",g="";b<a.length;){switch(a[b][0]){case 1:e++;g+=a[b][1];break;case -1:d++;f+=a[b][1];break;case 0:if(1<=d&&1<=e){a.splice(b-d-e,d+e);b=b-d-e;d=this.diff_main(f,g,!1,c);for(e=d.length-1;0<=e;e--)a.splice(b,0,d[e]);b+=d.length}d=e=0;g=f=""}b++}a.pop();return a};
diff_match_patch.prototype.diff_bisect_=function(a,b,c){for(var d=a.length,e=b.length,f=Math.ceil((d+e)/2),g=f,h=2*f,j=Array(h),i=Array(h),k=0;k<h;k++)j[k]=-1,i[k]=-1;j[g+1]=0;i[g+1]=0;for(var k=d-e,p=0!=k%2,q=0,s=0,o=0,v=0,u=0;u<f&&!((new Date).getTime()>c);u++){for(var n=-u+q;n<=u-s;n+=2){var l=g+n,m;m=n==-u||n!=u&&j[l-1]<j[l+1]?j[l+1]:j[l-1]+1;for(var r=m-n;m<d&&r<e&&a.charAt(m)==b.charAt(r);)m++,r++;j[l]=m;if(m>d)s+=2;else if(r>e)q+=2;else if(p&&(l=g+k-n,0<=l&&l<h&&-1!=i[l])){var t=d-i[l];if(m>=
t)return this.diff_bisectSplit_(a,b,m,r,c)}}for(n=-u+o;n<=u-v;n+=2){l=g+n;t=n==-u||n!=u&&i[l-1]<i[l+1]?i[l+1]:i[l-1]+1;for(m=t-n;t<d&&m<e&&a.charAt(d-t-1)==b.charAt(e-m-1);)t++,m++;i[l]=t;if(t>d)v+=2;else if(m>e)o+=2;else if(!p&&(l=g+k-n,0<=l&&l<h&&-1!=j[l]&&(m=j[l],r=g+m-l,t=d-t,m>=t)))return this.diff_bisectSplit_(a,b,m,r,c)}}return[[-1,a],[1,b]]};
diff_match_patch.prototype.diff_bisectSplit_=function(a,b,c,d,e){var f=a.substring(0,c),g=b.substring(0,d),a=a.substring(c),b=b.substring(d),f=this.diff_main(f,g,!1,e),e=this.diff_main(a,b,!1,e);return f.concat(e)};
diff_match_patch.prototype.diff_linesToChars_=function(a,b){function c(a){for(var b="",c=0,f=-1,g=d.length;f<a.length-1;){f=a.indexOf("\n",c);-1==f&&(f=a.length-1);var q=a.substring(c,f+1),c=f+1;(e.hasOwnProperty?e.hasOwnProperty(q):void 0!==e[q])?b+=String.fromCharCode(e[q]):(b+=String.fromCharCode(g),e[q]=g,d[g++]=q)}return b}var d=[],e={};d[0]="";var f=c(a),g=c(b);return{chars1:f,chars2:g,lineArray:d}};
diff_match_patch.prototype.diff_charsToLines_=function(a,b){for(var c=0;c<a.length;c++){for(var d=a[c][1],e=[],f=0;f<d.length;f++)e[f]=b[d.charCodeAt(f)];a[c][1]=e.join("")}};diff_match_patch.prototype.diff_commonPrefix=function(a,b){if(!a||!b||a.charAt(0)!=b.charAt(0))return 0;for(var c=0,d=Math.min(a.length,b.length),e=d,f=0;c<e;)a.substring(f,e)==b.substring(f,e)?f=c=e:d=e,e=Math.floor((d-c)/2+c);return e};
diff_match_patch.prototype.diff_commonSuffix=function(a,b){if(!a||!b||a.charAt(a.length-1)!=b.charAt(b.length-1))return 0;for(var c=0,d=Math.min(a.length,b.length),e=d,f=0;c<e;)a.substring(a.length-e,a.length-f)==b.substring(b.length-e,b.length-f)?f=c=e:d=e,e=Math.floor((d-c)/2+c);return e};
diff_match_patch.prototype.diff_commonOverlap_=function(a,b){var c=a.length,d=b.length;if(0==c||0==d)return 0;c>d?a=a.substring(c-d):c<d&&(b=b.substring(0,c));c=Math.min(c,d);if(a==b)return c;for(var d=0,e=1;;){var f=a.substring(c-e),f=b.indexOf(f);if(-1==f)return d;e+=f;if(0==f||a.substring(c-e)==b.substring(0,e))d=e,e++}};
diff_match_patch.prototype.diff_halfMatch_=function(a,b){function c(a,b,c){for(var d=a.substring(c,c+Math.floor(a.length/4)),e=-1,g="",h,j,n,l;-1!=(e=b.indexOf(d,e+1));){var m=f.diff_commonPrefix(a.substring(c),b.substring(e)),r=f.diff_commonSuffix(a.substring(0,c),b.substring(0,e));g.length<r+m&&(g=b.substring(e-r,e)+b.substring(e,e+m),h=a.substring(0,c-r),j=a.substring(c+m),n=b.substring(0,e-r),l=b.substring(e+m))}return 2*g.length>=a.length?[h,j,n,l,g]:null}if(0>=this.Diff_Timeout)return null;
var d=a.length>b.length?a:b,e=a.length>b.length?b:a;if(4>d.length||2*e.length<d.length)return null;var f=this,g=c(d,e,Math.ceil(d.length/4)),d=c(d,e,Math.ceil(d.length/2)),h;if(!g&&!d)return null;h=d?g?g[4].length>d[4].length?g:d:d:g;var j;a.length>b.length?(g=h[0],d=h[1],e=h[2],j=h[3]):(e=h[0],j=h[1],g=h[2],d=h[3]);h=h[4];return[g,d,e,j,h]};
diff_match_patch.prototype.diff_cleanupSemantic=function(a){for(var b=!1,c=[],d=0,e=null,f=0,g=0,h=0,j=0,i=0;f<a.length;)0==a[f][0]?(c[d++]=f,g=j,h=i,i=j=0,e=a[f][1]):(1==a[f][0]?j+=a[f][1].length:i+=a[f][1].length,e&&e.length<=Math.max(g,h)&&e.length<=Math.max(j,i)&&(a.splice(c[d-1],0,[-1,e]),a[c[d-1]+1][0]=1,d--,d--,f=0<d?c[d-1]:-1,i=j=h=g=0,e=null,b=!0)),f++;b&&this.diff_cleanupMerge(a);this.diff_cleanupSemanticLossless(a);for(f=1;f<a.length;){if(-1==a[f-1][0]&&1==a[f][0]){b=a[f-1][1];c=a[f][1];
d=this.diff_commonOverlap_(b,c);e=this.diff_commonOverlap_(c,b);if(d>=e){if(d>=b.length/2||d>=c.length/2)a.splice(f,0,[0,c.substring(0,d)]),a[f-1][1]=b.substring(0,b.length-d),a[f+1][1]=c.substring(d),f++}else if(e>=b.length/2||e>=c.length/2)a.splice(f,0,[0,b.substring(0,e)]),a[f-1][0]=1,a[f-1][1]=c.substring(0,c.length-e),a[f+1][0]=-1,a[f+1][1]=b.substring(e),f++;f++}f++}};
diff_match_patch.prototype.diff_cleanupSemanticLossless=function(a){function b(a,b){if(!a||!b)return 6;var c=a.charAt(a.length-1),d=b.charAt(0),e=c.match(diff_match_patch.nonAlphaNumericRegex_),f=d.match(diff_match_patch.nonAlphaNumericRegex_),g=e&&c.match(diff_match_patch.whitespaceRegex_),h=f&&d.match(diff_match_patch.whitespaceRegex_),c=g&&c.match(diff_match_patch.linebreakRegex_),d=h&&d.match(diff_match_patch.linebreakRegex_),i=c&&a.match(diff_match_patch.blanklineEndRegex_),j=d&&b.match(diff_match_patch.blanklineStartRegex_);
return i||j?5:c||d?4:e&&!g&&h?3:g||h?2:e||f?1:0}for(var c=1;c<a.length-1;){if(0==a[c-1][0]&&0==a[c+1][0]){var d=a[c-1][1],e=a[c][1],f=a[c+1][1],g=this.diff_commonSuffix(d,e);if(g)var h=e.substring(e.length-g),d=d.substring(0,d.length-g),e=h+e.substring(0,e.length-g),f=h+f;for(var g=d,h=e,j=f,i=b(d,e)+b(e,f);e.charAt(0)===f.charAt(0);){var d=d+e.charAt(0),e=e.substring(1)+f.charAt(0),f=f.substring(1),k=b(d,e)+b(e,f);k>=i&&(i=k,g=d,h=e,j=f)}a[c-1][1]!=g&&(g?a[c-1][1]=g:(a.splice(c-1,1),c--),a[c][1]=
h,j?a[c+1][1]=j:(a.splice(c+1,1),c--))}c++}};diff_match_patch.nonAlphaNumericRegex_=/[^a-zA-Z0-9]/;diff_match_patch.whitespaceRegex_=/\s/;diff_match_patch.linebreakRegex_=/[\r\n]/;diff_match_patch.blanklineEndRegex_=/\n\r?\n$/;diff_match_patch.blanklineStartRegex_=/^\r?\n\r?\n/;
diff_match_patch.prototype.diff_cleanupEfficiency=function(a){for(var b=!1,c=[],d=0,e=null,f=0,g=!1,h=!1,j=!1,i=!1;f<a.length;){if(0==a[f][0])a[f][1].length<this.Diff_EditCost&&(j||i)?(c[d++]=f,g=j,h=i,e=a[f][1]):(d=0,e=null),j=i=!1;else if(-1==a[f][0]?i=!0:j=!0,e&&(g&&h&&j&&i||e.length<this.Diff_EditCost/2&&3==g+h+j+i))a.splice(c[d-1],0,[-1,e]),a[c[d-1]+1][0]=1,d--,e=null,g&&h?(j=i=!0,d=0):(d--,f=0<d?c[d-1]:-1,j=i=!1),b=!0;f++}b&&this.diff_cleanupMerge(a)};
diff_match_patch.prototype.diff_cleanupMerge=function(a){a.push([0,""]);for(var b=0,c=0,d=0,e="",f="",g;b<a.length;)switch(a[b][0]){case 1:d++;f+=a[b][1];b++;break;case -1:c++;e+=a[b][1];b++;break;case 0:1<c+d?(0!==c&&0!==d&&(g=this.diff_commonPrefix(f,e),0!==g&&(0<b-c-d&&0==a[b-c-d-1][0]?a[b-c-d-1][1]+=f.substring(0,g):(a.splice(0,0,[0,f.substring(0,g)]),b++),f=f.substring(g),e=e.substring(g)),g=this.diff_commonSuffix(f,e),0!==g&&(a[b][1]=f.substring(f.length-g)+a[b][1],f=f.substring(0,f.length-
g),e=e.substring(0,e.length-g))),0===c?a.splice(b-d,c+d,[1,f]):0===d?a.splice(b-c,c+d,[-1,e]):a.splice(b-c-d,c+d,[-1,e],[1,f]),b=b-c-d+(c?1:0)+(d?1:0)+1):0!==b&&0==a[b-1][0]?(a[b-1][1]+=a[b][1],a.splice(b,1)):b++,c=d=0,f=e=""}""===a[a.length-1][1]&&a.pop();c=!1;for(b=1;b<a.length-1;)0==a[b-1][0]&&0==a[b+1][0]&&(a[b][1].substring(a[b][1].length-a[b-1][1].length)==a[b-1][1]?(a[b][1]=a[b-1][1]+a[b][1].substring(0,a[b][1].length-a[b-1][1].length),a[b+1][1]=a[b-1][1]+a[b+1][1],a.splice(b-1,1),c=!0):a[b][1].substring(0,
a[b+1][1].length)==a[b+1][1]&&(a[b-1][1]+=a[b+1][1],a[b][1]=a[b][1].substring(a[b+1][1].length)+a[b+1][1],a.splice(b+1,1),c=!0)),b++;c&&this.diff_cleanupMerge(a)};diff_match_patch.prototype.diff_xIndex=function(a,b){var c=0,d=0,e=0,f=0,g;for(g=0;g<a.length;g++){1!==a[g][0]&&(c+=a[g][1].length);-1!==a[g][0]&&(d+=a[g][1].length);if(c>b)break;e=c;f=d}return a.length!=g&&-1===a[g][0]?f:f+(b-e)};
diff_match_patch.prototype.diff_prettyHtml=function(a){for(var b=[],c=/&/g,d=/</g,e=/>/g,f=/\n/g,g=0;g<a.length;g++){var h=a[g][0],j=a[g][1],j=j.replace(c,"&amp;").replace(d,"&lt;").replace(e,"&gt;").replace(f,"<span style=\"color: #dcdcdc;\">&not;</span><br>");switch(h){case 1:b[g]='<ins style="background:#ccffcc; text-decoration: none;">'+j+"</ins>";break;case -1:b[g]='<del style="background:#ffffcc; text-decoration: line-through; color: orange;">'+j+"</del>";break;case 0:b[g]="<span>"+j+"</span>"}}return b.join("")};
diff_match_patch.prototype.diff_text1=function(a){for(var b=[],c=0;c<a.length;c++)1!==a[c][0]&&(b[c]=a[c][1]);return b.join("")};diff_match_patch.prototype.diff_text2=function(a){for(var b=[],c=0;c<a.length;c++)-1!==a[c][0]&&(b[c]=a[c][1]);return b.join("")};diff_match_patch.prototype.diff_levenshtein=function(a){for(var b=0,c=0,d=0,e=0;e<a.length;e++){var f=a[e][0],g=a[e][1];switch(f){case 1:c+=g.length;break;case -1:d+=g.length;break;case 0:b+=Math.max(c,d),d=c=0}}return b+=Math.max(c,d)};
diff_match_patch.prototype.diff_toDelta=function(a){for(var b=[],c=0;c<a.length;c++)switch(a[c][0]){case 1:b[c]="+"+encodeURI(a[c][1]);break;case -1:b[c]="-"+a[c][1].length;break;case 0:b[c]="="+a[c][1].length}return b.join("\t").replace(/%20/g," ")};
diff_match_patch.prototype.diff_fromDelta=function(a,b){for(var c=[],d=0,e=0,f=b.split(/\t/g),g=0;g<f.length;g++){var h=f[g].substring(1);switch(f[g].charAt(0)){case "+":try{c[d++]=[1,decodeURI(h)]}catch(j){throw Error("Illegal escape in diff_fromDelta: "+h);}break;case "-":case "=":var i=parseInt(h,10);if(isNaN(i)||0>i)throw Error("Invalid number in diff_fromDelta: "+h);h=a.substring(e,e+=i);"="==f[g].charAt(0)?c[d++]=[0,h]:c[d++]=[-1,h];break;default:if(f[g])throw Error("Invalid diff operation in diff_fromDelta: "+
f[g]);}}if(e!=a.length)throw Error("Delta length ("+e+") does not equal source text length ("+a.length+").");return c};diff_match_patch.prototype.match_main=function(a,b,c){if(null==a||null==b||null==c)throw Error("Null input. (match_main)");c=Math.max(0,Math.min(c,a.length));return a==b?0:a.length?a.substring(c,c+b.length)==b?c:this.match_bitap_(a,b,c):-1};
diff_match_patch.prototype.match_bitap_=function(a,b,c){function d(a,d){var e=a/b.length,g=Math.abs(c-d);return!f.Match_Distance?g?1:e:e+g/f.Match_Distance}if(b.length>this.Match_MaxBits)throw Error("Pattern too long for this browser.");var e=this.match_alphabet_(b),f=this,g=this.Match_Threshold,h=a.indexOf(b,c);-1!=h&&(g=Math.min(d(0,h),g),h=a.lastIndexOf(b,c+b.length),-1!=h&&(g=Math.min(d(0,h),g)));for(var j=1<<b.length-1,h=-1,i,k,p=b.length+a.length,q,s=0;s<b.length;s++){i=0;for(k=p;i<k;)d(s,c+
k)<=g?i=k:p=k,k=Math.floor((p-i)/2+i);p=k;i=Math.max(1,c-k+1);var o=Math.min(c+k,a.length)+b.length;k=Array(o+2);for(k[o+1]=(1<<s)-1;o>=i;o--){var v=e[a.charAt(o-1)];k[o]=0===s?(k[o+1]<<1|1)&v:(k[o+1]<<1|1)&v|(q[o+1]|q[o])<<1|1|q[o+1];if(k[o]&j&&(v=d(s,o-1),v<=g))if(g=v,h=o-1,h>c)i=Math.max(1,2*c-h);else break}if(d(s+1,c)>g)break;q=k}return h};
diff_match_patch.prototype.match_alphabet_=function(a){for(var b={},c=0;c<a.length;c++)b[a.charAt(c)]=0;for(c=0;c<a.length;c++)b[a.charAt(c)]|=1<<a.length-c-1;return b};
diff_match_patch.prototype.patch_addContext_=function(a,b){if(0!=b.length){for(var c=b.substring(a.start2,a.start2+a.length1),d=0;b.indexOf(c)!=b.lastIndexOf(c)&&c.length<this.Match_MaxBits-this.Patch_Margin-this.Patch_Margin;)d+=this.Patch_Margin,c=b.substring(a.start2-d,a.start2+a.length1+d);d+=this.Patch_Margin;(c=b.substring(a.start2-d,a.start2))&&a.diffs.unshift([0,c]);(d=b.substring(a.start2+a.length1,a.start2+a.length1+d))&&a.diffs.push([0,d]);a.start1-=c.length;a.start2-=c.length;a.length1+=
c.length+d.length;a.length2+=c.length+d.length}};
diff_match_patch.prototype.patch_make=function(a,b,c){var d;if("string"==typeof a&&"string"==typeof b&&"undefined"==typeof c)d=a,b=this.diff_main(d,b,!0),2<b.length&&(this.diff_cleanupSemantic(b),this.diff_cleanupEfficiency(b));else if(a&&"object"==typeof a&&"undefined"==typeof b&&"undefined"==typeof c)b=a,d=this.diff_text1(b);else if("string"==typeof a&&b&&"object"==typeof b&&"undefined"==typeof c)d=a;else if("string"==typeof a&&"string"==typeof b&&c&&"object"==typeof c)d=a,b=c;else throw Error("Unknown call format to patch_make.");
if(0===b.length)return[];for(var c=[],a=new diff_match_patch.patch_obj,e=0,f=0,g=0,h=d,j=0;j<b.length;j++){var i=b[j][0],k=b[j][1];if(!e&&0!==i)a.start1=f,a.start2=g;switch(i){case 1:a.diffs[e++]=b[j];a.length2+=k.length;d=d.substring(0,g)+k+d.substring(g);break;case -1:a.length1+=k.length;a.diffs[e++]=b[j];d=d.substring(0,g)+d.substring(g+k.length);break;case 0:k.length<=2*this.Patch_Margin&&e&&b.length!=j+1?(a.diffs[e++]=b[j],a.length1+=k.length,a.length2+=k.length):k.length>=2*this.Patch_Margin&&
e&&(this.patch_addContext_(a,h),c.push(a),a=new diff_match_patch.patch_obj,e=0,h=d,f=g)}1!==i&&(f+=k.length);-1!==i&&(g+=k.length)}e&&(this.patch_addContext_(a,h),c.push(a));return c};diff_match_patch.prototype.patch_deepCopy=function(a){for(var b=[],c=0;c<a.length;c++){var d=a[c],e=new diff_match_patch.patch_obj;e.diffs=[];for(var f=0;f<d.diffs.length;f++)e.diffs[f]=d.diffs[f].slice();e.start1=d.start1;e.start2=d.start2;e.length1=d.length1;e.length2=d.length2;b[c]=e}return b};
diff_match_patch.prototype.patch_apply=function(a,b){if(0==a.length)return[b,[]];var a=this.patch_deepCopy(a),c=this.patch_addPadding(a),b=c+b+c;this.patch_splitMax(a);for(var d=0,e=[],f=0;f<a.length;f++){var g=a[f].start2+d,h=this.diff_text1(a[f].diffs),j,i=-1;if(h.length>this.Match_MaxBits){if(j=this.match_main(b,h.substring(0,this.Match_MaxBits),g),-1!=j&&(i=this.match_main(b,h.substring(h.length-this.Match_MaxBits),g+h.length-this.Match_MaxBits),-1==i||j>=i))j=-1}else j=this.match_main(b,h,g);
if(-1==j)e[f]=!1,d-=a[f].length2-a[f].length1;else if(e[f]=!0,d=j-g,g=-1==i?b.substring(j,j+h.length):b.substring(j,i+this.Match_MaxBits),h==g)b=b.substring(0,j)+this.diff_text2(a[f].diffs)+b.substring(j+h.length);else if(g=this.diff_main(h,g,!1),h.length>this.Match_MaxBits&&this.diff_levenshtein(g)/h.length>this.Patch_DeleteThreshold)e[f]=!1;else{this.diff_cleanupSemanticLossless(g);for(var h=0,k,i=0;i<a[f].diffs.length;i++){var p=a[f].diffs[i];0!==p[0]&&(k=this.diff_xIndex(g,h));1===p[0]?b=b.substring(0,
j+k)+p[1]+b.substring(j+k):-1===p[0]&&(b=b.substring(0,j+k)+b.substring(j+this.diff_xIndex(g,h+p[1].length)));-1!==p[0]&&(h+=p[1].length)}}}b=b.substring(c.length,b.length-c.length);return[b,e]};
diff_match_patch.prototype.patch_addPadding=function(a){for(var b=this.Patch_Margin,c="",d=1;d<=b;d++)c+=String.fromCharCode(d);for(d=0;d<a.length;d++)a[d].start1+=b,a[d].start2+=b;var d=a[0],e=d.diffs;if(0==e.length||0!=e[0][0])e.unshift([0,c]),d.start1-=b,d.start2-=b,d.length1+=b,d.length2+=b;else if(b>e[0][1].length){var f=b-e[0][1].length;e[0][1]=c.substring(e[0][1].length)+e[0][1];d.start1-=f;d.start2-=f;d.length1+=f;d.length2+=f}d=a[a.length-1];e=d.diffs;0==e.length||0!=e[e.length-1][0]?(e.push([0,
c]),d.length1+=b,d.length2+=b):b>e[e.length-1][1].length&&(f=b-e[e.length-1][1].length,e[e.length-1][1]+=c.substring(0,f),d.length1+=f,d.length2+=f);return c};
diff_match_patch.prototype.patch_splitMax=function(a){for(var b=this.Match_MaxBits,c=0;c<a.length;c++)if(!(a[c].length1<=b)){var d=a[c];a.splice(c--,1);for(var e=d.start1,f=d.start2,g="";0!==d.diffs.length;){var h=new diff_match_patch.patch_obj,j=!0;h.start1=e-g.length;h.start2=f-g.length;if(""!==g)h.length1=h.length2=g.length,h.diffs.push([0,g]);for(;0!==d.diffs.length&&h.length1<b-this.Patch_Margin;){var g=d.diffs[0][0],i=d.diffs[0][1];1===g?(h.length2+=i.length,f+=i.length,h.diffs.push(d.diffs.shift()),
j=!1):-1===g&&1==h.diffs.length&&0==h.diffs[0][0]&&i.length>2*b?(h.length1+=i.length,e+=i.length,j=!1,h.diffs.push([g,i]),d.diffs.shift()):(i=i.substring(0,b-h.length1-this.Patch_Margin),h.length1+=i.length,e+=i.length,0===g?(h.length2+=i.length,f+=i.length):j=!1,h.diffs.push([g,i]),i==d.diffs[0][1]?d.diffs.shift():d.diffs[0][1]=d.diffs[0][1].substring(i.length))}g=this.diff_text2(h.diffs);g=g.substring(g.length-this.Patch_Margin);i=this.diff_text1(d.diffs).substring(0,this.Patch_Margin);""!==i&&
(h.length1+=i.length,h.length2+=i.length,0!==h.diffs.length&&0===h.diffs[h.diffs.length-1][0]?h.diffs[h.diffs.length-1][1]+=i:h.diffs.push([0,i]));j||a.splice(++c,0,h)}}};diff_match_patch.prototype.patch_toText=function(a){for(var b=[],c=0;c<a.length;c++)b[c]=a[c];return b.join("")};
diff_match_patch.prototype.patch_fromText=function(a){var b=[];if(!a)return b;for(var a=a.split("\n"),c=0,d=/^@@ -(\d+),?(\d*) \+(\d+),?(\d*) @@$/;c<a.length;){var e=a[c].match(d);if(!e)throw Error("Invalid patch string: "+a[c]);var f=new diff_match_patch.patch_obj;b.push(f);f.start1=parseInt(e[1],10);""===e[2]?(f.start1--,f.length1=1):"0"==e[2]?f.length1=0:(f.start1--,f.length1=parseInt(e[2],10));f.start2=parseInt(e[3],10);""===e[4]?(f.start2--,f.length2=1):"0"==e[4]?f.length2=0:(f.start2--,f.length2=
parseInt(e[4],10));for(c++;c<a.length;){e=a[c].charAt(0);try{var g=decodeURI(a[c].substring(1))}catch(h){throw Error("Illegal escape in patch_fromText: "+g);}if("-"==e)f.diffs.push([-1,g]);else if("+"==e)f.diffs.push([1,g]);else if(" "==e)f.diffs.push([0,g]);else if("@"==e)break;else if(""!==e)throw Error('Invalid patch mode "'+e+'" in: '+g);c++}}return b};diff_match_patch.patch_obj=function(){this.diffs=[];this.start2=this.start1=null;this.length2=this.length1=0};
diff_match_patch.patch_obj.prototype.toString=function(){var a,b;a=0===this.length1?this.start1+",0":1==this.length1?this.start1+1:this.start1+1+","+this.length1;b=0===this.length2?this.start2+",0":1==this.length2?this.start2+1:this.start2+1+","+this.length2;a=["@@ -"+a+" +"+b+" @@\n"];var c;for(b=0;b<this.diffs.length;b++){switch(this.diffs[b][0]){case 1:c="+";break;case -1:c="-";break;case 0:c=" "}a[b+1]=c+encodeURI(this.diffs[b][1])+"\n"}return a.join("").replace(/%20/g," ")};
this.diff_match_patch=diff_match_patch;this.DIFF_DELETE=-1;this.DIFF_INSERT=1;this.DIFF_EQUAL=0;})()
var dmp = new diff_match_patch(); function diffLaunch(){var text1 = document.getElementById('text').value; var text2 = document.getElementById('text2').value; dmp.Diff_Timeout = 0; dmp.Diff_EditCost = 4; var d = dmp.diff_main(text1, text2); var ds = dmp.diff_prettyHtml(d); document.getElementById('diff').innerHTML = ds;
}
//--><!]]></script>
<title>htmLawed (<?php echo hl_version();?>) test</title>
</head>
<body>
<div id="topmost">

<h5 style="float: left; display: inline; margin-top: 0; margin-bottom: 5px;"><a href="http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php" title="htmLawed home">HTM<big><big>L</big></big>AWED</a> <?php echo hl_version();?> <a href="htmLawedTest.php" title="test home">TEST</a></h5>
<span style="float: right;" class="help"><a href="htmLawed_README.htm"><span class="notice">htm</span></a> / <a href="htmLawed_README.txt"><span class="notice">txt</span></a> documentation</span><br style="clear:both;" />

<a href="htmLawedTest.php" title="[toggle visibility] type or copy-paste" onclick="javascript:toggle('inputF'); return false;"><span class="notice">Input &raquo;</span> <span class="help" title="limit lower with multibyte characters<?php echo (($_hlimit < $_limit && $_hlimit)? '; limit is '. $_hlimit. ' for viewing binaries' : ''); ?>"><small>(max. <?php echo htmlspecialchars($_limit);?> chars)</small></span></a>

<form id="testform" name="testform" action="htmLawedTest.php" method="post" accept-charset="<?php echo htmlspecialchars($_POST['enc']); ?>" style="padding:0; margin: 0; display:inline;">

<div id="inputF" style="display: block;">

<input type="hidden" name="token" id="token" value="<?php echo $token; ?>" />
<div><textarea id="text" class="textarea" name="text" rows="5" cols="100" style="width: 100%;"><?php echo htmlspecialchars($_POST['text']);?></textarea></div>
<input type="submit" id="submitF" name="submitF" value="Process" style="float:left;" title="filter using htmLawed" onclick="javascript: sndProc(); return false;" onkeypress="javascript: sndProc(); return false;" />

<?php
if($do){
 if($validation){
  echo '<input type="hidden" value="1" name="w3c_validate" id="w3c_validate" />';
 }
?>
 
<button type="button" title="rendered as web-page without a doctype or charset declaration" style="float: right;" onclick="javascript: sndUnproc(); return false;" onkeypress="javascript: sndUnproc(); return false;">View unprocessed</button>
<button type="button" onclick="javascript:document.getElementById('text').focus();document.getElementById('text').select()" title="select all to copy" style="float:right;">Select all</button>

<?php
if($_w3c_validate && $validation){
?>
  
<button type="button" title="HTML 4.01 W3C online validation" style="float: right;" onclick="javascript: sndValidn('text', 'html401'); return false;" onkeypress="javascript: sndValidn('text', 'html401'); return false;">Check HTML</button>
<button type="button" title="XHTML 1.1 W3C online validation" style="float: right;" onclick="javascript: sndValidn('text', 'xhtml110'); return false;" onkeypress="javascript: sndValidn('text', 'xhtml110'); return false;">Check XHTML</button>
  
<?php
 }
}
else{
 if($_w3c_validate){
  echo '<span style="float: right;" class="help" title="for direct submission of input or output code to W3C validator for (X)HTML validation"><span style="font-size: 85%;">&nbsp;Validator tools: </span><input type="checkbox" value="1" name="w3c_validate" id="w3c_validate" style="vertical-align: middle;"', ($validation ? ' checked="checked"' : ''), ' /></span>';
 }
}
?>

<span style="float:right;" class="help" title="IANA-recognized name of the input character-set; can be multiple ;- or space-separated values; may not work in some browsers"><span style="font-size: 85%;">Encoding: </span><input type="text" size="8" id="enc" name="enc" style="vertical-align: middle;" value="<?php echo htmlspecialchars($_POST['enc']); ?>" /></span>

</div>
<br style="clear:both;" />

<?php
if($limit_exceeded){
 echo '<br /><strong>Input text is too long!</strong><br />';
}
?>

<br />

<a href="htmLawedTest.php" title="[toggle visibility] htmLawed configuration" onclick="javascript:toggle('inputC'); return false;"><span class="notice">Settings &raquo;</span></a>

<div id="inputC" style="display: none;">
<table summary="none">
<tr>
<td><span class="help" title="$config argument">Config:</span></td>
<td><ul>
 
<?php
$cfg = array(
'abs_url'=>array('3', '0', 'absolute/relative URL conversion', '-1'),
'and_mark'=>array('2', '0', 'mark original <em>&amp;</em> chars', '0', 'd'=>1), // 'd' to disable
'anti_link_spam'=>array('1', '0', 'modify <em>href</em> values as an anti-link spam measure', '0', array(array('30', '1', '', 'regex for extra <em>rel</em>'), array('30', '2', '', 'regex for no <em>href</em>'))),
'anti_mail_spam'=>array('1', '0', 'replace <em>@</em> in <em>mailto:</em> URLs', '0', '8', 'NO@SPAM', 'replacement'),
'balance'=>array('2', '1', 'fix nestings and balance tags', '0'),
'base_url'=>array('', '', 'base URL', '25'),
'cdata'=>array('4', 'nil', 'allow <em>CDATA</em> sections', 'nil'),
'clean_ms_char'=>array('3', '0', 'replace bad characters introduced by Microsoft apps. like <em>Word</em>', '0'),
'comment'=>array('4', 'nil', 'allow HTML comments', 'nil'),
'css_expression'=>array('2', 'nil', 'allow dynamic expressions in CSS style properties', 'nil'),
'deny_attribute'=>array('1', '0', 'denied attributes', '0', '50', '', 'these'),
'direct_list_nest'=>array('2', 'nil', 'allow direct nesting of a list within another without requiring it to be a list item', 'nil'),
'elements'=>array('', '', 'allowed elements', '50'),
'hexdec_entity'=>array('3', '1', 'convert hexadecimal numeric entities to decimal ones, or vice versa', '0'),
'hook'=>array('', '', 'name of hook function', '25'),
'hook_tag'=>array('', '', 'name of custom function to further check attribute values', '25'),
'keep_bad'=>array('7', '6', 'keep, or remove <em>bad</em> tag content', '0'),
'lc_std_val'=>array('2', '1', 'lower-case std. attribute values like <em>radio</em>', '0'),
'make_tag_strict'=>array('3', 'nil', 'transform deprecated elements', 'nil'),
'named_entity'=>array('2', '1', 'allow named entities, or convert numeric ones', '0'),
'no_deprecated_attr'=>array('3', '1', 'allow deprecated attributes, or transform them', '0'),
'parent'=>array('', 'div', 'name of parent element', '25'),
'safe'=>array('2', '0', 'for most <em>safe</em> HTML', '0'),
'schemes'=>array('', 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https', 'allowed URL protocols', '50'),
'show_setting'=>array('', 'htmLawed_setting', 'variable name to record <em>finalized</em> htmLawed settings', '25', 'd'=>1),
'style_pass'=>array('2', 'nil', 'do not look at <em>style</em> attribute values', 'nil'),
'tidy'=>array('3', '0', 'beautify/compact', '-1', '8', '1t1', 'format'),
'unique_ids'=>array('2', '1', 'unique <em>id</em> values', '0', '8', 'my_', 'prefix'),
'valid_xhtml'=>array('2', 'nil', 'auto-set various parameters for most valid XHTML', 'nil'),
'xml:lang'=>array('3', 'nil', 'auto-add <em>xml:lang</em> attribute', '0'),
);
foreach($cfg as $k=>$v){
 echo '<li>', $k, ': ';
 if(!empty($v[0])){ // input radio
  $j = $v[3];
  for($i = $j-1; ++$i < $v[0]+$v[3];++$j){
   echo '<input type="radio" name="h', $k, '" value="', $i, '"', (!isset($_POST['h'. $k]) ? ($v[1] == $i ? ' checked="checked"' : '') : ($_POST['h'. $k] == $i ? ' checked="checked"' : '')), (isset($v['d']) ? ' disabled="disabled"' : ''), ' />', $i, ' ';
  }
  if($v[1] == 'nil'){
   echo '<input type="radio" name="h', $k, '" value="nil"', ((!isset($_POST['h'. $k]) or $_POST['h'. $k] == 'nil') ? ' checked="checked"' : ''), (isset($v['d']) ? ' disabled="disabled"' : ''), ' />not set ';
  }
  if(!empty($v[4])){ // + input text box
   echo '<input type="radio" name="h', $k, '" value="', $j, '"', (((isset($_POST['h'. $k]) && $_POST['h'. $k] == $j) or (!isset($_POST['h'. $k]) && $j == $v[1])) ? ' checked="checked"' : ''), (isset($v['d']) ? ' disabled="disabled"' : ''), ' />';
   if(!is_array($v[4])){
    echo $v[6], ': <input type="text" size="', $v[4], '" name="h', $k. $j, '" value="', htmlspecialchars(isset($_POST['h'. $k. $j][0]) ? $_POST['h'. $k. $j] : $v[5]), '"', (isset($v['d']) ? ' disabled="disabled"' : ''), ' />';
   }
   else{
    foreach($v[4] as $z){
     echo ' ', $z[3], ': <input type="text" size="', $z[0], '" name="h', $k. $j. $z[1], '" value="', htmlspecialchars(isset($_POST['h'. $k. $j. $z[1]][0]) ? $_POST['h'. $k. $j. $z[1]] : $z[2]), '"', (isset($v['d']) ? ' disabled="disabled"' : ''), ' />';
    }    
   }
  }
 }
 elseif(ctype_digit($v[3])){ // input text
  echo '<input type="text" size="', $v[3], '" name="h', $k, '" value="', htmlspecialchars(isset($_POST['h'. $k][0]) ? $_POST['h'. $k] : $v[1]), '"', (isset($v['d']) ? ' disabled="disabled"' : ''), ' />';  
 }
 else{} // text-area
 echo ' <span class="help">', $v[2], '</span></li>';
}
echo '</ul></td></tr><tr><td><span style="vertical-align: top;" class="help" title="$spec argument: element-specific attribute rules">Spec:</span></td><td><textarea name="spec" id="spec" cols="70" rows="3" style="width:80%;">', htmlspecialchars((isset($_POST['spec']) ? $_POST['spec'] : '')), '</textarea></td></tr></table>';
?>

</div>
</form>

<?php
if($do){
 $cfg = array();
 foreach($_POST as $k=>$v){
  if($k[0] == 'h' && $v != 'nil'){
   $cfg[substr($k, 1)] = $v;
  }
 }

 if(isset($cfg['anti_link_spam']) && $cfg['anti_link_spam'] && (!empty($cfg['anti_link_spam11']) or !empty($cfg['anti_link_spam12']))){
  $cfg['anti_link_spam'] = array($cfg['anti_link_spam11'], $cfg['anti_link_spam12']);
 }
 unset($cfg['anti_link_spam11'], $cfg['anti_link_spam12']);
 if(isset($cfg['anti_mail_spam']) && $cfg['anti_mail_spam'] == 1){
  $cfg['anti_mail_spam'] = isset($cfg['anti_mail_spam1'][0]) ? $cfg['anti_mail_spam1'] : 0;
 }
 unset($cfg['anti_mail_spam11']);
 if(isset($cfg['deny_attribute']) && $cfg['deny_attribute'] == 1){
  $cfg['deny_attribute'] = isset($cfg['deny_attribute1'][0]) ? $cfg['deny_attribute1'] : 0;
 }
 unset($cfg['deny_attribute1']);
 if(isset($cfg['tidy']) && $cfg['tidy'] == 2){
  $cfg['tidy'] = isset($cfg['tidy2'][0]) ? $cfg['tidy2'] : 0;
 }
 unset($cfg['tidy2']);
 if(isset($cfg['unique_ids']) && $cfg['unique_ids'] == 2){
  $cfg['unique_ids'] = isset($cfg['unique_ids2'][0]) ? $cfg['unique_ids2'] : 1;
 }
 unset($cfg['unique_ids2']);
 unset($cfg['and_mark']); // disabling and_mark

 $cfg['show_setting'] = 'hlcfg';
 $st = microtime(); 
 $out = htmLawed($_POST['text'], $cfg, $_POST['spec']);
 $et = microtime();
 echo '<br /><a href="htmLawedTest.php" title="[toggle visibility] syntax-highlighted" onclick="javascript:toggle(\'inputR\'); return false;"><span class="notice">Input code &raquo;</span></a> <span class="help" title="tags estimated as half of total &gt; and &lt; chars; values may be inaccurate for non-ASCII text"><small><big>', strlen($_POST['text']), '</big> chars, ~<big>', ($tag = round((substr_count($_POST['text'], '>') + substr_count($_POST['text'], '<'))/2)), '</big> tag', ($tag > 1 ? 's' : ''), '</small>&nbsp;</span><div id="inputR" style="display: none;">', format($_POST['text']), '</div><script type="text/javascript">hl(\'inputR\');</script>', (!isset($_POST['text'][$_hlimit]) ? ' <a href="htmLawedTest.php" title="[toggle visibility] hexdump; non-viewable characters like line-returns are shown as dots" onclick="javascript:toggle(\'inputD\'); return false;"><span class="notice">Input binary &raquo;&nbsp;</span></a><div id="inputD" style="display: none;">'. hexdump($_POST['text']). '</div>' : ''), ' <a href="htmLawedTest.php" title="[toggle visibility] finalized internal settings as interpreted by htmLawed; for developers" onclick="javascript:toggle(\'settingF\'); return false;"><span class="notice">Finalized internal settings &raquo;&nbsp;</span></a> <div id="settingF" style="display: none;">$config: ', str_replace(array('    ', "\t", '  '), array('  ', '&nbsp;  ', '&nbsp; '), nl2br(htmlspecialchars(print_r($GLOBALS['hlcfg']['config'], true)))), '<br />$spec: ', str_replace(array('    ', "\t", '  '), array('  ', '&nbsp;  ', '&nbsp; '), nl2br(htmlspecialchars(print_r($GLOBALS['hlcfg']['spec'], true)))), '</div><script type="text/javascript">hl(\'settingF\');</script>', '<br /><a href="htmLawedTest.php" title="[toggle visibility] suitable for copy-paste" onclick="javascript:toggle(\'outputF\'); return false;"><span class="notice">Output &raquo;</span></a> <span class="help" title="approx., server-specific value excluding the \'include()\' call"><small>htmLawed processing time <big>', number_format(((substr($et,0,9)) + (substr($et,-10)) - (substr($st,0,9)) - (substr($st,-10))),4), '</big> s</small></span>', (($mem = memory_get_peak_usage()) !== false ? '<span class="help"><small>, peak memory usage <big>'. round(($mem-$pre_mem)/1048576, 2). '</big> <small>MB</small>' : ''), '</small></span><div id="outputF"  style="display: block;"><div><textarea id="text2" class="textarea" name="text2" rows="5" cols="100" style="width: 100%;">', htmlspecialchars($out), '</textarea></div><button type="button" onclick="javascript:document.getElementById(\'text2\').focus();document.getElementById(\'text2\').select()" title="select all to copy" style="float:right;">Select all</button>';
 if($_w3c_validate && $validation)
 {
?>
  
<button type="button" title="HTML 4.01 W3C online validation" style="float: right;" onclick="javascript: sndValidn('text2', 'html401'); return false;" onkeypress="javascript: sndValidn('text2', 'html401'); return false;">Check HTML</button>
<button type="button" title="XHTML 1.1 W3C online validation" style="float: right;" onclick="javascript: sndValidn('text2', 'xhtml110'); return false;" onkeypress="javascript: sndValidn('text2', 'xhtml110'); return false;">Check XHTML</button>
  
<?php
 }
 echo '</div><br /><a href="htmLawedTest.php" title="[toggle visibility] syntax-highlighted" onclick="javascript:toggle(\'outputR\'); return false;"><span class="notice">Output code &raquo;</span></a><div id="outputR" style="display: block;">', format($out), '</div><script type="text/javascript">hl(\'outputR\');</script>', (!isset($_POST['text'][$_hlimit]) ? ' <a href="htmLawedTest.php" title="[toggle visibility] hexdump; non-viewable characters like line-returns are shown as dots" onclick="javascript:toggle(\'outputD\'); return false;"><span class="notice">Output binary &raquo;</span></a><div id="outputD" style="display: none;">'. hexdump($out). '</div>' : ''), ' <a href="htmLawedTest.php" title="[toggle visibility] inline output-input diff; might not be perfectly accurate, semantically or otherwise " onclick="javascript:toggle(\'diff\'); diffLaunch(); return false;"><span class="notice">Diff &raquo;</span></a> <div id="diff" style="display: none;"></div><br /><a href="htmLawedTest.php" title="[toggle visibility] XHTML 1 Transitional doctype" onclick="javascript:toggle(\'outputH\'); return false;"><span class="notice">Output rendered &raquo;</span></a><div id="outputH" style="display: block;">', $out, '</div>';
}
else{
?>

<br />

<div class="help">Use with a Javascript- and cookie-enabled, relatively new version of a common browser. <em>Submitted input will also be HTML-rendered (XHTML 1) after htmLawed-filtering.</em>

<?php echo (file_exists('./htmLawed_TESTCASE.txt') ? '<br /><br />You can use text from <a href="htmLawed_TESTCASE.txt"><span class="notice">this collection of test-cases</span></a> in the input. Set the character encoding of the browser to Unicode/utf-8 before copying.' : ''); ?>

<br /><br />For anti-XSS tests, try the <a href="http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/htmLawedSafeModeTest.php"><span class="notice">special test-page</span></a> or see <a href="http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/rsnake/RSnakeXSSTest.htm"><span class="notice">these results</span></a>.

<br /><br /><small>Change <em>Encoding</em> to reflect the character encoding of the input text. Even then, it may not work or some characters may not display properly because of variable browser support and because of the form interface. Developers can write some PHP code to capture the filtered input to a file if this is important.
<br /><br />Refer to the htmLawed documentation (<a href="htmLawed_README.htm"><span class="notice">htm</span></a>/<a href="htmLawed_README.txt"><span class="notice">txt</span></a>) for details about <em>Settings</em>, and htmLawed's behavior and limitations. For <em>Settings</em>, incorrectly-specified values like regular expressions are silently ignored. One or more settings form-fields may have been disabled. Some characters are not allowed in the <em>Spec</em> field.


<br /><br />Hovering the mouse over some of the text can provide additional information in some browsers.</small>

<?php
if($_w3c_validate){
?>

<small><br /><br />Because of character-encoding issues, the W3C validator (anyway not perfect) may reject validation requests or invalidate otherwise-valid code, esp. if text was copy-pasted in the input box. Local applications like the <em>HTML Validator</em> Firefox browser add-on may be useful in such cases.</small>

<?php
}
?>

</div>

<?php
}
?>

</div>
</body>
</html>