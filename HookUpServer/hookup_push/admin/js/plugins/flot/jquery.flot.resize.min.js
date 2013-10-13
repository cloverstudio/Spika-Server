/*
Flot plugin for automatically redrawing plots when the placeholder
size changes, e.g. on window resizes.

It works by listening for changes on the placeholder div (through the
jQuery resize event plugin) - if the size changes, it will redraw the
plot.

There are no options. If you need to disable the plugin for some
plots, you can just fix the size of their placeholders.
*//* Inline dependency: 
 * jQuery resize event - v1.1 - 3/14/2010
 * http://benalman.com/projects/jquery-resize-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */(function(e,t,n){function c(){s=t[o](function(){r.each(function(){var t=e(this),n=t.width(),r=t.height(),i=e.data(this,a);(n!==i.w||r!==i.h)&&t.trigger(u,[i.w=n,i.h=r])});c()},i[f])}var r=e([]),i=e.resize=e.extend(e.resize,{}),s,o="setTimeout",u="resize",a=u+"-special-event",f="delay",l="throttleWindow";i[f]=250;i[l]=!0;e.event.special[u]={setup:function(){if(!i[l]&&this[o])return!1;var t=e(this);r=r.add(t);e.data(this,a,{w:t.width(),h:t.height()});r.length===1&&c()},teardown:function(){if(!i[l]&&this[o])return!1;var t=e(this);r=r.not(t);t.removeData(a);r.length||clearTimeout(s)},add:function(t){function s(t,i,s){var o=e(this),u=e.data(this,a);u.w=i!==n?i:o.width();u.h=s!==n?s:o.height();r.apply(this,arguments)}if(!i[l]&&this[o])return!1;var r;if(e.isFunction(t)){r=t;return s}r=t.handler;t.handler=s}}})(jQuery,this);(function(e){function r(n){function r(n,r){function i(){var r=n.getPlaceholder();if(r.width()==0||r.height()==0)return;++t;e.plot(r,n.getData(),n.getOptions());--t}t||n.getPlaceholder().resize(i)}n.hooks.bindEvents.push(r)}var t=0,n={};e.plot.plugins.push({init:r,options:n,name:"resize",version:"1.0"})})(jQuery);