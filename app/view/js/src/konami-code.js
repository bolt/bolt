/*
 * Konami Code For jQuery Plugin
 *
 * Using the Konami code, easily configure and Easter Egg for your page or any element on the page.
 *
 * Copyright 2011 - 2013 8BIT, http://8BIT.io
 * Released under the MIT License
 */
(function(e) {"use strict";e.fn.konami=function(t){var n,r,i,s,o,u,a,n=e.extend({},e.fn.konami.defaults,t);return this.each(function(){r=[38,38,40,40,37,39,37,39,66,65];i=[];e(window).keyup(function(e){s=e.keyCode?e.keyCode:e.which;i.push(s);if(10===i.length){o=!0;for(u=0,a=r.length;u<a;u++)r[u]!==i[u]&&(o=!1);o&&n.cheat();i=[]}})})};e.fn.konami.defaults={cheat:null}})(jQuery);
