/*
 * frankenMenu v1.0 // Karmabunny Web Design // built by Luke Underwood
 */

;$(function() {

    var frankenMenu = $.fn.frankenMenu = function(options) {

        var $this = $(this);
        var opts = $.extend( {}, $.fn.frankenMenu.defaults, options );
        var menuVersion = '';
        var mobileMenuVisible = false;
        var $html = $("html");
        var $navList = $("#frankenmenu-list");


        // Handles console logs
        var frankenLog = function(output, type) {
            if(type === "error") {
                var bg = "#ffc1c1",
                color = "#660202",
                type = "ERROR";
            } else if(type === "warning") {
                var bg = "#fffca5",
                color = "#666302",
                type = "WARNING";
            }
            console.log('%cFrankenMenu ' + type + ': ' + output, 'background: ' + bg + '; color: ' + color + '; padding: 0 2px;');
        }


        // Set mobile menu position
        if(opts.mobileMenuPosition === "left" || opts.mobileMenuPosition === "right") {
            $html.addClass("frankenmenu-mobile-pos-"+opts.mobileMenuPosition);
        } else {
            frankenLog('Bad value "' + opts.mobileMenuPosition + '" set for "mobileMenuPosition". Valid options are "left" or "right"', "error");
        }

        // Add class so we know megamenu is active
        if(opts.megaMenu){
            $html.addClass("frankenmenu-mega");
        }

        // Add class for menu btn text
        if(opts.mobileMenuText){
            $html.addClass("frankenmenu-button-text");
        }

        // Alert if devMode active
        if(opts.devMode) {
            frankenLog("FrankenMenu developer mode is active. Ensure this is turned off on the live site", "warning");
        }

        // Init expand buttons for mobile menu
        var initExpandButtons = function() {

                $(".menu-current-item-ancestor").addClass("menu-item-submenu-open");

                $("<button class='submenu-toggle'><span class='submenu-toggle-label -vis-hidden'>Toggle submenu</span></button>").appendTo($('#frankenmenu-list .menu-item').has('.sub-menu, .mega-menu'));

                $('.submenu-toggle').click(function() {
                    var $this = $(this);
                    var $menuItem = $this.closest(".menu-item");
                    if(!$menuItem.hasClass('menu-item-submenu-open')) {
                        $menuItem.addClass('menu-item-submenu-open');
                        $this.siblings('.sub-menu, .mega-menu').slideDown(opts.mobileTransitionSpeedIn);
                    } else if($menuItem.hasClass('menu-item-submenu-open')) {
                        $menuItem.removeClass('menu-item-submenu-open');
                        $this.siblings('.sub-menu, .mega-menu').stop().slideUp(opts.mobileTransitionSpeedOut);
                    }
                });

        }

        // Destroy expand buttons for mobile menu
        var destroyExpandButtons = function() {
            $(".menu-current-item-ancestor").removeClass("menu-item-submenu-open");
            $('.submenu-toggle').remove();
        }


        // Initialise Desktop menu
        var subnavCloned = false;
        var initDesktopMenu = function() {

            if(!opts.megaMenu) {
                // Dropdown menu
                $navList = $navList.superfish(sfOptions);
            } else if(opts.megaMenu) {
                // Mega menu
                $navList = $navList.superfish(sfOptionsMegaMenu);
            }

            if(!subnavCloned) {
                // Move subnav items
                $("#frankenmenu-subnav").append('<ul id="frankenmenu-subnav-list"></ul>');
                $("#frankenmenu-list .menu-item-subnav").clone().appendTo("#frankenmenu-subnav-list");
                subnavCloned = true;
            }

            if(opts.devMode) {
                // Dev mode to force show/hide submenus/megamenus
                $(window).keypress(function(e) {
                    if(e.which === 104) {
                        $(".frankenhover").addClass("frankenhold");
                    } else if(e.which === 114) {
                        $(".frankenhold").removeClass("frankenhold");
                    }
                });
            }

            menuVersion = "desktop";

        }


        // Destroy Desktop menu
        var destroyDesktopMenu = function() {
            $navList.superfish('destroy');
        }


        // Initialise Mobile menu
        var frankenMoved = false;
        var initMobileMenu = function() {

            $('<div id="desktop-menu-anchor"></div>').insertAfter($this);
            $this.insertAfter("#mobile-header");
            initExpandButtons();

            if(!frankenMoved) {
                // Move elements into mobile header
                $(".frankenmove").clone().removeClass("frankenmove").addClass("frankenmoved").appendTo("#mobile-header .container");
                frankenMoved = true;
            }

            menuVersion = "mobile";

        }

        // Destroy Mobile menu
        var destroyMobileMenu = function() {

            destroyExpandButtons();
            $this.insertAfter("#desktop-menu-anchor");
            $("#desktop-menu-anchor").remove();
            // Strip inline styles from expanded submenus
            $("#frankenmenu-list .sub-menu").removeAttr('style');

        }

        // Checks if click is out of bounds mobile menu
        var checkMobileBoundary = function(e){
            if(!$(e.target).is("#frankenmenu") && !$(e.target).parents("#frankenmenu").length && !$(e.target).is("#mobile-header") && !$(e.target).parents("#mobile-header").length) {
                toggleMobileMenu();
                e.preventDefault();
            }
        }

        var toggleMobileMenu = function(){
            $html.addClass("frankenmenu-mob-menu-animations");
            $html.toggleClass("frankenmenu-mob-menu-visible");
            mobileMenuVisible = !mobileMenuVisible;
            if(mobileMenuVisible) {
                $("body").on("click", checkMobileBoundary);
            } else {
                $("body").off("click", checkMobileBoundary);
            }
        }

        // Mobile menu button
        $("#mobile-menu-button").click(function(){
            toggleMobileMenu();
        });


        // Initialise the menu, depending on which type
        var frankenMenuInit = function() {

            if (window.innerWidth > opts.menuBreakpoint && menuVersion !== "desktop") {

                if(menuVersion === "mobile") {
                    destroyMobileMenu();
                }
                initDesktopMenu();

            } else if (window.innerWidth <= opts.menuBreakpoint && menuVersion !== "mobile") {

                if(menuVersion === "desktop") {
                    destroyDesktopMenu();
                }
                initMobileMenu();

            }
        }


        // Superfish options
        var sfOptions = {
            delay: opts.desktopHoverDelay,
            speed: opts.desktopTransitionSpeedIn,
            speedOut: opts.desktopTransitionSpeedOut,
            cssArrows: false,
            animation: opts.desktopAnimationIn,
            animationOut: opts.desktopAnimationOut,
            hoverClass: "frankenhover",
            popUpSelector: '.sub-menu,.mega-menu',
            onBeforeShow: function(){
                var $submenu = $(this);
                setTimeout(function(){
                    if($submenu.offset().left+$submenu.width() > window.innerWidth){
                        $submenu.addClass('submenu-right-align');
                    }
                }, 10);
            }
        }

        var sfOptionsMegaMenu = {
            delay: opts.desktopHoverDelay,
            speed: opts.desktopTransitionSpeedIn,
            speedOut: opts.desktopTransitionSpeedOut,
            cssArrows: false,
            hoverClass: "frankenhover",
            popUpSelector: '.sub-menu,.mega-menu',
        }


        $(window).on('load resize', function () {
            frankenMenuInit();
        });

        frankenMenuInit();

    }


    // Default options
    $.fn.frankenMenu.defaults = {
        menuBreakpoint: 600,
        devMode: false,

        // Desktop Menu Options
        megaMenu: false,
        desktopHoverDelay: 600,
        desktopAnimationIn: {
            opacity: "show"
        },
        desktopAnimationOut: {
            opacity: "hide"
        },
        desktopTransitionSpeedIn: 300,
        desktopTransitionSpeedOut: 200,

        // Mobile Menu Options
        mobileMenuText: false,
        mobileMenuPosition: "right",
        mobileTransitionSpeedIn: 300,
        mobileTransitionSpeedOut: 300,
    }

});


