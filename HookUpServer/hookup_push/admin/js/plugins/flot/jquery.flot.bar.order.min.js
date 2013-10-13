/*
 * Flot plugin to order bars side by side.
 * 
 * Released under the MIT license by Benjamin BUFFET, 20-Sep-2010.
 *
 * This plugin is an alpha version.
 *
 * To activate the plugin you must specify the parameter "order" for the specific serie :
 *
 *  $.plot($("#placeholder"), [{ data: [ ... ], bars :{ order = null or integer }])
 *
 * If 2 series have the same order param, they are ordered by the position in the array;
 *
 * The plugin adjust the point by adding a value depanding of the barwidth
 * Exemple for 3 series (barwidth : 0.1) :
 *
 *          first bar dÃ©calage : -0.15
 *          second bar dÃ©calage : -0.05
 *          third bar dÃ©calage : 0.05
 *
 */(function(e){function t(e){function u(e,r,s){var o=null;if(a(r)){v(r);f(e);c(e);d(r);if(n>=2){var u=m(r),l=0,h=g();y(u)?l=-1*b(t,u-1,Math.floor(n/2)-1)-h:l=b(t,Math.ceil(n/2),u-2)+h+i*2;o=w(s,r,l);s.points=o}}return o}function a(e){return e.bars!=null&&e.bars.show&&e.bars.order!=null}function f(e){var t=o?e.getPlaceholder().innerHeight():e.getPlaceholder().innerWidth(),n=o?l(e.getData(),1):l(e.getData(),0),r=n[1]-n[0];s=r/t}function l(e,t){var n=new Array;for(var r=0;r<e.length;r++){n[0]=e[r].data[0][t];n[1]=e[r].data[e[r].data.length-1][t]}return n}function c(e){t=h(e.getData());n=t.length}function h(e){var t=new Array;for(var n=0;n<e.length;n++)e[n].bars.order!=null&&e[n].bars.show&&t.push(e[n]);return t.sort(p)}function p(e,t){var n=e.bars.order,r=t.bars.order;return n<r?-1:n>r?1:0}function d(e){r=e.bars.lineWidth?e.bars.lineWidth:2;i=r*s}function v(e){e.bars.horizontal&&(o=!0)}function m(e){var n=0;for(var r=0;r<t.length;++r)if(e==t[r]){n=r;break}return n+1}function g(){var e=0;n%2!=0&&(e=t[Math.ceil(n/2)].bars.barWidth/2);return e}function y(e){return e<=Math.ceil(n/2)}function b(e,t,n){var r=0;for(var s=t;s<=n;s++)r+=e[s].bars.barWidth+i*2;return r}function w(e,t,n){var r=e.pointsize,i=e.points,s=0;for(var u=o?1:0;u<i.length;u+=r){i[u]+=n;t.data[s][3]=i[u];s++}return i}var t,n,r,i,s=1,o=!1;e.hooks.processDatapoints.push(u)}var n={series:{bars:{order:null}}};e.plot.plugins.push({init:t,options:n,name:"orderBars",version:"0.2"})})(jQuery);