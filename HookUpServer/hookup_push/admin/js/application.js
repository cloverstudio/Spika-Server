/*
    FLAT Theme v.1.4
    */

    function sidebarFluid(){
     if($("#left").hasClass("sidebar-fixed")){
        $("#left").removeClass("sidebar-fixed").css({
            "height": "auto",
            "top": "0",
            "left": "auto"
        });
    }
    if($("#navigation").hasClass("navbar-fixed-top")){
        $("#left").css("top", 40);
    }
    $("#left").getNiceScroll().resize().hide();
    $("#left").removeClass("hasScroll");
}

function sidebarFixed(){
    $("#left").addClass("sidebar-fixed");
    $("#left .ui-resizable-handle").css("top", 0);
    if($(window).scrollTop() == 0 ) $("#left").css("top", 40);
    if($("#content").hasClass("container")){
        $("#left").css("left", $("#content").offset().left);
    }
    $("#left").getNiceScroll().resize().show();
    initSidebarScroll();
}

function topbarFixed(){
    $("#content").addClass("nav-fixed");
    $("#navigation").addClass("navbar-fixed-top");
    if($("#left").css("top") == "0px"){
        $("#left").css("top", 40);
    }
}

function topbarFluid(){
    $("#content").removeClass("nav-fixed");
    $("#navigation").removeClass("navbar-fixed-top");
    if($("#left").css("top") == "40px" && !$('#left').hasClass("sidebar-fixed")){
        $("#left").css("top", 0);
    }
}

function versionFixed(){
    if($(window).width() >= 1200) {
        $("#content").addClass("container").removeClass("container-fluid");
        $("#navigation .container-fluid").addClass("container").removeClass("container-fluid");
        if($("#left").hasClass("sidebar-fixed")){
            $("#left").css("left", $("#content").offset().left);
        }
    }
}

function versionFluid(){
    $("#content").addClass("container-fluid").removeClass("container");
    $("#navigation .container").addClass("container-fluid").removeClass("container");
    $("#left").css("left", 0);
}

function slimScrollUpdate(elem, toBottom) {
    if(elem.length > 0){
        var height = parseInt(elem.attr('data-height')),
        vis = (elem.attr("data-visible") == "true") ? true : false,
        start = (elem.attr("data-start") == "bottom") ? "bottom" : "top";
        var opt = {
            height: height,
            color: "#666",
            start: start
        };
        if (vis) {
            opt.alwaysVisible = true;
            opt.disabledFadeOut = true;
        }
        if (toBottom !== undefined) opt.scrollTo = toBottom+"px";
        elem.slimScroll(opt);
    }
}

function destroySlimscroll(elem) { 
    elem.parent().replaceWith(elem); 
}

function initSidebarScroll(){
    getSidebarScrollHeight();
    if(!$("#left").hasClass("hasScroll")){
        $("#left").niceScroll({
            cursorborder: 0,
            cursorcolor: '#999',
            railoffset:{
                top:0,
                left:-2
            },
            autohidemode:false,
            horizrailenabled:false
        });
        $("#left").addClass("hasScroll");
            // if mobile prevent scroll
            $("#left").on('touchmove', function(e){
                e.preventDefault();
            });
        } else {
            $("#left").getNiceScroll().resize().show();
        }
    }

    function getSidebarScrollHeight(){
        var $el = $("#left"),
        $w = $(window),
        $nav = $("#navigation");
        var height = $w.height();

        if(($nav.hasClass("navbar-fixed-top") && $w.scrollTop() == 0) || $w.scrollTop() == 0) height -= 40;

        if($el.hasClass("sidebar-fixed") || $el.hasClass("mobile-show")){
            $el.height(height);
        }
    }

    function checkLeftNav(){
        var $w = $(window),
        $content = $("#content"),
        $left = $("#left");
        if($w.width() <= 767){
            if(!$left.hasClass("mobile-show")){
                $left.hide();
                $("#main").css("margin-left", 0 );
            }
            if($(".toggle-mobile").length == 0){
                $("#navigation .user").before('<a href="#" class="toggle-mobile"><i class="icon-reorder"></i></a>');
            }

            if($(".mobile-nav").length == 0){
                createSubNav();
            }
        } else {
            if(!$left.is(":visible") && !$left.hasClass("forced-hide") && !$("#content").hasClass("nav-hidden")){
                $left.show();
                $("#main").css("margin-left", $left.width());
            }

            $(".toggle-mobile").remove();
            $(".mobile-nav").removeClass("open");

            if($content.hasClass("forced-fixed")){
             $content.removeClass("nav-fixed");
             $("#navigation").removeClass("navbar-fixed-top");
         }

         if($w.width() < 1200) {
            if($("#navigation .container").length > 0){
            // it is fixed layout -> reset to fluid
            versionFluid();
        }
    }
}
}