/*
 * hoverIntent v1.8.1 // 2014.08.11 // jQuery v1.9.1+
 * http://cherne.net/brian/resources/jquery.hoverIntent.html
 *
 * You may use hoverIntent under the terms of the MIT license. Basically that
 * means you are free to use hoverIntent as long as this header is left intact.
 * Copyright 2007, 2014 Brian Cherne
 */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery"],e):jQuery&&!jQuery.fn.hoverIntent&&e(jQuery)}(function(e){"use strict";var t,n,o={interval:100,sensitivity:6,timeout:0},i=0,u=function(e){t=e.pageX,n=e.pageY},r=function(e,o,i,v){return Math.sqrt((i.pX-t)*(i.pX-t)+(i.pY-n)*(i.pY-n))<v.sensitivity?(o.off(i.event,u),delete i.timeoutId,i.isActive=!0,e.pageX=t,e.pageY=n,delete i.pX,delete i.pY,v.over.apply(o[0],[e])):(i.pX=t,i.pY=n,i.timeoutId=setTimeout(function(){r(e,o,i,v)},v.interval),void 0)},v=function(e,t,n,o){return delete t.data("hoverIntent")[n.id],o.apply(t[0],[e])};e.fn.hoverIntent=function(t,n,a){var s=i++,d=e.extend({},o);e.isPlainObject(t)?(d=e.extend(d,t),e.isFunction(d.out)||(d.out=d.over)):d=e.isFunction(n)?e.extend(d,{over:t,out:n,selector:a}):e.extend(d,{over:t,out:t,selector:n});var f=function(t){var n=e.extend({},t),o=e(this),i=o.data("hoverIntent");i||o.data("hoverIntent",i={});var a=i[s];a||(i[s]=a={id:s}),a.timeoutId&&(a.timeoutId=clearTimeout(a.timeoutId));var f=a.event="mousemove.hoverIntent.hoverIntent"+s;if("mouseenter"===t.type){if(a.isActive)return;a.pX=n.pageX,a.pY=n.pageY,o.off(f,u).on(f,u),a.timeoutId=setTimeout(function(){r(n,o,a,d)},d.interval)}else{if(!a.isActive)return;o.off(f,u),a.timeoutId=setTimeout(function(){v(n,o,a,d.out)},d.timeout)}};return this.on({"mouseenter.hoverIntent":f,"mouseleave.hoverIntent":f},d.selector)}});

