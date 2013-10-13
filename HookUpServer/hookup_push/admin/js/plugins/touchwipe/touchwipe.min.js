/**
 * jQuery Plugin to obtain touch gestures from iPhone, iPod Touch and iPad, should also work with Android mobile phones (not tested yet!)
 * Common usage: wipe images (left and right to show the previous or next image)
 * 
 * @author Andreas Waltl, netCU Internetagentur (http://www.netcu.de)
 * @version 1.1.1 (9th December 2010) - fix bug (older IE's had problems)
 * @version 1.1 (1st September 2010) - support wipe up and wipe down
 * @version 1.0 (15th July 2010)
 */(function(e){e.fn.touchwipe=function(t){var n={min_move_x:20,min_move_y:20,wipeLeft:function(){},wipeRight:function(){},wipeUp:function(){},wipeDown:function(){},preventDefaultEvents:!0};t&&e.extend(n,t);this.each(function(){function i(){this.removeEventListener("touchmove",s);e=null;r=!1}function s(s){n.preventDefaultEvents&&s.preventDefault();if(r){var o=s.touches[0].pageX,u=s.touches[0].pageY,a=e-o,f=t-u;if(Math.abs(a)>=n.min_move_x){i();a>0?n.wipeLeft():n.wipeRight()}else if(Math.abs(f)>=n.min_move_y){i();f>0?n.wipeDown():n.wipeUp()}}}function o(n){if(n.touches.length==1){e=n.touches[0].pageX;t=n.touches[0].pageY;r=!0;this.addEventListener("touchmove",s,!1)}}var e,t,r=!1;"ontouchstart"in document.documentElement&&this.addEventListener("touchstart",o,!1)});return this}})(jQuery);