function resizeHandlerHeight(){
    var wHeight = $(window).height(),
    minus = ($(window).scrollTop() == 0) ? 40 : 0;
    $("#left .ui-resizable-handle").height(wHeight-minus);
}

function toggleMobileNav(){
    var mobileNav = $(".mobile-nav");
    mobileNav.toggleClass("open");
    mobileNav.find(".open").removeClass("open");
}

function getNavElement(current){
    var currentText = $.trim(current.find(">a").text()),
    element = "";
    element += "<li><a href='" + current.find(">a").attr("href") + "'>" + currentText + "</a>";
    if(current.find(">.dropdown-menu").length > 0){
        element += getNav(current.find(">.dropdown-menu"));
    }
    element += "</li>";
    return element;
}

var nav = "";
function getNav(current){
    var currentNav = "";
    currentNav += "<ul>";
    current.find(">li").each(function(){
        currentNav += getNavElement($(this));
    });
    currentNav += "</ul>";
    nav = currentNav;
    return currentNav;
}

function createSubNav(){
    if($(".mobile-nav").length == 0){
        var original = $("#navigation .main-nav");
        // loop
        var current = original;
        getNav(current);
        $("#navigation").append(nav);
        $("#navigation > ul").last().addClass("mobile-nav");

        $(".mobile-nav > li > a").click(function(e){
            var el = $(this);
            $("#navigation").getNiceScroll().resize().show();
            if(el.next().length !== 0){
                e.preventDefault();

                var sub = el.next();
                el.parents(".mobile-nav").find(".open").not(sub).each(function(){
                    var t = $(this);
                    t.removeClass("open");
                    t.prev().find("i").removeClass("icon-angle-down").addClass("icon-angle-left");
                });
                sub.toggleClass("open");
                el.find("i").toggleClass('icon-angle-left').toggleClass("icon-angle-down");
            }
        });
    }
}

function hideNav(){
    $("#left").toggle().toggleClass("forced-hide");
    if($("#left").is(":visible")) {
        $("#main").css("margin-left", $("#left").width());
    } else {
        $("#main").css("margin-left", 0);
    }
}

function scrolledClone($el, $cloned){
    $cloned.remove();
    $el.parent().removeClass("open");
}

function resizeContent(){
    if($("#main").height() < $(window).height()){
        var height = 40;
        if($("#footer").length > 0) {
            height += $("#footer").outerHeight();
        }
        $("#content").css({
            "min-height": "auto",
            "height": $(window).height() - height
        });
    }
}