/*
 * jQuery Superfish Menu Plugin - v1.7.5
 * Copyright (c) 2014 Joel Birch
 *
 * Dual licensed under the MIT and GPL licenses:
 *    http://www.opensource.org/licenses/mit-license.php
 *    http://www.gnu.org/licenses/gpl.html
 */

;(function(e,s){"use strict";var n=function(){var n={bcClass:"frankenmenu-breadcrumb",menuClass:"frankenmenu-js-enabled",anchorClass:"menu-item-link-with-submenu",menuArrowClass:"frankenmenu-arrows"},o=function(){var n=/iPhone|iPad|iPod/i.test(navigator.userAgent);return n&&e(s).load(function(){e("body").children().on("click",e.noop)}),n}(),t=function(){var e=document.documentElement.style;return"behavior"in e&&"fill"in e&&/iemobile/i.test(navigator.userAgent)}(),i=function(){return!!s.PointerEvent}(),r=function(e,s){var o=n.menuClass;s.cssArrows&&(o+=" "+n.menuArrowClass),e.toggleClass(o)},a=function(s,o){return s.find("li."+o.pathClass).slice(0,o.pathLevels).addClass(o.hoverClass+" "+n.bcClass).filter(function(){return e(this).children(o.popUpSelector).hide().show().length}).removeClass(o.pathClass)},l=function(e){e.children("a").toggleClass(n.anchorClass)},h=function(e){var s=e.css("ms-touch-action"),n=e.css("touch-action");n=n||s,n="pan-y"===n?"auto":"pan-y",e.css({"ms-touch-action":n,"touch-action":n})},u=function(s,n){var r="li:has("+n.popUpSelector+")";e.fn.hoverIntent&&!n.disableHI?s.hoverIntent(c,f,r):s.on("mouseenter.superfish",r,c).on("mouseleave.superfish",r,f);var a="MSPointerDown.superfish";i&&(a="pointerdown.superfish"),o||(a+=" touchend.superfish"),t&&(a+=" mousedown.superfish"),s.on("focusin.superfish","li",c).on("focusout.superfish","li",f).on(a,"a",n,p)},p=function(s){var n=e(this),o=n.siblings(s.data.popUpSelector);o.length>0&&o.is(":hidden")&&(n.one("click.superfish",!1),"MSPointerDown"===s.type||"pointerdown"===s.type?n.trigger("focus"):e.proxy(c,n.parent("li"))())},c=function(){var s=e(this),n=m(s);clearTimeout(n.sfTimer),s.siblings().superfish("hide").end().superfish("show")},f=function(){var s=e(this),n=m(s);o?e.proxy(d,s,n)():(clearTimeout(n.sfTimer),n.sfTimer=setTimeout(e.proxy(d,s,n),n.delay))},d=function(s){s.retainPath=e.inArray(this[0],s.$path)>-1,this.superfish("hide"),this.parents("."+s.hoverClass).length||(s.onIdle.call(v(this)),s.$path.length&&e.proxy(c,s.$path)())},v=function(e){return e.closest("."+n.menuClass)},m=function(e){return v(e).data("sf-options")};return{hide:function(s){if(this.length){var n=this,o=m(n);if(!o)return this;var t=o.retainPath===!0?o.$path:"",i=n.find("li."+o.hoverClass).add(this).not(t).removeClass(o.hoverClass).children(o.popUpSelector),r=o.speedOut;s&&(i.show(),r=0),o.retainPath=!1,o.onBeforeHide.call(i),i.stop(!0,!0).animate(o.animationOut,r,function(){var s=e(this);o.onHide.call(s)})}return this},show:function(){var e=m(this);if(!e)return this;var s=this.addClass(e.hoverClass),n=s.children(e.popUpSelector);return e.onBeforeShow.call(n),n.stop(!0,!0).animate(e.animation,e.speed,function(){e.onShow.call(n)}),this},destroy:function(){return this.each(function(){var s,o=e(this),t=o.data("sf-options");return t?(s=o.find(t.popUpSelector).parent("li"),clearTimeout(t.sfTimer),r(o,t),l(s),h(o),o.off(".superfish").off(".hoverIntent"),s.children(t.popUpSelector).attr("style",function(e,s){return s.replace(/display[^;]+;?/g,"")}),t.$path.removeClass(t.hoverClass+" "+n.bcClass).addClass(t.pathClass),o.find("."+t.hoverClass).removeClass(t.hoverClass),t.onDestroy.call(o),o.removeData("sf-options"),void 0):!1})},init:function(s){return this.each(function(){var o=e(this);if(o.data("sf-options"))return!1;var t=e.extend({},e.fn.superfish.defaults,s),i=o.find(t.popUpSelector).parent("li");t.$path=a(o,t),o.data("sf-options",t),r(o,t),l(i),h(o),u(o,t),i.not("."+n.bcClass).superfish("hide",!0),t.onInit.call(this)})}}}();e.fn.superfish=function(s){return n[s]?n[s].apply(this,Array.prototype.slice.call(arguments,1)):"object"!=typeof s&&s?e.error("Method "+s+" does not exist on jQuery.fn.superfish"):n.init.apply(this,arguments)},e.fn.superfish.defaults={popUpSelector:"ul,.sf-mega",hoverClass:"sfHover",pathClass:"overrideThisToUse",pathLevels:1,delay:800,animation:{opacity:"show"},animationOut:{opacity:"hide"},speed:"normal",speedOut:"fast",cssArrows:!0,disableHI:!1,onInit:e.noop,onBeforeShow:e.noop,onShow:e.noop,onBeforeHide:e.noop,onHide:e.noop,onIdle:e.noop,onDestroy:e.noop}})(jQuery,window);