$(document).ready(function () {

    resizeContent();

    if(($("#left").height() > $('#main').height()) && ($("#main").height() < $(window).height())){
        $("#left").addClass("full");
    }

    if($("#left").height() < $(window).height() && !$("#left").hasClass("force-full")){
        $("#left").removeClass("full");
    }

    if($(".gallery-dynamic").length > 0){
        $(".gallery-dynamic").imagesLoaded(function(){
            $(".gallery-dynamic").masonry({
                itemSelector: 'li',
                columnWidth: 201,
                isAnimated: true
            });
        });
    }

    $(".gototop").click(function(e){
        e.preventDefault();
        $("html, body").animate({ 
            scrollTop: 0 
        }, 600);
    });

    if($("body").attr("data-mobile-sidebar") == "slide"){
        $("body").touchwipe({
            wipeRight: function(){
                $("#left").show().addClass("mobile-show");
                initSidebarScroll();
            },
            wipeLeft:function(){
                $("#left").hide().removeClass("mobile-show");
            },
            preventDefaultEvents: false
        });
    }

    if($("body").attr("data-mobile-sidebar") == "button"){
        $(".mobile-sidebar-toggle").click(function(e){
            e.preventDefault();
            $("#left").toggle().toggleClass("mobile-show");
            initSidebarScroll();
        });
    }

    $('.main-nav > li, .subnav-menu > li').hover(function() { 
        if($(this).attr("data-trigger") == "hover"){
            if($(this).parents(".subnav-menu").length > 0 && $("#left").hasClass("sidebar-fixed")){
                $(this).find(">a").trigger("click");
            } else {
                $(this).closest('.dropdown-menu').stop(true, true).show(); 
                $(this).addClass('open'); 
            }
        }
    }, function() { 
        if($(this).attr("data-trigger") == "hover"){
            $(this).closest('.dropdown-menu').stop(true, true).hide(); 
            $(this).removeClass('open'); 
        }
    });

    $(".subnav-menu > li > a[data-toggle=dropdown]").click(function(){
        // Clone dropdown menu to body
        var $el = $(this);
        if($("#left").hasClass("sidebar-fixed") || $("#left").hasClass("mobile-show")){
            // Remove open clones
            $('.cloned').remove();
            var $ulToClone = $el.next();
            var offset = $el.offset();
            var $cloned = $ulToClone.clone().css({
                top: offset.top,
                left: offset.left + $("#left").width()
            }).show().addClass("cloned");
            $("body").append($cloned);
            $ulToClone.hide();
            $("#left").scroll(function(){
                scrolledClone($el, $cloned);
            });
            $(window).scroll(function(){
                scrolledClone($el, $cloned);
            });

            // if($("#left").hasClass("mobile-show")){
                // close when clicked
                $("body").click(function(e){
                    var target = $(e.target);
                    if(target.parents(".cloned").length == 0 && target.attr("data-toggle") != "dropdown"){
                        // close all
                        $el.parent().removeClass("open");
                        $cloned.remove();
                        console.log("ASD");
                    }
                });
            // }

            // $("body").on("mouseleave", '.cloned', function(){
            //     $el.parent().removeClass("open");
            //     $cloned.remove();
            // });
}
});

$('body').on('click',".change-input", function(e){
    e.preventDefault();
    var $el = $(this);
    var $inputToClone = $el.parent().prev(),
    $parentCloned = $el.parent().clone();
    $parentCloned.html($inputToClone.clone().val(""));
    $inputToClone.after($parentCloned);
    $el.addClass("btn-satgreen update-input").removeClass("btn-grey-4 change-input").text("Update");
});

$('body').on("click", '.update-input', function(e){
    e.preventDefault();
    var $el = $(this);
    var $parent = $el.parent();
    $el.after('<span><i class="icon-spinner icon-spin"></i>Updating...</span>');
    setTimeout(function(){
        $parent.find("span").remove();
        $parent.prev().slideUp(200, function(){
            $parent.prev().remove();
            $el.removeClass("update-input btn-satgreen").addClass("btn-grey-4 change-input").text("Change");
        });
    }, 1000);
});

$(".subnav-hidden").each(function(){
    if($(this).find(".subnav-menu").is(":visible")) $(this).find(".subnav-menu").hide();
});

setTimeout(function(){
    slimScrollUpdate($(".messages").parent(), 9999);
}, 1000);

createSubNav();
    // hide breadcrumbs
    $(".breadcrumbs .close-bread > a").click(function (e) {
        e.preventDefault();
        $(".breadcrumbs").fadeOut();
    });

    $("#navigation").on('click', '.toggle-mobile' , function(e){
        e.preventDefault();
        toggleMobileNav();
    });

    $(".content-slideUp").click(function (e) {
        e.preventDefault();
        var $el = $(this),
        content = $el.parents('.box').find(".box-content");
        content.slideToggle('fast', function(){
           $el.find("i").toggleClass('icon-angle-up').toggleClass("icon-angle-down");
           if(!$el.find("i").hasClass("icon-angle-up")){
            if(content.hasClass('scrollable')) slimScrollUpdate(content);
        } else {
            if(content.hasClass('scrollable')) destroySlimscroll(content);
        }
    });
    });

    $(".content-remove").click(function (e) {
        e.preventDefault();
        var $el = $(this);
        var spanElement = $el.parents("[class*=span]");
        var spanWidth = parseInt(spanElement.attr('class').replace("span", "")),
        previousElement = (spanElement.prev().length > 0) ? spanElement.prev() : spanElement.next();
        if(previousElement.length > 0){
            var prevSpanWidth = parseInt(previousElement.attr("class").replace("span", ""));
        }
        bootbox.animate(false);
        bootbox.confirm("Do you really want to remove the widget <strong>" + $el.parents(".box-title").find("h3").text() + "</strong>?", "Cancel", "Yes, remove", function (r) {
            if (r){
                $el.parents('[class*=span]').remove();
                if(previousElement.length > 0){
                    previousElement.removeClass("span"+prevSpanWidth).addClass("span"+(prevSpanWidth+spanWidth));
                }
            }
        });
    });

    $(".content-refresh").click(function (e) {
        e.preventDefault();
        var $el = $(this);
        $el.find("i").addClass("icon-spin");
        setTimeout(function () {
            $el.find("i").removeClass("icon-spin");
        }, 2000);
    });

    if($('#vmap').length > 0)
    {
     $('#vmap').vectorMap({
        map: 'world_en',
        backgroundColor: null,
        color: '#ffffff',
        hoverOpacity: 0.7,
        selectedColor: '#2d91ef',
        enableZoom: true,
        showTooltip: false,
        values: sample_data,
        scaleColors: ['#8cc3f6', '#5c86ac'],
        normalizeFunction: 'polynomial',
        onRegionClick: function(){
            alert("This Region has "+(Math.floor(Math.random() * 10) + 1) + " users!");
        }
    });
 }

 $(".custom-checkbox").each(function () {
    var $el = $(this);
    if ($el.hasClass("checkbox-active")) {
        $el.find("i").toggleClass("icon-check-empty").toggleClass("icon-check");
    }
    $el.bind('click', function (e) {
        e.preventDefault();
        $el.find("i").toggleClass("icon-check-empty").toggleClass("icon-check");
        $el.toggleClass("checkbox-active");
    });
});

   // task-list
   $(".tasklist").on('click', "li", function(e){
    var $el = $(this),
    $checkbox = $(this).find('input[type=checkbox]').first();
    $el.toggleClass('done');

    if(e.target.nodeName == 'LABEL'){
        e.preventDefault();
    }

    if(e.target.nodeName != "INS" && e.target.nodeName != 'INPUT'){
        $checkbox.prop('checked', !($checkbox.prop('checked')));
        $(".tasklist input").iCheck("update");
    }
});

   $(".tasklist").on("is.Changed", 'input[type=checkbox]', function(){
    $(this).parents("li").toggleClass("done");
});

   if($("#new-task .select2-me").length > 0){
    function formatIcons(option){
        if (!option.id) return option.text; 
        return "<i class='" + option.text +"'></i> ." + option.text;
    }
    $("#new-task .select2-me").select2({
        formatResult: formatIcons,
        formatSelection:formatIcons,
        escapeMarkup: function(m) { return m; }
    });
}

$(".tasklist").on('click', '.task-bookmark', function(e){
    var $el = $(this),
    $lielement = $(this).parents('li'),
    $ulelement = $(this).parents('ul');
    e.preventDefault();
    e.stopPropagation();
    $lielement.toggleClass('bookmarked');

    if($lielement.hasClass('bookmarked')){
        $lielement.fadeOut(200,function(){
            $lielement.prependTo($ulelement).fadeIn();
        });
    }else{
        if($ulelement.find('.bookmarked').length > 0){
            $lielement.fadeOut(200,function(){
                $lielement.insertAfter($ulelement.find('.bookmarked').last()).fadeIn();
            });
        }else{
            $lielement.fadeOut(200,function(){
                $lielement.prependTo($ulelement).fadeIn();
            });
        }
    }
});

$(".tasklist").on('click', '.task-delete', function(e){
    e.preventDefault();
    e.stopPropagation();
    var $el = $(this);
    $el.parents("li").fadeOut();
});

$(".tasklist").sortable({
    items: "li",
    opacity: 0.7,
    placeholder: 'widget-placeholder-2',
    forcePlaceholderSize: true,
    tolerance: "pointer"
});

$(".sortable-box").sortable({
    connectWith: ".box",
    items: ".box",
    opacity: 0.7,
    placeholder: 'widget-placeholder',
    forcePlaceholderSize: true,
    tolerance: "pointer"
});

$(".toggle-subnav").click(function (e) {
    e.preventDefault();
    var $el = $(this);
    $el.parents(".subnav").toggleClass("subnav-hidden").find('.subnav-menu,.subnav-content').slideToggle("fast");
    $el.find("i").toggleClass("icon-angle-down").toggleClass("icon-angle-right");

    if($("#left").hasClass("mobile-show") || $("#left").hasClass("sidebar-fixed")){
        getSidebarScrollHeight();
        $("#left").getNiceScroll().resize().show();
    }
});

$("#left").sortable({
    items:".subnav",
    placeholder: "widget-placeholder",
    forcePlaceholderSize: true,
    axis: "y",
    handle:".subnav-title",
    tolerance:"pointer"
});

if($(".scrollable").length > 0){
    $('.scrollable').each(function () {
        var $el = $(this);
        var height = parseInt($el.attr('data-height')),
        vis = ($el.attr("data-visible") == "true") ? true : false,
        start = ($el.attr("data-start") == "bottom") ? "bottom" : "top";
        var opt = {
            height: height,
            color: "#666",
            start: start,
            allowPageScroll:true
        };
        if (vis) {
            opt.alwaysVisible = true;
            opt.disabledFadeOut = true;
        }
        $el.slimScroll(opt);
    });
}

$(".new-task-form").submit(function(e){
    e.preventDefault();
    $("#new-task").modal("hide");
    var $form = $(this),
    $tasklist = $(".tasklist");
    var $icon = $form.find("select[name=icons]"),
    $name = $form.find("input[name=task-name]"),
    $bookmark = $form.find("input[name=task-bookmarked]");
    if($name.val() != ""){
        var elementToAdd = "";
        ($bookmark.is(":checked")) ? elementToAdd += "<li class='bookmarked'>" : elementToAdd += "<li>";

        elementToAdd += '<div class="check"><input type="checkbox" class="icheck-me" data-skin="square" data-color="blue"></div><span class="task"><i class="' + $icon.select2("val") + '"></i><span>' + $name.val() + '</span></span><span class="task-actions"><a href="#" class="task-delete" rel="tooltip" title="Delete that task"><i class="icon-remove"></i></a><a href="#" class="task-bookmark" rel="tooltip" title="Mark as important"><i class="icon-bookmark-empty"></i></a></span></li>';

        if($tasklist.find(".bookmarked").length > 0){
            if($bookmark.is(":checked")){
                $tasklist.find(".bookmarked").first().before(elementToAdd);
            } else {
                $tasklist.find(".bookmarked").last().after(elementToAdd);
            }
        } else {
            $tasklist.prepend(elementToAdd);
        }  

        icheck();
        $tasklist.find("[rel=tooltip]").tooltip();

        $icon.select2("val", 'icon-adjust');
        $name.val("");
        $bookmark.prop("checked", false);
    }
});

$("#message-form .text input").on("focus", function (e) {
    var $el = $(this);
    $el.parents(".messages").find(".typing").addClass("active").find(".name").html("John Doe");
    slimScrollUpdate($el.parents(".scrollable"), 100000);
});

$("#message-form .text input").on("blur", function (e) {
    var $el = $(this);
    $el.parents(".messages").find(".typing").removeClass("active");
    slimScrollUpdate($el.parents(".scrollable"), 100000);
});

if($(".jq-datepicker").length > 0){
    $(".jq-datepicker").datepicker({
        showOtherMonths: true,
        selectOtherMonths: true,
        prevText: "",
        nextText: ""
    });
}

if($(".spark-me").length > 0){
    $(".spark-me").sparkline("html", {
        height: '25px',
        enableTagOptions: true
    });
}


if(!$("#left").hasClass("no-resize")){
    $("#left").resizable({
        minWidth: 60,
        handles: "e",
        resize: function (event, ui) {
            var searchInput = $('.search-form .search-pane input[type=text]'),
            content = $("#main");
            searchInput.css({
                width: ui.size.width - 55
            });
            if(Math.abs(200 - ui.size.width) <= 20){
                $("#left").css("width", 200);
                searchInput.css("width", 145 );
                content.css("margin-left", 200);
            } else{
                content.css("margin-left", $("#left").width());
            }

        },
        stop: function(){
            $("#left .ui-resizable-handle").css("background","none");
        },
        start: function(){
            $("#left .ui-resizable-handle").css("background","#aaa");
        }
    });
}

$("[rel=popover]").popover();

$('.toggle-nav').click(function(e){
    e.preventDefault();
    hideNav();
});

if($("#content").hasClass("nav-hidden")){
    hideNav();
}

$('.table-mail .sel-star').click(function(e){
    e.preventDefault();
    e.stopPropagation();
    var $el = $(this);
    $el.toggleClass('active');
});

$('.table .sel-all').change(function(e){
    e.preventDefault();
    e.stopPropagation();
    var $el = $(this);
    $el.parents('.table').find("tbody .selectable").prop('checked', (el.prop('checked')));
});

$('.table-mail > tbody > tr').click(function(e){
    var $el = $(this);
    var checkbox = el.find('.table-checkbox > input');
    $el.toggleClass('warning');
    
    if(e.target.nodeName != 'INPUT')
    {
        checkbox.prop('checked', !(checkbox.prop('checked')));
    }
});

// set resize handler to corret height
resizeHandlerHeight();

$(".table .alpha").click(function (e) {
    e.preventDefault();
    var $el = $(this),
    str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
    elements = "",
    available = [];
    $el.parents().find('.alpha .alpha-val span').each(function(){
        available.push($(this).text());
    });

    elements += "<li class='active'><span>All</span></li>";

    for(var i=0; i<str.length; i++)
    {   
        var active = ($.inArray(str.charAt(i), available) != -1) ? " class='active'" : "";
        elements += "<li"+active+"><span>"+str.charAt(i)+"</span></li>";
    }
    $el.parents(".table").before("<div class='letterbox'><ul class='letter'>"+elements+"</ul></div>");
    $(".letterbox .letter > .active").click(function(){
        var $el = $(this);
        if($el.text() != "All"){
            slimScrollUpdate($el.parents(".scrollable"), 0);
            var scrollToElement = $el.parents(".box-content").find(".table .alpha:contains('"+$el.text()+"')");
            slimScrollUpdate($el.parents(".scrollable"), scrollToElement.position().top);
        }
        $el.parents(".letterbox").remove();
    });
});

$(".theme-colors > li > span").hover(function(e){
    var $el = $(this),
    body = $('body');
    body.attr("class","").addClass("theme-"+$el.attr("class"));
}, function(){
    var $el = $(this),
    body = $('body');
    if(body.attr("data-theme") !== undefined) {
        body.attr("class","").addClass(body.attr("data-theme"));
    } else {
        body.attr("class","");
    }
}).click(function(){
   var $el = $(this);
   $("body").addClass("theme-"+$el.attr("class")).attr("data-theme","theme-"+$el.attr("class"));
});

$(".version-toggle > a").click(function(e){
    e.preventDefault();
    e.stopPropagation();
    var $el = $(this);
    var parent = $el.parent();
    if(!$el.hasClass("active")){
        parent.find(".active").removeClass("active");
        $el.addClass("active");
    }

    if($el.hasClass("set-fixed")){
        versionFixed();
    } else {
        versionFluid();
    }
});

$(".topbar-toggle > a").click(function(e){
    e.preventDefault();
    e.stopPropagation();
    var $el = $(this);
    var $parent = $el.parent();
    if(!$el.hasClass("active")){
        $parent.find(".active").removeClass("active");
        $el.addClass("active");
    }

    if($el.hasClass("set-topbar-fixed")){
        topbarFixed();
    } else {
        topbarFluid();
    }
});

$(".sidebar-toggle > a").click(function(e){
    e.preventDefault();
    e.stopPropagation();
    var $el = $(this);
    var $parent = $el.parent();
    if(!$el.hasClass("active")){
        $parent.find(".active").removeClass("active");
        $el.addClass("active");
    }  

    $(".search-form .search-pane input").attr("style", "");
    $("#main").attr("style","");

    if($el.hasClass("set-sidebar-fixed")){
        sidebarFixed();
    } else {
       sidebarFluid();
   }
});


$(".del-gallery-pic").click(function(e){
    e.preventDefault();
    var $el = $(this);
    var $parent = $el.parents("li");
    $parent.fadeOut(400, function(){
        $parent.remove();
    });
});

checkLeftNav();

 // check layout
 if($("body").attr("data-layout") == "fixed"){
     versionFixed();
 }

 if($("body").attr("data-layout-topbar") == "fixed"){
    topbarFixed();
}

if($("body").attr("data-layout-sidebar") == "fixed"){
    sidebarFixed();
}
});

$.fn.scrollBottom = function() { 
  return $(document).height() - this.scrollTop() - this.height(); 
};

$(window).scroll(function(e){
    var height = 0,
    $w = $(window),
    $d = $(document);

    if($w.scrollTop() == 0 || $("#left").hasClass("sidebar-fixed"))
    {
        $("#left .ui-resizable-handle").css("top", height);
    } else {
        if($w.scrollTop() + $("#left .ui-resizable-handle").height() <= $d.height()) {
            height = $w.scrollTop() - 40;
        } else {
            height = $d.height() - $("#left .ui-resizable-handle").height() - 40;
        }
        $("#left .ui-resizable-handle").css("top", height);
    }

    if(!$("#content").hasClass("nav-fixed") && $("#left").hasClass("sidebar-fixed")){
        if($w.scrollTop() < 40){
            $("#left").css("top", 40 - $w.scrollTop());
        } else {
            $("#left").css("top", 0);
        }
    }


    
    getSidebarScrollHeight();
    resizeHandlerHeight();
});

$(window).resize(function(e){
    checkLeftNav();
    getSidebarScrollHeight();
    resizeContent();
    resizeHandlerHeight();
});